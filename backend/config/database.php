<?php

function db(): mysqli
{
    static $mysqli = null;

    if ($mysqli === null) {
        $host = 'db';          
        $user = 'appuser';
        $pass = 'secret';
        $db   = 'app';

        $mysqli = new mysqli($host, $user, $pass, $db);

        if ($mysqli->connect_error) {
            die("Ошибка подключения к базе: " . $mysqli->connect_error);
        }

        $mysqli->set_charset("utf8mb4");
    }

    return $mysqli;
}