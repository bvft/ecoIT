<?php

namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EditQuizSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'data_class' => Quiz::class,
                'label' => 'Titre du quiz',
                'required' => false,
                'mapped' => false
            ])
            ->add('question', null, [
                'data_class' => Quiz::class,
                'label' => 'Votre question',
                'attr' => [
                    'placeholder' => 'Mon nom est romain, Quel est mon sexe ?'
                ],
                'required' => false,
                'mapped' => false
            ])
            ->add('answers', null, [
                'data_class' => Quiz::class,
                'label' => 'Vos réponses',
                'attr' => [
                    'placeholder' => 'Je suis une femme:femme;Je suis un homme:homme;Je suis un animal:animal;'
                ],
                'required' => false,
                'mapped' => false
            ])
            ->add('solution', NumberType::class, [
                'data_class' => Quiz::class,
                'label' => 'Votre solution',
                'attr' => [
                    'placeholder' => 'Correspond à la place où figure la bonne réponse, ici 2'
                ],
                'required' => false,
                'mapped' => false
            ])
            ->add('send', SubmitType::class, ['label' => 'Modifier'])
        ;
    }
}