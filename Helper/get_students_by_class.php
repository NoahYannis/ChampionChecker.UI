<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../vendor/autoload.php';

use MVC\Controller\ClassController;

if (!isset($_SESSION['classes'])) {
    $allClasses = ClassController::getInstance()->getAll();
    $_SESSION['classes'] = $allClasses;
}

$classStudents = [];

if (isset($_SESSION['classes']) && isset($_GET['classId'])) {
    $classId = $_GET['classId'];
    foreach ($_SESSION['classes'] as $class) {
        if ($class instanceof \MVC\Model\ClassModel && $class->getId() == $classId) {
            $classStudents = $class->getStudents();
            break;
        }
    }
}

$filteredStudents = array_map(function ($values, $studentId) {
    return [
        'id' => $studentId,
        'classId' => $_GET['classId'],
        'firstName' => $values['firstName'],
        'lastName' => $values['lastName'],
    ];
}, $classStudents, array_keys($classStudents));


$encoded = json_encode($filteredStudents);
echo $encoded;
exit;
