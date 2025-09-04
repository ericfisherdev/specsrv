<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'root_landing')]
    public function landing(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'active',
            'message' => 'SpecsRV API is running',
            'user' => $this->getUser() ? [
                'id' => $this->getUser()->getId(),
                'email' => $this->getUser()->getEmail()
            ] : null
        ]);
    }
}
