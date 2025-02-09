<?php
require '../../vendor/autoload.php';
session_start();

use MVC\Controller\UserController;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;
use MVC\Controller\CompetitionController;
use MVC\Model\Role;

$userRole = UserController::getInstance()->getRole();

// Für Zugriff mindestens Rolle Lehrkraft
if ($userRole->value < 2) {
    header("Location: home.php");
    exit();
}


// Die GET-Anfrage wird zuerst serverseitig ausgeführt, die Daten sollen aber erst geladen und visualisiert werden sobald die Seite gerendert ist,
// damit der Nutzer schneller etwas sieht und während des Ladens ein Spinner durch JavaScript angezeigt werden kann.Das Custom-Attribut wird
// in der JavaScript Fetch-Anfrage mitgesendet und soll signalisieren, dass die Seite fertig geladen ist und die Daten nun angefordert werden.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_X_CUSTOM_ATTRIBUTE'])) {
    $students = loadAllStudents(300);
    echo json_encode($students);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $patchData = file_get_contents('php://input');
    $changedStudents = json_decode($patchData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }

    $patchSuccess = true;

    foreach ($changedStudents as $student) {
        $competitions = [];
        foreach ($student['competitions'] as $competition) {
            $competitions[(int)$competition['id']] = $competition['name'];
        }

        $data = ['competitions' => $competitions, 'isRegistrationFinalized' => $student['isRegistrationFinalized']];

        $patchResult = StudentController::getInstance()->patch($student['id'], $data, "replace");
        $patchSuccess &= $patchResult['success'] === true;
    }

    echo json_encode([
        'success' => $patchSuccess,
        'message' => $patchSuccess ? 'Änderungen erfolgreich gespeichert.' : 'Einige Änderungen konnten nicht übernommen werden.'
    ]);
    exit;
}


$isAdmin = $userRole == Role::Admin;

function loadAllStudents($cacheDuration = 300): array
{
    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['students']) && isset($_SESSION['overview_students_timestamp'])) {
        if ((time() - $_SESSION['overview_students_timestamp']) < $cacheDuration) {
            return $_SESSION['students'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $students = StudentController::getInstance()->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['students'] = $students;
    $_SESSION['overview_students_timestamp'] = time();

    return $students;
}


function loadAllClassNames($cacheDuration = 300): array
{
    $classNames = [];

    if (isset($_SESSION['classes']) && isset($_SESSION['overview_teachers_timestamp'])) {
        if ((time() - $_SESSION['overview_teachers_timestamp']) < $cacheDuration) {
            foreach ($_SESSION['classes'] as $class) {
                $classNames[$class->getId()] = $class->getName();
            }
            return $classNames;
        }
    }

    $classes = ClassController::getInstance()->getAll();
    $_SESSION['classes'] = $classes;

    foreach ($classes as $class) {
        $classNames[$class->getId()] = $class->getName();
    }

    $_SESSION['overview_teachers_timestamp'] = time();

    return $classNames;
}


function loadAllCompetitionNames(): array
{
    $studentCompetitions = [];
    $students = loadAllStudents(300);

    foreach ($students as $student) {
        foreach ($student->getCompetitions() as $competitionId => $competitionName) {
            $studentCompetitions[$student->getId()][$competitionId] = $competitionName;
        }
    }

    return $studentCompetitions;
}

function setStudentCompetitions($student, $competitions): void
{
    $competitionArray = [];
    foreach ($competitions as $competition) {
        $competitionArray[$competition['id']] = $competition['name'];
    }

    $student->setCompetitions($competitionArray);
}

include 'nav.php';
?>


<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/students_overview.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Schülerübersicht</title>
</head>

<body>
    <header>
        <h1>Schülerübersicht</h1>
    </header>

    <div id="result-message" class="result-message hidden"></div>
    <div id="timestamp-container" class="timestamp-container"></div>
    <div class="button-container">
        <button title="Neue Schüler im CSV-Format importieren" class="circle-button add-button" onclick="window.location.href='import_students_csv.php'">
            <i class="fas fa-plus"></i>
        </button>
        <button class="circle-button edit-button" id="edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden" id="cancel-button">
            <i class="fas fa-times"></i>
        </button>
        <div class="spinner" id="spinner"></div>
    </div>

    <section></section>

    <script>
        let isEditing = false;
        let sortDirections = {};
        let storedValues = [];
        let changedStudents = [];
        let table, tbody, rows;

        const spinner = document.getElementById('spinner');
        const editButton = document.getElementById("edit-button");
        const editButtonIcon = document.querySelector(".edit-button i");
        const cancelButton = document.querySelector(".cancel-button");

        document.addEventListener("DOMContentLoaded", () => loadStudentData());

        editButton.addEventListener('click', function() {
            toggleEditState();
        });

        cancelButton.addEventListener('click', function() {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
                cancelButton.classList.add("hidden");
            }
        });

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
                    saveChangedStudents(changedStudents).then(() => {
                        if (changedStudents.length > 0)
                            setTimeout(() => {
                                location.reload(); // Neu laden, um neue Zeitkonflikte anzuzeigen.
                            }, 300);
                    });
                }
                changedStudents = [];
            }
        }

        async function enterEditState() {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");

                let selector = '.name-badge.competition';
                let competitions = Array.from(row.cells[4].querySelectorAll(selector))
                    .map(element => ({
                        id: element.dataset.id,
                        name: element.textContent.trim()
                    }));

                let isRegistrationFinalized = cells[5].querySelector(".status-circle.green") !== null;

                // Werte zwischenspeichern, falls die Bearbeitung abgebrochen wird.
                storedValues[row.rowIndex] = [competitions.map(p => p.name.trim()).join(","), isRegistrationFinalized];

                cells[4].innerHTML = competitions.map(comp => {
                    return `<span data-id="${comp.id}" data-competition="${comp.name}"
                            class="name-badge competition" 
                            title="Station entfernen">
                                ${comp.name}
                            <i onclick="handleNameBadgeRemoval(this.parentElement, '', this.parentElement.parentElement)" 
                            class="fas fa-times"></i>
                            </span>`;
                }).join(' ');

                cells[5].innerHTML = `<input id='participation' type="checkbox" ${isRegistrationFinalized ? 'checked' : ''}>`;

                let allCompetitions =
                    <?php
                    if (isset($_SESSION['overview_competitions'])) {
                        echo json_encode($_SESSION['overview_competitions']);
                    } else {
                        $allComps = CompetitionController::getInstance()->getAll();
                        echo json_encode($allComps);
                    }
                    ?>;

                let competitionSelect = createCompetitionSelect(allCompetitions, cells[4]);
                cells[4].appendChild(competitionSelect);
            });
        }

        async function exitEditState(wasCanceled = false) {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");
                let storedStudent = storedValues[row.rowIndex];

                let selector = ".name-badge.competition";
                let competitions = wasCanceled ?
                    storedStudent[0].split(",").map(comp => comp.trim()).filter(comp => comp !== "") :
                    Array.from(cells[4].querySelectorAll(selector))
                    .map(element => element.textContent.trim())
                    .filter(comp => comp !== "");

                let compIds = Array.from(cells[4].querySelectorAll(selector))
                    .map(element => element.dataset.id)
                    .filter(id => id != null);

                let competitionObjects = competitions.map((name, index) => ({
                    id: compIds[index] || "",
                    name: name
                }));

                let isRegistrationFinalized = wasCanceled ? storedStudent[1] : cells[5].querySelector("input").checked;
                let currentStudent = [competitions.join(","), isRegistrationFinalized]

                if (!wasCanceled && checkIfStudentChanged(currentStudent, storedStudent)) {
                    let changedStudent = {
                        id: row.cells[1].dataset.id, // Schüler-ID hängt an der LastName-Zelle mit dran
                        isRegistrationFinalized: isRegistrationFinalized,
                        competitions: competitionObjects
                    };
                    changedStudents.push(changedStudent);
                }

                // Stationen-Anzeige
                cells[4].innerHTML = competitions.length === 0 ?
                    '-' :
                    competitionObjects.map(obj => {
                        return `<span data-id="${obj.id}" data-competition="${obj.name}"
                        class="name-badge competition">
                        ${obj.name}
                        </span>`;
                    }).join(' ');


                cells[5].innerHTML = `<div class='td-content'><span class='status-circle ${isRegistrationFinalized ? 'green' : 'red'}'></span></div>`;

            });

            storedValues = [];
        }

        async function loadStudentData() {
            spinner.style.display = "block";

            try {
                const response = await fetch('students_overview.php', {
                        method: 'GET',
                        headers: {
                            'X-Custom-Attribute': 'loadStudentData',
                        }
                    }).then(response => response.json())
                    .then(data => generateStudentTable(data));
            } catch (error) {
                console.error('Error:', error);
            } finally {
                spinner.style.display = "none";
            }
        }


        function createCompetitionSelect(allCompetitions, participantCell) {
            let competitionSelect = document.createElement("select");
            competitionSelect.id = "competition-select";
            competitionSelect.multiple = true;
            competitionSelect.setAttribute("title",
                "Wählen Sie alle Stationen aus, an denen der Schüler teilnimmt. Halten Sie STRG gedrückt, um mehrere Stationen auszuwählen."
            );

            let defaultOption = document.createElement("option");
            defaultOption.textContent = "Stationenauswahl";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            competitionSelect.appendChild(defaultOption);

            allCompetitions.forEach(comp => {
                let option = document.createElement("option");
                option.textContent = comp.name;
                option.value = option.textContent;
                option.dataset.id = comp.id;
                competitionSelect.appendChild(option);
            });

            const nameBadges = participantCell.querySelectorAll('.name-badge.competition');
            const compNames = Array.from(nameBadges).map(badge => badge.dataset.competition);
            for (const option of competitionSelect.options) {
                if (compNames.includes(option.text)) {
                    option.selected = true;
                }
            }

            let previousSelection = Array.from(competitionSelect.selectedOptions);

            competitionSelect.addEventListener('change', () => {
                const selectedComps = Array.from(competitionSelect.selectedOptions);
                toggleCompetitionBadge(allCompetitions, competitionSelect, selectedComps, previousSelection, participantCell);
                previousSelection = selectedComps;
            });

            return competitionSelect;
        }


        async function generateStudentTable(studentJSON) {
            let timeStamp = "<?php echo isset($_SESSION['overview_students_timestamp']) ? date('d.m.Y H:i:s', $_SESSION['overview_students_timestamp']) : ''; ?>";

            if (timeStamp) {
                document.getElementById('timestamp-container').innerHTML = `<p>Zuletzt aktualisiert: ${timeStamp}</p>`;
            }

            table = document.createElement('table');
            table.id = 'student-table';
            table.className = 'table-style';

            thead = document.createElement('thead');
            headerRow = document.createElement('tr');

            const headers = [
                'Vorname',
                'Nachname',
                'Klasse',
                'Geschlecht',
                'Stationen',
                "<abbr title='Die Anmeldung der Schüler-Stationen kann als Zwischenstand oder offiziell gespeichert werden'>Anmeldung</abbr>"
            ];

            headers.forEach((headerText, index) => {
                const th = document.createElement('th');
                th.innerHTML = headerText;
                th.onclick = () => filterTable(index);
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            tbody = document.createElement('tbody');
            const classNames = <?php echo json_encode(loadAllClassNames(300)); ?>;
            const studentCompetitions = <?php echo json_encode(loadAllCompetitionNames()); ?>;
            const timeCollisions = await fetch("../../Helper/check_time_collisions.php").then(r => r.json());

            for (const student of studentJSON) {
                const row = document.createElement('tr');

                const firstNameCell = document.createElement('td');
                firstNameCell.textContent = student.firstName;
                row.appendChild(firstNameCell);

                const lastNameCell = document.createElement('td');
                lastNameCell.textContent = student.lastName;
                lastNameCell.dataset.id = student.id;
                row.appendChild(lastNameCell);

                const classCell = document.createElement('td');
                classCell.textContent = classNames[student.classId] || "Unbekannte Klasse";
                row.appendChild(classCell);

                const genderCell = document.createElement('td');
                const genderIcon = document.createElement('i');

                if (student.isMale === true) {
                    genderIcon.className = 'fas fa-mars';
                } else {
                    genderIcon.className = 'fas fa-venus';
                }

                genderCell.appendChild(genderIcon);
                row.appendChild(genderCell);

                const competitionCell = document.createElement('td');
                const competitions = studentCompetitions[student.id] ? studentCompetitions[student.id] : [];

                if (Object.keys(competitions).length > 0) {
                    competitionCell.innerHTML = Object.entries(competitions).map(([competitionKey, competitionName]) =>
                        `<span data-id="${competitionKey}" class='name-badge competition'>${competitionName}</span>`
                    ).join(' ');
                } else {
                    competitionCell.textContent = "-";
                }
                row.appendChild(competitionCell);

                const registrationStateCell = document.createElement('td');
                registrationStateCell.innerHTML = `<div class='td-content'><span class='status-circle ${student.isRegistrationFinalized ? 'green' : 'red'}' title='${student.isRegistrationFinalized ? 'Offiziell' : 'Zwischenstand'}'></span></div>`;
                row.appendChild(registrationStateCell);

                if (Object.keys(competitions).length < 3) {
                    lastNameCell.innerHTML += ' <span class="competition-warning"><i class="fas fa-exclamation-circle" title="Schüler ist weniger als 3 Stationen zugeordnet."></i></span>';
                }

                if (timeCollisions.hasOwnProperty(student.id)) {
                    const collisionNames = timeCollisions[student.id].join(", ");
                    lastNameCell.innerHTML += ` <span class="time-collision"><i class="fas fa-exclamation-circle" title="Achtung: Schüler hat Stationen mit weniger als 15 Minuten Abstand => ${collisionNames}"></i></span>`;
                }

                tbody.appendChild(row);
            }

            rows = Array.from(tbody.getElementsByTagName("tr"));
            table.appendChild(tbody);
            document.querySelector('section').appendChild(table);
        }


        async function saveChangedStudents(changedStudents) {
            if (changedStudents.length === 0) {
                return;
            }

            const studentJson = JSON.stringify(changedStudents);
            spinner.style.display = 'inline-block';
            editButton.disabled = true;

            try {
                const response = await fetch('students_overview.php', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: studentJson
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

        function filterTable(columnIndex) {
            if (isEditing) {
                return;
            }
            
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


        function toggleCompetitionBadge(competitions, competitionSelect, selectedOptions, previousSelectedOptions, participantCell) {
            competitions.forEach(comp => {
                const option = competitionSelect.querySelector(`option[value="${comp.name}"]`);
                option.textContent = comp.name;

                const isSelected = selectedOptions.some(option => option.value === comp.name);
                const wasSelected = previousSelectedOptions.some(option => option.value === comp.name);

                if (isSelected && !wasSelected) {
                    const compNameBadge = document.createElement("span");
                    compNameBadge.classList.add("name-badge", "competition");
                    compNameBadge.setAttribute("data-competition", comp.name);
                    compNameBadge.setAttribute("data-id", comp.id);
                    compNameBadge.textContent = comp.name;
                    compNameBadge.setAttribute("title", "Station entfernen");

                    const removeIcon = document.createElement("i");
                    removeIcon.classList.add("fas", "fa-times");
                    removeIcon.onclick = () => handleNameBadgeRemoval(compNameBadge, option, participantCell);

                    // Stationennamen über das Select einfügen
                    compNameBadge.appendChild(removeIcon);
                    participantCell.insertBefore(compNameBadge, competitionSelect);

                } else if (!isSelected && wasSelected) {
                    const compNameBadge = participantCell.querySelector(
                        `span[data-competition="${comp.name}"]`
                    );

                    if (compNameBadge) {
                        compNameBadge.remove();
                    }
                }

            });
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


        function checkIfStudentChanged(currentStudent, storedStudent) {
            for (let i = 0; i < currentStudent.length; i++) {
                if (currentStudent[i] !== storedStudent[i]) {
                    return true;
                }
            }

            return false;
        }


        function handleNameBadgeRemoval(nameBadge, correspondingParticipantOption, participantCell) {
            if (correspondingParticipantOption) {
                correspondingParticipantOption.selected = false;
            } else {
                const optionValue = nameBadge.textContent.trim();
                correspondingParticipantOption = participantCell.querySelector(`option[value="${optionValue}"]`);

                if (correspondingParticipantOption) {
                    correspondingParticipantOption.selected = false;
                } else {
                    console.warn(`Option mit value="${optionValue}" nicht gefunden.`);
                }
            }

            nameBadge.remove();
        }
    </script>

</body>

</html>