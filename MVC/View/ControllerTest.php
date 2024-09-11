<?php

require_once __DIR__ . '/../Model/ClassModel.php';
require_once __DIR__ . '/../Controller/IController.php';
require_once __DIR__ . '/../Controller/ClassController.php';


use MVC\Controller\ClassController;

$controller = new ClassController();

$classes = $controller->getAll();
$class = null;
if (isset($_GET['id'])) {
    $class = $controller->getById((int)$_GET['id']);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Class View</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f4f4f4; }
        .container { width: 80%; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Klassenübersicht</h1>
        <h2>Alle Klassen</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Points Achieved</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                <tr>
                    <td><?= htmlspecialchars($class->getId()) ?></td>
                    <td><?= htmlspecialchars($class->getName()) ?></td>
                    <td><?= htmlspecialchars($class->getPointsAchieved()) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
