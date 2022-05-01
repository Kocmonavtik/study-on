<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\Transformer\CourseToString;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

class LessonType extends AbstractType
{
    private $transformer;

    public function __construct(CourseToString $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       /* $builder
            ->add('title')
            ->add('content')
            ->add('serial_number')
            ->add('course')
        ;*/
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название урока',
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название урока должно иметь не больше 255 символов']),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Контент',
            ])
            ->add('serial_number', NumberType::class, [
                'label' => 'Номер урока',
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым']),
                    new Range([
                        'notInRangeMessage' => 'Значение поля должно быть в диапазоне от {{ min }} до {{ max }}',
                        'min' => 1,
                        'max' => 10000
                    ]),
                ],
            ])
            ->add('course', HiddenType::class);
        $builder->get('course')
            ->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
