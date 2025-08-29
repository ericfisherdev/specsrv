<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Tests\AbstractKernelTestCase;

class UserTest extends AbstractKernelTestCase
{
    public function testUserCanBeCreated(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('password123', $user->getPassword());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getUpdatedAt());
    }

    public function testUserEmailMustBeUnique(): void
    {
        // Create first user
        $user1 = new User();
        $user1->setEmail('duplicate@example.com');
        $user1->setPassword('password123');

        $this->entityManager->persist($user1);
        $this->entityManager->flush();

        // Try to create second user with same email
        $user2 = new User();
        $user2->setEmail('duplicate@example.com');
        $user2->setPassword('password456');

        $this->entityManager->persist($user2);

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testUserCanHaveProjects(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);

        $this->entityManager->refresh($user);

        $this->assertTrue($user->getProjects()->contains($project));
        $this->assertEquals($user, $project->getUser());
    }

    public function testUserCanHaveApiKeys(): void
    {
        $user = $this->createTestUser();

        // Create ApiKey manually since User doesn't have inverse relationship
        $apiKey = new \App\Entity\ApiKey();
        $apiKey->setKeyHash('test-hash-123');
        $apiKey->setName('Test API Key');
        $apiKey->setUser($user);

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();

        $this->assertEquals($user, $apiKey->getUser());
        $this->assertEquals('Test API Key', $apiKey->getName());
    }
}
