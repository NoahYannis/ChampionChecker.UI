<?php
require '../../vendor/autoload.php';

use MVC\Controller\UserController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Alle Felder sind zwar required, was jedoch in den Dev-Tools manuell entfernt werden kann. Daher explizit nochmal prüfen.
    if (empty($_POST['email']) || empty($_POST['password'])) {
        echo "<script>alert('Bitte füllen Sie alle Felder aus.');</script>";
        exit;
    }

    $userController = UserController::getInstance();
    $loginResult = $userController->login($_POST['email'], $_POST['password']);

    if ($loginResult['success'] === true) {
        $userName = addslashes($loginResult['response'] ?? '');
        echo "<script>
            alert('Willkommen, $userName. Ihr Login war erfolgreich.');
            window.location.href = 'home.php';
        </script>";
        exit;
    } else {
        $errorDescription = $loginResult['response']['errors'][0]['description'] ?? '';
        $errorDescription = addslashes($errorDescription); // Sonderzeichen escapen
        echo "<script>alert('$errorDescription');</script>";
        echo "<script>window.location.href = 'login.php'</script>"; // Redirect, damit das Formular bei F5 nicht erneut abgeschickt wird
    }
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
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
                <input type="email" autocomplete="email" id="email" name="email" required>

                <label for="password">Passwort:</label>
                <div class="password-container">
                    <input type="password" autocomplete="current-password" id="password" name="password" required>
                    <span id="password-toggle" class="fa fa-fw fa-eye toggle-password-icon"></span>
                </div>
                <a href="forgot_password.php" class="forgot-password">Passwort vergessen?</a>

                <div class="flex-container">
                    <input type="submit" value="Login">
                </div>
            </fieldset>
        </form>

        <div class="divider-container">
            <span class="divider-text">Noch nicht registriert?</span>
        </div>

        <div>
            <button class="btn-register" onclick="window.location.href = 'signup.php'">Registrieren</button>
        </div>
    </div>
</body>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const passwordInput = document.getElementById("password");
        const togglePasswordIcon = document.getElementById('password-toggle');

        // Passwort ein/ausblenden.
        togglePasswordIcon.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                togglePasswordIcon.classList.remove('fa-eye');
                togglePasswordIcon.classList.add('fa-eye-slash');
            } else {
                togglePasswordIcon.classList.remove('fa-eye-slash');
                togglePasswordIcon.classList.add('fa-eye');
            }
        });
    })
</script>

</html>