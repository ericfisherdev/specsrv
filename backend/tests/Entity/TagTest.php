<?php

namespace App\Tests\Entity;

use App\Entity\Tag;
use App\Entity\TagAlias;
use App\Entity\Task;
use App\Entity\Project;
use App\Entity\File;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class TagTest extends TestCase
{
    private Tag $tag;

    protected function setUp(): void
    {
        $this->tag = new Tag();
    }

    public function testConstructorInitializesCollectionsAndTimestamps(): void
    {
        $tag = new Tag();

        $this->assertInstanceOf(ArrayCollection::class, $tag->getChildren());
        $this->assertInstanceOf(ArrayCollection::class, $tag->getTasks());
        $this->assertInstanceOf(ArrayCollection::class, $tag->getProjects());
        $this->assertInstanceOf(ArrayCollection::class, $tag->getFiles());
        $this->assertInstanceOf(ArrayCollection::class, $tag->getAliases());
        $this->assertInstanceOf(\DateTimeInterface::class, $tag->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $tag->getUpdatedAt());
        $this->assertNotNull($tag->getId());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $tag->getId());
    }

    public function testNameValidation(): void
    {
        $this->tag->setName('Test Tag');
        $this->assertEquals('Test Tag', $this->tag->getName());
    }

    public function testColorValidation(): void
    {
        $this->tag->setColor('#FF0000');
        $this->assertEquals('#FF0000', $this->tag->getColor());

        $this->tag->setColor('#123ABC');
        $this->assertEquals('#123ABC', $this->tag->getColor());

        $this->tag->setColor(null);
        $this->assertNull($this->tag->getColor());
    }

    public function testHierarchicalRelationshipsWithCycleDetection(): void
    {
        $parent = new Tag();
        $parent->setName('Parent');

        $child = new Tag();
        $child->setName('Child');

        $grandChild = new Tag();
        $grandChild->setName('GrandChild');

        // Normal hierarchy
        $child->setParent($parent);
        $grandChild->setParent($child);

        $this->assertEquals($parent, $child->getParent());
        $this->assertTrue($parent->getChildren()->contains($child));

        // Test cycle detection - should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Setting this parent would create a cycle');
        $parent->setParent($grandChild);
    }

    public function testSelfParentPrevention(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A tag cannot be its own parent');
        $this->tag->setParent($this->tag);
    }

    public function testUsageCountMethods(): void
    {
        $this->tag->setUsageCount(5);
        $this->assertEquals(5, $this->tag->getUsageCount());

        $this->tag->incrementUsageCount();
        $this->assertEquals(6, $this->tag->getUsageCount());

        $this->tag->decrementUsageCount();
        $this->assertEquals(5, $this->tag->getUsageCount());

        // Test decrement at zero doesn't go negative
        $this->tag->setUsageCount(0);
        $this->tag->decrementUsageCount();
        $this->assertEquals(0, $this->tag->getUsageCount());
    }

    public function testGetPath(): void
    {
        $grandParent = new Tag();
        $grandParent->setName('GrandParent');

        $parent = new Tag();
        $parent->setName('Parent');
        $parent->setParent($grandParent);

        $child = new Tag();
        $child->setName('Child');
        $child->setParent($parent);

        $this->assertEquals('GrandParent / Parent / Child', $child->getPath());
        $this->assertEquals('GrandParent / Parent', $parent->getPath());
        $this->assertEquals('GrandParent', $grandParent->getPath());
    }

    public function testIsDescendantOf(): void
    {
        $grandParent = new Tag();
        $parent = new Tag();
        $child = new Tag();

        $parent->setParent($grandParent);
        $child->setParent($parent);

        $this->assertTrue($child->isDescendantOf($parent));
        $this->assertTrue($child->isDescendantOf($grandParent));
        $this->assertFalse($parent->isDescendantOf($child));
        $this->assertFalse($grandParent->isDescendantOf($child));
        $this->assertFalse($child->isDescendantOf($child));
    }

    public function testGetAllDescendants(): void
    {
        $parent = new Tag();
        $child1 = new Tag();
        $child2 = new Tag();
        $grandChild = new Tag();

        $child1->setParent($parent);
        $child2->setParent($parent);
        $grandChild->setParent($child1);

        $descendants = $parent->getAllDescendants();

        $this->assertCount(3, $descendants);
        $this->assertContains($child1, $descendants);
        $this->assertContains($child2, $descendants);
        $this->assertContains($grandChild, $descendants);
    }

    public function testTaskRelationshipManagement(): void
    {
        $task = new Task();

        $this->tag->addTask($task);
        $this->assertTrue($this->tag->getTasks()->contains($task));
        $this->assertTrue($task->getTags()->contains($this->tag));

        $this->tag->removeTask($task);
        $this->assertFalse($this->tag->getTasks()->contains($task));
        $this->assertFalse($task->getTags()->contains($this->tag));
    }

    public function testProjectRelationshipManagement(): void
    {
        $project = new Project();

        $this->tag->addProject($project);
        $this->assertTrue($this->tag->getProjects()->contains($project));
        $this->assertTrue($project->getTags()->contains($this->tag));

        $this->tag->removeProject($project);
        $this->assertFalse($this->tag->getProjects()->contains($project));
        $this->assertFalse($project->getTags()->contains($this->tag));
    }

    public function testFileRelationshipManagement(): void
    {
        $file = new File();

        $this->tag->addFile($file);
        $this->assertTrue($this->tag->getFiles()->contains($file));
        $this->assertTrue($file->getTags()->contains($this->tag));

        $this->tag->removeFile($file);
        $this->assertFalse($this->tag->getFiles()->contains($file));
        $this->assertFalse($file->getTags()->contains($this->tag));
    }

    public function testAliasRelationshipManagement(): void
    {
        $alias = new TagAlias();

        $this->tag->addAlias($alias);
        $this->assertTrue($this->tag->getAliases()->contains($alias));
        $this->assertEquals($this->tag, $alias->getTag());

        $this->tag->removeAlias($alias);
        $this->assertFalse($this->tag->getAliases()->contains($alias));
    }

    public function testOnPreUpdateLifecycleCallback(): void
    {
        $originalUpdatedAt = $this->tag->getUpdatedAt();

        // Simulate time passing
        usleep(1000);

        $this->tag->onPreUpdate();

        $this->assertGreaterThan($originalUpdatedAt, $this->tag->getUpdatedAt());
    }

    public function testParentChangeUpdatesChildren(): void
    {
        $parent1 = new Tag();
        $parent2 = new Tag();
        $child = new Tag();

        $child->setParent($parent1);
        $this->assertTrue($parent1->getChildren()->contains($child));

        $child->setParent($parent2);
        $this->assertFalse($parent1->getChildren()->contains($child));
        $this->assertTrue($parent2->getChildren()->contains($child));
    }
}