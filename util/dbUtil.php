<?php

require_once __DIR__ . "/../config/config.php";

function getPDO(): PDO {
    global $dbHost, $dbName, $dbUser, $dbPassword;

    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $dbUser, $dbPassword, $options);
}