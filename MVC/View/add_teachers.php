<?php
require '../../vendor/autoload.php';

use MVC\Controller\TeacherController;
use MVC\Model\Teacher;

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
            <fieldset>
                <legend>Lehrer hinzufügen</legend>

                <label for="firstname">Vorname:</label>
                <input type="text" id="firstname" name="firstname" required>

                <label for="lastname">Nachname:</label>
                <input type="text" id="lastname" name="lastname" required>

                <label for="shortcode">Kürzel:</label>
                <input type="text" id="shortcode" name="shortcode" required>

                <label for="additional-info">Zusätzliche Informationen:</label>
                <textarea id="additional-info" name="additional-info"></textarea>

                <label for="participationToggle">Teilnahme:</label>
                <label class="toggle">
                    <input type="checkbox" id="participationToggle">
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