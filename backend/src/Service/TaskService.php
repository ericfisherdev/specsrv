<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function createTask(
        Project $project,
        string $title,
        ?string $description = null,
        TaskStatusEnum $status = TaskStatusEnum::TODO
    ): Task {
        $task = new Task();
        $task->setProject($project);
        $task->setTitle($title);
        $task->setStatus($status);

        if ($description) {
            $task->setDescription($description);
        }

        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Task validation failed: '.$this->formatValidationErrors($errors));
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function updateTask(Task $task, array $data): void
    {
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (isset($data['status']) && in_array($data['status'], Task::getAvailableStatuses())) {
            $statusEnum = TaskStatusEnum::from($data['status']);
            $task->setStatus($statusEnum);
        }

        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Task validation failed: '.$this->formatValidationErrors($errors));
        }

        $this->entityManager->flush();
    }

    public function updateTaskStatus(Task $task, TaskStatusEnum $status): void
    {
        $task->setStatus($status);
        $this->entityManager->flush();
    }

    public function deleteTask(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function getTasksForProject(Project $project, ?string $status = null): array
    {
        return $this->taskRepository->findByProject($project, $status);
    }

    public function getTaskById(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    public function userOwnsTask(User $user, Task $task): bool
    {
        $project = $task->getProject();

        return $project && $project->getUser() === $user;
    }

    public function getTasksByStatus(Project $project, TaskStatusEnum $status): array
    {
        return $this->taskRepository->findByProjectAndStatus($project, $status->value);
    }

    public function getTaskStatistics(Project $project): array
    {
        $activeTasks = $this->getActiveTasksForProject($project);
        $totalActiveTasks = count($activeTasks);
        $backlogTasks = count($this->getTasksByStatus($project, TaskStatusEnum::BACKLOG));
        $todoTasks = count($this->getTasksByStatus($project, TaskStatusEnum::TODO));
        $inProgressTasks = count($this->getTasksByStatus($project, TaskStatusEnum::IN_PROGRESS));
        $reviewTasks = count($this->getTasksByStatus($project, TaskStatusEnum::REVIEW));
        $completedTasks = count($this->getTasksByStatus($project, TaskStatusEnum::COMPLETED));
        $obsoleteTasks = count($this->getTasksByStatus($project, TaskStatusEnum::OBSOLETE));

        return [
            'total_active' => $totalActiveTasks,
            'total_all' => $totalActiveTasks + $obsoleteTasks,
            'backlog' => $backlogTasks,
            'todo' => $todoTasks,
            'in_progress' => $inProgressTasks,
            'review' => $reviewTasks,
            'completed' => $completedTasks,
            'obsolete' => $obsoleteTasks,
            'completion_rate' => $totalActiveTasks > 0 ? round(($completedTasks / $totalActiveTasks) * 100, 2) : 0,
        ];
    }

    public function getActiveTasksForProject(Project $project): array
    {
        return $this->taskRepository->findActiveByProject($project);
    }

    private function formatValidationErrors(\Symfony\Component\Validator\ConstraintViolationListInterface $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return implode(', ', $messages);
    }
}
