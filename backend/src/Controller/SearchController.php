<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository
    ) {
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        $query = trim((string) $request->query->get('q', ''));
        $projectFilter = $request->query->get('project');
        $statusFilter = $request->query->get('status');
        $priorityFilter = $request->query->get('priority');
        $dateFilter = $request->query->get('date_range');

        // Build search criteria
        $searchCriteria = [
            'query' => $query,
            'project_id' => $projectFilter,
            'status' => $statusFilter,
            'priority' => $priorityFilter,
            'date_range' => $dateFilter,
        ];

        // Get results
        $tasks = [];
        $projects = [];

        if ($query || $projectFilter || $statusFilter || $priorityFilter || $dateFilter) {
            $tasks = $this->taskRepository->searchWithFilters($user, $searchCriteria);
            if ($query) {
                $projects = $this->projectRepository->searchByTitle($user, $query);
            }
        }

        // Get filter options for the form
        $userProjects = $this->projectRepository->findByUser($user);
        
        return $this->render('search/results.html.twig', [
            'query' => $query,
            'tasks' => $tasks,
            'projects' => $projects,
            'filters' => $searchCriteria,
            'user_projects' => $userProjects,
            'priorities' => ['low', 'medium', 'high', 'critical'],
            'statuses' => ['backlog', 'todo', 'in_progress', 'review', 'completed'],
            'date_ranges' => [
                'today' => 'Today',
                'week' => 'This Week', 
                'month' => 'This Month',
                'quarter' => 'This Quarter',
                'year' => 'This Year'
            ]
        ]);
    }

    #[Route('/search/filters', name: 'app_search_filters', methods: ['POST'])]
    public function applyFilters(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        $data = $request->request->all();
        
        $searchCriteria = [
            'query' => trim((string) ($data['query'] ?? '')),
            'project_id' => $data['project_id'] ?? null,
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'date_range' => $data['date_range'] ?? null,
        ];

        // Get filtered results
        $tasks = $this->taskRepository->searchWithFilters($user, $searchCriteria);
        
        // Transform tasks for JSON response
        $tasksData = array_map(function ($task) {
            $project = $task->getProject();
            $createdAt = $task->getCreatedAt();
            
            return [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'project' => $project ? $project->getTitle() : 'No Project',
                'project_id' => $project ? $project->getId() : null,
                'created_at' => $createdAt ? $createdAt->format('Y-m-d H:i:s') : 'Unknown',
                'url' => $this->generateUrl('app_task_detail', ['id' => $task->getId()])
            ];
        }, $tasks);

        return new JsonResponse([
            'success' => true,
            'tasks' => $tasksData,
            'count' => count($tasksData)
        ]);
    }
}