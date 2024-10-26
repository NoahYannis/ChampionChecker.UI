<!DOCTYPE html>
<html lang="de">

<?php
require '../../vendor/autoload.php';
include 'nav.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/login.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Login</title>
</head>

<body>
    <div class="main-content">
        <form class="login-form" method="post">
            <fieldset>
                <legend>Login</legend>

                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>

                <div class="button-container">
                    <input type="submit" value="Login">
                    <button class="moodle-login">
                        <img src="../../moodle-logo.svg" alt="Moodle Logo">
                    </button>
                </div>
            </fieldset>
        </form>

        <div class="divider-container">
            <hr class="divider">
            <span class="divider-text">Noch nicht registriert?</span>
        </div>

        <div>
            <button class="btn-register">Registrieren</button>
        </div>
    </div>
</body>



</html>