<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use App\Service\ApiKeyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserApiController extends BaseApiController
{
    public function __construct(
        private UserRepository $userRepository,
        private ApiKeyRepository $apiKeyRepository,
        private ApiKeyService $apiKeyService,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        $user = $this->getUser();

        if (! $user) {
            return $this->errorResponse('Authentication failed', 'AUTH_FAILED', null, 401);
        }

        assert($user instanceof User);

        return $this->successResponse([
            'user' => $this->transformEntity($user),
            'message' => 'Login successful',
        ]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->successResponse(null, 'Logout successful');
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (! isset($data['email']) || ! isset($data['password'])) {
            return $this->errorResponse('email and password are required', 'MISSING_CREDENTIALS', null, 400);
        }

        if (strlen($data['password']) < 8) {
            return $this->errorResponse('Password must be at least 8 characters long', 'WEAK_PASSWORD', null, 400);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->errorResponse('Email already in use', 'EMAIL_EXISTS', null, 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->successResponse(
            [
                'user' => $this->transformEntity($user),
                'message' => 'User registered successfully',
            ],
            'User registered successfully',
            201
        );
    }

    #[Route('/api/user/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->successResponse($this->transformEntity($user));
    }

    #[Route('/api/user/profile', name: 'api_user_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        if (isset($data['email'])) {
            if ($data['email'] !== $user->getEmail()) {
                $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
                if ($existingUser && $existingUser !== $user) {
                    return $this->errorResponse('Email already in use', 'EMAIL_EXISTS', null, 409);
                }
                $user->setEmail($data['email']);
            }
        }

        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                return $this->errorResponse('Password must be at least 8 characters long', 'WEAK_PASSWORD', null, 400);
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $this->entityManager->flush();

        return $this->successResponse(
            $this->transformEntity($user),
            'Profile updated successfully'
        );
    }

    #[Route('/api/user/api-keys', name: 'api_user_api_keys_create', methods: ['POST'])]
    public function createApiKey(Request $request): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        try {
            $data = $this->getJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Invalid JSON payload', 'INVALID_JSON', null, 400);
        }

        $name = $data['name'] ?? 'API Key';

        try {
            $apiKey = $this->apiKeyService->generateApiKey($user, $name);

            return $this->successResponse([
                'api_key' => [
                    'id' => $apiKey->getId(),
                    'name' => $apiKey->getName(),
                    'key' => $apiKey->getKey(), // Only shown on creation
                    'created_at' => $apiKey->getCreatedAt()?->format('c'),
                    'expires_at' => $apiKey->getExpiresAt()?->format('c'),
                ],
            ], 'API key created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create API key: '.$e->getMessage(), 'API_KEY_CREATION_FAILED', null, 500);
        }
    }

    #[Route('/api/user/api-keys', name: 'api_user_api_keys_list', methods: ['GET'])]
    public function listApiKeys(): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $apiKeys = $this->apiKeyRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $apiKeysData = array_map(function ($apiKey) {
            return [
                'id' => $apiKey->getId(),
                'name' => $apiKey->getName(),
                'key_preview' => substr($apiKey->getKey(), 0, 8).'...',
                'created_at' => $apiKey->getCreatedAt()?->format('c'),
                'expires_at' => $apiKey->getExpiresAt()?->format('c'),
                'is_active' => $apiKey->isActive(),
            ];
        }, $apiKeys);

        return $this->successResponse([
            'api_keys' => $apiKeysData,
            'total' => count($apiKeysData),
        ]);
    }

    #[Route('/api/user/api-keys/{keyId}', name: 'api_user_api_keys_delete', methods: ['DELETE'])]
    public function revokeApiKey(int $keyId): JsonResponse
    {
        $this->requireAuth();
        $user = $this->getUser();
        assert($user instanceof User);

        $apiKey = $this->apiKeyRepository->find($keyId);

        if (! $apiKey || $apiKey->getUser() !== $user) {
            return $this->errorResponse('API key not found', 'API_KEY_NOT_FOUND', null, 404);
        }

        try {
            $this->apiKeyService->revokeApiKey($apiKey);

            return $this->successResponse(null, 'API key revoked successfully', 204);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke API key: '.$e->getMessage(), 'API_KEY_REVOKE_FAILED', null, 500);
        }
    }

    protected function transformEntity(object $entity): array
    {
        if ($entity instanceof User) {
            return [
                'id' => $entity->getId(),
                'email' => $entity->getEmail(),
                'roles' => $entity->getRoles(),
                'created_at' => $entity->getCreatedAt()?->format('c'),
                'updated_at' => $entity->getUpdatedAt()?->format('c'),
            ];
        }

        return parent::transformEntity($entity);
    }
}
