<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
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
</body>

</html>

<?php
require '../../vendor/autoload.php';

use MVC\Model\Student;
use MVC\Controller\ClassController;

$classController = new ClassController();

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
            classId: 9 // $line[3], // TODO Namen zu ID zuordnen
        );
    }

    fclose($file);
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