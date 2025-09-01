<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    // DISABLED for frontend migration: HTML-returning method
    // Frontend will use API endpoints instead
    // #[Route('/', name: 'app_dashboard')]
    // public function dashboard(): Response
    // {
    //     // Check if user is authenticated
    //     if (! $this->getUser()) {
    //         return $this->redirectToRoute('app_login');
    //     }
    //
    //     return $this->render('dashboard/index.html.twig', [
    //         'user' => $this->getUser(),
    //     ]);
    // }
}
