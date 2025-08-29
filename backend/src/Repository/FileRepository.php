<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * @return File[] Returns an array of File objects for a specific entity
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.entityType = :entityType')
            ->andWhere('f.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return File[] Returns an array of File objects by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.type = :type')
            ->setParameter('type', $type)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return File[] Returns an array of File objects for a task
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.task = :task')
            ->setParameter('task', $task)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
