<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

include 'nav.php';


use MVC\Controller\TeacherController;
use MVC\Controller\ClassController;

$teacherController = TeacherController::getInstance();
$classController = ClassController::getInstance();

function loadAllTeachers($cacheDuration = 300): array
{
    global $teacherController;

    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['overview_teachers']) && isset($_SESSION['overview_teachers_timestamp'])) {
        if ((time() - $_SESSION['overview_teachers_timestamp']) < $cacheDuration) {
            return $_SESSION['overview_teachers'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $teachers = $teacherController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['overview_teachers'] = $teachers;
    $_SESSION['overview_teachers_timestamp'] = time();

    return $teachers;
}



function printTeachers($teachers)
{
    if (isset($_SESSION['overview_teachers_timestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['overview_teachers_timestamp']) . "<br></p>";
    }

    global $classController;

    echo "<table class='table-style'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Nachname</th>";
    echo "<th>Vorname</th>";
    echo "<th>Kürzel</th>";
    echo "<th>Turnier-Teilnahme</th>";
    echo "<th>Klassen</th>";
    echo "<th>Sonstige Informationen</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($teachers as $teacher) {
        echo "<tr>";
        echo "<td><div class='td-content'>" . htmlspecialchars($teacher->getLastName()) . "</div></td>";
        echo "<td><div class='td-content'>" . htmlspecialchars($teacher->getFirstName()) . "</div></td>";
        echo "<td><div class='td-content'>" . htmlspecialchars($teacher->getShortCode()) . "</div></td>";
        echo "<td><div class='td-content'>" . ($teacher->getIsParticipating() == true ? "Ja" : "Nein") . "</div></td>";

        $classes = $teacher->getClasses() ?? [];
        $classNames = [];

        foreach ($classes as $class) {
            $className = $classController->getClassName($class['id']);
            if ($className) {
                $classNames[] = htmlspecialchars($className);
            }
        }

        echo "<td><div class='td-content'>" . (!empty($classNames) ? implode(', ', $classNames) : '-') . "</div></td>";
        echo "<td><div class='td-content'>" . (empty($teacher->getAdditionalInfo()) ? '-' : htmlspecialchars($teacher->getAdditionalInfo())) . "</div></td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}


$teachers = loadAllTeachers();

// Lehrer nach Nachnamen sortieren
usort($teachers, function ($teacherA, $teacherB) {
    return strcmp($teacherA->getLastName(), $teacherB->getLastName());
});
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Lehrerverwaltung</title>
</head>

<body>
    <header>
        <h1>Lehrerverwaltung</h1>
    </header>

    <button onclick="window.location.href='add_teachers_overview.php?mode=manual'">Lehrer hinzufügen</button>

    <section>
        <?php printTeachers($teachers); ?>
    </section>

</body>

</html>