<?php

require '../../vendor/autoload.php'; // Lädt alle benötigten Klassen automatisch aus MVC-Ordner, siehe composer.json.

use MVC\Controller\CompetitionController;
use MVC\Controller\UserController;

session_start();

$userRole = UserController::getInstance()->getRole();

// Für Zugriff mindestens Rolle Lehrkraft
if ($userRole->value < 2) {
	header("Location: home.php");
	exit();
}


if (!isset($_SESSION['soloresult_competitions'])) {
	$competitions = CompetitionController::getInstance()->getAll();
	$soloCompetitions = [];

	foreach ($competitions as $comp) {
		if (!$comp->getIsTeam()) {
			$soloCompetitions[] = $comp;
		}
	}

	$_SESSION['soloresult_competitions'] = $soloCompetitions;
} else {
	$soloCompetitions = $_SESSION['soloresult_competitions'];
}

include 'nav.php';
?>

<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" type="text/css" href="../../styles/base.css" />
	<link rel="stylesheet" type="text/css" href="../../styles/solo_results.css" />
	<script src="https://cdn.jsdelivr.net/npm/less"></script>
	<meta charset="utf-8">
	<meta name="description" content="Einzelergebnisse eintragen">
	<title>Einzelergebnisse eintragen</title>
</head>

<body>

	<header>
		<h1>Einzelpunkte</h1>
	</header>

	<div class="flex-container">
		<select id="competitions">
			<option selected disabled value="default">Station auswählen:</option>
			<?php foreach ($soloCompetitions as $comp): ?>
				<option value="<?= htmlspecialchars($comp->getName()) ?>"
					data-mode="<?= (stripos($comp->getName(), 'Tischtennis') !== false) ? 'tournament' : 'competition' ?>"
					<?= (count($comp->getStudentParticipants()) == 0) ? 'disabled' : '' ?>>
					<?= htmlspecialchars($comp->getName()) ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>


	<div id="result-form"></div> <!-- Hier wird nach Auswahl einer Option das Ergebnisformular angezeigt-->

	<button id="submit-station" class="submit-station hidden">Station abschließen</button>


	<script>
		let compSelect = document.getElementById("competitions");
		let submitStationButton = document.getElementById("submit-station");
		let resultForm = document.getElementById("result-form");

		compSelect.addEventListener("change", (event) => {
			const selectedOption = event.target.selectedOptions[0];
			const mode = selectedOption.dataset.mode;
			loadResultFormView(mode);
		});

		function loadResultFormView(mode) {
			let url = mode === "tournament" ?
				"solo_result_forms/tournament_form.php" :
				"solo_result_forms/competition_form.php";

			fetch(url)
				.then(response => response.text())
				.then(html => {
					resultForm.innerHTML = html;
					submitStationButton.classList.remove("hidden");
				})
				.catch(error => console.error("Error loading form:", error));
		}
	</script>
</body>

</html>