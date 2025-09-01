<?php

namespace App\Entity;

use App\Repository\KnowledgePatternRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KnowledgePatternRepository::class)]
#[ORM\Table(name: 'knowledge_patterns', indexes: [
    new ORM\Index(name: 'idx_pattern_type', columns: ['pattern_type']),
    new ORM\Index(name: 'idx_confidence_score', columns: ['confidence_score']),
    new ORM\Index(name: 'idx_usage_count', columns: ['usage_count']),
    new ORM\Index(name: 'idx_last_success', columns: ['last_successful_use']),
])]
#[ORM\HasLifecycleCallbacks]
class KnowledgePattern
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $patternName = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $patternType = null;

    #[ORM\Column(type: 'json')]
    private array $contextSignature = [];

    #[ORM\Column(type: 'json')]
    private array $solutionTemplate = [];

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2)]
    private ?float $confidenceScore = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $usageCount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastSuccessfulUse = null;

    #[ORM\Column(type: 'json')]
    private array $prerequisites = [];

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: PatternVariation::class, mappedBy: 'basePattern', orphanRemoval: true)]
    private Collection $variations;

    public function __construct()
    {
        $this->variations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatternName(): ?string
    {
        return $this->patternName;
    }

    public function setPatternName(string $patternName): static
    {
        $this->patternName = $patternName;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getPatternType(): ?string
    {
        return $this->patternType;
    }

    public function setPatternType(string $patternType): static
    {
        $this->patternType = $patternType;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getContextSignature(): array
    {
        return $this->contextSignature;
    }

    public function setContextSignature(array $contextSignature): static
    {
        $this->contextSignature = $contextSignature;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getSolutionTemplate(): array
    {
        return $this->solutionTemplate;
    }

    public function setSolutionTemplate(array $solutionTemplate): static
    {
        $this->solutionTemplate = $solutionTemplate;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getConfidenceScore(): ?float
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(float $confidenceScore): static
    {
        $this->confidenceScore = $confidenceScore;
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
        $this->usageCount++;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getLastSuccessfulUse(): ?\DateTimeInterface
    {
        return $this->lastSuccessfulUse;
    }

    public function setLastSuccessfulUse(?\DateTimeInterface $lastSuccessfulUse): static
    {
        $this->lastSuccessfulUse = $lastSuccessfulUse;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getPrerequisites(): array
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(array $prerequisites): static
    {
        $this->prerequisites = $prerequisites;
        $this->setUpdatedAt(new \DateTime());
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
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
     * @return Collection<int, PatternVariation>
     */
    public function getVariations(): Collection
    {
        return $this->variations;
    }

    public function addVariation(PatternVariation $variation): static
    {
        if (!$this->variations->contains($variation)) {
            $this->variations->add($variation);
            $variation->setBasePattern($this);
        }

        return $this;
    }

    public function removeVariation(PatternVariation $variation): static
    {
        if ($this->variations->removeElement($variation)) {
            if ($variation->getBasePattern() === $this) {
                $variation->setBasePattern(null);
            }
        }

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