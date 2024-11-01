	<?php

	require '../../vendor/autoload.php'; // Lädt alle benötigten Klassen automatisch aus MVC-Ordner, siehe composer.json.

	use MVC\Model\CompetitionResult;
	use MVC\Controller\CompetitionController;
	use MVC\Controller\CompetitionResultController;
	use MVC\Controller\ClassController;

	session_start();

	if(!isset($_COOKIE['ChampionCheckerCookie'])) {
		header("Location: login.php");
		exit();
	}

	$selectedCompetition = $_SESSION['classresult_selectedCompetition'] ?? null;
	$selectedClass = $_SESSION['classresult_selectedClass'] ?? null;

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($selectedCompetition)) {
		// Wenn sich der Wettbewerb ändert, die selektierte Klasse zurücksetzen.
		if (isset($_POST['competitions']) && $_POST['competitions'] !== $selectedCompetition->getName()) {
			unset($_POST['classes']); // Klasse zurücksetzen
		}

		// Die Klasse wurde zum ersten Mal gesetzt oder geändert. Klassen-Objekt neu laden.
		if (isset($_POST['classes']) && (!isset($selectedClass) || $selectedClass->getName() !== $_POST['classes'])) {
			$classController = ClassController::getInstance();
			$_SESSION['classresult_selectedCompetition'] = $selectedClass = $classController->getByName($_POST['classes']);
		}

		// Wenn der Absenden-Button gedrückt wurde, das Wettbewerbsergebnis an die API schicken.
		if (!empty($_POST['points']) && isset($selectedClass)) {
			$compResult = new CompetitionResult(null, $_POST['points'],  $selectedCompetition->getId(), $selectedClass->getId(), null);
			$compResController = new CompetitionResultController();
			$compResController->create($compResult);

			$_SESSION['classresult_selectedClass'] = null;
			$_SESSION['classresult_selectedCompetition'] = null;
			$_POST['points'] = null;
			header("Location: " . $_SERVER['REQUEST_URI']); // Redirect, um erneutes Absenden zu verhindern
			exit();
		}
	}

	if (!isset($_SESSION['classresult_competitions'])) {
		// Wettbewerbe aus der Datenbank holen, wenn noch nicht gecached
		$competitionController = CompetitionController::getInstance();
		$competitionModels = $competitionController->getAll();

		$teamCompetitions = [];
		$soloCompetitions = [];

		foreach ($competitionModels as $comp) {
			if ($comp->getIsTeam()) {
				$teamCompetitions[] = $comp;
			} else {
				$soloCompetitions[] = $comp;
			}
		}

		// Wettbewerbe cachen
		$_SESSION['classresult_competitions'] = [
			'team' => $teamCompetitions,
			'solo' => $soloCompetitions
		];
	} else {
		// Gecachte Wettbewerbe laden
		$teamCompetitions = $_SESSION['classresult_competitions']['team'];
		$soloCompetitions = $_SESSION['classresult_competitions']['solo'];
	}

	$competitionSelected = !empty($_POST['competitions']);
	$participantClassesNames = [];

	// Selektierten Wettbewerb laden falls vorhanden
	if ($competitionSelected) {
		$selectedCompName = $_POST['competitions'];
		$allCompetitions = array_merge($teamCompetitions, $soloCompetitions);

		foreach ($allCompetitions as $comp) {
			if ($comp->getName() === $selectedCompName) {
				$selectedCompetition = $comp;
				$_SESSION['classresult_selectedCompetition'] = $selectedCompetition;
				break;
			}
		}

		// Klassen des selektierten Wettbewerbs laden
		if ($selectedCompetition) {
			$classParticipants = $selectedCompetition->getClassParticipants();

			foreach ($classParticipants as $class) {
				$participantClassesNames[] = $class['name'];
			}
		}
	}

	$classSelected = !empty($_POST['classes']);

	if ($classSelected) {
		foreach ($classParticipants as $class) {
			if ($class['name'] === $_POST['classes']) {
				$classController = ClassController::getInstance();
				$_SESSION['classresult_selectedClass'] = $classController->getByName($_POST['classes']);
				break; // Selektierte Klasse in Sitzung speichern
			}
		}
	}

	include 'nav.php';
	?>

	<!DOCTYPE html>
	<html lang="de">

	<head>
		<link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
		<script src="https://cdn.jsdelivr.net/npm/less"></script>
		<meta charset="utf-8">
		<meta name="description" content="Klassenpunkte eintragen">
		<title>Klassenpunkte eintragen</title>
	</head>

	<body>

		<header>
			<h1>Klassenpunkte</h1>
		</header>

		<main class="main-content">
			<form method="POST" action="">
				<div class="styled-select">
					<!-- Wettbewerbs-Auswahl -->
					<select name="competitions" id="competitions" onchange="this.form.submit()">
						<option selected disabled value="default">Wettbewerb auswählen:</option>

						<!-- Gruppe für Mannschaft -->
						<optgroup label="Mannschaft">
							<?php foreach ($teamCompetitions as $comp): ?>
								<option value="<?= htmlspecialchars($comp->getName()) ?>"
									<?= isset($_POST['competitions']) && $_POST['competitions'] == $comp->getName() ? 'selected' : '' ?>>
									<?= htmlspecialchars($comp->getName()) ?>
								</option>
							<?php endforeach; ?>
						</optgroup>

						<!-- Gruppe für Einzeln -->
						<optgroup label="Einzel">
							<?php foreach ($soloCompetitions as $comp): ?>
								<option value="<?= htmlspecialchars($comp->getName()) ?>"
									<?= isset($_POST['competitions']) && $_POST['competitions'] == $comp->getName() ? 'selected' : '' ?>>
									<?= htmlspecialchars($comp->getName()) ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					</select>
				</div>

				<div class="styled-select">
					<!-- Klassen-Auswahl -->
					<select name="classes" id="classes" onchange="this.form.submit()" <?= empty($participantClassesNames) ? 'disabled' : '' ?>>
						<option value="default">Klasse auswählen:</option>
						<?php foreach ($participantClassesNames as $participantClassName): ?>
							<option value="<?= htmlspecialchars($participantClassName) ?>"
								<?= isset($_POST['classes']) && $_POST['classes'] == $participantClassName ? 'selected' : '' ?>>
								<?= htmlspecialchars($participantClassName) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<input name="points" placeholder="Punktzahl eingeben:" type="number" required minlength="1" maxlength="2" />
			</form>

			<button onclick="submitForm()" type="submit" name="submit" value="Abschicken">Abschicken</button>
		</main>

		<footer />

		<script>
			function submitForm() {
				if (document.querySelector('select[name="competitions"]').value === 'default') {
					alert('Bitte wählen Sie einen Wettbewerb aus.');
					return;
				}

				if (document.querySelector('select[name="classes"]').value === 'default') {
					alert('Bitte wählen Sie eine Klasse aus.');
					return;
				}

				const pointsInput = document.querySelector('input[name="points"]');
				const pointsValue = parseInt(pointsInput.value, 10);

				if (pointsInput.value === '' || pointsValue < 0 || pointsValue > 100) {
					alert('Bitte geben Sie eine gültige Punktzahl zwischen 0 und 100 ein.');
					return;
				}

				document.querySelector('form').submit();
			}
		</script>
	</body>

	</html>