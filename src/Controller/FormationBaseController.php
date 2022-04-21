<?php
namespace App\Controller;

use App\Repository\QuizRepository;
use App\Repository\LessonsRepository;
use App\Repository\SectionsRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FormationBaseController extends AbstractController
{
    protected array $sections = [];
    protected array $courses = [];
    protected array $quizs = [];
    protected array $lessons_status = [];
    protected array $quizs_status = [];
    protected array $count_lesson_and_quiz = [];

    public function __construct(protected SectionsRepository $s, protected LessonsRepository $l,
        protected QuizRepository $q, protected Security $security, protected ManagerRegistry $manager)
    {
        
    }

    /**
     * Obtient les sections, les cours, les quizs de la foramtion en cours
     *
     * @param \App\Entity\Formations $f
     * @return void
     */
    protected function setCurrentFormation(\App\Entity\Formations $f): void
    {
        $id = $f->getId();
        
        $this->formationSections($id);
        $this->lessonsSections($id);
        $this->quizsSections($id);
        $this->lessonsStatus($id);
        $this->quizsStatus($id);
    }

    /**
     * Obtient le statut des quizs pour les étudiants
     *
     * @param integer $id
     * @return void
     */
    private function quizsStatus(int $id)
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        
        if($user)
        {
            /** @var InstructorDetails $user */
            $user_id = $user->getPersonDetails()->getId();

            $quizs_status = $this->q->findQuizsStatusForStudent($id, $user_id);


            foreach($quizs_status as $k => $v)
            {
                $this->quizs_status[$k] = array_column($v,'id');
            }
        }
    }

    /**
     * Obtient le statut des leçons pour les étudiants
     *
     * @param integer $id
     * @return void
     */
    private function lessonsStatus(int $id)
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        
        if($user)
        {
            /** @var InstructorDetails $user */
            $user_id = $user->getPersonDetails()->getId();

            $lessons_status = $this->l->findLessonsStatusForStudent($id, $user_id);
            
            if(!empty($lessons_status))
            {
                foreach($lessons_status as $k => $v)
                {
                    $this->lessons_status[$k] = array_column($v, 'status', 'id');
                }
            }
        }
    }

    /**
     * Obtient les sections de la foramtion en cours
     *
     * @param integer $id
     * @return void
     */
    private function formationSections(int $id): void
    {
        $this->sections = $this->s->findSectionsByFormationForStudent($id);
    }

    /**
     * Obtient les leçons de section pour la formation en cours
     *
     * @param integer $id
     * @return void
     */
    private function lessonsSections(int $id): void
    {
        $this->courses = $this->l->findLessonsBySectionsForStudent($id);
    }

    /**
     * Obtient les quizs de section pour la formation en cours
     *
     * @param integer $id
     * @return void
     */
    private function quizsSections(int $id): void
    {
        $this->quizs = $this->q->findQuizsBySectionsForStudent($id);
    }
}