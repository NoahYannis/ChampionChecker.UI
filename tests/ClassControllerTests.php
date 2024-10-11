<?php

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use MVC\Controller\ClassController;
use MVC\Model\ClassModel;

class ClassControllerTests extends TestCase
{
    protected $classController;

    protected function setUp(): void
    {
        $this->classController = $this->getMockBuilder(ClassController::class) // Class-Controller Mock erstellen
            ->disableOriginalConstructor() // Nicht aufrufen, da config.php in Tests nicht verfügbar ist
            ->onlyMethods(['getApiData', 'sendApiRequest']) // Zu mockende Methoden
            ->getMock();

        $this->classController->method('getApiData')
            ->willReturn([
                'id' => 1,
                'name' => 'EFI22A',
                'students' => [],
                'pointsAchieved' => 10,
                'classTeacherId' => null
            ]);

        $this->classController->method('sendApiRequest')
            ->willReturnCallback(function () {});
    }

    public function testGetById()
    {
        $classModel = $this->classController->getById(1);
        $this->assertInstanceOf(ClassModel::class, $classModel);
        $this->assertEquals(1, $classModel->getId());
        $this->assertEquals('EFI22A', $classModel->getName());
    }
}
