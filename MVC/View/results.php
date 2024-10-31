<?php
require '../../vendor/autoload.php';
session_start();
include 'nav.php';

use MVC\Controller\CompetitionResultController;
use MVC\Controller\CompetitionController;
use MVC\Controller\ClassController;
use MVC\Model\CompetitionResult;

$classController = new ClassController();

/**
 * @param int $cacheDuration Die Dauer (in Sekunden), für die die Ergebnisse im Cache gehalten werden sollen. Standard ist 300 Sekunden.
 * @return CompetitionResult[] Ein Array von Wettbewerbsergebnissen.
 */
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


function getCompetitionName($competitionId): string
{
    if (isset($_SESSION['results_competitions']) && isset($_SESSION['results_competitions'][$competitionId])) {
        return $_SESSION['results_competitions'][$competitionId];
    }

    $competitionController = new CompetitionController();
    $competition = $competitionController->getById($competitionId);

    if ($competition === null) {
        return "???";
    }

    $compName = $competition->getName();
    $_SESSION['results_competitions'][$competitionId] = $compName;
    return $compName;
}

function printCompetitionResult($competitionResults)
{
    global $classController;

    if(isset($_SESSION['competitionResultsTimestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['competitionResultsTimestamp']) . "<br></p>";
    }

    echo "<table class='results-table'>";
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
        echo "<td>" . $classController->getClassName($result->getClassId()) . "</td>";
        echo "<td>{$result->getPointsAchieved()}</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

$competitionResults = loadCompetitionResults();
?>

<!-- TODO: Filtern, mehr Infos anzeigen -->

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/home.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <header>
        <h1>Ergebnisübersicht</h1>
    </header>

    <!-- Tabelle mit Klassenpunktzahlen -->
    <section>
        <?php printCompetitionResult($competitionResults); ?>
    </section>

</body>

</html>