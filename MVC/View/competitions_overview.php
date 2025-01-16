<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}


use MVC\Controller\CompetitionController;

// Die GET-Anfrage wird zuerst serverseitig ausgeführt, die Daten sollen aber erst geladen und visualisiert werden sobald die Seite gerendert ist,
// damit der Nutzer schneller etwas sieht und während des Ladens ein Spinner durch JavaScript angezeigt werden kann.Das Custom-Attribut wird
// in der JavaScript Fetch-Anfrage mitgesendet und soll signalisieren, dass die Seite fertig geladen ist und die Daten nun angefordert werden.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_X_CUSTOM_ATTRIBUTE'])) {
    $competitions = loadAllCompetitions(300);
    echo json_encode($competitions);
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

        document.addEventListener("DOMContentLoaded", () => loadCompetitionData());

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
            console.log(compJSON);

            let timeStamp = "<?php echo isset($_SESSION['overview_competitions_timestamp']) ? date('d.m.Y H:i:s', $_SESSION['overview_competitions_timestamp']) : ''; ?>";
            if (timeStamp) {
                document.getElementById('timestamp-container').innerHTML = `<p>Zuletzt aktualisiert: ${timeStamp}</p>`;
            }

            const table = document.createElement('table');
            table.id = 'comp-table';
            table.className = 'table-style';

            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');

            const headers = ['Name', 'Datum', 'Leiter', 'Art', 'Geschlecht', 'Teilnehmer', 'Status', 'Sonstiges'];
            headers.forEach((headerText, index) => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.onclick = () => filterTable(index);
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            for (const competition of compJSON) {
                const row = document.createElement('tr');

                const nameCell = document.createElement('td');
                nameCell.textContent = competition.name;
                row.appendChild(nameCell);

                const dateCell = document.createElement('td');
                const date = new Date(competition.date);
                const formattedDate = new Intl.DateTimeFormat('de-DE', {
                    year: '2-digit',
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

                // Erstmal Teilnehmeranzahl anzeigen. Später alle Teilnehmer bei Klick in das td-Element.
                if (competition.isTeam) {
                    participantsCell.textContent = competition.classParticipants.length;
                } else {
                    participantsCell.textContent = competition.studentParticipants.length;
                }

                row.appendChild(participantsCell);

                const statusCell = document.createElement('td');
                statusCell.textContent = competition.status ?? "-";
                row.appendChild(statusCell);

                const infoCell = document.createElement('td');
                infoCell.textContent = competition.additionalInfo ?? "-";
                row.appendChild(infoCell);

                tbody.appendChild(row);
            }

            table.appendChild(tbody);
            document.querySelector('section').appendChild(table);
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
    </script>

</body>

</html>