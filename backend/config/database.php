<?php

function db(): mysqli
{
    static $mysqli = null;

    if ($mysqli === null) {
        $host = 'db';          // имя сервиса MySQL в Docker
        $user = 'appuser';
        $pass = 'secret';
        $db   = 'app';

        $mysqli = new mysqli($host, $user, $pass, $db);

        // Проверка подключения
        if ($mysqli->connect_error) {
            die("Ошибка подключения к базе: " . $mysqli->connect_error);
        }

        // Устанавливаем кодировку
        $mysqli->set_charset("utf8mb4");
    }

    return $mysqli;
}