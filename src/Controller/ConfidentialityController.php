<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConfidentialityController extends AbstractController
{
    #[Route("/confidentiality", methods: ["GET"], name: "confidentiality")]
    /**
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('page/confidentiality.html.twig');
    }
}