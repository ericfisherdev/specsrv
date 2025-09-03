<?php

namespace App\Tests\Integration;

use App\Entity\Project;
use App\Entity\Task;
use App\Enum\TaskStatusEnum;
use App\Tests\AbstractWebTestCase;

class DashboardApiTest extends AbstractWebTestCase
{
    public function testDashboardStatsEndpoint(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        // Create test projects
        $project1 = $this->createTestProject($user, ['title' => 'Test Project 1']);
        $project2 = $this->createTestProject($user, ['title' => 'Test Project 2']);

        // Create test tasks with different statuses
        $this->createTestTask($project1, ['title' => 'Todo Task 1', 'status' => TaskStatusEnum::TODO]);
        $this->createTestTask($project1, ['title' => 'Todo Task 2', 'status' => TaskStatusEnum::BACKLOG]);
        $this->createTestTask($project1, ['title' => 'In Progress Task', 'status' => TaskStatusEnum::IN_PROGRESS]);
        $this->createTestTask($project1, ['title' => 'Review Task', 'status' => TaskStatusEnum::REVIEW]);
        $this->createTestTask($project2, ['title' => 'Completed Task', 'status' => TaskStatusEnum::COMPLETED]);

        $this->makeRequest('GET', '/api/v1/dashboard/stats');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];

        // Check user information
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals($user->getEmail(), $data['user']['email']);

        // Check project statistics
        $this->assertArrayHasKey('projects', $data);
        $this->assertEquals(2, $data['projects']['total']);
        $this->assertArrayHasKey('recent', $data['projects']);
        $this->assertIsArray($data['projects']['recent']);

        // Check task statistics
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('stats', $data['tasks']);
        $this->assertArrayHasKey('recent', $data['tasks']);

        $taskStats = $data['tasks']['stats'];
        $this->assertEquals(5, $taskStats['total']); // All tasks
        $this->assertEquals(2, $taskStats['todo']); // TODO + BACKLOG
        $this->assertEquals(2, $taskStats['in_progress']); // IN_PROGRESS + REVIEW
        $this->assertEquals(1, $taskStats['completed']); // COMPLETED
    }

    public function testDashboardStatsWithNoData(): void
    {
        $this->makeRequest('GET', '/api/v1/dashboard/stats');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $data = $responseData['data'];
        $this->assertEquals(0, $data['projects']['total']);
        $this->assertEquals(0, $data['tasks']['stats']['total']);
        $this->assertEmpty($data['projects']['recent']);
        $this->assertEmpty($data['tasks']['recent']);
    }

    public function testDashboardStatsUnauthorized(): void
    {
        // Make request without API key header
        $this->client->request('GET', '/api/v1/dashboard/stats');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDashboardStatsRecentItemsLimit(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        // Create more than 5 projects to test pagination
        $projects = [];
        for ($i = 1; $i <= 7; ++$i) {
            $projects[] = $this->createTestProject($user, ['title' => "Project $i"]);
        }

        // Create more than 5 tasks
        for ($i = 1; $i <= 8; ++$i) {
            $this->createTestTask($projects[0], ['title' => "Task $i"]);
        }

        $this->makeRequest('GET', '/api/v1/dashboard/stats');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $data = $responseData['data'];

        // Should return all projects and tasks but recent ones should be limited to 5
        $this->assertEquals(7, $data['projects']['total']);
        $this->assertCount(5, $data['projects']['recent']);

        $this->assertEquals(8, $data['tasks']['stats']['total']);
        $this->assertCount(5, $data['tasks']['recent']);
    }

    public function testDashboardStatsDataStructure(): void
    {
        $client = $this->getAuthenticatedClient();
        $user = $this->getUser($client);

        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $this->makeRequest('GET', '/api/v1/dashboard/stats');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertNotNull($responseData, 'Response data should not be null');
        $this->assertArrayHasKey('data', $responseData, 'Response should contain data key');
        
        $data = $responseData['data'];
        $this->assertNotNull($data, 'Data should not be null');

        // Verify complete data structure
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('tasks', $data);

        // User structure
        $userData = $data['user'];
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('email', $userData);

        // Projects structure
        $projectsData = $data['projects'];
        $this->assertArrayHasKey('total', $projectsData);
        $this->assertArrayHasKey('recent', $projectsData);
        $this->assertIsArray($projectsData['recent']);

        if (! empty($projectsData['recent'])) {
            $recentProject = $projectsData['recent'][0];
            $this->assertArrayHasKey('id', $recentProject);
            $this->assertArrayHasKey('title', $recentProject);
        }

        // Tasks structure
        $tasksData = $data['tasks'];
        $this->assertArrayHasKey('stats', $tasksData);
        $this->assertArrayHasKey('recent', $tasksData);

        $taskStats = $tasksData['stats'];
        $this->assertArrayHasKey('total', $taskStats);
        $this->assertArrayHasKey('todo', $taskStats);
        $this->assertArrayHasKey('in_progress', $taskStats);
        $this->assertArrayHasKey('completed', $taskStats);

        $this->assertIsArray($tasksData['recent']);
        if (! empty($tasksData['recent'])) {
            $recentTask = $tasksData['recent'][0];
            $this->assertArrayHasKey('id', $recentTask);
            $this->assertArrayHasKey('title', $recentTask);
        }
    }
}
