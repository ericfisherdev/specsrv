<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags', indexes: [
    new ORM\Index(name: 'idx_tags_workspace', columns: ['workspace_id']),
    new ORM\Index(name: 'idx_tags_parent', columns: ['parent_id']),
    new ORM\Index(name: 'idx_tags_usage_count', columns: ['usage_count']),
    new ORM\Index(name: 'idx_tags_created_at', columns: ['created_at']),
], uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_tags_workspace_name', columns: ['workspace_id', 'name']),
])]
#[ORM\HasLifecycleCallbacks]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'workspace_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Project $workspace = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Tag name cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 50,
        minMessage: 'Tag name must be at least {{ limit }} character long',
        maxMessage: 'Tag name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Color must be a valid hex color code (e.g., #FF5733)'
    )]
    private ?string $color = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $usageCount = 0;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]
    private Collection $tasks;

    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'tags')]
    private Collection $projects;

    #[ORM\ManyToMany(targetEntity: File::class, mappedBy: 'tags')]
    private Collection $files;

    #[ORM\OneToMany(targetEntity: TagAlias::class, mappedBy: 'tag', orphanRemoval: true)]
    private Collection $aliases;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->children = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->aliases = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getWorkspace(): ?Project
    {
        return $this->workspace;
    }

    public function setWorkspace(?Project $workspace): self
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        if ($parent === $this) {
            throw new \InvalidArgumentException('A tag cannot be its own parent');
        }

        if (null !== $parent) {
            // Check for cycles by walking up the parent chain
            $p = $parent;
            while (null !== $p) {
                if ($p === $this) {
                    throw new \InvalidArgumentException('Setting this parent would create a cycle');
                }
                $p = $p->getParent();
            }
        }

        // Remove from previous parent's children collection if needed
        if (null !== $this->parent && $this->parent !== $parent) {
            $this->parent->children->removeElement($this);
        }

        // Set the new parent
        $this->parent = $parent;

        // Add to new parent's children collection if needed
        if (null !== $parent && ! $parent->children->contains($this)) {
            $parent->children->add($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (! $this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;

        return $this;
    }

    public function incrementUsageCount(): self
    {
        ++$this->usageCount;

        return $this;
    }

    public function decrementUsageCount(): self
    {
        if ($this->usageCount > 0) {
            --$this->usageCount;
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (! $this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->addTag($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            $task->removeTag($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (! $this->projects->contains($project)) {
            $this->projects->add($project);
            if (! $project->getTags()->contains($this)) {
                $project->addTag($this);
            }
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            $project->removeTag($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): self
    {
        if (! $this->files->contains($file)) {
            $this->files->add($file);
            if (! $file->getTags()->contains($this)) {
                $file->addTag($this);
            }
        }

        return $this;
    }

    public function removeFile(File $file): self
    {
        if ($this->files->removeElement($file)) {
            $file->removeTag($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, TagAlias>
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    public function addAlias(TagAlias $alias): self
    {
        if (! $this->aliases->contains($alias)) {
            $this->aliases->add($alias);
            $alias->setTag($this);
        }

        return $this;
    }

    public function removeAlias(TagAlias $alias): self
    {
        if ($this->aliases->removeElement($alias)) {
            if ($alias->getTag() === $this) {
                $alias->setTag(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get the full hierarchical path of the tag.
     */
    public function getPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while (null !== $parent) {
            array_unshift($path, $parent->getName());
            $parent = $parent->getParent();
        }

        return implode(' / ', $path);
    }

    /**
     * Check if this tag is a descendant of another tag.
     */
    public function isDescendantOf(Tag $tag): bool
    {
        $parent = $this->parent;

        while (null !== $parent) {
            if ($parent->getId() === $tag->getId()) {
                return true;
            }
            $parent = $parent->getParent();
        }

        return false;
    }

    /**
     * Get all descendants of this tag.
     */
    public function getAllDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getAllDescendants());
        }

        return $descendants;
    }
}
