<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
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
                <li><a href="#">Registrieren</a></li>
                <li><a href="#">Anmelden</a></li>
                <li><a href="#">Einstellungen</a></li>
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
</script>


</html>