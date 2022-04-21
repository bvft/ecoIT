<?php

namespace App\Form;

use App\Entity\Sections;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NewSectionFormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'data_class' => Sections::class,
                'label' => 'Titre de la section'
            ])
            ->add('send', SubmitType::class, ['label' => 'Créer'])
        ;
    }
}