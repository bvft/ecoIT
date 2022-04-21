<?php

namespace App\Form;

use App\Entity\Formations;
use App\Entity\Rubrics;
use App\Repository\RubricsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EditFormationType extends AbstractType
{
    /**
     * @var ClassMetadata
     */
    private $entityMetaData;
 
    public function __construct(ValidatorInterface $validator)
    {
        $this->entityMetaData = $validator->getMetadataFor(Formations::class);
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraints = [];

        foreach ($this->entityMetaData->getPropertyMetadata('picture') as $set) {
            foreach ($set->getConstraints() as $constraint) {
                $constraints[] = $constraint;
            }
        }
       
        $builder
            ->add('rubrics', EntityType::class, [
                'class' => Rubrics::class,
                'query_builder' => function(RubricsRepository $rep){
                    return $rep->findAlls();
                },
                'choice_label' => 'name',
                'label' => 'Sélectionner une rubrique',
            ])
            ->add('title', null, [
                'data_class' => Formations::class,
                'label' => 'Titre de la formation',
                'mapped' => false,
            ])
           ->add('short_text', TextareaType::class, [
                'data_class' => Formations::class,
                'label' => 'Courte description',
                'mapped' => false,
            ])
            ->add('picture', FileType::class, [
                'data_class' => Formations::class,
                'label' => 'Image representant la formation',
                'mapped' => false,
                'constraints' => $constraints,
            ])
            ->add('send', SubmitType::class, ['label' => 'Modifier'])
        ;
    }
}