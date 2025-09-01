<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\GitLink;
use App\Entity\User;
use App\Repository\GitLinkRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/git-links')]
class GitLinkApiController extends BaseApiController
{
    public function __construct(
        private GitLinkRepository $gitLinkRepository,
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/create', name: 'api_git_links_create', methods: ['POST'])]
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

        $taskId = $data['task_id'] ?? null;
        if (! $taskId) {
            return $this->errorResponse('Task ID is required', 'TASK_ID_REQUIRED', null, 400);
        }

        $task = $this->taskRepository->find($taskId);
        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        // Check if user owns the task's project
        $project = $task->getProject();
        if (! $project || $project->getUser() !== $user) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $commitHash = $data['commit_hash'] ?? null;
        $prReference = $data['pr_reference'] ?? null;

        if (! $commitHash && ! $prReference) {
            return $this->errorResponse('Either commit hash or PR reference is required', 'INVALID_DATA', null, 400);
        }

        $gitLink = new GitLink();
        $gitLink->setTask($task);
        $gitLink->setCommitHash($commitHash);
        $gitLink->setPrReference($prReference);

        $violations = $this->validator->validate($gitLink);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->persist($gitLink);
        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($gitLink),
            'Git link created successfully',
            201
        );
    }

    #[Route('/{id}', name: 'api_git_links_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $gitLink = $this->gitLinkRepository->find($id);
        if (! $gitLink) {
            return $this->errorResponse('Git link not found', 'GIT_LINK_NOT_FOUND', null, 404);
        }

        // Check if user owns the git link's task's project
        $task = $gitLink->getTask();
        $project = $task?->getProject();
        if (! $task || ! $project || $project->getUser() !== $user) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $this->entityManager->remove($gitLink);
        $this->entityManager->flush();

        return $this->successResponse(null, 'Git link deleted successfully', 204);
    }

    #[Route('/{id}', name: 'api_git_links_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $gitLink = $this->gitLinkRepository->find($id);
        if (! $gitLink) {
            return $this->errorResponse('Git link not found', 'GIT_LINK_NOT_FOUND', null, 404);
        }

        // Check if user owns the git link's task's project
        $task = $gitLink->getTask();
        $project = $task?->getProject();
        if (! $task || ! $project || $project->getUser() !== $user) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        return $this->successResponse($this->transformEntity($gitLink));
    }
}
