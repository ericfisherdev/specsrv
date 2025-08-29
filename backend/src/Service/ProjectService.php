<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function createProject(User $user, string $title, ?string $description = null, ?string $githubRepo = null): Project
    {
        $project = new Project();
        $project->setUser($user);
        $project->setTitle($title);

        if ($description) {
            $project->setDescription($description);
        }

        if ($githubRepo) {
            $project->setGithubRepo($githubRepo);
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Project validation failed: '.(string) $errors);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    public function updateProject(Project $project, array $data): void
    {
        if (isset($data['title'])) {
            $project->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['github_repo'])) {
            $project->setGithubRepo($data['github_repo']);
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Project validation failed: '.(string) $errors);
        }

        $this->entityManager->flush();
    }

    public function deleteProject(Project $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    public function getProjectsForUser(User $user, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $projects = $this->projectRepository->findPaginatedByUser($user, $perPage, $offset);
        $totalCount = $this->projectRepository->countByUser($user);

        return [
            'projects' => $projects,
            'total_count' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($totalCount / $perPage),
        ];
    }

    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->find($id);
    }

    public function findProjectByTitleAndUser(string $title, User $user): ?Project
    {
        return $this->projectRepository->findByTitleAndUser($title, $user);
    }

    public function userOwnsProject(User $user, Project $project): bool
    {
        return $project->getUser() === $user;
    }
}