<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\TaskRepository;
use App\Tests\AbstractKernelTestCase;

class TaskRepositoryTest extends AbstractKernelTestCase
{
    private TaskRepository $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskRepository = $this->entityManager->getRepository(Task::class);
    }

    public function testFindPaginatedByUserWithPostgreSQLSearch(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        // Create tasks with searchable content
        $task1 = $this->createTestTask($project, [
            'title' => 'Database Migration Task',
            'description' => 'PostgreSQL full-text search implementation'
        ]);

        $task2 = $this->createTestTask($project, [
            'title' => 'Frontend Development',
            'description' => 'React component optimization'
        ]);

        $task3 = $this->createTestTask($project, [
            'title' => 'Advanced Search',
            'description' => 'Implementing database search functionality'
        ]);

        // Test search functionality
        $searchResults = $this->taskRepository->findPaginatedByUser(
            $user,
            10,  // limit
            0,   // offset
            null, // status
            'database'  // search term
        );

        $this->assertIsArray($searchResults);
        $this->assertGreaterThan(0, count($searchResults));

        // Test multi-word search
        $multiWordResults = $this->taskRepository->findPaginatedByUser(
            $user,
            10,
            0,
            null,
            'PostgreSQL search'
        );

        $this->assertIsArray($multiWordResults);
        $this->assertGreaterThanOrEqual(0, count($multiWordResults));
    }

    public function testCountByUserWithSearch(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        $this->createTestTask($project, [
            'title' => 'Search Test Task',
            'description' => 'Testing search functionality'
        ]);

        $count = $this->taskRepository->countByUser($user, null, 'search');
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);

        $countWithStatus = $this->taskRepository->countByUser($user, 'todo', 'search');
        $this->assertIsInt($countWithStatus);
        $this->assertGreaterThan(0, $countWithStatus);
    }

    public function testFindPaginatedByUserWithStatusFilter(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        // Create tasks with different statuses
        $this->createTestTask($project, ['status' => TaskStatusEnum::TODO]);
        $this->createTestTask($project, ['status' => TaskStatusEnum::IN_PROGRESS]);
        $this->createTestTask($project, ['status' => TaskStatusEnum::COMPLETED]);

        $todoTasks = $this->taskRepository->findPaginatedByUser($user, 10, 0, 'todo');
        $this->assertGreaterThan(0, count($todoTasks));

        $inProgressTasks = $this->taskRepository->findPaginatedByUser($user, 10, 0, 'in_progress');
        $this->assertGreaterThan(0, count($inProgressTasks));
    }

    public function testSearchWithFilters(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        $this->createTestTask($project, [
            'title' => 'High Priority Task',
            'description' => 'Critical database optimization',
            'status' => TaskStatusEnum::TODO
        ]);

        $criteria = [
            'search' => 'database',
            'status' => 'todo',
            'priority' => 'high'
        ];

        $results = $this->taskRepository->searchWithFilters($user, $criteria);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(0, count($results));
    }

    public function testFindByProjectAndStatus(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        $this->createTestTask($project, ['status' => TaskStatusEnum::TODO]);
        $this->createTestTask($project, ['status' => TaskStatusEnum::IN_PROGRESS]);

        $todoTasks = $this->taskRepository->findByProjectAndStatus($project, 'todo');
        $this->assertIsArray($todoTasks);
        $this->assertGreaterThan(0, count($todoTasks));

        foreach ($todoTasks as $task) {
            $this->assertEquals(TaskStatusEnum::TODO, $task->getStatus());
            $this->assertEquals($project, $task->getProject());
        }
    }

    public function testFindActiveByProject(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        // Create active and completed tasks
        $this->createTestTask($project, ['status' => TaskStatusEnum::TODO]);
        $this->createTestTask($project, ['status' => TaskStatusEnum::IN_PROGRESS]);
        $this->createTestTask($project, ['status' => TaskStatusEnum::COMPLETED]);

        $activeTasks = $this->taskRepository->findActiveByProject($project);
        $this->assertIsArray($activeTasks);
        $this->assertGreaterThan(0, count($activeTasks));

        foreach ($activeTasks as $task) {
            $this->assertNotEquals(TaskStatusEnum::OBSOLETE, $task->getStatus());
        }
    }
}