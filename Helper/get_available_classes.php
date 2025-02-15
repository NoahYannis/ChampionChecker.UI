<?php
//  Liefert eine Auflistung aller Klassen mit Namen, Lehreranzahl und ob die Klasse noch für die Betreuung durch weitere Leherer verfügbar ist.

require_once '../vendor/autoload.php';

use MVC\Controller\ClassController;

$classes = ClassController::getInstance()->getAll();

$result = array_map(function ($class) {
    return [
        'id' => $class->getId(),
        'name' => $class->getName(),
        'teacherCount' => count($class->getTeachers()),
        'available' => count($class->getTeachers()) < 2,
    ];
}, $classes);

echo json_encode($result);
