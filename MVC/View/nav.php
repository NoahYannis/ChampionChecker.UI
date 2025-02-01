<?php

use MVC\Controller\UserController;
use MVC\Model\Role;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userRole = UserController::getInstance()->getRole();

$icon = match ($userRole->name) {
    'Gast' => 'fas fa-eye',
    'Schüler' => 'fas fa-user-graduate',
    'Lehrkraft' => 'fas fa-chalkboard-teacher',
    'Admin' => 'fas fa-user-shield',
    default => 'fas fa-question-circle',
};

$isAuthenticated = isset($_COOKIE['ChampionCheckerCookie']);
$profileImageUrl = $isAuthenticated
    ? '../../resources/profile-authenticated.png'
    : '../../resources/profile.webp';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/nav.css" />
    <link rel="icon" type="image/x-icon" href="../../resources/favicon.ico" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="nav-items">
            <ul>
                <li><a href="results.php">Ergebnisse</a></li>
                <?php if (UserController::getInstance()->getRole()->value > 1): ?> <!-- Lehrkraft oder Admin -->
                    <li><a href="add_classresult.php">Klassenergebnis hinzufügen</a></li>
                    <li><a href="add_soloresult.php">Einzelergebnis hinzufügen</a></li>
                    <hr />
                    <li><a href="competitions_overview.php">Stationenverwaltung</a></li>
                <?php endif; ?>

                <?php if (UserController::getInstance()->getRole() === Role::Admin): ?>
                    <li><a href="teachers_overview.php">Lehrerverwaltung</a></li>
                <?php endif; ?>

                <?php if (UserController::getInstance()->getRole()->value > 1): ?> <!-- Lehrkraft oder Admin -->
                    <li><a href="students_overview.php">Schülerübersicht</a></li>
                <?php endif; ?>

            </ul>
        </div>
    </aside>
    <nav class="nav-bar">
        <div class="hamburger-logo-group">
            <label class="hamburger-menu">
                <input type="checkbox" id="hamburger-input" />
            </label>
            <div class="nav-logo">
                <a href="home.php">
                    <img src="../../resources/logo.png" alt="ChampionChecker Logo" />
                </a>
            </div>
        </div>
        <div class="nav-items">
            <ul>
                <li class="dropdown">
                    <a href="results.php">Ergebnisse</a>
                    <?php if (UserController::getInstance()->getRole()->value > 1): ?> <!-- Lehrkraft oder Admin -->
                        <ul class="dropdown-menu">
                            <li><a href="results.php">Ergebnisse ansehen</a></li>
                            <li><a href="add_classresult.php">Klassenergebnis hinzufügen</a></li>
                            <li><a href="add_soloresult.php">Einzelgebnis hinzufügen</a></li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php if (UserController::getInstance()->getRole()->value > 1): ?> <!-- Lehrkraft oder Admin -->
                    <li class="dropdown">
                        <a href="competitions_overview.php">Stationen</a>
                        <ul class="dropdown-menu">
                            <li><a href="competitions_overview.php">Stationenverwaltung</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (UserController::getInstance()->getRole()->value > 1): ?> <!-- Lehrkraft oder Admin -->
                    <li class="dropdown">
                        <a href="students_overview.php">Schüler</a>
                        <ul class="dropdown-menu">
                            <li><a href="students_overview.php">Schülerübersicht</a></li>
                            <li><a href="import_students_csv.php">CSV-Import Schüler</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (UserController::getInstance()->getRole() === Role::Admin): ?>
                    <li class="dropdown">
                        <a href="teachers_overview.php">Lehrer</a>
                        <ul class="dropdown-menu">
                            <li><a href="teachers_overview.php">Lehrerverwaltung</a></li>
                            <li><a href="add_teachers_manual.php">Lehrer hinzufügen</a></li>
                            <li><a href="import_teachers_csv.php">CSV-Import Lehrer</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="profile" id="profile"
            style="background-image: url('<?= $profileImageUrl; ?>');"
            data-content-initials="">
        </div>

        <div class="profile-menu" id="profile-menu" style="display: none;">
            <ul>
                <?php if ($isAuthenticated): ?>
                    <li>
                        <a href="#" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i> Ausloggen
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="<?php echo $icon; ?>"></i>Rolle: <?php echo $userRole->name; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="signup.php">
                            <i class="fas fa-user-plus"></i> Registrieren
                        </a>
                    </li>
                    <li>
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Anmelden
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="<?php echo $icon; ?>"></i>Rolle: <?php echo $userRole->name; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</body>

<script>
    const profilePic = document.getElementById('profile');
    const profileMenu = document.getElementById('profile-menu');
    const hamburgerInput = document.getElementById('hamburger-input');
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    const sideBar = document.querySelector('.sidebar');

    document.addEventListener("DOMContentLoaded", () => {
        const isAuthenticated = <?php echo json_encode($isAuthenticated); ?>;

        (isAuthenticated) && profilePic.setAttribute(
            'data-content-initials',
            localStorage.getItem("Initials") ?? ""
        );
    })

    // Sidebar bei Klick auf Hamburger-Icon toggeln
    hamburgerInput.addEventListener('change', function() {
        sideBar.classList.toggle("open")
    });

    profilePic.addEventListener('click', function(event) {
        event.preventDefault();
        if (!profilePic.contains(event.target) && !profileMenu.contains(event.target)) {
            profileMenu.style.display = 'none';
            return;
        }
        profileMenu.style.display = 'block';
        profileMenu.style.left = `${event.pageX - 200}px`; // Für mehr Platz etwas nach links verschieben
        profileMenu.style.top = `${event.pageY + 30}px`;
    });

    // Click-Event ebenfalls auf document für Klicks außerhalb des Profilbildes
    document.addEventListener('click', function(event) {
        if (!profilePic.contains(event.target) && !profileMenu.contains(event.target)) {
            profileMenu.style.display = 'none';
        }

        // Hamburger-Menü ausblenden, wenn außerhalb geklickt wird
        if (!hamburgerMenu.contains(event.target) && hamburgerInput.checked) {
            hamburgerInput.checked = false;
            sideBar.classList.toggle("open");
        }
    });

    function logout() {
        fetch('../../Helper/logout.php', {
                method: 'POST'
            })
            .then(() => window.location.href = 'home.php')
            .catch(error => console.error('Fehler:', error));
    }
</script>


</html>