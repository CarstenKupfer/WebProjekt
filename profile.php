<?php
// Session starten (nur einmal pro Request)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Profilseite ist nur für eingeloggte User
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/util/dbUtil.php";

$db = getDb();

// Userdaten für die Profilbox laden
$stmt = $db->prepare("SELECT username, email, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Eigene Items des Users laden (neueste zuerst)
$stmt = $db->prepare("SELECT id, type, title, location, event_date, image_path FROM items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include __DIR__ . "/includes/header.php"; ?>

<section class="profile-page">
    <h1>Mein Profil</h1>

    <div class="profile-box">
        <p><strong>Username:</strong> <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></p>
        <p><strong>E-Mail:</strong> <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?></p>
    </div>

    <h2>Meine Beiträge</h2>

    <?php if (empty($items)): ?>
        <p class="no-posts-hint">Du hast noch keine Beiträge erstellt.</p>
    <?php else: ?>
        <div class="posts-list">
            <?php foreach ($items as $item): ?>
                <article class="post-card">
                    <h3><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></h3>

                    <p>
                        <?= $item['type'] === 'lost' ? 'Verloren' : 'Gefunden' ?>
                        • <?= htmlspecialchars($item['location'], ENT_QUOTES, "UTF-8") ?>
                        • <?= date("d.m.Y", strtotime($item['event_date'])) ?>
                    </p>

                    <?php if (!empty($item["image_path"])): ?>
                        <?php
                            // File-Extension prüfen, um PDF anders zu behandeln
                            $path = $item["image_path"];
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        ?>

                        <?php if ($ext === "pdf"): ?>
                            <a class="post-file"
                               href="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>"
                               target="_blank" rel="noopener">
                                PDF ansehen
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">
                                <img class="post-image"
                                     src="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>"
                                     alt="Item Bild">
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Später: Bearbeiten / Löschen Buttons -->
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
