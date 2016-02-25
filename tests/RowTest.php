<?php

require 'sample_test_classes.php';

class RowTest extends PHPUnit_Framework_TestCase {

    public function testInsert() {
        $person = new \Person("Unit Test", "Mrs.");
        $this->assertTrue(($person->save() !== false));
        $this->assertTrue($person->getID() !== null);
        $person->delete();
    }

    public function testUpdate() {
        $person = new \Person("Unit Test", "Mrs.");
        $this->assertTrue(($person->save() !== false));
        $this->assertTrue($person->getID() !== null);
        $person_id = $person->getID();

        $person->name = "Updated Name";
        $this->assertTrue(($person->save() !== false));
        $person_id_b = $person->getID();

        $this->assertEquals($person_id, $person_id_b);
        $retrievePerson = Person::getByID($person_id);
        $this->assertEquals($person->name, $retrievePerson->name);

        $person->delete();
    }

}

?>