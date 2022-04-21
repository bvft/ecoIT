<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TermsOfUseController extends AbstractController
{
    #[Route("/terms-of-use", methods: ["GET"], name: "terms_of_use")]
    /**
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('page/terms_of_use.html.twig');
    }
}