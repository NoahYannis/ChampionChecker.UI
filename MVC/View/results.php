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

    if (isset($_SESSION['competitionResultsTimestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['competitionResultsTimestamp']) . "<br></p>";
    }

    echo "<div id='result-message' class='result-message hidden'></div>";

    if (isset($_COOKIE['ChampionCheckerCookie'])) {
        echo '<div class="button-container">
        <button class="circle-button edit-button" id="edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden" id="cancel-button">
            <i class="fas fa-times"></i>
        </button>
    </div>';
    }



    echo "<table id='results-table' class='table-style'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th onclick='filterTable(0)'>Wettbewerb</th>";
    echo "<th onclick='filterTable(1)'>Klasse</th>";
    echo "<th onclick='filterTable(2)'>Punkte</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($competitionResults as $result) {
        echo "<tr>";
        echo "<td>" . getCompetitionName($result->getCompetitionId()) . "</td>";
        echo "<td>" . $classController->getClassName($result->getClassId()) . "</td>";
        $pointsAchieved = htmlspecialchars($result->getPointsAchieved());
        echo "<td data-points=\"$pointsAchieved\"><span class=\"td-content\">$pointsAchieved</span></td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

$competitionResults = loadCompetitionResults();

// Ergebnisse alphabetisch sortieren
usort($competitionResults, function ($resultA, $resultB) {
    return strcmp(
        getCompetitionName($resultA->getCompetitionId()),
        getCompetitionName($resultB->getCompetitionId())
    );
});
?>

<!-- TODO: Filtern, mehr Infos anzeigen -->

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/results.css" />
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

    <script>
        let sortDirections = {};
        let isEditing = false;
        let storedValues = [];
        let changedScores = [];

        const editButton = document.getElementById("edit-button");
        const editButtonIcon = document.querySelector(".edit-button i");
        const cancelButton = document.getElementById("cancel-button");
        const table = document.getElementById("results-table");
        const tbody = table.getElementsByTagName("tbody")[0];
        const headerRow = table.getElementsByTagName("tr")[0];
        const rows = Array.from(tbody.getElementsByTagName("tr"))
        const pointsCells = document.querySelectorAll('td[data-points]');

        editButton.addEventListener("click", toggleEditState);

        cancelButton.addEventListener("click", () => {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
                cancelButton.classList.toggle("hidden");
            }
        })

        function toggleEditState(wasCanceled = false) {
            isEditing = !isEditing;
            editButtonIcon.classList.toggle('fa-pencil-alt');
            editButtonIcon.classList.toggle('fa-save');
            cancelButton.classList.toggle("hidden");


            if (isEditing) {
                enterEditState();
            } else {
                exitEditState(wasCanceled);
                if (!wasCanceled) {
                    // saveChangedSores(changedScores);
                }
                changedScores = [];
            }
        }

        function enterEditState() {
            pointsCells.forEach(cell => {
                const currentPoints = cell.dataset.points;
                const rowIndex = cell.parentElement.rowIndex;

                storedValues[rowIndex] = [currentPoints];
                cell.innerHTML = `<input type="text" value="${currentPoints}" class="edit-input">`;
            });
        }

        function exitEditState(wasCanceled = false) {
            pointsCells.forEach(cell => {
                const rowIndex = cell.parentElement.rowIndex;
                const originalValue = storedValues[rowIndex];

                if (wasCanceled) {
                    cell.innerHTML = `<span>${originalValue}</span>`;
                } else {
                    const input = cell.querySelector('input');
                    const newValue = input.value;
                    cell.innerHTML = `<span class="td-content">${newValue}</span>`;
                    cell.dataset.points = newValue;
                }
            });

            storedValues = {};
        }

        function saveChangedSores(changedScores) {

        }


        function filterTable(columnIndex) {
            let table = document.getElementById("results-table");
            let tbody = table.getElementsByTagName("tbody")[0];
            let rows = Array.from(tbody.getElementsByTagName("tr"));

            // Richtung togglen
            sortDirections[columnIndex] = sortDirections[columnIndex] === "asc" ? "desc" : "asc";
            let sortOrder = sortDirections[columnIndex];

            rows.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName("td")[columnIndex].innerText.trim();
                let cellB = rowB.getElementsByTagName("td")[columnIndex].innerText.trim();
                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        }
    </script>
</body>

</html>