<?php

namespace App\Controller\Api;

use App\Controller\Trait\ValidationTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseApiController extends AbstractController
{
    use ValidationTrait;
    /**
     * Create a successful API response.
     */
    protected function successResponse($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if (null !== $data) {
            $response['data'] = $data;
        }

        if (null !== $message) {
            $response['message'] = $message;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Create an error API response.
     */
    protected function errorResponse(
        string $message,
        string $code = 'GENERIC_ERROR',
        $details = null,
        int $status = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if (null !== $details) {
            $response['error']['details'] = $details;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Create a validation error response.
     */
    protected function validationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->errorResponse(
            'Validation failed',
            'VALIDATION_ERROR',
            $errors,
            422
        );
    }

    /**
     * Create a paginated response.
     */
    protected function paginatedResponse(
        array $items,
        int $currentPage,
        int $perPage,
        int $totalItems,
        ?string $message = null
    ): JsonResponse {
        $totalPages = (int) ceil($totalItems / $perPage);

        $data = [
            'items' => $items,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ],
        ];

        return $this->successResponse($data, $message);
    }

    /**
     * Get pagination parameters from request.
     */
    protected function getPaginationParams(Request $request): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('per_page', 20)));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /**
     * Get JSON payload from request.
     */
    protected function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        return $data ?? [];
    }

    /**
     * Check if user owns the resource.
     */
    protected function checkResourceOwnership($resource): bool
    {
        $user = $this->getUser();

        if (! $user) {
            return false;
        }

        // If resource has a user property
        if (method_exists($resource, 'getUser')) {
            return $resource->getUser() === $user;
        }

        // If resource has a project property (like tasks)
        if (method_exists($resource, 'getProject')) {
            $project = $resource->getProject();

            return $project && $project->getUser() === $user;
        }

        return false;
    }

    /**
     * Require user authentication.
     */
    protected function requireAuth(): void
    {
        if (! $this->getUser()) {
            throw $this->createAccessDeniedException('Authentication required');
        }
    }

    /**
     * Transform entity to array for API response.
     */
    protected function transformEntity($entity): array
    {
        if (method_exists($entity, 'toArray')) {
            return $entity->toArray();
        }

        // Basic transformation
        $reflection = new \ReflectionClass($entity);
        $data = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (0 === strpos($methodName, 'get') && 0 === $method->getNumberOfParameters()) {
                $property = lcfirst(substr($methodName, 3));
                $value = $method->invoke($entity);

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('c'); // ISO 8601 format
                }

                $data[$property] = $value;
            }
        }

        return $data;
    }
}
