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

    $success = $userController->login($_POST['email'], $_POST['password']);
    if ($success) {
        echo "<script>alert('Login erfolgreich.');</script>";
        header("Location: home.php");
        exit;
    } else {
        echo "<script>alert('Login fehlgeschlagen.');</script>";
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
                <input type="password" autocomplete="current-password id=" password" name="password" required>
                <a href="forgot_password.php" class="forgot-password">Passwort vergessen?</a>

                <div class="button-container">
                    <input type="submit" value="Login">
                    <button class="moodle-login" onclick="window.location.href = 'login.php'">
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
            <button class="btn-register" onclick="window.location.href = 'signup.php'">Registrieren</button>
        </div>
    </div>
</body>



</html>