<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/projects')]
class ProjectApiController extends BaseApiController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_projects_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);
        $pagination = $this->getPaginationParams($request);

        $projects = $this->projectRepository->findPaginatedByUser(
            $user,
            $pagination['per_page'],
            $pagination['offset']
        );

        $totalProjects = $this->projectRepository->countByUser($user);

        $projectData = array_map([$this, 'transformEntity'], $projects);

        return $this->paginatedResponse(
            $projectData,
            $pagination['page'],
            $pagination['per_page'],
            $totalProjects
        );
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        $project = new Project();
        $project->setUser($user);

        if (isset($data['title'])) {
            $project->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['github_repo'])) {
            $project->setGithubRepo($data['github_repo']);
        }

        $violations = $this->validator->validate($project);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($project),
            'Project created successfully',
            201
        );
    }

    #[Route('/{id}', name: 'api_projects_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->requireAuth();

        $project = $this->projectRepository->find($id);

        if (! $project) {
            return $this->errorResponse('Project not found', 'PROJECT_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($project)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        return $this->successResponse($this->transformEntity($project));
    }

    #[Route('/{id}', name: 'api_projects_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->requireAuth();

        $project = $this->projectRepository->find($id);

        if (! $project) {
            return $this->errorResponse('Project not found', 'PROJECT_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($project)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (isset($data['title'])) {
            $project->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['github_repo'])) {
            $project->setGithubRepo($data['github_repo']);
        }

        $violations = $this->validator->validate($project);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($project),
            'Project updated successfully'
        );
    }

    #[Route('/{id}', name: 'api_projects_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->requireAuth();

        $project = $this->projectRepository->find($id);

        if (! $project) {
            return $this->errorResponse('Project not found', 'PROJECT_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($project)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->successResponse(null, 'Project deleted successfully', 204);
    }
}
