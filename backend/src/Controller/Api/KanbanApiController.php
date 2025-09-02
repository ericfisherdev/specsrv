<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/kanban')]
class KanbanApiController extends BaseApiController
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/boards', name: 'api_kanban_boards', methods: ['GET'])]
    public function boards(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $projectId = $request->query->get('project');
        $projectIdString = is_string($projectId) ? $projectId : null;
        
        // Get all projects for the user
        $projects = $this->projectRepository->findByUser($user);

        // Get tasks grouped by status
        $tasksByStatus = $this->getTasksByStatus($user, $projectIdString);

        // Get status configuration
        $statuses = $this->getStatusConfig();

        return $this->successResponse([
            'projects' => array_map([$this, 'transformEntity'], $projects),
            'selected_project' => $projectIdString,
            'tasks_by_status' => $tasksByStatus,
            'statuses' => $statuses,
        ]);
    }

    #[Route('/move-task', name: 'api_v1_kanban_move_task', methods: ['POST'])]
    public function moveTask(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        $taskId = $data['taskId'] ?? null;
        $newStatus = $data['status'] ?? null;

        if (! $taskId || ! $newStatus) {
            return $this->errorResponse('Task ID and status are required', 'MISSING_FIELDS', null, 400);
        }

        $task = $this->taskRepository->find($taskId);
        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        // Check ownership - verify task belongs to current user
        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        // Validate status with safer enum handling
        $statusEnum = TaskStatusEnum::tryFrom($newStatus);
        if (! $statusEnum) {
            return $this->errorResponse('Invalid status', 'INVALID_STATUS', null, 400);
        }

        $task->setStatus($statusEnum);
        $this->entityManager->flush();

        return $this->successResponse(['success' => true]);
    }

    #[Route('/tasks', name: 'api_v1_kanban_tasks', methods: ['GET'])]
    public function getTasks(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $projectId = $request->query->get('project');
        $projectIdString = is_string($projectId) ? $projectId : null;
        $tasksByStatus = $this->getTasksByStatus($user, $projectIdString);

        return $this->successResponse($tasksByStatus);
    }

    private function getTasksByStatus(User $user, ?string $projectId): array
    {
        $queryBuilder = $this->taskRepository->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.user', 'u')
            ->addSelect('p')
            ->addSelect('u')
            ->where('t.status != :obsolete')
            ->andWhere('u.id = :userId')
            ->setParameter('obsolete', TaskStatusEnum::OBSOLETE->value)
            ->setParameter('userId', $user->getId());

        if ($projectId) {
            $queryBuilder->andWhere('p.id = :projectId')
                ->setParameter('projectId', $projectId);
        }

        // Order by priority to ensure highest priority tasks are selected first
        $queryBuilder->orderBy(
            'CASE 
                WHEN t.priority = :critical THEN 1
                WHEN t.priority = :high THEN 2
                WHEN t.priority = :medium THEN 3
                WHEN t.priority = :low THEN 4
                ELSE 5
            END',
            'ASC'
        )
        ->setParameter('critical', Task::PRIORITY_CRITICAL)
        ->setParameter('high', Task::PRIORITY_HIGH)
        ->setParameter('medium', Task::PRIORITY_MEDIUM)
        ->setParameter('low', Task::PRIORITY_LOW)
        ->addOrderBy('t.createdAt', 'DESC');

        $tasks = $queryBuilder->getQuery()->getResult();

        // Group tasks by status
        $tasksByStatus = [
            TaskStatusEnum::BACKLOG->value => [],
            TaskStatusEnum::TODO->value => [],
            TaskStatusEnum::IN_PROGRESS->value => [],
            TaskStatusEnum::REVIEW->value => [],
            TaskStatusEnum::COMPLETED->value => [],
        ];

        $projectTaskCount = [];

        foreach ($tasks as $task) {
            $status = $task->getStatusValue();
            $taskProjectId = $task->getProject() ? $task->getProject()->getId() : null;

            // If no specific project is selected, limit to 6 highest priority tasks per project
            if (! $projectId && $taskProjectId) {
                if (! isset($projectTaskCount[$taskProjectId])) {
                    $projectTaskCount[$taskProjectId] = 0;
                }

                if ($projectTaskCount[$taskProjectId] >= 6) {
                    continue;
                }

                ++$projectTaskCount[$taskProjectId];
            }

            if (isset($tasksByStatus[$status])) {
                $tasksByStatus[$status][] = [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $status,
                    'priority' => $task->getPriority(),
                    'priority_value' => $this->getPriorityValue($task->getPriority()),
                    'project' => $task->getProject() ? [
                        'id' => $task->getProject()->getId(),
                        'name' => $task->getProject()->getTitle(),
                    ] : null,
                ];
            }
        }

        return $tasksByStatus;
    }

    private function getPriorityValue(string $priority): int
    {
        return match ($priority) {
            Task::PRIORITY_CRITICAL => 1,
            Task::PRIORITY_HIGH => 2,
            Task::PRIORITY_MEDIUM => 3,
            Task::PRIORITY_LOW => 4,
            default => 5,
        };
    }

    private function getStatusConfig(): array
    {
        $config = [];
        foreach (TaskStatusEnum::getActiveStatuses() as $status) {
            $config[$status->value] = [
                'label' => $status->getLabel(),
                'color' => $status->getColor(),
            ];
        }

        return $config;
    }
}