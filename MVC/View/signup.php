<?php
require '../../vendor/autoload.php';

use MVC\Controller\UserController;
use MVC\Model\User;

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

    $registerResult = $userController->register($user);

    if ($registerResult['success'] === true) {
        $userName = addslashes($_POST['firstname'] . ' ' . $_POST['lastname']);
        echo "<script>
            alert('Willkommen, $userName. Ihre Registrierung war erfolgreich.');
            window.location.href = 'home.php';
        </script>";
        exit;
    } else {
        $errorDescription = $registerResult['response']['errors'][0]['description'] ?? 'Unbekannter Fehler';
        $errorDescription = addslashes($errorDescription); // Sonderzeichen escapen
        echo "<script>alert('$errorDescription');</script>";
        echo "<script>window.location.href = 'signup.php'</script>"; // Redirect, damit das Formular bei F5 nicht erneut abgeschickt wird
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
    <link rel="stylesheet" type="text/css" href="../../styles/signup.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Registrierung</title>
</head>

<body>
    <div class="main-content">
        <form class="signup-form" method="post">
            <fieldset class="signup-fieldset">
                <legend>Registrierung</legend>

                <label for="firstname">Vorname:</label>
                <input type="text" id="firstname" name="firstname" required>

                <label for="lastname">Nachname:</label>
                <input type="text" id="lastname" name="lastname" required>

                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>

                <label for="repeat-password">Passwort wiederholen:</label>
                <input type="password" id="repeat-password" name="repeat-password" required>

                <input type="submit" value="Registrieren">
            </fieldset>
        </form>

        <div class="divider-container">
            <hr class="divider">
            <span class="divider-text">Bereits registriert?</span>
        </div>

        <div class="button-container">
            <button id="login-email" onclick="window.location.href = 'login.php'">Login E-Mail</button>
            <button class="moodle-login" onclick="window.location.href = 'login.php'">
                <img src="../../moodle-logo.svg" alt="Moodle Logo">
            </button>
        </div>
    </div>
</body>

<script>
    // Register-Validierung
    document.querySelector('.register-form').addEventListener('submit', function(event) {

        // Vorname und Nachname prüfen
        const lastname = document.getElementById('lastname').value.trim();
        const firstname = document.getElementById('firstname').value.trim();

        if (!/^[\p{L}äöüÄÖÜß]+(-[\p{L}äöüÄÖÜß]+)*$/u.test(firstname) ||
            !/^[\p{L}äöüÄÖÜß]+(-[\p{L}äöüÄÖÜß]+)*$/u.test(lastname)) {
            alert('Vor- und Nachname dürfen nur Buchstaben und Bindestriche enthalten.');
            event.preventDefault();
            return;
        }

        // E-Mail prüfen
        const email = document.getElementById('email').value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            event.preventDefault();
            return;
        }

        // Passwort prüfen => gültiges Passwort enthält mindestens 8 Zeichen, Groß- + Kleinbuchstaben, Zahlen und Sonderzeichen
        const password = document.getElementById('password').value.trim();
        if (password.length < 8 ||
            !/[A-Z]/.test(password) || // Großbuchstaben
            !/[a-z]/.test(password) || // Kleinbuchstaben
            !/[0-9]/.test(password) || // Zahlen
            !/[^A-Za-z0-9]/.test(password)) // Sonderzeichen
        {
            alert('Das Passwort muss mindestens 8 Zeichen lang sein sowie Großbuchstaben, Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.');
            event.preventDefault();
        }

        const repeatPassword = document.getElementById('repeat-password').value.trim();

        if (password !== repeatPassword) {
            alert('Die Passwörter stimmen nicht überein.');
            event.preventDefault();
        }
    });
</script>

</html>