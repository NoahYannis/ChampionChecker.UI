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

        $this->classController->method('sendApiRequest')
            ->willReturnCallback(function () {});
    }

    public function test_GetById_WithExistingId_ReturnsClass()
    {
        $this->classController->method('getApiData')
            ->willReturn([
                'id' => 1,
                'name' => 'EFI22A',
                'students' => [],
                'pointsAchieved' => 10,
                'classTeacherId' => null
            ]);

        $classModel = $this->classController->getById(1);
        $this->assertInstanceOf(ClassModel::class, $classModel);
        $this->assertEquals(1, $classModel->getId());
        $this->assertEquals('EFI22A', $classModel->getName());
    }

    public function test_GetById_WithNonExistingId_ReturnsNull()
    {
        $this->classController->method('getApiData')
            ->willReturn(null); // Nicht existierende Klasse, API gibt null zurück.

        $classModel = $this->classController->getById(2);
        $this->assertNull($classModel);
    }

    public function test_getByName_withExistingClass_returnsClass()
    {
        // Hier wird ein Array zurückgegeben, da die API alle Klassen zurückgibt, falls keine Session-Daten vorhanden sind.
        $this->classController->method('getApiData')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'EFI22A',
                    'students' => [],
                    'pointsAchieved' => 10,
                    'classTeacherId' => null
                ]
            ]);

        $classModel = $this->classController->getByName('efi22a'); // Groß- und Kleinschreibung müssen egal sein
        $this->assertInstanceOf(ClassModel::class, $classModel);
        $this->assertEquals(1, $classModel->getId());
        $this->assertEquals('EFI22A', $classModel->getName());
    }

    public function test_getByName_withNonExistingClass_returnsNull()
    {
        $this->classController->method('getApiData')
            ->willReturn(null);

        $classModel = $this->classController->getByName('efi22b');
        $this->assertNull($classModel);
    }
}
