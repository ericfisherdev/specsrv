<?php

namespace App\Tests;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractKernelTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean up database before each test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Clean learning system tables first (due to foreign keys)
        $connection->executeStatement('DELETE FROM pattern_variations');
        $connection->executeStatement('DELETE FROM agent_interactions');
        $connection->executeStatement('DELETE FROM knowledge_patterns');
        
        // Clean existing tables
        $connection->executeStatement('DELETE FROM git_links');
        $connection->executeStatement('DELETE FROM files');
        $connection->executeStatement('DELETE FROM tasks');
        $connection->executeStatement('DELETE FROM projects');
        $connection->executeStatement('DELETE FROM api_keys');
        $connection->executeStatement('DELETE FROM users');
    }

    protected function createTestUser(array $data = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? 'test@example.com');
        $user->setPassword($data['password'] ?? 'password123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createTestProject(User $user, array $data = []): Project
    {
        $project = new Project();
        $project->setTitle($data['title'] ?? 'Test Project');
        $project->setDescription($data['description'] ?? 'Test project description');
        $project->setGithubRepo($data['githubRepo'] ?? 'user/test-repo');
        $project->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function createTestTask(Project $project, array $data = []): Task
    {
        $task = new Task();
        $task->setTitle($data['title'] ?? 'Test Task');
        $task->setDescription($data['description'] ?? 'Test task description');
        $task->setStatus($data['status'] ?? TaskStatusEnum::TODO);
        $task->setProject($project);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    protected function getService(string $serviceId): object
    {
        return static::getContainer()->get($serviceId);
    }
}
