<?php

namespace App\Form;

use App\Entity\Sections;
use Symfony\Component\Form\FormEvent;
use App\Repository\SectionsRepository;
use Symfony\Component\Form\FormEvents;
use App\Repository\FormationsRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NewCourseType extends AbstractType
{
    public function __construct(private FormationsRepository $rep_f, private SectionsRepository $rep_s)
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formations', ChoiceType::class, [
                'choices' => $this->choiceList($options, 'f'),
                'label' => 'Choisissez une formation',
                'placeholder' => 'Choisissez une formation'
            ])
            ->add('send', SubmitType::class, ['label' => 'Validez la formation et choisir une section'])
        ;

        $builder->get('formations')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event){
            $form = $event->getForm();

            $form->getParent()->add('sections', ChoiceType::class, [
                'choices' => $this->choiceList([$form->getData()], 's'),
                'label' => 'Choisissez une section',
                'placeholder' => 'Choisissez une section',
            ])
            ->add('send', SubmitType::class, ['label' => 'Créer une leçon pour cette section de formation'])
            ->add('send_quiz', SubmitType::class, ['label' => 'Créer un quiz pour cette section de formation']);
            
            /*$form->getParent()->add('sections', EntityType::class, [
                'class' => Sections::class,
                'placeholder' => 'Choisissez une section',
                'query_builder' => function(SectionsRepository $rep) use($form) {
                    return $rep->findSectionsByFormation($form->getData());
                },
                'mapped' => false
            ]);*/
        });

    }

    /**
     * Retourne une liste de formations ou de sections
     *
     * @param array $options
     * @param string $type Soit f ou s
     * @return array
     */
    private function choiceList(array $options, string $type): array
    {
        if($type == 'f')
        {
            return $this->rep_f->findFormationsByUser($options);
        }
        else if($type == 's')
        {
            return $this->rep_s->findSectionsByFormation($options);
        }
    }
}