<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use App\Service\DecodeJwt;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private BillingClient $billingClient;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private DecodeJwt $decodeJwt;
    private SerializerInterface $serializer;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        BillingClient $billingClient,
        DecodeJwt $decodeJwt,
        SerializerInterface $serializer
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->billingClient = $billingClient;
        $this->decodeJwt = $decodeJwt;
        $this->serializer = $serializer;
    }

    public function authenticate(Request $request): Passport
    {
        $data = [
            'username' => $request->request->get('email', ''),
            'password' => $request->request->get('password', '')
        ];
        $creditials = $this->serializer->serialize($data, 'json');
       /* $creditials['email'] = $request->request->get('email', '');
        $creditials['password'] = $request->request->get('password', '');*/

        $request->getSession()->set(Security::LAST_USERNAME, $data['username']);

        $passport = new SelfValidatingPassport(
            new UserBadge($data['username'], function () use ($creditials) {
                try {
                    $userDto = $this->billingClient->loginUser($creditials);
                    $user = Users::fromDto($userDto, $this->decodeJwt);
                } catch (BillingUnavailableException $exception) {
                    throw new CustomUserMessageAuthenticationException($exception->getMessage());
                }
                return $user;
            }),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge()
            ]
        );
        return $passport;
       /* return new Passport(
            new UserBadge($email),
            new CustomCredentials()
            //new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );*/
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));

        // For example:
        //return new RedirectResponse($this->urlGenerator->generate('some_route'));
        //throw new \Exception('TODO: provide a valid redirect inside ' . __FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
