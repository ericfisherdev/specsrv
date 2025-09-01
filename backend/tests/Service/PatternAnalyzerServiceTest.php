<?php

namespace App\Tests\Service;

use App\Entity\AgentInteraction;
use App\Entity\Task;
use App\Service\PatternAnalyzerService;
use App\Tests\AbstractKernelTestCase;

class PatternAnalyzerServiceTest extends AbstractKernelTestCase
{
    private PatternAnalyzerService $patternAnalyzer;
    private Task $testTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patternAnalyzer = $this->getService(PatternAnalyzerService::class);

        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->testTask = $this->createTestTask($project);
    }

    public function testAnalyzeSimilarity(): void
    {
        $context1 = ['task_type' => 'bug_fix', 'technology' => 'php', 'complexity' => 'simple'];
        $context2 = ['task_type' => 'bug_fix', 'technology' => 'php', 'complexity' => 'moderate'];
        $context3 = ['task_type' => 'feature', 'technology' => 'javascript', 'complexity' => 'complex'];

        // Similar contexts should have high similarity
        $similarity1 = $this->patternAnalyzer->analyzeSimilarity($context1, $context2);
        $this->assertGreaterThan(0.5, $similarity1);
        $this->assertLessThanOrEqual(1.0, $similarity1);

        // Different contexts should have lower similarity
        $similarity2 = $this->patternAnalyzer->analyzeSimilarity($context1, $context3);
        $this->assertLessThan($similarity1, $similarity2);

        // Identical contexts should have perfect similarity
        $similarity3 = $this->patternAnalyzer->analyzeSimilarity($context1, $context1);
        $this->assertEquals(1.0, $similarity3, '', 0.01);
    }

    public function testAnalyzeSimilarityWithEmptyContexts(): void
    {
        $emptyContext1 = [];
        $emptyContext2 = [];
        $filledContext = ['task_type' => 'bug_fix'];

        // Empty contexts should return 0 similarity
        $similarity1 = $this->patternAnalyzer->analyzeSimilarity($emptyContext1, $emptyContext2);
        $this->assertEquals(0.0, $similarity1);

        // Empty vs filled should return low similarity
        $similarity2 = $this->patternAnalyzer->analyzeSimilarity($emptyContext1, $filledContext);
        $this->assertEquals(0.0, $similarity2);
    }

    public function testIdentifyPatternType(): void
    {
        // Implementation pattern
        $implSteps = [
            ['type' => 'code_generation', 'result' => 'success'],
            ['type' => 'implementation', 'result' => 'success'],
        ];
        $this->assertEquals('implementation', $this->patternAnalyzer->identifyPatternType($implSteps));

        // Quality assurance pattern
        $qaSteps = [
            ['type' => 'testing', 'result' => 'success'],
            ['type' => 'validation', 'result' => 'success'],
        ];
        $this->assertEquals('quality_assurance', $this->patternAnalyzer->identifyPatternType($qaSteps));

        // Analysis pattern
        $analysisSteps = [
            ['type' => 'analysis', 'result' => 'success'],
            ['type' => 'research', 'result' => 'success'],
        ];
        $this->assertEquals('analysis', $this->patternAnalyzer->identifyPatternType($analysisSteps));

        // Debugging pattern
        $debugSteps = [
            ['type' => 'debugging', 'result' => 'success'],
            ['type' => 'error_resolution', 'result' => 'success'],
        ];
        $this->assertEquals('debugging', $this->patternAnalyzer->identifyPatternType($debugSteps));

        // General pattern (unknown types)
        $unknownSteps = [
            ['type' => 'unknown_step', 'result' => 'success'],
        ];
        $this->assertEquals('general', $this->patternAnalyzer->identifyPatternType($unknownSteps));
    }

    public function testExtractKeyFeatures(): void
    {
        $context = [
            'task_type' => 'bug_fix',
            'technologies' => ['php', 'symfony'],
            'files_count' => 3,
            'domain' => 'web_development',
            'project_size' => 500,
            'time_constraints' => 8,
            'quality_requirements' => 'high',
        ];

        $features = $this->patternAnalyzer->extractKeyFeatures($context);

        $this->assertArrayHasKey('task_type', $features);
        $this->assertArrayHasKey('tech_stack', $features);
        $this->assertArrayHasKey('complexity', $features);
        $this->assertArrayHasKey('domain', $features);
        $this->assertArrayHasKey('project_scale', $features);
        $this->assertArrayHasKey('urgency', $features);
        $this->assertArrayHasKey('quality_level', $features);

        $this->assertEquals('bug_fix', $features['task_type']);
        $this->assertEquals(['php', 'symfony'], $features['tech_stack']);
        $this->assertEquals('moderate', $features['complexity']);
        $this->assertEquals('web_development', $features['domain']);
        $this->assertEquals('medium', $features['project_scale']);
        $this->assertEquals('high', $features['urgency']);
        $this->assertEquals('high', $features['quality_level']);
    }

    public function testCalculatePatternConfidence(): void
    {
        $interaction = new AgentInteraction();
        $interaction->setTask($this->testTask)
            ->setAgentType('implementation')
            ->setInputContext(['task_type' => 'feature'])
            ->setExecutionSteps([['type' => 'implementation']])
            ->setOutputResult(['success' => true])
            ->setSuccessScore(0.9)
            ->setPatternHash('test-hash')
            ->setExecutionTimeMs(1500);

        // Test with no similar interactions
        $confidence1 = $this->patternAnalyzer->calculatePatternConfidence($interaction, []);
        $this->assertEquals(0.9, $confidence1);

        // Create similar interactions
        $similar1 = new AgentInteraction();
        $similar1->setTask($this->testTask)
            ->setSuccessScore(0.85)
            ->setAgentType('implementation')
            ->setInputContext(['task_type' => 'feature'])
            ->setExecutionSteps([['type' => 'implementation']])
            ->setOutputResult(['success' => true])
            ->setPatternHash('test-hash-2')
            ->setExecutionTimeMs(1200);

        $similar2 = new AgentInteraction();
        $similar2->setTask($this->testTask)
            ->setSuccessScore(0.95)
            ->setAgentType('implementation')
            ->setInputContext(['task_type' => 'feature'])
            ->setExecutionSteps([['type' => 'implementation']])
            ->setOutputResult(['success' => true])
            ->setPatternHash('test-hash-3')
            ->setExecutionTimeMs(1800);

        $confidence2 = $this->patternAnalyzer->calculatePatternConfidence($interaction, [$similar1, $similar2]);
        $this->assertGreaterThan($confidence1, $confidence2);
        $this->assertLessThanOrEqual(1.0, $confidence2);
    }

    public function testExtractSolutionTemplate(): void
    {
        $interaction = new AgentInteraction();
        $interaction->setTask($this->testTask)
            ->setAgentType('implementation')
            ->setInputContext(['task_type' => 'feature'])
            ->setExecutionSteps([
                ['type' => 'analysis', 'tools' => ['ide', 'debugger'], 'outcome' => 'requirements_understood'],
                ['type' => 'implementation', 'tools' => ['editor', 'compiler'], 'outcome' => 'code_written'],
            ])
            ->setOutputResult(['tests_passed' => true, 'performance_improvement' => 15])
            ->setSuccessScore(0.9)
            ->setPatternHash('test-hash')
            ->setExecutionTimeMs(120000)
            ->setErrorLog([['type' => 'syntax_error', 'resolution' => 'fixed_typo']]);

        $template = $this->patternAnalyzer->extractSolutionTemplate($interaction);

        $this->assertArrayHasKey('approach', $template);
        $this->assertArrayHasKey('key_steps', $template);
        $this->assertArrayHasKey('tools_used', $template);
        $this->assertArrayHasKey('success_indicators', $template);
        $this->assertArrayHasKey('time_estimate', $template);
        $this->assertArrayHasKey('common_pitfalls', $template);

        $this->assertEquals('analytical', $template['approach']);
        $this->assertCount(2, $template['key_steps']);
        $this->assertContains('ide', $template['tools_used']);
        $this->assertContains('debugger', $template['tools_used']);
        $this->assertContains('editor', $template['tools_used']);
        $this->assertContains('compiler', $template['tools_used']);
        $this->assertEquals('moderate', $template['time_estimate']);
        $this->assertNotEmpty($template['common_pitfalls']);
    }

    public function testComplexityCategorizationEdgeCases(): void
    {
        // Test numeric values
        $features1 = $this->patternAnalyzer->extractKeyFeatures(['files_count' => 1]);
        $this->assertEquals('simple', $features1['complexity']);

        $features2 = $this->patternAnalyzer->extractKeyFeatures(['files_count' => 15]);
        $this->assertEquals('very_complex', $features2['complexity']);

        // Test project size categorization
        $features3 = $this->patternAnalyzer->extractKeyFeatures(['project_size' => 50]);
        $this->assertEquals('small', $features3['project_scale']);

        $features4 = $this->patternAnalyzer->extractKeyFeatures(['project_size' => 15000]);
        $this->assertEquals('enterprise', $features4['project_scale']);

        // Test urgency categorization
        $features5 = $this->patternAnalyzer->extractKeyFeatures(['time_constraints' => 2]);
        $this->assertEquals('urgent', $features5['urgency']);

        $features6 = $this->patternAnalyzer->extractKeyFeatures(['time_constraints' => 200]);
        $this->assertEquals('low', $features6['urgency']);
    }
}
