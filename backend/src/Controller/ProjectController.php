<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    // DISABLED for frontend migration: HTML-returning method
    // Frontend will use API endpoints instead
    // #[Route('/projects', name: 'app_projects')]
    // public function index(): Response
    // {
    //     $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    //
    //     return $this->render('projects/index.html.twig');
    // }

    // DISABLED for frontend migration: HTML-returning method
    // Frontend will use API endpoints instead
    // #[Route('/projects/list', name: 'app_projects_list_fragment', methods: ['GET'])]
    // public function listFragment(Request $request): Response
    // {
    //     $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    //     $user = $this->getUser();
    //     assert($user instanceof User);
    //
    //     $search = (string) $request->query->get('search', '');
    //     $status = (string) $request->query->get('status', '');
    //
    //     $projects = $this->projectRepository->findByUserWithFilters($user, $search, $status);
    //
    //     // Add task count for each project
    //     $projectsWithStats = array_map(function (Project $project) {
    //         $taskCount = $this->taskRepository->countByProject($project);
    //
    //         return [
    //             'project' => $project,
    //             'task_count' => $taskCount,
    //         ];
    //     }, $projects);
    //
    //     return $this->render('projects/partials/project_list.html.twig', [
    //         'projects' => $projectsWithStats,
    //     ]);
    // }

    #[Route('/projects/create', name: 'app_projects_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        // Handle JSON body parsing
        $data = [];
        if (str_starts_with($request->headers->get('Content-Type', ''), 'application/json')) {
            $json = $request->getContent();
            $data = json_decode($json, true) ?: [];
        }

        $name = (string) ($data['name'] ?? $request->request->get('name', ''));
        $description = (string) ($data['description'] ?? $request->request->get('description', ''));
        $githubRepo = (string) ($data['github_repo'] ?? $request->request->get('github_repo', ''));

        $project = new Project();
        $project->setUser($user);
        $project->setTitle($name);
        if (! empty($description)) {
            $project->setDescription($description);
        }

        if (! empty($githubRepo)) {
            $project->setGithubRepo($githubRepo);
        }

        $violations = $this->validator->validate($project);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $this->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Validation failed'],
                'errors' => $errors,
            ], 422);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $taskCount = 0; // New project has no tasks

        // Return JSON for API with HTTP 201, ISO-8601 timestamps, and Location header
        $response = $this->json([
            'success' => true,
            'project' => [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'description' => $project->getDescription(),
                'github_repo' => $project->getGithubRepo(),
                'created_at' => $project->getCreatedAt()?->format('c') ?? null,
                'task_count' => $taskCount,
            ],
        ], 201);

        $response->headers->set('Location', '/api/v1/projects/' . $project->getId());

        return $response;
    }

    #[Route('/projects/{id}', name: 'app_project_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // DISABLED for frontend migration: HTML-returning method
        // Frontend will use API endpoints instead
        throw $this->createNotFoundException('HTML view disabled. Use API endpoints instead.');
    }


    #[Route('/projects/{id}/delete', name: 'app_projects_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $project = $this->projectRepository->find($id);

        if (! $project) {
            return new Response('', 404);
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            return new Response('', 403);
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return new Response('', 200);
    }

    #[Route('/search/autocomplete', name: 'app_search_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        $query = (string) $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['tasks' => [], 'projects' => []]);
        }

        // Search projects
        $projects = $this->projectRepository->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.title', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Search tasks
        $tasks = $this->entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->where('p.user = :user')
            ->andWhere('t.title LIKE :query OR t.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%'.$query.'%')
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
                'task_count' => count($project->getTasks()),
                'url' => '/api/v1/projects/' . $project->getId(),
            ];
        }

        $taskResults = [];
        foreach ($tasks as $task) {
            $taskResults[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'project_title' => $task->getProject()->getTitle(),
                'priority' => $task->getPriority(),
                'status' => $task->getStatus(),
                'url' => '/api/v1/tasks/' . $task->getId(),
            ];
        }

        return new JsonResponse([
            'tasks' => $taskResults,
            'projects' => $projectResults,
        ]);
    }
}
