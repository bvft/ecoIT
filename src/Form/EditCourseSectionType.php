<?php

namespace App\Form;

use App\Entity\Lessons;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EditCourseSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'][1];
        
        $builder
            ->add('title', null, [
                'data_class' => Lessons::class,
                'label' => 'Titre du cours',
                'mapped' => false
            ])
            ->add('content', CKEditorType::class, [
                'data_class' => Lessons::class,
                'label' => 'Contenu de la leçon',
                'mapped' => false,
                'config' => [
                    'filebrowserBrowseRouteParameters' => [
                        'homeFolder' => $user->getPersonDetails()->getPseudo()
                    ]
                ],
            ])
            ->add('send', SubmitType::class, ['label' => 'Modifier'])
        ;
    }
}