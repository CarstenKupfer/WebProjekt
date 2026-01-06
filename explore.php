<?php
// Session starten, falls noch nicht
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zugriff nur für eingeloggte User
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/util/dbUtil.php";
require_once __DIR__ . "/util/upload.php";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $type = $_POST["type"] ?? "";
    $location = trim($_POST["location"] ?? "");
    $event_date = $_POST["event_date"] ?? "";

    if($title === "" || $description === "" || $type === "" || $location === "" || $event_date === ""){
        $error = "Bitte alle Felder ausfüllen.";
    }else{

        $targetDir = "uploads/items/";
        $targetFile = null;
        
        // Bild optional
        if(!empty($_FILES["image"]["name"])){
            $filename = basename($_FILES["image"]["name"]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ["pdf","jpg","jpeg","png"];
             
            if(!in_array($ext, $allowed)){
                $error = "Nur PDF, JPG, oder PNG erlaubt.";
            }else{
                // kriegen uniqe name damit nichts überschrieben wird
                $filename = uniqid("item_", true) . "." . $ext;
                $targetFile = $targetDir . $filename;

                if(!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)){
                    $error = "Fehler beim Upload.";
                    $targetFile = null;
                }
            }
        }
        // Wenn keine Fehler ist, dann in DB speichern
        if(!isset($error)){
            createItemInDB($type, $title, $description, $location, $event_date, $targetFile, (int)$_SESSION["user_id"]);
            header("Location: explore.php");
            exit();

        }

    }



}


// Header + Navigation laden
include __DIR__ . '/includes/header.php';
?>

<section class="explore-layout">

    <!-- Checkbox, um das Formular rechts ein- / auszublenden -->
    <input type="checkbox" id="toggle-post-form" class="toggle-checkbox">

    <!-- LINKE SPALTE: Beiträge / Feed -->
    <div class="posts-column">

        <div class="explore-topbar">
            <h1 class="explore-heading">Gegenstände entdecken</h1>

            <label for="toggle-post-form" class="post-toggle-btn">
                + Beitrag melden
            </label>
        </div>

        <div class="posts-list">
            <!-- Noch keine echten Beiträge – später mit DB füllen -->
            <p class="no-posts-hint">
                Noch keine Beiträge vorhanden. Sei der Erste, der einen Gegenstand meldet.
            </p>
        </div>
    </div>

    <!-- RECHTE SPALTE: Beitrag erstellen (startet versteckt) -->
    <aside class="post-form-column">
        <h2 class="post-form-heading">Neuen Gegenstand melden</h2>

        <form class="post-form" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Titel</label>
                <input type="text" id="title" name="title"
                       placeholder="z.B. 'Schlüsselbund gefunden'" required>
            </div>
            <div class="form-group">
                <label for="type">Art</label>
                <select name="type" id="type" required>
                    <option value="">Bitte wählen</option>
                    <option value="lost">Verloren</option>
                    <option value="found">Gefunden</option>
                </select>
            </div>
            <div class="form-group">
                <label for="location">Ort</label>
                <input type="text" name="location" id="location" required>
            </div>
            <div class="form-group">
                <label for="event_date">Datum</label>
                <input type="date" name="event_date" id="event_date" required>
            </div>

            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Was wurde gefunden/verloren? Wo? Wann?" required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Bild oder PDF hinzufügen (optional)</label>
                <input type="file" id="image" name="image">
            </div>

            <button type="submit" class="post-submit-btn">Posten</button>
        </form>
    </aside>
</section>

<?php
// Footer einbinden
include __DIR__ . '/includes/footer.php';
?>
