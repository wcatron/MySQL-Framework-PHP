<?php

/**
 * Local testing can run:
 * vendor/bin/phpunit --bootstrap autoload.php tests
 */
use wcatron\MySQLDBFramework\MyDB;

require 'sample_test_classes.php';

class RowTest extends PHPUnit_Framework_TestCase {

    public function testInsert() {
        $person = new \Person("Unit Test", "Mrs.");
        $this->assertTrue(($person->save() !== false));
        $this->assertTrue($person->getID() !== null);
        $person->delete();
    }

    public function testUpdate() {
        $person = new \Person("Unit Test", "Ms.");
        $this->assertTrue(($person->save() !== false));
        $this->assertTrue($person->getID() !== null);
        $person_id = $person->getID();

        $person->title = "Mrs.";
        $this->assertTrue(($person->save() !== false));
        $person_id_b = $person->getID();

        $this->assertEquals($person_id, $person_id_b);
        $retrievePerson = Person::getByID($person_id);
        $this->assertEquals($person->title, $retrievePerson->title);

        $person->delete();
    }

    public function testLinkedObject() {
        $author = new \Author("Art Buchwald");
        $this->assertTrue($author->save());

        $book = new \Book();
        $book->title = "I Think I Don't Remember";
        $book->author->set($author);
        $this->assertTrue($book->save());

        $bookID = $book->getID();
        $retrievedBook = \Book::getByID($bookID);
        $this->assertTrue($retrievedBook->author->isEqual($author));

        /** @var Author $retrievedAuthor */
        $retrievedAuthor = $retrievedBook->author->get();
        $this->assertEquals($author->name, $retrievedAuthor->name);

        $book->delete();
        $author->delete();
    }

    public function testGetAllObjects() {
        $author = new \Author("Art Buchwald");
        $author->save();
        $bookA = new Book("I Think I Don't Remember");
        $bookA->author->set($author);
        $bookB = new Book("Down the Seine and Up the Potomac", $author);
        $bookC = new Book("The Bollo caper");
        $bookC->author->setID($author->getID());
        $bookA->save();
        $bookB->save();
        $bookC->save();

        $books = Book::getAllObjects();
        $this->assertTrue((count($books) == 3));
        foreach ($books as $book) {
            $this->assertTrue($book->author->isEqual($author));
            $book->delete();
        }
        $author->delete();
    }

    public function testRollback() {
        MyDB::getInstance()->beginTransaction();
        $exception = null;
        try {
            $author = new \Author("Art Buchwald");
            $author->save();
            // This throws an exception because the title is longer than the 45 characters the database allows.
            $book = new Book("Too Soon to Say Goodbye: I Don't Know Where I'm Going. I Don't Even Know Why I'm Here", $author);
            $book->save();

            throw new Exception("Hard coded exception in case travis ci doesn't fail at inserted long title.");
            MyDB::getInstance()->commitTransaction();
        } catch (\Exception $e) {
            $exception = $e;
            MyDB::getInstance()->rollbackTransaction();
        }

        var_dump($exception->getMessage());

        $this->assertTrue(($exception !== null), "Exception was not thrown during try.");

        $authors = Author::getAllObjects();
        $this->assertTrue((count($authors) == 0));
        $books = Book::getAllObjects();
        $this->assertTrue((count($books) == 0));
    }

    public function testFailGettingObject() {
        $author = Author::getOneByColumn('name', 'Unknown Author');
        $this->assertFalse($author);
    }

}

?>