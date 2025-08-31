<?php

namespace App\Repository;

use App\Entity\TagAlias;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TagAlias>
 */
class TagAliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagAlias::class);
    }

    /**
     * Find aliases by workspace
     */
    public function findByWorkspace(Project $workspace): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('a.alias', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if alias exists in workspace
     */
    public function aliasExists(string $alias, Project $workspace): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.workspace = :workspace')
            ->andWhere('LOWER(a.alias) = LOWER(:alias)')
            ->setParameter('workspace', $workspace)
            ->setParameter('alias', $alias)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}