<!DOCTYPE html>
<html lang="de">

<head>
	<link rel="stylesheet/less" type="text/css" href="styles/styles.less" />
	<script src="https://cdn.jsdelivr.net/npm/less"></script>
	<meta charset="utf-8">
	<meta name="description" content="">

	<title>Navigation</title>
</head>

<body>
    <header><h1>Navigation</h1></header>
</body>

	<main class="main-content">

		<?php

		require_once __DIR__ . '/MVC/Model/ClassModel.php';
		require_once __DIR__ . '/MVC/Model/Competition.php';

		require_once __DIR__ . '/MVC/Controller/IController.php';
		require_once __DIR__ . '/MVC/Controller/ClassController.php';
		require_once __DIR__ . '/MVC/Controller/CompetitionController.php';

		use MVC\Controller\ClassController;
		use MVC\Controller\CompetitionController;

	//	$classController = new ClassController();
	//	$types = $classController->getAll();
	//	$participantClassesNames = [];

	//	foreach ($types as $class) {
	//		$participantClassesNames[] = $class->getName();
	//	}

		$competitionController = new CompetitionController();
		$competitionModels = $competitionController->getAll();
		$teamCompetitions = array("Mannschaft" => []);
		$soloCompetitions = array("Einzeln" => []);

		foreach ($competitionModels as $comp) {
			if ($comp->getIsTeam()) {
				$teamCompetitions["Mannschaft"][] = $comp->getName();
			} else {
				$soloCompetitions["Einzeln"][] = $comp->getName();
			}
		}
		?>
		<!-- Replace with 3Radios (Männlich,Weiblich,Gemischt) and 2Radios(Single and team)-->
		<div class="styled-select">
			<input type="radio" id="MaleIDPlaceholder" name="competetorsGender" value= Männlich>
			<label for=MaleIDPlaceholder> Männlich</label>
			<input type="radio" id="FemaleIDPlaceholder" name="competetorsGender" value= Weiblich>
			<label for=FemaleIDPlaceholder> Weiblich</label>
			<input type="radio" id="MixedIDPlaceholder" name="competetorsGender" value= Gemischt>
			<label for=MixedIDPlaceholder> Gemischt</label>
			<br>
			<input type="radio" id="TeamIDPlaceholder" name="competitiontype" value= Mannschaft>
			<label for=TeamIDPlaceholder> Mannschaft</label>
			<input type="radio" id="SingleIDPlaceholder" name="competitiontype" value= Einzeln>
			<label for=SingleIDPlaceholder> Einzeln</label>

		</div>

		<div class="styled-select">
			<select name="competitions">
				<option value="">Wettbewerb auswählen:</option>
                <!--Add if else -->
				<?php foreach ($teamCompetitions as $key => $compNames): ?>
					<optgroup label="<?= $key ?>">
						<?php foreach ($compNames as $name): ?>
							<option value="<?= $name ?>">
								<?= $name ?>
							</option>
						<?php endforeach; ?>
					<?php endforeach; ?>
				<?php foreach ($soloCompetitions as $key => $compNames): ?>
					<optgroup label="<?= $key ?>">
						<?php foreach ($compNames as $name): ?>
							<option value="<?= $name ?>">
								<?= $name ?>
							</option>
						<?php endforeach; ?>
					<?php endforeach; ?>
			</select>
		</div>

		<input name="points" placeholder="Punktzahl eingeben:" type="number" Punktzahl eingeben:"
			type="text" required minlength="1" maxlength="2" />

		<button type="submit" name="submit" value="Abschicken">Abschicken</button>
	</main>
	<footer />
	</footer>
</body>

</html>