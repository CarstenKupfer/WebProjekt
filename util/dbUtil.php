<?php

require_once __DIR__ . "/../config/config.php";

$db_obj = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($db_obj->connect_errno) {
    throw new RuntimeException(
        "DB Connection failed: " . $db_obj->connect_error,
    );
}

$db_obj->set_charset("utf8mb4");
