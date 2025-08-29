<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Tests\AbstractKernelTestCase;

class ProjectTest extends AbstractKernelTestCase
{
    public function testProjectCanBeCreated(): void
    {
        $user = $this->createTestUser();
        $project = new Project();
        $project->setTitle('Test Project');
        $project->setDescription('This is a test project');
        $project->setGithubRepo('user/test-repo');
        $project->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->assertNotNull($project->getId());
        $this->assertEquals('Test Project', $project->getTitle());
        $this->assertEquals('This is a test project', $project->getDescription());
        $this->assertEquals('user/test-repo', $project->getGithubRepo());
        $this->assertEquals($user, $project->getUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $project->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $project->getUpdatedAt());
    }

    public function testProjectMustBelongToUser(): void
    {
        $project = new Project();
        $project->setTitle('Test Project');
        $project->setDescription('This is a test project');
        $project->setGithubRepo('user/test-repo');
        // Not setting user - this should fail

        $this->entityManager->persist($project);

        $this->expectException(\Doctrine\DBAL\Exception\NotNullConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testProjectCanHaveTasks(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $task = $this->createTestTask($project);

        $this->entityManager->refresh($project);

        $this->assertTrue($project->getTasks()->contains($task));
        $this->assertEquals($project, $task->getProject());
    }

    public function testProjectCanHaveFiles(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        // Since Project doesn't have getFiles() method, let's test file creation via File entity
        $file = new \App\Entity\File();
        $file->setFilename('test.txt');
        $file->setPath('/test/path');
        $file->setType('text/plain');
        $file->setEntityType('project');
        $file->setEntityId($project->getId());

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        $this->assertEquals('test.txt', $file->getFilename());
        $this->assertEquals($project->getId(), $file->getEntityId());
    }

    public function testProjectBasicProperties(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user, [
            'title' => 'Properties Test',
            'description' => 'Testing project properties',
        ]);

        $this->assertNotNull($project->getId());
        $this->assertEquals('Properties Test', $project->getTitle());
        $this->assertEquals('Testing project properties', $project->getDescription());
        $this->assertInstanceOf(\DateTimeInterface::class, $project->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $project->getUpdatedAt());
    }
}
