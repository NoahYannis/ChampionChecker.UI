<?php

namespace MVC\Model;

class User
{
    // Wird von der API abgefragt.
    private string $username;

    public function __construct(
        private string $firstName,
        private string $lastName,
        private string $email,
        private string $password
    ) {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }


    public function getUsername(): string
    {
        return $this->username;
    }


    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
