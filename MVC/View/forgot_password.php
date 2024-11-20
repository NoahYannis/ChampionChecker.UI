<!DOCTYPE html>
<html lang="de">

<?php
require '../../vendor/autoload.php';

use MVC\Controller\UserController;

$passwordResetEmailClicked = false;
$token = null;
$email = null;

// Query-Parameter sind im Email-Link enthalten
$token = $_GET['token'] ?? null;
$email = $_GET['email'] ?? null;

if ($token && $email) {
    $passwordResetEmailClicked = true;
}

// Eingabe des neuen Passworts (nach Klick auf Link in E-Mail)
if (isset($_POST['newPassword']) && $token && $email) {
    $userController = UserController::getInstance();
    $passwordResetSuccessful = $userController->resetPassword($email, $token, $_POST['newPassword']);

    if ($passwordResetSuccessful['success']) {
        echo "<script>
        alert('Ihr Passwort wurde erfolgreich geändert.');
        window.location.href = 'login.php';
        </script>";
    }
    else {
        $error = $passwordResetSuccessful['response']['errors'][0]['description'] ?? '';
        $error = addslashes($error); // Sonderzeichen escapen
        echo "<script>alert('$error');</script>";
    }
}

// Eingabe der E-Mail, an die das Token geschickt wird
if (isset($_POST['email']) && !$passwordResetEmailClicked) {
    $userController = UserController::getInstance();
    $requestResetEmailSuccess = $userController->forgotPassword($_POST['email']);

    if ($requestResetEmailSuccess['success']) {
        echo "<script>alert('Eine E-Mail zum Zurücksetzen des Passworts wurde an " . htmlspecialchars($_POST['email'], ENT_QUOTES) . " gesendet.');</script>";
    } else {
        $error = $requestResetEmailSuccess['response']['errors'][0]['description'] ?? '';
        $error = addslashes($error); // Sonderzeichen escapen
        echo "<script>alert('$error');</script>";
    }
}

include 'nav.php';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/forgot_password.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Passwort zurücksetzen</title>
</head>

<body>
    <div class="main-content">
        <form class="forgot-password-form" method="post">
            <fieldset>
                <legend>Passwort zurücksetzen</legend>

                <?php if ($passwordResetEmailClicked): ?>
                    <label for="newPassword">Neues Passwort:</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <!-- Versteckte Felder für token und email -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <?php else: ?>
                    <label for="email">E-Mail:</label>
                    <input type="email" autocomplete="email" id="email" name="email" required>
                <?php endif; ?>

                <div class="button-container">
                    <input type="submit" value="Bestätigen">
                </div>
            </fieldset>
        </form>
    </div>
</body>

<script>
    document.querySelector('.forgot-password-form').addEventListener('submit', function(event) {
        const newPassword = document.getElementById('newPassword');
        const email = document.getElementById('email');

        if (newPassword) {
            const password = newPassword.value.trim();

            if (password.length < 8 || // Mindestlänge 8 Zeichen
                !/[A-Z]/.test(password) || // Mindestens ein Großbuchstabe
                !/[a-z]/.test(password) || // Mindestens ein Kleinbuchstabe
                !/[0-9]/.test(password) || // Mindestens eine Zahl
                !/[^A-Za-z0-9]/.test(password)) { // Mindestens ein Sonderzeichen
                alert('Das Passwort muss mindestens 8 Zeichen lang sein und Großbuchstaben, Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.');
                event.preventDefault();
                return;
            }
        }

        // E-Mail-Validierung
        if (email && !email.checkValidity()) {
            alert('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            event.preventDefault();
            return;
        }
    });
</script>

</html>