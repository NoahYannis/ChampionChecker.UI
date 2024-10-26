<!DOCTYPE html>
<html lang="de">

<?php
require '../../vendor/autoload.php';
include 'nav.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/reset_password.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Passwort zurücksetzen</title>
</head>

<body>
    <div class="main-content">
        <form class="reset-password-form" method="post">
            <fieldset>
                <legend>Passwort zurücksetzen</legend>

                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>
                <div class="button-container">
                    <input type="submit" value="Bestätigen">
                </div>
            </fieldset>
        </form>
    </div>
</body>

</html>