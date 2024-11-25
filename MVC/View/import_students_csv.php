<?php
require '../../vendor/autoload.php';

if(!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

use MVC\Model\Student;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enthält den POST-Request Body.
    $postData = file_get_contents('php://input');

    if (empty($postData)) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $studentsData = json_decode($postData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    session_start();
    $classController = ClassController::getInstance();
    $studentController = StudentController::getInstance();

    foreach ($studentsData as $data) {
        $student = new Student(
            id: null,
            firstName: $data['firstName'],
            lastName: $data['lastName'],
            isMale: $data['isMale'] == true,
            classId: $classController->getIdFromName($data['className'])
        );

        $studentController->create($student);
    }
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/import_csv.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>CSV-Import</title>
</head>

<body>
    <form class="uploadForm" id="uploadForm" action="" method="POST">
        <fieldset>
            <legend>Schüler importieren </legend>
            <div class="import-header">
                <label id="upload-label" for="fileToUpload" class="custom-file-upload">
                    CSV-Datei auswählen:
                    <input type="file" id="fileToUpload" name="fileToUpload" accept=".csv" onchange="previewStudents()">
                </label>
            </div>
            <div class="import-preview" id="studentPreview"></div> <!-- Import-Vorschau -->
            <button id="submitButton" disabled onclick="event.preventDefault(); uploadStudents();" name="submitButton">Importieren</button>
        </fieldset>
    </form>

    <script>
        // Schüler-Vorschau dynamisch nach Auswahl einer CSV-Datei anzeigen (JavaScript nötig).
        const fileInput = document.getElementById('fileToUpload');
        const submitButton = document.getElementById('submitButton');

        let students = [];

        fileInput.addEventListener('change', function() {
            // Importieren-Button aktivieren, wenn Datei ausgewählt.
            submitButton.disabled = fileInput.files.length == 0 || students.length == 0;
        });

        function previewStudents() {
            students = []; // Leeren, falls noch alte Daten vorhanden sind.
            const fileInput = document.getElementById('fileToUpload');
            const file = fileInput.files[0];

            if (!file) {
                return;
            }

            if (!file.name.endsWith('.csv')) {
                alert('Bitte wählen Sie eine CSV-Datei aus.');
                return;
            }

            // Namen der ausgewählten Datei anzeigen im File-Input.
            document.getElementById('upload-label').innerText = file.name;

            const reader = new FileReader();
            reader.onload = function(e) {
                const fileContent = e.target.result; // e.target = FileReader, der das onload-Event ausgelöst hat.
                students = parseCSV(fileContent);
                displayPreview(students);
            };

            reader.readAsText(file); // onload-Event wird ausgelöst, wenn das Lesen abgeschlossen ist.
        }

        function parseCSV(data) {
            const lines = data.split('\n').slice(1); // Kopfzeile entfernen.

            for (const line of lines) {
                const [lastName, firstName, isMale, className] = line.split(';');
                if (lastName && firstName && isMale && className) {
                    students.push({
                        lastName,
                        firstName,
                        className,
                        isMale: isMale.toLowerCase() === 'männlich'
                    });
                }
            }

            submitButton.disabled = students.length == 0;
            return students;
        }

        function displayPreview(students) {
            const previewDiv = document.getElementById('studentPreview');
            previewDiv.innerHTML = '';

            if (students.length === 0) {
                previewDiv.innerHTML = '<p>Keine Schüler gefunden.</p>';
                return;
            }

            // Tabelle erstellen und Schülerdaten anzeigen.
            const table = document.createElement('table');
            const header = document.createElement('tr');
            header.innerHTML = '<th>Vorname</th><th>Nachname</th><th>Geschlecht</th><th>Klasse</th>';
            table.appendChild(header);

            // Alle Zeilen bis auf Kopfzeile durchgehen.
            students.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${student.firstName}</td><td>${student.lastName}</td><td>${student.isMale ? 'Männlich' : 'Weiblich'}</td><td>${student.className}</td>`;
                table.appendChild(row);
            });

            previewDiv.appendChild(table);
        }


        // Postet die Schülerdaten, sodass PHP sie an die API weiterleiten kann.
        function uploadStudents() {
            const studentsJSON = JSON.stringify(students);

            fetch('import_students_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: studentsJSON
                })
                .catch(error => {
                    console.error('Fehler:', error);
                });
        }
    </script>
</body>

</html>