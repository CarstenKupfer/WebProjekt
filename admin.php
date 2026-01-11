<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "user") !== "admin") {
    header("Location: index.php");
    exit;
}

include __DIR__ . "/includes/header.php";
?>

<section class="page-content" style="max-width: 900px;">
    <h1>Admin Panel</h1>
    <p class="no-posts-hint">Wähle, was du verwalten möchtest:</p>

    <div class="posts-list" style="gap: 12px;">
        <article class="post-card">
            <h3>Beiträge verwalten</h3>
            <p>Alle gemeldeten Gegenstände ansehen, filtern und löschen.</p>
            <a class="post-file" href="admin-items.php">Zu den Beiträgen →</a>
        </article>

        <article class="post-card">
            <h3>User verwalten</h3>
            <p>User sperren/entsperren und Rollen sehen.</p>
            <a class="post-file" href="admin-users.php">Zu den Usern →</a>
        </article>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
