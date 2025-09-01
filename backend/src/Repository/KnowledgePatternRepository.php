<?php

namespace App\Repository;

use App\Entity\KnowledgePattern;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgePattern>
 */
class KnowledgePatternRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgePattern::class);
    }

    /**
     * @return KnowledgePattern[]
     */
    public function findByPatternType(string $patternType, int $limit = 50): array
    {
        return $this->createQueryBuilder('kp')
            ->andWhere('kp.patternType = :patternType')
            ->setParameter('patternType', $patternType)
            ->orderBy('kp.confidenceScore', 'DESC')
            ->addOrderBy('kp.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return KnowledgePattern[]
     */
    public function findHighConfidencePatterns(float $minConfidence = 0.8): array
    {
        return $this->createQueryBuilder('kp')
            ->andWhere('kp.confidenceScore >= :minConfidence')
            ->setParameter('minConfidence', $minConfidence)
            ->orderBy('kp.confidenceScore', 'DESC')
            ->addOrderBy('kp.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySignature(array $contextSignature, string $agentType): ?KnowledgePattern
    {
        // For complex JSON matching, we might need to use native SQL
        // This is a simplified version - in production, you'd want more sophisticated matching
        $patterns = $this->createQueryBuilder('kp')
            ->andWhere('kp.patternType = :patternType')
            ->setParameter('patternType', $agentType)
            ->getQuery()
            ->getResult();

        foreach ($patterns as $pattern) {
            if ($this->arraysHaveSignificantOverlap($pattern->getContextSignature(), $contextSignature)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * @return KnowledgePattern[]
     */
    public function findSimilarPatterns(
        array $contextSignature,
        string $agentType,
        float $minConfidence = 0.7,
        int $limit = 10
    ): array {
        $patterns = $this->createQueryBuilder('kp')
            ->andWhere('kp.patternType = :patternType')
            ->andWhere('kp.confidenceScore >= :minConfidence')
            ->setParameter('patternType', $agentType)
            ->setParameter('minConfidence', $minConfidence)
            ->orderBy('kp.confidenceScore', 'DESC')
            ->addOrderBy('kp.usageCount', 'DESC')
            ->setMaxResults($limit * 3) // Get more to filter
            ->getQuery()
            ->getResult();

        $patternsWithScores = [];
        foreach ($patterns as $pattern) {
            $similarity = $this->calculateSimilarity($pattern->getContextSignature(), $contextSignature);
            if ($similarity >= 0.6) { // 60% similarity threshold
                $patternsWithScores[] = [
                    'pattern' => $pattern,
                    'score' => $similarity,
                ];
            }
        }

        // Sort by score descending
        usort($patternsWithScores, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Return top $limit patterns
        return array_map(
            fn ($item) => $item['pattern'],
            array_slice($patternsWithScores, 0, $limit)
        );
    }

    /**
     * @return KnowledgePattern[]
     */
    public function findRecentlyUsed(int $days = 30, int $limit = 20): array
    {
        $dateFrom = new \DateTime();
        $dateFrom->modify("-{$days} days");

        return $this->createQueryBuilder('kp')
            ->andWhere('kp.lastSuccessfulUse >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->orderBy('kp.lastSuccessfulUse', 'DESC')
            ->addOrderBy('kp.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return KnowledgePattern[]
     */
    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('kp');

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(kp.tags, :tag{$index}) = 1")
               ->setParameter("tag{$index}", json_encode($tag));
        }

        return $qb->orderBy('kp.confidenceScore', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTopPerformingPatterns(int $limit = 10): array
    {
        return $this->createQueryBuilder('kp')
            ->select('kp, (kp.confidenceScore * kp.usageCount) as performanceScore')
            ->orderBy('performanceScore', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getPatternAnalytics(string $timeRange = '30d'): array
    {
        $dateFrom = new \DateTime();
        match ($timeRange) {
            '7d' => $dateFrom->modify('-7 days'),
            '30d' => $dateFrom->modify('-30 days'),
            '90d' => $dateFrom->modify('-90 days'),
            default => $dateFrom->modify('-30 days'),
        };

        return $this->createQueryBuilder('kp')
            ->select('
                kp.patternType,
                COUNT(kp.id) as totalPatterns,
                AVG(kp.confidenceScore) as avgConfidence,
                SUM(kp.usageCount) as totalUsage,
                MAX(kp.lastSuccessfulUse) as lastUsed
            ')
            ->andWhere('kp.lastSuccessfulUse >= :dateFrom OR kp.lastSuccessfulUse IS NULL')
            ->setParameter('dateFrom', $dateFrom)
            ->groupBy('kp.patternType')
            ->orderBy('totalUsage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function arraysHaveSignificantOverlap(array $arr1, array $arr2, float $threshold = 0.7): bool
    {
        $keys1 = array_keys($arr1);
        $keys2 = array_keys($arr2);

        $intersection = array_intersect($keys1, $keys2);
        $union = array_unique(array_merge($keys1, $keys2));

        if (empty($union)) {
            return false;
        }

        $jaccardSimilarity = count($intersection) / count($union);

        return $jaccardSimilarity >= $threshold;
    }

    private function calculateSimilarity(array $signature1, array $signature2): float
    {
        $keys1 = array_keys($signature1);
        $keys2 = array_keys($signature2);

        $intersection = array_intersect($keys1, $keys2);
        $union = array_unique(array_merge($keys1, $keys2));

        if (empty($union)) {
            return 0.0;
        }

        $jaccardSimilarity = count($intersection) / count($union);

        // Add value-based similarity for matching keys
        $valueSimilarity = 0.0;
        $matchingKeys = 0;

        foreach ($intersection as $key) {
            if (isset($signature1[$key]) && isset($signature2[$key])) {
                if ($signature1[$key] === $signature2[$key]) {
                    $valueSimilarity += 1.0;
                } elseif (is_string($signature1[$key]) && is_string($signature2[$key])) {
                    similar_text($signature1[$key], $signature2[$key], $percent);
                    $valueSimilarity += $percent / 100;
                }
                ++$matchingKeys;
            }
        }

        if ($matchingKeys > 0) {
            $valueSimilarity = $valueSimilarity / $matchingKeys;

            return ($jaccardSimilarity * 0.7) + ($valueSimilarity * 0.3);
        }

        return $jaccardSimilarity;
    }
}
