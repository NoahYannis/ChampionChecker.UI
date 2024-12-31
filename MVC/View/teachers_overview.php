<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

include 'nav.php';


use MVC\Controller\TeacherController;
use MVC\Controller\ClassController;

$teacherController = TeacherController::getInstance();
$classController = ClassController::getInstance();

function loadAllTeachers($cacheDuration = 300): array
{
    global $teacherController;

    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['overview_teachers']) && isset($_SESSION['overview_teachers_timestamp'])) {
        if ((time() - $_SESSION['overview_teachers_timestamp']) < $cacheDuration) {
            return $_SESSION['overview_teachers'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $teachers = $teacherController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['overview_teachers'] = $teachers;
    $_SESSION['overview_teachers_timestamp'] = time();

    return $teachers;
}



function printTeachers($teachers)
{
    global $classController;

    echo "<table id='teacherTable' class='table-style'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th onclick='filterTable(0)'>Nachname</th>";
    echo "<th onclick='filterTable(1)'>Vorname</th>";
    echo "<th onclick='filterTable(2)'>Kürzel</th>";
    echo "<th onclick='filterTable(3)'>Klassen</th>";
    echo "<th onclick='filterTable(4)'>Sonstige Informationen</th>";
    echo "<th onclick='filterTable(5)'>Turnier-Teilnahme</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($teachers as $teacher) {
        echo "<tr>";
        echo "<td data-column='Nachname'><div class='td-content'>" . htmlspecialchars($teacher->getLastName()) . "</div></td>";
        echo "<td data-column='Vorname'><div class='td-content'>" . htmlspecialchars($teacher->getFirstName()) . "</div></td>";
        echo "<td data-column='Kürzel'><div class='td-content'>" . htmlspecialchars($teacher->getShortCode()) . "</div></td>";

        $classes = $teacher->getClasses() ?? [];
        $classNames = [];

        foreach ($classes as $class) {
            $className = $classController->getClassName($class['id']);
            if ($className) {
                $classNames[] = htmlspecialchars($className);
            }
        }

        echo "<td data-column='Klassen'><div class='td-content'>" . (!empty($classNames) ? implode(', ', $classNames) : '-') . "</div></td>";
        echo "<td data-column='Sonstiges'><div class='td-content'>" . (empty($teacher->getAdditionalInfo()) ? '-' : htmlspecialchars($teacher->getAdditionalInfo())) . "</div></td>";
        echo "<td data-column='Teilnahme'><div class='td-content'><span class='status-circle " . ($teacher->getIsParticipating() ? "green" : "red") . "'></span></div></td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    if (isset($_SESSION['overview_teachers_timestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['overview_teachers_timestamp']) . "<br></p>";
    }
}


$teachers = loadAllTeachers();

// Lehrer nach Nachnamen sortieren
usort($teachers, function ($teacherA, $teacherB) {
    return strcmp($teacherA->getLastName(), $teacherB->getLastName());
});
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/teacher_overview.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Lehrerverwaltung</title>
</head>

<body>
    <header>
        <h1>Lehrerverwaltung</h1>
    </header>

    <div class="button-container">
        <button class="circle-button add-button" onclick="window.location.href='add_teachers_overview.php?mode=manual'">
            <i class="fas fa-plus"></i>
        </button>
        <button class="circle-button edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <section>
        <?php printTeachers($teachers); ?>
    </section>

    <script>
        let isEditing = false;
        let sortDirections = {};
        let storedValues = [];
        let changedTeachers = [];

        const editButton = document.querySelector('.edit-button i');
        const cancelButton = document.querySelector(".cancel-button");
        const table = document.getElementById("teacherTable");
        const tbody = table.getElementsByTagName("tbody")[0];
        const rows = Array.from(tbody.getElementsByTagName("tr"));

        document.querySelector('.edit-button').addEventListener('click', function() {
            toggleEditState();
            cancelButton.classList.toggle("hidden");
        });

        document.querySelector('.cancel-button').addEventListener('click', function() {
            toggleEditState(true);
            cancelButton.classList.toggle("hidden");
        });


        function toggleEditState(wasCanceled = false) {
            isEditing = !isEditing;
            toggleEditButtonIcon();

            if (isEditing) {
                displayEditInputs();
            } else {
                exitEditState(wasCanceled);
            }
        }

        function toggleEditButtonIcon() {
            editButton.classList.toggle('fa-pencil-alt');
            editButton.classList.toggle('fa-save');
        }


        // Zeileninhalt innerhalb von Input-Elementen anzeigen.
        function displayEditInputs() {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");

                let lastName = cells[0].querySelector('.td-content').innerText;
                let firstName = cells[1].querySelector('.td-content').innerText;
                let shortCode = cells[2].querySelector('.td-content').innerText;
                let classes = cells[3].querySelector('.td-content').innerText;
                let additionalInfo = cells[4].querySelector('.td-content').innerText;
                let isParticipating = cells[5].querySelector('.td-content').querySelector('.status-circle').classList.contains('green');

                // Werte zwischenspeichern, falls die Bearbeitung abgebrochen wird.
                storedValues[row.rowIndex] = [lastName, firstName, shortCode, classes, additionalInfo, isParticipating];

                cells[0].innerHTML = `<input type="text" value="${lastName}">`;
                cells[1].innerHTML = `<input type="text" value="${firstName}">`;
                cells[2].innerHTML = `<input type="text" value="${shortCode}">`;
                cells[3].innerHTML = `<input type="text" value="${classes}">`;
                cells[4].innerHTML = `<input type="text" value="${additionalInfo}">`;
                cells[5].innerHTML = `<input type="checkbox" ${isParticipating ? 'checked' : ''}>`;
            });
        }

        // Input-Elemente durch Text ersetzen.
        function exitEditState(wasCanceled = false) {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");
                let storedRow = storedValues[row.rowIndex];

                let lastName = wasCanceled && storedRow ? storedRow[0] : cells[0].querySelector('input').value;
                let firstName = wasCanceled && storedRow ? storedRow[1] : cells[1].querySelector('input').value;
                let shortCode = wasCanceled && storedRow ? storedRow[2] : cells[2].querySelector('input').value;
                let classes = wasCanceled && storedRow ? storedRow[3] : cells[3].querySelector('input').value;
                let additionalInfo = wasCanceled && storedRow ? storedRow[4] : cells[4].querySelector('input').value;
                let isParticipating = wasCanceled && storedRow ? storedRow[5] : cells[5].querySelector('input').checked;

                // Prüfen, ob Zeile geändert wurde
                if (checkIfRowWasModified(row, storedRow)) {
                    let changedTeacher = {
                        lastName: lastName,
                        firstName: firstName,
                        shortCode: shortCode,
                        classes: classes,
                        additionalInfo: additionalInfo,
                        isParticipating: isParticipating
                    };
                    changedTeachers.push(changedTeacher);
                }

                lastName = lastName || "-";
                firstName = firstName || "-";
                shortCode = shortCode || "-";
                classes = classes || "-";
                additionalInfo = additionalInfo || "-";

                cells[0].innerHTML = `<div class='td-content'>${lastName}</div>`;
                cells[1].innerHTML = `<div class='td-content'>${firstName}</div>`;
                cells[2].innerHTML = `<div class='td-content'>${shortCode}</div>`;
                cells[3].innerHTML = `<div class='td-content'>${classes}</div>`;
                cells[4].innerHTML = `<div class='td-content` + (additionalInfo === '-' ? ' empty' : '') + `'>${additionalInfo}</div>`;
                cells[5].innerHTML = `<div class='td-content'><span class='status-circle ${isParticipating ? 'green' : 'red'}'></span></div>`;
            });

            storedValues = [];
            console.log(changedTeachers);
        }


        function checkIfRowWasModified(row, storedRow) {
            let cells = row.getElementsByTagName("td");
            for (let i = 0; i < cells.length; i++) {
                const inputElement = cells[i].querySelector('input, textarea');
                const storedValue = storedRow[i];
                let inputValue;

                if (inputElement && inputElement.type === 'checkbox') {
                    inputValue = inputElement.checked;
                } else if (inputElement && inputElement.tagName === 'TEXTAREA') {
                    inputValue = inputElement.value;
                } else if (inputElement) {
                    inputValue = inputElement.value;
                }

                // Nur geänderte Datensätze speichern.
                if (inputValue !== storedValue) {
                    return true;
                }
            }
            return false;
        }

        function filterTable(columnIndex) {
            // Richtung togglen
            sortDirections[columnIndex] = sortDirections[columnIndex] === "asc" ? "desc" : "asc";
            let sortOrder = sortDirections[columnIndex];

            rows.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName("td")[columnIndex].innerText.trim();
                let cellB = rowB.getElementsByTagName("td")[columnIndex].innerText.trim();

                // Turnier-Teilnahme
                if (columnIndex === 5) {
                    let circleA = rowA.querySelector('.status-circle');
                    let circleB = rowB.querySelector('.status-circle');
                    let isGreenA = circleA.classList.contains('green') ? 1 : 0;
                    let isGreenB = circleB.classList.contains('green') ? 1 : 0;
                    return sortOrder === "asc" ? isGreenA - isGreenB : isGreenB - isGreenA;
                }

                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        }
    </script>

</body>

</html>