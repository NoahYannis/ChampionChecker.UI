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

$filteredStudents = array_map(function ($student) {
    return [
        'id' => $student['id'],
        'firstName' => $student['firstName'],
        'lastName' => $student['lastName'],
    ];
}, $classStudents);

$encoded = json_encode($filteredStudents);
echo $encoded;
exit;
