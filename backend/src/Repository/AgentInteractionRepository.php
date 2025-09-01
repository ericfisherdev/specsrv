<?php

namespace App\Repository;

use App\Entity\AgentInteraction;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgentInteraction>
 */
class AgentInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentInteraction::class);
    }

    /**
     * @return AgentInteraction[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('ai')
            ->andWhere('ai.task = :task')
            ->setParameter('task', $task)
            ->orderBy('ai.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AgentInteraction[]
     */
    public function findByAgentType(string $agentType, int $limit = 100): array
    {
        return $this->createQueryBuilder('ai')
            ->andWhere('ai.agentType = :agentType')
            ->setParameter('agentType', $agentType)
            ->orderBy('ai.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AgentInteraction[]
     */
    public function findSuccessfulInteractions(float $minSuccessScore = 0.8, int $limit = 100): array
    {
        return $this->createQueryBuilder('ai')
            ->andWhere('ai.successScore >= :minScore')
            ->setParameter('minScore', $minSuccessScore)
            ->orderBy('ai.successScore', 'DESC')
            ->addOrderBy('ai.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AgentInteraction[]
     */
    public function findByPatternHash(string $patternHash): array
    {
        return $this->createQueryBuilder('ai')
            ->andWhere('ai.patternHash = :patternHash')
            ->setParameter('patternHash', $patternHash)
            ->orderBy('ai.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AgentInteraction[]
     */
    public function findByPatternId(int $patternId): array
    {
        return $this->createQueryBuilder('ai')
            ->andWhere('ai.pattern = :patternId')
            ->setParameter('patternId', $patternId)
            ->orderBy('ai.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AgentInteraction[]
     */
    public function findSimilarInteractions(
        string $agentType,
        array $contextKeys,
        float $minSuccessScore = 0.7,
        int $limit = 10
    ): array {
        // Native SQL for JSON querying (MySQL 8+). Ensure all provided keys exist in the JSON object.
        $conn = $this->getEntityManager()->getConnection();
        $pathParams = [];
        foreach ($contextKeys as $idx => $key) {
            // Quote JSON path keys to be safe: $.\"key\"
            $pathParams[] = ':path'.$idx;
        }
        $pathsExpr = $pathParams ? ', '.implode(', ', $pathParams) : '';
        $sql = '
            SELECT ai.id
            FROM agent_interactions ai
            WHERE ai.agent_type = :agentType
              AND ai.success_score >= :minScore
              AND ai.input_context IS NOT NULL
              AND JSON_VALID(ai.input_context)
              '.($pathsExpr ? "AND JSON_CONTAINS_PATH(ai.input_context, 'all' {$pathsExpr})" : '').'
            ORDER BY ai.success_score DESC, ai.created_at DESC
            LIMIT :limit
        ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('agentType', $agentType);
        $stmt->bindValue('minScore', $minSuccessScore);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        foreach ($contextKeys as $idx => $key) {
            $stmt->bindValue('path'.$idx, '$."'.str_replace('"', '\"', $key).'"');
        }
        $result = $stmt->executeQuery()->fetchAllAssociative();

        // Avoid N+1: bulk-load by IDs and preserve order.
        $ids = array_column($result, 'id');
        if (! $ids) {
            return [];
        }
        $list = $this->createQueryBuilder('ai')
            ->andWhere('ai.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
        $byId = [];
        foreach ($list as $i) {
            $byId[$i->getId()] = $i;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function getPerformanceMetrics(string $timeRange = '30d'): array
    {
        $dateFrom = new \DateTime();
        match ($timeRange) {
            '7d' => $dateFrom->modify('-7 days'),
            '30d' => $dateFrom->modify('-30 days'),
            '90d' => $dateFrom->modify('-90 days'),
            default => $dateFrom->modify('-30 days'),
        };

        $qb = $this->createQueryBuilder('ai')
            ->select('
                ai.agentType,
                COUNT(ai.id) as totalInteractions,
                AVG(ai.successScore) as avgSuccessScore,
                AVG(ai.executionTimeMs) as avgExecutionTime,
                COUNT(CASE WHEN ai.successScore >= 0.8 THEN 1 END) as successfulInteractions
            ')
            ->andWhere('ai.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->groupBy('ai.agentType')
            ->orderBy('avgSuccessScore', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getInteractionTrends(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('ai')
            ->select('
                DATE(ai.createdAt) as date,
                COUNT(ai.id) as totalInteractions,
                AVG(ai.successScore) as avgSuccessScore
            ')
            ->andWhere('ai.createdAt >= :from')
            ->andWhere('ai.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
