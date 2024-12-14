<?php
require '../../vendor/autoload.php';

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

use MVC\Model\Student;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;

session_start();

$response = [
    'success' => true,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enthält den POST-Request Body in JSON-Form.
    $postData = file_get_contents('php://input');

    if (empty($postData)) {
        $response['success'] = false;
        $response['message'] = 'Leere Anfrage erhalten.';
        echo json_encode($response);
        exit;
    }

    $studentsData = json_decode($postData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }

    $classController = ClassController::getInstance();
    $studentController = StudentController::getInstance();
    $importSuccess = true;

    foreach ($studentsData as $data) {
        $student = new Student(
            id: null,
            firstName: trim($data['firstName']),
            lastName: trim($data['lastName']),
            isMale: filter_var($data['isMale'], FILTER_VALIDATE_BOOLEAN),
            classId: $classController->getIdFromName(trim($data['className']))
        );

        $result = $studentController->create($student);
        if (!$result['success']) {
            $importSuccess = false;
            $response['success'] = false;
            $response['message'] = "Fehler beim Importieren: " . ($result['error'] ?? 'Unbekannter Fehler');
            break;
        }
    }

    if ($importSuccess) {
        $response['message'] = 'Schüler erfolgreich importiert.';
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
            <legend>CSV-Import: Schüler</legend>
            <div class="import-header">
                <label id="upload-label" for="fileToUpload" class="custom-file-upload">
                    <abbr title="Format der CSV-Datei:
                    1. Erste Zeile: Kopfzeile mit den Spaltennamen.
                    2. Reihenfolge der Spalten: Nachname;Vorname;Geschlecht;Klasse.
                    Beispiel: Mustermann;Max;männlich;EFI22A
                    3. Trennzeichen: Semikolon ( ; )">
                        CSV
                    </abbr>-Datei auswählen:
                    <span id="fileName">Keine Datei ausgewählt</span>
                    <input type="file" id="fileToUpload" name="fileToUpload" accept=".csv" onchange="previewStudents()">
                </label>
            </div>
            <div class="import-preview" id="studentPreview"></div>
            <button id="submitButton" disabled onclick="event.preventDefault(); uploadStudents();" name="submitButton">
                Importieren
                <div class="spinner" id="spinner"></div>
            </button>
        </fieldset>
    </form>


    <script>
        const fileInput = document.getElementById('fileToUpload');
        const submitButton = document.getElementById('submitButton');
        const fileName = document.getElementById('fileName');
        const resultMessage = document.getElementById('resultMessage');
        const spinner = document.getElementById('spinner');

        let students = [];

        fileInput.addEventListener('change', function() {
            // Importieren-Button aktivieren, wenn Datei ausgewählt.
            submitButton.disabled = fileInput.files.length == 0 || students.length == 0;
            fileName.textContent = fileInput.files[0].name; // Dateiname anzeigen
        });

        function previewStudents() {
            students = []; // Leeren, falls noch alte Daten vorhanden sind.
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
                previewDiv.innerHTML = `
                <p style="text-align: center; margin: 0;">
                    Keine Schüler gefunden.<br>
                    <strong>Format der CSV-Datei:</strong><br>
                    1. Erste Zeile: Kopfzeile mit den Spaltennamen.<br>
                    2. Reihenfolge der Spalten: <em>Nachname;Vorname;Geschlecht;Klasse</em>.<br>
                    <strong>Beispiel:</strong> Mustermann;Max;männlich;EFI22A<br>
                    3. Trennzeichen: Semikolon ( ; )
                </p>
            `;
                return;
            }

            const table = document.createElement('table');
            const header = document.createElement('tr');
            header.innerHTML = '<th>Vorname</th><th>Nachname</th><th>Geschlecht</th><th>Klasse</th>';
            table.appendChild(header);

            students.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${student.firstName}</td><td>${student.lastName}</td><td>${student.isMale ? 'Männlich' : 'Weiblich'}</td><td>${student.className}</td>`;
                table.appendChild(row);
            });

            previewDiv.appendChild(table);
        }

        function uploadStudents() {
            const studentsJSON = JSON.stringify(students);

            // Ladespinner anzeigen
            resultMessage.innerHTML = '';
            spinner.style.display = 'inline-block';

            fetch('import_students_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: studentsJSON
                })
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    resultMessage.innerHTML = `<p class="resultMessage ${data.success ? 'success' : 'error'}">${data.message}</p>`;
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    console.error('Fehler:', error);
                    resultMessage.innerHTML = `<p class="resultMessage";>Fehler beim Importieren der Schüler.</p>`;
                });
        }
    </script>

</body>

</html>