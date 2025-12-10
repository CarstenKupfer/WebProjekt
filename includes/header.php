<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php include __DIR__ . '/head-includes.php'; ?>
</head>
<body>
<header>
    <?php include __DIR__ . '/nav.php'; ?>
</header>
<main class="page-content">
