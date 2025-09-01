#!/usr/bin/env php
<?php

// PR details
$owner = 'ericfisherdev';
$repo = 'specsrv';
$prNumber = 11;

// Output file
$outputFile = './planning/comment_example.md';

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
$issueCommentsCommand = "gh api repos/$owner/$repo/issues/$prNumber/comments";
$issueCommentsJson = shell_exec($issueCommentsCommand);
$issueComments = json_decode($issueCommentsJson, true) ?: [];

// Fetch PR reviews (including CodeRabbit review summaries)
echo "Fetching PR reviews...\n";
$reviewsCommand = "gh api repos/$owner/$repo/pulls/$prNumber/reviews";
$reviewsJson = shell_exec($reviewsCommand);
$reviews = json_decode($reviewsJson, true) ?: [];

// Fetch PR review comments
echo "Fetching review comments...\n";
$reviewCommentsCommand = "gh api repos/$owner/$repo/pulls/$prNumber/comments";
$reviewCommentsJson = shell_exec($reviewCommentsCommand);
$reviewComments = json_decode($reviewCommentsJson, true) ?: [];

// Start building the markdown content
$markdown = "# PR #$prNumber: {$prData['title']}\n\n";
$markdown .= "**Author:** {$prData['user']['login']}\n";
$markdown .= "**Created:** {$prData['created_at']}\n";
$markdown .= "**State:** {$prData['state']}\n\n";

// Add PR description
$markdown .= "## Description\n\n";
$markdown .= $prData['body'] ?: "(No description provided)";
$markdown .= "\n\n---\n\n";

// Add PR reviews
if (count($reviews) > 0) {
    $markdown .= "## PR Reviews\n\n";
    foreach ($reviews as $review) {
        if (!empty($review['body'])) {
            $markdown .= "### Review by {$review['user']['login']}\n";
            $markdown .= "_State: {$review['state']}_\n";
            $markdown .= "_Submitted: {$review['submitted_at']}_\n\n";
            $markdown .= $review['body'] . "\n\n";
            $markdown .= "---\n\n";
        }
    }
}

// Add issue comments
if (count($issueComments) > 0) {
    $markdown .= "## Issue Comments\n\n";
    foreach ($issueComments as $comment) {
        $markdown .= "### Comment by {$comment['user']['login']}\n";
        $markdown .= "_Posted: {$comment['created_at']}_\n\n";
        $markdown .= $comment['body'] . "\n\n";
        $markdown .= "---\n\n";
    }
}

// Add review comments
if (count($reviewComments) > 0) {
    $markdown .= "## Review Comments\n\n";
    foreach ($reviewComments as $comment) {
        $markdown .= "### Review comment by {$comment['user']['login']}\n";
        $markdown .= "_File: {$comment['path']}";
        if (isset($comment['line'])) {
            $markdown .= " (Line {$comment['line']})";
        }
        $markdown .= "_\n";
        $markdown .= "_Posted: {$comment['created_at']}_\n\n";
        $markdown .= "```diff\n{$comment['diff_hunk']}\n```\n\n";
        $markdown .= $comment['body'] . "\n\n";
        $markdown .= "---\n\n";
    }
}

// Write to file
file_put_contents($outputFile, $markdown);

echo "Successfully saved comments to $outputFile\n";
echo "Total PR reviews: " . count(array_filter($reviews, fn($r) => !empty($r['body']))) . "\n";
echo "Total issue comments: " . count($issueComments) . "\n";
echo "Total review comments: " . count($reviewComments) . "\n";