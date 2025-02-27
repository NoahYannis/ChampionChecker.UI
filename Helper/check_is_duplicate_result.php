<?php
// Prüft, ob ein Klassenergebnis oder Einzelergebnis bei einer Station bereits existiert.

require_once '../vendor/autoload.php';

use MVC\Controller\CompetitionResultController;

session_start();

$currentCompResults = loadCompetitionResults();

$selectedClass = !isset($_GET['studentId']) ? $_SESSION['classresult_selectedClass'] : null; // Für Klassen
$selectedStudent = $_GET['studentId'] ?? null; // Für Schüler

$selectedCompetition = $_GET['compId'] ?? $_SESSION['classresult_selectedCompetition'] ?? null;

$duplicateResults = array_filter($currentCompResults, function ($comp) use ($selectedClass, $selectedStudent, $selectedCompetition) {
    return ($selectedClass && $comp->getClassId() == $selectedClass->getId()) || 
           ($selectedStudent && $comp->getStudentId() == $selectedStudent) &&
           $comp->getCompetitionId() == $selectedCompetition;
});

$isDuplicateResult = !empty($duplicateResults);
echo json_encode($isDuplicateResult);
exit;


function loadCompetitionResults($cacheDuration = 300): array
{
    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['results_competitionResults']) && isset($_SESSION['results_competitionResultsTimestamp'])) {
        if ((time() - $_SESSION['results_competitionResultsTimestamp']) < $cacheDuration) {
            return $_SESSION['results_competitionResults'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $competitionResultController = new CompetitionResultController();
    $competitionResults = $competitionResultController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['results_competitionResults'] = $competitionResults;
    $_SESSION['results_competitionResultsTimestamp'] = time();

    return $competitionResults;
}
