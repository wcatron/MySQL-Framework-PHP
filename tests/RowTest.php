<?php

require 'sample_test_classes.php';

class RowTest extends PHPUnit_Framework_TestCase {

    public function testInsert() {
        $person = new \Person("Unit Test", "Mrs.");
        $this->assertTrue(($person->save() !== false));
        $this->assertTrue($person->getID() !== null);
        $person->delete();
    }

}

?>