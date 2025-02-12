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
    <link rel="stylesheet" type="text/css" href="../../../styles/solo_results.css" />
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
                <th>Vorname</th>
                <th>Nachname</th>
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
                    <td><?= htmlspecialchars($participant['firstName']) ?></td>
                    <td><?= htmlspecialchars($participant['lastName']) ?></td>
                    <td><?= $class ?></td>
                    <td class="attempt-cell">
                        <div class="flex-container row">
                            <label>1. Versuch:</label>
                            <input type="number" min="0" value="0">
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        let attemptsSelection = document.getElementById("attempts-selection");
        let unitSelection = document.getElementById("unit-selection");

        let attemptTable = document.getElementById("attempt-table");
        let attemptCells = Array.from(attemptTable.getElementsByClassName("attempt-cell"));


        attemptsSelection.addEventListener("change", () => {
            let count = attemptsSelection.selectedOptions[0].value;
            createAttemptInputs(count)
        });

        unitSelection.addEventListener("change", () => {
            let unit = unitSelection.selectedOptions[0].value;
            createUnitInputs(unit);
        })

        function createAttemptInputs(count) {
            attemptCells.forEach(cell => {
                cell.innerHTML = "";

                for (let i = 1; i <= count; i++) {
                    let flexContainer = document.createElement("div");
                    flexContainer.classList.add("flex-container", "row");

                    let label = document.createElement("label");
                    label.textContent = i + ". Versuch:";

                    let input = document.createElement("input");
                    input.type = "number";
                    input.min = input.value = "0";

                    flexContainer.appendChild(label);
                    flexContainer.appendChild(input);

                    cell.appendChild(flexContainer);
                }
            });
        }

        function createUnitInputs(unit) {
            attemptCells.forEach(c => {
                c.querySelectorAll("input").forEach(i => {
                    i.type = (unit === "z") ? "time" : "number";
                    i.value = (unit === "z") ? "00:00" : "0";
                });
            });
        }
    </script>
</body>

</html>