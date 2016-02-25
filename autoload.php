<?php

use wcatron\MySQLDBFramework\MyDB;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists('config.ini')) {
    $config = parse_ini_file('config.ini');
    MyDB::configure($config['mysql']);
} else {
    $config = [
        "host" => "127.0.0.1",
        "user" => "root",
        "pass" => "",
        "port" => "3306",
        "db" => "mysql_framework_php_testing"
    ];
    MyDB::configure($config);
}



?>
