<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UpdateLessonStatusStudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $status = null;
        
        if($options['data'])
        {
            if($options['data'][0] !== null || !empty($options['data'][0]))
            {
                if(array_key_exists(0, $options['data'][0]))
                {
                    $status = $options['data'][0][0]->getStatus();
                }
            }
        }

        if($status == 1)
        {
            $builder->add('send_update', SubmitType::class);
        }
        else
        {
            $builder->add('send_updatable', SubmitType::class);
        }
    }
}