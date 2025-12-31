<?php

try {
    require_once __DIR__ . "/../util/dbUtil.php";
    echo "Datenbankverbindung erfolgreich!";
} catch (RuntimeException $e) {
    echo "Fehler: " . $e->getMessage();
}

echo "<br>";


try {
    $db = getDb();
    echo "DB OK ";
} catch (Throwable $e) {
    echo "DB FEHLER : " . $e->getMessage();
}

