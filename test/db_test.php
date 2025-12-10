<?php

require_once __DIR__ . "/../util/dbUtil.php";

if($db_obj){
    echo "Datenbankverbindung erfolgreich!";
}else{
    echo "Verbindung fehlgeschlagen";
}

