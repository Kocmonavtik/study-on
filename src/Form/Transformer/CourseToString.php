<?php

namespace App\Form\Transformer;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToString implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    // Трансформирование объекта в строку
    public function transform($course): string
    {
        if ($course === null) {
            return '';
        }

        return $course->getId();
    }
    //Трансформирование строки в объект
    public function reverseTransform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->find($courseId)
        ;

        if ($course === null) {
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $courseId
            ));
        }

        return $course;
    }
}
