<?php

require_once '../vendor/autoload.php';

use MVC\Model\CompetitionStatus;
use MVC\Controller\CompetitionController;

session_start();

$compEvaluations = [];
$totalCompCount;
$completedCount;

if (isset($_SESSION['overview_competitions'])) {
    $totalCompCount = count($_SESSION['overview_competitions']);
    $completedCount = count(array_filter(
        $_SESSION['overview_competitions'],
        fn($competition) => $competition->getStatus() === CompetitionStatus::Beendet
    ));

    $compEvaluations = [$completedCount, $totalCompCount];
    echo json_encode($compEvaluations);
    exit;
}

$competitions = CompetitionController::getInstance()->getAll();

$totalCompCount = count($competitions);
$completedCount = count(array_filter(
    $competitions,
    fn($competition) => $competition->getStatus() === CompetitionStatus::Beendet
));
$compEvaluations = [$completedCount, $totalCompCount];

echo json_encode($compEvaluations);
