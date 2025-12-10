<?php
$role = $_SESSION['role'] ?? 'guest';
require_once __DIR__ . '/../config/config.php';
?>
<nav class="top-nav">
    <a class="logo" href="<?= $base_url ?>/index.php"><div class="logo">Lost &amp; Found</div></a>
    <div class="nav-links">

        <?php if ($role === 'guest'): ?>
            <a href="<?= $base_url ?>/login.php">Login / Register</a>
        <?php else: ?>
            <a href="<?= $base_url ?>/profile.php">Profil</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a href="<?= $base_url ?>/admin.php">Admin</a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= $base_url ?>/logout.php">Abmelden</a>
        <?php endif; ?>

    </div>
</nav>
