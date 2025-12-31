<?php
// -------------------------------------------
// SESSION / TIMEOUT LOGIK
// -------------------------------------------

if (session_status() === PHP_SESSION_NONE){
    session_start();
}


// Timeout für inaktivität 
$inactiveTimeout = 30 * 60;

if(isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"]) > $inactiveTimeout){
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

// Absolute Session-Lifetime
$absoluteTimeout = 4 * 60 * 60;

if (isset($_SESSION['login_time']) &&
    (time() - $_SESSION['login_time']) > $absoluteTimeout
) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

// Zeit aktualisieren
$_SESSION['last_activity'] = time();
