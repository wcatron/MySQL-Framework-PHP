<?php

namespace wcatron\MySQLDBFramework;

class MyDB {
    /** @var \mysqli */
    var $db;

    private static $instance;
    /**
     * Gets the singleton instance of MyDB. Used throughout the framework.
     * @return MyDB Returns the singlton MyDB object.
     */
    public static function getInstance($connect = true) {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        if ($connect) {
            static::$instance->connect();
        }
        return static::$instance;
    }

    public static function configure($config) {
        static::getInstance(false)->config = $config;
    }

    function connect($config = null) {
        if (!isset($this->db)) {
            if ($config == null) {
                $config = $this->config;
            }
            $conn = new \mysqli($config['host'], $config['user'], $config['pass'], $config['db'], $config['port']) or Die("Connection failed");
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $this->db = $conn;
        }
    }

    /**
     * @param      $object Row
     * @param bool $secure
     * @return mixed
     * @throws DependencyException
     */
    function saveRow($object, $secure = false) {
        $new_row = $object->toRow();
        $keys = [];
        if ($object->needsInsert()) {
            // Insert
            foreach ($new_row as $key => $value) {
                if (empty($value)) {
                    unset($new_row[$key]);
                }
            }

            $keys = array_keys($new_row);
            $values = array_values($new_row);

            $keys_prepared = implode(',', $keys);
            $values_prepared = self::stringOfPreparedVariables(1, count($values) + 1);

            $statement = 'INSERT INTO ' . $object::TABLE . ' (' . $keys_prepared . ') VALUES (' . $values_prepared . ');';

            $results = $this->runPreparedStatement("saveRow" . $object::TABLE . implode('_', $keys), $statement, $values);

            if ($this->db->errno !== 0) {
                return false;
            }

            if ($object::ID_COLUMN != null) {
                $new_row[$object::ID_COLUMN] = $this->db->insert_id;
            }

            return $new_row;
        } else {
            $update_query = $object->customUpdate();
            if ($update_query == false) {
                $keys = self::keys_changed($object->old_row, $new_row);

                if (count($keys) == 0) {
                    // No-Change
                    return $new_row;
                }

                $set_strings = [];
                $values = [];
                foreach ($keys as $key) {
                    $set_strings[] = $key . '=?';
                    $values[] = $new_row[$key];
                }

                $statement = 'UPDATE ' . $object::TABLE . ' SET ' . implode(',', $set_strings) . ' WHERE ' . $object->selector() . ' ;';

                $update_query = ["statement" => $statement, "values" => array_merge($values, $object->getSelectorValues())];

            }

            $result = $this->runPreparedStatement("updateRow" . $object::TABLE . implode('-', $keys), $update_query['statement'], $update_query['values']);

            if ($this->db->errno !== 0) {
                return false;
            }

            return $new_row;
        }

    }

    function removeRow($object) {
        $statement = 'DELETE FROM ' . $object::TABLE . ' WHERE ' . $object->selector() . ';';
        $values = $object->getSelectorValues();

        $results = $this->runPreparedStatement("deleteRowFrom" . $object::TABLE, $statement, $values);

        if ($this->db->errno !== 0) {
            return false;
            //throw(new PostgresException(pg_last_error() . ": deleteRowFrom" . $object::TABLE));
        }

        return true;
    }

    function getObjectByID($objectType, $id) {
        $column = $objectType::ID_COLUMN;
        return $this->getObjectByColumn($objectType, $column, $id);
    }

    function getObjectByColumn($objectType, $column, $value) {
        $values = [$value];
        $results = $this->runPreparedStatement('get' . $objectType . 'By' . $column, 'SELECT * FROM ' . $objectType::TABLE . ' WHERE ' . $column . ' = ? LIMIT 1', $values);

        if ($results->num_rows == 1) {
            $row = $results->fetch_assoc();
        } else {
            // throw new \Exception('No rows matching column value. ');
        }

        if ($row == false) {
            return false;
        }
        $object = new $objectType();
        $object->fromRow($row);
        return $object;
    }

    function getObjectWithQuery($objectType, $query) {
        $object = new $objectType();
        $results = $this->db->query($query);
        try {
            $row = $results->fetch_assoc();
        } catch (Exception $e) {
            return false;
        }
        if ($row == false) {
            return false;
        }

        $object = new $objectType();
        $object->fromRow($row);
        return $object;
    }

    function getObjectWithStatement($objectType, $name, $statement, &$values) {
        $results = $this->runPreparedStatement($name, $statement, $values);
        try {
            $row = pg_fetch_array($results, 0, PGSQL_ASSOC);
        } catch (Exception $e) {
            return false;
        }

        if ($row == false) {
            return false;
        }

        $object = new $objectType();
        $object->fromRow($row);
        return $object;
    }

    function getObjectsByColumn($objectType, $column, $value) {
        $results = $this->runPreparedStatement("getObjectsByColumnFrom" . $objectType::TABLE, 'SELECT * FROM ' . $objectType::TABLE . ' WHERE ' . $column . ' = ?', [$value]);
        $objects = [];

        while ($row = $results->fetch_assoc()) {
            $object = new $objectType();
            $object->fromRow($row);
            $objects[] = $object;
        }

        return $objects;
    }

    function getObjectsByColumnsAndValues($objectType, $columns, $values) {

        $statement = 'SELECT * FROM ' . $objectType::TABLE . ' WHERE';

        $num = 1;
        foreach ($columns as $column) {
            if ($num > 1) {
                $statement .= " AND";
            }
            $statement .= ' ' . $column . ' = $' . $num;
            $num++;
        }

        return $this->getObjectsWithStatement($objectType, "getObjectsFrom" . $objectType::TABLE . 'By' . implode('-', $columns), $statement, $values);
    }

    function getAllObjects($objectType) {
        return $this->getObjectsWithQuery($objectType, 'SELECT * FROM ' . $objectType::TABLE);
    }

    function getObjectsWithQuery($objectType, $query, DBPagination $pagination = null) {
        if (is_null($pagination)) {
            $results = $this->db->query($query);
            if (!$results) {
                return false;
            }
        } else {
            // TO-DO md5 or something the query as the statement name.
            return $this->getObjectsWithStatement($objectType, $query, $query, [], $pagination);
        }
        $objects = [];

        while ($row = $results->fetch_assoc()) {
            $object = new $objectType();
            $object->fromRow($row);
            $objects[] = $object;
        }

        return $objects;
    }

    function getObjectsWithStatement($objectType, $name, $statement, $values, DBPagination $pagination = null) {
        if (!is_null($pagination)) {
            $statement = $pagination->getStatement($statement, $values);
        }
        $results = $this->runPreparedStatement($name, $statement, $values);
        $objects = [];

        while ($row = $results->fetch_assoc()) {
            $object = new $objectType();
            $object->fromRow($row);
            $objects[] = $object;
        }

        return $objects;
    }

    function getCountWithStatement($name, $statement, $values, DBPagination $pagination = null) {
        if (!is_null($pagination)) {
            $statement = $pagination->getCountStatement($statement, $values);
        }
        $results = $this->runPreparedStatement($name, $statement, $values);
        $data = pg_fetch_assoc($results);
        return (int)$data['total'];
    }

    private $statements = [];

    /**
     * @param $name
     * @param $statement
     * @param $values
     * @return \mysqli_result
     */
    function runPreparedStatement($name, $statement, &$values) {
        $statement = $this->db->prepare($statement);
        if ($statement == false) {
            throw new \Exception('Could not prepare statement. '.$this->db->error);
        }
        $allValues = [""];
        foreach($values as &$value) {
            if (is_int($value)) {
                $allValues[0] .= "i";
            } else if (is_string($value)) {
                $allValues[0] .= "s";
            } else if (is_double($value)) {
                $allValues[0] .= "d";
            } else {
                throw new \Exception('Unsupported type for prepared statements.');
            }
            $allValues[] = &$value;
        }
        $ref    = new \ReflectionClass('mysqli_stmt');
        $method = $ref->getMethod("bind_param");
        $method->invokeArgs($statement,$allValues);

        $results = $statement->execute();

        if ($results === false) {
            throw new \Exception('Could not execute statement '.$this->db->error);
        }
        return $statement->get_result();
    }

    function runQuery($query) {
        return pg_query($this->db, $query);
    }

    static function keys_changed($old, $new) {
        $all_keys = array_keys($new);
        $return_keys = [];

        foreach ($all_keys as $key) {
            if ($new[$key] !== $old[$key]) {
                array_push($return_keys, $key);
            }
        }

        return $return_keys;
    }

    static function stringOfPreparedVariables($start, $end) {
        $str = '?';
        for ($num = $start + 1; $num < $end; $num++) {
            $str .= ', ?';
        }
        return $str;
    }

    function beginTransaction() {
        pg_query("BEGIN");
    }

    function commitTransaction() {
        pg_query("COMMIT");
    }

    function rollbackTransaction() {
        pg_query("ROLLBACK");
    }
}


?>
