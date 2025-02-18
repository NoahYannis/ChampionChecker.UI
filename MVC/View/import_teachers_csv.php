<?php
require '../../vendor/autoload.php';

use MVC\Controller\UserController;
use MVC\Controller\TeacherController;
use MVC\Model\Teacher;
use MVC\Model\Role;

session_start();

if (UserController::getInstance()->getRole() !== Role::Admin) {
    header("Location: home.php");
    exit();
}


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
        echo $response['message'];
        exit;
    }

    $teachersData = json_decode($postData, true);
    $importSuccess = true;

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo $response['message'];
        exit;
    }

    $teacherController = TeacherController::getInstance();

    foreach ($teachersData as $data) {
        $teacher = new Teacher(
            id: null, // wird von API gesetzt
            firstName: htmlspecialchars($data['firstName'], ENT_QUOTES, 'UTF-8'),
            lastName: htmlspecialchars($data['lastName'], ENT_QUOTES, 'UTF-8'),
            shortCode: htmlspecialchars($data['shortCode'], ENT_QUOTES, 'UTF-8'),
            classes: null,
            additionalInfo: null
        );

        $result = $teacherController->create($teacher);
        if (!$result['success']) {
            $importSuccess = false;
            $response['success'] = false;
            $response['message'] = "Fehler beim Importieren: " . ($result['error'] ?? 'Unbekannter Fehler');
            echo $response['message'];
            exit;
        }
    }

    if ($importSuccess) {
        $response['message'] = 'Lehrer erfolgreich importiert.';
        if (isset($_SESSION['teachers'])) {
            unset($_SESSION['teachers']);
            unset($_SESSION['overview_teachers_timestamp']);
        }
    }

    echo $response['message'];
    exit;
}

$mode = $_GET['mode'] ?? null;

// Seite wurde direkt aufgerufen statt über die Lehrerverwaltung, Nav-Menü für die Seite einbinden.
if (!isset($mode)) {
    include 'nav.php';
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
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
            <div class="scrollable-container" id="teacherPreview"></div> <!-- Import-Vorschau -->
            <button class="submitButton" id="submitButton" disabled onclick="event.preventDefault(); uploadTeachers();" name="submitButton">
                Importieren
                <div class="spinner" id="spinner"></div>
            </button>
        </fieldset>
    </form>

    <script src="../../scripts/import_teachers_csv.js"></script>
</body>

</html>