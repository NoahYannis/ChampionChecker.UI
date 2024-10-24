<!DOCTYPE html>
<html lang="de">

<?php
require '../../vendor/autoload.php';
include 'nav.php'; ?>

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
        <form class="register-form" action="signup.php" method="post">
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
            <button>Login</button>
            <button>Login mit Moodle</button>
        </div>
    </div>
</body>



</html>