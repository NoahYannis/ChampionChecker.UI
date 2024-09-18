<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

class Competition {
    private ?int $id;
    private string $name;
    private ?array $classParticipants;
    private ?array $studentParticipants;
    private bool $isTeam;
    private ?bool $isMale;
    private ?DateTime $date;
    private ?int $refereeId;
    private ?Referee $referee;

    public function __construct(
        int $id = null,
        string $name,
        ?array $classParticipants = [],
        ?array $studentParticipants = [],
        bool $isTeam = false,
        ?bool $isMale = null,
        $date = null,
        ?int $refereeId = null,
        ?Referee $referee = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->classParticipants = $classParticipants;
        $this->studentParticipants = $studentParticipants;
        $this->isTeam = $isTeam;
        $this->isMale = $isMale;
        $this->date = $date instanceof DateTime ? $date : new DateTime($date);
        $this->refereeId = $refereeId;
        $this->referee = $referee;
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

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        if (strlen($name) < 3 || strlen($name) > 50) {
            throw new InvalidArgumentException('Name must be between 3 and 50 characters.');
        }
        $this->name = $name;
    }

    public function getClassParticipants(): ?array {
        return $this->classParticipants;
    }

    public function setClassParticipants(?array $classParticipants): void {
        $this->classParticipants = $classParticipants;
    }

    public function getStudentParticipants(): ?array {
        return $this->studentParticipants;
    }

    public function setStudentParticipants(?array $studentParticipants): void {
        $this->studentParticipants = $studentParticipants;
    }

    public function getIsTeam(): bool {
        return $this->isTeam;
    }

    public function setIsTeam(bool $isTeam): void {
        $this->isTeam = $isTeam;
    }

    public function getIsMale(): ?bool {
        return $this->isMale;
    }

    public function setIsMale(?bool $isMale): void {
        $this->isMale = $isMale;
    }

    public function getDate(): ?DateTime {
        return $this->date;
    }

    public function setDate(?DateTime $date): void {
        $this->date = $date;
    }

    public function getRefereeId(): ?int {
        return $this->refereeId;
    }

    public function setRefereeId(?int $refereeId): void {
        $this->refereeId = $refereeId;
    }

    public function getReferee(): ?Referee {
        return $this->referee;
    }

    public function setReferee(?Referee $referee): void {
        $this->referee = $referee;
    }
}
