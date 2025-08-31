<?php

namespace App\Entity;

use App\Repository\GitLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GitLinkRepository::class)]
#[ORM\Table(name: 'git_links', indexes: [
    new ORM\Index(name: 'idx_git_link_task', columns: ['task_id']),
    new ORM\Index(name: 'idx_git_link_commit', columns: ['commit_hash']),
    new ORM\Index(name: 'idx_git_link_pr', columns: ['pr_reference']),
])]
class GitLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'gitLinks')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $commitHash = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $prReference = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): static
    {
        $this->task = $task;

        return $this;
    }

    public function getCommitHash(): ?string
    {
        return $this->commitHash;
    }

    public function setCommitHash(?string $commitHash): static
    {
        $this->commitHash = $commitHash;

        return $this;
    }

    public function getPrReference(): ?string
    {
        return $this->prReference;
    }

    public function setPrReference(?string $prReference): static
    {
        $this->prReference = $prReference;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
