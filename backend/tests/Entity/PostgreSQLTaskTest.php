<?php

namespace App\Tests\Entity;

use App\Entity\File;
use App\Entity\GitLink;
use App\Entity\Task;
use App\Enum\TaskStatusEnum;
use App\Tests\AbstractKernelTestCase;

class PostgreSQLTaskTest extends AbstractKernelTestCase
{
    public function testTaskEnhancedPriorityFunctionality(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        // Test all available priorities
        $availablePriorities = Task::getAvailablePriorities();
        $this->assertContains('low', $availablePriorities);
        $this->assertContains('medium', $availablePriorities);
        $this->assertContains('high', $availablePriorities);
        $this->assertContains('critical', $availablePriorities);

        // Test priority colors (using Tailwind classes)
        $task->setPriority('critical');
        $this->assertEquals('text-red-600 bg-red-100', $task->getPriorityColor());
        $this->assertEquals('Critical', $task->getPriorityLabel());

        $task->setPriority('high');
        $this->assertEquals('text-orange-600 bg-orange-100', $task->getPriorityColor());
        $this->assertEquals('High', $task->getPriorityLabel());

        $task->setPriority('medium');
        $this->assertEquals('text-yellow-600 bg-yellow-100', $task->getPriorityColor());
        $this->assertEquals('Medium', $task->getPriorityLabel());

        $task->setPriority('low');
        $this->assertEquals('text-green-600 bg-green-100', $task->getPriorityColor());
        $this->assertEquals('Low', $task->getPriorityLabel());
    }

    public function testTaskAvailableStatuses(): void
    {
        $availableStatuses = Task::getAvailableStatuses();

        $expectedStatuses = ['backlog', 'todo', 'in_progress', 'review', 'completed', 'obsolete'];
        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, $availableStatuses);
        }
    }

    public function testTaskLifecycleCallbacks(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = new Task();
        $task->setTitle('Lifecycle Test');
        $task->setDescription('Testing lifecycle callbacks');
        $task->setProject($project);
        $task->setStatus(TaskStatusEnum::TODO);

        // Test onPrePersist
        $task->onPrePersist();
        $this->assertNotNull($task->getCreatedAt());
        $this->assertNotNull($task->getUpdatedAt());

        $originalCreatedAt = $task->getCreatedAt();
        $originalUpdatedAt = $task->getUpdatedAt();

        sleep(1);

        // Test onPreUpdate
        $task->onPreUpdate();
        $this->assertEquals($originalCreatedAt, $task->getCreatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $task->getUpdatedAt());
    }

    public function testTaskFileRelationship(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        // Create a file associated with the task
        $file = new File();
        $file->setFilename('task-document.pdf');
        $file->setPath('/documents/task-document.pdf');
        $file->setType('application/pdf');
        $file->setEntityType('task');
        $file->setEntityId($task->getId());

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        // Test file collection
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $task->getFiles());

        // Add file to task collection
        $task->addFile($file);
        $this->assertTrue($task->getFiles()->contains($file));

        // Remove file from task collection
        $task->removeFile($file);
        $this->assertFalse($task->getFiles()->contains($file));
    }

    public function testTaskGitLinkRelationship(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $gitLink = new GitLink();
        $gitLink->setCommitHash('abc123def456789');
        $gitLink->setPrReference('PR #142');
        $gitLink->setTask($task);

        $this->entityManager->persist($gitLink);
        $this->entityManager->flush();

        // Test git link collection
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $task->getGitLinks());

        // Add git link to task collection
        $task->addGitLink($gitLink);
        $this->assertTrue($task->getGitLinks()->contains($gitLink));

        // Remove git link from task collection
        $task->removeGitLink($gitLink);
        $this->assertFalse($task->getGitLinks()->contains($gitLink));
    }

    public function testTaskStatusEnum(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        // Test setting status with enum
        $task->setStatus(TaskStatusEnum::IN_PROGRESS);
        $this->assertEquals(TaskStatusEnum::IN_PROGRESS, $task->getStatus());
        $this->assertEquals('in_progress', $task->getStatusValue());

        $task->setStatus(TaskStatusEnum::COMPLETED);
        $this->assertEquals(TaskStatusEnum::COMPLETED, $task->getStatus());
        $this->assertEquals('completed', $task->getStatusValue());
    }

    public function testTaskWithPersistence(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        $task = new Task();
        $task->setTitle('Persistence Test Task');
        $task->setDescription('Testing task persistence with all features');
        $task->setProject($project);
        $task->setStatus(TaskStatusEnum::TODO);
        $task->setPriority('high');

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Verify the task was persisted correctly
        $this->assertNotNull($task->getId());
        $this->assertEquals('Persistence Test Task', $task->getTitle());
        $this->assertEquals('high', $task->getPriority());
        $this->assertEquals(TaskStatusEnum::TODO, $task->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getUpdatedAt());
    }
}
