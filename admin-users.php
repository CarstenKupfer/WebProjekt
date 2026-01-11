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

require_once __DIR__ . "/util/dbUtil.php";

// ----------------------------
// USER TOGGLE BLOCK (POST)
// ----------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_block_id"])) {
    $targetId = (int)$_POST["toggle_block_id"];

    // optional: nicht sich selbst sperren
    if ($targetId === (int)$_SESSION["user_id"]) {
        $adminError = "Du kannst dich nicht selbst sperren.";
    } else {
        try {
            $db = getDb();

            // aktuellen status holen
            $stmt = $db->prepare("SELECT is_blocked FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $targetId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $newVal = ((int)$row["is_blocked"] === 1) ? 0 : 1;

                $stmt = $db->prepare("UPDATE users SET is_blocked = ? WHERE id = ? LIMIT 1");
                $stmt->bind_param("ii", $newVal, $targetId);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: admin-users.php");
            exit;

        } catch (Throwable $e) {
            $adminError = "User-Update fehlgeschlagen: " . $e->getMessage();
        }
    }
}

// ----------------------------
// USERS LADEN
// ----------------------------
try {
    $db = getDb();
    $res = $db->query("SELECT id, username, email, role, is_blocked, created_at FROM users ORDER BY created_at DESC");
    if (!$res) throw new RuntimeException($db->error);

    $users = $res->fetch_all(MYSQLI_ASSOC);

} catch (Throwable $e) {
    $adminError = "User konnten nicht geladen werden: " . $e->getMessage();
    $users = [];
}

include __DIR__ . "/includes/header.php";
?>

<section class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <h1>Admin – User</h1>
        <a class="post-file" href="admin.php">← Zurück zum Admin Panel</a>
    </div>

    <?php if (isset($adminError)): ?>
        <p class="login-error"><?= htmlspecialchars($adminError, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <?php if (empty($users)): ?>
        <p class="no-posts-hint">Keine User gefunden.</p>
    <?php else: ?>
        <div class="posts-list">
            <?php foreach ($users as $u): ?>
                <article class="post-card">
                    <h3 style="margin-bottom:6px;">
                        @<?= htmlspecialchars($u["username"], ENT_QUOTES, "UTF-8") ?>
                        <?php if ($u["role"] === "admin"): ?>
                            <span class="post-badge badge-found" style="margin-left:10px;">ADMIN</span>
                        <?php endif; ?>
                        <?php if ((int)$u["is_blocked"] === 1): ?>
                            <span class="post-badge badge-lost" style="margin-left:10px;">GESPERRT</span>
                        <?php endif; ?>
                    </h3>

                    <p class="no-posts-hint">
                        <?= htmlspecialchars($u["email"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <div class="item-actions" style="margin-top:10px;">
                        <?php if ((int)$u["id"] !== (int)$_SESSION["user_id"]): ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="toggle_block_id" value="<?= (int)$u["id"] ?>">
                                <?php if ((int)$u["is_blocked"] === 1): ?>
                                    <button type="submit" class="btn-small">Entsperren</button>
                                <?php else: ?>
                                    <button type="submit" class="btn-small btn-danger"
                                            onclick="return confirm('User wirklich sperren?');">
                                        Sperren
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <span class="no-posts-hint">Das bist du.</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
