<?php

namespace App\Entity;

use App\Repository\TagAliasRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagAliasRepository::class)]
#[ORM\Table(name: 'tag_aliases', indexes: [
    new ORM\Index(name: 'idx_tag_alias_workspace', columns: ['workspace_id']),
    new ORM\Index(name: 'idx_tag_alias_tag', columns: ['tag_id']),
])]
#[ORM\UniqueConstraint(name: 'uniq_tag_alias_workspace_alias', columns: ['workspace_id', 'alias'])]
class TagAlias
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'aliases')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tag $tag = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Alias cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 50,
        minMessage: 'Alias must be at least {{ limit }} character long',
        maxMessage: 'Alias cannot be longer than {{ limit }} characters'
    )]
    private ?string $alias = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'workspace_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Project $workspace = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}