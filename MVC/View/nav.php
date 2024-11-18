<?php
$isAuthenticated = isset($_COOKIE['ChampionCheckerCookie']);
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
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="nav-items">
            <ul>
                <li><a href="results.php">Ergebnisse</a></li>
                <li><a href="add_classresult.php">Klassenergebnis hinzufügen</a></li>
                <li><a href="add_soloresult.php">Soloergebnis hinzufügen</a></li>
                <li><a href="import_students_csv.php">Schüler-CSV-Import</a></li>
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
                    <img src="../../logo.png" alt="ChampionChecker Logo" />
                </a>
            </div>
        </div>
        <div class="nav-items">
            <ul>
                <li><a href="results.php" data-text="Ergebnisse">Ergebnisse</a></li>
                <li><a href="add_classresult.php" data-text="Klassenergebnis hinzufügen">Klassenergebnis hinzufügen</a></li>
                <li><a href="add_soloresult.php" data-text="Soloergebnis hinzufügen">Soloergebnis hinzufügen</a></li>
                <li><a href="import_students_csv.php" data-text="Schüler-CSV-Import">Schüler-CSV-Import</a></li>
            </ul>
        </div>
        <div class="profile" id="profile">
            <img src="../../profile.webp" alt="Profilbild" />
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
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Einstellungen
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

    // Sidebar bei Klick auf Hamburger-Icon toggeln
    hamburgerInput.addEventListener('change', function() {
        if (this.checked) {
            sideBar.style.left = '0';
        } else {
            sideBar.style.left = '-250px';
        }
    });

    profilePic.addEventListener('click', function(event) {
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
        if (!hamburgerMenu.contains(event.target)) {
            sideBar.style.left = '-250px';
            hamburgerInput.checked = false;
        }
    });

    function logout() {
        fetch('../../logout.php', {
                method: 'POST'
            })
            .then(() => window.location.href = 'home.php')
            .catch(error => console.error('Fehler:', error));
    }
</script>


</html>