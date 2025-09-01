<?php

// backend/tests/Entity/AgentInteractionTest.php

namespace App\Tests\Entity;

use App\Entity\AgentInteraction;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

class AgentInteractionTest extends TestCase
{
    public function testConstructor(): void
    {
        $interaction = new AgentInteraction();

        $this->assertInstanceOf(\DateTime::class, $interaction->getCreatedAt());
        $this->assertEquals([], $interaction->getInputContext());
        $this->assertEquals([], $interaction->getExecutionSteps());
        $this->assertEquals([], $interaction->getOutputResult());
    }

    public function testSettersAndGetters(): void
    {
        $interaction = new AgentInteraction();
        $task = $this->createMock(Task::class);
        $createdAt = new \DateTime();
        $inputContext = ['key' => 'value'];
        $executionSteps = [['step' => 1]];
        $outputResult = ['result' => 'success'];
        $errorLog = ['error' => 'test'];

        $interaction->setTask($task);
        $interaction->setAgentType('implementation');
        $interaction->setInputContext($inputContext);
        $interaction->setExecutionSteps($executionSteps);
        $interaction->setOutputResult($outputResult);
        $interaction->setSuccessScore(0.85);
        $interaction->setPatternHash('hash123');
        $interaction->setErrorLog($errorLog);
        $interaction->setExecutionTimeMs(1500);
        $interaction->setCreatedAt($createdAt);

        $this->assertSame($task, $interaction->getTask());
        $this->assertEquals('implementation', $interaction->getAgentType());
        $this->assertEquals($inputContext, $interaction->getInputContext());
        $this->assertEquals($executionSteps, $interaction->getExecutionSteps());
        $this->assertEquals($outputResult, $interaction->getOutputResult());
        $this->assertEquals(0.85, $interaction->getSuccessScore());
        $this->assertEquals('hash123', $interaction->getPatternHash());
        $this->assertEquals($errorLog, $interaction->getErrorLog());
        $this->assertEquals(1500, $interaction->getExecutionTimeMs());
        $this->assertEquals($createdAt, $interaction->getCreatedAt());
    }

    public function testOnPrePersist(): void
    {
        $interaction = new AgentInteraction();

        // Test that onPrePersist sets createdAt when it's null (constructor already sets it)
        // We need to use reflection to test the lifecycle callback properly
        $reflection = new \ReflectionClass($interaction);
        $property = $reflection->getProperty('createdAt');
        $property->setAccessible(true);
        $property->setValue($interaction, null);

        $interaction->onPrePersist();

        $this->assertInstanceOf(\DateTime::class, $interaction->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $interaction = new AgentInteraction();

        $result = $interaction
            ->setAgentType('test')
            ->setSuccessScore(0.8)
            ->setExecutionTimeMs(1000);

        $this->assertSame($interaction, $result);
    }
}
