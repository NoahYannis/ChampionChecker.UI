<?php
// Hier werden die Klassen- und Einzelergebnisse angezeigt. Nutzer mit Admin-Status können Ergebnisse bearbeiten und löschen.

require '../../vendor/autoload.php';
session_start();

use MVC\Controller\CompetitionResultController;
use MVC\Controller\CompetitionController;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;
use MVC\Controller\UserController;
use MVC\Model\CompetitionResult;
use MVC\Model\Role;

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

    if ($patchResult) {
        unset($_SESSION['competitionResultsTimestamp']); // Home-Cache zurücksetzen, da sich Ergebnisse geändert haben.
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['compResId'])) {
        echo json_encode(['success' => false, 'message' => 'Die Wettbewerbs-ID wurde nicht übermittelt.']);
        exit;
    }

    $compResId = $_GET['compResId'];
    $compResController = new CompetitionResultController();
    $deleteResult = $compResController->delete($compResId);

    if ($deleteResult['success'] === true) {
        unset($_SESSION['results_competitionResultsTimestamp']);
        echo json_encode(['success' => true, 'message' => 'Das Ergebnis wurde erfolgreich entfernt.']);
    } else {
        $errorMessage = addslashes(htmlspecialchars($deleteResult["error"], ENT_NOQUOTES, 'UTF-8'));
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
    exit;
}


/** Gibt alle Stationsergebnisse zurück. 
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

    $competitionResults = CompetitionResultController::getInstance()->getAll();

    $classResults = [];
    $studentResults = [];

    foreach ($competitionResults as $result) {
        if ($result->getClassId() !== null) {
            $classResults[] = $result;
        } else {
            $studentResults[] = $result;
        }
    }

    $_SESSION['results_competitionResults_class'] = $classResults;
    $_SESSION['results_competitionResults_students'] = $studentResults;
    $_SESSION['results_competitionResultsTimestamp'] = time();
    $_SESSION['results_competitionResults'] = $competitionResults;

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


function printCompetitionResult()
{
    if (isset($_SESSION['results_competitionResultsTimestamp'])) {
        echo "<p class='timestamp-container'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['results_competitionResultsTimestamp']) . "<br></p>";
    }

    echo "<div id='result-message' class='result-message hidden'></div>";

    // Bearbeitung für Lehrer und Admins
    if (UserController::getInstance()->getRole()->value > 1) { 
        echo
        '<div class="button-container">
        <button class="circle-button edit-button" id="edit-button">
        <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden" id="cancel-button">
        <i class="fas fa-times"></i>
        </button>
        <div class="spinner" id="spinner"></div>
        </div>';
    }

    // Klassen-Ergebnisse
    echo "<h2>Klassen-Ergebnisse:</h2>";
    echo "<table id='results-table-classes' class='table-style'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th onclick='filterTable(0, \"results-table-classes\")'>Station</th>";
    echo "<th onclick='filterTable(1, \"results-table-classes\")'>Klasse</th>";
    echo "<th onclick='filterTable(2, \"results-table-classes\")'>Punkte</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    $classResults = $_SESSION['results_competitionResults_class'] ?? [];

    foreach ($classResults as $result) {
        echo "<tr>";
        echo "<td data-id=\"{$result->getId()}\">" . getCompetitionName(competitionId: $result->getCompetitionId()) . "</td>";
        echo "<td>" . ClassController::getInstance()->getClassName($result->getClassId()) . "</td>";
        $pointsAchieved = htmlspecialchars($result->getPointsAchieved());
        echo "<td data-points=\"$pointsAchieved\"><span class=\"td-content\">$pointsAchieved</span></td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";


    // Einzelergebnisse nur ab Rolle Lehrkraft sichtbar
    if (UserController::getInstance()->getRole()->value > 1) {
        echo "<h2>Schüler-Ergebnisse:</h2>";
        echo "<table id='results-table-students' class='table-style'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th onclick='filterTable(0, \"results-table-students\")'>Station</th>";
        echo "<th onclick='filterTable(1, \"results-table-students\")'>Schüler</th>";
        echo "<th onclick='filterTable(2, \"results-table-students\")'>Klasse</th>";
        echo "<th onclick='filterTable(3, \"results-table-students\")'>Punkte</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $studentResults = $_SESSION['results_competitionResults_students'] ?? [];

        foreach ($studentResults as $result) {
            echo "<tr>";
            echo "<td data-id=\"{$result->getId()}\">" . getCompetitionName(competitionId: $result->getCompetitionId()) . "</td>";
            $student = StudentController::getInstance()->getById($result->getStudentId());
            $studentName = $student ? "{$student->getFirstName()} {$student->getLastName()}" : "???";
            echo "<td>" . htmlspecialchars($studentName) . "</td>";
            echo "<td>" . ClassController::getInstance()->getClassName($student->getClassId()) . "</td>";
            $pointsAchieved = htmlspecialchars($result->getPointsAchieved());
            echo "<td data-points=\"$pointsAchieved\"><span class=\"td-content\">$pointsAchieved</span></td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
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

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ergebnisübersicht</title>
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/results.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <header>
        <h1>Ergebnisübersicht</h1>
    </header>

    <!-- Tabelle mit Punktzahlen -->
    <section>
        <?php printCompetitionResult(); ?>
    </section>

    <script>
        let sortDirections = {};
        let isEditing = false;
        let storedValues = new Map();
        let changedScores = [];
        let pointsCells = document.querySelectorAll('td[data-points]');

        const editButton = document.getElementById("edit-button");
        const editButtonIcon = document.querySelector(".edit-button i");
        const cancelButton = document.getElementById("cancel-button");

        const classResultTable = document.getElementById("results-table-classes");
        const tbodyClasses = classResultTable.getElementsByTagName("tbody")[0];
        const headerRowClasses = classResultTable.getElementsByTagName("tr")[0];
        const rowsClasses = Array.from(tbodyClasses.getElementsByTagName("tr"))

        const studentResultsTable = document.getElementById("results-table-students");
        const tbodyStudents = studentResultsTable.getElementsByTagName("tbody")[0];
        const headerRowStudents = studentResultsTable.getElementsByTagName("tr")[0];
        const rowsStudents = Array.from(tbodyStudents.getElementsByTagName("tr"))

        const spinner = document.getElementById('spinner');

        editButton.addEventListener('click', () => toggleEditState());

        cancelButton.addEventListener("click", () => {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
            }
        })


        async function toggleEditState(wasCanceled = false) {
            isEditing = !isEditing;
            editButtonIcon.classList.toggle('fa-pencil-alt');
            editButtonIcon.classList.toggle('fa-save');
            cancelButton.classList.toggle("hidden");

            if (isEditing) {
                enterEditState();
            } else {
                exitEditState(wasCanceled);
                if (!wasCanceled) {
                    await saveChangedScores(changedScores);
                }
                changedScores = [];
            }
        }


        function enterEditState() {
            let deleteHeaderClasses = document.createElement("th");
            let deleteHeaderStudents = document.createElement("th");

            pointsCells.forEach(cell => {
                const row = cell.parentElement;
                const currentPoints = cell.dataset.points;
                const compResId = cell.parentElement.querySelector("td[data-id]").dataset.id;

                storedValues.set(row, [compResId, currentPoints]); // Zu jeder Zeile die Stations-ID und Punktzahl speichern.

                cell.innerHTML = `<input type="text" value="${currentPoints}" class="edit-input" maxlength="2">`;

                let deleteColumn = document.createElement("td");
                deleteColumn.innerHTML = `
                <button class="circle-button delete-button">
                    <i class="fas fa-trash"></i>
                </button>`;
                cell.parentElement.appendChild(deleteColumn);

                let deleteButton = cell.parentElement.querySelector('.delete-button');
                deleteButton.addEventListener('click', () => {
                    const confirmation = confirm('Sind Sie sicher, dass Sie dieses Ergebnis löschen möchten?');
                    if (confirmation) {
                        const table = cell.closest('table');
                        deleteCompResult(compResId, table, cell.parentElement.rowIndex);
                    }
                });
            });

            headerRowClasses.appendChild(deleteHeaderClasses);
            headerRowStudents.appendChild(deleteHeaderStudents);
        }


        function exitEditState(wasCanceled = false) {
            pointsCells.forEach(cell => {
                const row = cell.parentElement;
                const storedValue = storedValues.get(row);

                if (!storedValue) {
                    return;
                }

                const storedScore = storedValue[1];

                if (wasCanceled) {
                    cell.innerHTML = `<span>${storedScore}</span>`;
                } else {
                    const inputValue = cell.querySelector('input')?.value;

                    if (!wasCanceled && checkIfScoreWasModified(inputValue, storedValue)) {
                        const compResId = row.querySelector("td[data-id]").dataset.id;
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

            storedValues.clear();
            headerRowClasses.querySelector("th:last-child").remove();
            headerRowStudents.querySelector("th:last-child").remove();
            document.querySelectorAll(".delete-button").forEach(b => b.parentElement.remove());
        }


        async function deleteCompResult(compResId, table, rowIndex) {
            spinner.style.display = 'inline-block';
            editButton.disabled = true;

            try {
                const response = await fetch(`results.php?compResId=${compResId}`, {
                    method: 'DELETE',
                });

                const data = await response.json();

                if (data.success) {
                    const row = table.rows[rowIndex];

                    // Gelöschte Zeile aus Tabelle und Cache entfernen.
                    if (row) {
                        storedValues.delete(row);
                        row.remove();
                        pointsCells = document.querySelectorAll('td[data-points]');
                    }
                }

                showResultMessage(data.message, data.success);
            } catch (error) {
                console.error('Error:', error);
            } finally {
                spinner.style.display = 'none';
                editButton.disabled = false;
            }
        }


        function filterTable(columnIndex, tableId) {
            if (isEditing) {
                return;
            }

            let table = document.getElementById(tableId);
            let tbody = table.getElementsByTagName("tbody")[0];
            let rows = Array.from(tbody.getElementsByTagName("tr"));

            // Richtung togglen
            sortDirections[columnIndex] = sortDirections[columnIndex] === "asc" ? "desc" : "asc";
            let sortOrder = sortDirections[columnIndex];

            rows.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName("td")[columnIndex].innerText.trim();
                let cellB = rowB.getElementsByTagName("td")[columnIndex].innerText.trim();

                let numA = parseInt(cellA);
                let numB = parseInt(cellB);

                if (!isNaN(numA) && !isNaN(numB)) {
                    return sortOrder === "asc" ? numA - numB : numB - numA;
                } else {
                    return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });

            rows.forEach(row => tbody.appendChild(row));
        }


        async function saveChangedScores(changedScores) {
            if (changedScores.length === 0) {
                return;
            }

            const invalidScore = changedScores.some(score => isNaN(score.pointsAchieved));
            if (invalidScore) {
                alert('Punktzahlen müssen ein numerischer Wert von 0 bis 100 sein.');
                return;
            }

            const scoreJSON = JSON.stringify(changedScores);
            spinner.style.display = 'inline-block';
            editButton.disabled = true;

            try {
                const response = await fetch('results.php', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: scoreJSON
                });

                const data = await response.json();
                showResultMessage(data.message, data.success);
            } catch (error) {
                console.error('Error:', error);
            } finally {
                spinner.style.display = 'none';
                editButton.disabled = false;
            }
        }


        // Überprüft, ob eine Punktzahl bearbeitet wurde. storedScore[0] speichert die Stations-ID, storedScore[1] den gecachten Wert dazu.
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