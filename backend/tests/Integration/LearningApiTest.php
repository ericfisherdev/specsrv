<?php

namespace App\Tests\Integration;

use App\Entity\AgentInteraction;
use App\Entity\KnowledgePattern;
use App\Tests\AbstractWebTestCase;

class LearningApiTest extends AbstractWebTestCase
{
    private function getAuthenticatedClient(): object
    {
        $client = static::createClient();
        
        // Create a test user and authenticate
        $user = $this->createTestUser(['email' => 'test@learning.com']);
        $this->login($client, $user);
        
        return $client;
    }

    public function testRecordInteractionEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $requestData = [
            'task_id' => $task->getId(),
            'agent_type' => 'implementation',
            'input_context' => ['task_type' => 'feature', 'complexity' => 'simple'],
            'execution_steps' => [
                ['type' => 'analysis', 'outcome' => 'completed'],
                ['type' => 'implementation', 'outcome' => 'success']
            ],
            'output_result' => ['files_modified' => 2, 'tests_passed' => true],
            'success_score' => 0.85,
            'execution_time_ms' => 2000,
            'error_log' => null
        ];

        $client->request('POST', '/api/learning/record-interaction', [
            'json' => $requestData
        ]);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('interaction_id', $response['data']);
        $this->assertArrayHasKey('pattern_extracted', $response['data']);
        
        // Verify interaction was saved to database
        $interactionRepo = $this->entityManager->getRepository(AgentInteraction::class);
        $interactions = $interactionRepo->findAll();
        $this->assertCount(1, $interactions);
        
        $interaction = $interactions[0];
        $this->assertEquals('implementation', $interaction->getAgentType());
        $this->assertEquals(0.85, $interaction->getSuccessScore());
        $this->assertEquals(2000, $interaction->getExecutionTimeMs());
    }

    public function testRecordInteractionWithMissingFields(): void
    {
        $client = $this->getAuthenticatedClient();

        $requestData = [
            'task_id' => 999, // Missing other required fields
        ];

        $client->request('POST', '/api/learning/record-interaction', [
            'json' => $requestData
        ]);

        $this->assertResponseStatusCodeSame(400);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testRecordInteractionWithInvalidTask(): void
    {
        $client = $this->getAuthenticatedClient();

        $requestData = [
            'task_id' => 999999, // Non-existent task
            'agent_type' => 'implementation',
            'input_context' => [],
            'execution_steps' => [],
            'output_result' => [],
            'success_score' => 0.5,
            'execution_time_ms' => 1000
        ];

        $client->request('POST', '/api/learning/record-interaction', [
            'json' => $requestData
        ]);

        $this->assertResponseStatusCodeSame(404);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertEquals('TASK_NOT_FOUND', $response['error']['code']);
    }

    public function testRecommendSolutionEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // First create a pattern by recording a successful interaction
        $this->createTestPattern($client);

        $requestData = [
            'task_context' => ['task_type' => 'feature', 'complexity' => 'simple'],
            'agent_type' => 'implementation',
            'min_confidence' => 0.7
        ];

        $client->request('POST', '/api/learning/recommend-solution', [
            'json' => $requestData
        ]);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pattern', $response['data']);
        $this->assertArrayHasKey('confidence', $response['data']);
        $this->assertArrayHasKey('adapted_solution', $response['data']);
        $this->assertArrayHasKey('usage_history', $response['data']);
    }

    public function testRecommendSolutionWithNoPatterns(): void
    {
        $client = $this->getAuthenticatedClient();

        $requestData = [
            'task_context' => ['task_type' => 'unknown_task_type'],
            'agent_type' => 'unknown_agent',
            'min_confidence' => 0.9
        ];

        $client->request('POST', '/api/learning/recommend-solution', [
            'json' => $requestData
        ]);

        $this->assertResponseStatusCodeSame(404);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertEquals('NO_PATTERNS_FOUND', $response['error']['code']);
    }

    public function testGetPatternsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // Create some test patterns
        $this->createTestPattern($client);
        $this->createTestPattern($client, ['task_type' => 'debug']);

        $client->request('GET', '/api/learning/patterns');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('items', $response['data']);
        $this->assertArrayHasKey('pagination', $response['data']);
        
        $patterns = $response['data']['items'];
        $this->assertNotEmpty($patterns);
        
        // Check pattern structure
        $pattern = $patterns[0];
        $this->assertArrayHasKey('id', $pattern);
        $this->assertArrayHasKey('name', $pattern);
        $this->assertArrayHasKey('type', $pattern);
        $this->assertArrayHasKey('confidence_score', $pattern);
        $this->assertArrayHasKey('usage_count', $pattern);
    }

    public function testGetPatternsWithFilters(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // Create test patterns
        $this->createTestPattern($client);

        // Test filtering by agent type
        $client->request('GET', '/api/learning/patterns?agent_type=implementation');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        
        $patterns = $response['data']['items'];
        $this->assertNotEmpty($patterns);
        
        // All returned patterns should be of the filtered type
        foreach ($patterns as $pattern) {
            $this->assertEquals('implementation', $pattern['type']);
        }
    }

    public function testGetPerformanceAnalyticsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // Create some test data
        $this->createTestPattern($client);

        $client->request('GET', '/api/learning/analytics/performance?range=30d');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        
        $analytics = $response['data'];
        $this->assertArrayHasKey('interaction_metrics', $analytics);
        $this->assertArrayHasKey('pattern_analytics', $analytics);
        $this->assertArrayHasKey('learning_effectiveness', $analytics);
    }

    public function testSubmitPatternFeedbackEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // Create a test pattern first
        $this->createTestPattern($client);
        
        $patternRepo = $this->entityManager->getRepository(KnowledgePattern::class);
        $patterns = $patternRepo->findAll();
        $this->assertNotEmpty($patterns);
        
        $pattern = $patterns[0];

        $feedbackData = [
            'feedback_type' => 'success',
            'success_score' => 0.9,
            'comments' => 'This pattern worked well for my use case'
        ];

        $client->request('POST', "/api/learning/patterns/{$pattern->getId()}/feedback", [
            'json' => $feedbackData
        ]);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('feedback_id', $response['data']);
    }

    public function testSearchInteractionsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        
        // Create test patterns
        $this->createTestPattern($client);

        $searchData = [
            'agent_type' => 'implementation',
            'context' => ['task_type' => 'feature'],
            'min_success_score' => 0.7,
            'limit' => 10
        ];

        $client->request('POST', '/api/learning/interactions/search', [
            'json' => $searchData
        ]);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('patterns', $response['data']);
        $this->assertArrayHasKey('total_found', $response['data']);
        $this->assertArrayHasKey('returned', $response['data']);
    }

    public function testLearningHealthCheckEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/learning/health');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        
        $health = $response['data'];
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('services', $health);
        $this->assertEquals('healthy', $health['status']);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = static::createClient();

        // Try to access learning endpoints without authentication
        $endpoints = [
            'POST' => ['/api/learning/record-interaction'],
            'GET' => ['/api/learning/patterns', '/api/learning/analytics/performance']
        ];

        foreach ($endpoints as $method => $paths) {
            foreach ($paths as $path) {
                $client->request($method, $path);
                $this->assertResponseStatusCodeSame(401);
            }
        }
    }

    private function createTestPattern(object $client, array $contextOverrides = []): void
    {
        $user = $this->getUser($client);
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $defaultContext = ['task_type' => 'feature', 'complexity' => 'simple'];
        $context = array_merge($defaultContext, $contextOverrides);

        $requestData = [
            'task_id' => $task->getId(),
            'agent_type' => 'implementation',
            'input_context' => $context,
            'execution_steps' => [['type' => 'implementation', 'outcome' => 'success']],
            'output_result' => ['success' => true],
            'success_score' => 0.9,
            'execution_time_ms' => 1500
        ];

        $client->request('POST', '/api/learning/record-interaction', [
            'json' => $requestData
        ]);

        $this->assertResponseIsSuccessful();
    }
}