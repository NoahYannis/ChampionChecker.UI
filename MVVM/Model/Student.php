<?php

namespace MVVM\Model;

use RuntimeException;
use InvalidArgumentException;

class Student {
    private ?int $id;
    private string $firstName;
    private string $lastName;

    private int $classId;
    private ClassModel $class;

    private ?array $competitions = [];
    private ?array $competitionResults = [];

    public function __construct(
        ?int $id = null,          
        string $firstName,
        string $lastName,
        int $classId,
        ClassModel $class,
        ?array $competitions = [],
        ?array $competitionResults = []
    ) {
        $this->id = $id;          
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->classId = $classId;
        $this->class = $class;
        $this->competitions = $competitions;
        $this->competitionResults = $competitionResults;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(int $id): void {
        if (isset($this->id)) {
            throw new RuntimeException('ID cannot be set manually after initialization.');
        }
        $this->id = $id;
    }

    public function getFirstName(): string {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void {
        if (strlen($firstName) < 1 || strlen($firstName) > 50) {
            throw new InvalidArgumentException('FirstName must be between 1 and 50 characters.');
        }
        $this->firstName = $firstName;
    }

    public function getLastName(): string {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void {
        if (strlen($lastName) < 1 || strlen($lastName) > 50) {
            throw new InvalidArgumentException('LastName must be between 1 and 50 characters.');
        }
        $this->lastName = $lastName;
    }

    public function getClassId(): int {
        return $this->classId;
    }

    public function setClassId(int $classId): void {
        $this->classId = $classId;
    }

    public function getClass(): ClassModel {
        return $this->class;
    }

    public function setClass(ClassModel $class): void {
        $this->class = $class;
    }

    public function getCompetitions(): ?array {
        return $this->competitions;
    }

    public function setCompetitions(?array $competitions): void {
        $this->competitions = $competitions;
    }

    public function getCompetitionResults(): ?array {
        return $this->competitionResults;
    }

    public function setCompetitionResults(?array $competitionResults): void {
        $this->competitionResults = $competitionResults;
    }
}