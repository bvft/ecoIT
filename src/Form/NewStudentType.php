<?php

namespace App\Form;

use App\Entity\PersonDetails;
use App\Entity\PersonLoginInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewStudentType extends AbstractType
{
    /**
     * @var ClassMetadata
     */
    private $entityMetaData;
 
    public function __construct(ValidatorInterface $validator)
    {
        $this->entityMetaData = $validator->getMetadataFor(PersonDetails::class);
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraints = [];

        foreach ($this->entityMetaData->getPropertyMetadata('pseudo') as $set) {
            foreach ($set->getConstraints() as $constraint) {
                $constraints[] = $constraint;
            }
        }
        
        $builder
            ->add('pseudo', null, [
                'data_class' => PersonDetails::class, 
                'mapped' => false, 
                'constraints' => $constraints
            ])
            ->add('email', null, ['data_class' => PersonLoginInfo::class])
            ->add('password', PasswordType::class, [
                'data_class' => PersonLoginInfo::class,
                'label' => 'Mot de passe'
            ])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
        ;
    }
}