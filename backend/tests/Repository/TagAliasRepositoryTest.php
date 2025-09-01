<?php

namespace App\Tests\Repository;

use App\Entity\Project;
use App\Entity\TagAlias;
use App\Repository\TagAliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @group skip
 */
class TagAliasRepositoryTest extends TestCase
{
    private TagAliasRepository $repository;
    private EntityManagerInterface|MockObject $entityManager;
    private QueryBuilder|MockObject $queryBuilder;
    private Query|MockObject $query;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')
            ->with(TagAlias::class)
            ->willReturn($this->entityManager);

        $this->repository = new TagAliasRepository($managerRegistry);
    }

    public function testFindByWorkspace(): void
    {
        $workspace = new Project();
        $expectedAliases = [new TagAlias(), new TagAlias()];

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.workspace = :workspace');

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.alias', 'ASC');

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedAliases);

        $result = $this->repository->findByWorkspace($workspace);

        $this->assertEquals($expectedAliases, $result);
    }

    public function testAliasExists(): void
    {
        $workspace = new Project();
        $alias = 'test-alias';

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(a.id)');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('LOWER(a.alias) = LOWER(:alias)');

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $result = $this->repository->aliasExists($alias, $workspace);

        $this->assertTrue($result);
    }

    public function testAliasDoesNotExist(): void
    {
        $workspace = new Project();
        $alias = 'non-existent-alias';

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

        $result = $this->repository->aliasExists($alias, $workspace);

        $this->assertFalse($result);
    }
}
