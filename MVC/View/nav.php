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
    <link rel="icon" type="image/x-icon" href="../../resources/favicon.ico" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="nav-items">
            <ul>
                <li><a href="results.php">Ergebnisse</a></li>
                <?php if ($isAuthenticated): ?>
                    <li><a href="add_classresult.php">Klassenergebnis hinzufügen</a></li>
                    <li><a href="add_soloresult.php">Soloergebnis hinzufügen</a></li>
                    <li><a href="teachers_overview.php">Lehrerverwaltung</a></li>
                    <li><a href="competitions_overview.php">Stationenverwaltung</a></li>
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
                <li><a href="results.php">Ergebnisse</a></li>
                <?php if ($isAuthenticated): ?>
                    <li><a href="add_classresult.php">Klassenergebnis hinzufügen</a></li>
                    <li><a href="add_soloresult.php">Soloergebnis hinzufügen</a></li>
                    <li><a href="teachers_overview.php">Lehrerverwaltung</a></li>
                    <li><a href="competitions_overview.php">Stationenverwaltung</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="profile" id="profile">
            <?php if ($isAuthenticated): ?>
                <img src="../../resources/profile-authenticated.png" alt="Profilbild" />
                <div class="profile-initials" id="profile-initials"></div>
            <?php else: ?>
                <img src="../../resources/profile.webp" alt="Profilbild" />
            <?php endif; ?>
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
    const profileInitials = document.getElementById("profile-initials");

    document.addEventListener("DOMContentLoaded", () => {
        const isAuthenticated = <?php echo json_encode($isAuthenticated); ?>;

        if (isAuthenticated) {
            let userInitials = localStorage.getItem("Initials") ?? "";
            profileInitials.textContent = userInitials;
        }
    })

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
        fetch('../../Helper/logout.php', {
                method: 'POST'
            })
            .then(() => window.location.href = 'home.php')
            .catch(error => console.error('Fehler:', error));
    }
</script>


</html>