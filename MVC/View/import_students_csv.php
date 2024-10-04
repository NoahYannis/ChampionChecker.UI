<?php

declare(strict_types=1);

require '../../vendor/autoload.php';

use MVC\Model\Student;
use MVC\Controller\ClassController;
use MVC\Controller\StudentController;

session_start();

$classController = ClassController::getInstance();
$allClassNames = $_SESSION["classNames"] ?? $classController->getAllClassNames();

$studentController = StudentController::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_SESSION['students'])) {

    foreach ($_SESSION['students'] as $student) {
        $studentController->create($student);
    }
}


/**
 * Erstellt Schüler aus einer CSV-Datei.
 * @param string $csvFile Der Pfad zur CSV-Datei, die die Studentendaten enthält.
 * @return Student[] Ein Array von Student-Objekten.
 * @throws Exception Wenn die Datei nicht geöffnet werden kann.
 */
function createStudentsFromCSV($csvFile)
{
    $students = [];
    $file = fopen($csvFile, 'r');

    if (!$file) {
        throw new Exception("Could not open file: $csvFile");
    }

    // Kopfzeile überspringen
    fgetcsv($file, 0, ";");

    while (($line = fgetcsv($file, 0, ";")) !== FALSE) {
        if (count($line) < 3) {
            continue;
        }

        $students[] = new Student(
            id: null,
            firstName: $line[1],
            lastName: $line[0],
            isMale: $line[2] === 'männlich',
            classId: 9 // $line[3]
        );
    }

    fclose($file);

    if (empty(($students))) {
        throw new Exception("No students found in file: $csvFile");
    }

    $_SESSION['students'] = $students;

    return $students;
}

function printStudents($students)
{
    global $classController;
    echo "<table class='results-table'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Vorname</th>";
    echo "<th>Nachname</th>";
    echo "<th>Klasse</th>";
    echo "<th>Geschlecht</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student->getFirstName()) . "</td>";
        echo "<td>" . htmlspecialchars($student->getLastName()) . "</td>";
        $className = htmlspecialchars($classController->getClassName($student->getClassId()));
        echo "<td>{$className}</td>";
        echo "<td>" . ($student->getIsMale() ? 'männlich' : 'weiblich') . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}


$students = createStudentsFromCSV('../../EFI22aKlassenliste.csv');
printStudents($students);
?>


<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Import Students</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            height: 100vh;
            margin: 20px 0 0 0;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <form method="POST" action="">
        <div class="styled-select">
            <select name="classes" id="classes" onchange="this.form.submit()" <?= empty($allClassNames) ? 'disabled' : '' ?>>
                <option value="default">Klasse auswählen:</option>
                <?php foreach ($allClassNames as $classNames): ?>
                    <option value="<?= htmlspecialchars($classNames) ?>"
                        <?= isset($_POST['classes']) && $_POST['classes'] == $classNames ? 'selected' : '' ?>>
                        <?= htmlspecialchars($classNames) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="submit" value="Abschicken">Abschicken</button>
        </div>
    </form>

</body>

</html>