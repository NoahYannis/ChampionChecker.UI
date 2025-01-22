<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}


use MVC\Controller\CompetitionController;
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

    foreach ($compData as $data) {
        $isTeam = $data['type'] === 'Team';
        // $classParticipants = $isTeam ? $data['participants'] : null;
        // $studentParticipants = !$isTeam ? $data['participants'] : null;
        $classParticipants = [];
        $studentParticipants = [];

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
        <button class="circle-button add-button" id="" onclick="window.location.href='#'">
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

    <section>
    </section>

    <script>
        let isEditing = false;
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
            let deleteHeader = document.createElement("th");

            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");
                let deleteColumn = document.createElement("td");
                deleteColumn.innerHTML = `
                <button class="circle-button delete-button">
                    <i class="fas fa-trash"></i>
                </button>`;
                row.appendChild(deleteColumn);

                let name = row.cells[0].innerText;
                let date = row.cells[1].innerText;
                let referee = row.cells[2].innerText;
                let type = row.cells[3].innerText;
                let genderIcon = row.cells[4].querySelector("i").classList.value;
                let gender = mapGender(genderIcon, false);
                let genderSelect = createGenderSelect(gender);

                let participants = row.cells[5].innerText;
                let state = row.cells[6].innerText;
                let additionalInfo = row.cells[7].innerText;

                // Werte zwischenspeichern, falls die Bearbeitung abgebrochen wird.
                storedValues[row.rowIndex] = [name, date, referee, type, gender, participants, state, additionalInfo];

                cells[0].innerHTML = `<input type="text" value="${name}">`;

                let dateValue = createISODateValueFromString(date);
                cells[1].innerHTML = `<input type="datetime-local" value="${dateValue}">`;

                cells[2].innerHTML = `<input type="text" value="${referee}">`;

                const typeSelect = createTypeSelect(type)
                cells[3].innerHTML = typeSelect;

                cells[4].innerHTML = createGenderSelect(gender);
                cells[5].innerHTML = `<input type="text" value="${participants}">`;

                const statusSelect = document.createElement("select");
                statusSelect.id = "status-select";

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

                let deleteButton = row.querySelector('.delete-button');
                deleteButton.addEventListener('click', () => {
                    const confirmation = confirm('Sind Sie sicher, dass Sie diese Station löschen möchten?');
                    if (confirmation) {
                        deleteCompetition(row.cells[0].dataset.compId, row.rowIndex);
                    }
                });
            });

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
                let participants = wasCanceled ? storedRow[5] : cells[5].querySelector('input').value;

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
                        participants: participants,
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

                cells[5].innerHTML = `<div class='td-content'>${participants}</div>`;
                cells[6].innerHTML = `<div class='td-content'>${state}</div>`;
                cells[7].innerHTML = `<div class='td-content'>${additionalInfo}</div>`;
            });

            storedValues = [];

            // Löschen-Spalte & -Knöpfe entfernen.
            headerRow.querySelector("th:last-child").remove();
            document.querySelectorAll(".delete-button").forEach(b => b.parentElement.remove());
        }

        async function deleteCompetition(compId, rowIndex) {
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
                const inputElement = cells[i].querySelector('input') ||
                    cells[i].querySelector('select') ||
                    cells[i].querySelector('input[type="datetime-local"]');

                const storedValue = storedRow[i];
                let currentValue = inputElement.value;

                if (inputElement.type === 'datetime-local') {
                    if (!inputElement.value) {
                        continue;
                    }

                    currentValue = createDateStringFromISOValue(inputElement.value);
                }

                if (currentValue !== storedValue) {
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
            <select id="gender-select">
                <option value="M" ${gender === 'M' ? 'selected' : ''}>Männlich</option>
                <option value="W" ${gender === 'W' ? 'selected' : ''}>Weiblich</option>
                <option value="N" ${gender === 'N' ? 'selected' : ''}>Neutral</option>
            </select>`;
            return optionsHTML;
        }

        function createTypeSelect(type) {
            const optionsHTML = `
            <select id="type-select">
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
            // Erstmal Anzeige der Klassen bei Team-Wettbewerben, anschließend Schülernamen
            let participantsHTML = "";

            if (comp.isTeam === true) {
                participantsHTML = comp.classParticipants.map(p => {
                    return `<span data-id="${p.id}" data-name="${p.name}" class="class">${p.name}</span>`;
                }).join(' ');
            }

            return participantsHTML;
        }
    </script>

</body>

</html>