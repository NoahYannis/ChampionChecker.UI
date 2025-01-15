<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}


use MVC\Controller\CompetitionController;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_X_CUSTOM_ATTRIBUTE'])) {
    $competitions = loadAllCompetitions(300);
    echo json_encode($competitions);
    exit;
}


function loadAllCompetitions($cacheDuration = 300): array
{
    $compController = CompetitionController::getInstance();

    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['overview_competitions']) && isset($_SESSION['overview_competitions_timestamp'])) {
        if ((time() - $_SESSION['overview_competitions_timestamp']) < $cacheDuration) {
            return $_SESSION['overview_competitions'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $competitions = $compController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['overview_competitions'] = $competitions;
    $_SESSION['overview_competitions_timestamp'] = time();

    return $competitions;
}



include 'nav.php';
?>


<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/competitions_overview.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Stationenverwaltung</title>
</head>

<body>
    <header>
        <h1>Stationenverwaltung</h1>
    </header>

    <div id="result-message" class="result-message hidden"></div>
    <div class="button-container">
        <button class="circle-button add-button" id="" onclick="window.location.href='#'">
            <i class="fas fa-plus"></i>
        </button>
        <button class="circle-button edit-button" id="edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden" id="cancel-button">
            <i class="fas fa-times"></i>
        </button>
        <div class="spinner" id="spinner"></div>
    </div>

    <section>
    </section>

    <script>
        let isEditing = false;
        let sortDirections = {};
        let storedValues = [];
        let changedCompetitions = [];

        const editButton = document.getElementById("edit-button");
        const editButtonIcon = document.querySelector(".edit-button i");
        const cancelButton = document.querySelector(".cancel-button");
        const addButton = document.querySelector(".add-button");
        const spinner = document.getElementById('spinner');

        async function generateCompetitionTable() {
            spinner.style.display = "block";
            editButton.disabled = true;
            addButton.disabled = true;

            try {
                const response = await fetch('competitions_overview.php', {
                        method: 'GET',
                        headers: {
                            'X-Custom-Attribute': 'generateCompetitionTable',
                        }
                    }).then(response => response.json())
                    .then(data => console.log(data));
            } catch (error) {
                console.error('Error:', error);
            } finally {
                spinner.style.display = "none";
                editButton.disabled = false;
                addButton.disabled = false;
            }
        }

        document.addEventListener("DOMContentLoaded", () => generateCompetitionTable());
    </script>

</body>

</html>