<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;

class RateLimitService
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    public function isAllowed(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): bool
    {
        $cacheKey = $this->getCacheKey($identifier);
        $currentTime = time();
        
        // Get existing requests data
        $requests = $this->cache->get($cacheKey, function() {
            return [];
        });

        // Clean up old requests outside the window
        $requests = array_filter($requests, function($timestamp) use ($currentTime, $windowSeconds) {
            return ($currentTime - $timestamp) < $windowSeconds;
        });

        // Check if limit is exceeded
        if (count($requests) >= $maxRequests) {
            return false;
        }

        // Add current request
        $requests[] = $currentTime;
        
        // Store back to cache - delete and recreate with new data
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function() use ($requests) {
            return $requests;
        }, $windowSeconds);

        return true;
    }

    public function getRemainingRequests(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): int
    {
        $cacheKey = $this->getCacheKey($identifier);
        $currentTime = time();
        
        $requests = $this->cache->get($cacheKey, function() {
            return [];
        });

        // Clean up old requests outside the window
        $requests = array_filter($requests, function($timestamp) use ($currentTime, $windowSeconds) {
            return ($currentTime - $timestamp) < $windowSeconds;
        });

        return max(0, $maxRequests - count($requests));
    }

    public function getResetTime(string $identifier, int $windowSeconds = 3600): ?int
    {
        $cacheKey = $this->getCacheKey($identifier);
        
        $requests = $this->cache->get($cacheKey, function() {
            return [];
        });

        if (empty($requests)) {
            return null;
        }

        $oldestRequest = min($requests);
        return $oldestRequest + $windowSeconds;
    }

    public function generateIdentifierFromRequest(Request $request): string
    {
        $user = $request->attributes->get('user');
        
        if ($user && method_exists($user, 'getId')) {
            return 'user_' . $user->getId();
        }

        // Fall back to IP address
        return 'ip_' . $request->getClientIp();
    }

    public function generateApiKeyIdentifier(string $apiKeyHash): string
    {
        return 'api_key_' . substr($apiKeyHash, 0, 16);
    }

    private function getCacheKey(string $identifier): string
    {
        return 'rate_limit_' . $identifier;
    }

    public function getDefaultLimits(): array
    {
        return [
            'api' => [
                'requests' => 1000,
                'window' => 3600 // 1 hour
            ],
            'auth' => [
                'requests' => 5,
                'window' => 300 // 5 minutes
            ],
            'upload' => [
                'requests' => 50,
                'window' => 3600 // 1 hour
            ]
        ];
    }
}