<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;

class Referee
{
    public function __construct(
        private ?int $id = null,
        private string $firstName,
        private string $lastName,
        private ?array $competitions = []
    ) {}
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if (isset($this->id)) {
            throw new RuntimeException('ID cannot be set manually after initialization.');
        }
        $this->id = $id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        if (strlen($firstName) < 1 || strlen($firstName) > 50) {
            throw new InvalidArgumentException('FirstName must be between 1 and 50 characters.');
        }
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        if (strlen($lastName) < 1 || strlen($lastName) > 50) {
            throw new InvalidArgumentException('LastName must be between 1 and 50 characters.');
        }
        $this->lastName = $lastName;
    }

    public function getCompetitions(): ?array
    {
        return $this->competitions;
    }

    public function setCompetitions(?array $competitions): void
    {
        $this->competitions = $competitions;
    }
}
