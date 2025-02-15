<?php
// Die Hauptseite der Anwendung. Hier wird der Auswertungsfortschritt, der aktuelle Zwischenstand und anstehende Stationen angezeigt.

require '../../vendor/autoload.php';
session_start();
include 'nav.php';

use MVC\Controller\CompetitionResultController;
use MVC\Controller\ClassController;
use MVC\Controller\CompetitionController;
use MVC\Model\CompetitionResult;


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

    echo "<p class='timestamp-container'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['competitionResultsTimestamp']) . "<br></p>";

    // Gesamtpunkte pro Klasse berechnen
    $pointsByClass = aggregatePointsByClass($competitionResults);

    echo "<table class='table-style competition-table'>";
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


<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/home.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <header>
        <h1>Auswertung</h1>
    </header>

    <div class="flex-container">
        <p id="evaluation-text"></p>
        <div id="evaluation-progressbar" class="progressbar hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
        <div class="spinner" id="spinner"></div>
    </div>

    <section>
        <?php printCompetitionResult($competitionResults); ?>
    </section>

    <section>
        <div class="flex-container">
            <h2>Anstehende Stationen</h2>
            <table id="upcoming-competitions-table" class="table-style">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Zeitpunkt</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </section>


    <script>
        const spinner = document.getElementById('spinner');
        const progressText = document.getElementById('evaluation-text');
        const progressBar = document.getElementById('evaluation-progressbar');

        document.addEventListener("DOMContentLoaded", () => {
            getCompEvaluationProgress()
            displayUpcomingCompetitions()
        });

        async function getCompEvaluationProgress() {
            try {
                spinner.style.display = 'block';
                const response = await fetch("../../Helper/get_comp_evaluation_progress.php").then(r => r.json());
                const completed = response[0];
                const total = response[1];
                const progress = Math.min(Math.round((completed / total) * 100), 100);

                progressText.textContent = `Es wurden ${completed} von ${total} Stationen ausgewertet.`;
                progressBar.style.setProperty('--value', progress);
                progressBar.setAttribute('aria-valuenow', progress);
                progressBar.classList.remove('hidden');
            } catch (error) {
                console.error("Error fetching competition evaluation states:", error);
            } finally {
                spinner.style.display = 'none';
            }
        }

        async function displayUpcomingCompetitions() {
            let allComps = <?php echo json_encode($_SESSION['overview_competitions'] ?? CompetitionController::getInstance()->getAll()); ?>;
            const tableBody = document.getElementById('upcoming-competitions-table').querySelector('tbody');
            const currentTime = new Date();

            allComps
                .filter(competition => new Date(competition.date) > currentTime) // Zukünftige Stationen
                .sort((a, b) => new Date(a.date) - new Date(b.date)) // Zeitlich ordnen
                .forEach(competition => {
                    const row = document.createElement('tr');
                    const nameCell = document.createElement('td');
                    const dateCell = document.createElement('td');

                    nameCell.textContent = competition.name;
                    dateCell.textContent = new Date(competition.date).toLocaleString('de-DE');

                    row.appendChild(nameCell);
                    row.appendChild(dateCell);
                    tableBody.appendChild(row);
                });
        }
    </script>
</body>

</html>