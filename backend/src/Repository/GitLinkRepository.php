<?php

namespace App\Repository;

use App\Entity\GitLink;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GitLink>
 */
class GitLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GitLink::class);
    }

    /**
     * @return GitLink[] Returns an array of GitLink objects for a task
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.task = :task')
            ->setParameter('task', $task)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find GitLink by commit hash.
     */
    public function findByCommitHash(string $commitHash): ?GitLink
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.commitHash = :commitHash')
            ->setParameter('commitHash', $commitHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find GitLink by PR reference.
     */
    public function findByPrReference(string $prReference): ?GitLink
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.prReference = :prReference')
            ->setParameter('prReference', $prReference)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
