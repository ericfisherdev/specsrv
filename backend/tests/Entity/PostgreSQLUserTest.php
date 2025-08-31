<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\ApiKey;
use App\Tests\AbstractKernelTestCase;

class PostgreSQLUserTest extends AbstractKernelTestCase
{
    public function testUserJsonbRolesFunctionality(): void
    {
        $user = new User();
        $user->setEmail('jsonb-test@example.com');
        $user->setPassword('hashed_password');

        // Test complex roles array (JSONB functionality)
        $complexRoles = [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_PROJECT_MANAGER',
            'ROLE_DEVELOPER'
        ];

        $user->setRoles($complexRoles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Refresh from database to test JSONB persistence
        $this->entityManager->refresh($user);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_PROJECT_MANAGER', $roles);
        $this->assertContains('ROLE_DEVELOPER', $roles);
        $this->assertContains('ROLE_USER', $roles); // Always included
    }

    public function testUserLifecycleCallbacks(): void
    {
        $user = new User();
        $user->setEmail('lifecycle-test@example.com');
        $user->setPassword('password');

        // Test onPrePersist
        $user->onPrePersist();
        $this->assertNotNull($user->getCreatedAt());
        $this->assertNotNull($user->getUpdatedAt());

        $originalCreatedAt = $user->getCreatedAt();
        $originalUpdatedAt = $user->getUpdatedAt();

        sleep(1);

        // Test onPreUpdate
        $user->onPreUpdate();
        $this->assertEquals($originalCreatedAt, $user->getCreatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUserApiKeyRelationship(): void
    {
        $user = $this->createTestUser();

        // Create API keys for the user
        $apiKey1 = new ApiKey();
        $apiKey1->setName('Development Key');
        $apiKey1->setKeyHash(hash('sha256', 'dev-key-123'));
        $apiKey1->setUser($user);

        $apiKey2 = new ApiKey();
        $apiKey2->setName('Production Key');
        $apiKey2->setKeyHash(hash('sha256', 'prod-key-456'));
        $apiKey2->setUser($user);
        $apiKey2->setIsActive(false);

        $this->entityManager->persist($apiKey1);
        $this->entityManager->persist($apiKey2);
        $this->entityManager->flush();

        // Test relationships
        $this->assertEquals($user, $apiKey1->getUser());
        $this->assertEquals($user, $apiKey2->getUser());
        $this->assertTrue($apiKey1->isActive());
        $this->assertFalse($apiKey2->isActive());
    }

    public function testUserProjectsRelationship(): void
    {
        $user = $this->createTestUser();
        $project1 = $this->createTestProject($user, ['title' => 'First Project']);
        $project2 = $this->createTestProject($user, ['title' => 'Second Project']);

        // Refresh user to load the projects collection
        $this->entityManager->refresh($user);

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getProjects());
        $this->assertTrue($user->getProjects()->contains($project1));
        $this->assertTrue($user->getProjects()->contains($project2));
        $this->assertEquals(2, $user->getProjects()->count());
    }

    public function testUserWithComplexRolesAndPersistence(): void
    {
        $user = new User();
        $user->setEmail('complex-roles@example.com');
        $user->setPassword('secure_password');

        // Test with nested array-like roles structure
        $complexRoles = [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_PROJECT_MANAGER'
        ];

        $user->setRoles($complexRoles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Test that data persists correctly with JSONB
        $foundUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'complex-roles@example.com']);
        $this->assertNotNull($foundUser);
        $this->assertEquals($complexRoles, $foundUser->getRoles());
        $this->assertInstanceOf(\DateTimeInterface::class, $foundUser->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $foundUser->getUpdatedAt());
    }

    public function testUserTimestampPersistence(): void
    {
        $user = new User();
        $user->setEmail('timestamp-test@example.com');
        $user->setPassword('password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $originalUpdatedAt = $user->getUpdatedAt();

        // Update user and test that updated_at changes
        $user->setEmail('timestamp-updated@example.com');
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }
}