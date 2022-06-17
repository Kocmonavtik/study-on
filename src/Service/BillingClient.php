<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Model\UserDto;
//use JMS\Serializer\Serializer;

use App\Security\Users;
use JMS\Serializer\SerializerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class BillingClient
{
    private $url;
    private $serializer;
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->url = $_ENV['BILLING'];
    }
    public function loginUser(string $creditials)
    {
        //$params = ['username' => $creditials['email'],'password' => $creditials['password']];
        $defaults = array(
            CURLOPT_URL => $this->url . '/api/v1/auth',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $creditials,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($creditials)
            ]
        );

        $ch = curl_init();
        curl_setopt_array($ch, $defaults);
        $resultJson = curl_exec($ch);
        if ($resultJson === false) {
            throw new BillingUnavailableException('Сервис временно недоступен');
        }
        curl_close($ch);

        $result = json_decode($resultJson, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new BillingUnavailableException('Неверные учетные данные');
            }
        }
        $userDto = $this->serializer->deserialize($resultJson, UserDto::class, 'json');
        return $userDto;
    }
    public function register(UserDto $userDto): UserDto
    {
        $creditials = $this->serializer->serialize($userDto, 'json');

        $defaults = array(
            CURLOPT_URL => $this->url . '/api/v1/register',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $creditials,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($creditials)
            ]
        );
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);
        $resultJson = curl_exec($ch);
        if ($resultJson === false) {
            throw new BillingUnavailableException('Сервис временно недоступен');
        }
        curl_close($ch);

        $result = json_decode($resultJson, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }
        return $this->serializer->deserialize($resultJson, UserDto::class, 'json');
    }
    public function getCurrentUser(Users $user)
    {
        //$decodeJwt->decode($user->getToken());
        $defaults = array(
            CURLOPT_URL => $this->url . '/api/v1/current',
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $user->getToken()
            ]
        );
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);
        $resultJson = curl_exec($ch);
        if ($resultJson === false) {
            throw new BillingUnavailableException('Сервис временно недоступен');
        }
        curl_close($ch);
        $result = json_decode($resultJson, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }
        return $resultJson;
    }
}
