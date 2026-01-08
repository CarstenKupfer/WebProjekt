<?php
// ----------------------------
// SESSION + LOGIN CHECK
// ----------------------------

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/util/dbUtil.php";

// ----------------------------
// ITEM ID AUS GET
// ----------------------------
$itemId = (int)($_GET["item_id"] ?? 0);
if($itemId <= 0){
    header("Location: explore.php");
    exit();
}

$db = getDb();

// ----------------------------
// ITEM + OWNER LADEN
// ----------------------------
$stmt=$db->prepare("SELECT i.id, i.user_id, i.type, i.title, i.location, i.event_date, u.username
FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ? LIMIT 1");

if(!$stmt){
    $error = "DB-Fehler: " . ($db->error);
}else{
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();

    if(!$item){
        header("Location: explore.php");
        exit();
    }

    // Wenn Eigenes Item dann zurückleiten
    if ((int)$item["user_id"] === (int)$_SESSION["user_id"]) {
        header("Location: explore.php");
        exit;
    }
}

// ----------------------------
// FORMULAR ABSCHICKEN
// ----------------------------
if(!isset($error) && $_SERVER["REQUEST_METHOD"] === "POST"){

    $message = trim($_POST["message"] ?? "");

    if($message === ""){
        $error = "Bitte eine Nachricht eingeben.";
    }elseif (mb_strlen($message) > 1000){
        $error = "Nachricht ist zu lang (max. 1000 Zeichen).";
    }else{

        try{
            //verhindern das derselbe User das gleiche Item merfach claimt
            $stmt = $db->prepare("SELECT id FROM claims WHERE item_id = ? AND claimer_id = ? LIMIT 1");
            if(!$stmt){
                throw new RuntimeException($db->error);
            }
            $uid = (int)$_SESSION["user_id"];
            $stmt->bind_param("ii", $itemId, $uid);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if($existing){
                $error = "Du hast dieses Item bereits geclaimt.";
            }else{
                //Claim speichern
                $stmt = $db->prepare("INSERT INTO claims (item_id, claimer_id, message) VALUES (?, ?, ?)");
                if(!$stmt){
                    throw new RuntimeException($db->error);
                }
                $stmt->bind_param("iis", $itemId, $uid, $message);
                $stmt->execute();
                $stmt->close();

                //Zurück zu explore seite wieder (oder Profil?)
                header("Location: explore.php");
                exit();
            }

        }catch (Throwable $e){
            $error = "DB-Fehler: " . ($e->getMessage());
        }
    }
}

// ----------------------------
// HEADER
// ----------------------------
include __DIR__ . "/includes/header.php";
?>

<section class="page-content" style="max-width: 720px; margin: 24px auto;">
    <h1>Claim / Kontakt</h1>

    <?php if (isset($error)): ?>
        <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <div class="profile-box" style="margin-top: 12px;">
        <p><strong>Gegenstand:</strong> <?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></p>
        <p><strong>Typ:</strong> <?= htmlspecialchars($item["type"] === "lost" ? "Verloren" : "Gefunden", ENT_QUOTES, "UTF-8") ?></p>
        <p><strong>Ort:</strong> <?= htmlspecialchars($item["location"], ENT_QUOTES, "UTF-8") ?></p>
        <p><strong>Datum:</strong> <?= date("d.m.Y", strtotime($item["event_date"])) ?></p>
        <p><strong>Ersteller:</strong> <?= htmlspecialchars($item["username"], ENT_QUOTES, "UTF-8") ?></p>
    </div>

    <h2 style="margin-top: 18px;">Nachricht</h2>

    <form class="login-form" method="post" style="margin-top: 10px;">
        <label for="message">Schreibe deine Nachricht an den Ersteller</label>
        <textarea name="message" id="message" rows="6" required 
            placeholder="Z.B. Wo und wann du den Gegenstand abholen kannst..."
            style="padding:10px; border-radius: 6px; border: 1px solid #ccc;"><?= htmlspecialchars($_POST["message"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>

        <button type="submit" class="login-btn">Claim senden</button>
        <a href="explore.php" class="claim-cancel">Abbrechen</a>
    </form>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>

