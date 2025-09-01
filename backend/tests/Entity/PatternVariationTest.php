<?php

// backend/tests/Entity/PatternVariationTest.php

namespace App\Tests\Entity;

use App\Entity\PatternVariation;
use PHPUnit\Framework\TestCase;

class PatternVariationTest extends TestCase
{
    public function testConstructor(): void
    {
        $variation = new PatternVariation();

        $this->assertInstanceOf(\DateTime::class, $variation->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $variation->getUpdatedAt());
        $this->assertEquals(0, $variation->getUsageCount());
        $this->assertEquals([], $variation->getContextDifferences());
        $this->assertEquals([], $variation->getAdaptations());
    }

    public function testIncrementUsageCount(): void
    {
        $variation = new PatternVariation();
        $initialUpdatedAt = $variation->getUpdatedAt();

        // Wait a bit to ensure timestamp difference
        usleep(1000);

        $variation->incrementUsageCount();

        $this->assertEquals(1, $variation->getUsageCount());
        $this->assertGreaterThan($initialUpdatedAt, $variation->getUpdatedAt());
    }

    public function testOnPrePersist(): void
    {
        $variation = new PatternVariation();

        // Use reflection to set properties to null for testing lifecycle callbacks
        $reflection = new \ReflectionClass($variation);
        $createdAtProperty = $reflection->getProperty('createdAt');
        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $createdAtProperty->setAccessible(true);
        $updatedAtProperty->setAccessible(true);
        $createdAtProperty->setValue($variation, null);
        $updatedAtProperty->setValue($variation, null);

        $variation->onPrePersist();

        $this->assertInstanceOf(\DateTime::class, $variation->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $variation->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $variation = new PatternVariation();

        $result = $variation
            ->setSuccessRate(0.8)
            ->setUsageCount(5)
            ->setContextDifferences(['test' => 'diff']);

        $this->assertSame($variation, $result);
    }
}
