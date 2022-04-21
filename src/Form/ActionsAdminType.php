<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ActionsAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('waiting', SubmitType::class, [
                'label' => 'En attente',
                'attr' => ['class' => 'btn-secondary']
            ])
            ->add('validate', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'btn-success']
            ])
            ->add('refuse', SubmitType::class, [
                'label' => 'Refuser',
                'attr' => ['class' => 'btn-danger']
            ])
        ;
    }
}