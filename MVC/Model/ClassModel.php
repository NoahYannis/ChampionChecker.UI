<?php

namespace MVC\Model;

use InvalidArgumentException;
use JsonSerializable;

class ClassModel implements JsonSerializable
{
    private ?int $id = null;
    private string $name;
    private ?array $students = [];
    private ?array $competitions = [];
    private ?array $competitionResults = [];
    private ?array $teachers = [];

    public function __construct(
        ?int $id,
        string $name,
        ?array $students = [],
        ?array $competitions = [],
        ?array $competitionResults = [],
        ?array $teachers = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->students = $students;
        $this->competitions = $competitions;
        $this->competitionResults = $competitionResults;
        $this->teachers = $teachers;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if (isset($this->id)) {
            throw new InvalidArgumentException('ID cannot be set manually after initialization.');
        }
        $this->id = $id;
    }

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

    public function getStudents(): ?array
    {
        return $this->students;
    }

    public function setStudents(?array $students): void
    {
        $this->students = $students;
    }

    public function getCompetitions(): ?array
    {
        return $this->competitions;
    }

    public function setCompetitions(?array $competitions): void
    {
        $this->competitions = $competitions;
    }

    public function getCompetitionResults(): ?array
    {
        return $this->competitionResults;
    }

    public function setCompetitionResults(?array $competitionResults): void
    {
        $this->competitionResults = $competitionResults;
    }

    public function getTeachers(): ?array
    {
        return $this->teachers;
    }

    public function setTeachers(?array $teachers): void
    {
        $this->teachers = $teachers;
    }

    public function getPointsAchieved(): int
    {
        // Erreichte Punkt aller Ergebnisse addieren.
        return array_reduce($this->competitionResults, function ($totalPoints, $result) {
            return is_object($result) && method_exists($result, 'getPointsAchieved')
                ? $totalPoints + $result->getPointsAchieved()
                : $totalPoints;
        }, 0);
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'students' => $this->students,
            'competitions' => $this->competitions,
            'competitionResults' => $this->competitionResults,
            'teachers' => $this->teachers,
            'pointsAchieved' => $this->getPointsAchieved(),
        ];
    }
}
