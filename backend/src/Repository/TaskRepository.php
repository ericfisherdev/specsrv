<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Task;
use App\Enum\TaskStatusEnum;
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
    public function findPaginatedByUser(\App\Entity\User $user, int $limit, int $offset, ?string $status = null, ?string $search = null): array
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
    public function countByUser(\App\Entity\User $user, ?string $status = null, ?string $search = null): int
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

    /**
     * @return Task[] Advanced search with multiple filters
     */
    public function searchWithFilters(\App\Entity\User $user, array $criteria): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

        // Text search
        if (! empty($criteria['query'])) {
            $qb->andWhere('(t.title LIKE :search OR t.description LIKE :search)')
               ->setParameter('search', '%'.$criteria['query'].'%');
        }

        // Project filter
        if (! empty($criteria['project_id'])) {
            $qb->andWhere('t.project = :project_id')
               ->setParameter('project_id', $criteria['project_id']);
        }

        // Status filter
        if (! empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        // Priority filter
        if (! empty($criteria['priority'])) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $criteria['priority']);
        }

        // Date range filter
        if (! empty($criteria['date_range'])) {
            $dateConstraints = $this->getDateRangeConstraints($criteria['date_range']);
            if ($dateConstraints) {
                $qb->andWhere('t.createdAt >= :date_from AND t.createdAt <= :date_to')
                   ->setParameter('date_from', $dateConstraints['from'])
                   ->setParameter('date_to', $dateConstraints['to']);
            }
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get date range constraints for filtering.
     */
    private function getDateRangeConstraints(string $range): ?array
    {
        $now = new \DateTime();
        $from = clone $now;
        $to = clone $now;

        switch ($range) {
            case 'today':
                $from->setTime(0, 0, 0);
                $to->setTime(23, 59, 59);

                break;

            case 'week':
                $from->modify('monday this week')->setTime(0, 0, 0);
                $to->modify('sunday this week')->setTime(23, 59, 59);

                break;

            case 'month':
                $from->modify('first day of this month')->setTime(0, 0, 0);
                $to->modify('last day of this month')->setTime(23, 59, 59);

                break;

            case 'quarter':
                $month = (int) $now->format('n');
                $quarterStart = floor(($month - 1) / 3) * 3 + 1;
                $from->setDate((int) $now->format('Y'), (int) $quarterStart, 1)->setTime(0, 0, 0);
                $to->setDate((int) $now->format('Y'), (int) ($quarterStart + 2), 1)
                   ->modify('last day of this month')->setTime(23, 59, 59);

                break;

            case 'year':
                $from->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0, 0);
                $to->setDate((int) $now->format('Y'), 12, 31)->setTime(23, 59, 59);

                break;

            default:
                return null;
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @return Task[] Returns an array of active Task objects for a project (excludes obsolete)
     */
    public function findActiveByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->andWhere('t.status != :obsolete')
            ->setParameter('project', $project)
            ->setParameter('obsolete', TaskStatusEnum::OBSOLETE->value)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
