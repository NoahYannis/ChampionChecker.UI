<?php

require_once '../vendor/autoload.php';

use MVC\Model\CompetitionStatus;
use MVC\Controller\CompetitionController;

$compId = $_GET['compId']; // Kommt aus add_soloresults.php

return;
if (empty($compId)) {
    die("Es wurde keine Stations-ID übermittelt");
}

session_start();

$compModel = CompetitionController::getInstance()->getById($compId);
$compModel->setStatus(CompetitionStatus::Beendet);

$updateResult = CompetitionController::getInstance()->update($compModel);

if($updateResult['success'] === false)
{
    error_log("Fehler beim Speichern des Wettbewerbs mit ID: " . $compId . " am " . date('Y-m-d H:i'));
}

exit;
