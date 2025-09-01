<?php

namespace App\Tests\Repository;

use App\Entity\Tag;
use App\Entity\TagAlias;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\File;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class TagRepositoryTest extends TestCase
{
    private TagRepository $repository;
    private EntityManagerInterface|MockObject $entityManager;
    private QueryBuilder|MockObject $queryBuilder;
    private Query|MockObject $query;
    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->connection = $this->createMock(Connection::class);

        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('addOrderBy')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('leftJoin')->willReturnSelf();
        $this->queryBuilder->method('join')->willReturnSelf();
        $this->queryBuilder->method('distinct')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);

        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $this->repository = new TagRepository($managerRegistry);

        // Use reflection to inject the mocked EntityManager
        $reflection = new \ReflectionClass($this->repository);
        $property = $reflection->getProperty('_em');
        $property->setAccessible(true);
        $property->setValue($this->repository, $this->entityManager);
    }

    public function testFindByWorkspace(): void
    {
        $workspace = new Project();
        $expectedTags = [new Tag(), new Tag()];

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('t.workspace = :workspace');

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('t.usageCount', 'DESC');

        $this->queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('t.name', 'ASC');

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedTags);

        $result = $this->repository->findByWorkspace($workspace);

        $this->assertEquals($expectedTags, $result);
    }

    public function testFindByWorkspaceWithSearch(): void
    {
        $workspace = new Project();
        $searchTerm = 'test';
        $expectedTags = [new Tag()];

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('LOWER(t.name) LIKE LOWER(:search) OR LOWER(t.description) LIKE LOWER(:search)');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(['workspace', $workspace], ['search', '%test%']);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedTags);

        $result = $this->repository->findByWorkspace($workspace, $searchTerm);

        $this->assertEquals($expectedTags, $result);
    }

    public function testFindRootTagsByWorkspace(): void
    {
        $workspace = new Project();
        $expectedTags = [new Tag(), new Tag()];

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('t.parent IS NULL');

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedTags);

        $result = $this->repository->findRootTagsByWorkspace($workspace);

        $this->assertEquals($expectedTags, $result);
    }

    public function testFindMostUsed(): void
    {
        $workspace = new Project();
        $limit = 5;
        $expectedTags = [new Tag(), new Tag()];

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('t.usageCount', 'DESC');

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($limit);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedTags);

        $result = $this->repository->findMostUsed($workspace, $limit);

        $this->assertEquals($expectedTags, $result);
    }

    public function testFindByNameInWorkspace(): void
    {
        $workspace = new Project();
        $name = 'Test Tag';
        $expectedTag = new Tag();

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('LOWER(t.name) = LOWER(:name)');

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expectedTag);

        $result = $this->repository->findByNameInWorkspace($name, $workspace);

        $this->assertEquals($expectedTag, $result);
    }

    public function testFindByAlias(): void
    {
        $workspace = new Project();
        $alias = 'test-alias';
        $expectedTag = new Tag();

        $this->queryBuilder->expects($this->once())
            ->method('join')
            ->with('t.aliases', 'a');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('LOWER(a.alias) = LOWER(:alias)');

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expectedTag);

        $result = $this->repository->findByAlias($alias, $workspace);

        $this->assertEquals($expectedTag, $result);
    }

    public function testMergeTagsValidation(): void
    {
        $source = new Tag();
        $target = new Tag();

        $workspace1 = new Project();
        $workspace2 = new Project();

        $source->setWorkspace($workspace1);
        $target->setWorkspace($workspace2);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot merge tags across different workspaces.');

        $this->repository->mergeTags($source, $target);
    }

    public function testMergeTagsSelfValidation(): void
    {
        $tag = new Tag();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot merge into self or descendant.');

        $this->repository->mergeTags($tag, $tag);
    }

    public function testUpdateUsageCount(): void
    {
        $tag = new Tag();
        $task = new Task();
        $project = new Project();
        $file = new File();

        $tag->addTask($task);
        $tag->addProject($project);
        $tag->addFile($file);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($tag);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->updateUsageCount($tag);

        $this->assertEquals(3, $tag->getUsageCount());
    }
}