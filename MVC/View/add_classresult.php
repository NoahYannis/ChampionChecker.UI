	<?php

	require '../../vendor/autoload.php'; // Lädt alle benötigten Klassen automatisch aus MVC-Ordner, siehe composer.json.

	use MVC\Model\CompetitionResult;
	use MVC\Controller\CompetitionController;
	use MVC\Controller\CompetitionResultController;
	use MVC\Controller\ClassController;
	use MVC\Controller\UserController;

	session_start();

	$userRole = UserController::getInstance()->getRole();

	// Für Zugriff mindestens Rolle Lehrkraft
	if ($userRole->value < 2) {
		header("Location: home.php");
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
			$createResult = $compResController->create($compResult);

			if ($createResult['success'] === true) {
				echo '<script>alert("Das Ergebnis wurde erfolgreich eingetragen.");</script>';
			} else {
				echo '<script>alert("Beim Erstellen des Ergebnisses ist ein Fehler aufgetreten.");</script>';
			}

			unset($_SESSION['classresult_selectedClass']);
			unset($_SESSION['classresult_selectedCompetition']);
			unset($_POST['points']);
		}
	}

	if (!isset($_SESSION['classresult_competitions'])) {
		$competitionModels = CompetitionController::getInstance()->getAll();
		$teamCompetitions = [];

		foreach ($competitionModels as $comp) {
			if ($comp->getIsTeam()) {
				$teamCompetitions[] = $comp;
			}
		}

		$_SESSION['classresult_competitions'] = $teamCompetitions;
	} else {
		$teamCompetitions = $_SESSION['classresult_competitions'];
	}

	$competitionSelected = !empty($_POST['competitions']);
	$participantClassesNames = [];

	// Selektierten Wettbewerb laden falls vorhanden
	if ($competitionSelected) {
		$selectedCompName = $_POST['competitions'];

		foreach ($teamCompetitions as $comp) {
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
				$participantClassesNames[] = $class;
			}
		}
	}

	$classSelected = !empty($_POST['classes']);

	if ($classSelected) {
		foreach ($classParticipants as $class) {
			if ($class === $_POST['classes']) {
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
			<form method="POST" style="display: flex; flex-direction: column;" action="">
				<div class="styled-select">
					<!-- Stations-Auswahl -->
					<select name="competitions" id="competitions" onchange="this.form.submit()">
						<option selected disabled value="default">Station auswählen:</option>

						<!-- Gruppe für Mannschaft -->
						<optgroup label="Mannschaft">
							<?php foreach ($teamCompetitions as $comp): ?>
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
				<input name="points" placeholder="Punktzahl eingeben:" type="number"
					required minlength="1" maxlength="2" min="0" max="99"
					inputmode="numeric" oninput="validatePointInput(this)" />
			</form>

			<button onclick="submitForm()" type="submit" name="submit" value="Abschicken">Abschicken</button>
		</main>

		<script>
			const invalidChars = ['+', '-', 'E', 'e'];

			function validatePointInput(input) {
				let value = input.value;

				// Input auf zwei Zahlen begrenzen.
				if (value.length > 2) {
					input.value = value.slice(0, 2);
					return;
				}

				// Ungültige Zeichen entfernen.
				if (invalidChars.some(char => value.includes(char))) {
					input.value = value.slice(0, -1);
					return;
				}

				// Wert darf nur zwischen 0 und 99 liegen.
				if (value < 0 || value > 99 || value === '' || isNaN(value)) {
					input.value = value.slice(0, -1);
					return;
				}
			}

			async function submitForm() {
				const competitionSelect = document.querySelector('select[name="competitions"]');
				const classSelect = document.querySelector('select[name="classes"]');

				const selectedCompetition = competitionSelect.value;
				const selectedClass = classSelect.value;

				if (selectedCompetition === 'default') {
					alert('Bitte wählen Sie eine Station aus.');
					return;
				}

				if (selectedClass === 'default') {
					alert('Bitte wählen Sie eine Klasse aus.');
					return;
				}

				const pointsInput = document.querySelector('input[name="points"]');
				const pointsValue = parseInt(pointsInput.value, 10);

				if (pointsInput.value === '' || pointsValue < 0 || pointsValue > 99) {
					alert('Bitte geben Sie eine gültige Punktzahl zwischen 0 und 99 ein.');
					return;
				}

				try {
					const isDuplicateResult = await fetch("../../Helper/check_is_duplicate_result.php").then(r => r.json());

					if (isDuplicateResult) {
						alert(`Es existiert bereits ein Ergebnis für Klasse ${selectedClass} und Station ${selectedCompetition}. Bitte löschen oder bearbeiten Sie das bestehende, um ein neues hinzuzufügen.`);

						// Get-Request auslösen, wodurch vorherige POST-Daten entfernt werden.
						const form = document.createElement('form');
						form.method = 'GET';
						form.action = window.location.href;
						document.body.appendChild(form);
						form.submit();
						return;
					}
				} catch (error) {
					console.error("Error checking for duplicates:", error);
					return;
				}

				document.querySelector('form').submit();
			}
		</script>
	</body>

	</html>