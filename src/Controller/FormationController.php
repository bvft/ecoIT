<?php
namespace App\Controller;

use App\Entity\Formations;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class FormationController extends FormationBaseController
{
    #[
        Route("/formation/{f_N}", methods: ["GET"], name: "formation"),
        ParamConverter('f', options: ['mapping' => ['f_N' => 'number']])
    ]
    /**
     * @return Response
     */
    public function index(Formations $f): Response
    {
        $this->setCurrentFormation($f);
        
        return $this->render('page/formation.html.twig', [
            'sections' => $this->sections,
            'courses' => $this->courses,
            'quizs' => $this->quizs,
            'lessons_status' => $this->lessons_status,
            'summary' => true,
            'current_f' => $f
        ]);
    }

    /**
     * Initialise la formation en cours
     *
     * @param \App\Entity\Formations $f
     * @return void
     */
    protected function setCurrentFormation(\App\Entity\Formations $f): void
    {
        parent::setCurrentFormation($f);
    }
}