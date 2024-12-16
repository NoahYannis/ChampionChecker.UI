<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;

class Teacher
{
    public function __construct(
        private ?int $id = null,
        private string $firstName,
        private string $lastName,
        private string $shortCode,
        private bool $isParticipating = false,
        private ?string $additionalInfo = null,
        private ?ClassModel $class = null,
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
            throw new InvalidArgumentException('First name must be between 1 and 50 characters.');
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
            throw new InvalidArgumentException('Last name must be between 1 and 50 characters.');
        }
        $this->lastName = $lastName;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function setShortCode(string $shortCode): void
    {
        if (strlen($shortCode) < 1 || strlen($shortCode) > 4) {
            throw new InvalidArgumentException('Short code must be between 1 and 4 characters.');
        }
        $this->shortCode = $shortCode;
    }

    public function getIsParticipating(): bool
    {
        return $this->isParticipating;
    }

    public function setIsParticipating(bool $isParticipating): void
    {
        $this->isParticipating = $isParticipating;
    }


    public function getClass(): ?ClassModel
    {
        return $this->class;
    }

    public function setClass(?ClassModel $class): void
    {
        $this->class = $class;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): void
    {
        if ($additionalInfo !== null && strlen($additionalInfo) > 255) {
            throw new InvalidArgumentException('Additional info must not exceed 255 characters.');
        }
        $this->additionalInfo = $additionalInfo;
    }
}
