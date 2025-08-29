<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiKey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findActiveByKeyHash(string $keyHash): ?ApiKey
    {
        return $this->createQueryBuilder('ak')
            ->andWhere('ak.keyHash = :keyHash')
            ->andWhere('ak.isActive = :isActive')
            ->setParameter('keyHash', $keyHash)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('ak')
            ->andWhere('ak.user = :user')
            ->andWhere('ak.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->orderBy('ak.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ApiKey $apiKey, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiKey);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiKey $apiKey, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiKey);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
