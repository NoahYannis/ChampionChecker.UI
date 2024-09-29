<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet/less" type="text/css" href="styles/styles.less" />
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

session_start();

function loadCompetitionResults()
{
    $competitionResultController = new CompetitionResultController();
    $competitionResults = $competitionResultController->getAll();
    return $competitionResults;
}

function printCompetitionResult($competitionResults)
{
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Competition ID</th>";
    echo "<th>Class ID</th>";
    // echo "<th>Student ID</th>";
    echo "<th>Points</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($competitionResults as $result) {
        echo "<tr>";
        echo "<td>{$result->getPointsAchieved()}</td>";
        echo "<td>{$result->getCompetitionId()}</td>";
        echo "<td>{$result->getClassId()}</td>";
        // echo "<td>" . ($result->getStudentId() === null ? 'N/A' : $result->getStudentId()) . "</td>";
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
