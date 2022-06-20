<?php

namespace App\Tests\Authorization;

use App\Service\BillingClient;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class Auth extends AbstractTest
{
    private $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function auth(string $data)
    {
        $requestData = json_decode($data, true);
        // Изменение сервиса
        $this->getBillingClient();
        $client = self::getClient();
        // Переход на страницу авторизации
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();
        // Заполнение формы
        $button = $crawler->selectButton('Войти');
        $form=$button->form([
            'email'=>$requestData['username'],
            'password'=>$requestData['password']
        ]);
        $client->submit($form);
        // Проверка ошибок
        $error = $crawler->filter('#errors');
        self::assertCount(0, $error);
        $crawler = $client->followRedirect();
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        return $crawler;
    }

    // Замена биллинга
    public function getBillingClient(): void
    {
        // запрет перезагрузки ядра
        self::getClient()->disableReboot();
        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock($this->serializer)
        );
    }
}