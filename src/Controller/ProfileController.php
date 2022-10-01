<?php

namespace App\Controller;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Exception\BillingUnavailableException;
use App\Form\RegistrationFormType;
use App\Model\UserDto;
use App\Security\BillingAuthenticator;
use App\Security\Users;
use App\Service\BillingClient;
use App\Service\DecodeJwt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Repository\CourseRepository;

/**
 * @Route("/profile")
 *
 */
class ProfileController extends AbstractController
{
    private $billingClient;
    private $decodeJwt;
    private $serializer;

    public function __construct(
        BillingClient $billingClient,
        DecodeJwt $decodeJwt,
        SerializerInterface $serializer
    ) {
        $this->billingClient = $billingClient;
        $this->decodeJwt = $decodeJwt;
        $this->serializer = $serializer;
    }
    /**
     * @Route("/", name="profile")
     */
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }
        try {
            $response = $this->billingClient->getCurrentUser($this->getUser());
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
        $userDto = $this->serializer->deserialize($response, UserDto::class, 'json');
        return $this->render('profile/index.html.twig', [
            'userDto' => $userDto
        ]);
    }
    /**
     * @Route("/history", name="app_history")
     */
    public function history(BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        $transactions = $billingClient->getTransactions([], $this->getUser());
        uasort($transactions, function ($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });
        return $this->render('profile/history.html.twig', [
            'transactions' => $transactions
        ]);
    }
}
