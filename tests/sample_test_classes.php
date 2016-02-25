<?php

/**
 * These classes are not autoloaded on purpose because composer packages can't exclude test files.
 */

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
        $document['name'] = $this->name;
        return $row;
    }

    function fromRow($row) {
        parent::fromDocument($row);
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

    function __construct() {
        $this->setObjectForKey(Author::class, 'author');
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

?>
