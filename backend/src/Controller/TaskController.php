<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController extends AbstractController
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private FileUploadService $fileUploadService
    ) {
    }

    #[Route('/tasks/create', name: 'app_task_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        assert($user instanceof User);

        $title = (string) $request->request->get('title', '');
        $description = (string) $request->request->get('description', '');
        $projectId = (int) $request->request->get('project_id', 0);
        $priority = (string) $request->request->get('priority', Task::PRIORITY_MEDIUM);
        $status = (string) $request->request->get('status', 'todo');

        if (0 === $projectId) {
            return $this->json(['error' => 'Project is required'], 400);
        }

        $project = $this->projectRepository->find($projectId);
        if (! $project || $project->getUser() !== $user) {
            return $this->json(['error' => 'Invalid project'], 400);
        }

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setProject($project);
        $task->setPriority($priority);

        // Validate status with safer enum handling
        $statusEnum = TaskStatusEnum::tryFrom($status);
        if (! $statusEnum) {
            return $this->json(['error' => 'Invalid status'], 400);
        }
        $task->setStatus($statusEnum);

        $violations = $this->validator->validate($task);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], 400);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Handle file uploads
        $uploadedFiles = $request->files->get('files', []);
        if (! is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                try {
                    $taskId = $task->getId();
                    if (null === $taskId) {
                        continue; // Skip if task ID is null
                    }

                    $file = $this->fileUploadService->uploadFile(
                        $uploadedFile,
                        'task',
                        $taskId
                    );
                    $task->addFile($file);
                } catch (\Exception $e) {
                    // Log error but don't fail task creation
                    error_log('File upload error: '.$e->getMessage());
                }
            }
        }

        if (! empty($uploadedFiles)) {
            $this->entityManager->flush();
        }

        return $this->render('tasks/partials/task_card.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}', name: 'app_task_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = $this->taskRepository->find($id);

        if (! $task) {
            throw $this->createNotFoundException('Task not found');
        }

        $project = $task->getProject();
        if (! $project) {
            throw $this->createNotFoundException('Task project not found');
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('tasks/detail.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $project = $task->getProject();
        if (! $project) {
            return $this->json(['error' => 'Task project not found'], 404);
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        if ($request->isMethod('POST')) {
            $title = (string) $request->request->get('title', '');
            $description = (string) $request->request->get('description', '');
            $priority = (string) $request->request->get('priority', Task::PRIORITY_MEDIUM);
            $status = (string) $request->request->get('status', 'todo');

            $task->setTitle($title);
            $task->setDescription($description);
            $task->setPriority($priority);

            // Validate status with safer enum handling
            $statusEnum = TaskStatusEnum::tryFrom($status);
            if (! $statusEnum) {
                return $this->json(['error' => 'Invalid status'], 400);
            }
            $task->setStatus($statusEnum);

            $violations = $this->validator->validate($task);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }

                return $this->json(['errors' => $errors], 400);
            }

            $this->entityManager->flush();

            return $this->json(['success' => true]);
        }

        return $this->render('tasks/edit.html.twig', [
            'task' => $task,
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    #[Route('/tasks/{id}/delete', name: 'app_task_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return new Response('', 404);
        }

        $project = $task->getProject();
        if (! $project) {
            return new Response('', 404);
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            return new Response('', 403);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return new Response('', 200);
    }

    #[Route('/tasks/{id}/status', name: 'app_task_update_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = $this->taskRepository->find($id);

        if (! $task) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $project = $task->getProject();
        if (! $project) {
            return $this->json(['error' => 'Task project not found'], 404);
        }

        $user = $this->getUser();
        if ($project->getUser() !== $user) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? '';

        if (! in_array($status, Task::getAvailableStatuses())) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        $statusEnum = TaskStatusEnum::from($status);
        $task->setStatus($statusEnum);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    private function getStatusOptions(): array
    {
        $options = [];
        foreach (TaskStatusEnum::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }

        return $options;
    }
}
