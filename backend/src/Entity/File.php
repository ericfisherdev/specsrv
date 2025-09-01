<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: 'files', indexes: [
    new ORM\Index(name: 'idx_file_task', columns: ['task_id']),
    new ORM\Index(name: 'idx_file_entity', columns: ['entity_type', 'entity_id']),
])]
#[ORM\HasLifecycleCallbacks]
class File
{
    public const TYPE_UPLOAD = 'upload';
    public const TYPE_GENERATED = 'generated';
    public const TYPE_ATTACHMENT = 'attachment';

    public const ENTITY_TYPE_TASK = 'task';
    public const ENTITY_TYPE_PROJECT = 'project';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 500)]
    private ?string $path = null;

    #[ORM\Column(length: 50)]
    private ?string $type = self::TYPE_UPLOAD;

    #[ORM\Column(length: 20)]
    private ?string $entityType = null;

    #[ORM\Column]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?Task $task = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'files')]
    #[ORM\JoinTable(name: 'file_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

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

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): static
    {
        $this->task = $task;

        return $this;
    }

    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_UPLOAD,
            self::TYPE_GENERATED,
            self::TYPE_ATTACHMENT,
        ];
    }

    public static function getAvailableEntityTypes(): array
    {
        return [
            self::ENTITY_TYPE_TASK,
            self::ENTITY_TYPE_PROJECT,
        ];
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (! $this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addFile($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeFile($this);
        }

        return $this;
    }
}
