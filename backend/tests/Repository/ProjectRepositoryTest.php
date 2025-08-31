<?php

namespace App\Tests\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Tests\AbstractKernelTestCase;

class ProjectRepositoryTest extends AbstractKernelTestCase
{
    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    public function testFindByUserWithFilters(): void
    {
        $user = $this->createTestUser();

        $this->createTestProject($user, [
            'title' => 'PostgreSQL Database Project',
            'description' => 'Advanced database features implementation'
        ]);

        $this->createTestProject($user, [
            'title' => 'API Development',
            'description' => 'REST API with PostgreSQL backend'
        ]);

        // Test search functionality
        $searchResults = $this->projectRepository->findByUserWithFilters($user, 'database');
        $this->assertIsArray($searchResults);
        $this->assertGreaterThan(0, count($searchResults));

        // Test empty search (should return all user projects)
        $allResults = $this->projectRepository->findByUserWithFilters($user);
        $this->assertIsArray($allResults);
        $this->assertGreaterThanOrEqual(2, count($allResults));
    }

    public function testSearchByTitle(): void
    {
        $user = $this->createTestUser();

        $this->createTestProject($user, [
            'title' => 'PostgreSQL Migration Project',
            'description' => 'Database migration and optimization'
        ]);

        $this->createTestProject($user, [
            'title' => 'Frontend Development',
            'description' => 'React application development'
        ]);

        $searchResults = $this->projectRepository->searchByTitle($user, 'PostgreSQL');
        $this->assertIsArray($searchResults);
        $this->assertGreaterThan(0, count($searchResults));

        // Verify results are ordered by updatedAt
        if (count($searchResults) > 1) {
            $firstResult = $searchResults[0];
            $secondResult = $searchResults[1];
            $this->assertGreaterThanOrEqual(
                $secondResult->getUpdatedAt(),
                $firstResult->getUpdatedAt()
            );
        }
    }

    public function testFindByTitleAndUser(): void
    {
        $user1 = $this->createTestUser(['email' => 'user1@example.com']);
        $user2 = $this->createTestUser(['email' => 'user2@example.com']);

        $project1 = $this->createTestProject($user1, ['title' => 'Unique Project']);
        $project2 = $this->createTestProject($user2, ['title' => 'Unique Project']);

        // Test finding project by specific user
        $foundProject = $this->projectRepository->findByTitleAndUser('Unique Project', $user1);
        $this->assertNotNull($foundProject);
        $this->assertEquals($project1->getId(), $foundProject->getId());
        $this->assertEquals($user1, $foundProject->getUser());

        // Test that it returns correct project for different user
        $foundProject2 = $this->projectRepository->findByTitleAndUser('Unique Project', $user2);
        $this->assertNotNull($foundProject2);
        $this->assertEquals($project2->getId(), $foundProject2->getId());
        $this->assertEquals($user2, $foundProject2->getUser());
    }

    public function testFindPaginatedByUser(): void
    {
        $user = $this->createTestUser();

        // Create multiple projects
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestProject($user, ['title' => "Project {$i}"]);
        }

        $paginatedResults = $this->projectRepository->findPaginatedByUser($user, 3, 0);
        $this->assertIsArray($paginatedResults);
        $this->assertLessThanOrEqual(3, count($paginatedResults));

        $secondPageResults = $this->projectRepository->findPaginatedByUser($user, 3, 3);
        $this->assertIsArray($secondPageResults);
        $this->assertLessThanOrEqual(2, count($secondPageResults));
    }

    public function testCountByUser(): void
    {
        $user1 = $this->createTestUser(['email' => 'user1@example.com']);
        $user2 = $this->createTestUser(['email' => 'user2@example.com']);

        $this->createTestProject($user1);
        $this->createTestProject($user1);
        $this->createTestProject($user2);

        $user1Count = $this->projectRepository->countByUser($user1);
        $this->assertEquals(2, $user1Count);

        $user2Count = $this->projectRepository->countByUser($user2);
        $this->assertEquals(1, $user2Count);
    }

    public function testFindByUser(): void
    {
        $user = $this->createTestUser();

        $project1 = $this->createTestProject($user, ['title' => 'First Project']);
        $project2 = $this->createTestProject($user, ['title' => 'Second Project']);

        $userProjects = $this->projectRepository->findByUser($user);
        $this->assertIsArray($userProjects);
        $this->assertCount(2, $userProjects);

        $projectIds = array_map(fn($p) => $p->getId(), $userProjects);
        $this->assertContains($project1->getId(), $projectIds);
        $this->assertContains($project2->getId(), $projectIds);
    }
}