<?php
require '../../vendor/autoload.php';

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

use MVC\Model\Teacher;
use MVC\Controller\TeacherController;

session_start();

$response = [
    'success' => true,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enthält den POST-Request Body.
    $postData = file_get_contents('php://input');

    if (empty($postData)) {
        $response['success'] = false;
        $response['message'] = 'Leere Anfrage erhalten.';
        echo json_encode($response);
        exit;
    }

    $teachersData = json_decode($postData, true);
    $importSuccess = true;

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }

    $teacherController = TeacherController::getInstance();

    foreach ($teachersData as $data) {
        $teacher = new Teacher(
            id: null, // wird von API gesetzt
            firstName: $data['firstName'],
            lastName: $data['lastName'],
            shortCode: $data['shortCode'],
            class: null,
            additionalInfo: null
        );

        $result = $teacherController->create($teacher);
        if (!$result['success']) {
            $importSuccess = false;
            $response['success'] = false;
            $response['message'] = "Fehler beim Importieren: " . ($result['error'] ?? 'Unbekannter Fehler');
            break;
        }
    }

    if ($importSuccess) {
        $response['message'] = 'Lehrer erfolgreich importiert.';
    }

    echo json_encode($response);
    exit;
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
    <div class="resultMessage" id="resultMessage"></div>
    <form class="uploadForm" id="uploadForm" action="" method="POST">
        <fieldset>
            <legend>CSV-Import: Lehrer</legend>
            <div class="import-header">
                <label id="upload-label" for="fileToUpload" class="custom-file-upload">
                    <abbr title="Format der CSV-Datei:
                        1. Erste Zeile: Kopfzeile mit den Spaltennamen.
                        2. Reihenfolge der Spalten: Nachname;Vorname;Kürzel.
                           Beispiel: Mustermann;Max;MM
                        3. Datensätze werden durch ein Semikolon (;) getrennt.">
                        CSV
                    </abbr>-Datei auswählen:
                    <span id="fileName">Keine Datei ausgewählt</span>
                    <input type="file" id="fileToUpload" name="fileToUpload" accept=".csv" onchange="previewTeachers()">
                </label>
            </div>
            <div class="import-preview" id="teacherPreview"></div> <!-- Import-Vorschau -->
            <button id="submitButton" disabled onclick="event.preventDefault(); uploadTeachers();" name="submitButton">
                Importieren
                <div class="spinner" id="spinner"></div>
            </button>
        </fieldset>
    </form>

    <script>
        const fileInput = document.getElementById('fileToUpload');
        const submitButton = document.getElementById('submitButton');
        const fileName = document.getElementById('fileName');
        const spinner = document.getElementById('spinner');

        let teachers = [];

        fileInput.addEventListener('change', function() {
            // Importieren-Button aktivieren, wenn Datei ausgewählt.
            submitButton.disabled = fileInput.files.length == 0 || teachers.length == 0;
            fileName.textContent = fileInput.files[0].name; // Dateiname anzeigen
        });

        function previewTeachers() {
            teachers = []; // Leeren, falls noch alte Daten vorhanden sind.
            const file = fileInput.files[0];

            if (!file) {
                return;
            }

            if (!file.name.endsWith('.csv')) {
                alert('Bitte wählen Sie eine CSV-Datei aus.');
                return;
            }

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
                previewDiv.innerHTML = `
                <p style="text-align: center; margin: 0;">
                    Keine Lehrer gefunden.<br>
                    <strong>Format der CSV-Datei:</strong><br>
                    1. Erste Zeile: Kopfzeile mit den Spaltennamen.<br>
                    2. Reihenfolge der Spalten: <em>Nachname;Vorname;Kürzel</em>.<br>
                    <strong>Beispiel:</strong> Mustermann;Max;MM<br>
                    3. Trennzeichen: Semikolon ( ; )
                </p>
            `;
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

            resultMessage.innerHTML = '';
            spinner.style.display = 'inline-block';

            fetch('import_teachers_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: teachersJSON
                })
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    resultMessage.innerHTML = `<p class="resultMessage ${data.success ? 'success' : 'error'}">${data.message}</p>`;
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    console.error('Fehler:', error);
                    resultMessage.innerHTML = `<p class="resultMessage error">Fehler beim Importieren der Lehrer.</p>`;
                });
        }
    </script>
</body>

</html>