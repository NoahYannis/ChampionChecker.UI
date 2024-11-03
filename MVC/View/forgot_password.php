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

if (isset($_POST['newPassword']) && $token && $email) {
    $userController = UserController::getInstance();
    $passwordResetSuccessful = $userController->resetPassword($email, $token, $_POST['newPassword']);

    if ($passwordResetSuccessful) {
        header("Location: login.php");
        exit();
    }
}

if (isset($_POST['email']) && !$passwordResetEmailClicked) {
    $userController = UserController::getInstance();
    $requestResetEmailSuccess = $userController->forgotPassword($_POST['email']);

    if($requestResetEmailSuccess) {
        echo "<script>alert('Eine E-Mail zum Zurücksetzen des Passworts wurde an " . htmlspecialchars($_POST['email'], ENT_QUOTES) . " gesendet.');</script>";
    }
    else {
        echo "<script>alert('Fehler beim Zurücksetzen des Passworts.');</script>";
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

</html>