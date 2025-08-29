<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
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
        string $status = Task::STATUS_TODO
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
            throw new \InvalidArgumentException('Task validation failed: '.(string) $errors);
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
            $task->setStatus($data['status']);
        }

        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Task validation failed: '.(string) $errors);
        }

        $this->entityManager->flush();
    }

    public function updateTaskStatus(Task $task, string $status): void
    {
        if (! in_array($status, Task::getAvailableStatuses())) {
            throw new \InvalidArgumentException('Invalid task status: '.$status);
        }

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
        return $task->getProject()->getUser() === $user;
    }

    public function getTasksByStatus(Project $project, string $status): array
    {
        return $this->taskRepository->findByProjectAndStatus($project, $status);
    }

    public function getTaskStatistics(Project $project): array
    {
        $totalTasks = count($this->getTasksForProject($project));
        $todoTasks = count($this->getTasksByStatus($project, Task::STATUS_TODO));
        $inProgressTasks = count($this->getTasksByStatus($project, Task::STATUS_IN_PROGRESS));
        $completedTasks = count($this->getTasksByStatus($project, Task::STATUS_COMPLETED));
        $cancelledTasks = count($this->getTasksByStatus($project, Task::STATUS_CANCELLED));

        return [
            'total' => $totalTasks,
            'todo' => $todoTasks,
            'in_progress' => $inProgressTasks,
            'completed' => $completedTasks,
            'cancelled' => $cancelledTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
        ];
    }
}