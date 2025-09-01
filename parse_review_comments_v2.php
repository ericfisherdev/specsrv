#!/usr/bin/env php
<?php

// DTO for unified comment format
class ReviewComment {
    public string $type; // 'nitpick' or 'actionable' or 'ai_prompt'
    public string $file;
    public ?string $lineRange;
    public string $title;
    public string $description;
    public ?string $suggestedCode;
    public string $category;
    public int $priority;
    
    public function __construct(
        string $type,
        string $file,
        ?string $lineRange,
        string $title,
        string $description,
        ?string $suggestedCode = null,
        string $category = 'improvement',
        int $priority = 3
    ) {
        $this->type = $type;
        $this->file = $file;
        $this->lineRange = $lineRange;
        $this->title = $title;
        $this->description = $description;
        $this->suggestedCode = $suggestedCode;
        $this->category = $category;
        $this->priority = $priority;
    }
    
    public function toTaskArray(): array {
        $taskTitle = sprintf("[%s] %s", strtoupper($this->type), $this->title);
        
        $taskDescription = sprintf(
            "File: %s%s\n\n%s",
            $this->file,
            $this->lineRange ? " (Lines {$this->lineRange})" : '',
            $this->description
        );
        
        if ($this->suggestedCode) {
            $taskDescription .= "\n\n### Suggested Fix:\n```\n{$this->suggestedCode}\n```";
        }
        
        return [
            'title' => $taskTitle,
            'description' => $taskDescription,
            'status' => 'todo',
            'priority' => $this->priority,
            'tags' => [$this->type, $this->category, basename($this->file)]
        ];
    }
}

class CodeRabbitParser {
    private array $comments = [];
    
    public function parseMarkdownFile(string $filePath): void {
        $content = file_get_contents($filePath);
        if (!$content) {
            die("Failed to read file: $filePath\n");
        }
        
        // Find PR review section - get everything after the review header to the last ---
        $reviewStart = strpos($content, '### Review by coderabbitai[bot]');
        if ($reviewStart !== false) {
            // Find the last --- in the file
            $lastDash = strrpos($content, "\n---\n");
            if ($lastDash !== false && $lastDash > $reviewStart) {
                $reviewBody = substr($content, $reviewStart + strlen('### Review by coderabbitai[bot]'), 
                                   $lastDash - $reviewStart - strlen('### Review by coderabbitai[bot]'));
            
                // Parse AI prompts
                $this->parseAIPrompts($reviewBody);
                
                // Parse nitpick comments
                $this->parseNitpickComments($reviewBody);
            } else {
                echo "Could not find review body end marker\n";
            }
        } else {
            echo "Could not find CodeRabbit review section\n";
        }
    }
    
    private function parseNitpickComments(string $reviewBody): void {
        // Find the nitpick section
        $nitpickStart = strpos($reviewBody, '<summary>🧹 Nitpick comments (');
        if ($nitpickStart === false) {
            return;
        }
        
        // Go back to find the opening <details> tag
        for ($i = $nitpickStart; $i >= 0; $i--) {
            if (substr($reviewBody, $i, 9) === '<details>') {
                $nitpickStart = $i;
                break;
            }
        }
        
        // Find the start of content after opening blockquote
        $contentStart = strpos($reviewBody, '</summary><blockquote>', $nitpickStart);
        if ($contentStart === false) {
            return;
        }
        $contentStart += strlen('</summary><blockquote>');
        
        // Find matching closing </blockquote></details> by counting nesting levels
        $nestLevel = 1;
        $nitpickEnd = -1;
        $searchPos = $contentStart;
        
        while ($searchPos < strlen($reviewBody)) {
            $remainingText = substr($reviewBody, $searchPos);
            $openPos = strpos($remainingText, '<blockquote>');
            $closePos = strpos($remainingText, '</blockquote>');
            
            if ($openPos !== false && ($closePos === false || $openPos < $closePos)) {
                $nestLevel++;
                $searchPos += $openPos + strlen('<blockquote>');
            } elseif ($closePos !== false) {
                $nestLevel--;
                $searchPos += $closePos + strlen('</blockquote>');
                if ($nestLevel === 0) {
                    $nitpickEnd = $searchPos - strlen('</blockquote>');
                    break;
                }
            } else {
                break;
            }
        }
        
        if ($nitpickEnd === -1) {
            return;
        }
        
        $nitpickContent = substr($reviewBody, $contentStart, $nitpickEnd - $contentStart);
        // Parse file sections
        preg_match_all('/<details>\s*<summary>([^<>]+(?:\([^)]+\))?)<\/summary><blockquote>(.*?)<\/blockquote><\/details>/s', 
                      $nitpickContent, $fileMatches, PREG_SET_ORDER);
        
        foreach ($fileMatches as $fileMatch) {
            $fileName = trim($fileMatch[1]);
            // Remove count from filename
            $fileName = preg_replace('/\s*\(\d+\)$/', '', $fileName);
            $fileContent = $fileMatch[2];
            
            $this->parseCommentsInFileSection($fileName, $fileContent, 'nitpick');
        }
    }
    
    private function parseCommentsInFileSection(string $fileName, string $fileContent, string $type): void {
        // Split content into individual comments
        $lines = explode("\n", $fileContent);
        $currentComment = [];
        $allComments = [];
        
        foreach ($lines as $line) {
            // Check if this line starts a new comment (backtick pattern)
            if (preg_match('/^`([^`]+)`: \*\*/', trim($line))) {
                // Save previous comment if it exists
                if (!empty($currentComment)) {
                    $allComments[] = $currentComment;
                }
                // Start new comment
                $currentComment = [$line];
            } elseif (!empty($currentComment)) {
                // Add line to current comment
                $currentComment[] = $line;
            }
        }
        
        // Don't forget the last comment
        if (!empty($currentComment)) {
            $allComments[] = $currentComment;
        }
        
        // Parse each comment
        foreach ($allComments as $commentLines) {
            if (empty($commentLines)) {
                continue;
            }
            
            $firstLine = $commentLines[0];
            
            // Parse line range and title
            if (!preg_match('/`([^`]+)`: \*\*([^*]+?)\*\*/', $firstLine, $match)) {
                continue;
            }
            
            $lineRange = trim($match[1]);
            $title = trim($match[2]);
            
            // Join remaining lines as description
            $descriptionLines = array_slice($commentLines, 1);
            $description = trim(implode("\n", $descriptionLines));
            
            // Extract code suggestions
            $suggestedCode = $this->extractSuggestion($description);
            
            // Clean description
            if ($suggestedCode) {
                // Remove code blocks from description
                $description = preg_replace('/```[^`]*```/s', '', $description);
                $description = trim($description);
            }
            
            // Determine category
            $category = $this->determineCategory($title, $description);
            
            // Determine priority
            $priority = $type === 'nitpick' ? 4 : 2;
            if (stripos($title, 'security') !== false || stripos($title, 'vulnerability') !== false) {
                $priority = 1;
            } elseif (stripos($title, 'performance') !== false) {
                $priority = 2;
            } elseif (stripos($title, 'validation') !== false || stripos($title, 'error') !== false) {
                $priority = 3;
            }
            
            $comment = new ReviewComment(
                $type,
                $fileName,
                $lineRange,
                $title,
                $description ?: 'Review and implement suggested changes',
                $suggestedCode,
                $category,
                $priority
            );
            
            $this->comments[] = $comment;
        }
    }
    
    
    private function parseAIPrompts(string $reviewBody): void {
        // Find ALL AI prompt sections in the review body
        $pattern = '/<details>\s*<summary>🤖 Prompt for AI Agents<\/summary>\s*(.*?)<\/details>/s';
        
        if (preg_match_all($pattern, $reviewBody, $matches, PREG_SET_ORDER)) {
            echo "Found " . count($matches) . " AI prompt sections\n";
            
            foreach ($matches as $match) {
                $promptSection = $match[1];
                
                // Extract the code block content
                if (preg_match('/```(?:[a-z]+)?\n?(.*?)\n?```/s', $promptSection, $codeMatch)) {
                    $promptContent = trim($codeMatch[1]);
                    $promptContent = $this->cleanPromptContent($promptContent);
                    
                    if (!empty($promptContent)) {
                        // Extract file path from prompt if possible
                        $fileName = 'General';
                        if (preg_match('/(?:file|path|In\s+)([^\s]+\.[a-z]+)/i', $promptContent, $fileMatch)) {
                            $fileName = trim($fileMatch[1]);
                        }
                        
                        $title = $this->generateTitleFromPrompt($promptContent);
                        
                        $comment = new ReviewComment(
                            'ai_prompt',
                            $fileName,
                            null,
                            $title,
                            $promptContent,
                            null,
                            'ai-implementation',
                            2 // Higher priority for AI prompts
                        );
                        
                        $this->comments[] = $comment;
                    }
                }
            }
        }
    }
    
    private function cleanPromptContent(string $content): string {
        $lines = explode("\n", $content);
        
        // Remove language specifier if present
        if (!empty($lines)) {
            $firstLine = trim($lines[0]);
            if (strlen($firstLine) < 20 && !strpos($firstLine, ' ') && $this->isLanguageSpecifier($firstLine)) {
                array_shift($lines);
            }
        }
        
        // Remove internal metadata
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (!str_starts_with($trimmedLine, '<!-- ') &&
                !str_starts_with($trimmedLine, '---') &&
                !str_contains($trimmedLine, 'coderabbit') &&
                !str_contains($trimmedLine, 'review-id:') &&
                !str_contains($trimmedLine, 'commit-id:')) {
                $cleanLines[] = $line;
            }
        }
        
        return trim(implode("\n", $cleanLines));
    }
    
    private function isLanguageSpecifier(string $line): bool {
        $commonLanguages = [
            'bash', 'shell', 'sh', 'go', 'javascript', 'js', 'typescript', 'ts',
            'python', 'py', 'java', 'c', 'cpp', 'rust', 'sql', 'json', 'yaml', 'xml',
            'html', 'css', 'markdown', 'md', 'text', 'plain', 'php', 'diff'
        ];
        
        return in_array(strtolower($line), $commonLanguages);
    }
    
    private function generateTitleFromPrompt(string $content): string {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }
            
            // Use first substantial line as title
            $words = explode(' ', $line);
            if (count($words) >= 3) {
                if (strlen($line) > 100) {
                    return substr($line, 0, 100) . '...';
                }
                return $line;
            }
        }
        
        return 'AI Implementation Task';
    }
    
    private function extractSuggestion(string $description): ?string {
        // Look for code blocks
        if (preg_match_all('/```(?:[a-z]+)?\n(.*?)\n```/s', $description, $matches)) {
            return implode("\n\n", $matches[1]);
        }
        
        return null;
    }
    
    private function determineCategory(string $title, string $description): string {
        $text = strtolower($title . ' ' . $description);
        
        if (strpos($text, 'security') !== false || strpos($text, 'vulnerability') !== false) {
            return 'security';
        } elseif (strpos($text, 'performance') !== false || strpos($text, 'optimize') !== false) {
            return 'performance';
        } elseif (strpos($text, 'validation') !== false || strpos($text, 'validate') !== false) {
            return 'validation';
        } elseif (strpos($text, 'type') !== false || strpos($text, 'typing') !== false) {
            return 'type-safety';
        } elseif (strpos($text, 'test') !== false) {
            return 'testing';
        } elseif (strpos($text, 'doc') !== false || strpos($text, 'comment') !== false) {
            return 'documentation';
        } elseif (strpos($text, 'import') !== false || strpos($text, 'unused') !== false) {
            return 'cleanup';
        } elseif (strpos($text, 'strict') !== false) {
            return 'strict-mode';
        }
        
        return 'improvement';
    }
    
    public function getComments(): array {
        return $this->comments;
    }
    
    public function saveAsSpecsrvTasks(int $projectId): void {
        $savedCount = 0;
        $failedCount = 0;
        
        foreach ($this->comments as $comment) {
            $taskData = $comment->toTaskArray();
            
            // Create task using Specsrv CLI
            $cmd = sprintf(
                './build/specsrv task create --project-id %d --title %s --description %s --status %s --priority %d',
                $projectId,
                escapeshellarg($taskData['title']),
                escapeshellarg($taskData['description']),
                escapeshellarg($taskData['status']),
                $taskData['priority']
            );
            
            // Add tags
            foreach ($taskData['tags'] as $tag) {
                $cmd .= ' --tag ' . escapeshellarg($tag);
            }
            
            $output = shell_exec($cmd . ' 2>&1');
            
            if (strpos($output, 'Task created successfully') !== false) {
                $savedCount++;
                echo ".";
            } else {
                $failedCount++;
                echo "F";
                error_log("Failed to create task: " . $taskData['title'] . "\nOutput: " . $output);
            }
            
            // Add a small delay to avoid overwhelming the system
            usleep(100000); // 100ms
        }
        
        echo "\n\nResults:\n";
        echo "Successfully created: $savedCount tasks\n";
        echo "Failed: $failedCount tasks\n";
    }
}

// Main execution
$parser = new CodeRabbitParser();

echo "Parsing CodeRabbit review comments from comment_example.md...\n";
$parser->parseMarkdownFile('./planning/comment_example.md');

$comments = $parser->getComments();
echo "Found " . count($comments) . " total comments\n";

$nitpickCount = count(array_filter($comments, fn($c) => $c->type === 'nitpick'));
$aiPromptCount = count(array_filter($comments, fn($c) => $c->type === 'ai_prompt'));

echo "- Nitpick comments: $nitpickCount\n";
echo "- AI Prompt comments: $aiPromptCount\n";

// Group by category for summary
$categories = [];
foreach ($comments as $comment) {
    $categories[$comment->category] = ($categories[$comment->category] ?? 0) + 1;
}
arsort($categories);

echo "\nComments by category:\n";
foreach ($categories as $category => $count) {
    echo "  - $category: $count\n";
}

// Group by file
$files = [];
foreach ($comments as $comment) {
    $files[$comment->file] = ($files[$comment->file] ?? 0) + 1;
}
arsort($files);

echo "\nComments by file:\n";
$fileCount = 0;
foreach ($files as $file => $count) {
    echo "  - $file: $count\n";
    $fileCount++;
    if ($fileCount >= 10) {
        $remaining = count($files) - 10;
        if ($remaining > 0) {
            echo "  ... and $remaining more files\n";
        }
        break;
    }
}

// Ask for confirmation before creating tasks
echo "\nDo you want to create these as tasks in project 'Specsrv Development' (ID: 4)? [y/N]: ";
$confirmation = trim(fgets(STDIN));

if (strtolower($confirmation) === 'y') {
    echo "\nCreating tasks in Specsrv...\n";
    $parser->saveAsSpecsrvTasks(4);
} else {
    echo "Aborted. No tasks were created.\n";
}