<?php

namespace App\Tests\Integration;

use App\Entity\Task;
use App\Enum\TaskStatusEnum;
use App\Tests\AbstractWebTestCase;

class SearchApiTest extends AbstractWebTestCase
{

    public function testSearchSuggestionsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        // Create test data
        $project1 = $this->createTestProject($user, [
            'title' => 'React Application',
            'description' => 'A modern React-based web application',
        ]);
        $project2 = $this->createTestProject($user, [
            'title' => 'Vue Dashboard',
            'description' => 'Administrative dashboard built with Vue.js',
        ]);

        $task1 = $this->createTestTask($project1, [
            'title' => 'Implement React components',
            'description' => 'Create reusable React components',
            'priority' => Task::PRIORITY_HIGH,
            'status' => TaskStatusEnum::TODO,
        ]);
        $task2 = $this->createTestTask($project2, [
            'title' => 'Vue router setup',
            'description' => 'Configure routing for Vue application',
            'priority' => Task::PRIORITY_MEDIUM,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        // Test search suggestions with query
        $this->makeRequest('GET', '/api/v1/search/suggestions?q=React');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('projects', $data);

        // Should find React-related items
        $this->assertNotEmpty($data['projects']);
        $this->assertNotEmpty($data['tasks']);

        // Check project structure
        $project = $data['projects'][0];
        $this->assertArrayHasKey('id', $project);
        $this->assertArrayHasKey('title', $project);
        $this->assertArrayHasKey('description', $project);
        $this->assertArrayHasKey('type', $project);
        $this->assertEquals('project', $project['type']);

        // Check task structure
        $task = $data['tasks'][0];
        $this->assertArrayHasKey('id', $task);
        $this->assertArrayHasKey('title', $task);
        $this->assertArrayHasKey('project_title', $task);
        $this->assertArrayHasKey('priority', $task);
        $this->assertArrayHasKey('status', $task);
        $this->assertArrayHasKey('type', $task);
        $this->assertEquals('task', $task['type']);
    }

    public function testSearchSuggestionsWithShortQuery(): void
    {
        // Query too short (less than 2 characters)
        $this->makeRequest('GET', '/api/v1/search/suggestions?q=R');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $data = $responseData['data'];
        $this->assertEmpty($data['tasks']);
        $this->assertEmpty($data['projects']);
    }

    public function testSearchSuggestionsNoResults(): void
    {
        $this->makeRequest('GET', '/api/v1/search/suggestions?q=nonexistent');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $data = $responseData['data'];
        $this->assertEmpty($data['tasks']);
        $this->assertEmpty($data['projects']);
    }

    public function testSearchSuggestionsLimit(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user, ['title' => 'Test Project']);

        // Create more than 5 tasks to test the limit
        for ($i = 1; $i <= 7; $i++) {
            $this->createTestTask($project, [
                'title' => "Test Task $i",
                'description' => "Test description for task $i",
            ]);
        }

        $this->makeRequest('GET', '/api/v1/search/suggestions?q=Test');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];
        
        // Should be limited to 5 results each
        $this->assertCount(1, $data['projects']); // 1 project
        $this->assertLessThanOrEqual(5, count($data['tasks'])); // Max 5 tasks
    }

    public function testSearchEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project1 = $this->createTestProject($user, ['title' => 'Project Alpha']);
        $project2 = $this->createTestProject($user, ['title' => 'Project Beta']);

        $this->createTestTask($project1, [
            'title' => 'Alpha Task 1',
            'description' => 'First task in Alpha project',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_HIGH,
        ]);
        $this->createTestTask($project2, [
            'title' => 'Beta Task 1',
            'description' => 'First task in Beta project',
            'status' => TaskStatusEnum::IN_PROGRESS,
            'priority' => Task::PRIORITY_MEDIUM,
        ]);

        $searchData = [
            'query' => 'Alpha',
        ];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('criteria', $data);

        // Should find Alpha-related task
        $this->assertNotEmpty($data['tasks']);
        $this->assertEquals(1, $data['count']);

        $task = $data['tasks'][0];
        $this->assertStringContainsString('Alpha', $task['title']);
    }

    public function testSearchWithFilters(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user, ['title' => 'Filter Test Project']);

        $this->createTestTask($project, [
            'title' => 'High Priority Task',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_HIGH,
        ]);
        $this->createTestTask($project, [
            'title' => 'Medium Priority Task',
            'status' => TaskStatusEnum::IN_PROGRESS,
            'priority' => Task::PRIORITY_MEDIUM,
        ]);

        // Search with priority filter
        $searchData = [
            'query' => 'Priority',
            'priority' => Task::PRIORITY_HIGH,
        ];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];
        $this->assertEquals(1, $data['count']);
        $this->assertEquals('High Priority Task', $data['tasks'][0]['title']);
    }

    public function testSearchWithProjectFilter(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project1 = $this->createTestProject($user, ['title' => 'Project 1']);
        $project2 = $this->createTestProject($user, ['title' => 'Project 2']);

        $this->createTestTask($project1, ['title' => 'Task in Project 1']);
        $this->createTestTask($project2, ['title' => 'Task in Project 2']);

        // Search within specific project
        $searchData = [
            'query' => 'Task',
            'project_id' => $project1->getId(),
        ];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];
        $this->assertEquals(1, $data['count']);
        $this->assertEquals('Task in Project 1', $data['tasks'][0]['title']);
        $this->assertEquals($project1->getId(), $data['tasks'][0]['project']['id']);
    }

    public function testSearchWithStatusFilter(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);

        $this->createTestTask($project, [
            'title' => 'Todo Task',
            'status' => TaskStatusEnum::TODO,
        ]);
        $this->createTestTask($project, [
            'title' => 'Completed Task',
            'status' => TaskStatusEnum::COMPLETED,
        ]);

        // Search for only TODO tasks
        $searchData = [
            'query' => 'Task',
            'status' => TaskStatusEnum::TODO->value,
        ];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];
        $this->assertEquals(1, $data['count']);
        $this->assertEquals('Todo Task', $data['tasks'][0]['title']);
        $this->assertEquals(TaskStatusEnum::TODO->value, $data['tasks'][0]['status']);
    }

    public function testSearchEmptyQuery(): void
    {
        $searchData = [
            'query' => '',
        ];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];
        $this->assertEquals(0, $data['count']);
        $this->assertEmpty($data['tasks']);
    }

    public function testSearchTaskDataStructure(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user, ['title' => 'Structure Test']);
        $task = $this->createTestTask($project, [
            'title' => 'Structure Task',
            'description' => 'Task to test data structure',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_HIGH,
        ]);

        $searchData = ['query' => 'Structure'];

        $this->makeRequest('POST', '/api/v1/search', $searchData);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $taskData = $responseData['data']['tasks'][0];

        // Verify complete task data structure
        $this->assertArrayHasKey('id', $taskData);
        $this->assertArrayHasKey('title', $taskData);
        $this->assertArrayHasKey('description', $taskData);
        $this->assertArrayHasKey('status', $taskData);
        $this->assertArrayHasKey('priority', $taskData);
        $this->assertArrayHasKey('project', $taskData);
        $this->assertArrayHasKey('created_at', $taskData);

        // Verify project sub-object
        $this->assertIsArray($taskData['project']);
        $this->assertArrayHasKey('id', $taskData['project']);
        $this->assertArrayHasKey('title', $taskData['project']);

        // Verify created_at format (ISO 8601)
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/',
            $taskData['created_at']
        );
    }

    public function testUnauthorizedAccess(): void
    {
        $endpoints = [
            'GET' => ['/api/v1/search/suggestions?q=test'],
            'POST' => ['/api/v1/search'],
        ];

        foreach ($endpoints as $method => $paths) {
            foreach ($paths as $path) {
                $this->client->request($method, $path);
                $this->assertResponseStatusCodeSame(401);
            }
        }
    }

    public function testInvalidJsonPayload(): void
    {
        $user = $this->createTestUser(['email' => 'invalid@test.com']);
        $apiKey = 'invalid-json-key';
        $this->createTestApiKey($user, ['keyHash' => hash('sha256', $apiKey)]);

        $this->client->request(
            'POST',
            '/api/v1/search',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_API_KEY' => $apiKey,
            ],
            'invalid json'
        );

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('INVALID_JSON', $responseData['error']['code']);
    }
}