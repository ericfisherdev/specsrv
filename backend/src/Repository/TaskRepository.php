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

    /**
     * @return Task[] Returns paginated tasks for a user across all projects
     */
    public function findPaginatedByUser($user, int $limit, int $offset, ?string $status = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if (null !== $search) {
            $qb->andWhere('(t.title LIKE :search OR t.description LIKE :search)')
               ->setParameter('search', '%'.$search.'%');
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total tasks for a user.
     */
    public function countByUser($user, ?string $status = null, ?string $search = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
            ->join('t.project', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if (null !== $search) {
            $qb->andWhere('(t.title LIKE :search OR t.description LIKE :search)')
               ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Task[] Returns paginated tasks for a specific project
     */
    public function findPaginatedByProject(Project $project, int $limit, int $offset, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project);

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count tasks for a specific project.
     */
    public function countByProject(Project $project, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project);

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
