<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Tests\AbstractKernelTestCase;

class TaskTest extends AbstractKernelTestCase
{
    public function testTaskCanBeCreated(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('This is a test task');
        $task->setStatus('pending');
        $task->setProject($project);
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        $this->assertNotNull($task->getId());
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('This is a test task', $task->getDescription());
        $this->assertEquals('pending', $task->getStatus());
        $this->assertEquals($project, $task->getProject());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getUpdatedAt());
    }

    public function testTaskMustBelongToProject(): void
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('This is a test task');
        $task->setStatus('pending');
        // Not setting project - this should fail
        
        $this->entityManager->persist($task);
        
        $this->expectException(\Doctrine\DBAL\Exception\NotNullConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testTaskStatusValidation(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        
        foreach ($validStatuses as $status) {
            $task = new Task();
            $task->setTitle("Test Task - {$status}");
            $task->setDescription('Testing status validation');
            $task->setStatus($status);
            $task->setProject($project);
            
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            
            $this->assertEquals($status, $task->getStatus());
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }
    }

    public function testTaskCanHaveFiles(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);
        
        // Test file creation for task
        $file = new \App\Entity\File();
        $file->setFilename('task-file.txt');
        $file->setPath('/task/path');
        $file->setType('text/plain');
        $file->setEntityType('task');
        $file->setEntityId($task->getId());
        
        $this->entityManager->persist($file);
        $this->entityManager->flush();
        
        $this->assertEquals('task-file.txt', $file->getFilename());
        $this->assertEquals($task->getId(), $file->getEntityId());
    }

    public function testTaskCanHaveGitLinks(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);
        
        // Test GitLink creation for task
        $gitLink = new \App\Entity\GitLink();
        $gitLink->setCommitHash('abc123def456');
        $gitLink->setPrReference('PR #123');
        $gitLink->setTask($task);
        
        $this->entityManager->persist($gitLink);
        $this->entityManager->flush();
        
        $this->assertEquals('abc123def456', $gitLink->getCommitHash());
        $this->assertEquals('PR #123', $gitLink->getPrReference());
        $this->assertEquals($task, $gitLink->getTask());
    }

    public function testTaskBasicProperties(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project, [
            'title' => 'Properties Test',
            'description' => 'Testing task properties',
            'status' => 'in_progress'
        ]);
        
        $this->assertNotNull($task->getId());
        $this->assertEquals('Properties Test', $task->getTitle());
        $this->assertEquals('Testing task properties', $task->getDescription());
        $this->assertEquals('in_progress', $task->getStatus());
        $this->assertEquals($project, $task->getProject());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getUpdatedAt());
    }
}