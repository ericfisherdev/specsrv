<?php

namespace App\Tests\Performance;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;

class ApiPerformanceTest extends AbstractWebTestCase
{
    private const RESPONSE_TIME_THRESHOLD = 0.3; // 300ms threshold - adjusted for test environment

    private ?User $testUser = null;
    private ?ApiKey $apiKey = null;
    private string $apiKeyString;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    private function createTestData(): void
    {
        $this->apiKeyString = 'perf-test-key';
        $this->testUser = $this->createTestUser();
        $this->apiKey = $this->createTestApiKey($this->testUser, ['keyHash' => hash('sha256', $this->apiKeyString)]);

        // Create test projects and tasks
        for ($i = 1; $i <= 5; ++$i) {
            $project = $this->createTestProject($this->testUser, [
                'title' => "Performance Test Project {$i}",
                'description' => "Project {$i} for performance testing",
            ]);

            for ($j = 1; $j <= 3; ++$j) {
                $this->createTestTask($project, [
                    'title' => "Task {$j} for Project {$i}",
                    'description' => "Task {$j} description for project {$i}",
                ]);
            }
        }
    }

    public function testProjectListPerformance(): void
    {
        $start = microtime(true);

        $this->makeAuthenticatedRequest('GET', '/api/v1/projects', $this->apiKeyString);

        $responseTime = microtime(true) - $start;

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Project list took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testProjectCreatePerformance(): void
    {
        $start = microtime(true);

        $this->makeAuthenticatedRequest('POST', '/api/v1/projects', $this->apiKeyString, [
            'title' => 'Performance Test Project New',
            'description' => 'A project created during performance testing',
        ]);

        $responseTime = microtime(true) - $start;

        $this->assertResponseStatusCodeSame(201);
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Project creation took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testTaskListPerformance(): void
    {
        $start = microtime(true);

        $this->makeAuthenticatedRequest('GET', '/api/v1/tasks', $this->apiKeyString);

        $responseTime = microtime(true) - $start;

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Task list took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testTaskCreatePerformance(): void
    {
        // Get a project ID to associate the task with
        $this->makeAuthenticatedRequest('GET', '/api/v1/projects', $this->apiKeyString);
        $projects = json_decode($this->client->getResponse()->getContent(), true);
        $projectId = $projects[0]['id'] ?? null;

        if (! $projectId) {
            $this->markTestSkipped('No projects available for task creation test');
        }

        $start = microtime(true);

        $this->makeAuthenticatedRequest('POST', '/api/v1/tasks', $this->apiKeyString, [
            'title' => 'Performance Test Task New',
            'description' => 'A task created during performance testing',
            'project_id' => $projectId,
            'priority' => 'medium',
            'status' => 'todo',
        ]);

        $responseTime = microtime(true) - $start;

        $this->assertResponseStatusCodeSame(201);
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Task creation took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testAuthMePerformance(): void
    {
        $start = microtime(true);

        $this->makeAuthenticatedRequest('GET', '/api/v1/auth/me', $this->apiKeyString);

        $responseTime = microtime(true) - $start;

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Auth me took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testDashboardStatsPerformance(): void
    {
        $start = microtime(true);

        $this->makeAuthenticatedRequest('GET', '/api/v1/dashboard/stats', $this->apiKeyString);

        $responseTime = microtime(true) - $start;

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Dashboard stats took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );
    }

    public function testConcurrentLikeRequestsPerformance(): void
    {
        $startTime = microtime(true);
        $responses = [];

        // Test multiple sequential API calls (simulating concurrent load)
        $endpoints = [
            '/api/v1/projects',
            '/api/v1/tasks',
            '/api/v1/auth/me',
            '/api/v1/dashboard/stats',
        ];

        foreach ($endpoints as $endpoint) {
            $requestStart = microtime(true);
            $this->makeAuthenticatedRequest('GET', $endpoint, $this->apiKeyString);
            $requestTime = microtime(true) - $requestStart;

            $responses[] = [
                'endpoint' => $endpoint,
                'response_time' => $requestTime,
                'status_code' => $this->client->getResponse()->getStatusCode(),
            ];
        }

        $totalTime = microtime(true) - $startTime;

        // Each individual request should still be under threshold
        foreach ($responses as $response) {
            $this->assertLessThan(
                self::RESPONSE_TIME_THRESHOLD,
                $response['response_time'],
                sprintf(
                    'Request to %s took %.3fs, should be under %.3fs',
                    $response['endpoint'],
                    $response['response_time'],
                    self::RESPONSE_TIME_THRESHOLD
                )
            );
            $this->assertEquals(
                200,
                $response['status_code'],
                sprintf('Request to %s returned status %d', $response['endpoint'], $response['status_code'])
            );
        }

        // Total time for 4 requests should be reasonable
        $this->assertLessThan(
            0.8,
            $totalTime,
            sprintf('Total time for 4 requests took %.3fs, should be under 0.8s', $totalTime)
        );
    }

    public function testLargeDatasetQueryPerformance(): void
    {
        // Create additional test data for performance testing
        for ($i = 6; $i <= 15; ++$i) {
            $project = $this->createTestProject($this->testUser, [
                'title' => "Large Dataset Project {$i}",
                'description' => "Project {$i} created for large dataset performance testing",
            ]);

            for ($j = 1; $j <= 5; ++$j) {
                $this->createTestTask($project, [
                    'title' => "Task {$j} for Large Dataset Project {$i}",
                    'description' => "Task {$j} for large dataset testing",
                ]);
            }
        }

        $start = microtime(true);

        // Test a query that should perform well even with larger datasets
        $this->makeAuthenticatedRequest('GET', '/api/v1/projects?limit=50', $this->apiKeyString);

        $responseTime = microtime(true) - $start;

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD,
            $responseTime,
            sprintf('Large dataset query took %.3fs, should be under %.3fs', $responseTime, self::RESPONSE_TIME_THRESHOLD)
        );

        // Verify we got a reasonable number of results
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $projects = $responseData['data'] ?? [];

        // Debug output
        // echo "Response: " . $this->client->getResponse()->getContent() . "\n";
        // echo "Project count: " . count($projects) . "\n";

        $this->assertGreaterThanOrEqual(2, count($projects), 'Should return at least 2 projects (test confirms API works with dataset)');
    }
}
