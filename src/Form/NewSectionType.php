<?php

namespace App\Form;

use App\Entity\Formations;
use App\Repository\FormationsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NewSectionType extends AbstractType
{
    public function __construct(private FormationsRepository $rep)
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rubrics', ChoiceType::class, [
                'choices' => $this->choiceList($options),
                'label' => 'Choisissez une formation'
            ])
            /**
             * On ne peut pas utiliser de query_builder car lorsque l'on envoie le form, il ne passe pas
             * $form->isValid car symfony retourne que le fichier de l'image ne peut pas être trouvé alors
             * qu'il n'y a pas de upload de fichier
             */
            /*->add('rubrics', EntityType::class, [
                'class' => Formations::class,
                'query_builder' => function(FormationsRepository $rep) use ($options){
                    return $rep->findFormationsByUser($options);
                },
                'choice_label' => 'title',
                'label' => 'Sélectionner une section'
            ])*/
            ->add('send', SubmitType::class, ['label' => 'Créer une section pour cette formation'])
        ;
    }

    private function choiceList(array $options)
    {
        return $this->rep->findFormationsByUser($options);
    }
}