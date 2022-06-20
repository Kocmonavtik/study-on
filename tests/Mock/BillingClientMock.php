<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Model\UserDto;
use App\Service\BillingClient;
use JMS\Serializer\SerializerInterface;

class BillingClientMock extends BillingClient
{
    private $serializer;
    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);
        $this->serializer = $serializer;
    }

    public function loginUser(string $creditials)
    {
        $data = json_decode($creditials, true);
        if ($data['username'] === 'succUser@gmail.com' && $data['password'] === '123456') {
            $tmp = [
                'token' => $this->generateToken('ROLE_USER', 'succUser@gmail.com'),
                'username' => 'succUser@gmail.com',
                'roles' => ["ROLE_USER"]
            ];
            $json = json_encode($tmp);
            $dto = $this->serializer->deserialize($json, UserDto::class, 'json');
            return $dto;
        }
        if ($data['username'] === 'adminuser@gmail.com' && $data['password'] === '123456') {
            $tmp = [
                'token' => $this->generateToken('ROLE_SUPER_ADMIN', 'adminuser@gmail.com'),
                'username' => 'adminuser@gmail.com',
                'roles' => ["ROLE_SUPER_ADMIN", "ROLE_USER"]
            ];
            $json = json_encode($tmp);
            $dto = $this->serializer->deserialize($json, UserDto::class, 'json');
            return $dto;
        }
        throw new BillingUnavailableException('Неверные учетные данные');
    }
    private function generateToken(string $role, string $username): string
    {
        $roles = null;
        if ($role === 'ROLE_USER') {
            $roles = ["ROLE_USER"];
        } elseif ($role === 'ROLE_SUPER_ADMIN') {
            $roles = ["ROLE_SUPER_ADMIN", "ROLE_USER"];
        }
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));
        return 'header.' . $query . '.signature';
    }

    public function register(UserDto $userDto): UserDto
    {
        if ($userDto->getUsername() === 'succUser@gmail.com' | $userDto->getUsername() === 'adminuser@gmail.com') {
            throw new BillingUnavailableException('Данный пользователь уже существует');
        }
        $token = $this->generateToken('ROLE_USER', $userDto->getUsername());
        $userDto->setToken($token);
        $userDto->setBalance(0);
        $userDto->setRoles(["ROLE_USER"]);
        return $userDto;
    }
}
