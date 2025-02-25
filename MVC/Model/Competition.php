<?php

namespace MVC\Model;

use RuntimeException;
use InvalidArgumentException;
use DateTime;
use JsonSerializable;

enum CompetitionStatus: int
{
    case Geplant = 0;
    case Läuft = 1;
    case Ausstehend = 2;
    case Abgesagt = 3;
    case Verschoben = 4;
    case Beendet = 5;

    public static function fromString(string $value): self
    {
        return match ($value) {
            'Geplant' => self::Geplant,
            'Läuft' => self::Läuft,
            'Ausstehend' => self::Ausstehend,
            'Abgesagt' => self::Abgesagt,
            'Verschoben' => self::Verschoben,
            'Beendet' => self::Beendet,
            default => throw new InvalidArgumentException("Ungültiger Status: $value"),
        };
    }
}

class Competition implements JsonSerializable
{
    public function __construct(
        private ?int $id = null,
        private string $name,
        private ?array $classParticipants = [],
        private ?array $studentParticipants = [],
        private bool $isTeam = false,
        private ?bool $isMale = null,
        private $date = null,
        private ?int $refereeId = null,
        private CompetitionStatus $status = CompetitionStatus::Geplant,
        private ?string $additionalInfo = null,
    ) {
        $this->date = $date instanceof DateTime ? $date : DateTime::createFromFormat('d.m.y, H:i:s', $date);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'classParticipants' => $this->classParticipants,
            'studentParticipants' => $this->studentParticipants,
            'isTeam' => $this->isTeam,
            'isMale' => $this->isMale,
            'date' => $this->date ? $this->date->format(DateTime::ATOM) : null,
            'refereeId' => $this->refereeId,
            'status' => $this->status->name,
            'additionalInfo' => $this->additionalInfo
        ];
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (strlen($name) < 3 || strlen($name) > 50) {
            throw new InvalidArgumentException('Name must be between 3 and 50 characters.');
        }
        $this->name = $name;
    }

    public function getClassParticipants(): ?array
    {
        return $this->classParticipants;
    }

    public function setClassParticipants(?array $classParticipants): void
    {
        $this->classParticipants = $classParticipants;
    }

    public function getStudentParticipants(): ?array
    {
        return $this->studentParticipants;
    }

    public function setStudentParticipants(?array $studentParticipants): void
    {
        $this->studentParticipants = $studentParticipants;
    }

    public function getIsTeam(): bool
    {
        return $this->isTeam;
    }

    public function setIsTeam(bool $isTeam): void
    {
        $this->isTeam = $isTeam;
    }

    public function getIsMale(): ?bool
    {
        return $this->isMale;
    }

    public function setIsMale(?bool $isMale): void
    {
        $this->isMale = $isMale;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getRefereeId(): ?int
    {
        return $this->refereeId;
    }

    public function setRefereeId(?int $refereeId): void
    {
        $this->refereeId = $refereeId;
    }

    public function getStatus(): CompetitionStatus
    {
        return $this->status;
    }

    public function setStatus(CompetitionStatus $status): void
    {
        if (!in_array($status, CompetitionStatus::cases())) {
            throw new InvalidArgumentException('Ungültiger Status-Wert.');
        }
        $this->status = $status;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): void
    {
        if ($additionalInfo !== null && strlen($additionalInfo) > 200) {
            throw new InvalidArgumentException('Zusätzliche Informationen dürfen maximal 200 Zeichen lang sein.');
        }
        $this->additionalInfo = $additionalInfo;
    }
}
