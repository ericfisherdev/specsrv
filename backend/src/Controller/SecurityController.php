<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('root_landing');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // DISABLED for frontend migration: HTML-returning method
        // Frontend will use API endpoints instead
        throw $this->createNotFoundException('HTML view disabled. Use API endpoints instead.');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('root_landing');
        }

        // DISABLED for frontend migration: HTML-returning method
        // Frontend will use API endpoints instead
        throw $this->createNotFoundException('HTML view disabled. Use API endpoints instead.');
    }

    #[Route(path: '/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // DISABLED for frontend migration: HTML-returning method
        // Frontend will use API endpoints instead
        throw $this->createNotFoundException('HTML view disabled. Use API endpoints instead.');
    }

    #[Route(path: '/api/v1/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(): Response
    {
        $user = $this->getUser();

        if (! $user) {
            return $this->json([
                'message' => 'Authentication required',
            ], 401);
        }

        assert($user instanceof \App\Entity\User);

        return $this->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route(path: '/api/v1/user', name: 'api_user', methods: ['GET'])]
    public function getCurrentUser(): Response
    {
        $user = $this->getUser();

        if (! $user) {
            return $this->json([
                'message' => 'Not authenticated',
            ], 401);
        }

        assert($user instanceof \App\Entity\User);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route(path: '/api/v1/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
    public function getCsrfToken(CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $token = $csrfTokenManager->getToken('authenticate');

        return $this->json([
            'csrf_token' => $token->getValue(),
        ]);
    }
}
