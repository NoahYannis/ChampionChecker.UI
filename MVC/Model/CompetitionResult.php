<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;

class CompetitionResult {
    private ?int $id;
    private int $pointsAchieved;
    private int $competitionId;
    private ?int $classId;
    private ?int $studentId;

    public function __construct(
        ?int $id = null,
        int $pointsAchieved,
        int $competitionId,
        ?int $classId,
        ?int $studentId,
    ) {
        $this->id = $id; 
        $this->pointsAchieved = $pointsAchieved;
        $this->competitionId = $competitionId;
        $this->classId = $classId;
        $this->studentId = $studentId;
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

    public function getPointsAchieved(): int {
        return $this->pointsAchieved;
    }

    public function setPointsAchieved(int $pointsAchieved): void {
        if ($pointsAchieved < 0 || $pointsAchieved > 100) {
            throw new InvalidArgumentException('PointsAchieved must be between 0 and 100.');
        }
        $this->pointsAchieved = $pointsAchieved;
    }

    public function getCompetitionId(): int {
        return $this->competitionId;
    }

    public function setCompetitionId(int $competitionId): void {
        $this->competitionId = $competitionId;
    }

    public function getClassId(): ?int {
        return $this->classId;
    }

    public function setClassId(?int $classId): void {
        $this->classId = $classId;
    }

    public function getStudentId(): ?int {
        return $this->studentId;
    }

    public function setStudentId(?int $studentId): void {
        $this->studentId = $studentId;
    }
}