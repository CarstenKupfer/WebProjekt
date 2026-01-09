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

// ----------------------------
// ITEM LÖSCHEN 
// ----------------------------
if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_item_id"])){
    $deleteId = (int)$_POST["delete_item_id"];


try{
    $db = getDb();
    $uid = (int)$_SESSION["user_id"];

    // Item laden (nur eigenes Item darf gelöscht werden logisch)
    $stmt = $db->prepare("SELECT image_path FROM items WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $deleteId, $uid);
    $stmt->execute();
    $itemRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($itemRow){
        //Item löschen
        $stmt = $db->prepare("DELETE FROM items WHERE id = ? and user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $deleteId, $uid);
        $stmt->execute();
        $stmt->close();

        // Datei aus uploads/items löschen, wenn vorhanden
        if(!empty($itemRow["image_path"])){
            $file = __DIR__ . "/" . $itemRow["image_path"];

            if(file_exists($file)){
                unlink($file); //unlink löscht die Datei
            }    
        }
    }

    header("Location: profile.php");
    exit();

}catch (Throwable $e){
    $profileError = "Löschung fehlgeschlagen: " . ($e->getMessage());
    }
}


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


// ----------------------------
// CLAIMS (Inbox) laden
// ----------------------------
try{
    $db = getDb();
    $uid = (int)$_SESSION["user_id"];

    // Eingegangene Claims (Items die jemand von mir geclaimt hat)

    $stmt = $db->prepare("SELECT c.id AS claim_id, c.message, c.created_at, u.username AS claimer_username, i.id AS item_id, i.title AS item_title, i.type AS item_type
    FROM claims c JOIN items i ON i.id = c.item_id JOIN users u on u.id = c.claimer_id WHERE i.user_id = ? ORDER BY c.created_at DESC LIMIT 50");

    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $incomingClaims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

}catch (Throwable $e){
    $claimsError = "Claims konnten nicht geladen werden: " . ($e->getMessage());
    $incomingClaims = [];
}

?>

<?php include __DIR__ . "/includes/header.php"; ?>

<section class="profile-page">
    <h1>Mein Profil</h1>

    <div class="profile-box">
        <p><strong>Username:</strong> <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></p>
        <p><strong>E-Mail:</strong> <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?></p>
    </div>

    <h2>Claims</h2>

    <?php if (isset($claimsError)): ?>
        <p class="login-error"><?= htmlspecialchars($claimsError, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

        <!-- Eingegangene Claims -->
    <div class="claims-box">
        <h3>Eingegangene Claims</h3>

        <?php if (empty($incomingClaims)): ?>
            <p class="no-posts-hint">Noch keine Claims für deine Items.</p>
        <?php else: ?>
            <?php foreach ($incomingClaims as $c): ?>
                <div class="claim-card">
                    <div class="claim-top">
                        <span class="claim-user">@<?= htmlspecialchars($c["claimer_username"], ENT_QUOTES, "UTF-8") ?></span>
                        <span class="claim-date"><?= date("d.m.Y", strtotime($c["created_at"])) ?></span>
                    </div>

                    <p class="claim-item"><strong>Item: </strong><?= htmlspecialchars($c["item_title"], ENT_QUOTES, "UTF-8") ?> (<?= $c["item_type"] === "lost" ? "Verloren" :
                    "Gefunden" ?>)</p>

                    <p class="claim-msg"><?= htmlspecialchars($c["message"], ENT_QUOTES, "UTF-8") ?></p>
                </div>    
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr class="section-divider">

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

                    <!-- Bearbeiten / Löschen Buttons -->
                <div class="item-actions">
                    <a class="btn-small" href="edit_item.php?id=<?= (int)$item["id"] ?>">Bearbeiten</a>

                    <form method="post" class="inline-form">
                        <input type="hidden" name="delete_item_id" value="<?= (int)$item["id"] ?>">
                        <button type="submit" class="btn-small btn-danger">Löschen</button>
                    </form>
                </div>

                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
