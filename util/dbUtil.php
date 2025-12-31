<?php

require_once __DIR__ . "/../config/config.php";

function getDb():mysqli{
    global $dbHost, $dbUser, $dbPassword, $dbName;

    $db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);


if ($db->connect_errno) {
    throw new RuntimeException(
        "DB Connection failed: " . $db->connect_error,
    );
    
}

$db->set_charset("utf8mb4");
return $db;
}