<?php

namespace App\Tests\Service;

use App\Entity\AgentInteraction;
use App\Entity\KnowledgePattern;
use App\Entity\Task;
use App\Repository\AgentInteractionRepository;
use App\Repository\KnowledgePatternRepository;
use App\Repository\PatternVariationRepository;
use App\Service\ContextExtractorService;
use App\Service\LearningEngineService;
use App\Service\PatternAnalyzerService;
use App\Tests\AbstractKernelTestCase;
use Psr\Log\LoggerInterface;

class LearningEngineServiceTest extends AbstractKernelTestCase
{
    private LearningEngineService $learningEngine;
    private Task $testTask;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->learningEngine = $this->getService(LearningEngineService::class);
        
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->testTask = $this->createTestTask($project);
    }

    public function testRecordInteraction(): void
    {
        $inputContext = ['task_type' => 'bug_fix', 'technology' => 'php'];
        $executionSteps = [['type' => 'analysis'], ['type' => 'code_generation']];
        $outputResult = ['files_modified' => 1, 'tests_passed' => true];
        
        $interaction = $this->learningEngine->recordInteraction(
            $this->testTask,
            'implementation',
            $inputContext,
            $executionSteps,
            $outputResult,
            0.9,
            1500
        );
        
        $this->assertInstanceOf(AgentInteraction::class, $interaction);
        $this->assertEquals('implementation', $interaction->getAgentType());
        $this->assertEquals(0.9, $interaction->getSuccessScore());
        $this->assertEquals(1500, $interaction->getExecutionTimeMs());
        $this->assertNotEmpty($interaction->getPatternHash());
    }

    public function testRecordInteractionWithLowSuccessScore(): void
    {
        $inputContext = ['task_type' => 'bug_fix'];
        $executionSteps = [['type' => 'analysis']];
        $outputResult = ['success' => false];
        
        $interaction = $this->learningEngine->recordInteraction(
            $this->testTask,
            'implementation',
            $inputContext,
            $executionSteps,
            $outputResult,
            0.3,
            3000
        );
        
        $this->assertInstanceOf(AgentInteraction::class, $interaction);
        $this->assertEquals(0.3, $interaction->getSuccessScore());
        
        // With low success score, no pattern should be extracted
        $patternRepo = $this->getService(KnowledgePatternRepository::class);
        $patterns = $patternRepo->findAll();
        $this->assertEmpty($patterns);
    }

    public function testRecordInteractionWithHighSuccessScore(): void
    {
        $inputContext = ['task_type' => 'feature', 'complexity' => 'simple'];
        $executionSteps = [['type' => 'implementation'], ['type' => 'testing']];
        $outputResult = ['success' => true, 'quality_score' => 0.95];
        
        $interaction = $this->learningEngine->recordInteraction(
            $this->testTask,
            'implementation',
            $inputContext,
            $executionSteps,
            $outputResult,
            0.85,
            2000
        );
        
        $this->assertInstanceOf(AgentInteraction::class, $interaction);
        
        // With high success score, a pattern should be created
        $patternRepo = $this->getService(KnowledgePatternRepository::class);
        $patterns = $patternRepo->findAll();
        $this->assertNotEmpty($patterns);
        
        $pattern = $patterns[0];
        $this->assertInstanceOf(KnowledgePattern::class, $pattern);
        $this->assertEquals('implementation', $pattern->getPatternType());
        $this->assertEquals(0.85, $pattern->getConfidenceScore());
        $this->assertEquals(1, $pattern->getUsageCount());
    }

    public function testFindSimilarSuccessfulPatterns(): void
    {
        // First, create a successful interaction to generate a pattern
        $this->createSuccessfulPattern();
        
        // Now search for similar patterns
        $context = ['task_type' => 'feature', 'complexity' => 'simple'];
        $patterns = $this->learningEngine->findSimilarSuccessfulPatterns($context, 'implementation');
        
        $this->assertNotEmpty($patterns);
        $this->assertInstanceOf(KnowledgePattern::class, $patterns[0]);
    }

    public function testRecommendSolution(): void
    {
        // Create a successful pattern first
        $this->createSuccessfulPattern();
        
        // Request recommendation
        $taskContext = ['task_type' => 'feature', 'complexity' => 'simple', 'technology' => 'php'];
        $recommendation = $this->learningEngine->recommendSolution($taskContext, 'implementation');
        
        $this->assertNotNull($recommendation);
        $this->assertArrayHasKey('pattern', $recommendation);
        $this->assertArrayHasKey('confidence', $recommendation);
        $this->assertArrayHasKey('adapted_solution', $recommendation);
        $this->assertArrayHasKey('usage_history', $recommendation);
        $this->assertArrayHasKey('estimated_success_rate', $recommendation);
        
        $this->assertGreaterThan(0, $recommendation['confidence']);
        $this->assertLessThanOrEqual(1, $recommendation['confidence']);
    }

    public function testRecommendSolutionWithNoPatterns(): void
    {
        $taskContext = ['task_type' => 'unknown_task', 'complexity' => 'very_complex'];
        $recommendation = $this->learningEngine->recommendSolution($taskContext, 'unknown_agent');
        
        $this->assertNull($recommendation);
    }

    public function testGetPatterns(): void
    {
        // Create some patterns
        $this->createSuccessfulPattern();
        $this->createSuccessfulPattern(['task_type' => 'debug', 'complexity' => 'moderate']);
        
        // Get all patterns
        $patterns = $this->learningEngine->getPatterns();
        $this->assertCount(2, $patterns);
        
        // Filter by agent type
        $filteredPatterns = $this->learningEngine->getPatterns(['agent_type' => 'implementation']);
        $this->assertNotEmpty($filteredPatterns);
        
        // Each pattern should be properly serialized
        $pattern = $filteredPatterns[0];
        $this->assertArrayHasKey('id', $pattern);
        $this->assertArrayHasKey('name', $pattern);
        $this->assertArrayHasKey('type', $pattern);
        $this->assertArrayHasKey('confidence_score', $pattern);
        $this->assertArrayHasKey('usage_count', $pattern);
    }

    public function testGetPerformanceAnalytics(): void
    {
        // Create some interactions
        $this->createSuccessfulPattern();
        $this->createSuccessfulPattern(['task_type' => 'debug']);
        
        $analytics = $this->learningEngine->getPerformanceAnalytics('30d');
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('interaction_metrics', $analytics);
        $this->assertArrayHasKey('pattern_analytics', $analytics);
        $this->assertArrayHasKey('learning_effectiveness', $analytics);
        
        $this->assertNotEmpty($analytics['interaction_metrics']);
        $this->assertNotEmpty($analytics['pattern_analytics']);
        $this->assertNotEmpty($analytics['learning_effectiveness']);
    }

    public function testPatternUpdateOnSecondInteraction(): void
    {
        // Create first successful interaction
        $this->createSuccessfulPattern();
        
        $patternRepo = $this->getService(KnowledgePatternRepository::class);
        $patterns = $patternRepo->findAll();
        $this->assertCount(1, $patterns);
        
        $originalPattern = $patterns[0];
        $originalConfidence = $originalPattern->getConfidenceScore();
        $originalUsageCount = $originalPattern->getUsageCount();
        
        // Create second similar interaction
        $inputContext = ['task_type' => 'feature', 'complexity' => 'simple'];
        $executionSteps = [['type' => 'implementation']];
        $outputResult = ['success' => true];
        
        $this->learningEngine->recordInteraction(
            $this->testTask,
            'implementation',
            $inputContext,
            $executionSteps,
            $outputResult,
            0.95, // Higher success score
            1800
        );
        
        $this->entityManager->refresh($originalPattern);
        
        // Pattern should be updated, not duplicated
        $patternsAfter = $patternRepo->findAll();
        $this->assertCount(1, $patternsAfter);
        
        // Confidence should be recalculated and usage count incremented
        $this->assertGreaterThan($originalConfidence, $originalPattern->getConfidenceScore());
        $this->assertEquals($originalUsageCount + 1, $originalPattern->getUsageCount());
        $this->assertNotNull($originalPattern->getLastSuccessfulUse());
    }

    private function createSuccessfulPattern(array $contextOverrides = []): AgentInteraction
    {
        $defaultContext = ['task_type' => 'feature', 'complexity' => 'simple'];
        $inputContext = array_merge($defaultContext, $contextOverrides);
        
        $executionSteps = [['type' => 'implementation'], ['type' => 'testing']];
        $outputResult = ['success' => true, 'quality_score' => 0.9];
        
        return $this->learningEngine->recordInteraction(
            $this->testTask,
            'implementation',
            $inputContext,
            $executionSteps,
            $outputResult,
            0.9,
            2000
        );
    }
}