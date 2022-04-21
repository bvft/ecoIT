<?php
namespace App\Controller;

use App\Repository\FormationsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route("/", methods: ["GET"], name: "home")]
    /**
     * @return Response
     */
    public function index(FormationsRepository $formations): Response
    {
        return $this->render('page/index.html.twig', [
            'formations' => $formations->findLastFormationsByPublicationDate()
        ]);
    }
}