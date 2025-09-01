<?php

namespace App\Controller\Api;

use App\Service\LearningEngineService;
use App\Service\TaskService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/learning')]
class LearningApiController extends BaseApiController
{
    public function __construct(
        private LearningEngineService $learningService,
        private TaskService $taskService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/record-interaction', methods: ['POST'])]
    public function recordInteraction(Request $request): JsonResponse
    {
        $this->requireAuth();

        try {
            $data = $this->getJsonPayload($request);

            $requiredFields = ['task_id', 'agent_type', 'input_context', 'execution_steps', 'output_result', 'success_score', 'execution_time_ms'];
            foreach ($requiredFields as $field) {
                if (! isset($data[$field])) {
                    return $this->errorResponse(
                        "Missing required field: {$field}",
                        'MISSING_FIELD',
                        null,
                        400
                    );
                }
            }

            $task = $this->taskService->getTaskById($data['task_id']);
            if (! $task) {
                return $this->errorResponse(
                    'Task not found',
                    'TASK_NOT_FOUND',
                    null,
                    404
                );
            }

            if (! $this->checkResourceOwnership($task)) {
                return $this->errorResponse(
                    'Access denied',
                    'ACCESS_DENIED',
                    null,
                    403
                );
            }

            if (! is_numeric($data['success_score'])) {
                return $this->errorResponse(
                    'Success score must be a float between 0 and 1',
                    'INVALID_SUCCESS_SCORE',
                    null,
                    400
                );
            }

            $score = (float) $data['success_score'];
            if ($score < 0 || $score > 1) {
                return $this->errorResponse(
                    'Success score must be a float between 0 and 1',
                    'INVALID_SUCCESS_SCORE',
                    null,
                    400
                );
            }

            if (! is_int($data['execution_time_ms']) || $data['execution_time_ms'] < 0) {
                return $this->errorResponse(
                    'Execution time must be a non-negative integer',
                    'INVALID_EXECUTION_TIME',
                    null,
                    400
                );
            }

            $interaction = $this->learningService->recordInteraction(
                $task,
                $data['agent_type'],
                $data['input_context'],
                $data['execution_steps'],
                $data['output_result'],
                $score,
                $data['execution_time_ms'],
                $data['error_log'] ?? null
            );

            return $this->successResponse([
                'interaction_id' => $interaction->getId(),
                'pattern_extracted' => $interaction->getSuccessScore() >= 0.8,
                'pattern_hash' => $interaction->getPatternHash(),
            ], 'Interaction recorded successfully');

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'VALIDATION_ERROR',
                null,
                400
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to record interaction', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/recommend-solution', methods: ['POST'])]
    public function recommendSolution(Request $request): JsonResponse
    {
        $this->requireAuth();

        try {
            $data = $this->getJsonPayload($request);

            if (! isset($data['task_context']) || ! isset($data['agent_type'])) {
                return $this->errorResponse(
                    'Missing required fields: task_context, agent_type',
                    'MISSING_FIELDS',
                    null,
                    400
                );
            }

            if (! is_array($data['task_context'])) {
                return $this->errorResponse(
                    'task_context must be an array',
                    'INVALID_TASK_CONTEXT',
                    null,
                    400
                );
            }

            $minConfidence = isset($data['min_confidence']) ? (float) $data['min_confidence'] : 0.7;
            if ($minConfidence < 0 || $minConfidence > 1) {
                return $this->errorResponse(
                    'min_confidence must be between 0 and 1',
                    'INVALID_CONFIDENCE',
                    null,
                    400
                );
            }

            $recommendation = $this->learningService->recommendSolution(
                $data['task_context'],
                $data['agent_type'],
                $minConfidence
            );

            if (! $recommendation) {
                return $this->errorResponse(
                    'No similar patterns found for the given context',
                    'NO_PATTERNS_FOUND',
                    null,
                    404
                );
            }

            return $this->successResponse(
                $recommendation,
                'Solution recommendation generated successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate recommendation', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/patterns', methods: ['GET'])]
    public function getPatterns(Request $request): JsonResponse
    {
        $this->requireAuth();

        try {
            $agentType = $request->query->get('agent_type');
            $patternType = $request->query->get('pattern_type');
            $rawMinConfidence = $request->query->get('min_confidence');

            if (null === $rawMinConfidence) {
                $minConfidence = 0.7;
            } else {
                if (! is_numeric($rawMinConfidence)) {
                    return $this->errorResponse(
                        'min_confidence must be between 0 and 1',
                        'INVALID_CONFIDENCE',
                        null,
                        400
                    );
                }
                $minConfidence = (float) $rawMinConfidence;
                if ($minConfidence < 0 || $minConfidence > 1) {
                    return $this->errorResponse(
                        'min_confidence must be between 0 and 1',
                        'INVALID_CONFIDENCE',
                        null,
                        400
                    );
                }
            }

            $criteria = array_filter([
                'agent_type' => $agentType,
                'pattern_type' => $patternType,
                'min_confidence' => (float) $minConfidence,
            ]);

            $patterns = $this->learningService->getPatterns($criteria);

            $paginationParams = $this->getPaginationParams($request);
            $totalItems = count($patterns);
            $paginatedPatterns = array_slice(
                $patterns,
                $paginationParams['offset'],
                $paginationParams['per_page']
            );

            return $this->paginatedResponse(
                $paginatedPatterns,
                $paginationParams['page'],
                $paginationParams['per_page'],
                $totalItems,
                'Patterns retrieved successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve patterns', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/analytics/performance', methods: ['GET'])]
    public function getPerformanceAnalytics(Request $request): JsonResponse
    {
        $this->requireAuth();

        try {
            $timeRange = (string) $request->query->get('range', '30d');
            $allowedRanges = ['7d', '30d', '90d'];

            if (! in_array($timeRange, $allowedRanges)) {
                return $this->errorResponse(
                    'Invalid time range. Allowed values: '.implode(', ', $allowedRanges),
                    'INVALID_TIME_RANGE',
                    null,
                    400
                );
            }

            $analytics = $this->learningService->getPerformanceAnalytics($timeRange);

            return $this->successResponse(
                $analytics,
                'Performance analytics retrieved successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate analytics', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getUserIdentifier(),
                'time_range' => $timeRange ?? 'unknown',
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/patterns/{id}/feedback', methods: ['POST'])]
    public function submitPatternFeedback(Request $request, int $id): JsonResponse
    {
        $this->requireAuth();

        try {
            $data = $this->getJsonPayload($request);

            if (! isset($data['feedback_type']) || ! isset($data['success_score'])) {
                return $this->errorResponse(
                    'Missing required fields: feedback_type, success_score',
                    'MISSING_FIELDS',
                    null,
                    400
                );
            }

            $allowedFeedbackTypes = ['success', 'failure', 'partial_success'];
            if (! in_array($data['feedback_type'], $allowedFeedbackTypes)) {
                return $this->errorResponse(
                    'Invalid feedback type. Allowed values: '.implode(', ', $allowedFeedbackTypes),
                    'INVALID_FEEDBACK_TYPE',
                    null,
                    400
                );
            }

            $successScore = (float) $data['success_score'];
            if ($successScore < 0 || $successScore > 1) {
                return $this->errorResponse(
                    'success_score must be between 0 and 1',
                    'INVALID_SUCCESS_SCORE',
                    null,
                    400
                );
            }

            // For now, just log the feedback. In a full implementation, you'd update the pattern
            $this->logger->info('Pattern feedback received', [
                'pattern_id' => $id,
                'feedback_type' => $data['feedback_type'],
                'success_score' => $successScore,
                'user_id' => $this->getUser()?->getUserIdentifier(),
                'comments' => $data['comments'] ?? null,
            ]);

            return $this->successResponse(
                ['feedback_id' => uniqid()],
                'Feedback submitted successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to submit pattern feedback', [
                'error' => $e->getMessage(),
                'pattern_id' => $id,
                'user_id' => $this->getUser()->getUserIdentifier(),
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/interactions/search', methods: ['POST'])]
    public function searchInteractions(Request $request): JsonResponse
    {
        $this->requireAuth();

        try {
            $data = $this->getJsonPayload($request);

            $agentType = $data['agent_type'] ?? null;
            $context = $data['context'] ?? [];
            $minSuccessScore = isset($data['min_success_score']) ? (float) $data['min_success_score'] : 0.7;
            $limit = isset($data['limit']) ? min(100, max(1, (int) $data['limit'])) : 10;

            if ($minSuccessScore < 0 || $minSuccessScore > 1) {
                return $this->errorResponse(
                    'min_success_score must be between 0 and 1',
                    'INVALID_SUCCESS_SCORE',
                    null,
                    400
                );
            }

            $patterns = $this->learningService->findSimilarSuccessfulPatterns(
                $context,
                $agentType,
                $minSuccessScore
            );

            $results = array_slice($patterns, 0, $limit);

            // Transform entities to arrays to avoid circular references
            $transformedResults = array_map(
                fn ($pattern) => $this->transformEntity($pattern),
                $results
            );

            return $this->successResponse([
                'patterns' => $transformedResults,
                'total_found' => count($patterns),
                'returned' => count($results),
            ], 'Search completed successfully');

        } catch (\Exception $e) {
            $this->logger->error('Failed to search interactions', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->errorResponse(
                'Internal server error',
                'INTERNAL_ERROR',
                null,
                500
            );
        }
    }

    #[Route('/health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        try {
            // Basic health check - verify service availability
            $testContext = ['test' => true];
            $this->learningService->findSimilarSuccessfulPatterns($testContext, 'test', 0.5);

            return $this->successResponse([
                'status' => 'healthy',
                'timestamp' => (new \DateTime())->format('c'),
                'services' => [
                    'learning_engine' => 'operational',
                    'pattern_analyzer' => 'operational',
                    'database' => 'operational',
                ],
            ], 'Learning system is healthy');

        } catch (\Exception $e) {
            $this->logger->error('Learning system health check failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Learning system is unhealthy',
                'HEALTH_CHECK_FAILED',
                ['error' => $e->getMessage()],
                503
            );
        }
    }
}
