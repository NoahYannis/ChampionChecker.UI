<?php

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testAddition()
    {
        $result = 1 + 1;
        $this->assertEquals(2, $result);
    }
}
