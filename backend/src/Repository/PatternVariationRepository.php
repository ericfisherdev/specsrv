<?php

namespace App\Repository;

use App\Entity\PatternVariation;
use App\Entity\KnowledgePattern;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PatternVariation>
 */
class PatternVariationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatternVariation::class);
    }

    /**
     * @return PatternVariation[]
     */
    public function findByBasePattern(KnowledgePattern $basePattern): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.basePattern = :basePattern')
            ->setParameter('basePattern', $basePattern)
            ->orderBy('pv.successRate', 'DESC')
            ->addOrderBy('pv.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PatternVariation[]
     */
    public function findHighPerformingVariations(float $minSuccessRate = 0.8): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.successRate >= :minSuccessRate')
            ->andWhere('pv.usageCount >= 2') // At least used twice
            ->setParameter('minSuccessRate', $minSuccessRate)
            ->orderBy('pv.successRate', 'DESC')
            ->addOrderBy('pv.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PatternVariation[]
     */
    public function findRecentVariations(int $days = 30): array
    {
        $dateFrom = new \DateTime();
        $dateFrom->modify("-{$days} days");

        return $this->createQueryBuilder('pv')
            ->andWhere('pv.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->orderBy('pv.successRate', 'DESC')
            ->addOrderBy('pv.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBestVariationForPattern(KnowledgePattern $basePattern): ?PatternVariation
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.basePattern = :basePattern')
            ->andWhere('pv.usageCount >= 2') // Require some usage data
            ->setParameter('basePattern', $basePattern)
            ->orderBy('pv.successRate', 'DESC')
            ->addOrderBy('pv.usageCount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getVariationPerformanceStats(): array
    {
        return $this->createQueryBuilder('pv')
            ->select('
                COUNT(pv.id) as totalVariations,
                AVG(pv.successRate) as avgSuccessRate,
                SUM(pv.usageCount) as totalUsage,
                MAX(pv.successRate) as maxSuccessRate,
                MIN(pv.successRate) as minSuccessRate
            ')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @return PatternVariation[]
     */
    public function findUnderperformingVariations(float $maxSuccessRate = 0.5): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.successRate <= :maxSuccessRate')
            ->andWhere('pv.usageCount >= 3') // Only consider variations with enough data
            ->setParameter('maxSuccessRate', $maxSuccessRate)
            ->orderBy('pv.successRate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}