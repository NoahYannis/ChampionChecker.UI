<?php

require '../../../vendor/autoload.php';

use MVC\Controller\ClassController;
use MVC\Controller\StudentController;


$inputJSON = file_get_contents('php://input');
$studentParticipants = json_decode($inputJSON, true);

function getStudentClassName($id)
{
    $student = $_SESSION['students'][$id] ?? StudentController::getInstance()->getById($id);
    return ClassController::getInstance()->getClassName($student->getClassId());
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Stationsauswertung</title>
</head>

<body>
    <table class="table-style">
        <thead>
            <tr>
                <th>#</th>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Klasse</th>
                <th>Ergebnis</th>
                <th>Notizen</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            foreach ($studentParticipants as $id => $participant):
                $class = getStudentClassName($id);
            ?>
                <tr>
                    <td data-id="<?= $id ?>"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($participant['firstName']) ?></td>
                    <td><?= htmlspecialchars($participant['lastName']) ?></td>
                    <td><?= $class ?></td>
                    <td>
                        <input type="number">
                    </td>
                    <td>
                        <input type="text">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>