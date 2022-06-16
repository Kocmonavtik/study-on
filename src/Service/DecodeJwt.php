<?php

namespace App\Service;

class DecodeJwt
{
    private $username;
    private $roles;
    private $exp;

    public function decode($token): void
    {
        $partToken = explode('.', $token);
        $tmp = json_decode(base64_decode($partToken[1]), true);
        $this->username = $tmp['email'];
        $this->roles = $tmp['roles'];
        $this->exp = $tmp['exp'];
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function getRoles()
    {
        return $this->roles;
    }
    public function getExp(): ?int
    {
        return $this->exp;
    }
}
