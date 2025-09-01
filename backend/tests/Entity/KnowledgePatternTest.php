<?php

// backend/tests/Entity/KnowledgePatternTest.php

namespace App\Tests\Entity;

use App\Entity\KnowledgePattern;
use App\Entity\PatternVariation;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class KnowledgePatternTest extends TestCase
{
    public function testConstructor(): void
    {
        $pattern = new KnowledgePattern();

        $this->assertInstanceOf(\DateTime::class, $pattern->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $pattern->getUpdatedAt());
        $this->assertInstanceOf(ArrayCollection::class, $pattern->getVariations());
        $this->assertEmpty($pattern->getVariations());
        $this->assertEquals(0, $pattern->getUsageCount());
        $this->assertEquals([], $pattern->getContextSignature());
        $this->assertEquals([], $pattern->getSolutionTemplate());
        $this->assertEquals([], $pattern->getPrerequisites());
        $this->assertEquals([], $pattern->getTags());
    }

    public function testIncrementUsageCount(): void
    {
        $pattern = new KnowledgePattern();
        $initialUpdatedAt = $pattern->getUpdatedAt();

        // Wait a bit to ensure timestamp difference
        usleep(1000);

        $pattern->incrementUsageCount();

        $this->assertEquals(1, $pattern->getUsageCount());
        $this->assertGreaterThan($initialUpdatedAt, $pattern->getUpdatedAt());
    }

    public function testAddVariation(): void
    {
        $pattern = new KnowledgePattern();

        // Test adding variations
        $variation1 = $this->createMock(PatternVariation::class);
        $variation1->expects($this->once())->method('setBasePattern')->with($pattern);
        $pattern->addVariation($variation1);
        $this->assertTrue($pattern->getVariations()->contains($variation1));

        $variation2 = $this->createMock(PatternVariation::class);
        $variation2->expects($this->once())->method('setBasePattern')->with($pattern);
        $pattern->addVariation($variation2);
        $this->assertCount(2, $pattern->getVariations());
    }

    public function testRemoveVariation(): void
    {
        $pattern = new KnowledgePattern();

        // Create a mock that will be added and then removed
        $variation = $this->createMock(PatternVariation::class);
        
        // For adding: expect setBasePattern to be called with the pattern
        $variation->expects($this->exactly(2))->method('setBasePattern')
            ->willReturnCallback(function ($arg) use ($pattern, $variation) {
                // This will be called twice: once with $pattern, once with null
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertSame($pattern, $arg);
                } else {
                    $this->assertNull($arg);
                }
                return $variation; // Return self for fluent interface
            });
        
        // For removing: expect getBasePattern to return the pattern
        $variation->expects($this->once())->method('getBasePattern')->willReturn($pattern);

        // Add the variation
        $pattern->addVariation($variation);
        $this->assertCount(1, $pattern->getVariations());
        
        // Remove the variation
        $pattern->removeVariation($variation);
        $this->assertFalse($pattern->getVariations()->contains($variation));
        $this->assertCount(0, $pattern->getVariations());
    }

    public function testOnPrePersist(): void
    {
        $pattern = new KnowledgePattern();
        
        // Use reflection to set properties to null for testing lifecycle callbacks
        $reflection = new \ReflectionClass($pattern);
        $createdAtProperty = $reflection->getProperty('createdAt');
        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $createdAtProperty->setAccessible(true);
        $updatedAtProperty->setAccessible(true);
        $createdAtProperty->setValue($pattern, null);
        $updatedAtProperty->setValue($pattern, null);

        $pattern->onPrePersist();

        $this->assertInstanceOf(\DateTime::class, $pattern->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $pattern->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $pattern = new KnowledgePattern();

        $result = $pattern
            ->setPatternName('Test')
            ->setPatternType('test')
            ->setConfidenceScore(0.8);

        $this->assertSame($pattern, $result);
    }
}
