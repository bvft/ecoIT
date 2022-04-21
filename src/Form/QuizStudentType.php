<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class QuizStudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if($options['data'][0])
        {
            $student_answers = [];
            $data_student = '';

            if($options['data'][2])
            {
                $student_quiz = $options['data'][2];

                $student_answers = $student_quiz->getAnswers();
            }
            
            // Id de la section en cours
            $section_id = $options['data'][0]->getSections()->getId();

            /** @var \App\Repository\QuizRepository $rep_quizs */
            $rep_quizs = $options['data'][1];

            $question = $rep_quizs->findBy(['sections' => $section_id]);
            
            if($question)
            {
                foreach($question as $k => $v)
                {
                    // On supprime le dernier caractère, le ";"
                    $answers = mb_substr($v->getAnswers()[0], 0, -1);
                    // Le fait de supprimer le dernier caractère, evite d'avoir un tableau vide
                    $q = explode(';', $answers);
                    
                    $list_choice = [];

                    foreach($q as $i => $j)
                    {
                        $a = explode(':', $j);

                        // Ajoute une liste de choix, avec le format conseillé dans la doc de Symfony
                        $list_choice[$a[0]] = $a[1];
                    }
                    
                    if(array_key_exists('quiz_' . $k, $student_answers))
                    {
                        $data_student = $student_answers['quiz_' . $k];
                    }

                    $builder->add('quiz_' . $k, ChoiceType::class, [
                        'choices' => $list_choice,
                        'label' => $v->getQuestion(),
                        'expanded' => true,
                        'data' => $data_student
                    ]);
                }

                $builder->add('send_quiz', SubmitType::class, [
                    'label' => 'Valider mes réponses',
                ]);
            }
        }
    }
}