<?php

use MVC\Controller\UserController;
use MVC\Model\Role;

require '../../vendor/autoload.php';
session_start();

$userRole = UserController::getInstance()->getRole();

// Für Zugriff mindestens Rolle Lehrkraft
if ($userRole->value < 2) {
    header("Location: login.php");
    exit();
}

$isAdmin = $userRole == Role::Admin;

use MVC\Controller\CompetitionController;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;
use MVC\Model\CompetitionStatus;
use MVC\Model\Competition;

// Die GET-Anfrage wird zuerst serverseitig ausgeführt, die Daten sollen aber erst geladen und visualisiert werden sobald die Seite gerendert ist,
// damit der Nutzer schneller etwas sieht und während des Ladens ein Spinner durch JavaScript angezeigt werden kann.Das Custom-Attribut wird
// in der JavaScript Fetch-Anfrage mitgesendet und soll signalisieren, dass die Seite fertig geladen ist und die Daten nun angefordert werden.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_X_CUSTOM_ATTRIBUTE'])) {
    $competitions = loadAllCompetitions(300);
    echo json_encode($competitions);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $putData = file_get_contents('php://input');
    $competitionController = CompetitionController::getInstance();

    if (empty($putData)) {
        $response['success'] = false;
        $response['message'] = 'Leere Anfrage erhalten.';
        echo json_encode($response);
        exit;
    }

    $compData = json_decode($putData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }


    $changedComps = [];

    $classParticipants = [];
    $studentParticipants = [];

    $studentController = StudentController::getInstance();
    $classController = ClassController::getInstance();

    foreach ($compData as $data) {
        $isTeam = $data['type'] === 'Team';

        if ($isTeam && isset($data['participants'])) {
            $classParticipants = getClassDictionary($data['participants']);
        } else if (isset($data['participants'])) {
            $studentParticipants = getStudentDictionary($data['participants']);
        }

        $comp = new Competition(
            id: $data['id'],
            name: trim($data['name']),
            classParticipants: $classParticipants,
            studentParticipants: $studentParticipants,
            isTeam: $isTeam,
            isMale: match ($data['gender']) {
                'M' => true,
                'W' => false,
                'N' => null,
                default => null,
            },
            date: DateTime::createFromFormat("Y-m-d\TH:i:s", $data['date']),
            refereeId: 0,
            referee: null, // TODO: Nur ID übergeben
            status: CompetitionStatus::fromString($data['state']),
            additionalInfo: trim($data['additionalInfo'])
        );
        $changedComps[] = $comp;
    }

    $putSuccess = true;

    foreach ($changedComps as $comp) {
        $updateResult = $competitionController->update($comp);
        $putSuccess &= $updateResult['success'] === true;
    }

    $response = [
        'success' => $putSuccess,
        'message' => $putSuccess ? 'Änderungen erfolgreich gespeichert.' : 'Einige Änderungen konnten nicht übernommen werden.'
    ];

    echo json_encode($response);
    exit;
}

function getStudentDictionary(array $studentIds): array
{
    global $studentController;

    $studentObjects = array_map(function ($studentId) use ($studentController) {
        return $studentController->getById($studentId);
    }, $studentIds);

    $studentDictionary = [];
    foreach ($studentObjects as $student) {
        $studentDictionary[$student->getId()] = [
            'firstName' => $student->getFirstName(),
            'lastName' => $student->getLastName()
        ];
    }

    return $studentDictionary;
}

function getClassDictionary(array $classIds): array
{
    global $classController;

    $classIdNameMap = [];
    foreach ($classIds as $classId) {
        $classIdNameMap[$classId] = $classController->getClassName($classId);
    }

    return $classIdNameMap;
}


function loadAllCompetitions($cacheDuration = 300): array
{
    $compController = CompetitionController::getInstance();

    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['overview_competitions']) && isset($_SESSION['overview_competitions_timestamp'])) {
        if ((time() - $_SESSION['overview_competitions_timestamp']) < $cacheDuration) {
            return $_SESSION['overview_competitions'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $competitions = $compController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['overview_competitions'] = $competitions;
    $_SESSION['overview_competitions_timestamp'] = time();

    return $competitions;
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/competitions_overview.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Stationenverwaltung</title>
</head>

<body>
    <header>
        <h1>Stationenverwaltung</h1>
    </header>

    <div id="result-message" class="result-message hidden"></div>
    <div id="timestamp-container" class="timestamp-container"></div>
    <div class="button-container">

        <?php if ($isAdmin): ?>
            <button class="circle-button add-button" id="" onclick="window.location.href='#'">
                <i class="fas fa-plus"></i>
            </button>
        <?php endif; ?>
        <button class="circle-button edit-button" id="edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden" id="cancel-button">
            <i class="fas fa-times"></i>
        </button>
        <div class="spinner" id="spinner"></div>
    </div>

    <section>
    </section>

    <script>
        let isEditing = false;
        let isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        let sortDirections = {};
        let storedValues = [];
        let changedCompetitions = [];

        const editButton = document.getElementById("edit-button");
        const editButtonIcon = document.querySelector(".edit-button i");
        const cancelButton = document.querySelector(".cancel-button");
        const addButton = document.querySelector(".add-button");
        const spinner = document.getElementById('spinner');
        const statuses = {
            Geplant: 0,
            Läuft: 1,
            Ausstehend: 2,
            Abgesagt: 3,
            Verschoben: 4,
            Beendet: 5
        };
        const statusKeys = Object.fromEntries(Object.entries(statuses).map(([key, value]) => [value, key]));


        let table, tbody, rows;

        document.addEventListener("DOMContentLoaded", () => loadCompetitionData());

        editButton.addEventListener('click', () => toggleEditState());

        cancelButton.addEventListener("click", () => {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
            }
        })

        async function loadCompetitionData() {
            spinner.style.display = "block";
            editButton.disabled = true;

            if (isAdmin)
                addButton.disabled = true;

            try {
                const response = await fetch('competitions_overview.php', {
                        method: 'GET',
                        headers: {
                            'X-Custom-Attribute': 'loadCompetitionData',
                        }
                    }).then(response => response.json())
                    .then(data => generateCompetitionTable(data));
            } catch (error) {
                console.error('Error:', error);
            } finally {
                spinner.style.display = "none";
                editButton.disabled = false;

                if (isAdmin)
                    addButton.disabled = false;
            }
        }

        async function generateCompetitionTable(compJSON) {
            let timeStamp = "<?php echo isset($_SESSION['overview_competitions_timestamp']) ? date('d.m.Y H:i:s', $_SESSION['overview_competitions_timestamp']) : ''; ?>";
            if (timeStamp) {
                document.getElementById('timestamp-container').innerHTML = `<p>Zuletzt aktualisiert: ${timeStamp}</p>`;
            }

            table = document.createElement('table');
            table.id = 'comp-table';
            table.className = 'table-style';

            thead = document.createElement('thead');
            headerRow = document.createElement('tr');

            const headers = ['Name', 'Zeit', 'Leiter', 'Art', 'Geschlecht', 'Teilnehmer', 'Status', 'Sonstiges'];
            headers.forEach((headerText, index) => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.onclick = () => filterTable(index);
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            tbody = document.createElement('tbody');

            for (const competition of compJSON) {
                const row = document.createElement('tr');

                const nameCell = document.createElement('td');
                nameCell.textContent = competition.name;

                // Stations-ID anhängen, damit diese beim Löschen abgefragt werden kann.
                nameCell.dataset.compId = competition.id;
                row.appendChild(nameCell);

                const dateCell = document.createElement('td');
                const date = new Date(competition.date);
                const formattedDate = new Intl.DateTimeFormat('de-DE', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'Europe/Berlin'
                }).format(date);
                dateCell.textContent = formattedDate;
                row.appendChild(dateCell);

                const refereeCell = document.createElement('td');
                refereeCell.textContent = competition.referee ?? "-";
                row.appendChild(refereeCell);

                const competitionTypeCell = document.createElement('td');
                competitionTypeCell.textContent = competition.isTeam ? "Team" : "Einzel";
                row.appendChild(competitionTypeCell);

                const genderCell = document.createElement('td');
                const genderIcon = document.createElement('i');

                if (competition.isMale === true) {
                    genderIcon.className = 'fas fa-mars';
                } else if (competition.isMale === false) {
                    genderIcon.className = 'fas fa-venus';
                } else {
                    genderIcon.className = 'fas fa-user';
                }

                genderCell.appendChild(genderIcon);
                row.appendChild(genderCell);

                const participantsCell = document.createElement('td');
                const participants = displayParticipants(competition)
                participantsCell.innerHTML = participants;
                row.appendChild(participantsCell);

                const statusCell = document.createElement('td');
                statusCell.textContent = competition.status ?? "-";
                row.appendChild(statusCell);

                const infoCell = document.createElement('td');
                infoCell.textContent = competition.additionalInfo ?? "-";
                row.appendChild(infoCell);

                tbody.appendChild(row);
            }

            rows = Array.from(tbody.getElementsByTagName("tr"));
            table.appendChild(tbody);
            document.querySelector('section').appendChild(table);
        }


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
                    await saveChangedCompetitions(changedCompetitions);
                }
                changedCompetitions = [];
            }
        }

        async function enterEditState() {
            if (isAdmin) {
                let deleteHeader = document.createElement("th");
            }

            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");

                if (isAdmin) {
                    let deleteColumn = document.createElement("td");
                    deleteColumn.innerHTML = `
                    <button class="circle-button delete-button">
                        <i class="fas fa-trash"></i>
                    </button>`;
                    row.appendChild(deleteColumn);
                }

                let name = row.cells[0].innerText;
                let date = row.cells[1].innerText;
                let referee = row.cells[2].innerText;
                let type = row.cells[3].innerText;
                let genderIcon = row.cells[4].querySelector("i").classList.value;
                let gender = mapGender(genderIcon, false);
                let genderSelect = createGenderSelect(gender);

                let selector = type === "Team" ? '.name-badge.class' : '.name-badge.student';
                let participants = Array.from(row.cells[5].querySelectorAll(selector))
                    .map(element => ({
                        id: element.dataset.id,
                        name: element.textContent.trim()
                    }));

                let state = row.cells[6].innerText;
                let additionalInfo = row.cells[7].innerText;

                // Werte zwischenspeichern, falls die Bearbeitung abgebrochen wird.
                storedValues[row.rowIndex] = [name, date, referee, type, gender, participants.map(p => p.name).join(","), state, additionalInfo];

                cells[0].innerHTML = `<input type="text" value="${name}" ${!isAdmin ? 'readonly' : ''}>`;

                let dateValue = createISODateValueFromString(date);
                cells[1].innerHTML = `<input type="datetime-local" value="${dateValue}" ${!isAdmin ? 'readonly' : ''}>`;

                cells[2].innerHTML = `<input type="text" value="${referee}" ${!isAdmin ? 'readonly' : ''}>`;

                const typeSelect = createTypeSelect(type)
                cells[3].innerHTML = typeSelect;

                cells[4].innerHTML = createGenderSelect(gender);
                cells[5].innerHTML = participants.map(participant => {
                    return `<span data-id="${participant.id}" data-participant="${participant.name}"
                            class="name-badge ${type === "Team" ? "class" : "student"}" 
                            title="Teilnehmer entfernen">
                                ${participant.name}
                            <i onclick="handleNameBadgeRemoval(this.parentElement, '', this.parentElement.parentElement)" 
                            class="fas fa-times"></i>
                            </span>`;
                }).join(' ');

                let allClasses =
                    <?php
                    if (isset($_SESSION['classes'])) {
                        echo json_encode($_SESSION['classes']);
                    } else {
                        $allClasses = ClassController::getInstance()->getAll();
                        echo json_encode($allClasses);
                    }
                    ?>;

                let classSelect = createClassSelect(type, allClasses, cells[5]);
                cells[5].appendChild(classSelect);

                const statusSelect = document.createElement("select");
                statusSelect.id = "status-select";
                statusSelect.disabled = !isAdmin;

                let selectedOption = document.createElement("option");
                selectedOption.textContent = state;
                selectedOption.selected = true;
                statusSelect.appendChild(selectedOption);

                for (const [statusName, statusValue] of Object.entries(statuses)) {
                    if (statusName === state) {
                        continue;
                    }
                    const option = document.createElement('option');
                    option.value = statusValue;
                    option.textContent = statusName;
                    statusSelect.appendChild(option);
                }


                cells[6].innerHTML = ""; // Entfernt die Anzeige des bisherigen Status
                cells[6].appendChild(statusSelect);
                cells[7].innerHTML = `<input type="text" value="${additionalInfo}">`;

                if (isAdmin) {
                    let deleteButton = row.querySelector('.delete-button');
                    deleteButton.addEventListener('click', () => {
                        const confirmation = confirm('Sind Sie sicher, dass Sie diese Station löschen möchten?');
                        if (confirmation) {
                            deleteCompetition(row.cells[0].dataset.compId, row.rowIndex);
                        }
                    });
                }
            });

            if (isAdmin)
                headerRow.appendChild(deleteHeader);
        }

        function exitEditState(wasCanceled = false) {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");
                let storedRow = storedValues[row.rowIndex];

                let name = wasCanceled ? storedRow[0] : cells[0].querySelector('input').value;

                let dateInputValue = cells[1].querySelector('input').value;
                let date = wasCanceled || !dateInputValue ? storedRow[1] : dateInputValue;

                let referee = wasCanceled ? storedRow[2] : cells[2].querySelector('input').value;
                let type = wasCanceled ? storedRow[3] : cells[3].querySelector('select').value;
                let gender = wasCanceled ? storedRow[4] : cells[4].querySelector('select').value;

                let selector = type === "Team" ? ".name-badge.class" : ".name-badge.student";
                let participants = wasCanceled ?
                    storedRow[5].split(",").filter(participant => participant.trim() !== "") :
                    Array.from(cells[5].querySelectorAll(selector))
                    .map(element => element.textContent.trim());

                // Nötig, um aus der Option das zugehörige Class bzw. Schüler-Objekt zu finden.
                let participantIds = Array.from(cells[5].querySelectorAll(selector))
                    .map(element => element.dataset.id)
                    .filter(id => id != null);

                // Beim Bestätigen den Wert der selektierten Option abfragen, bei keiner Änderung wird der bisherige Wert verwendet.
                let state = wasCanceled ? storedRow[6] : statusKeys[cells[6].querySelector('select').value] ?? storedRow[6];
                let additionalInfo = wasCanceled ? storedRow[7] : cells[7].querySelector('input').value;

                if (checkIfRowWasModified(row, storedRow)) {
                    let changedComp = {
                        id: row.cells[0].dataset.compId,
                        name: name,
                        date: date,
                        referee: referee,
                        type: type,
                        gender: gender,
                        participants: participantIds,
                        state: state,
                        additionalInfo: additionalInfo
                    };
                    changedCompetitions.push(changedComp);
                }

                cells[0].innerHTML = `<div class='td-content'>${name}</div>`;

                // Bei Abbruch oder gelöschtem Datum das gespeicherte Datum wiederherstellen
                cells[1].innerHTML = `<div class='td-content'>${wasCanceled || !dateInputValue ? date : createDateStringFromISOValue(date)}</div>`;

                cells[2].innerHTML = `<div class='td-content'>${referee}</div>`;
                cells[3].innerHTML = `<div class='td-content'>${type}</div>`;

                cells[4].innerHTML = ''; // Entfernt das Input-Element.
                let genderContent = document.createElement('div');
                let genderIcon = document.createElement('i');
                let genderIconClasses = mapGender(gender, true).split(" ");
                genderContent.classList.add("td-content");
                genderIcon.classList.add(...genderIconClasses);
                genderContent.appendChild(genderIcon);
                cells[4].appendChild(genderContent);

                let participantObjects = participants.map((name, index) => ({
                    id: participantIds[index] || "",
                    name: name
                }));

                // Teilnehmer-Anzeige
                cells[5].innerHTML = participants.length === 0 ?
                    '-' :
                    participantObjects.map(obj => {
                        return `<span data-id="${obj.id}" data-participant="${obj.name}"
                        class="name-badge ${type === "Team" ? "class" : "student"}" title="Teilnehmer entfernen">
                        ${obj.name}
                        </span>`;
                    }).join(' ');

                cells[6].innerHTML = `<div class='td-content'>${state}</div>`;
                cells[7].innerHTML = `<div class='td-content'>${additionalInfo}</div>`;
            });

            storedValues = [];

            // Löschen-Spalte & -Knöpfe entfernen.
            if (isAdmin) {
                headerRow.querySelector("th:last-child").remove();
                document.querySelectorAll(".delete-button").forEach(b => b.parentElement.remove());
            }
        }

        async function deleteCompetition(compId, rowIndex) {
            if (!isAdmin)
                return;

            spinner.style.display = 'inline-block';
            editButton.disabled = true;

            try {
                const response = await fetch(`competitions_overview?compId=${compId}`, {
                    method: 'DELETE',
                });

                const data = await response.json();
                if (data.success) {
                    const row = table.rows[rowIndex];
                    if (row) {
                        row.remove();
                        storedValues.splice(rowIndex, 1);
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

        async function saveChangedCompetitions(changedCompetitions) {
            if (changedCompetitions.length === 0) {
                return;
            }

            // TODO: Validierung

            const compJSON = JSON.stringify(changedCompetitions);
            spinner.style.display = 'inline-block';
            editButton.disabled = true;

            try {
                const response = await fetch('competitions_overview.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: compJSON
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

        function checkIfRowWasModified(row, storedRow) {
            if (!storedRow) {
                return false;
            }

            let cells = row.getElementsByTagName("td");

            // Kopfzeile überspringen
            for (let i = 0; i < cells.length - 1; i++) {

                let currentValue =
                    (cells[i].querySelector('input')?.value) ||
                    (cells[i].querySelector('select:not(#class-select):not(#student-select)')?.value) ||
                    (cells[i].querySelector('input[type="datetime-local"]')?.value) ||
                    Array.from(cells[i].querySelectorAll('.name-badge')).map(el => el.textContent.trim()).join(',');

                const storedValue = storedRow[i];

                // Zeitpunkt
                if (i === 1) {
                    if (!currentValue) {
                        continue;
                    }

                    currentValue = createDateStringFromISOValue(currentValue);
                }

                if (currentValue !== storedValue) {
                    console.log(`Vorher: ${storedValue} vs Nachher: ${currentValue}`);
                    return true;
                }
            }

            return false;
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


        function filterTable(columnIndex) {
            let table = document.getElementById("comp-table");
            let tbody = table.getElementsByTagName("tbody")[0];
            let rows = Array.from(tbody.getElementsByTagName("tr"));

            sortDirections[columnIndex] = sortDirections[columnIndex] === "asc" ? "desc" : "asc";
            let sortOrder = sortDirections[columnIndex];

            rows.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName("td")[columnIndex].innerText.trim();
                let cellB = rowB.getElementsByTagName("td")[columnIndex].innerText.trim();
                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        function createGenderSelect(gender) {
            const optionsHTML = `
            <select id="gender-select" ${!isAdmin ? 'disabled' : ''}>
                <option value="M" ${gender === 'M' ? 'selected' : ''}>Männlich</option>
                <option value="W" ${gender === 'W' ? 'selected' : ''}>Weiblich</option>
                <option value="N" ${gender === 'N' ? 'selected' : ''}>Neutral</option>
            </select>`;
            return optionsHTML;
        }

        function createTypeSelect(type) {
            const optionsHTML = `
            <select id="type-select" ${!isAdmin ? 'disabled' : ''}>
                <option value="Einzel" ${type === 'Einzel' ? 'selected' : ''}>Einzel</option>
                <option value="Team" ${type === 'Team' ? 'selected' : ''}>Team</option>
            </select>`;
            return optionsHTML;
        }

        function mapGender(input, toIcon) {
            const genderToIcon = {
                'M': 'fas fa-mars',
                'W': 'fas fa-venus',
                'N': 'fas fa-user',
            };

            const iconToGender = {
                'fas fa-mars': 'M',
                'fas fa-venus': 'W',
                'fas fa-user': 'N'
            };

            if (toIcon) {
                return genderToIcon[input] || '';
            } else {
                return iconToGender[input] || '';
            }
        }


        function createISODateValueFromString(dateTimeString) {
            // Anzeigeformat: 09.10.24, 20:13:29 (dd-mm-yyyy)
            // Nötiges Format für datetime-local input: yyyy-MM-ddThh:mm
            let dateParts = dateTimeString.split(",").map(part => part.trim());
            let date = dateParts[0];
            let time = dateParts[1];

            let [day, month, year] = date.split(".");
            let isoString = `${year}-${month}-${day}T${time}`;
            return isoString;
        }

        function createDateStringFromISOValue(isoValue) {
            let dateParts = isoValue.split("T").map(part => part.trim());
            let date = dateParts[0];
            let time = dateParts[1];

            let [year, month, day] = date.split("-");
            let dateString = `${day}.${month}.${year}, ${time}`;
            return dateString;
        }

        function displayParticipants(comp) {
            let participantsHTML = "";

            if (comp.isTeam === true) {
                participantsHTML = Object.entries(comp.classParticipants).length === 0 ?
                    '-' :
                    Object.entries(comp.classParticipants).map(([id, name]) => {
                        return `<span data-id="${id}" data-participant="${name}" class="name-badge class">${name}</span>`;
                    }).join(' ');
            } else {
                participantsHTML = Object.entries(comp.studentParticipants).length === 0 ?
                    '-' :
                    Object.entries(comp.studentParticipants).map(([id, student]) => {
                        return `<span data-id="${id}" data-participant="${student.firstName} ${student.lastName}" class="name-badge student">${student.firstName} ${student.lastName}</span>`;
                    }).join(' ');
            }

            return participantsHTML;
        }


        function createClassSelect(type, classes, participantCell) {
            let classSelect = document.createElement("select");
            classSelect.id = "class-select";
            classSelect.multiple = type === "Team";
            classSelect.setAttribute("data-type", type === "Team" ? "Team" : "Einzel");
            classSelect.setAttribute("title",
                type === "Team" ?
                "Wählen Sie die teilnehmenden Klassen aus. Drücken Sie STRG, um mehrere Klassen gleichzeitig auszuwählen." :
                "Wählen Sie eine Klasse aus, um deren Schüler auszuwählen."
            )

            let defaultOption = document.createElement("option");
            defaultOption.textContent = "Klassenauswahl";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            classSelect.appendChild(defaultOption);

            // Alle Klassennamen als Option hinzufügen
            classes.forEach(c => {
                let option = document.createElement("option");
                option.value = option.textContent = c.name;
                option.dataset.id = c.id;
                classSelect.appendChild(option);
            });

            // Alle bereits zugewiesenen Klassen vorselektieren
            const nameBadge = participantCell.querySelectorAll('.name-badge.class');
            const participantNames = Array.from(nameBadge).map(badge => badge.dataset.participant);
            for (const option of classSelect.options) {
                if (participantNames.includes(option.text)) {
                    option.selected = true;
                }
            }

            // Vorherige Selektion speichern, um diese bei Deselektion zu entfernen
            let previousSelectedOptions = Array.from(classSelect.selectedOptions);

            classSelect.addEventListener('change', () => {
                const selectedOptions = Array.from(classSelect.selectedOptions);

                if (type === "Team") {
                    toggleClassNameBadge(classes, classSelect, selectedOptions, previousSelectedOptions, participantCell);
                } else {
                    // Zweites Select mit allen Schülern der ausgewählten Klasse anzeigen
                    let classId = classSelect.selectedOptions[0].dataset.id;

                    fetch(`../../Helper/get_students_by_class.php?classId=${classId}`)
                        .then(response => {
                            return response.json();
                        })
                        .then(classStudents => {
                            let previousSelect = participantCell.querySelector('#student-select');
                            if (previousSelect) {
                                previousSelect.remove();
                            }
                            let studentSelect = createStudentSelect(classStudents, participantCell);
                            participantCell.appendChild(studentSelect);
                        })
                        .catch(error => console.error('Error fetching students:', error));
                }

                previousSelectedOptions = selectedOptions;
            });

            return classSelect;
        }

        function createStudentSelect(allStudents, participantCell) {
            let studentSelect = document.createElement("select");
            studentSelect.id = "student-select";
            studentSelect.multiple = true;
            studentSelect.setAttribute("title",
                "Wählen Sie die teilnehmenden Schüler aus"
            );

            let defaultOption = document.createElement("option");
            defaultOption.textContent = "Schülerauswahl";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            studentSelect.appendChild(defaultOption);

            allStudents.forEach(s => {
                let option = document.createElement("option");
                option.textContent = `${s.firstName} ${s.lastName}`;
                option.value = option.textContent;
                option.dataset.classId = s.classId;
                studentSelect.appendChild(option);
            });

            // Alle bereits zugewiesenen Schüler vorselektieren
            const nameBadge = participantCell.querySelectorAll('.name-badge.student');
            const participantNames = Array.from(nameBadge).map(badge => badge.dataset.participant);
            for (const option of studentSelect.options) {
                if (participantNames.includes(option.text)) {
                    option.selected = true;
                }
            }

            let previousSelection = Array.from(studentSelect.selectedOptions);

            studentSelect.addEventListener('change', () => {
                const selectedStudents = Array.from(studentSelect.selectedOptions);
                toggleStudentNameBadge(allStudents, studentSelect, selectedStudents, previousSelection, participantCell);
                previousSelection = selectedStudents;
            });

            return studentSelect;
        }

        function handleNameBadgeRemoval(nameBadge, correspondingParticipantOption, participantCell) {
            if (correspondingParticipantOption) {
                correspondingParticipantOption.selected = false;
            } else {
                // Die Teilnehmer-Selects werden erst im Bearbeitungsmodus angezeigt. Daher kann die passende Option 
                // für bereits vorher zugewiesene Teilnehmer erst hier ermittelt werden. Für alle im Bearbeitungsmodus
                // hinzugefügten Teilnehmer kann die Option hier direkt als Argument übergeben werden.
                const optionValue = nameBadge.textContent.trim();
                correspondingParticipantOption = participantCell.querySelector(`option[value="${optionValue}"]`);

                if (correspondingParticipantOption) {
                    correspondingParticipantOption.selected = false;
                } else {
                    // Derzeit der Fall für Schülernamen.
                    console.warn(`Option mit value="${optionValue}" nicht gefunden.`);
                }
            }

            nameBadge.remove();
        }

        // Verantwortlich für das Hinzufügen und Entfernen der Klassen-Anzeigeelemente je nach Selektionsstatus.
        function toggleClassNameBadge(classes, classSelect, selectedOptions, previousSelectedOptions, participantCell) {
            classes.forEach(classItem => {
                const option = classSelect.querySelector(`option[value="${classItem.name}"]`);
                option.textContent = classItem.name;

                const isSelected = selectedOptions.some(option => option.value === classItem.name);
                const wasSelected = previousSelectedOptions.some(option => option.value === classItem.name);

                if (isSelected && !wasSelected) {
                    const classElement = document.createElement("span");
                    classElement.classList.add("name-badge", "class");
                    classElement.setAttribute("data-participant", classItem.name);
                    classElement.setAttribute("data-id", classItem.id);
                    classElement.textContent = `${classItem.name}`;
                    classElement.setAttribute("title", "Klasse entfernen");

                    const removeIcon = document.createElement("i");
                    removeIcon.classList.add("fas", "fa-times");
                    removeIcon.onclick = () => handleNameBadgeRemoval(classElement, option, participantCell);

                    // Klassennamen über das Select einfügen
                    classElement.appendChild(removeIcon);
                    participantCell.insertBefore(classElement, classSelect);

                } else if (!isSelected && wasSelected) {
                    const classElement = participantCell.querySelector(
                        `span[data-participant="${classItem.name}"]`
                    );

                    if (classElement) {
                        classElement.remove();
                    }
                }

            });
        }

        function toggleStudentNameBadge(students, studentSelect, selectedOptions, previousSelectedOptions, participantCell) {
            students.forEach(s => {
                let name = `${s.firstName} ${s.lastName}`;
                const option = studentSelect.querySelector(`option[value="${name}"]`);
                option.textContent = name;
                const isSelected = selectedOptions.some(option => option.value === name);
                const wasSelected = previousSelectedOptions.some(option => option.value === name);

                if (isSelected && !wasSelected) {
                    const nameBadge = document.createElement("span");
                    nameBadge.classList.add("name-badge", "student");
                    nameBadge.setAttribute("data-participant", name);
                    nameBadge.setAttribute("data-id", s.id);
                    nameBadge.textContent = name;
                    nameBadge.setAttribute("title", "Schüler entfernen");

                    const removeIcon = document.createElement("i");
                    removeIcon.classList.add("fas", "fa-times");
                    removeIcon.onclick = () => handleNameBadgeRemoval(nameBadge, option, participantCell);

                    // Schülernamen über das Select einfügen
                    nameBadge.appendChild(removeIcon);
                    classSelect = document.getElementById("class-select");

                    if (classSelect) {
                        const lastNameBadge = participantCell.querySelector('.name-badge:last-of-type') ?? participantCell.firstChild;
                        participantCell.insertBefore(nameBadge, lastNameBadge.nextSibling);
                    }

                } else if (!isSelected && wasSelected) {
                    const nameBadge = participantCell.querySelector(
                        `span[data-participant="${name}"]`
                    );

                    if (nameBadge) {
                        nameBadge.remove();
                    }
                }

            });
        }
    </script>
</body>

</html>