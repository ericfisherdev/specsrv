<?php

namespace App\Service;

use App\Entity\AgentInteraction;
use App\Entity\KnowledgePattern;
use App\Entity\PatternVariation;
use App\Entity\Task;
use App\Repository\AgentInteractionRepository;
use App\Repository\KnowledgePatternRepository;
use App\Repository\PatternVariationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class LearningEngineService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AgentInteractionRepository $interactionRepo,
        private KnowledgePatternRepository $patternRepo,
        private PatternVariationRepository $variationRepo,
        private PatternAnalyzerService $patternAnalyzer,
        private ContextExtractorService $contextExtractor,
        private LoggerInterface $logger
    ) {
    }

    public function recordInteraction(
        Task $task,
        string $agentType,
        array $inputContext,
        array $executionSteps,
        array $outputResult,
        float $successScore,
        int $executionTimeMs,
        ?array $errorLog = null
    ): AgentInteraction {
        try {
            $interaction = new AgentInteraction();
            $interaction->setTask($task)
                ->setAgentType($agentType)
                ->setInputContext($inputContext)
                ->setExecutionSteps($executionSteps)
                ->setOutputResult($outputResult)
                ->setSuccessScore($successScore)
                ->setExecutionTimeMs($executionTimeMs)
                ->setErrorLog($errorLog)
                ->setPatternHash($this->generatePatternHash($inputContext, $executionSteps))
                ->setCreatedAt(new \DateTime());

            $this->entityManager->persist($interaction);
            $this->entityManager->flush();

            if ($successScore >= 0.8) {
                $this->extractPatternsFromInteraction($interaction);
            }

            $this->logger->info('Agent interaction recorded', [
                'task_id' => $task->getId(),
                'agent_type' => $agentType,
                'success_score' => $successScore,
                'execution_time_ms' => $executionTimeMs,
            ]);

            return $interaction;
        } catch (\Exception $e) {
            $this->logger->error('Failed to record agent interaction', [
                'error' => $e->getMessage(),
                'task_id' => $task->getId(),
                'agent_type' => $agentType,
            ]);

            throw $e;
        }
    }

    public function findSimilarSuccessfulPatterns(
        array $context,
        string $agentType,
        float $minConfidence = 0.7
    ): array {
        $contextSignature = $this->contextExtractor->extractSignature($context);

        return $this->patternRepo->findSimilarPatterns(
            $contextSignature,
            $agentType,
            $minConfidence
        );
    }

    public function recommendSolution(array $taskContext, string $agentType, float $minConfidence = 0.7): ?array
    {
        try {
            $patterns = $this->findSimilarSuccessfulPatterns($taskContext, $agentType, $minConfidence);

            if (empty($patterns)) {
                $this->logger->info('No similar patterns found for recommendation', [
                    'agent_type' => $agentType,
                    'context_keys' => array_keys($taskContext),
                ]);

                return null;
            }

            $rankedPattern = $this->rankPatterns($patterns, $taskContext);

            $variation = $this->variationRepo->findBestVariationForPattern($rankedPattern);

            $recommendation = [
                'pattern' => $this->serializePattern($rankedPattern),
                'confidence' => $rankedPattern->getConfidenceScore(),
                'adapted_solution' => $this->adaptSolutionToContext(
                    $rankedPattern->getSolutionTemplate(),
                    $taskContext
                ),
                'usage_history' => $this->getPatternUsageHistory($rankedPattern),
                'estimated_success_rate' => $this->calculateEstimatedSuccessRate($rankedPattern, $taskContext),
            ];

            if ($variation) {
                $recommendation['variation'] = $this->serializeVariation($variation);
                $recommendation['adapted_solution'] = $this->adaptSolutionWithVariation(
                    $rankedPattern->getSolutionTemplate(),
                    $variation->getAdaptations(),
                    $taskContext
                );
            }

            $rankedPattern->incrementUsageCount();
            $this->entityManager->flush();

            $this->logger->info('Solution recommendation provided', [
                'pattern_id' => $rankedPattern->getId(),
                'confidence' => $rankedPattern->getConfidenceScore(),
                'agent_type' => $agentType,
            ]);

            return $recommendation;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate solution recommendation', [
                'error' => $e->getMessage(),
                'agent_type' => $agentType,
            ]);

            return null;
        }
    }

    public function getPatterns(array $criteria = []): array
    {
        // Support both 'pattern_type' and 'agent_type' aliases
        $patternType = $criteria['pattern_type'] ?? $criteria['agent_type'] ?? null;

        if (null !== $patternType) {
            $patterns = $this->patternRepo->findByPatternType($patternType);
        } elseif (isset($criteria['min_confidence'])) {
            $patterns = $this->patternRepo->findHighConfidencePatterns($criteria['min_confidence']);
        } else {
            $patterns = $this->patternRepo->findAll();
        }

        return array_map([$this, 'serializePattern'], $patterns);
    }

    public function getPerformanceAnalytics(string $timeRange = '30d'): array
    {
        try {
            $interactionMetrics = $this->interactionRepo->getPerformanceMetrics($timeRange);
            $patternAnalytics = $this->patternRepo->getPatternAnalytics($timeRange);
            $variationStats = $this->variationRepo->getVariationPerformanceStats();

            return [
                'interaction_metrics' => $interactionMetrics,
                'pattern_analytics' => $patternAnalytics,
                'variation_stats' => $variationStats,
                'learning_effectiveness' => $this->calculateLearningEffectiveness($timeRange),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate performance analytics', [
                'error' => $e->getMessage(),
                'time_range' => $timeRange,
            ]);

            return [];
        }
    }

    private function extractPatternsFromInteraction(AgentInteraction $interaction): void
    {
        $contextSignature = $this->contextExtractor->extractSignature(
            $interaction->getInputContext()
        );

        $agentType = $interaction->getAgentType();
        if (null === $agentType) {
            return;
        }

        $existingPattern = $this->patternRepo->findBySignature(
            $contextSignature,
            $agentType
        );

        if ($existingPattern) {
            $this->updateExistingPattern($existingPattern, $interaction);
            $interaction->setPattern($existingPattern);
        } else {
            $newPattern = $this->createNewPattern($interaction, $contextSignature);
            $interaction->setPattern($newPattern);
        }

        $this->entityManager->flush();
    }

    private function createNewPattern(
        AgentInteraction $interaction,
        array $contextSignature
    ): KnowledgePattern {
        $agentType = $interaction->getAgentType();
        if (null === $agentType) {
            throw new \InvalidArgumentException('Agent type cannot be null when creating a pattern');
        }

        $pattern = new KnowledgePattern();
        $pattern->setPatternName($this->generatePatternName($contextSignature))
            ->setPatternType($agentType)
            ->setContextSignature($contextSignature)
            ->setSolutionTemplate($this->patternAnalyzer->extractSolutionTemplate($interaction))
            ->setDescription($this->generatePatternDescription($interaction))
            ->setConfidenceScore($interaction->getSuccessScore() ?? 0.0)
            ->setUsageCount(1)
            ->setLastSuccessfulUse(new \DateTime())
            ->setPrerequisites($this->extractPrerequisites($interaction))
            ->setTags(array_unique(array_merge(
                $this->extractTags($interaction),
                ['category:'.$this->patternAnalyzer->identifyPatternType($interaction->getExecutionSteps())]
            )));

        $this->entityManager->persist($pattern);
        $this->entityManager->flush();

        $this->logger->info('New knowledge pattern created', [
            'pattern_id' => $pattern->getId(),
            'pattern_name' => $pattern->getPatternName(),
            'pattern_type' => $pattern->getPatternType(),
        ]);

        return $pattern;
    }

    private function updateExistingPattern(KnowledgePattern $pattern, AgentInteraction $interaction): void
    {
        $currentScore = $pattern->getConfidenceScore() ?? 0.0;
        $newScore = $interaction->getSuccessScore() ?? 0.0;
        $usageCount = $pattern->getUsageCount();

        $updatedScore = (($currentScore * $usageCount) + $newScore) / ($usageCount + 1);

        $pattern->setConfidenceScore($updatedScore)
            ->incrementUsageCount()
            ->setLastSuccessfulUse(new \DateTime());

        if ($this->shouldCreateVariation($pattern, $interaction)) {
            $this->createPatternVariation($pattern, $interaction);
        }

        $this->entityManager->flush();
    }

    private function shouldCreateVariation(KnowledgePattern $pattern, AgentInteraction $interaction): bool
    {
        $contextSimilarity = $this->patternAnalyzer->analyzeSimilarity(
            $pattern->getContextSignature(),
            $this->contextExtractor->extractSignature($interaction->getInputContext())
        );

        return $contextSimilarity >= 0.6 && $contextSimilarity < 0.9;
    }

    private function createPatternVariation(KnowledgePattern $basePattern, AgentInteraction $interaction): PatternVariation
    {
        $contextSignature = $this->contextExtractor->extractSignature($interaction->getInputContext());
        $contextDifferences = $this->calculateContextDifferences(
            $basePattern->getContextSignature(),
            $contextSignature
        );

        $variation = new PatternVariation();
        $variation->setBasePattern($basePattern)
            ->setContextDifferences($contextDifferences)
            ->setAdaptations($this->extractAdaptations($interaction))
            ->setSuccessRate($interaction->getSuccessScore() ?? 0.0)
            ->setUsageCount(1);

        $this->entityManager->persist($variation);

        return $variation;
    }

    private function rankPatterns(array $patterns, array $taskContext): KnowledgePattern
    {
        if (empty($patterns)) {
            throw new \InvalidArgumentException('Cannot rank empty pattern array');
        }

        if (1 === count($patterns)) {
            return $patterns[0];
        }

        $contextSignature = $this->contextExtractor->extractSignature($taskContext);

        usort($patterns, function (KnowledgePattern $a, KnowledgePattern $b) use ($contextSignature) {
            $scoreA = $this->calculatePatternScore($a, $contextSignature);
            $scoreB = $this->calculatePatternScore($b, $contextSignature);

            return $scoreB <=> $scoreA;
        });

        return $patterns[0];
    }

    private function calculatePatternScore(KnowledgePattern $pattern, array $contextSignature): float
    {
        $confidence = $pattern->getConfidenceScore();
        $usageBonus = min(0.1, $pattern->getUsageCount() * 0.01);
        $recencyBonus = $this->calculateRecencyBonus($pattern->getLastSuccessfulUse());
        $similarityScore = $this->patternAnalyzer->analyzeSimilarity(
            $pattern->getContextSignature(),
            $contextSignature
        );

        return ($confidence * 0.4) + ($similarityScore * 0.4) + $usageBonus + $recencyBonus;
    }

    private function calculateRecencyBonus(?\DateTimeInterface $lastUse): float
    {
        if (! $lastUse) {
            return 0.0;
        }

        $daysSinceUse = (new \DateTime())->diff($lastUse)->days;

        if ($daysSinceUse <= 7) {
            return 0.05;
        }
        if ($daysSinceUse <= 30) {
            return 0.03;
        }
        if ($daysSinceUse <= 90) {
            return 0.01;
        }

        return 0.0;
    }

    private function adaptSolutionToContext(array $solutionTemplate, array $taskContext): array
    {
        $adaptedSolution = $solutionTemplate;

        if (isset($taskContext['technologies'])) {
            $adaptedSolution['tools_used'] = array_merge(
                $adaptedSolution['tools_used'] ?? [],
                is_array($taskContext['technologies']) ? $taskContext['technologies'] : [$taskContext['technologies']]
            );
        }

        if (isset($taskContext['constraints'])) {
            $adaptedSolution['constraints'] = $taskContext['constraints'];
        }

        if (isset($taskContext['time_constraints'])) {
            $adaptedSolution['time_estimate'] = $this->adjustTimeEstimate(
                $adaptedSolution['time_estimate'] ?? 'moderate',
                $taskContext['time_constraints']
            );
        }

        return $adaptedSolution;
    }

    private function adaptSolutionWithVariation(array $solutionTemplate, array $adaptations, array $taskContext): array
    {
        $adaptedSolution = $this->adaptSolutionToContext($solutionTemplate, $taskContext);

        foreach ($adaptations as $key => $adaptation) {
            if (isset($adaptedSolution[$key])) {
                if (is_array($adaptedSolution[$key]) && is_array($adaptation)) {
                    $adaptedSolution[$key] = array_merge($adaptedSolution[$key], $adaptation);
                } else {
                    $adaptedSolution[$key] = $adaptation;
                }
            } else {
                $adaptedSolution[$key] = $adaptation;
            }
        }

        return $adaptedSolution;
    }

    private function getPatternUsageHistory(KnowledgePattern $pattern): array
    {
        $patternId = $pattern->getId();
        if (null === $patternId) {
            return [];
        }

        $interactions = $this->interactionRepo->findByPatternId($patternId);

        return array_slice(array_map(function (AgentInteraction $interaction) {
            $createdAt = $interaction->getCreatedAt();
            $task = $interaction->getTask();

            return [
                'date' => $createdAt ? $createdAt->format('Y-m-d') : 'unknown',
                'success_score' => $interaction->getSuccessScore() ?? 0.0,
                'execution_time_ms' => $interaction->getExecutionTimeMs() ?? 0,
                'task_id' => $task ? $task->getId() : 0,
            ];
        }, $interactions), 0, 10);
    }

    private function calculateEstimatedSuccessRate(KnowledgePattern $pattern, array $taskContext): float
    {
        $baseRate = $pattern->getConfidenceScore();
        $contextSignature = $this->contextExtractor->extractSignature($taskContext);

        $similarity = $this->patternAnalyzer->analyzeSimilarity(
            $pattern->getContextSignature(),
            $contextSignature
        );

        return min(1.0, $baseRate * $similarity);
    }

    private function calculateLearningEffectiveness(string $timeRange): array
    {
        $dateFrom = new \DateTime();
        match ($timeRange) {
            '7d' => $dateFrom->modify('-7 days'),
            '30d' => $dateFrom->modify('-30 days'),
            '90d' => $dateFrom->modify('-90 days'),
            default => $dateFrom->modify('-30 days'),
        };

        $totalInteractions = (int) $this->interactionRepo->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->getQuery()
            ->getSingleScalarResult();

        $patternsCreated = (int) $this->patternRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->getQuery()
            ->getSingleScalarResult();
        $reusesResult = $this->patternRepo->createQueryBuilder('p')
            ->select('SUM(p.usageCount)')
            ->where('p.lastSuccessfulUse >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->getQuery()
            ->getSingleScalarResult();

        $patternReuses = (int) ($reusesResult ?? 0);

        return [
            'total_interactions' => $totalInteractions,
            'patterns_learned' => $patternsCreated,
            'pattern_reuses' => $patternReuses,
            'learning_rate' => $totalInteractions > 0 ? $patternsCreated / $totalInteractions : 0,
            'reuse_rate' => $totalInteractions > 0 ? $patternReuses / $totalInteractions : 0,
        ];
    }

    private function generatePatternHash(array $inputContext, array $executionSteps): string
    {
        $hashData = json_encode(['context' => $inputContext, 'steps' => $executionSteps]);
        if (false === $hashData) {
            $hashData = serialize(['context' => $inputContext, 'steps' => $executionSteps]);
        }

        return hash('sha256', $hashData);
    }

    private function generatePatternName(array $contextSignature): string
    {
        $components = [];

        if (isset($contextSignature['task_type'])) {
            $components[] = ucfirst($contextSignature['task_type']);
        }

        if (isset($contextSignature['technologies']) && is_array($contextSignature['technologies'])) {
            $components[] = implode('-', array_slice($contextSignature['technologies'], 0, 2));
        }

        if (isset($contextSignature['complexity'])) {
            $components[] = ucfirst($contextSignature['complexity']);
        }

        $baseName = implode('-', $components) ?: 'General';

        return $baseName.'-Pattern-'.substr(uniqid(), -4);
    }

    private function generatePatternDescription(AgentInteraction $interaction): string
    {
        $context = $interaction->getInputContext();
        $steps = $interaction->getExecutionSteps();

        $description = 'Automated pattern for ';

        if (isset($context['task_type'])) {
            $description .= $context['task_type'].' tasks';
        } else {
            $description .= 'general tasks';
        }

        if (! empty($steps)) {
            $stepTypes = array_unique(array_map(fn ($s) => $s['type'] ?? 'unknown', $steps));
            $description .= ' involving '.implode(', ', $stepTypes);
        }

        $description .= '. Success rate: '.number_format($interaction->getSuccessScore() * 100, 1).'%';

        return $description;
    }

    private function extractPrerequisites(AgentInteraction $interaction): array
    {
        $prerequisites = [];
        $context = $interaction->getInputContext();

        if (isset($context['technologies'])) {
            $prerequisites['required_technologies'] = is_array($context['technologies'])
                ? $context['technologies']
                : [$context['technologies']];
        }

        if (isset($context['permissions'])) {
            $prerequisites['required_permissions'] = $context['permissions'];
        }

        if (isset($context['dependencies'])) {
            $prerequisites['dependencies'] = $context['dependencies'];
        }

        return $prerequisites;
    }

    private function extractTags(AgentInteraction $interaction): array
    {
        $tags = [];
        $context = $interaction->getInputContext();

        if (isset($context['domain'])) {
            $tags[] = $context['domain'];
        }

        if (isset($context['complexity'])) {
            $tags[] = 'complexity:'.$context['complexity'];
        }

        if (isset($context['urgency'])) {
            $tags[] = 'urgency:'.$context['urgency'];
        }

        $tags[] = 'agent:'.$interaction->getAgentType();
        $tags[] = 'success:'.($interaction->getSuccessScore() >= 0.8 ? 'high' : 'medium');

        return array_unique($tags);
    }

    private function calculateContextDifferences(array $baseContext, array $newContext): array
    {
        $differences = [];

        foreach ($newContext as $key => $value) {
            if (! isset($baseContext[$key]) || $baseContext[$key] !== $value) {
                $differences[$key] = [
                    'base_value' => $baseContext[$key] ?? null,
                    'new_value' => $value,
                ];
            }
        }

        return $differences;
    }

    private function extractAdaptations(AgentInteraction $interaction): array
    {
        $adaptations = [];
        $steps = $interaction->getExecutionSteps();

        foreach ($steps as $step) {
            if (isset($step['adaptations'])) {
                $adaptations = array_merge($adaptations, $step['adaptations']);
            }
        }

        return $adaptations;
    }

    private function adjustTimeEstimate(string $baseEstimate, mixed $timeConstraint): string
    {
        if (is_numeric($timeConstraint)) {
            $hours = (int) $timeConstraint;
            if ($hours <= 4) {
                return 'urgent';
            }
            if ($hours <= 24) {
                return 'fast';
            }

            return 'moderate';
        }

        return $baseEstimate;
    }

    private function serializePattern(KnowledgePattern $pattern): array
    {
        return [
            'id' => $pattern->getId(),
            'name' => $pattern->getPatternName(),
            'type' => $pattern->getPatternType(),
            'description' => $pattern->getDescription(),
            'confidence_score' => $pattern->getConfidenceScore(),
            'usage_count' => $pattern->getUsageCount(),
            'last_successful_use' => $pattern->getLastSuccessfulUse()?->format('c'),
            'tags' => $pattern->getTags(),
            'context_signature' => $pattern->getContextSignature(),
            'solution_template' => $pattern->getSolutionTemplate(),
        ];
    }

    private function serializeVariation(PatternVariation $variation): array
    {
        return [
            'id' => $variation->getId(),
            'success_rate' => $variation->getSuccessRate(),
            'usage_count' => $variation->getUsageCount(),
            'context_differences' => $variation->getContextDifferences(),
            'adaptations' => $variation->getAdaptations(),
        ];
    }
}
