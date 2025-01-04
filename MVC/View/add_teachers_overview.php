<?php
require '../../vendor/autoload.php';

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/add_teachers.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Lehrer hinzufügen</title>
</head>

<body>
    <div class="main-content">

        <div class="add-teachers-mode">
            <abbr title="Importieren Sie mehrere Lehrer auf einmal aus einer bestehenden CSV-Datei.">
                <label for="toggleSwitch">CSV-Import</label>
            </abbr>
            <label class="toggle">
                <input type="checkbox" id="toggleSwitch" onclick="showForm()">
                <span class="slider"></span>
            </label>
            <abbr title="Fügen Sie Lehrer manuell hinzu, indem Sie das Formular ausfüllen und alle erforderlichen Informationen eingeben.">
                <label for="toggleSwitch">Manuell</label>
        </div>

        <div id="form-container">
            <!-- View wird durch JavaScript geladen -->
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const mode = urlParams.get('mode');
            const toggleSwitch = document.getElementById('toggleSwitch');
            toggleSwitch.checked = mode === 'manual';
            loadForm(mode === 'manual' ? 'add_teachers_manual.php' : 'import_teachers_csv.php');
        });

        function showForm() {
            const toggleSwitch = document.getElementById('toggleSwitch');
            const mode = toggleSwitch.checked ? 'manual' : 'csv';
            const url = new URL(window.location.href);
            url.searchParams.set('mode', mode); // 'mode' in der URL setzen
            history.pushState({}, '', url);
            loadForm(mode === 'manual' ? 'add_teachers_manual.php' : 'import_teachers_csv.php');
        }

        function loadForm(url) {
            const formContainer = document.getElementById('form-container');
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    formContainer.innerHTML = html;

                    const scriptSrc = url.includes('import_teachers_csv.php') ?
                        '../../scripts/import_teachers_csv.js' :
                        '../../scripts/add_teachers_manual.js';

                    const script = document.createElement('script');
                    script.src = scriptSrc;
                    document.body.appendChild(script);
                })
                .catch(error => console.error('Fehler beim Laden des Formulars:', error));
        }
    </script>

</body>

</html>