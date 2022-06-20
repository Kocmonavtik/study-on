<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;
use App\Tests\Authorization\Auth;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

    /**
     * @group testSuccessfulAuth
     * @group testAuth
     */
    public function testSuccessfulAuth(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $data = [
            'username' => 'succUser@gmail.com',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);
    }
    /**
     * @group testErrorAuth
     * @group testAuth
     */
    public function testErrorAuth(): void
    {
        $client = AbstractTest::getClient();
        $url = '/courses/';


        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        $authorization = $crawler->selectLink('Авторизация')->link();
        $crawler = $client->click($authorization);
        $this->assertResponseOk();


        self::assertEquals('/login', $client->getRequest()->getPathInfo());

        $data = [
            'username' => 'succUser@gmail.com',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');

        $auth = new Auth();
        $auth->setSerializer($this->serializer);

        $requestData = json_decode($requestData, true);


        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = $requestData['username'];
        $form['password'] = $requestData['password'];
        $client->submit($form);


        self::assertFalse($client->getResponse()->isRedirect('/courses/'));
        //$crawler = $client->followRedirect();


        //$error = $crawler->filter('#errors');
        //self::assertEquals('Неверные учетные данные', $error->text());


    }
    /**
     * @group testLogout
     * @group testAuth
     */
    public function testLogout(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);

        $data = [
            'username' => 'succUser@gmail.com',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);

        $client = self::getClient();
        $logout = $crawler->selectLink('Выход')->link();
        $crawler = $client->click($logout);
        $this->assertResponseRedirect();
        self::assertEquals('/logout', $client->getRequest()->getPathInfo());

        $crawler = $client->followRedirect();
        $this->assertResponseRedirect();
        self::assertEquals('/', $client->getRequest()->getPathInfo());

        $crawler = $client->followRedirect();
        self::assertEquals('/courses', $client->getRequest()->getPathInfo());
    }

    /**
     * @group testSuccessfulRegister
     * @group testRegister
     */
    public function testSuccessfulRegister(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $auth->getBillingClient();

        $client = static::getClient();
        $crawler = $client->request('GET', '/register');


        $this->assertResponseOk();


        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[username]'] = 'intaro@intaro.ru';
        $form['registration_form[password][first]'] = 'intaro123';
        $form['registration_form[password][second]'] = 'intaro123';
        $form['registration_form[agreeTerms]'] = true;


        $crawler = $client->submit($form);


        $errors = $crawler->filter('span.form-error-message');
        self::assertCount(0, $errors);


        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
    }

    /**
     * @group testFailedLengthPassRegister
     * @group testFailedPasswordRegister
     * @group testRegister
     */
    public function testFailedLengthPassRegister(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $auth->getBillingClient();

        $client = static::getClient();
        $crawler = $client->request('GET', '/register');


        $this->assertResponseOk();


        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[username]'] = 'intaro@gmail.com';
        $form['registration_form[password][first]'] = '123';
        $form['registration_form[password][second]'] = '123';
        $form['registration_form[agreeTerms]'] = true;


        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();

        self::assertEquals('Минимальное число символов 6', $error->text());
    }
    /**
     * @group testFailedConfirmPassRegister
     * @group testFailedPasswordRegister
     * @group testRegister
     */
    public function testFailedConfirmPassRegister(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $auth->getBillingClient();

        $client = static::getClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseOk();

        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[username]'] = 'intaro@intaro.ru';
        $form['registration_form[password][first]'] = '123456';
        $form['registration_form[password][second]'] = '123';
        $form['registration_form[agreeTerms]'] = true;

        $crawler = $client->submit($form);

        $error = $crawler->filter('.invalid-feedback')->first();

        self::assertEquals('Пароли не совпадают', $error->text());
    }
}
