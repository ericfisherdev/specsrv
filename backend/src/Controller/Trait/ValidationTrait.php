<?php

declare(strict_types=1);

namespace App\Controller\Trait;

use App\Service\RequestValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait ValidationTrait
{
    private function validateRequestData(
        Request $request, 
        array $constraints,
        RequestValidationService $validationService
    ): ?JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'error' => 'Invalid JSON payload'
            ], 400);
        }

        $errors = $validationService->validateData($data, $constraints);
        
        if (!empty($errors)) {
            return $validationService->createValidationErrorResponse($errors);
        }

        return null;
    }

    private function validateQueryParams(
        Request $request,
        array $constraints,
        RequestValidationService $validationService
    ): ?JsonResponse {
        $params = $request->query->all();
        
        // Convert string values to appropriate types
        if (isset($params['page'])) {
            $params['page'] = (int) $params['page'];
        }
        if (isset($params['limit'])) {
            $params['limit'] = (int) $params['limit'];
        }

        $errors = $validationService->validateData($params, $constraints);
        
        if (!empty($errors)) {
            return $validationService->createValidationErrorResponse($errors);
        }

        return null;
    }

    private function getRequestData(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }
}