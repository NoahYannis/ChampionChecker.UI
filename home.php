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

<?php
require 'vendor/autoload.php';

use MVC\Controller\CompetitionResultController;
use MVC\Controller\ClassController;
use MVC\Model\CompetitionResult;

session_start();

$classController = new ClassController();

/**
 * @param int $cacheDuration Die Dauer (in Sekunden), für die die Ergebnisse im Cache gehalten werden sollen. Standard ist 300 Sekunden.
 * @return CompetitionResult[] Ein Array von Wettbewerbsergebnissen.
 */
function loadCompetitionResults($cacheDuration = 300): array
{
    if (isset($_SESSION['competitionResults']) && isset($_SESSION['competitionResultsTimestamp'])) {
        if ((time() - $_SESSION['competitionResultsTimestamp']) < $cacheDuration) {
            return $_SESSION['competitionResults'];
        }
    }

    $competitionResultController = new CompetitionResultController();
    $competitionResults = $competitionResultController->getAll();

    $_SESSION['competitionResults'] = $competitionResults;
    $_SESSION['competitionResultsTimestamp'] = time();

    return $competitionResults;
}

function aggregatePointsByClass($competitionResults)
{
    $pointsByClass = [];

    foreach ($competitionResults as $result) {
        $classId = $result->getClassId();
        $points = $result->getPointsAchieved();

        // Punkte zur Gesamtpunktzahl für die Klasse addieren
        if (!isset($pointsByClass[$classId])) {
            $pointsByClass[$classId] = 0;
        }
        $pointsByClass[$classId] += $points;
    }

    arsort($pointsByClass); // Ergebnisse nach Punkten sortieren

    return $pointsByClass;
}

function printCompetitionResult($competitionResults)
{
    global $classController;

    echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['competitionResultsTimestamp']) . "<br></p>";

    // Gesamtpunkte pro Klasse berechnen
    $pointsByClass = aggregatePointsByClass($competitionResults);

    echo "<table class='competition-table'>"; 
    echo "<thead>";
    echo "<tr>";
    echo "<th>Klasse</th>";
    echo "<th>Gesamtpunkte</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($pointsByClass as $classId => $totalPoints) {
        echo "<tr>";
        echo "<td>" . $classController->getClassName($classId) . "</td>";
        echo "<td>{$totalPoints}</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

$competitionResults = loadCompetitionResults();
?>

<body>
    <header>
        <h1>Klassenübersicht</h1>
    </header>

    <p>Es wurden X von Y Wettbewerben ausgewertet.</p>

    <progress value="33" max="100" id="progress"></progress>

    <section>
        <?php printCompetitionResult($competitionResults); ?>
    </section>
</body>

</html>
