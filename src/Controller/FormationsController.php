<?php
namespace App\Controller;

use App\Entity\Formations;
use App\Repository\FormationsRepository;
use App\Repository\StudentFormationStatusRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FormationsController extends AbstractController
{
    public function __construct(private Security $security)
    {
        
    }

    #[Route("/formations", methods: ["GET"], name: "formations")]
    /**
     * @return Response
     */
    public function index(FormationsRepository $formations, StudentFormationStatusRepository $sfs): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        
        $user_formations = null;
        $total = null;
        $total_student = null;

        if($user)
        {
            if(!in_array('ROLE_STUDENT', $user->getRoles()))
            {
                goto end;
            }

            /** @var InstructorDetails $user */
            $user_id = $user->getPersonDetails()->getId();

            $user_formations = $sfs->findFormationsByUser($user_id);

            $count_l = $formations->countAllLessons();
            $count_q = $formations->countAllQuizs();
            $count_qs = $formations->countAllQuizsStatusByStudent($user_id);
            $count_ls = $formations->countAllLessonsStatusByStudent($user_id);

            $total = [];
            $total_student = [];

            foreach($count_l as $k => $v)
            {
                if(array_key_exists($k, $count_q))
                {
                    $total[$k] = $v + $count_q[$k];
                }
            }

            // Pour les étudiants, il peut avoir fait plus de quiz que de leçon
            // Si on lit réellement cela est faut, mais si on fait tout et n'importe quoi
            // cela est vrai

            // On compte le nbre d'élément dans chaque tableau
            $c_ls = count($count_ls);
            $c_qs = count($count_qs);

            // On définit de nouveau tableau
            if($c_ls > $c_qs)
            {
                $a_k = $count_ls;
                $b_k = $count_qs;
            }
            else
            {
                $a_k = $count_qs;
                $b_k = $count_ls;
            }
           
            foreach($a_k as $k => $v)
            {
                if(array_key_exists($k, $b_k))
                {
                    $total_student[$k] = $v + $b_k[$k];
                }
                else
                {
                    $total_student[$k] = $v;
                }
            }
        }

        end:

        return $this->render('page/formations.html.twig', [
            'formations' => $formations->findAllFormationsByRubrics(),
            'sfs' => $user_formations,
            'count_total' => $total,
            'count_total_student' => $total_student,
        ]);
    }
}