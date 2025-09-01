<?php

// backend/tests/Repository/PatternVariationRepositoryTest.php

namespace App\Tests\Repository;

use App\Entity\KnowledgePattern;
use App\Entity\PatternVariation;
use App\Repository\PatternVariationRepository;
use App\Tests\AbstractKernelTestCase;

class PatternVariationRepositoryTest extends AbstractKernelTestCase
{
    private PatternVariationRepository $repository;
    private KnowledgePattern $testPattern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(PatternVariationRepository::class);

        // Create test pattern
        $this->testPattern = $this->createTestPattern();
        $this->entityManager->persist($this->testPattern);
        $this->entityManager->flush();
    }

    public function testFindByBasePattern(): void
    {
        $variation1 = $this->createTestVariation($this->testPattern, 0.8, 5);
        $variation2 = $this->createTestVariation($this->testPattern, 0.9, 3);

        $this->entityManager->persist($variation1);
        $this->entityManager->persist($variation2);
        $this->entityManager->flush();

        $results = $this->repository->findByBasePattern($this->testPattern);

        $this->assertCount(2, $results);
        $this->assertEquals($variation2->getId(), $results[0]->getId()); // Ordered by success rate DESC
    }

    public function testFindHighPerformingVariations(): void
    {
        $variation1 = $this->createTestVariation($this->testPattern, 0.9, 5);
        $variation2 = $this->createTestVariation($this->testPattern, 0.6, 8);
        $variation3 = $this->createTestVariation($this->testPattern, 0.85, 3);

        $this->entityManager->persist($variation1);
        $this->entityManager->persist($variation2);
        $this->entityManager->persist($variation3);
        $this->entityManager->flush();

        $results = $this->repository->findHighPerformingVariations(0.8);

        $this->assertCount(2, $results);
        foreach ($results as $variation) {
            $this->assertGreaterThanOrEqual(0.8, $variation->getSuccessRate());
            $this->assertGreaterThanOrEqual(2, $variation->getUsageCount());
        }
    }

    public function testFindBestVariationForPattern(): void
    {
        $variation1 = $this->createTestVariation($this->testPattern, 0.8, 5);
        $variation2 = $this->createTestVariation($this->testPattern, 0.9, 3);
        $variation3 = $this->createTestVariation($this->testPattern, 0.95, 1); // Won't qualify due to low usage

        $this->entityManager->persist($variation1);
        $this->entityManager->persist($variation2);
        $this->entityManager->persist($variation3);
        $this->entityManager->flush();

        $result = $this->repository->findBestVariationForPattern($this->testPattern);

        $this->assertNotNull($result);
        $this->assertEquals($variation2->getId(), $result->getId()); // Best with usage >= 2
    }

    public function testGetVariationPerformanceStats(): void
    {
        $variation1 = $this->createTestVariation($this->testPattern, 0.8, 5);
        $variation2 = $this->createTestVariation($this->testPattern, 0.9, 3);

        $this->entityManager->persist($variation1);
        $this->entityManager->persist($variation2);
        $this->entityManager->flush();

        $stats = $this->repository->getVariationPerformanceStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalVariations', $stats);
        $this->assertArrayHasKey('avgSuccessRate', $stats);
        $this->assertArrayHasKey('totalUsage', $stats);
        $this->assertArrayHasKey('maxSuccessRate', $stats);
        $this->assertArrayHasKey('minSuccessRate', $stats);

        $this->assertEquals(2, $stats['totalVariations']);
        $this->assertEquals(8, $stats['totalUsage']);
    }

    private function createTestPattern(): KnowledgePattern
    {
        $pattern = new KnowledgePattern();
        $pattern->setPatternName('Test Base Pattern');
        $pattern->setPatternType('implementation');
        $pattern->setConfidenceScore(0.8);
        $pattern->setDescription('Test base pattern');
        $pattern->setContextSignature(['test' => 'context']);
        $pattern->setSolutionTemplate(['test' => 'solution']);
        $pattern->setPrerequisites([]);
        $pattern->setTags([]);

        return $pattern;
    }

    private function createTestVariation(KnowledgePattern $basePattern, float $successRate, int $usageCount): PatternVariation
    {
        $variation = new PatternVariation();
        $variation->setBasePattern($basePattern);
        $variation->setSuccessRate($successRate);
        $variation->setUsageCount($usageCount);
        $variation->setContextDifferences(['diff' => 'test']);
        $variation->setAdaptations(['adaptation' => 'test']);

        return $variation;
    }
}
