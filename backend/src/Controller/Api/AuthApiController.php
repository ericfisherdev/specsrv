<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
class AuthApiController extends BaseApiController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $this->getJsonPayload($request);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->errorResponse(
                'Email and password are required',
                'MISSING_CREDENTIALS',
                null,
                400
            );
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->errorResponse(
                'Invalid credentials',
                'INVALID_CREDENTIALS',
                null,
                401
            );
        }

        $token = $this->jwtManager->create($user);

        return $this->successResponse([
            'token' => $token,
            'user' => $this->transformEntity($user),
        ], 'Login successful');
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->errorResponse(
                'Authentication required',
                'AUTH_REQUIRED',
                null,
                401
            );
        }

        $token = $this->jwtManager->create($user);

        return $this->successResponse([
            'token' => $token,
            'user' => $this->transformEntity($user),
        ], 'Token refreshed successfully');
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // For JWT, logout is handled client-side by removing the token
        // Server-side blacklisting could be implemented here if needed
        return $this->successResponse(null, 'Logout successful');
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->getJsonPayload($request);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->errorResponse(
                'Email and password are required',
                'MISSING_CREDENTIALS',
                null,
                400
            );
        }

        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->errorResponse(
                'User with this email already exists',
                'USER_ALREADY_EXISTS',
                null,
                409
            );
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        // Validate the user entity
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $this->userRepository->save($user, true);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create user',
                'USER_CREATION_FAILED',
                null,
                500
            );
        }

        $token = $this->jwtManager->create($user);

        return $this->successResponse([
            'token' => $token,
            'user' => $this->transformEntity($user),
        ], 'User registered successfully', 201);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->errorResponse(
                'Authentication required',
                'AUTH_REQUIRED',
                null,
                401
            );
        }

        return $this->successResponse([
            'user' => $this->transformEntity($user),
        ]);
    }
}