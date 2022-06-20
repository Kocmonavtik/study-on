<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\RegistrationFormType;
use App\Model\UserDto;
use App\Security\BillingAuthenticator;
use App\Security\Users;
use App\Service\BillingClient;
use App\Service\DecodeJwt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        BillingClient $billingClient,
        DecodeJwt $decodeJwt,
        BillingAuthenticator $billingAuthenticator

    ) {
        if ($this->getUser()) {
            return new RedirectResponse($this->generateUrl('app_course_index'));
        }
        $userDto = new UserDto();
        $form = $this->createForm(RegistrationFormType::class, $userDto);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //try {
                $userDto = $billingClient->register($userDto);
                $user = Users::fromDto($userDto, $decodeJwt);
            //} catch (BillingUnavailableException $e) {
             //   return $this->render('registration/register.html.twig', [
             //       'registrationForm' => $form->createView(),
             //       'errors' => $e->getMessage(),
             //   ]);
            //}
            return $userAuthenticator->authenticateUser(
                $user,
                $billingAuthenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
