<?php

namespace App\Form;

use App\Entity\PersonDetails;
use App\Entity\PersonLoginInfo;
use App\Entity\InstructorDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewInstructorType extends AbstractType
{
    /**
     * @var ClassMetadata
     */
    private $entityMetaData;
 
    public function __construct(ValidatorInterface $validator)
    {
        $this->entityMetaData = $validator->getMetadataFor(InstructorDetails::class);
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
            ->add('first_name', null, [
                'data_class' => PersonDetails::class,
                'label' => 'Prénom'
            ])
            ->add('name', null, [
                'data_class' => PersonDetails::class,
                'label' => 'Nom'
            ])
            ->add('email', EmailType::class, ['data_class' => PersonLoginInfo::class])
            ->add('password', PasswordType::class, [
                'data_class' => PersonLoginInfo::class,
                'label' => 'Mot de passe'
            ])
            ->add('desc_specs', TextareaType::class, [
                'data_class' => InstructorDetails::class,
                'label' => 'Vos spécialités'
            ])
            ->add('picture', FileType::class, [
                'data_class' => InstructorDetails::class,
                'label' => 'Photo de profil',
                'mapped' => false,
                'constraints' => $constraints
            ])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
        ;
    }
}