<?php

/**
 * These classes are not autoloaded on purpose because composer packages can't exclude test files.
 */

use wcatron\CommonDBFramework\DB;
use wcatron\MySQLDBFramework\Row;

class Author extends Row {
    public $name;

    const TABLE = "authors";
    const ID_COLUMN = "author_id";

    function __construct($name = null) {
        $this->name = $name;
    }

    function toRow() {
        $row = parent::toRow();
        $row['name'] = $this->name;
        return $row;
    }

    function fromRow($row) {
        parent::fromRow($row);
        $this->name = $row['name'];
    }

}

use wcatron\CommonDBFramework\LinkedObject;

class Book extends Row {
    var $title;
    /** @var LinkedObject */
    var $author;

    const TABLE = "books";
    const ID_COLUMN = "book_id";

    function __construct($title = null,Author $author = null) {
        $this->setObjectForKey(Author::class, 'author_id', 'author');
        if ($title) {
            $this->title = $title;
        }
        if ($author) {
            $this->author->set($author);
        }
    }

    function toRow() {
        $row = parent::toRow();
        $row['title'] = $this->title;
        return $row;
    }

    function fromRow($row) {
        parent::fromRow($row);
        $this->title = $row['title'];
    }
}

class Person extends Row {
    var $name;
    var $title;

    const TABLE = "people";
    const ID_COLUMN = "person_id";

    function __construct($name = null, $title = null) {
        $this->name = $name;
        $this->title = $title;
    }

    function getFullName() {
        return (($this->title) ? $this->title . " " : "") . $this->name;
    }

    function toRow() {
        $row = parent::toRow();
        $row['name'] = $this->name;
        $row['title'] = $this->title;
        return $row;
    }

    function fromRow($row) {
        parent::fromRow($row);
        $this->name = $row['name'];
        $this->title = $row['title'];
    }
}

class TestDB extends DB {
    var $authors = [];

    var $db = false;

    function connect() {
        if ($this->db == false) {
            echo "Connecting... ";
            $this->db = true;
            echo "Connected.";
        }
    }

    public function saveRow($object, $secure = false) {
        if (get_class($object) == Author::class) {
            $id = count($this->authors);
            $this->authors[] = $object;
            $object->old_row = array_merge($object->toRow(), [
                Author::ID_COLUMN => $id
            ]);
        }
    }

    public function removeRow($object) {
        if (get_class($object) == Author::class) {
            unset($this->authors[$object->getID()]);
        }
    }

    public function getObjectByID($objectType, $id) {
        if ($objectType == Author::class) {
            return (isset($this->authors[$id])) ? $this->authors[$id] : false;
        }
    }

    public function getAllObjects($objectType) {
        if ($objectType == Author::class) {
            return array_values($this->authors);
        }
    }
}

?>
