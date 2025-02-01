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

    <div id="timestamp-container" class="timestamp-container"></div>
    <div class="button-container">
        <button title="Neue Schüler im CSV-Format importieren" class="circle-button add-button" onclick="window.location.href='import_students_csv.php'">
            <i class="fas fa-plus"></i>
        </button>
        <div class="spinner" id="spinner"></div>
    </div>

    <section></section>

    <script>
        let sortDirections = {};
        let table, tbody, rows;
        const spinner = document.getElementById('spinner');


        document.addEventListener("DOMContentLoaded", () => loadStudentData());

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

            const headers = ['Vorname', 'Nachname', 'Klasse', 'Geschlecht', 'Stationen'];
            headers.forEach((headerText, index) => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.onclick = () => filterTable(index);
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            tbody = document.createElement('tbody');
            const classNames = <?php echo json_encode(loadAllClassNames(300)); ?>;
            const studentCompetitions = <?php echo json_encode(loadAllCompetitionNames()); ?>;

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
                if (studentCompetitions[student.id]) {
                    const competitions = Object.values(studentCompetitions[student.id]);
                    competitionCell.innerHTML = competitions.map(competition => {
                        return `<span class='name-badge competition'>${competition}</span>`;
                    }).join(' ');
                } else {
                    competitionCell.textContent = "-";
                }

                row.appendChild(competitionCell);
                tbody.appendChild(row);
            }

            rows = Array.from(tbody.getElementsByTagName("tr"));
            table.appendChild(tbody);
            document.querySelector('section').appendChild(table);
        }

        function filterTable(columnIndex) {
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