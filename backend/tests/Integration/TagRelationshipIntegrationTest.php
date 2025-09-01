<?php

namespace App\Tests\Integration;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\Project;
use App\Entity\File;
use App\Entity\TagAlias;
use PHPUnit\Framework\TestCase;

class TagRelationshipIntegrationTest extends TestCase
{
    public function testComplexHierarchicalStructure(): void
    {
        // Create a complex tag hierarchy
        $development = new Tag();
        $development->setName('Development');

        $frontend = new Tag();
        $frontend->setName('Frontend');
        $frontend->setParent($development);

        $backend = new Tag();
        $backend->setName('Backend');
        $backend->setParent($development);

        $react = new Tag();
        $react->setName('React');
        $react->setParent($frontend);

        $php = new Tag();
        $php->setName('PHP');
        $php->setParent($backend);

        // Test paths
        $this->assertEquals('Development / Frontend / React', $react->getPath());
        $this->assertEquals('Development / Backend / PHP', $php->getPath());

        // Test descendants
        $allDescendants = $development->getAllDescendants();
        $this->assertCount(4, $allDescendants);
        $this->assertContains($frontend, $allDescendants);
        $this->assertContains($backend, $allDescendants);
        $this->assertContains($react, $allDescendants);
        $this->assertContains($php, $allDescendants);

        // Test ancestry
        $this->assertTrue($react->isDescendantOf($frontend));
        $this->assertTrue($react->isDescendantOf($development));
        $this->assertFalse($php->isDescendantOf($frontend));
    }

    public function testTagEntityRelationships(): void
    {
        $tag = new Tag();
        $tag->setName('Important');

        $task = new Task();
        $task->setTitle('Test Task');

        $project = new Project();
        $project->setName('Test Project');

        $file = new File();
        $file->setFilename('test.php');

        // Add tag to all entities
        $task->addTag($tag);
        $project->addTag($tag);
        $file->addTag($tag);

        // Verify bidirectional relationships
        $this->assertTrue($tag->getTasks()->contains($task));
        $this->assertTrue($tag->getProjects()->contains($project));
        $this->assertTrue($tag->getFiles()->contains($file));

        $this->assertTrue($task->getTags()->contains($tag));
        $this->assertTrue($project->getTags()->contains($tag));
        $this->assertTrue($file->getTags()->contains($tag));

        // Test manual usage count calculation
        $expectedUsage = $tag->getTasks()->count() +
            $tag->getProjects()->count() +
            $tag->getFiles()->count();
        $this->assertEquals(3, $expectedUsage);
    }

    public function testTagAliasManagement(): void
    {
        $workspace = new Project();
        $workspace->setName('Test Workspace');

        $tag = new Tag();
        $tag->setName('JavaScript');
        $tag->setWorkspace($workspace);

        $alias1 = new TagAlias();
        $alias1->setAlias('JS');
        $alias1->setWorkspace($workspace);

        $alias2 = new TagAlias();
        $alias2->setAlias('js');
        $alias2->setWorkspace($workspace);

        $tag->addAlias($alias1);
        $tag->addAlias($alias2);

        $this->assertTrue($tag->getAliases()->contains($alias1));
        $this->assertTrue($tag->getAliases()->contains($alias2));
        $this->assertEquals($tag, $alias1->getTag());
        $this->assertEquals($tag, $alias2->getTag());

        // Test removal
        $tag->removeAlias($alias1);
        $this->assertFalse($tag->getAliases()->contains($alias1));
        $this->assertCount(1, $tag->getAliases());
    }

    public function testWorkspaceIsolation(): void
    {
        $workspace1 = new Project();
        $workspace1->setName('Workspace 1');

        $workspace2 = new Project();
        $workspace2->setName('Workspace 2');

        $tag1 = new Tag();
        $tag1->setName('Shared Name');
        $tag1->setWorkspace($workspace1);

        $tag2 = new Tag();
        $tag2->setName('Shared Name');
        $tag2->setWorkspace($workspace2);

        // Same name but different workspaces should be allowed
        $this->assertEquals('Shared Name', $tag1->getName());
        $this->assertEquals('Shared Name', $tag2->getName());
        $this->assertNotEquals($tag1->getWorkspace(), $tag2->getWorkspace());
    }
}