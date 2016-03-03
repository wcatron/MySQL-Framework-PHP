<?php

namespace wcatron\MySQLDBFramework;

use wcatron\CommonDBFramework\DBPagination;
use wcatron\CommonDBFramework\LinkedObject;
use wcatron\CommonDBFramework\DBObject;

abstract class Row extends DBObject {
    public $old_row = null;
    /**
     * Array of linked objects.
     * @var LinkedObject[]
     */
    private $linked_objects = array();
    protected static $dbClass = MyDB::class;
    const TABLE = "undefined";
    /** If using multicolumn primary keys or anything besides a standard Id column
     * then leave null and use selector and getSelectorValues.
     */
    const ID_COLUMN = null;

    public function save() {
        $new_row = self::getDBInstance()->saveRow($this);
        if ($new_row) {
            $this->fromRow($new_row);
            return true;
        }
        return false;
    }

    public function delete() {
        return self::getDBInstance()->removeRow($this);
    }

    public function getID() {
        if (isset($this->old_row)) {
            return $this->old_row[static::ID_COLUMN];
        }
        return null;
    }

    /** Must be called by Child classes
     * @return array Associative array of row values to be saved in column => value format.
     */
    public function toRow() {
        if ($this->getID() == null) {
            $row = array();
        } else {
            $row = array(static::ID_COLUMN=>$this->getID());
        }
        foreach ($this->linked_objects as $object_property) {
            $this->$object_property->toArray($row);
        }
        return $row;
    }

    /** Must be called by Child classes.
     * @param array Array defining values of Row object.
     */
    public function fromRow($row) {
        $this->old_row = $row;
        foreach ($this->linked_objects as $object_property) {
            $this->$object_property->fromArray($row);
        }
    }

    /** Links child object from a document key, to a class property.
     * @param $object string Fully qualified class string for object.
     * @param $column string Column name containing the id for the object.
     * @param $property string Property name in object that will be used to access the object.
     */
    public function setObjectForKey($object, $column, $property = null, $rowObjectClass = LinkedObject::class) {
        if ($property == null) {
            $property = $column;
        }
        $this->$property = new $rowObjectClass($object, $column);
        $this->linked_objects[] = $property;
    }

    public function customUpdate() {
        return false;
    }
    /**
     * Returns selector statement. Override for multiple values.
     * Returns what would follow the WHERE clause in a SELECT UPDATE or DELETE
     */
    public function selector() {
        return static::ID_COLUMN.' = ?';
    }
    public function getSelectorValues() {
        return array($this->getID());
    }

    public static function getObjectName() {
        return get_called_class();
    }

    /**
     * @return static
     */
    public static function getByID($id) {
        return self::getDBInstance()->getObjectByID(self::getObjectName(),$id);
    }
    /**
     * @param string[] Values for the selector.
     * @return static
     */
    public static function getByValues($values) {
        return self::getDBInstance()->getObjectWithStatement(self::getObjectName(),'get'.static::class,'SELECT * FROM '.static::TABLE.' WHERE '.static::selector(),$values);
    }

    public static function toBoolean($value) {
        return ($value == 't');
    }
    public static function fromBoolean($value) {
        if ($value) {
            return "t";
        }
        return "f";
    }
    public static function toInt($value) {
        return (int)$value;
    }

    /**
     * Gets objects by a particular column value.
     * @param  string $column Name of column.
     * @param  string $value  Value to query for.
     * @return static[]
     */
    public static function getManyByColumn($column, $value) {
        return self::getDBInstance()->getObjectsByColumn(static::class, $column, $value);
    }

    /**
     * Gets one object by a particular column value.
     * @param  string $column Name of column.
     * @param  string $value  Value to query for.
     * @return static
     */
    public static function getOneByColumn($column, $value) {
        return self::getDBInstance()->getObjectByColumn(static::class, $column, $value);
    }

    /**
     * @param $name string Name for statement.
     * @param $statement string PostgresSQL statement.
     * @param $values array Values for statement.
     * @param DBPagination|null $pagination Pagination object.
     * @return static[] Array of objects.
     */
    public static function getObjectsWithStatement($name, $statement, $values, DBPagination $pagination = null) {
        return self::getDBInstance()->getObjectsWithStatement(static::class, $name, $statement, $values, $pagination);
    }

    /**
     * @return static[] Array of objects.
     */
    public static function getAllObjects() {
        return self::getDBInstance()->getAllObjects(static::class);
    }

    public function isEqual($object) {
        return ($this->getID() == $object->getID());
    }

    public function needsInsert() {
        return !($this->old_row);
    }

    /**
     * @return MyDB
     */
    public static function getDBInstance() {
        return parent::getDBInstance();
    }

}

?>
