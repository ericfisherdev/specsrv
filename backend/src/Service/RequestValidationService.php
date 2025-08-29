<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationService
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validateData(array $data, array $constraints): array
    {
        $violations = $this->validator->validate($data, new Assert\Collection($constraints));

        if (count($violations) > 0) {
            return $this->formatValidationErrors($violations);
        }

        return [];
    }

    public function createValidationErrorResponse(array $errors): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Validation failed',
            'details' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    public function formatValidationErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = trim($violation->getPropertyPath(), '[]');
            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }

    public function getProjectValidationConstraints(): array
    {
        return [
            'title' => [
                new Assert\NotBlank(['message' => 'Title is required']),
                new Assert\Length([
                    'max' => 255,
                    'maxMessage' => 'Title cannot be longer than {{ limit }} characters',
                ]),
            ],
            'description' => [
                new Assert\Length([
                    'max' => 2000,
                    'maxMessage' => 'Description cannot be longer than {{ limit }} characters',
                ]),
            ],
            'github_repo' => [
                new Assert\Url(['message' => 'GitHub repository must be a valid URL']),
            ],
        ];
    }

    public function getTaskValidationConstraints(): array
    {
        return [
            'title' => [
                new Assert\NotBlank(['message' => 'Title is required']),
                new Assert\Length([
                    'max' => 255,
                    'maxMessage' => 'Title cannot be longer than {{ limit }} characters',
                ]),
            ],
            'description' => [
                new Assert\Length([
                    'max' => 5000,
                    'maxMessage' => 'Description cannot be longer than {{ limit }} characters',
                ]),
            ],
            'status' => [
                new Assert\Choice([
                    'choices' => ['todo', 'in_progress', 'completed', 'on_hold'],
                    'message' => 'Status must be one of: todo, in_progress, completed, on_hold',
                ]),
            ],
            'project_id' => [
                new Assert\NotBlank(['message' => 'Project ID is required']),
                new Assert\Type([
                    'type' => 'integer',
                    'message' => 'Project ID must be an integer',
                ]),
            ],
        ];
    }

    public function getUserValidationConstraints(): array
    {
        return [
            'email' => [
                new Assert\NotBlank(['message' => 'Email is required']),
                new Assert\Email(['message' => 'Invalid email address']),
                new Assert\Length([
                    'max' => 180,
                    'maxMessage' => 'Email cannot be longer than {{ limit }} characters',
                ]),
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Password is required']),
                new Assert\Length([
                    'min' => 8,
                    'max' => 255,
                    'minMessage' => 'Password must be at least {{ limit }} characters long',
                    'maxMessage' => 'Password cannot be longer than {{ limit }} characters',
                ]),
                new Assert\Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                    'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one digit',
                ]),
            ],
        ];
    }

    public function getApiKeyValidationConstraints(): array
    {
        return [
            'name' => [
                new Assert\NotBlank(['message' => 'API key name is required']),
                new Assert\Length([
                    'max' => 255,
                    'maxMessage' => 'API key name cannot be longer than {{ limit }} characters',
                ]),
            ],
        ];
    }

    public function validatePaginationParams(array $params): array
    {
        $constraints = [
            'page' => [
                new Assert\Type([
                    'type' => 'integer',
                    'message' => 'Page must be an integer',
                ]),
                new Assert\Range([
                    'min' => 1,
                    'minMessage' => 'Page must be at least {{ limit }}',
                ]),
            ],
            'limit' => [
                new Assert\Type([
                    'type' => 'integer',
                    'message' => 'Limit must be an integer',
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 100,
                    'minMessage' => 'Limit must be at least {{ limit }}',
                    'maxMessage' => 'Limit cannot be more than {{ limit }}',
                ]),
            ],
        ];

        return $this->validateData($params, $constraints);
    }
}
