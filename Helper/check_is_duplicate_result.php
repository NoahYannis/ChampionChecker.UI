<?php
// Prüft, ob ein Klassenergebnis einer Klasse bei einer Station bereits existiert.

require_once '../vendor/autoload.php';

use MVC\Controller\CompetitionResultController;

session_start();

$currentCompResults = loadCompetitionResults();
$selectedClass = $_SESSION['classresult_selectedClass'] ?? null;
$selectedCompetition = $_SESSION['classresult_selectedCompetition'] ?? null;

$duplicateResults = array_filter($currentCompResults, function ($comp) use ($selectedClass, $selectedCompetition) {
    return $comp->getClassId() == $selectedClass->getId() && $comp->getCompetitionId() == $selectedCompetition->getId();
});

$isDuplicateResult = !empty($duplicateResults);
echo json_encode($isDuplicateResult);
exit;


// Lädt die Stationsergebnisse. Falls ein gültiges Cache besteht daraus, ansonsten aus der Datenbank.
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
