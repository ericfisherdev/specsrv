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

        $name = (string) $request->request->get('name', '');
        $description = (string) $request->request->get('description', '');
        $githubRepo = (string) $request->request->get('github_repo', '');

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
            ], 400);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $taskCount = 0; // New project has no tasks

        // DISABLED: Return JSON for API instead of HTML template
        return $this->json([
            'success' => true,
            'project' => [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'description' => $project->getDescription(),
                'github_repo' => $project->getGithubRepo(),
                'created_at' => $project->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'unknown',
                'task_count' => $taskCount,
            ],
        ]);
    }

    #[Route('/projects/{id}', name: 'app_project_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $project = $this->projectRepository->find($id);

        if (! $project) {
            throw $this->createNotFoundException('Project not found');
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $tasks = $this->taskRepository->findByProject($project);
        $taskStats = [
            'total' => count($tasks),
            'todo' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        foreach ($tasks as $task) {
            $status = $task->getStatus();
            if (TaskStatusEnum::TODO === $status || TaskStatusEnum::BACKLOG === $status) {
                ++$taskStats['todo'];
            } elseif (TaskStatusEnum::IN_PROGRESS === $status || TaskStatusEnum::REVIEW === $status) {
                ++$taskStats['in_progress'];
            } elseif (TaskStatusEnum::COMPLETED === $status) {
                ++$taskStats['completed'];
            }
        }

        // DISABLED for frontend migration: HTML-returning method
        // Frontend will use API endpoints instead
        throw $this->createNotFoundException('HTML view disabled. Use API endpoints instead.');
    }

    #[Route('/projects/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $project = $this->projectRepository->find($id);

        if (! $project) {
            throw $this->createNotFoundException('Project not found');
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied');
        }

        if ($request->isMethod('POST')) {
            $name = (string) $request->request->get('name', '');
            $description = (string) $request->request->get('description', '');
            $githubRepo = (string) $request->request->get('github_repo', '');

            $project->setTitle($name);
            if (! empty($description)) {
                $project->setDescription($description);
            }

            if (! empty($githubRepo)) {
                $project->setGithubRepo($githubRepo);
            } else {
                $project->setGithubRepo(null);
            }

            $violations = $this->validator->validate($project);
            if (count($violations) > 0) {
                return $this->json(['errors' => $violations], 400);
            }

            $this->entityManager->flush();

            return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_project_detail', ['id' => $project->getId()])]);
        }

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
                'url' => $this->generateUrl('app_project_detail', ['id' => $project->getId()]),
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
                'url' => $this->generateUrl('app_task_detail', ['id' => $task->getId()]),
            ];
        }

        return new JsonResponse([
            'tasks' => $taskResults,
            'projects' => $projectResults,
        ]);
    }
}
