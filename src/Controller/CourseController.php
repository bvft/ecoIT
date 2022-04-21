<?php
namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Lessons;
use App\Entity\Sections;
use App\Entity\Formations;
use App\Entity\StudentFormationStatus;
use App\Form\QuizStudentType;
use App\Entity\StudentQuizStatus;
use App\Repository\QuizRepository;
use App\Entity\StudentLessonStatus;
use App\Repository\LessonsRepository;
use Symfony\Component\Form\FormError;
use App\Repository\SectionsRepository;
use App\Form\UpdateLessonStatusStudentType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UpdateFormationStatusStudentType;
use App\Repository\StudentFormationStatusRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\StudentQuizStatusRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\StudentLessonStatusRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class CourseController extends FormationBaseController
{
    #[
        Route("/formation-c/{f_N}/{s_N}/{id}", methods: ["GET", "POST"], name: "formation_courses"),
        ParamConverter('f', options: ['mapping' => ['f_N' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['s_N' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function course(Formations $f, Sections $s, Lessons $l, LessonsRepository $lessons,
        QuizRepository $quizs, SectionsRepository $sections, StudentLessonStatusRepository $lesson_status,
        Request $request, StudentQuizStatusRepository $quiz_status, StudentFormationStatusRepository $formation_status): Response
    {
        $this->setCurrentFormation($f);

        $next_c = null;
        $next_q = null;
        $prev_c = null;
        $prev_q = null;
        $prev_r = null;
        $next_r = null;
        $count_lq = null;
        $count_lsqs = null;
        $fs = null;
        $contains_lesson = null;

        $section_ramk_max = $sections->findOneBy([
            'formations' => $f->getId()
        ], ['rank_order' => 'DESC']);

        $lesson_ramk_max = $lessons->findOneBy([
            'sections' => $section_ramk_max->getId()
        ], ['rank_order' => 'DESC']);

        // Si la session en cours = la dernière session disponible ET si la leçon en cours = la dernière leçon disponible
        if($section_ramk_max->getRankOrder() == $s->getRankOrder()
            && $lesson_ramk_max->getRankOrder() == $l->getRankOrder())
        {
            // On vérifie si il y a un quiz
            $quiz_available = $quizs->findOneBy([
                'sections' => $section_ramk_max->getId()
            ]);

            // Si c'est vide c'est quil n'y a pas de quiz
            if(empty($quiz_available))
            {
                $next_q = null;
            }

            $next_q = $quiz_available;
        }
        else
        {
             // On récupère la leçon suivante
            $next_c = $lessons->findOneBy([
                'rank_order' => $l->getRankOrder() + 1,
                'sections' => $s->getId()
            ]);

            // Si next est vide, on vérifie s'il y a un quiz dans la section en cours
            if(empty($next_c))
            {
                $next_q = $quizs->findOneBy(['sections' => $s->getId()]);
            }
        }

        // Pour le cours précédent soit c'est une leçon de la même section
        // Soit on change de section

        // Si la section est au rang 1 ET que la leçon est au rang 1, On ne va pas plus loin
        // car la page précédente est le sommaire
        if($s->getRankOrder() == 1 && $l->getRankOrder() == 1)
        {
            $prev_r = $f->getNumber();
        }
        else
        {
            // On vérifie le rang du cours
            // Si on est au rang 1, on change de section
            if($l->getRankOrder() == 1)
            {
                $prev_r = $this->recursivePrev(
                    $sections,
                    $quizs,
                    $lessons,
                    $f->getId(),
                    $s->getRankOrder() - 1
                );

               if($prev_r instanceof \App\Entity\Quiz)
               {
                   $prev_q = $prev_r;
                   $prev_r = null;
               }
            }
            // Sinon il reste des cours dans la même section
            else
            {
                $prev_c = $lessons->findOneBy([
                    'rank_order' => $l->getRankOrder() - 1,
                    'sections' => $s->getId()
                ]);
            }
        }

        /** @var Security $user */
        $user = $this->security->getUser();
        
        if($user)
        {
            // Si il n'a pas le role d'étudiant
            if(!in_array('ROLE_STUDENT', $user->getRoles()))
            {
                goto end_1;
            }

            // Dès que l'on arrive sur la page du cours, on passe le statut de la leçon
            // au statut 1 (en cours de lecture)
            $contains_lesson = $lesson_status->findBy([
                'person_details' => $user->getPersonDetails()->getId(),
                'lessons' => $l->getId()
            ]);
        
            if(empty($contains_lesson))
            {
                $slt = new StudentLessonStatus();
                $slt->setPersonDetails($user->getPersonDetails());
                $slt->setLessons($l);
                $slt->setStatus(1);

                $em = $this->manager->getManager();

                $em->persist($slt);

                $em->flush();
            }

            $fs = $formation_status->findOneBy([
                'person_details' => $user->getPersonDetails()->getId(),
                'formations' => $f->getId()
            ]);

            if($fs === null)
            {
                $formation_status_student = new StudentFormationStatus();
                $formation_status_student->setPersonDetails($user->getPersonDetails());
                $formation_status_student->setFormations($f);
                $formation_status_student->setStatus(1);

                $em = $this->manager->getManager();

                $em->persist($formation_status_student);

                $em->flush();
            }

            $count_ls = $lesson_status->countCompletedLessonsByUser($f->getId(), $user->getPersonDetails()->getId());
            $count_l = $lessons->findAllLessons($f->getId());
            $count_q = $quizs->findAllQuizs($f->getId());
            $count_qs = $quiz_status->countCompletedQuizsByUser($f->getId(), $user->getPersonDetails()->getId());

            $count_lq = $count_l + $count_q;
            $count_lsqs = $count_ls + $count_qs;
        }

        end_1:

        $form = $this->createForm(UpdateLessonStatusStudentType::class, [$contains_lesson]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $btn_name = $form->getClickedButton()->getName();

            if($btn_name === 'send_update' && $form->get('send_update')->isClicked())
            {
                $status = 2;
            }
            else if($btn_name === 'send_updatable' && $form->get('send_updatable')->isClicked())
            {
                $status = 1;
            }

            if($btn_name === 'send_updatable' || $btn_name === 'send_update')
            {
                $lesson_status->updateAnswers(
                    $status,
                    $user->getPersonDetails()->getId(),
                    $l->getId()
                );

                return $this->redirectToRoute('formation_courses', [
                    'f_N' => $f->getNumber(),
                    's_N' => $s->getNumber(),
                    'id' => $l->getId()
                ]);
            }
        }

        $form1 = $this->createForm(UpdateFormationStatusStudentType::class, null);

        $form1->handleRequest($request);

        if($form1->isSubmitted() && $form1->isValid())
        {
            $formation_status = new StudentFormationStatus();
            $formation_status->setPersonDetails($user->getPersonDetails());
            $formation_status->setFormations($f);
            $formation_status->setStatus(2);

            $em = $this->manager->getManager();

            $em->persist($formation_status);

            $em->flush();

            return $this->redirectToRoute('formation_courses', [
                'f_N' => $f->getNumber(),
                's_N' => $s->getNumber(),
                'id' => $l->getId()
            ]);
        }

        return $this->render('page/formation.html.twig', [
            'sections' => $this->sections,
            'courses' => $this->courses,
            'quizs' => $this->quizs,
            'lessons_status' => $this->lessons_status,
            'quizs_status' => $this->quizs_status,
            'summary' => false,
            'current_f' => $f,
            'current_course' => $l,
            'next_c' => $next_c,
            'next_q' => $next_q,
            'prev_c' => $prev_c,
            'prev_q' => $prev_q,
            'prev_r' => $prev_r,
            'next_r' => $next_r,
            'form' => $form->createView(),
            'count_lq' => $count_lq,
            'count_lsqs' => $count_lsqs,
            'form1' => $form1->createView(),
            'fs' => $fs
        ]);
    }

    #[
        Route("/formation-q/{f_N}/{s_N}/{id}", methods: ["GET"], name: "formation_quizs"),
        ParamConverter('f', options: ['mapping' => ['f_N' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['s_N' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function quiz(Formations $f, Sections $s, Quiz $q, LessonsRepository $lessons,
        QuizRepository $quizs, SectionsRepository $sections, StudentQuizStatusRepository $quiz_student): Response
    {
        $this->setCurrentFormation($f);

        $next_c = null;
        $next_q = null;
        $prev_c = null;
        $prev_q = null;
        $prev_r = null;
        $next_r = null;
        $student_quiz = null;
        $quiz_solution = [];

        // On récupère la leçon suivante
        // Si on est au quiz, puisqu'il y en a qu'un seul par section
        // le cours suivant correspond à la section suivante

        // On récupère la section suivante
        $next_r = $this->recursiveNext(
            $sections,
            $quizs,
            $lessons,
            $f->getId(),
            $s->getRankOrder() + 1
        );

        // Si on est au quiz, c'est qu'il y a forçément un cours dans le section
        $prev_c = $lessons->findOneBy([
            'sections' => $s->getId(),
        ], ['rank_order' => 'DESC']);

        /** @var Security $user */
        $user = $this->security->getUser();

        if($user)
        {
            // Si il n'a pas le role d'étudiant
            if(!in_array('ROLE_STUDENT', $user->getRoles()))
            {
                goto end_2;
            }

            // On récupère les réponses du quiz des étudiants de la section en cours
            $student_quiz = $quiz_student->findOneBy([
                'person_details' => $user->getPersonDetails()->getId(),
                'sections' => $s->getId()
            ]);

            $answers_available = $quizs->findBy(['sections' => $s]);

            $quiz_solution = [];

            foreach($answers_available as $k => $v)
            {
                // -1 car l'index commence à 0 et non 1
                $solution = $v->getSolution() - 1;
                $qst = explode(';', mb_substr($v->getAnswers()[0], 0, -1));
                $final_solution = mb_substr(mb_strstr($qst[$solution], ':'), 1);

                $key_q = 'quiz_' . $k;

                $quiz_solution[$key_q] = $final_solution;
            }
        }

        end_2:

        $form = $this->createForm(QuizStudentType::class, [$q, $quizs, $student_quiz]);

        return $this->render('page/formation.html.twig', [
            'sections' => $this->sections,
            'courses' => $this->courses,
            'quizs' => $this->quizs,
            'lessons_status' => $this->lessons_status,
            'quizs_status' => $this->quizs_status,
            'summary' => false,
            'current_f' => $f,
            'current_quiz' => $q,
            'next_c' => $next_c,
            'next_q' => $next_q,
            'prev_c' => $prev_c,
            'prev_r' => $prev_r,
            'next_r' => $next_r,
            'prev_q' => $prev_q,
            'form' => $form->createView(),
            'quiz_student' => $student_quiz,
            'answers_available' => $quizs->findBy(['sections' => $s]),
            'quiz_solution' => $quiz_solution
        ]);
    }

    #[
        Route("/formation-q/{f_N}/{s_N}/{id}", methods: ["POST"], name: "formation_quizs_sent"),
        ParamConverter('f', options: ['mapping' => ['f_N' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['s_N' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function quizSent(Request $request, Formations $f, Sections $s, Quiz $q,
        LessonsRepository $lessons, QuizRepository $quizs, SectionsRepository $sections,
        ?StudentQuizStatus $qss, StudentQuizStatusRepository $quizs_student): Response
    {
        $this->setCurrentFormation($f);

        $next_c = null;
        $next_q = null;
        $prev_c = null;
        $prev_q = null;
        $prev_r = null;
        $next_r = null;

        // On récupère la leçon suivante
        // Si on est au quiz, puisqu'il y en a qu'un seul par section
        // le cours suivant correspond à la section suivante

        // On récupère la section suivante
        $next_r = $this->recursiveNext(
            $sections,
            $quizs,
            $lessons,
            $f->getId(),
            $s->getRankOrder() + 1
        );

        // Si on est au quiz, c'est qu'il y a forçément un cours dans le section
        $prev_c = $lessons->findOneBy([
            'sections' => $s->getId(),
        ], ['rank_order' => 'DESC']);



        $form = $this->createForm(QuizStudentType::class, [$q, $quizs, []]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $datas = $form->getData();

            // On récupère uniquement les clés du tableau qui contient "quiz_"
            $keys = preg_grep("/^quiz_[0-1]{1,2}$/", array_keys($datas));

            // Id de la section en cours
            $section_id = $datas[0]->getSections()->getId();

            /** @var \App\Repository\QuizRepository $rep_quizs */
            $rep_quizs = $datas[1];

            $question = $rep_quizs->findBy(['sections' => $section_id]);

            // On compare nos 2 tableaux, si il ne sont pas identiques, c'est qu'il manque des questions
            if(count($keys) != count($question))
            {
                $this->addFlash('error_intern', 'Une erreur est survenue lors de la validation du quiz.');

                return $this->redirectToRoute('formation_quizs', [
                    'f_N' => $f->getNumber(),
                    's_N' => $s->getNumber(),
                    'id' => $q->getId()
                ]);
            }

            // On garde uniquement le reste du tableau, les 3 premiers ne nous intéressent pas
            // ATTENTION: Il faut que le chiffre correspond au nbre d'élément passé dans le
            // tableau de $form = $this->createForm()
            // Ceci est essentiel pour éviter des bug/erreurs inattendue
            $datas_quizs = array_slice($datas, 3);

            $existing = false;

            foreach($datas_quizs as $k => $v)
            {
                if($v === null)
                {
                    $existing = true;
                    $form->get($k)->addError(new FormError('Ce champ est requis.'));
                }
            }

            if($existing)
            {
                goto end;
            }

            /** @var Security $user */
            $user = $this->security->getUser();
            
            // Si c'est null, On suppose qu'il n'a jamais répondu à ce quiz
            if($qss === null)
            {
                // On vérifie si vraiment il n'a jamais répondu à ce quiz
                // POURQUOI ? Car un quiz à plusieurs questions et donc plusieurs ID
                // de se fait, l'id du quiz dans les url est presque aléatoire

                // On récupère ce quiz dans la section correspondante
                $contains_quiz_student = $quizs_student->findOneBy([
                    'person_details' => $user->getPersonDetails()->getId(),
                    'sections' => $s->getId()
                ]);

                if($contains_quiz_student)
                {
                    // On met à jour
                    $quizs_student->updateAnswers(
                        $datas_quizs,
                        $user->getPersonDetails()->getId(),
                        $s->getId()
                    );
                }
                else
                {
                    // On enregistre ses valeurs
                    $qss = new StudentQuizStatus();
                    $qss->setPersonDetails($user->getPersonDetails());
                    $qss->setSections($s);
                    $qss->setAnswers($datas_quizs);
                    $qss->setStatus(1);

                    $em = $this->manager->getManager();

                    $em->persist($qss);

                    $em->flush();
                }
            }
            else
            {
                $this->addFlash('error_intern', 'Malheureusement une erreur est survenu. Impossible de valider vos réponses.');

                return $this->redirectToRoute('formation_quizs', [
                    'f_N' => $f->getNumber(),
                    's_N' => $s->getNumber(),
                    'id' => $q->getId()
                ]);
            }

            $this->addFlash('success', 'Ci-dessous les résultats de votre quiz.');

            return $this->redirectToRoute('formation_quizs', [
                'f_N' => $f->getNumber(),
                's_N' => $s->getNumber(),
                'id' => $q->getId()
            ]);
        }

        end:

        return $this->render('page/formation.html.twig', [
            'sections' => $this->sections,
            'courses' => $this->courses,
            'quizs' => $this->quizs,
            'lessons_status' => $this->lessons_status,
            'quizs_status' => $this->quizs_status,
            'summary' => false,
            'current_f' => $f,
            'current_quiz' => $q,
            'next_c' => $next_c,
            'next_q' => $next_q,
            'prev_c' => $prev_c,
            'prev_r' => $prev_r,
            'next_r' => $next_r,
            'prev_q' => $prev_q,
            'form' => $form->createView()
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

    private function recursiveNext($sections, $quizs, $lessons, $id_f, $rank)
    {
        // On récupère la section suivante
        $next_s = $sections->findOneBy([
            'formations' => $id_f,
            'rank_order' => $rank
        ]);

        if(!empty($next_s))
        {
            // On vérifie si cette section contient un cours
            $contains_lesson = $lessons->findOneBy([
                'sections' => $next_s->getId()
            ]);

            // Si c'est null, la section ne contient pas de cours, on passe à la suivante
            if($contains_lesson === null)
            {
                return $this->recursiveNext($sections, $quizs, $lessons, $id_f, $next_s->getRankOrder() + 1);
            }
            // Il y a un cours
            else
            {
                // Le cours de cette section est le rang 1
                return $lessons->findOneBy([
                    'rank_order' => 1,
                    'sections' => $next_s->getId()
                ]);
            }
        }
        else
        {
            return null;
        }
    }

    private function recursivePrev($sections, $quizs, $lessons, $id_f, $rank)
    {
        // On récupère la section précédente
        $prev_s = $sections->findOneBy([
            'formations' => $id_f,
            'rank_order' => $rank
        ]);

        if(!empty($prev_s))
        {
            // On vérifie si cette section contient un cours
            // Puisque pour avoir un quiz, il faut necessairement un cours, il est inutile à ce stade de vérifier
            // si la section précédente contient un quiz
            $contains_lesson = $lessons->findOneBy([
                'sections' => $prev_s->getId()
            ]);
    
            // Si c'est null, c'est que la section précédente ne contient pas de leçon
            if($contains_lesson === null)
            {
                // On recule encore d'une section
    
                // Si le rang est 1, il y aura pas plus bas, donc la page précédente vaut le sommaire
                if($prev_s->getRankOrder() == 1)
                {
                    return $prev_s->getFormations()->getNumber();
                }
                else
                {
                    return $this->recursivePrev($sections, $quizs, $lessons, $id_f, $prev_s->getRankOrder() - 1);
                }
            }
            else
            {
                // On vérifie si dans la section précédente il y a un quiz
                $prev_q = $quizs->findOneBy([
                    'sections' => $prev_s->getId()
                ]);
    
                // Si c'est null, c'est qu'il n'y a pas de quiz, donc c'est un cours
                if($prev_q === null)
                {
                    return $lessons->findOneBy([
                        'sections' => $prev_s->getId()
                    ], ['rank_order' => 'DESC']);
                }

                return $prev_q;
            }
        }
        else
        {
            return null;
        }
    }
}