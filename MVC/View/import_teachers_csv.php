<?php
require '../../vendor/autoload.php';

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

use MVC\Model\Teacher;
use MVC\Controller\TeacherController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enthält den POST-Request Body.
    $postData = file_get_contents('php://input');

    if (empty($postData)) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $teachersData = json_decode($postData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    session_start();
    $teacherController = TeacherController::getInstance();

    foreach ($teachersData as $data) {
        $teacher = new Teacher(
            id: null, // wird von API gesetzt
            firstName: $data['firstName'],
            lastName: $data['lastName'],
            shortCode: $data['shortCode'],
            classId: null,
            class: null,
            additionalInfo: null
        );

        $teacherController->create($teacher);
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
            <legend>Lehrer importieren </legend>
            <div class="import-header">
                <label id="upload-label" for="fileToUpload" class="custom-file-upload">
                    CSV-Datei auswählen:
                    <input type="file" id="fileToUpload" name="fileToUpload" accept=".csv" onchange="previewTeachers()">
                </label>
            </div>
            <div class="import-preview" id="teacherPreview"></div> <!-- Import-Vorschau -->
            <button id="submitButton" disabled onclick="event.preventDefault(); uploadTeachers();" name="submitButton">Importieren</button>
        </fieldset>
    </form>

    <script>
        // Lehrer-Vorschau dynamisch nach Auswahl einer CSV-Datei anzeigen (JavaScript nötig).
        const fileInput = document.getElementById('fileToUpload');
        const submitButton = document.getElementById('submitButton');

        let teachers = [];

        fileInput.addEventListener('change', function() {
            // Importieren-Button aktivieren, wenn Datei ausgewählt.
            submitButton.disabled = fileInput.files.length == 0 || teachers.length == 0;
        });

        function previewTeachers() {
            teachers = []; // Leeren, falls noch alte Daten vorhanden sind.
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
                teachers = parseCSV(fileContent);
                displayPreview(teachers);
            };

            reader.readAsText(file); // onload-Event wird ausgelöst, wenn das Lesen abgeschlossen ist.
        }

        function parseCSV(data) {
            const lines = data.split('\n').slice(1); // Kopfzeile entfernen.

            for (const line of lines) {
                const [lastName, firstName, shortCode] = line.split(';').map(item => item.trim());
                if (lastName && firstName && shortCode) {
                    teachers.push({
                        lastName,
                        firstName,
                        shortCode
                    });
                }
            }

            submitButton.disabled = teachers.length == 0;
            return teachers;
        }

        function displayPreview(teachers) {
            const previewDiv = document.getElementById('teacherPreview');
            previewDiv.innerHTML = '';

            if (teachers.length === 0) {
                previewDiv.innerHTML = '<p>Keine Lehrer gefunden.</p>';
                return;
            }

            // Tabelle erstellen und Lehrer-Daten anzeigen.
            const table = document.createElement('table');
            const header = document.createElement('tr');
            header.innerHTML = '<th>Vorname</th><th>Nachname</th><th>Kürzel</th>';
            table.appendChild(header);

            // Alle Zeilen bis auf Kopfzeile durchgehen.
            teachers.forEach(teacher => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${teacher.firstName}</td><td>${teacher.lastName}</td><td>${teacher.shortCode}</td>`;
                table.appendChild(row);
            });

            previewDiv.appendChild(table);
        }

        // Postet die Lehrer-Daten, sodass PHP sie an die API weiterleiten kann.
        function uploadTeachers() {
            const teachersJSON = JSON.stringify(teachers);

            fetch('import_teachers_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: teachersJSON
                })
                .catch(error => {
                    console.error('Fehler:', error);
                });
        }
    </script>
</body>

</html>
