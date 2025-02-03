<?php

// Prüft den Zeitplan aller Schüler auf zeitliche Konflikte

require_once '../vendor/autoload.php';

use MVC\Controller\CompetitionController;
use MVC\Controller\StudentController;


function loadAllStudents($cacheDuration = 300): array
{
    if (isset($_SESSION['students']) && isset($_SESSION['overview_students_timestamp'])) {
        if ((time() - $_SESSION['overview_students_timestamp']) < $cacheDuration) {
            return $_SESSION['students'];
        }
    }

    $students = StudentController::getInstance()->getAll();

    $_SESSION['students'] = $students;
    $_SESSION['overview_students_timestamp'] = time();

    return $students;
}

$allStudents = loadAllStudents();
$competitionData = $_SESSION['overview_competitions'] ?? CompetitionController::getInstance()->getAll();
$timeCollisions = [];

foreach ($allStudents as $student) {
    $studentCompetitions = $student->getCompetitions();

    // Keine zeitlichen Konflikte bei keiner oder nur einer Station
    if (count($studentCompetitions) <= 1) {
        continue;
    }

    $competitionTimes = [];
    foreach ($studentCompetitions as $compId => $compName) {
        $competition = array_filter($competitionData, fn($comp) => $comp->getId() == $compId);
        $competition = reset($competition);
        if ($competition) {
            $competitionTimes[$competition->getId()] = $competition->getDate()->getTimestamp();
        }
    }

    asort($competitionTimes);

    $collisionCompetitions = [];
    $previousCompId = null;
    $previousTime = null;

    foreach ($competitionTimes as $compId => $timestamp) {
        if ($previousTime !== null && abs($timestamp - $previousTime) < 900) { // Weniger als 15 Minuten Abstand
            if (!in_array($previousCompId, $collisionCompetitions)) {
                $collisionCompetitions[] = $previousCompId;
            }
            $collisionCompetitions[] = $compId;
        }

        $previousCompId = $compId;
        $previousTime = $timestamp;
    }

    if (!empty($collisionCompetitions)) {
        $timeCollisions[$student->getId()] = array_unique($collisionCompetitions);
    }
}

echo json_encode($timeCollisions);
