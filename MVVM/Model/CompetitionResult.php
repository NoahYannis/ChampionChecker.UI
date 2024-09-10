<?php

namespace MVVM\Model;

use RuntimeException;
use InvalidArgumentException;

class CompetitionResult {
    private ?int $id;
    private int $pointsAchieved;

    private int $competitionId;
    private Competition $competition;

    private int $winnerId;
    private Student $winner;

    public function __construct(
        ?int $id = null,
        int $pointsAchieved,
        int $competitionId,
        Competition $competition,
        int $winnerId,
        Student $winner,
    ) {
        $this->id = $id; 
        $this->pointsAchieved = $pointsAchieved;
        $this->competitionId = $competitionId;
        $this->competition = $competition;
        $this->winnerId = $winnerId;
        $this->winner = $winner;
        $this->id = $id; 
    }

    public function getId(): int {
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

    public function getCompetition(): Competition {
        return $this->competition;
    }

    public function setCompetition(Competition $competition): void {
        $this->competition = $competition;
    }

    public function getWinnerId(): int {
        return $this->winnerId;
    }

    public function setWinnerId(int $winnerId): void {
        $this->winnerId = $winnerId;
    }

    public function getWinner(): Student {
        return $this->winner;
    }

    public function setWinner(Student $winner): void {
        $this->winner = $winner;
    }
}