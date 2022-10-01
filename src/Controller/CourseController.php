<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="app_course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        $billingCourses = $billingClient->getCourses();
        $localCourses = $courseRepository->findAllInArray();
        $billingCourses = $this->arrayByKeys($billingCourses, 'code');
        $localCourses = $this->arrayByKeys($localCourses, 'code');

        if (!$this->getUser()) {
            $freeCourses = [];
            foreach ($localCourses as $code => $course) {
                if (!isset($billingCourses[$code]) || $billingCourses[$code]['type'] === 'free') {
                    $freeCourses[] = [
                        'course' => $course,
                        'billingInfo' => ['type' => 'free'],
                        'transaction' => null
                    ];
                }
            }
            return $this->render('course/index.html.twig', [
                'courses' => $freeCourses,
            ]);
        }
        $user = $this->getUser();
        $transactions = $billingClient->getTransactions(['type' => 'payment', 'skip_expired' => true], $user);
        $transactions = $this->arrayByKeys($transactions, 'course_code');

        $courses = [];
        foreach ($localCourses as $code => $course) {
            $courses[] = [
                'course' => $course,
                'billingInfo' => $billingCourses[$code],
                'transaction' => $transactions[$code] ?? null
            ];
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }
    private function arrayByKeys($courses, $key): array
    {
        $array = [];
        foreach ($courses as $course) {
            $array[$course[$key]] = $course;
        }
        return $array;
    }

    /**
     * @Route("/new", name="app_course_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_course_index');
        }
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course);
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/pay", name="app_course_pay", methods={"GET"})
     */
    public function pay(Course $course, BillingClient $billingClient): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $user = $this->getUser();
        $response = $billingClient->pay($course->getCode(), $user);
        return $this->redirectToRoute('app_course_index');
    }

    /**
     * @Route("/{id}", name="app_course_show", methods={"GET"})
     */
    public function show(Course $course, BillingClient $billingClient): Response
    {
        $billingCourse = $billingClient->getCourseByCode($course->getCode());
        if ($billingCourse['type'] === 'free') {
            return $this->render('course/show.html.twig', [
                'course' => $course
            ]);
        }
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $user = $this->getUser();
        $transaction = $billingClient->getTransactions(
            ['course_code' => $course->getCode(), 'skip_expired' => true],
            $user
        );
        if ($transaction) {
            return $this->render('course/show.html.twig', [
                'course' => $course
            ]);
        }
        throw new \Exception('Данный курс недоступен!');
    }

    /**
     * @Route("/{id}/edit", name="app_course_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_course_index');
        }
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course);
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_delete", methods={"POST"})
     */
    public function delete(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_course_index');
        }

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $courseRepository->remove($course);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
