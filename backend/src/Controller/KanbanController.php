<?php

namespace App\Controller;

use App\Entity\Task;
use App\Enum\TaskStatusEnum;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KanbanController extends AbstractController
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/kanban', name: 'app_kanban')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $projectId = $request->query->get('project');
        $projectIdString = is_string($projectId) ? $projectId : null;
        $projects = $this->projectRepository->findAll();

        // Get tasks grouped by status
        $tasksByStatus = $this->getTasksByStatus($projectIdString);

        return $this->render('kanban/index.html.twig', [
            'projects' => $projects,
            'selectedProject' => $projectId,
            'tasksByStatus' => $tasksByStatus,
            'statuses' => $this->getStatusConfig(),
        ]);
    }

    #[Route('/api/kanban/move-task', name: 'api_kanban_move_task', methods: ['POST'])]
    public function moveTask(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);
        $taskId = $data['taskId'] ?? null;
        $newStatus = $data['status'] ?? null;

        if (! $taskId || ! $newStatus) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        $task = $this->taskRepository->find($taskId);
        if (! $task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        $statusEnum = TaskStatusEnum::from($newStatus);
        $task->setStatus($statusEnum);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/kanban/tasks', name: 'api_kanban_tasks', methods: ['GET'])]
    public function getTasks(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $projectId = $request->query->get('project');
        $projectIdString = is_string($projectId) ? $projectId : null;
        $tasksByStatus = $this->getTasksByStatus($projectIdString);

        return new JsonResponse($tasksByStatus);
    }

    private function getTasksByStatus(?string $projectId): array
    {
        $queryBuilder = $this->taskRepository->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->where('t.status != :obsolete')
            ->setParameter('obsolete', TaskStatusEnum::OBSOLETE->value);

        if ($projectId) {
            $queryBuilder->andWhere('p.id = :projectId')
                ->setParameter('projectId', $projectId);
        }

        // Order by priority to ensure highest priority tasks are selected first
        // when limiting to 6 per project
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
        ->addOrderBy('t.createdAt', 'DESC'); // Secondary sort by creation date

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
