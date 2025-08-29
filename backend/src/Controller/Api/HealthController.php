<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // Database connectivity check
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            $checks['database'] = 'healthy';
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['database'] = 'unhealthy';
            $checks['database_error'] = $e->getMessage();
        }

        // File system availability check
        try {
            /** @var string $projectDir */
            $projectDir = $this->getParameter('kernel.project_dir');
            $uploadsDir = $projectDir.'/var/uploads';
            if (! is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }

            if (is_writable($uploadsDir)) {
                $checks['filesystem'] = 'healthy';
            } else {
                $status = 'unhealthy';
                $checks['filesystem'] = 'unhealthy';
                $checks['filesystem_error'] = 'Uploads directory is not writable';
            }
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['filesystem'] = 'unhealthy';
            $checks['filesystem_error'] = $e->getMessage();
        }

        $httpStatus = 'healthy' === $status ? 200 : 503;

        return $this->json([
            'status' => $status,
            'timestamp' => (new \DateTime())->format('c'),
            'checks' => $checks,
        ], $httpStatus);
    }

    #[Route('/ready', name: 'readiness_check', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $ready = true;
        $checks = [];

        // Database connectivity check
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            $checks['database'] = 'ready';
        } catch (\Exception $e) {
            $ready = false;
            $checks['database'] = 'not_ready';
            $checks['database_error'] = $e->getMessage();
        }

        // Check if application has finished startup
        try {
            /** @var string $projectDir */
            $projectDir = $this->getParameter('kernel.project_dir');
            $cacheDir = $projectDir.'/var/cache';
            if (is_dir($cacheDir) && is_readable($cacheDir)) {
                $checks['cache'] = 'ready';
            } else {
                $ready = false;
                $checks['cache'] = 'not_ready';
                $checks['cache_error'] = 'Cache directory not accessible';
            }
        } catch (\Exception $e) {
            $ready = false;
            $checks['cache'] = 'not_ready';
            $checks['cache_error'] = $e->getMessage();
        }

        $httpStatus = $ready ? 200 : 503;

        return $this->json([
            'ready' => $ready,
            'timestamp' => (new \DateTime())->format('c'),
            'checks' => $checks,
        ], $httpStatus);
    }
}
