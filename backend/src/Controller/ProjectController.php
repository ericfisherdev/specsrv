<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/projects', name: 'app_projects')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('projects/index.html.twig');
    }

    #[Route('/projects/list', name: 'app_projects_list_fragment', methods: ['GET'])]
    public function listFragment(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        $search = (string) $request->query->get('search', '');
        $status = (string) $request->query->get('status', '');
        
        $projects = $this->projectRepository->findByUserWithFilters($user, $search, $status);
        
        // Add task count for each project
        $projectsWithStats = array_map(function (Project $project) {
            $taskCount = $this->taskRepository->countByProject($project);
            return [
                'project' => $project,
                'task_count' => $taskCount,
            ];
        }, $projects);

        return $this->render('projects/partials/project_list.html.twig', [
            'projects' => $projectsWithStats,
        ]);
    }

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
        if (!empty($description)) {
            $project->setDescription($description);
        }
        
        if (!empty($githubRepo)) {
            $project->setGithubRepo($githubRepo);
        }

        $violations = $this->validator->validate($project);
        if (count($violations) > 0) {
            return $this->render('projects/partials/error.html.twig', [
                'errors' => $violations,
            ], new Response('', 400));
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $taskCount = 0; // New project has no tasks

        return $this->render('projects/partials/project_card.html.twig', [
            'project' => $project,
            'task_count' => $taskCount,
        ]);
    }

    #[Route('/projects/{id}/delete', name: 'app_projects_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $project = $this->projectRepository->find($id);

        if (!$project) {
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
}