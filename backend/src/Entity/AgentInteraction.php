<?php

namespace App\Entity;

use App\Repository\AgentInteractionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentInteractionRepository::class)]
#[ORM\Table(name: 'agent_interactions', indexes: [
    new ORM\Index(name: 'idx_agent_task', columns: ['task_id']),
    new ORM\Index(name: 'idx_agent_type', columns: ['agent_type']),
    new ORM\Index(name: 'idx_success_score', columns: ['success_score']),
    new ORM\Index(name: 'idx_created_at', columns: ['created_at']),
    new ORM\Index(name: 'idx_pattern_hash', columns: ['pattern_hash']),
])]
#[ORM\HasLifecycleCallbacks]
class AgentInteraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $agentType = null;

    #[ORM\Column(type: 'json')]
    private array $inputContext = [];

    #[ORM\Column(type: 'json')]
    private array $executionSteps = [];

    #[ORM\Column(type: 'json')]
    private array $outputResult = [];

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2)]
    private ?float $successScore = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $patternHash = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $errorLog = null;

    #[ORM\Column(type: 'integer')]
    private ?int $executionTimeMs = null;

    #[ORM\Column(type: 'datetime')]
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

    public function getAgentType(): ?string
    {
        return $this->agentType;
    }

    public function setAgentType(string $agentType): static
    {
        $this->agentType = $agentType;
        return $this;
    }

    public function getInputContext(): array
    {
        return $this->inputContext;
    }

    public function setInputContext(array $inputContext): static
    {
        $this->inputContext = $inputContext;
        return $this;
    }

    public function getExecutionSteps(): array
    {
        return $this->executionSteps;
    }

    public function setExecutionSteps(array $executionSteps): static
    {
        $this->executionSteps = $executionSteps;
        return $this;
    }

    public function getOutputResult(): array
    {
        return $this->outputResult;
    }

    public function setOutputResult(array $outputResult): static
    {
        $this->outputResult = $outputResult;
        return $this;
    }

    public function getSuccessScore(): ?float
    {
        return $this->successScore;
    }

    public function setSuccessScore(float $successScore): static
    {
        $this->successScore = $successScore;
        return $this;
    }

    public function getPatternHash(): ?string
    {
        return $this->patternHash;
    }

    public function setPatternHash(string $patternHash): static
    {
        $this->patternHash = $patternHash;
        return $this;
    }

    public function getErrorLog(): ?array
    {
        return $this->errorLog;
    }

    public function setErrorLog(?array $errorLog): static
    {
        $this->errorLog = $errorLog;
        return $this;
    }

    public function getExecutionTimeMs(): ?int
    {
        return $this->executionTimeMs;
    }

    public function setExecutionTimeMs(int $executionTimeMs): static
    {
        $this->executionTimeMs = $executionTimeMs;
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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }
}