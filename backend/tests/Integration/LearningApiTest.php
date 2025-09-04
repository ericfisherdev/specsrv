<?php

namespace App\Tests\Integration;

use App\Entity\AgentInteraction;
use App\Entity\KnowledgePattern;
use App\Tests\AbstractWebTestCase;

class LearningApiTest extends AbstractWebTestCase
{
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
                ['type' => 'implementation', 'outcome' => 'success'],
            ],
            'output_result' => ['files_modified' => 2, 'tests_passed' => true],
            'success_score' => 0.85,
            'execution_time_ms' => 2000,
            'error_log' => null,
        ];

        $this->makeRequest('POST', '/api/v1/learning/record-interaction', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('interaction_id', $responseData['data']);
        $this->assertArrayHasKey('pattern_extracted', $responseData['data']);

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
        $requestData = [
            'task_id' => 999, // Missing other required fields
        ];

        $this->makeRequest('POST', '/api/v1/learning/record-interaction', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testRecordInteractionWithInvalidTask(): void
    {
        $requestData = [
            'task_id' => 999999, // Non-existent task
            'agent_type' => 'implementation',
            'input_context' => [],
            'execution_steps' => [],
            'output_result' => [],
            'success_score' => 0.5,
            'execution_time_ms' => 1000,
        ];

        $this->makeRequest('POST', '/api/v1/learning/record-interaction', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('TASK_NOT_FOUND', $responseData['error']['code']);
    }

    public function testRecommendSolutionEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();

        // First create a pattern by recording a successful interaction
        $this->createTestPattern($client);

        $requestData = [
            'task_context' => ['task_type' => 'feature', 'complexity' => 'simple'],
            'agent_type' => 'implementation',
            'min_confidence' => 0.7,
        ];

        $this->makeRequest('POST', '/api/v1/learning/recommend-solution', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Auth failed. Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('pattern', $responseData['data']);
        $this->assertArrayHasKey('confidence', $responseData['data']);
        $this->assertArrayHasKey('adapted_solution', $responseData['data']);
        $this->assertArrayHasKey('usage_history', $responseData['data']);
    }

    public function testRecommendSolutionWithNoPatterns(): void
    {
        $client = $this->getAuthenticatedClient();

        $requestData = [
            'task_context' => ['task_type' => 'unknown_task_type'],
            'agent_type' => 'unknown_agent',
            'min_confidence' => 0.9,
        ];

        $this->makeRequest('POST', '/api/v1/learning/recommend-solution', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('NO_PATTERNS_FOUND', $responseData['error']['code']);
    }

    public function testGetPatternsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();

        // Create some test patterns
        $this->createTestPattern($client);
        $this->createTestPattern($client, ['task_type' => 'debug']);

        $this->makeRequest('GET', '/api/v1/learning/patterns', []);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('items', $responseData['data']);
        $this->assertArrayHasKey('pagination', $responseData['data']);

        $patterns = $responseData['data']['items'];
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
        $this->makeRequest('GET', '/api/v1/learning/patterns?agent_type=implementation', []);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $patterns = $responseData['data']['items'];
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

        $this->makeRequest('GET', '/api/v1/learning/analytics/performance?range=30d', []);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $analytics = $responseData['data'];
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
            'comments' => 'This pattern worked well for my use case',
        ];

        $this->makeRequest('POST', "/api/v1/learning/patterns/{$pattern->getId()}/feedback", $feedbackData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('feedback_id', $responseData['data']);
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
            'limit' => 10,
        ];

        $this->makeRequest('POST', '/api/v1/learning/interactions/search', $searchData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('patterns', $responseData['data']);
        $this->assertArrayHasKey('total_found', $responseData['data']);
        $this->assertArrayHasKey('returned', $responseData['data']);
    }

    public function testLearningHealthCheckEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();

        $this->makeRequest('GET', '/api/v1/learning/health', []);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $health = $responseData['data'];
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('services', $health);
        $this->assertEquals('healthy', $health['status']);
    }

    public function testUnauthorizedAccess(): void
    {
        // Try to access learning endpoints without authentication
        $endpoints = [
            'POST' => ['/api/v1/learning/record-interaction'],
            'GET' => ['/api/v1/learning/patterns', '/api/v1/learning/analytics/performance'],
        ];

        foreach ($endpoints as $method => $paths) {
            foreach ($paths as $path) {
                // Make request without API key header
                $this->client->request($method, $path);
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
            'execution_time_ms' => 1500,
        ];

        $this->makeRequest('POST', '/api/v1/learning/record-interaction', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
