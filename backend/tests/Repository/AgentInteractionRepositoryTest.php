<?php

// backend/tests/Repository/AgentInteractionRepositoryTest.php

namespace App\Tests\Repository;

use App\Entity\AgentInteraction;
use App\Entity\Task;
use App\Repository\AgentInteractionRepository;
use App\Tests\AbstractKernelTestCase;

class AgentInteractionRepositoryTest extends AbstractKernelTestCase
{
    private AgentInteractionRepository $repository;
    private Task $testTask;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(AgentInteractionRepository::class);

        // Create test task
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->testTask = $this->createTestTask($project);
    }

    public function testFindByTask(): void
    {
        // First check how many interactions already exist for this task
        $existingCount = count($this->repository->findByTask($this->testTask));

        // Create test interactions
        $interaction1 = $this->createTestInteraction($this->testTask, 'agent_a', 0.8);
        $interaction2 = $this->createTestInteraction($this->testTask, 'agent_b', 0.9);

        $this->entityManager->persist($interaction1);
        $this->entityManager->persist($interaction2);
        $this->entityManager->flush();

        $results = $this->repository->findByTask($this->testTask);

        $this->assertCount($existingCount + 2, $results);
        // Find our new interactions in the results
        $resultIds = array_map(fn ($i) => $i->getId(), $results);
        $this->assertContains($interaction1->getId(), $resultIds);
        $this->assertContains($interaction2->getId(), $resultIds);
    }

    public function testFindByAgentType(): void
    {
        $interaction1 = $this->createTestInteraction($this->testTask, 'implementation', 0.8);
        $interaction2 = $this->createTestInteraction($this->testTask, 'implementation', 0.9);
        $interaction3 = $this->createTestInteraction($this->testTask, 'testing', 0.7);

        $this->entityManager->persist($interaction1);
        $this->entityManager->persist($interaction2);
        $this->entityManager->persist($interaction3);
        $this->entityManager->flush();

        $results = $this->repository->findByAgentType('implementation', 10);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals('implementation', $result->getAgentType());
        }
    }

    public function testFindSuccessfulInteractions(): void
    {
        $interaction1 = $this->createTestInteraction($this->testTask, 'agent_a', 0.9);
        $interaction2 = $this->createTestInteraction($this->testTask, 'agent_b', 0.6);
        $interaction3 = $this->createTestInteraction($this->testTask, 'agent_c', 0.85);

        $this->entityManager->persist($interaction1);
        $this->entityManager->persist($interaction2);
        $this->entityManager->persist($interaction3);
        $this->entityManager->flush();

        $results = $this->repository->findSuccessfulInteractions(0.8, 10);

        $this->assertCount(2, $results);
        $this->assertEquals($interaction1->getId(), $results[0]->getId()); // Ordered by successScore DESC
        $this->assertEquals($interaction3->getId(), $results[1]->getId());
    }

    public function testFindSimilarInteractions(): void
    {
        $contextKeys = ['task_type', 'technology', 'complexity'];
        $inputContext = [
            'task_type' => 'implementation',
            'technology' => 'php',
            'complexity' => 'moderate',
        ];

        $interaction = $this->createTestInteraction($this->testTask, 'implementation', 0.9, $inputContext);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        $results = $this->repository->findSimilarInteractions('implementation', $contextKeys, 0.7, 10);

        $this->assertCount(1, $results);
        $this->assertEquals('implementation', $results[0]->getAgentType());
    }

    public function testGetPerformanceMetrics(): void
    {
        $interaction1 = $this->createTestInteraction($this->testTask, 'implementation', 0.8, [], 1000);
        $interaction2 = $this->createTestInteraction($this->testTask, 'implementation', 0.9, [], 1500);
        $interaction3 = $this->createTestInteraction($this->testTask, 'testing', 0.7, [], 800);

        $this->entityManager->persist($interaction1);
        $this->entityManager->persist($interaction2);
        $this->entityManager->persist($interaction3);
        $this->entityManager->flush();

        $results = $this->repository->getPerformanceMetrics('30d');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        // Check that we have metrics for both agent types
        $agentTypes = array_column($results, 'agentType');
        $this->assertContains('implementation', $agentTypes);
        $this->assertContains('testing', $agentTypes);
    }

    public function testGetInteractionTrends(): void
    {
        $from = new \DateTime('-7 days');
        $to = new \DateTime('now');

        $interaction = $this->createTestInteraction($this->testTask, 'implementation', 0.8);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        $results = $this->repository->getInteractionTrends($from, $to);

        $this->assertIsArray($results);
        // Should have at least one entry for today
        $this->assertNotEmpty($results);
    }

    private function createTestInteraction(Task $task, string $agentType, float $successScore, array $inputContext = [], int $executionTime = 1000): AgentInteraction
    {
        $interaction = new AgentInteraction();
        $interaction->setTask($task);
        $interaction->setAgentType($agentType);
        $interaction->setSuccessScore($successScore);
        $interaction->setExecutionTimeMs($executionTime);
        $interaction->setInputContext($inputContext ?: ['test' => 'context']);
        $interaction->setExecutionSteps([['step' => 'test_step']]);
        $interaction->setOutputResult(['result' => 'success']);
        $interaction->setPatternHash(md5($agentType.$successScore));

        return $interaction;
    }
}
