<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet/less" type="text/css" href="styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="styles/home.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<!-- Oben Nav-Leiste mit Unter-Menüs + Login Button & Profil -->
<!-- Darunter Progressbar mit Auswertungs-Fortschritt -->
<!-- Mittig Tabelle mit Klassenpunktzahlen -->

<?php

// CompetitionResults laden aus Cache oder DB
// Progressbar-Wert berechnen
// Tabelle mit Klassenpunktzahlen erstellen

require_once __DIR__ . '/MVC/Model/Competition.php';
require_once __DIR__ . '/MVC/Model/ClassModel.php';
require_once __DIR__ . '/MVC/Model/CompetitionResult.php';
require_once __DIR__ . '/MVC/Controller/IController.php';
require_once __DIR__ . '/MVC/Controller/ClassController.php';
require_once __DIR__ . '/MVC/Controller/CompetitionController.php';
require_once __DIR__ . '/MVC/Controller/CompetitionResultController.php';

use MVC\Controller\CompetitionResultController;
use MVC\Controller\CompetitionController;
use MVC\Controller\ClassController;


session_start();

function loadCompetitionResults($cacheDuration = 300)
{
    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['competitionResults']) && isset($_SESSION['competitionResultsTimestamp'])) {
        if ((time() - $_SESSION['competitionResultsTimestamp']) < $cacheDuration) {
            return $_SESSION['competitionResults'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $competitionResultController = new CompetitionResultController();
    $competitionResults = $competitionResultController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['competitionResults'] = $competitionResults;
    $_SESSION['competitionResultsTimestamp'] = time();

    return $competitionResults;
}


function getCompetitionName($competitionId)
{
    if (isset($_SESSION['competitions']) && isset($_SESSION['competitions'][$competitionId])) {
        return $_SESSION['competitions'][$competitionId];
    }

    $competitionController = new CompetitionController();
    $competition = $competitionController->getById($competitionId);
    $compName = $competition->getName();
    $_SESSION['competitions'][$competitionId] = $compName;
    return $compName;
}

function getClassName($classId)
{
    if (isset($_SESSION['classes']) && isset($_SESSION['classes'][$classId])) {
        return $_SESSION['classes'][$classId];
    }

    $classController = new ClassController();
    $class = $classController->getById($classId);
    $className = $class->getName();
    $_SESSION['classes'][$classId] = $className;
    return $className;
}


// TODO: Gesamtpunktzahl der Klassen berechnen
function printCompetitionResult($competitionResults)
{
    echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['competitionResultsTimestamp']) . "<br></p>";

    echo "<table class='competition-table'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Wettbewerb</th>";
    echo "<th>Klasse</th>";
    echo "<th>Punkte</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($competitionResults as $result) {
        echo "<tr>";
        echo "<td>" . getCompetitionName($result->getCompetitionId()) . "</td>";
        echo "<td>" . getClassName($result->getClassId()) . "</td>";
        echo "<td>{$result->getPointsAchieved()}</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

$competitionResults = loadCompetitionResults();
?>

<body>
    <header>
        <h1>Turnierübersicht</h1>
    </header>

    <p>Es wurden X von Y Wettbewerben ausgewertet.</p>

    <progress value="33" max="100" id="progress"></progress>

    <!-- Tabelle mit Klassenpunktzahlen -->
    <section>
        <?php printCompetitionResult($competitionResults); ?>
    </section>

</body>

</html>