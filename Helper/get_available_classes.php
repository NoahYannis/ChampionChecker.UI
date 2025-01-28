<?php

require_once '../vendor/autoload.php';

use MVC\Controller\ClassController;

$controller = new ClassController();
$classes = $controller->getAll();

$result = array_map(function ($class) {
    return [
        'id' => $class->getId(),
        'name' => $class->getName(),
        'teacherCount' => count($class->getTeachers()),
        'available' => count($class->getTeachers()) < 2,
    ];
}, $classes);

echo json_encode($result);
