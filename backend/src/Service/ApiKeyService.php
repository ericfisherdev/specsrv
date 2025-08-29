<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyService
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function generateApiKey(User $user, string $name): array
    {
        $plainKey = $this->generateSecureKey();
        $keyHash = hash('sha256', $plainKey);

        $apiKey = new ApiKey();
        $apiKey
            ->setKeyHash($keyHash)
            ->setName($name)
            ->setUser($user);

        $this->apiKeyRepository->save($apiKey, true);

        return [
            'api_key' => $plainKey,
            'id' => $apiKey->getId(),
            'name' => $name,
            'created_at' => $apiKey->getCreatedAt()->format('c')
        ];
    }

    public function revokeApiKey(int $apiKeyId, User $user): bool
    {
        $apiKey = $this->entityManager->getRepository(ApiKey::class)->find($apiKeyId);

        if (!$apiKey || $apiKey->getUser() !== $user) {
            return false;
        }

        $apiKey->setIsActive(false);
        $this->apiKeyRepository->save($apiKey, true);

        return true;
    }

    public function getUserApiKeys(User $user): array
    {
        $apiKeys = $this->apiKeyRepository->findActiveByUser($user);

        return array_map(function (ApiKey $apiKey) {
            return [
                'id' => $apiKey->getId(),
                'name' => $apiKey->getName(),
                'created_at' => $apiKey->getCreatedAt()->format('c'),
                'last_used_at' => $apiKey->getLastUsedAt()?->format('c')
            ];
        }, $apiKeys);
    }

    private function generateSecureKey(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }
}