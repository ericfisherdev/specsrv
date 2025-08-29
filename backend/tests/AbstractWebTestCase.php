<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\ApiKey;

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
        $user->setPassword($data['password'] ?? 'password123');
        
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
        $headers = ['HTTP_X-API-KEY' => $apiKey];
        $content = !empty($data) ? json_encode($data) : null;
        
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
            sprintf('Expected status code %d, got %d. Response: %s', 
                $expectedStatusCode, 
                $response->getStatusCode(), 
                $response->getContent()
            )
        );
        
        $this->assertJson($response->getContent());
        
        return json_decode($response->getContent(), true);
    }
}