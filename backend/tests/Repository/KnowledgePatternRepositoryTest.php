<?php

// backend/tests/Repository/KnowledgePatternRepositoryTest.php

namespace App\Tests\Repository;

use App\Entity\KnowledgePattern;
use App\Repository\KnowledgePatternRepository;
use App\Tests\AbstractKernelTestCase;

class KnowledgePatternRepositoryTest extends AbstractKernelTestCase
{
    private KnowledgePatternRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(KnowledgePatternRepository::class);
    }

    public function testFindByPatternType(): void
    {
        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern2 = $this->createTestPattern('implementation', 0.9);
        $pattern3 = $this->createTestPattern('testing', 0.7);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->persist($pattern3);
        $this->entityManager->flush();

        $results = $this->repository->findByPatternType('implementation', 10);

        $this->assertCount(2, $results);
        $this->assertEquals($pattern2->getId(), $results[0]->getId()); // Ordered by confidence DESC
        $this->assertEquals($pattern1->getId(), $results[1]->getId());
    }

    public function testFindHighConfidencePatterns(): void
    {
        $pattern1 = $this->createTestPattern('implementation', 0.9);
        $pattern2 = $this->createTestPattern('testing', 0.6);
        $pattern3 = $this->createTestPattern('analysis', 0.85);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->persist($pattern3);
        $this->entityManager->flush();

        $results = $this->repository->findHighConfidencePatterns(0.8);

        $this->assertCount(2, $results);
        foreach ($results as $pattern) {
            $this->assertGreaterThanOrEqual(0.8, $pattern->getConfidenceScore());
        }
    }

    public function testFindBySignature(): void
    {
        $contextSignature = ['task_type' => 'implementation', 'technology' => 'php'];

        $pattern = $this->createTestPattern('implementation', 0.8);
        $pattern->setContextSignature($contextSignature);

        $this->entityManager->persist($pattern);
        $this->entityManager->flush();

        $result = $this->repository->findBySignature($contextSignature, 'implementation');

        $this->assertNotNull($result);
        $this->assertEquals('implementation', $result->getPatternType());
    }

    public function testFindSimilarPatterns(): void
    {
        $contextSignature = ['task_type' => 'implementation', 'technology' => 'php'];

        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern1->setContextSignature(['task_type' => 'implementation', 'technology' => 'javascript']);

        $pattern2 = $this->createTestPattern('implementation', 0.9);
        $pattern2->setContextSignature(['task_type' => 'testing', 'technology' => 'php']);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->flush();

        $results = $this->repository->findSimilarPatterns($contextSignature, 'implementation', 0.7, 10);

        $this->assertIsArray($results);
        // Results should include patterns with some similarity to the context signature
    }

    public function testFindRecentlyUsed(): void
    {
        $recentDate = new \DateTime('-5 days');
        $oldDate = new \DateTime('-40 days');

        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern1->setLastSuccessfulUse($recentDate);

        $pattern2 = $this->createTestPattern('testing', 0.9);
        $pattern2->setLastSuccessfulUse($oldDate);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->flush();

        $results = $this->repository->findRecentlyUsed(30, 10);

        $this->assertCount(1, $results);
        $this->assertEquals($pattern1->getId(), $results[0]->getId());
    }

    public function testFindByTags(): void
    {
        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern1->setTags(['php', 'api']);

        $pattern2 = $this->createTestPattern('testing', 0.9);
        $pattern2->setTags(['javascript', 'frontend']);

        $pattern3 = $this->createTestPattern('analysis', 0.7);
        $pattern3->setTags(['php', 'database']);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->persist($pattern3);
        $this->entityManager->flush();

        $results = $this->repository->findByTags(['php'], 10);

        $this->assertCount(2, $results);
        foreach ($results as $pattern) {
            $this->assertContains('php', $pattern->getTags());
        }
    }

    public function testGetTopPerformingPatterns(): void
    {
        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern1->setUsageCount(10);

        $pattern2 = $this->createTestPattern('testing', 0.9);
        $pattern2->setUsageCount(5);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->flush();

        $results = $this->repository->getTopPerformingPatterns(10);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        // Should be ordered by performance score (confidence * usage)
        if (count($results) > 1) {
            $firstScore = $results[0]->getConfidenceScore() * $results[0]->getUsageCount();
            $secondScore = $results[1]->getConfidenceScore() * $results[1]->getUsageCount();
            $this->assertGreaterThanOrEqual($secondScore, $firstScore);
        }
    }

    public function testGetPatternAnalytics(): void
    {
        $pattern1 = $this->createTestPattern('implementation', 0.8);
        $pattern2 = $this->createTestPattern('testing', 0.9);

        $this->entityManager->persist($pattern1);
        $this->entityManager->persist($pattern2);
        $this->entityManager->flush();

        $results = $this->repository->getPatternAnalytics('30d');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        foreach ($results as $result) {
            $this->assertArrayHasKey('patternType', $result);
            $this->assertArrayHasKey('totalPatterns', $result);
            $this->assertArrayHasKey('avgConfidence', $result);
            $this->assertArrayHasKey('totalUsage', $result);
        }
    }

    private function createTestPattern(string $patternType, float $confidence): KnowledgePattern
    {
        $pattern = new KnowledgePattern();
        $pattern->setPatternName('Test Pattern '.$patternType);
        $pattern->setPatternType($patternType);
        $pattern->setConfidenceScore($confidence);
        $pattern->setDescription('Test pattern for '.$patternType);
        $pattern->setContextSignature(['test' => 'context']);
        $pattern->setSolutionTemplate(['test' => 'solution']);
        $pattern->setPrerequisites([]);
        $pattern->setTags([]);

        return $pattern;
    }
}
