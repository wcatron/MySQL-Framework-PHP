# MySQL Framework PHP
A MySQL ODM (assistant) extremely flexible for any project.

[![Build Status](https://travis-ci.org/wcatron/MySQL-Framework-PHP.svg?branch=master)](https://travis-ci.org/wcatron/MySQL-Framework-PHP)

 Overview

This framework allows you to quickly map objects to documents in a MongoDB collection.

**Setup**

Create models for your objects and have them extend *Row*. Implement the `toRow` and `fromRow` methods and set the `TABLE` and `ID_COLUMN` constants. Call `MyDB::configure()` in your autoload.php file or wherever needed before database calls. You can now perform queries and get php objects back.

# Installation

## Composer

```
composer require wcatron/mysql-db-framework
```

# Models & MDB

### Models

Your classes. Add two functions and two constants and allow any model to create objects.

```php
class Your Class extends Row {
    /** @var LinkedObject */
    var $linkedObject;

    const TABLE = "table";
    const ID_COLUMN = "table_id";

    function __construct() {
        $this->setObjectForKey(LinkedObjectClass::class, 'linked_id', 'linkedObject');
    }

    function toRow() {
        $row = parent::toRow();
        // ... Add your fields to the row.
        return $row;
    }

    function fromRow($row) {
        parent::fromRow($row);
        // ... Set your fields.
    }
}
```

Saving is extremely simple when you have an object whose class extends row.

`$object->save();`

### MyDB

Your connection to mysql. To get your rows as objects use this singleton `MyDB::getInstance()`

**getObjectByID(Class::class,$id)**

Alternatively you can just call `getByID($id)` on your custom Row object.

This will return your exact object.

**getObjectsWithQuery(Class::class,$query)**

An array of objects based on a custom query. Not all queries need to be written out though.

**getObjectByColumn(Class::class, 'ColumnName', $value)**

If you're only searching by one column use this simple function.