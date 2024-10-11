<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;

class ClassModel
{
    public function __construct(
        private ?int $id = null,
        private string $name,
        private ?array $students = [],
        private int $pointsAchieved,
        private ?int $classTeacherId = null,
        private ?ClassTeacher $classTeacher = null
    ) {
        $this->classTeacherId = $classTeacher?->getId();
    }


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

    // Getter und Setter für Name
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }
        $this->name = $name;
    }

    // Getter und Setter für Students
    public function getStudents(): ?array
    {
        return $this->students;
    }

    public function setStudents(?array $students): void
    {
        $this->students = $students;
    }

    // Getter und Setter für PointsAchieved
    public function getPointsAchieved(): int
    {
        return $this->pointsAchieved;
    }

    public function setPointsAchieved(int $pointsAchieved): void
    {
        if ($pointsAchieved < 0 || $pointsAchieved > 100) {
            throw new InvalidArgumentException('PointsAchieved must be between 0 and 100.');
        }
        $this->pointsAchieved = $pointsAchieved;
    }

    public function getClassTeacherId(): ?int
    {
        return $this->classTeacherId;
    }

    public function setClassTeacherId(?int $classTeacherId): void
    {
        $this->classTeacherId = $classTeacherId;
    }

    public function getClassTeacher(): ?ClassTeacher
    {
        return $this->classTeacher;
    }

    public function setClassTeacher(?ClassTeacher $classTeacher): void
    {
        $this->classTeacher = $classTeacher;
    }
}
