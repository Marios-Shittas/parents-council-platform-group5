<?php
// Arxeio: app\core\Database.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

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
