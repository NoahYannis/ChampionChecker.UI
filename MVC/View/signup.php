<!DOCTYPE html>
<html lang="de">

<?php
require '../../vendor/autoload.php';

use MVC\Controller\UserController;
use MVC\Model\User;

include 'nav.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Alle Felder sind zwar required, was jedoch in den Dev-Tools manuell entfernt werden kann. Daher explizit nochmal prüfen.
    if (empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || empty($_POST['password'])) {
        echo "<script>alert('Bitte füllen Sie alle Felder aus.');</script>";
        exit;
    }

    $userController = UserController::getInstance();
    $user = new User(
        firstName: $_POST['firstname'],
        lastName: $_POST['lastname'],
        email: $_POST['email'],
        password: $_POST['password']
    );

    try {
        $success = $userController->register($user);
        if ($success) {
            // Erstmal nur zur Login-Seite weiterleiten, später direkt einloggen
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {
        echo "<script>alert('Fehler bei der Registrierung: {$e->getMessage()}');</script>";
    }
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/signup.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Registrierung</title>
</head>

<body>
    <div class="main-content">
        <form class="register-form" method="post">
            <fieldset>
                <legend>Registrierung</legend>

                <label for="firstname">Vorname:</label>
                <input type="text" id="firstname" name="firstname" required>

                <label for="lastname">Nachname:</label>
                <input type="text" id="lastname" name="lastname" required>

                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>

                <input type="submit" value="Registrieren">
            </fieldset>
        </form>

        <div class="divider-container">
            <hr class="divider">
            <span class="divider-text">Bereits registriert?</span>
        </div>

        <div class="button-container">
            <button id="login-email">Login E-Mail</button>
            <button class="moodle-login">
                <img src="../../moodle-logo.svg" alt="Moodle Logo">
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var emailButton = document.getElementById('login-email');
            emailButton.addEventListener('click', function() {
                window.location.href = 'login.php';
            });

            var moodleButton = document.querySelector('.moodle-login');
            moodleButton.addEventListener('click', function() {
                window.location.href = 'login.php';
            });
        });
    </script>
</body>


</html>