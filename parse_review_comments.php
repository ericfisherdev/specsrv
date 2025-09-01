#!/usr/bin/env php
<?php

// DTO for unified comment format
class ReviewComment {
    public string $type; // 'nitpick' or 'actionable'
    public string $file;
    public ?int $lineNumber;
    public string $title;
    public string $description;
    public ?string $suggestedCode;
    public string $category; // e.g., 'performance', 'validation', 'type-safety', etc.
    public int $priority; // 1-5 (1=highest)
    
    public function __construct(
        string $type,
        string $file,
        ?int $lineNumber,
        string $title,
        string $description,
        ?string $suggestedCode = null,
        string $category = 'improvement',
        int $priority = 3
    ) {
        $this->type = $type;
        $this->file = $file;
        $this->lineNumber = $lineNumber;
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
            $this->lineNumber ? " (Line {$this->lineNumber})" : '',
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

class CommentParser {
    private array $comments = [];
    
    public function parseMarkdownFile(string $filePath): void {
        $content = file_get_contents($filePath);
        if (!$content) {
            die("Failed to read file: $filePath\n");
        }
        
        // Parse nitpick comments
        $this->parseNitpickComments($content);
        
        // Parse actionable review comments
        $this->parseActionableComments($content);
    }
    
    private function parseNitpickComments(string $content): void {
        // Extract the nitpick section - look for the outer details block
        if (preg_match('/🧹 Nitpick comments \(\d+\)<\/summary><blockquote>(.*?)<\/blockquote><\/details>/s', $content, $matches)) {
            $nitpickSection = $matches[1];
            
            // Parse individual file sections (nested details blocks)
            preg_match_all('/<details>\s*<summary>(.*?)<\/summary><blockquote>(.*?)<\/blockquote><\/details>/s', $nitpickSection, $fileMatches, PREG_SET_ORDER);
            
            foreach ($fileMatches as $fileMatch) {
                $fileInfo = trim($fileMatch[1]);
                $fileContent = $fileMatch[2];
                
                // Extract file name and count
                if (preg_match('/^(.*?)\s*\((\d+)\)$/', $fileInfo, $fileInfoMatch)) {
                    $fileName = trim($fileInfoMatch[1]);
                    
                    // Parse individual comments within the file
                    $this->parseFileComments($fileName, $fileContent, 'nitpick');
                }
            }
        }
    }
    
    private function parseActionableComments(string $content): void {
        // Look for the review section that contains actionable comments
        if (preg_match('/\*\*Actionable comments posted: \d+\*\*(.*?)(<details>|$)/s', $content, $matches)) {
            $actionableSection = $matches[1];
            
            // Check if there are nested details blocks (for grouped comments)
            if (strpos($actionableSection, '<details>') !== false) {
                // Parse individual file sections (nested details blocks)
                preg_match_all('/<details>\s*<summary>(.*?)<\/summary><blockquote>(.*?)<\/blockquote><\/details>/s', $actionableSection, $fileMatches, PREG_SET_ORDER);
                
                foreach ($fileMatches as $fileMatch) {
                    $fileInfo = trim($fileMatch[1]);
                    $fileContent = $fileMatch[2];
                    
                    // Extract file name and count
                    if (preg_match('/^(.*?)\s*\((\d+)\)$/', $fileInfo, $fileInfoMatch)) {
                        $fileName = trim($fileInfoMatch[1]);
                        
                        // Parse individual comments within the file
                        $this->parseFileComments($fileName, $fileContent, 'actionable');
                    }
                }
            }
        }
    }
    
    private function parseFileComments(string $fileName, string $content, string $type): void {
        // Split content by horizontal rules to get individual comments
        $commentBlocks = preg_split('/\n---\n/', $content);
        
        foreach ($commentBlocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            // Extract line number reference (at the beginning of the block)
            $lineNumber = null;
            if (preg_match('/^`(\d+)-(\d+)`:\s*|^`(\d+)`:\s*/m', $block, $lineMatch)) {
                $lineNumber = (int)($lineMatch[3] ?? $lineMatch[1]);
                // Remove the line reference from the block
                $block = preg_replace('/^`\d+(-\d+)?`:\s*/m', '', $block);
            }
            
            // Extract title (usually the first bold text after line number)
            $title = '';
            if (preg_match('/^\*\*(.*?)\*\*/m', $block, $titleMatch)) {
                $title = trim($titleMatch[1]);
            }
            
            // Extract suggested code if present
            $suggestedCode = null;
            if (preg_match('/```(?:diff|[a-z]+)?\n(.*?)\n```/s', $block, $codeMatch)) {
                $suggestedCode = trim($codeMatch[1]);
            }
            
            // Build description from the block content
            $lines = explode("\n", $block);
            $descriptionLines = [];
            $inCodeBlock = false;
            
            foreach ($lines as $line) {
                // Skip title line
                if (strpos($line, '**') === 0 && strpos($line, $title) !== false) {
                    continue;
                }
                
                // Track code blocks
                if (strpos($line, '```') === 0) {
                    $inCodeBlock = !$inCodeBlock;
                    continue;
                }
                
                // Skip code block content for description
                if ($inCodeBlock) {
                    continue;
                }
                
                // Add non-empty lines to description
                $line = trim($line);
                if (!empty($line)) {
                    $descriptionLines[] = $line;
                }
            }
            
            $description = implode(' ', $descriptionLines);
            
            // Skip if we don't have meaningful content
            if (empty($title) && empty($description)) {
                continue;
            }
            
            // Determine category based on content
            $category = $this->determineCategory($title, $description);
            
            // Determine priority
            $priority = $type === 'actionable' ? 2 : 4;
            if (stripos($title, 'security') !== false || stripos($title, 'vulnerability') !== false) {
                $priority = 1;
            } elseif (stripos($title, 'performance') !== false || stripos($title, 'optimize') !== false) {
                $priority = 2;
            } elseif (stripos($title, 'validation') !== false || stripos($title, 'error') !== false) {
                $priority = 3;
            }
            
            $comment = new ReviewComment(
                $type,
                $fileName,
                $lineNumber,
                $title ?: 'Code improvement needed',
                $description ?: 'Review and implement suggested changes',
                $suggestedCode,
                $category,
                $priority
            );
            
            $this->comments[] = $comment;
        }
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
                error_log("Failed to create task: " . $taskData['title']);
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
$parser = new CommentParser();

echo "Parsing review comments from comment_example.md...\n";
$parser->parseMarkdownFile('./planning/comment_example.md');

$comments = $parser->getComments();
echo "Found " . count($comments) . " total comments\n";

$nitpickCount = count(array_filter($comments, fn($c) => $c->type === 'nitpick'));
$actionableCount = count(array_filter($comments, fn($c) => $c->type === 'actionable'));

echo "- Nitpick comments: $nitpickCount\n";
echo "- Actionable comments: $actionableCount\n";

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

// Ask for confirmation before creating tasks
echo "\nDo you want to create these as tasks in project 'Specsrv Development' (ID: 4)? [y/N]: ";
$confirmation = trim(fgets(STDIN));

if (strtolower($confirmation) === 'y') {
    echo "\nCreating tasks in Specsrv...\n";
    $parser->saveAsSpecsrvTasks(4);
} else {
    echo "Aborted. No tasks were created.\n";
}