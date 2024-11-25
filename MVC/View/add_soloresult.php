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
	

	if (!isset($_SESSION['soloresult_competitions'])) {
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
		$_SESSION['soloresult_competitions'] = [
			'team' => $teamCompetitions,
			'solo' => $soloCompetitions
		];
	} else {
		// Gecachte Wettbewerbe laden
		$teamCompetitions = $_SESSION['soloresult_competitions']['team'];
		$soloCompetitions = $_SESSION['soloresult_competitions']['solo'];
	}

	include 'nav.php';
	?>
    
	<!DOCTYPE html>
	<html>

    <head>
		<link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
		<script src="https://cdn.jsdelivr.net/npm/less"></script>
		<meta charset="utf-8">
		<meta name="description" content="Klassenpunkte eintragen">
		<title>Klassenpunkte eintragen</title>
        <script type="text/javascript" language="JavaScript">
            function inputNumericValidate(){
                const e = event || window.event;
                const key = e.keyCode || e.which;
                if (((key<=48)||(key>=57)) &&
                    (key!==8)&&(key!==46)&&(key!==37)&&(key!==39)){
                    if (e.preventDefault) e.preventDefault();
                    e.returnValue = false;
                }
            }
        </script>
	</head>

    <body>

        <header>
			<h1>Solopunkte</h1>
		</header>

		<main class="main-content">
			<form method="POST" style="display: flex; flex-direction: column;" action="">
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
            </form>

			<?php				
				// Check if a competition is selected
				if (isset($_POST['competitions']) && $_POST['competitions'] !== 'default') {
					$selectedCompetition = $_POST['competitions'];

					// Display table for the selected competition
					echo '<div class="competition-table">';
					if (in_array($selectedCompetition, array_map(fn($c) => $c->getName(), $teamCompetitions))) {
						// Display table for Mannschaft competitions
						echo '<h2>Mannschaft: ' . htmlspecialchars($selectedCompetition) . '</h2>';
					}
					elseif (in_array($selectedCompetition, array_map(fn($c) => $c->getName(), $soloCompetitions))) {
						// Display table for Einzel competitions
						echo '<h2>Einzel: ' . htmlspecialchars($selectedCompetition) . '</h2>';
						if ( $_POST['competitions'] == 'Tischtennis')
						switch ( $_POST['competitions'])
						{
							case'Tischtennis':
								include '.\Tables\table_tennis.php';  // Path to the specific table view
								break;
						}
					}
					else
					{
						echo '<p>Keine Tabelle verfügbar für die Auswahl.</p>';
					}
					echo '</div>';
				}
			?>
        </main>
    </body>

</html>