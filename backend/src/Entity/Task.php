<?php

namespace App\Entity;

use App\Enum\TaskStatusEnum;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
class Task
{
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'todo';

    #[ORM\Column(length: 20)]
    private ?string $priority = self::PRIORITY_MEDIUM;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'task', orphanRemoval: true)]
    private Collection $files;

    #[ORM\OneToMany(targetEntity: GitLink::class, mappedBy: 'task', orphanRemoval: true)]
    private Collection $gitLinks;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->gitLinks = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getStatus(): ?TaskStatusEnum
    {
        if (! $this->status) {
            return null;
        }

        // Use tryFrom to avoid fatal errors on unknown/unmigrated DB values
        $statusEnum = TaskStatusEnum::tryFrom($this->status);
        if (! $statusEnum) {
            // Log unexpected values for debugging but don't throw
            error_log('Warning: Unknown task status value: '.$this->status);

            return null;
        }

        return $statusEnum;
    }

    public function setStatus(TaskStatusEnum $status): static
    {
        $this->status = $status->value;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getStatusValue(): ?string
    {
        return $this->status;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        $this->setUpdatedAt(new \DateTime());

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (! $this->files->contains($file)) {
            $this->files->add($file);
            $file->setTask($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            if ($file->getTask() === $this) {
                $file->setTask(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GitLink>
     */
    public function getGitLinks(): Collection
    {
        return $this->gitLinks;
    }

    public function addGitLink(GitLink $gitLink): static
    {
        if (! $this->gitLinks->contains($gitLink)) {
            $this->gitLinks->add($gitLink);
            $gitLink->setTask($this);
        }

        return $this;
    }

    public function removeGitLink(GitLink $gitLink): static
    {
        if ($this->gitLinks->removeElement($gitLink)) {
            if ($gitLink->getTask() === $this) {
                $gitLink->setTask(null);
            }
        }

        return $this;
    }

    public static function getAvailableStatuses(): array
    {
        return array_map(fn (TaskStatusEnum $status) => $status->value, TaskStatusEnum::cases());
    }

    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'text-green-600 bg-green-100',
            self::PRIORITY_MEDIUM => 'text-yellow-600 bg-yellow-100',
            self::PRIORITY_HIGH => 'text-orange-600 bg-orange-100',
            self::PRIORITY_CRITICAL => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Unknown',
        };
    }
}
