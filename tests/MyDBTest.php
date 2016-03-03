<?php

/**
 * Local testing can run:
 * vendor/bin/phpunit --bootstrap autoload.php tests
 */
use wcatron\CommonDBFramework\DB;
use wcatron\MySQLDBFramework\MyDB;
use wcatron\MySQLDBFramework\Row;

require 'sample_test_classes.php';

class MyDBTest extends PHPUnit_Framework_TestCase {
    public function testDI() {
        TestDB::configure([]);
        Row::setDBInstance(TestDB::class);
        $this->assertEquals(Author::getDBInstance(), TestDB::getInstance());
        $author = new Author("Mr. A");
        $authorB = new Author("Mr. B");
        $author->save();
        $authorB->save();
        $authors = TestDB::getInstance()->getAllObjects(Author::class);
        $this->assertTrue((count($authors) == 2));
        foreach ($authors as $author) {
            $author->delete();
        }
    }
}

?>