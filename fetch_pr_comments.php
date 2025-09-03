#!/usr/bin/env php
<?php

// PR details
$owner = 'ericfisherdev';
$repo = 'specsrv';
$prNumber = 13;

// Output file
$outputFile = './planning/coderabbit_comments_1.md';

// Ensure planning directory exists
if (!file_exists('./planning')) {
    mkdir('./planning', 0755, true);
}

// Fetch PR details
echo "Fetching PR #$prNumber details...\n";
$prCommand = "gh api repos/$owner/$repo/pulls/$prNumber";
$prJson = shell_exec($prCommand);
$prData = json_decode($prJson, true);

if (!$prData) {
    die("Failed to fetch PR details\n");
}

// Fetch issue comments
echo "Fetching issue comments...\n";
$issueCommentsCommand = "gh api repos/$owner/$repo/issues/$prNumber/comments --paginate";
$issueCommentsJson = shell_exec($issueCommentsCommand);
$issueComments = json_decode($issueCommentsJson, true) ?: [];

// Fetch PR reviews (including CodeRabbit review summaries)
echo "Fetching PR reviews...\n";
$reviewsCommand = "gh api repos/$owner/$repo/pulls/$prNumber/reviews --paginate";
$reviewsJson = shell_exec($reviewsCommand);
$reviews = json_decode($reviewsJson, true) ?: [];

// Fetch PR review comments with pagination to get all 112 comments
echo "Fetching review comments (with pagination)...\n";
$reviewCommentsCommand = "gh api repos/$owner/$repo/pulls/$prNumber/comments --paginate";
$reviewCommentsJson = shell_exec($reviewCommentsCommand);
$reviewComments = json_decode($reviewCommentsJson, true) ?: [];

// Function to extract prompts from CodeRabbit comments
function extractPromptsFromComment($body) {
    $prompts = [];
    
    // Look for "🤖 Prompt for AI Agents" section in details block
    if (preg_match('/<details>\s*<summary>🤖 Prompt for AI Agents<\/summary>\s*(.*?)<\/details>/si', $body, $matches)) {
        $promptSection = trim($matches[1]);
        
        // Extract content between triple backticks if present
        if (preg_match('/```(?:\w+)?\s*(.*?)\s*```/s', $promptSection, $codeMatch)) {
            $promptText = trim($codeMatch[1]);
            if (!empty($promptText)) {
                $prompts[] = $promptText;
            }
        } else {
            // If no code block, use the whole content
            $promptText = strip_tags($promptSection);
            $promptText = trim($promptText);
            if (!empty($promptText)) {
                $prompts[] = $promptText;
            }
        }
    }
    
    // Alternative pattern: Sometimes prompts might be in different formats
    if (empty($prompts)) {
        // Look for "Prompt for AI agents" without emoji
        if (preg_match('/<details>\s*<summary>(?:Prompt for AI Agents|AI Agent Prompt)<\/summary>\s*(.*?)<\/details>/si', $body, $matches)) {
            $promptSection = trim($matches[1]);
            
            // Extract content between triple backticks if present
            if (preg_match('/```(?:\w+)?\s*(.*?)\s*```/s', $promptSection, $codeMatch)) {
                $promptText = trim($codeMatch[1]);
                if (!empty($promptText)) {
                    $prompts[] = $promptText;
                }
            }
        }
    }
    
    return $prompts;
}

// Start building the markdown content
$markdown = "# CodeRabbit AI Prompts from PR #$prNumber\n\n";
$markdown .= "**PR Title:** {$prData['title']}\n";
$markdown .= "**Author:** {$prData['user']['login']}\n";
$markdown .= "**Date:** {$prData['created_at']}\n\n";
$markdown .= "---\n\n";

$allPrompts = [];
$promptCount = 0;

// Process PR reviews (look for CodeRabbit bot)
foreach ($reviews as $review) {
    if (!empty($review['body']) && 
        (stripos($review['user']['login'], 'coderabbit') !== false || 
         stripos($review['body'], 'coderabbitai') !== false)) {
        $prompts = extractPromptsFromComment($review['body']);
        foreach ($prompts as $prompt) {
            $promptCount++;
            $markdown .= "## Prompt $promptCount (from PR Review)\n";
            $markdown .= "**Source:** PR Review by {$review['user']['login']}\n";
            $markdown .= "**Date:** {$review['submitted_at']}\n\n";
            $markdown .= "### Prompt:\n";
            $markdown .= "$prompt\n\n";
            $markdown .= "---\n\n";
            $allPrompts[] = $prompt;
        }
    }
}

// Process issue comments (look for CodeRabbit bot)
foreach ($issueComments as $comment) {
    if (stripos($comment['user']['login'], 'coderabbit') !== false || 
        stripos($comment['body'], 'coderabbitai') !== false) {
        $prompts = extractPromptsFromComment($comment['body']);
        foreach ($prompts as $prompt) {
            $promptCount++;
            $markdown .= "## Prompt $promptCount (from Issue Comment)\n";
            $markdown .= "**Source:** Issue Comment by {$comment['user']['login']}\n";
            $markdown .= "**Date:** {$comment['created_at']}\n\n";
            $markdown .= "### Prompt:\n";
            $markdown .= "$prompt\n\n";
            $markdown .= "---\n\n";
            $allPrompts[] = $prompt;
        }
    }
}

// Process review comments (look for CodeRabbit bot)
foreach ($reviewComments as $comment) {
    if (stripos($comment['user']['login'], 'coderabbit') !== false || 
        stripos($comment['body'], 'coderabbitai') !== false) {
        $prompts = extractPromptsFromComment($comment['body']);
        foreach ($prompts as $prompt) {
            $promptCount++;
            $markdown .= "## Prompt $promptCount (from Review Comment)\n";
            $markdown .= "**Source:** Review Comment by {$comment['user']['login']}\n";
            $markdown .= "**File:** {$comment['path']}";
            if (isset($comment['line'])) {
                $markdown .= " (Line {$comment['line']})";
            }
            $markdown .= "\n";
            $markdown .= "**Date:** {$comment['created_at']}\n\n";
            $markdown .= "### Prompt:\n";
            $markdown .= "$prompt\n\n";
            $markdown .= "---\n\n";
            $allPrompts[] = $prompt;
        }
    }
}

// Add summary at the end
$markdown .= "## Summary\n\n";
$markdown .= "Total prompts extracted: **$promptCount**\n\n";
if ($promptCount > 0) {
    $markdown .= "All prompts have been extracted from CodeRabbit AI bot comments in PR #$prNumber.\n";
} else {
    $markdown .= "No CodeRabbit AI prompts found in PR #$prNumber.\n";
}

// Write to file
file_put_contents($outputFile, $markdown);

echo "Successfully saved CodeRabbit AI prompts to $outputFile\n";
echo "Total prompts extracted: $promptCount\n";
echo "Processed:\n";
echo "  - PR reviews: " . count($reviews) . "\n";
echo "  - Issue comments: " . count($issueComments) . "\n";
echo "  - Review comments: " . count($reviewComments) . "\n";