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

        // Get task statistics
        $taskStats = [
            'total' => $this->taskRepository->countByUser($user),
            'todo' => $this->taskRepository->countByUser($user, TaskStatusEnum::TODO->value) +
                      $this->taskRepository->countByUser($user, TaskStatusEnum::BACKLOG->value),
            'in_progress' => $this->taskRepository->countByUser($user, TaskStatusEnum::IN_PROGRESS->value) +
                            $this->taskRepository->countByUser($user, TaskStatusEnum::REVIEW->value),
            'completed' => $this->taskRepository->countByUser($user, TaskStatusEnum::COMPLETED->value),
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
