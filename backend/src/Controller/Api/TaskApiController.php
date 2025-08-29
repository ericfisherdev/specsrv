<?php

namespace App\Controller\Api;

use App\Entity\GitLink;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\GitLinkRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks')]
class TaskApiController extends BaseApiController
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private FileRepository $fileRepository,
        private GitLinkRepository $gitLinkRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $pagination = $this->getPaginationParams($request);
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        if ($status && ! in_array($status, Task::getAvailableStatuses())) {
            return $this->errorResponse(
                'Invalid status. Available statuses: '.implode(', ', Task::getAvailableStatuses()),
                'INVALID_STATUS',
                null,
                400
            );
        }

        $tasks = $this->taskRepository->findPaginatedByUser(
            $user,
            $pagination['per_page'],
            $pagination['offset'],
            $status,
            $search
        );

        $totalTasks = $this->taskRepository->countByUser($user, $status, $search);

        $taskData = array_map([$this, 'transformEntity'], $tasks);

        return $this->paginatedResponse(
            $taskData,
            $pagination['page'],
            $pagination['per_page'],
            $totalTasks
        );
    }

    #[Route('/{id}', name: 'api_tasks_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        return $this->successResponse($this->transformEntity($task));
    }

    #[Route('', name: 'api_tasks_create', methods: ['POST'])]
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

        if (! isset($data['project_id'])) {
            return $this->errorResponse('project_id is required', 'MISSING_PROJECT_ID', null, 400);
        }

        $project = $this->projectRepository->find($data['project_id']);

        if (! $project) {
            return $this->errorResponse('Project not found', 'PROJECT_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($project)) {
            return $this->errorResponse('Access denied to project', 'ACCESS_DENIED', null, 403);
        }

        $task = new Task();
        $task->setProject($project);

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (isset($data['status']) && in_array($data['status'], Task::getAvailableStatuses())) {
            $task->setStatus($data['status']);
        }

        $violations = $this->validator->validate($task);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($task),
            'Task created successfully',
            201
        );
    }

    #[Route('/{id}', name: 'api_tasks_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (isset($data['status'])) {
            if (! in_array($data['status'], Task::getAvailableStatuses())) {
                return $this->errorResponse(
                    'Invalid status. Available statuses: '.implode(', ', Task::getAvailableStatuses()),
                    'INVALID_STATUS',
                    null,
                    400
                );
            }
            $task->setStatus($data['status']);
        }

        $violations = $this->validator->validate($task);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($task),
            'Task updated successfully'
        );
    }

    #[Route('/{id}/status', name: 'api_tasks_update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (! isset($data['status'])) {
            return $this->errorResponse('status is required', 'MISSING_STATUS', null, 400);
        }

        if (! in_array($data['status'], Task::getAvailableStatuses())) {
            return $this->errorResponse(
                'Invalid status. Available statuses: '.implode(', ', Task::getAvailableStatuses()),
                'INVALID_STATUS',
                null,
                400
            );
        }

        $task->setStatus($data['status']);
        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($task),
            'Task status updated successfully'
        );
    }

    #[Route('/{id}', name: 'api_tasks_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->successResponse(null, 'Task deleted successfully', 204);
    }

    #[Route('/{id}/files', name: 'api_tasks_files_list', methods: ['GET'])]
    public function listFiles(int $id): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $files = $this->fileRepository->findBy([
            'entityType' => 'task',
            'entityId' => $id,
        ], ['createdAt' => 'DESC']);

        $filesData = array_map([$this, 'transformEntity'], $files);

        return $this->successResponse([
            'files' => $filesData,
            'total' => count($filesData),
        ]);
    }

    #[Route('/{taskId}/files/{fileId}', name: 'api_tasks_files_delete', methods: ['DELETE'])]
    public function deleteFile(int $taskId, int $fileId): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($taskId);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $file = $this->fileRepository->find($fileId);

        if (! $file || 'task' !== $file->getEntityType() || $file->getEntityId() !== $taskId) {
            return $this->errorResponse('File not found', 'FILE_NOT_FOUND', null, 404);
        }

        $this->entityManager->remove($file);
        $this->entityManager->flush();

        return $this->successResponse(null, 'File deleted successfully', 204);
    }

    #[Route('/{id}/git-links', name: 'api_tasks_git_links_create', methods: ['POST'])]
    public function createGitLink(int $id, Request $request): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (empty($data['commit_hash']) && empty($data['pr_reference'])) {
            return $this->errorResponse('Either commit_hash or pr_reference is required', 'MISSING_PARAMS', null, 400);
        }

        $gitLink = new GitLink();
        $gitLink->setTask($task);

        if (isset($data['commit_hash'])) {
            $commitHash = trim($data['commit_hash']);
            if (preg_match('/^[a-f0-9]{7,40}$/i', $commitHash)) {
                $gitLink->setCommitHash($commitHash);
            } else {
                return $this->errorResponse('Invalid commit hash format', 'INVALID_COMMIT_HASH', null, 400);
            }
        }

        if (isset($data['pr_reference'])) {
            $prReference = trim($data['pr_reference']);
            if (preg_match('/^#?\d+$/', $prReference)) {
                $gitLink->setPrReference(ltrim($prReference, '#'));
            } else {
                return $this->errorResponse('Invalid PR reference format', 'INVALID_PR_REFERENCE', null, 400);
            }
        }

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

    #[Route('/{id}/git-links', name: 'api_tasks_git_links_list', methods: ['GET'])]
    public function listGitLinks(int $id): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $gitLinks = $this->gitLinkRepository->findBy([
            'task' => $task,
        ], ['createdAt' => 'DESC']);

        $gitLinksData = array_map([$this, 'transformEntity'], $gitLinks);

        return $this->successResponse([
            'git_links' => $gitLinksData,
            'total' => count($gitLinksData),
        ]);
    }

    #[Route('/{taskId}/git-links/{linkId}', name: 'api_tasks_git_links_delete', methods: ['DELETE'])]
    public function deleteGitLink(int $taskId, int $linkId): JsonResponse
    {
        $this->requireAuth();

        $task = $this->taskRepository->find($taskId);

        if (! $task) {
            return $this->errorResponse('Task not found', 'TASK_NOT_FOUND', null, 404);
        }

        if (! $this->checkResourceOwnership($task)) {
            return $this->errorResponse('Access denied', 'ACCESS_DENIED', null, 403);
        }

        $gitLink = $this->gitLinkRepository->find($linkId);

        if (! $gitLink || $gitLink->getTask() !== $task) {
            return $this->errorResponse('Git link not found', 'GIT_LINK_NOT_FOUND', null, 404);
        }

        $this->entityManager->remove($gitLink);
        $this->entityManager->flush();

        return $this->successResponse(null, 'Git link deleted successfully', 204);
    }
}
