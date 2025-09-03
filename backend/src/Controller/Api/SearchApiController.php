<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/search')]
class SearchApiController extends BaseApiController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository
    ) {
    }

    #[Route('/suggestions', name: 'api_search_suggestions', methods: ['GET'])]
    public function suggestions(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) < 2) {
            return $this->successResponse([
                'tasks' => [],
                'projects' => [],
            ]);
        }

        // Normalize query to lowercase and escape wildcard characters
        $normalizedQuery = strtolower($query);
        $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalizedQuery);
        $searchPattern = '%' . $escapedQuery . '%';

        // Search projects with case-insensitive search and wildcard escaping
        $projects = $this->projectRepository->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('LOWER(p.title) LIKE :query ESCAPE :escape OR LOWER(p.description) LIKE :query ESCAPE :escape')
            ->setParameter('user', $user)
            ->setParameter('query', $searchPattern)
            ->setParameter('escape', '\\')
            ->orderBy('p.title', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Search tasks with case-insensitive search and wildcard escaping
        $tasks = $this->taskRepository->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->where('p.user = :user')
            ->andWhere('LOWER(t.title) LIKE :query ESCAPE :escape OR LOWER(t.description) LIKE :query ESCAPE :escape')
            ->setParameter('user', $user)
            ->setParameter('query', $searchPattern)
            ->setParameter('escape', '\\')
            ->orderBy('t.title', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $projectResults = [];
        foreach ($projects as $project) {
            $projectResults[] = [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'description' => $project->getDescription() ?
                    (strlen($project->getDescription()) > 50 ?
                        substr($project->getDescription(), 0, 50).'...' :
                        $project->getDescription()) : '',
                'type' => 'project',
            ];
        }

        $taskResults = [];
        foreach ($tasks as $task) {
            $taskResults[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'project_title' => $task->getProject()->getTitle(),
                'priority' => $task->getPriority(),
                'status' => $task->getStatus()?->value ?? 'unknown',
                'type' => 'task',
            ];
        }

        return $this->successResponse([
            'tasks' => $taskResults,
            'projects' => $projectResults,
        ]);
    }

    #[Route('', name: 'api_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        $searchCriteria = [
            'query' => trim((string) ($data['query'] ?? '')),
            'project_id' => $data['project_id'] ?? null,
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'date_range' => $data['date_range'] ?? null,
        ];

        // Get filtered results
        $tasks = $this->taskRepository->searchWithFilters($user, $searchCriteria);

        // Transform tasks for API response
        $tasksData = array_map(function ($task) {
            $project = $task->getProject();
            $createdAt = $task->getCreatedAt();

            return [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus()?->value ?? 'unknown',
                'priority' => $task->getPriority(),
                'project' => $project ? [
                    'id' => $project->getId(),
                    'title' => $project->getTitle(),
                ] : null,
                'created_at' => $createdAt ? $createdAt->format('c') : null,
            ];
        }, $tasks);

        return $this->successResponse([
            'tasks' => $tasksData,
            'count' => count($tasksData),
            'criteria' => $searchCriteria,
        ]);
    }
}
