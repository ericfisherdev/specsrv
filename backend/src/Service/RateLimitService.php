<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

class RateLimitService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $apiRateLimitRequests = '1000',
        private readonly string $apiRateLimitWindow = '3600'
    ) {
    }

    public function isAllowed(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): bool
    {
        $cacheKey = $this->getCacheKey($identifier);
        $currentTime = time();

        // Get existing requests data
        $requests = $this->cache->get($cacheKey, function () {
            return [];
        });

        // Clean up old requests outside the window
        $requests = array_filter($requests, function ($timestamp) use ($currentTime, $windowSeconds) {
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
        $this->cache->get($cacheKey, function () use ($requests) {
            return $requests;
        }, $windowSeconds);

        return true;
    }

    public function getRemainingRequests(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): int
    {
        $cacheKey = $this->getCacheKey($identifier);
        $currentTime = time();

        $requests = $this->cache->get($cacheKey, function () {
            return [];
        });

        // Clean up old requests outside the window
        $requests = array_filter($requests, function ($timestamp) use ($currentTime, $windowSeconds) {
            return ($currentTime - $timestamp) < $windowSeconds;
        });

        return max(0, $maxRequests - count($requests));
    }

    public function getResetTime(string $identifier, int $windowSeconds = 3600): ?int
    {
        $cacheKey = $this->getCacheKey($identifier);

        /** @var array<int> $requests */
        $requests = $this->cache->get($cacheKey, function () {
            return [];
        });

        if (0 === count($requests)) {
            return null;
        }

        $oldestRequest = min($requests);

        return $oldestRequest + $windowSeconds;
    }

    public function generateIdentifierFromRequest(Request $request): string
    {
        $user = $request->attributes->get('user');

        if ($user && is_object($user) && method_exists($user, 'getId')) {
            return 'user_'.$user->getId();
        }

        // Fall back to IP address
        return 'ip_'.$request->getClientIp();
    }

    public function generateApiKeyIdentifier(string $apiKeyHash): string
    {
        return 'api_key_'.substr($apiKeyHash, 0, 16);
    }

    private function getCacheKey(string $identifier): string
    {
        return 'rate_limit_'.$identifier;
    }

    public function getDefaultLimits(): array
    {
        $baseRequests = (int) $this->apiRateLimitRequests;
        $baseWindow = (int) $this->apiRateLimitWindow;
        
        return [
            'api' => [
                'requests' => $baseRequests,
                'window' => $baseWindow,
            ],
            'auth' => [
                'requests' => max(50, (int) ($baseRequests * 0.1)), // At least 50 or 10% of base limit
                'window' => min(300, (int) ($baseWindow * 0.1)), // At most 5 minutes or 10% of base window
            ],
            'upload' => [
                'requests' => max(20, (int) ($baseRequests * 0.05)), // At least 20 or 5% of base limit
                'window' => $baseWindow,
            ],
        ];
    }
}
