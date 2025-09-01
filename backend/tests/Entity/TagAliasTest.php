<?php

namespace App\Tests\Entity;

use App\Entity\TagAlias;
use App\Entity\Tag;
use App\Entity\Project;
use PHPUnit\Framework\TestCase;

class TagAliasTest extends TestCase
{
    private TagAlias $tagAlias;

    protected function setUp(): void
    {
        $this->tagAlias = new TagAlias();
    }

    public function testConstructorInitializesIdAndCreatedAt(): void
    {
        $alias = new TagAlias();

        $this->assertNotNull($alias->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $alias->getCreatedAt());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $alias->getId());
    }

    public function testAliasProperty(): void
    {
        $this->tagAlias->setAlias('test-alias');
        $this->assertEquals('test-alias', $this->tagAlias->getAlias());
    }

    public function testTagRelationship(): void
    {
        $tag = new Tag();
        $this->tagAlias->setTag($tag);

        $this->assertEquals($tag, $this->tagAlias->getTag());
    }

    public function testWorkspaceRelationship(): void
    {
        $workspace = new Project();
        $this->tagAlias->setWorkspace($workspace);

        $this->assertEquals($workspace, $this->tagAlias->getWorkspace());
    }

    public function testCreatedAtProperty(): void
    {
        $date = new \DateTime();
        $this->tagAlias->setCreatedAt($date);

        $this->assertEquals($date, $this->tagAlias->getCreatedAt());
    }
}