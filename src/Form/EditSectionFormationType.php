<?php

namespace App\Form;

use App\Entity\Sections;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EditSectionFormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'data_class' => Sections::class,
                'label' => 'Titre de la section',
                'mapped' => false
            ])
            ->add('send', SubmitType::class, ['label' => 'Modifier'])
        ;
    }
}