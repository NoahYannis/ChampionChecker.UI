<?php

require '../../../vendor/autoload.php';

use MVC\Controller\ClassController;
use MVC\Controller\StudentController;


$inputJSON = file_get_contents('php://input');
$studentParticipants = json_decode($inputJSON, true);

function getStudentClassName($id)
{
    $student = $_SESSION['students'][$id] ?? StudentController::getInstance()->getById($id);
    return ClassController::getInstance()->getClassName($student->getClassId());
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Stationsauswertung</title>
</head>

<body>
    <div class="flex-container row">
        <div>
            <label for="attempts-selection">Versuche:</label>
            <select class="attempts-selection" id="attempts-selection">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
            </select>
        </div>
        <div>
            <label for="unit-selection">Einheit:</label>
            <select id="unit-selection">
                <option value="p">Punkte</option>
                <option value="m">Meter</option>
                <option value="z">Zeit</option>
            </select>
        </div>
    </div>

    <table id="attempt-table" class="table-style">
        <thead>
            <tr>
                <th>#</th>
                <th>Nachname</th>
                <th>Vorname</th>
                <th>Klasse</th>
                <th>Ergebnis</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            foreach ($studentParticipants as $id => $participant):
                $class = getStudentClassName($id);
            ?>
                <tr>
                    <td data-id="<?= $id ?>"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($participant['lastName']) ?></td>
                    <td><?= htmlspecialchars($participant['firstName']) ?></td>
                    <td><?= $class ?></td>
                    <td class="attempt-cell"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr class="horizontal-separator">

    <h2>Auswertung:</h2>

    <table id="evaluation-table" class="table-style">
        <thead>
            <tr>
                <th>Platz</th>
                <th>Nachname</th>
                <th>Vorname</th>
                <th>Klasse</th>
                <th>Ergebnis</th>
                <th>Punkte</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script>
        let attemptsSelection = document.getElementById("attempts-selection");
        let unitSelection = document.getElementById("unit-selection");

        let attemptTable = document.getElementById("attempt-table");
        let attemptRows = document.querySelectorAll("#attempt-table tbody tr");
        let attemptCells = Array.from(attemptTable.getElementsByClassName("attempt-cell"));

        let evaluationTableBody = document.querySelector("#evaluation-table tbody");

        let pointsDistribution = [7, 5, 4, 3, 2, 1];
        let unit = "p"; // Punkte


        // Standardmäß ein Versuch pro Schüler
        displayAttemptInputs(1);
        updateEvaluationTable();

        attemptsSelection.addEventListener("change", () => {
            let count = attemptsSelection.selectedOptions[0].value;
            displayAttemptInputs(count)
        });

        unitSelection.addEventListener("change", () => {
            unit = unitSelection.selectedOptions[0].value;
            let bestAttempts = document.querySelectorAll(".best-attempt");
            bestAttempts.forEach(input => input.classList.remove("best-attempt"));
            createUnitInputs(unit);
        })

        function displayAttemptInputs(count) {
            attemptCells.forEach(cell => {
                let currentInputs = cell.querySelectorAll("input");
                let inputCount = currentInputs.length;


                while (inputCount > count) {
                    cell.removeChild(cell.lastChild);
                    inputCount--;
                }

                while (inputCount < count) {
                    let flexContainer = document.createElement("div");

                    flexContainer.classList.add(
                        "flex-container", window.matchMedia("(width < 37rem)").matches 
                        ?   "column" : "row");

                    let label = document.createElement("label");
                    label.textContent = (inputCount + 1) + ". Versuch:";

                    let input = document.createElement("input");
                    input.type = (unit === "z") ? "time" : "number";
                    input.value = (unit === "z") ? "00:00" : "";
                    input.min = (unit === "z") ? "00:00" : "0";
                    input.addEventListener("input", () => {
                        determineBestAttempt(unit, cell);
                        updateEvaluationTable();
                    });

                    flexContainer.appendChild(label);
                    flexContainer.appendChild(input);

                    cell.appendChild(flexContainer);
                    inputCount++;
                }

                let cellHasResults = Array.from(currentInputs).some(input =>
                    (unit === "z" && input.value !== "00:00") ||
                    (unit !== "z" && input.value !== "")
                );

                if (cellHasResults) {
                    determineBestAttempt(unit, cell);
                }
            });

            updateEvaluationTable();
        }

        function createUnitInputs(unit) {
            attemptCells.forEach(c => {
                let inputs = c.querySelectorAll("input");
                inputs.forEach(i => {
                    i.type = (unit === "z") ? "time" : "number";
                    i.value = (unit === "z") ? "00:00" : "";
                    i.min = (unit === "z") ? "00:00" : "0";
                    i.addEventListener("input", () => {
                        determineBestAttempt(unit, c);
                    });
                });
            });

            updateEvaluationTable();
        }

        function determineBestAttempt(unit, cell) {
            let attemptInputs = Array.from(cell.querySelectorAll("input"));
            attemptInputs.forEach(input => input.classList.remove("best-attempt"));

            let bestAttemptInput;

            if (unit === "z") {
                bestAttemptInput = attemptInputs.reduce((best, input) => {
                    let [bestMinutes, bestSeconds] = best.value.split(":").map(Number);
                    let [inputMinutes, inputSeconds] = input.value.split(":").map(Number);
                    return (inputMinutes < bestMinutes || (inputMinutes === bestMinutes && inputSeconds < bestSeconds)) ? input : best;
                }, attemptInputs[0]);
            } else {
                bestAttemptInput = attemptInputs.reduce((best, input) =>
                    (!best.value || (input.value && parseFloat(input.value) > parseFloat(best.value))) ? input : best, attemptInputs[0]
                );
            }

            if (bestAttemptInput) {
                bestAttemptInput.classList.add("best-attempt");
            }
        }


        function updateEvaluationTable() {
            let currentResults = [];

            // Bisherigen Stand abfragen
            attemptRows.forEach((row, index) => {
                let bestAttempt = row.querySelector(".best-attempt");
                let result = bestAttempt ? bestAttempt.value : "";
                currentResults.push({
                    index: index,
                    result: result,
                    id: row.querySelector("td:first-child").dataset.id, // Schüler-ID
                    lastName: row.querySelector("td:nth-child(2)").textContent,
                    firstName: row.querySelector("td:nth-child(3)").textContent,
                    className: row.querySelector("td:nth-child(4)").textContent
                });
            });

            // Schüler nach Ergebnis neu sortieren
            currentResults.sort((a, b) => {
                if (unit === "z") {
                    let [aMinutes, aSeconds] = a.result.split(":").map(Number);
                    let [bMinutes, bSeconds] = b.result.split(":").map(Number);
                    return (aMinutes - bMinutes) || (aSeconds - bSeconds);
                } else {
                    return parseFloat(b.result) - parseFloat(a.result);
                }
            });

            // Tabelle aktualisieren
            currentResults.forEach((result, index) => {
                let row = evaluationTableBody.querySelector(`tr:nth-child(${index + 1})`);
                if (!row) {
                    row = document.createElement("tr");
                    evaluationTableBody.appendChild(row);
                }

                row.innerHTML = `
                    <td data-id="${result.id}">${index + 1}</td>
                    <td>${result.lastName}</td>
                    <td>${result.firstName}</td>
                    <td>${result.className}</td>
                    <td>${result.result}</td>
                    <td>${pointsDistribution[index] || 0}</td>
                `;
            });
        }

        window.addEventListener("resize", () => {
            document.querySelectorAll(".flex-container").forEach(fc =>
                fc.classList.toggle("row", !window.matchMedia("(width < 37rem)").matches)
            );
        });
    </script>
</body>

</html>