<?php

require_once __DIR__ . '/../config/config.php';

class Database
{
// Anoigei mysqli connection me ta project-wide DB constants, elegxei oti i syndesi petyxe
// kai efarmozei to configured charset prin epistrepsei to handle.
    public static function connect(): mysqli
    {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($connection->connect_error) {
            die('Connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset(DB_CHARSET);

        return $connection;
    }
}
