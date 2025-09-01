<?php

// backend/tests/Service/ContextExtractorServiceTest.php

namespace App\Tests\Service;

use App\Service\ContextExtractorService;
use PHPUnit\Framework\TestCase;

class ContextExtractorServiceTest extends TestCase
{
    private ContextExtractorService $service;

    protected function setUp(): void
    {
        $this->service = new ContextExtractorService();
    }

    public function testExtractSignatureBasic(): void
    {
        $context = [
            'task_type' => 'implementation',
            'domain' => 'api',
            'methodology' => 'tdd',
            'constraints' => 'budget',
            'complexity' => 'moderate',
        ];

        $signature = $this->service->extractSignature($context);

        $this->assertEquals('implementation', $signature['task_type']);
        $this->assertEquals('api', $signature['domain']);
        $this->assertEquals('tdd', $signature['methodology']);
        $this->assertEquals(['budget'], $signature['constraints']);
        $this->assertEquals('moderate', $signature['complexity']);
    }

    public function testExtractSignatureComplexityCategories(): void
    {
        $testCases = [
            [1, 'simple'],
            [2, 'simple'],
            [3, 'moderate'],
            [5, 'moderate'],
            [8, 'complex'],
            [10, 'complex'],
            [15, 'very_complex'],
            [100, 'very_complex'],
            ['custom', 'custom'],
            [null, 'unknown'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $context = ['task_type' => 'test', 'complexity' => $input];
            $signature = $this->service->extractSignature($context);

            $this->assertArrayHasKey('complexity', $signature, 'Complexity key missing for input: '.var_export($input, true));
            $this->assertEquals($expected, $signature['complexity'], 'Failed for input: '.var_export($input, true));
        }
    }

    public function testExtractKeywordsFromTitleAndDescription(): void
    {
        $context = [
            'title' => 'Implement user authentication system',
            'description' => 'Create secure login functionality with validation',
        ];

        $keywords = $this->service->extractKeywords($context);

        $this->assertContains('implement', $keywords);
        $this->assertContains('user', $keywords);
        $this->assertContains('authentication', $keywords);
        $this->assertContains('system', $keywords);
        $this->assertContains('create', $keywords);
        $this->assertContains('secure', $keywords);
        $this->assertContains('login', $keywords);
        $this->assertContains('functionality', $keywords);
        $this->assertContains('validation', $keywords);
    }

    public function testExtractKeywordsFiltersStopWords(): void
    {
        $context = [
            'title' => 'The quick implementation of the system',
            'description' => 'This is a test description with many common words',
        ];

        $keywords = $this->service->extractKeywords($context);

        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('is', $keywords);
        $this->assertNotContains('of', $keywords);
        $this->assertNotContains('with', $keywords);
        $this->assertContains('quick', $keywords);
        $this->assertContains('implementation', $keywords);
        $this->assertContains('system', $keywords);
        $this->assertContains('test', $keywords);
        $this->assertContains('description', $keywords);
    }

    public function testExtractKeywordsHandlesEmptyInput(): void
    {
        $context = [];

        $keywords = $this->service->extractKeywords($context);

        $this->assertIsArray($keywords);
        $this->assertEmpty($keywords);
    }
}
