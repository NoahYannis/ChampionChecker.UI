<?php
require '../../vendor/autoload.php';

use MVC\Controller\TeacherController;
use MVC\Model\Teacher;

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Alle Felder sind zwar required, was jedoch in den Dev-Tools manuell entfernt werden kann. Daher explizit nochmal prüfen.
    if (empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['shortcode'])) {
        echo
        "<script>
            alert('Bitte füllen Sie alle Felder aus.');
            window.location.href = 'add_teachers.php';
        </script>";
        exit;
    }

    $teacherController = TeacherController::getInstance();
    $teacher = new Teacher(
        id: null,
        firstName: $_POST['firstname'],
        lastName: $_POST['lastname'],
        shortCode: $_POST['shortcode'],
        isParticipating: isset($_POST['participationToggle']) && $_POST['participationToggle'] === 'on' ? true : false,
        additionalInfo: $_POST['additional-info'],
        class: null,
    );

    $addResult = $teacherController->create($teacher);

    if ($addResult['success'] === true) {
        $teacherName = addslashes($_POST['firstname'] . ' ' . $_POST['lastname']);
        echo
        "<script>
            alert('$teacherName wurde erfolgreich hinzugefügt.');
            window.location.href = 'add_teachers.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('{$addResult["error"]}');</script>";
        echo "<script>window.location.href = 'add_teachers.php'</script>"; // Redirect, damit das Formular bei F5 nicht erneut abgeschickt wird
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
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/add_teachers.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Lehrer hinzufügen</title>
</head>

<body>
    <div class="main-content">
        <form method="post">
            <fieldset class="add-teachers-fieldset">
                <legend>Lehrer hinzufügen</legend>

                <label for="firstname">Vorname:</label>
                <input type="text" id="firstname" name="firstname" required>

                <label for="lastname">Nachname:</label>
                <input type="text" id="lastname" name="lastname" required>

                <label for="shortcode">
                    <abbr title="Das Kürzel muss einzigartig und zwischen 2 bis 5 Zeichen lang sein.">Kürzel:</abbr>
                </label> <input type="text" id="shortcode" name="shortcode" minlength="2" maxlength="5" required>

                <label for="additional-info">Zusätzliche Informationen:</label>
                <textarea id="additional-info" name="additional-info"></textarea>

                <label for="participationToggle">Teilnahme:</label>
                <label class="toggle">
                    <input type="checkbox" id="participationToggle" name="participationToggle">
                    <span class="slider"></span>
                </label>

                <input type="submit" value="Hinzufügen">
            </fieldset>
        </form>
    </div>
</body>

<script>
</script>

</html>