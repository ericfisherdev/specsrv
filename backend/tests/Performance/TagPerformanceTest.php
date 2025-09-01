<?php

namespace App\Tests\Performance;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

class TagPerformanceTest extends TestCase
{
    public function testLargeHierarchyPerformance(): void
    {
        $start = microtime(true);

        $root = new Tag();
        $root->setName('Root');

        // Create a deep hierarchy (10 levels)
        $current = $root;
        for ($i = 1; $i <= 10; ++$i) {
            $child = new Tag();
            $child->setName("Level $i");
            $child->setParent($current);
            $current = $child;
        }

        // Test path generation performance
        $path = $current->getPath();

        $end = microtime(true);
        $executionTime = $end - $start;

        $this->assertLessThan(0.1, $executionTime, 'Path generation should be fast even for deep hierarchies');
        $this->assertStringContainsString('Root', $path);
        $this->assertStringContainsString('Level 10', $path);
    }

    public function testCircularReferenceDetection(): void
    {
        $tagA = new Tag();
        $tagA->setName('Tag A');

        $tagB = new Tag();
        $tagB->setName('Tag B');

        $tagC = new Tag();
        $tagC->setName('Tag C');

        // Create a chain: A -> B -> C
        $tagB->setParent($tagA);
        $tagC->setParent($tagB);

        // Try to create a cycle: A -> B -> C -> A
        $this->expectException(\InvalidArgumentException::class);
        $tagA->setParent($tagC);
    }

    public function testManyRelationshipsManagement(): void
    {
        $tag = new Tag();
        $tag->setName('Popular Tag');

        // Add many tasks
        for ($i = 0; $i < 100; ++$i) {
            $task = new \App\Entity\Task();
            $task->setTitle("Task $i");
            $tag->addTask($task);
        }

        $this->assertCount(100, $tag->getTasks());

        // Remove half the tasks
        $tasks = $tag->getTasks()->toArray();
        for ($i = 0; $i < 50; ++$i) {
            $tag->removeTask($tasks[$i]);
        }

        $this->assertCount(50, $tag->getTasks());
    }
}
