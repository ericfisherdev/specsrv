<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/health', name: 'app_health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkFilesystem(),
            'frontend_assets' => $this->checkFrontendAssets(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = array_reduce($checks, fn ($carry, $check) => $carry && $check['healthy'], true);
        $status = $allHealthy ? 200 : 503;

        return new JsonResponse([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'checks' => $checks,
            'version' => $this->getParameter('app.version') ?? '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
        ], $status);
    }

    #[Route('/health/readiness', name: 'app_readiness_check', methods: ['GET'])]
    public function readinessCheck(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'frontend_assets' => $this->checkFrontendAssets(),
        ];

        $ready = array_reduce($checks, fn ($carry, $check) => $carry && $check['healthy'], true);
        $status = $ready ? 200 : 503;

        return new JsonResponse([
            'status' => $ready ? 'ready' : 'not_ready',
            'timestamp' => date('c'),
            'checks' => $checks,
        ], $status);
    }

    #[Route('/health/liveness', name: 'app_liveness_check', methods: ['GET'])]
    public function livenessCheck(): JsonResponse
    {
        // Basic liveness check - just verify the application is responding
        return new JsonResponse([
            'status' => 'alive',
            'timestamp' => date('c'),
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            $connection->executeQuery('SELECT 1');

            return [
                'healthy' => true,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkFilesystem(): array
    {
        try {
            $uploadDir = $this->getParameter('uploads_directory');

            if (! is_string($uploadDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Upload directory parameter is not properly configured',
                ];
            }

            if (! is_dir($uploadDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Upload directory does not exist',
                ];
            }

            if (! is_writable($uploadDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Upload directory is not writable',
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Filesystem checks passed',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Filesystem check failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkFrontendAssets(): array
    {
        try {
            $projectDir = $this->getParameter('kernel.project_dir');
            if (! is_string($projectDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Project directory parameter is not properly configured',
                ];
            }

            $publicDir = $projectDir.'/public';
            $assetDirs = [
                'build' => $publicDir.'/build',
                'assets' => $publicDir.'/assets',
            ];

            $missingAssets = [];
            $totalAssets = 0;

            foreach ($assetDirs as $type => $dir) {
                if (is_dir($dir)) {
                    try {
                        $assetCount = $this->countFilesRecursively($dir);
                        $totalAssets += $assetCount;
                    } catch (\Exception $e) {
                        // If directory is unreadable, treat it as having 0 files
                        $totalAssets += 0;
                    }
                } else {
                    $missingAssets[] = $type;
                }
            }

            // Check for critical frontend files
            $criticalFiles = [
                $publicDir.'/build/app.css',
                $publicDir.'/build/app.js',
            ];

            $missingCritical = [];
            foreach ($criticalFiles as $file) {
                if (! file_exists($file)) {
                    $missingCritical[] = basename($file);
                }
            }

            if (! empty($missingCritical)) {
                return [
                    'healthy' => false,
                    'message' => 'Missing critical frontend assets: '.implode(', ', $missingCritical),
                    'details' => [
                        'missing_assets' => $missingAssets,
                        'missing_critical' => $missingCritical,
                        'total_assets' => $totalAssets,
                    ],
                ];
            }

            if (! empty($missingAssets)) {
                return [
                    'healthy' => false,
                    'message' => 'Missing asset directories: '.implode(', ', $missingAssets),
                    'details' => [
                        'missing_assets' => $missingAssets,
                        'total_assets' => $totalAssets,
                    ],
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Frontend assets are available',
                'details' => [
                    'total_assets' => $totalAssets,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Frontend asset check failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $cacheDir = $this->getParameter('kernel.cache_dir');

            if (! is_string($cacheDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Cache directory parameter is not properly configured',
                ];
            }

            if (! is_dir($cacheDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Cache directory does not exist',
                ];
            }

            if (! is_writable($cacheDir)) {
                return [
                    'healthy' => false,
                    'message' => 'Cache directory is not writable',
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Cache directory accessible',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Cache check failed: '.$e->getMessage(),
            ];
        }
    }

    private function countFilesRecursively(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $count = 0;
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    // Optionally skip dotfiles
                    if (! str_starts_with($fileInfo->getFilename(), '.')) {
                        ++$count;
                    }
                }
            }

            return $count;
        } catch (\Exception $e) {
            // Return 0 for unreadable directories or other errors
            return 0;
        }
    }
}
