<?php

namespace MVVM\Model;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

class Competition {
    private ?int $id;
    private string $name;
    private ?array $participants = [];
    private ?DateTime $date = null;
    private ?int $refereeId = null;
    private ?Referee $referee = null;

    public function __construct(
        int $id = null,
        string $name,
        ?array $participants = [],
        ?DateTime $date = null,
        ?int $refereeId = null,
        ?Referee $referee = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->participants = $participants;
        $this->date = $date;
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

    public function getParticipants(): ?array {
        return $this->participants;
    }

    public function setParticipants(?array $participants): void {
        $this->participants = $participants;
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
