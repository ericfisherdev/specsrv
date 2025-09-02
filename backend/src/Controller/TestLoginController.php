<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TestLoginController extends AbstractController
{
    #[Route('/test-login', name: 'test_login')]
    public function testLogin(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session
    ): Response {
        // Find the admin user
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'admin@specsrv.dev']);

        if (! $user) {
            return new Response('User not found', 404);
        }

        // Manually create authentication token
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);

        // Save token to session
        $session->set('_security_main', serialize($token));
        $session->save();

        // Redirect to frontend dashboard instead of rendering template
        return $this->redirect('http://localhost:3000/dashboard');
    }
}
