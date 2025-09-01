<?php

namespace App\Entity;

use App\Repository\PatternVariationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatternVariationRepository::class)]
#[ORM\Table(name: 'pattern_variations', indexes: [
    new ORM\Index(name: 'idx_variation_pattern', columns: ['base_pattern_id']),
    new ORM\Index(name: 'idx_variation_success_rate', columns: ['success_rate']),
    new ORM\Index(name: 'idx_variation_usage_count', columns: ['usage_count']),
    new ORM\Index(name: 'idx_variation_created_at', columns: ['created_at']),
])]
#[ORM\HasLifecycleCallbacks]
class PatternVariation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: KnowledgePattern::class, inversedBy: 'variations')]
    #[ORM\JoinColumn(name: 'base_pattern_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?KnowledgePattern $basePattern = null;

    #[ORM\Column(type: 'json')]
    private array $contextDifferences = [];

    #[ORM\Column(type: 'json')]
    private array $adaptations = [];

    #[ORM\Column(name: 'success_rate', type: 'float')]
    private ?float $successRate = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $usageCount = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBasePattern(): ?KnowledgePattern
    {
        return $this->basePattern;
    }

    public function setBasePattern(?KnowledgePattern $basePattern): static
    {
        $this->basePattern = $basePattern;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getContextDifferences(): array
    {
        return $this->contextDifferences;
    }

    public function setContextDifferences(array $contextDifferences): static
    {
        $this->contextDifferences = $contextDifferences;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getAdaptations(): array
    {
        return $this->adaptations;
    }

    public function setAdaptations(array $adaptations): static
    {
        $this->adaptations = $adaptations;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getSuccessRate(): ?float
    {
        return $this->successRate;
    }

    public function setSuccessRate(float $successRate): static
    {
        $this->successRate = $successRate;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function incrementUsageCount(): static
    {
        ++$this->usageCount;
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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }
        if (null === $this->updatedAt) {
            $this->updatedAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
