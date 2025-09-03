<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/dashboard')]
class DashboardApiController extends BaseApiController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository
    ) {
    }

    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        // Get project count
        $projectCount = $this->projectRepository->countByUser($user);

        // Get task statistics efficiently with single query
        $statusCounts = $this->taskRepository->getStatusCountsByUser($user);
        
        $taskStats = [
            'total' => array_sum($statusCounts),
            'todo' => ($statusCounts[TaskStatusEnum::TODO->value] ?? 0) + 
                     ($statusCounts[TaskStatusEnum::BACKLOG->value] ?? 0),
            'in_progress' => ($statusCounts[TaskStatusEnum::IN_PROGRESS->value] ?? 0) + 
                           ($statusCounts[TaskStatusEnum::REVIEW->value] ?? 0),
            'completed' => $statusCounts[TaskStatusEnum::COMPLETED->value] ?? 0,
        ];

        // Get recent tasks (last 5)
        $recentTasks = $this->taskRepository->findPaginatedByUser($user, 5, 0);
        $recentTasksData = array_map([$this, 'transformEntity'], $recentTasks);

        // Get recent projects (last 5)
        $recentProjects = $this->projectRepository->findPaginatedByUser($user, 5, 0);
        $recentProjectsData = array_map([$this, 'transformEntity'], $recentProjects);

        return $this->successResponse([
            'user' => $this->transformEntity($user),
            'projects' => [
                'total' => $projectCount,
                'recent' => $recentProjectsData,
            ],
            'tasks' => [
                'stats' => $taskStats,
                'recent' => $recentTasksData,
            ],
        ]);
    }
}
