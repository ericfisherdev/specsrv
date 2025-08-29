<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[] Returns an array of Task objects for a project
     */
    public function findByProject(Project $project, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project);

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects by project and status
     */
    public function findByProjectAndStatus(Project $project, string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->andWhere('t.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
