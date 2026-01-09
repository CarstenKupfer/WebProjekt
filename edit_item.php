<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/util/dbUtil.php";

$db = getDb();
$uid = (int)$_SESSION["user_id"];
$itemId = (int)($_GET["id"] ?? 0);

if($itemId <= 0){
    header("Location: profile.php");
    exit();
}

// Eigenen Items laden
$stmt = $db->prepare("SELECT id, type, title, description, location, event_date, image_path FROM items WHERE id = ? AND user_id = ? LIMIT 1");
if(!$stmt){
    throw new RuntimeException($db->error);
}
$stmt->bind_param("ii", $itemId, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$item){
    header("Location: profile.php");
    exit();
}

// Update
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $type = $_POST["type"] ?? "";
    $location = trim($_POST["location"] ?? "");
    $event_date = $_POST["event_date"] ?? "";


    if($title === "" || $description === "" || $type === "" || $location === "" || $event_date === ""){
        $error = "Bitte alle Felder ausfüllen.";
    }elseif (!in_array($type, ["lost", "found"], true)){
        $error = "Ungültiger Typ.";
    }else{

        $stmt = $db->prepare("UPDATE items SET type = ?, title = ?, description = ?, location = ?, event_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ? LIMIT 1");
        if(!$stmt){
            throw new RuntimeException($db->error);
        }
        $stmt->bind_param("sssssii", $type, $title, $description, $location, $event_date, $itemId, $uid);
        $stmt->execute();
        $stmt->close();
        
        header("Location: profile.php");
        exit();
    }
}

include __DIR__ . "/includes/header.php";
?>

<section class="page-content" style="max-width: 720px; margin: 24px auto;">
     <h1>Beitrag bearbeiten</h1>

     <?php if (isset($error)): ?>
        <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
     <?php endif; ?>   

     <form class="post-form" method="post">
        <div class="form-group">
            <label for="title">Titel</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?>">
        </div>

        <div class="form-group">
            <label for="type">Art</label>
            <select name="type" id="type" required>
                <option value="lost" <?= $item["type"] === "lost" ? "selected" : "" ?>>Verloren</option>
                <option value="found" <?= $item["type"] === "found" ? "selected" : "" ?>>Gefunden</option>
            </select>
        </div>

        <div class="form-group">
            <label for="location">Ort</label>
            <input type="text" name="location" id="location" required value="<?= htmlspecialchars($item["location"], ENT_QUOTES, "UTF-8") ?>">
        </div>

        <div class="form-group">
            <label for="event_date">Datum</label>
            <input type="date" name="event_date" id="event_date" required value="<?= htmlspecialchars($item["event_date"], ENT_QUOTES, "UTF-8") ?>">
        </div>

        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea name="description" id="description" rows="6" required><?= htmlspecialchars($item["description"], ENT_QUOTES, "UTF-8") ?></textarea>
        </div>

        <button type="submit" class="post-submit-btn">Speichern</button>
        <a href="profile.php" class="claim-cancel">Abbrechen</a>
     </form>
</section>

<?php include __DIR__ . "/includes/footer.php";