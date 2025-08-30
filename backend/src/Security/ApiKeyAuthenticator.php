<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\ApiKeyRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Only support this authenticator if API key is provided
        // If no API key is provided, fall back to session authentication
        return $request->headers->has('X-API-Key') || $request->query->has('api_key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-Key') ?? $request->query->get('api_key');

        if (null === $apiKey || ! is_string($apiKey)) {
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        $keyHash = hash('sha256', $apiKey);
        $apiKeyEntity = $this->apiKeyRepository->findActiveByKeyHash($keyHash);

        if (! $apiKeyEntity) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        // Update last used timestamp
        $apiKeyEntity->updateLastUsedAt();
        $this->apiKeyRepository->save($apiKeyEntity, true);

        $user = $apiKeyEntity->getUser();

        return new SelfValidatingPassport(
            new UserBadge($user->getEmail() ?? '')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
