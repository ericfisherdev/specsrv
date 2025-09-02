<?php

namespace App\Tests\Integration;

use App\Entity\Task;
use App\Enum\TaskStatusEnum;
use App\Tests\AbstractWebTestCase;

class KanbanApiTest extends AbstractWebTestCase
{

    public function testKanbanBoardsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        // Create test projects
        $project1 = $this->createTestProject($user, ['title' => 'Project 1']);
        $project2 = $this->createTestProject($user, ['title' => 'Project 2']);

        // Create test tasks with different statuses and priorities
        $this->createTestTask($project1, [
            'title' => 'Backlog Task',
            'status' => TaskStatusEnum::BACKLOG,
            'priority' => Task::PRIORITY_HIGH,
        ]);
        $this->createTestTask($project1, [
            'title' => 'Todo Task',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_MEDIUM,
        ]);
        $this->createTestTask($project2, [
            'title' => 'In Progress Task',
            'status' => TaskStatusEnum::IN_PROGRESS,
            'priority' => Task::PRIORITY_CRITICAL,
        ]);

        $this->makeRequest('GET', '/api/v1/kanban/boards');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        
        // Check projects list
        $this->assertArrayHasKey('projects', $data);
        $this->assertCount(2, $data['projects']);
        
        // Check tasks by status
        $this->assertArrayHasKey('tasks_by_status', $data);
        $tasksByStatus = $data['tasks_by_status'];
        
        // Verify all status categories exist
        $this->assertArrayHasKey(TaskStatusEnum::BACKLOG->value, $tasksByStatus);
        $this->assertArrayHasKey(TaskStatusEnum::TODO->value, $tasksByStatus);
        $this->assertArrayHasKey(TaskStatusEnum::IN_PROGRESS->value, $tasksByStatus);
        $this->assertArrayHasKey(TaskStatusEnum::REVIEW->value, $tasksByStatus);
        $this->assertArrayHasKey(TaskStatusEnum::COMPLETED->value, $tasksByStatus);

        // Check status configuration
        $this->assertArrayHasKey('statuses', $data);
        $this->assertIsArray($data['statuses']);
    }

    public function testKanbanBoardsWithProjectFilter(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project1 = $this->createTestProject($user, ['title' => 'Project 1']);
        $project2 = $this->createTestProject($user, ['title' => 'Project 2']);

        // Create tasks in different projects
        $this->createTestTask($project1, [
            'title' => 'Project 1 Task',
            'status' => TaskStatusEnum::TODO,
        ]);
        $this->createTestTask($project2, [
            'title' => 'Project 2 Task',
            'status' => TaskStatusEnum::TODO,
        ]);

        // Filter by specific project
        $this->makeRequest('GET', "/api/v1/kanban/boards?project={$project1->getId()}");

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        
        $data = $responseData['data'];
        $this->assertEquals((string) $project1->getId(), $data['selected_project']);
        
        // Should have tasks only from project 1
        $todoTasks = $data['tasks_by_status'][TaskStatusEnum::TODO->value];
        $this->assertCount(1, $todoTasks);
        $this->assertEquals('Project 1 Task', $todoTasks[0]['title']);
    }

    public function testMoveTaskEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project, [
            'title' => 'Test Task',
            'status' => TaskStatusEnum::TODO,
        ]);

        $requestData = [
            'taskId' => $task->getId(),
            'status' => TaskStatusEnum::IN_PROGRESS->value,
        ];

        $this->makeRequest('POST', '/api/v1/kanban/move-task', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Verify task status was updated in database
        $this->entityManager->refresh($task);
        $this->assertEquals(TaskStatusEnum::IN_PROGRESS, $task->getStatus());
    }

    public function testMoveTaskWithInvalidTaskId(): void
    {
        $requestData = [
            'taskId' => 99999,
            'status' => TaskStatusEnum::IN_PROGRESS->value,
        ];

        $this->makeRequest('POST', '/api/v1/kanban/move-task', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertNotNull($responseData, 'Response should be valid JSON: ' . $response->getContent());
        $this->assertArrayHasKey('success', $responseData, 'Response missing success field. Full response: ' . $response->getContent());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('TASK_NOT_FOUND', $responseData['error']['code']);
    }

    public function testMoveTaskWithInvalidStatus(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $requestData = [
            'taskId' => $task->getId(),
            'status' => 'invalid_status',
        ];

        $this->makeRequest('POST', '/api/v1/kanban/move-task', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('INVALID_STATUS', $responseData['error']['code']);
    }

    public function testMoveTaskMissingFields(): void
    {
        $requestData = [
            'taskId' => 123,
            // Missing status
        ];

        $this->makeRequest('POST', '/api/v1/kanban/move-task', $requestData);

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('MISSING_FIELDS', $responseData['error']['code']);
    }

    public function testGetTasksEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);
        
        // Create tasks with different priorities to test ordering
        $this->createTestTask($project, [
            'title' => 'Low Priority Task',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_LOW,
        ]);
        $this->createTestTask($project, [
            'title' => 'Critical Priority Task',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_CRITICAL,
        ]);

        $this->makeRequest('GET', '/api/v1/kanban/tasks');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $tasksByStatus = $responseData['data'];
        $todoTasks = $tasksByStatus[TaskStatusEnum::TODO->value];
        
        // Should be ordered by priority (critical first)
        $this->assertEquals('Critical Priority Task', $todoTasks[0]['title']);
        $this->assertEquals('Low Priority Task', $todoTasks[1]['title']);
    }

    public function testTasksDataStructure(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user, ['title' => 'Test Project']);
        $this->createTestTask($project, [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => TaskStatusEnum::TODO,
            'priority' => Task::PRIORITY_HIGH,
        ]);

        $this->makeRequest('GET', '/api/v1/kanban/tasks');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        
        $todoTasks = $responseData['data'][TaskStatusEnum::TODO->value];
        $task = $todoTasks[0];

        // Verify task data structure
        $this->assertArrayHasKey('id', $task);
        $this->assertArrayHasKey('title', $task);
        $this->assertArrayHasKey('description', $task);
        $this->assertArrayHasKey('status', $task);
        $this->assertArrayHasKey('priority', $task);
        $this->assertArrayHasKey('priority_value', $task);
        $this->assertArrayHasKey('project', $task);

        // Verify project data
        $this->assertIsArray($task['project']);
        $this->assertArrayHasKey('id', $task['project']);
        $this->assertArrayHasKey('name', $task['project']);
        $this->assertEquals('Test Project', $task['project']['name']);
    }

    public function testTaskLimitWithoutProjectFilter(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);
        
        // Create more than 6 tasks to test the limit
        for ($i = 1; $i <= 8; $i++) {
            $this->createTestTask($project, [
                'title' => "Task $i",
                'status' => TaskStatusEnum::TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ]);
        }

        $this->makeRequest('GET', '/api/v1/kanban/tasks');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        
        $todoTasks = $responseData['data'][TaskStatusEnum::TODO->value];
        
        // Should be limited to 6 tasks per project when no project filter is applied
        $this->assertCount(6, $todoTasks);
    }

    public function testUnauthorizedAccess(): void
    {
        $endpoints = [
            'GET' => ['/api/v1/kanban/boards', '/api/v1/kanban/tasks'],
            'POST' => ['/api/v1/kanban/move-task'],
        ];

        foreach ($endpoints as $method => $paths) {
            foreach ($paths as $path) {
                $this->client->request($method, $path);
                $this->assertResponseStatusCodeSame(401);
            }
        }
    }
}