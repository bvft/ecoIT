<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LegalNoticeController extends AbstractController
{
    #[Route("/legal-notice", methods: ["GET"], name: "legal_notice")]
    /**
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('page/legal_notice.html.twig');
    }
}