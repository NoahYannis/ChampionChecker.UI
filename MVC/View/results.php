<?php
require '../../vendor/autoload.php';
session_start();

use MVC\Controller\CompetitionResultController;
use MVC\Controller\CompetitionController;
use MVC\Controller\ClassController;
use MVC\Model\CompetitionResult;


if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $changedScores = file_get_contents('php://input');
    $scoreData = json_decode($changedScores, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }

    $compResController = new CompetitionResultController();
    $patchResult = true;

    foreach ($scoreData as $changedScore) {
        $data = ['pointsAchieved' => $changedScore["pointsAchieved"]];
        $result = $compResController->patch($changedScore["compResId"], $data, "replace");
        $patchResult &= $result["success"];
    }

    $response = [
        'success' => $patchResult,
        'message' => $patchResult ? 'Änderungen erfolgreich gespeichert.' : 'Einige Änderungen konnten nicht übernommen werden.'
    ];

    echo json_encode($response);
    exit;
}


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

    if (isset($_SESSION['results_competitionResultsTimestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['results_competitionResultsTimestamp']) . "<br></p>";
    }

    echo "<div id='result-message' class='result-message hidden'></div>";

    if (isset($_COOKIE['ChampionCheckerCookie'])) {
        echo
        '<div class="button-container">
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
        echo "<td data-id=\"{$result->getId()}\">" . getCompetitionName(competitionId: $result->getCompetitionId()) . "</td>";
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

include 'nav.php';
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

        editButton.addEventListener('click', () => toggleEditState());

        cancelButton.addEventListener("click", () => {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
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
                    saveChangedScores(changedScores);
                }
                changedScores = [];
            }
        }

        function enterEditState() {
            pointsCells.forEach(cell => {
                const currentPoints = cell.dataset.points;
                const rowIndex = cell.parentElement.rowIndex;
                const compId = cell.parentElement.querySelector("td[data-id]").dataset.id;
                storedValues[rowIndex] = [compId, currentPoints];
                cell.innerHTML = `<input type="text" value="${currentPoints}" class="edit-input">`;
            });
        }

        function exitEditState(wasCanceled = false) {
            pointsCells.forEach(cell => {
                let storedValue = storedValues[cell.parentElement.rowIndex];
                let storedScore = storedValues[cell.parentElement.rowIndex][1];

                if (wasCanceled) {
                    cell.innerHTML = `<span>${storedScore}</span>`;
                } else {
                    const inputValue = cell.querySelector('input')?.value;

                    if (checkIfScoreWasModified(inputValue, storedValue)) {
                        const compResId = cell.parentElement.querySelector("td[data-id]").dataset.id;
                        const scoreData = {
                            compResId,
                            pointsAchieved: inputValue
                        };
                        changedScores.push(scoreData);
                    }


                    cell.innerHTML = `<span>${inputValue}</span>`;
                    cell.dataset.points = inputValue;
                }
            });

            storedValues = {};
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

        function saveChangedScores(changedScores) {
            if (changedScores.length === 0) {
                return;
            }

            const invalidScore = changedScores.some(score => isNaN(score.pointsAchieved));
            if (invalidScore) {
                alert('Punktzahlen müssen ein numerischer Wert von 0 bis 100 sein.');
                return;
            }

            const scoreJSON = JSON.stringify(changedScores);

            fetch('results.php', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: scoreJSON
                }).then(response => response.json())
                .then(data => {
                    showResultMessage(data.message, data.success);
                }).catch(error => console.error('Error:', error));
        }

        // Überprüft, ob eine Punktzahl bearbeitet wurde. storedScore[0] speichert die Wettbewerbs-ID, storedScore[1] den gecachten Wert dazu.
        function checkIfScoreWasModified(inputValue, storedScore) {
            return inputValue !== storedScore[1];
        }

        function showResultMessage(message, isSuccess = true) {
            const resultMessage = document.getElementById('result-message');
            resultMessage.textContent = message;
            resultMessage.style.color = isSuccess ? 'green' : 'red';
            resultMessage.classList.remove('hidden');

            setTimeout(() => {
                resultMessage.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>

</html>