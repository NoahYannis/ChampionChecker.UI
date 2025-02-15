<?php
// Hier werden Einzelergebnisse angelegt. Nach Auswahl einer Station aus dem Select wird ein weiteres Formular für die Auswertung eingebunden.

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
		<h1>Einzelergebnisse eintragen</h1>
	</header>

	<div class="flex-container">
		<select id="competitions">
			<option selected disabled value="default">Station auswählen:</option>
			<?php foreach ($soloCompetitions as $comp): ?>
				<option value="<?= htmlspecialchars($comp->getName()) ?>"
					data-mode="<?= (stripos($comp->getName(), 'Tischtennis') !== false) ? 'tournament' : 'competition' ?>"
					data-time="<?= htmlspecialchars($comp->getDate()->format('Y-m-d H:i:s')) ?>"
					data-gender="<?= htmlspecialchars($comp->getIsMale() === true ? 'M' : ($comp->getIsMale() === false ? 'W' : 'N')) ?>"
					data-participants="<?= htmlspecialchars(json_encode($comp->getStudentParticipants())) ?>"
					data-info="<?= htmlspecialchars($comp->getAdditionalInfo()) ?>"
					<?= (count($comp->getStudentParticipants()) == 0) ? 'disabled' : '' ?>>
					<?= htmlspecialchars($comp->getName()) ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div id="competition-info-section" class="hidden">
		<label>Stations-Informationen:</label>
		<table id="info-table" class="table-style">
			<thead>
				<tr>
					<th>Name</th>
					<th>Zeit</th>
					<th>Geschlecht</th>
					<th>Teilnehmer</th>
					<th>Sonstiges</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id="comp-name"></td>
					<td id="comp-time"></td>
					<td id="comp-gender"></td>
					<td id="comp-participants"></td>
					<td id="comp-other"></td>
				</tr>
			</tbody>
		</table>
	</div>

	<hr id="horizontal-separator" class="horizontal-separator hidden">

	<div id="result-form"></div> <!-- Hier wird nach Auswahl einer Option das Ergebnisformular angezeigt-->

	<button id="submit-station" class="submit-station hidden">Station abschließen</button>


	<script>
		let compSelect = document.getElementById("competitions");
		let submitButton = document.getElementById("submit-station");
		let resultForm = document.getElementById("result-form");
		let competitionInfoTable = document.getElementById("info-table");
		let separator = document.getElementById("horizontal-separator");

		compSelect.addEventListener("change", (event) => {
			const selectedOption = event.target.selectedOptions[0];
			const mode = selectedOption.dataset.mode;
			loadResultFormView(mode);
			updateCompetitionInfo(selectedOption);
		});

		submitButton.addEventListener("click", () => submitStationResults());

		function loadResultFormView(mode) {
			let url = mode === "tournament" ?
				"solo_result_forms/tournament_form.php" :
				"solo_result_forms/competition_form.php";

			fetch(url, {
					method: "POST",
					headers: {
						"Content-Type": "application/json"
					},
					body: compSelect.selectedOptions[0].dataset.participants
				})
				.then(response => response.text())
				.then(html => {
					resultForm.innerHTML = html;
					submitButton.classList.remove("hidden");
					const formScript = resultForm.querySelector("script");
					eval(formScript.textContent);
				})
				.catch(error => console.error("Error loading form:", error));
		}


		function updateCompetitionInfo(selectedOption) {
			document.getElementById("comp-name").textContent = selectedOption.value;
			document.getElementById("comp-time").textContent = new Date(selectedOption.dataset.time).toLocaleString('de-DE');
			document.getElementById("comp-other").textContent = selectedOption.dataset.info;
			document.getElementById("comp-gender").textContent = selectedOption.dataset.gender;

			let participants = Object.values(JSON.parse(selectedOption.dataset.participants));
			const participantsHTML = participants.map(p => {
				const participantName = `${p.firstName} ${p.lastName}` || '???';
				return `<span class='name-badge student'>${participantName}</span>`;
			}).join(' ');

			document.getElementById("comp-participants").innerHTML = participantsHTML;
			document.getElementById("competition-info-section").classList.remove("hidden");
			separator.classList.remove("hidden");
		}

		function submitStationResults() {
			if (!confirm("Bitte bestätigen Sie die Vollständigkeit der Ergebnisse.")) {
				return;
			}

			// Ergebnisse speichern.
		}
	</script>
</body>

</html>