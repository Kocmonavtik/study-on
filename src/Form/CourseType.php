<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       /* $builder
            ->add('code')
            ->add('title')
            ->add('description')
        ;*/
        $builder
            ->add('code', TextType::class, [
                'label' => 'Символьный код',
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Код должен иметь не больше 255 символов']),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9]+$/',
                        'message' => 'Код должен содержить только буквы латинского алфавита и цифры',
                    ]),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Название курса',
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название курса должно иметь не больше 255 символов']),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Описание курса',
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым']),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Описание курса должно иметь не больше 1000 символов']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
