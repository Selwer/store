<?php

namespace App\Form;

use App\Entity\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class FeedbackFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Составное поле, можно пользоваться, только нужно в шаблоне скрыть вывод поля
        // $builder
        //     ->add('fields', CollectionType::class, [
        //         'entry_type' => TextType::class,
        //         'allow_add' => true,
        //         'prototype' => false,
        //         'required' => false
        //     ])
        //     ->add('type', HiddenType::class, ['mapped' => false])
        // ;

        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'mapped' => false
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'mapped' => false
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'mobile_phone'
                ]
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Form::class,
        ]);
    }
}
