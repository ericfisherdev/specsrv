<?php

namespace App\Tests;

use App\Entity\ApiKey;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean up database before each test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM knowledge_patterns');
        $connection->executeStatement('DELETE FROM agent_interactions');
        $connection->executeStatement('DELETE FROM git_links');
        $connection->executeStatement('DELETE FROM files');
        $connection->executeStatement('DELETE FROM tasks');
        $connection->executeStatement('DELETE FROM projects');
        $connection->executeStatement('DELETE FROM api_keys');
        $connection->executeStatement('DELETE FROM users');
    }

    protected function createTestUser(array $data = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? 'test@example.com');

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        // Hash the password using the password hasher service
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password'] ?? 'password123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createTestApiKey(User $user, array $data = []): ApiKey
    {
        $apiKey = new ApiKey();
        $apiKey->setUser($user);
        $apiKey->setKeyHash($data['keyHash'] ?? 'test-key-hash');
        $apiKey->setName($data['name'] ?? 'Test API Key');

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();

        return $apiKey;
    }

    protected function makeAuthenticatedRequest(string $method, string $uri, string $apiKey, array $data = []): void
    {
        $headers = ['HTTP_X-API-Key' => $apiKey];
        $content = ! empty($data) ? json_encode($data) : null;

        if ($content) {
            $headers['CONTENT_TYPE'] = 'application/json';
        }

        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    protected function assertJsonResponse(int $expectedStatusCode = 200): array
    {
        $response = $this->client->getResponse();

        $this->assertEquals(
            $expectedStatusCode,
            $response->getStatusCode(),
            sprintf(
                'Expected status code %d, got %d. Response: %s',
                $expectedStatusCode,
                $response->getStatusCode(),
                $response->getContent()
            )
        );

        $this->assertJson($response->getContent());

        return json_decode($response->getContent(), true);
    }

    protected function getAuthenticatedClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function getUser(KernelBrowser $client): User
    {
        // Return the first user from the database, or create one if none exists
        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();

        if (empty($users)) {
            return $this->createTestUser();
        }

        return $users[0];
    }

    protected function createTestProject(User $user, array $data = []): Project
    {
        $project = new Project();
        $project->setTitle($data['title'] ?? 'Test Project');
        $project->setDescription($data['description'] ?? 'Test project description');
        $project->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function createTestTask(Project $project, array $data = []): Task
    {
        $task = new Task();
        $task->setTitle($data['title'] ?? 'Test Task');
        $task->setDescription($data['description'] ?? 'Test task description');
        $task->setStatus($data['status'] ?? TaskStatusEnum::TODO);
        $task->setPriority($data['priority'] ?? 'medium');
        $task->setProject($project);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    protected function makeRequest(string $method, string $uri, array $data = []): void
    {
        // Get or create a test user and API key for authentication
        $user = $this->getTestUser();
        $apiKey = $this->getOrCreateTestApiKey($user);

        $headers = ['HTTP_X-API-Key' => $apiKey];
        $content = ! empty($data) ? json_encode($data) : null;

        if ($content) {
            $headers['CONTENT_TYPE'] = 'application/json';
        }

        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    private function getTestUser(): User
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();

        if (empty($users)) {
            return $this->createTestUser(['email' => 'test@example.com']);
        }

        return $users[0];
    }

    private function getOrCreateTestApiKey(User $user): string
    {
        $apiKeyRepo = $this->entityManager->getRepository(ApiKey::class);
        $apiKeyEntity = $apiKeyRepo->findOneBy(['user' => $user]);

        if (! $apiKeyEntity) {
            $apiKey = 'test-api-key-'.uniqid();
            $this->createTestApiKey($user, ['keyHash' => hash('sha256', $apiKey)]);

            return $apiKey;
        }

        // For existing API keys, we need to use a consistent key that matches the hash
        // Since we can't reverse the hash, let's use a well-known test key
        $knownTestKey = 'test-api-key-known';

        // Update the existing API key entity to use our known test key hash
        $apiKeyEntity->setKeyHash(hash('sha256', $knownTestKey));
        $this->entityManager->flush();

        return $knownTestKey;
    }
}
