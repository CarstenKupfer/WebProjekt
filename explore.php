<?php
// Session starten (nur einmal pro Request)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Diese Seite ist nur für eingeloggte User
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// DB und Funktionen fürs Speichern/Upload laden
require_once __DIR__ . "/util/dbUtil.php";
require_once __DIR__ . "/util/upload.php";

// Wenn das Formular abgeschickt wurde, neuen Eintrag speichern
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Eingaben holen und trimmen
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $type = $_POST["type"] ?? "";
    $location = trim($_POST["location"] ?? "");
    $event_date = $_POST["event_date"] ?? "";

    // Basic Check: alles Pflichtfelder müssen ausgefüllt sein
    if ($title === "" || $description === "" || $type === "" || $location === "" || $event_date === "") {
        $error = "Bitte alle Felder ausfüllen.";
    } else {

        // Upload: Datei ist optional
        $targetDir = "uploads/items/";
        $targetFile = null;

        if (!empty($_FILES["image"]["name"])) {
            $filename = basename($_FILES["image"]["name"]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Erlaubte Dateitypen
            $allowed = ["pdf", "jpg", "jpeg", "jfif", "png"];

            if (!in_array($ext, $allowed)) {
                $error = "Nur PDF, JPG, oder PNG erlaubt.";
            } else {
                // Eindeutiger Name, damit nichts überschrieben wird
                $filename = uniqid("item_", true) . "." . $ext;
                $targetFile = $targetDir . $filename;

                // Datei ins Upload-Verzeichnis verschieben
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    $error = "Fehler beim Upload.";
                    $targetFile = null;
                }
            }
        }

        // Wenn alles passt, in die DB speichern und neu laden (damit kein Doppel-Post bei Refresh passiert)
        if (!isset($error)) {
            createItemInDB($type, $title, $description, $location, $event_date, $targetFile, (int)$_SESSION["user_id"]);
            header("Location: explore.php");
            exit();
        }
    }
}

// Feed-Pagination: wie viele Items pro Seite
$itemsPerPage = 5;

// Aktuelle Seite holen (mindestens 1)
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $itemsPerPage;

try {
    $db = getDb();

    // Items laden inkl. Username vom Ersteller
    $sql = "SELECT i.id, i.type, i.title, i.description, i.location, i.event_date, i.image_path, i.created_at, u.username
            FROM items i
            JOIN users u ON u.id = i.user_id
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $itemsPerPage, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Für Pagination: Gesamtanzahl zählen
    $resCount = $db->query("SELECT COUNT(*) AS cnt FROM items");
    if (!$resCount) {
        throw new RuntimeException($db->error);
    }

    $totalItems = (int)$resCount->fetch_assoc()["cnt"];
    $totalPages = (int)ceil($totalItems / $itemsPerPage);

} catch (Throwable $e) {
    // Falls DB down ist oder Query schief geht, bleibt die Seite trotzdem stabil
    $feedError = "Feed konnte nicht geladen werden: " . $e->getMessage();
    $items = [];
    $totalPages = 1;
}

// Header + Navigation laden
include __DIR__ . "/includes/header.php";
?>

<section class="explore-layout">

    <!-- Checkbox zum Ein-/Ausblenden des Formulars (nur HTML/CSS, kein JavaScript) -->
    <input type="checkbox" id="toggle-post-form" class="toggle-checkbox">

    <!-- Linke Spalte: Feed -->
    <div class="posts-column">

        <div class="explore-topbar">
            <h1 class="explore-heading">Gegenstände entdecken</h1>

            <label for="toggle-post-form" class="post-toggle-btn">
                + Beitrag melden
            </label>
        </div>

        <div class="posts-list">

            <?php if (isset($feedError)): ?>
                <p class="login-error"><?= htmlspecialchars($feedError, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <?php if (empty($items)): ?>
                <p class="no-posts-hint">
                    Noch keine Beiträge vorhanden. Sei der Erste, der einen Gegenstand meldet.
                </p>
            <?php else: ?>

                <?php foreach ($items as $item): ?>
                    <article class="post-card">
                        <div class="post-card-top">
                            <div class="post-badge <?= $item["type"] === "lost" ? "badge-lost" : "badge-found" ?>">
                                <?= htmlspecialchars($item["type"] === "lost" ? "VERLOREN" : "GEFUNDEN", ENT_QUOTES, "UTF-8") ?>
                            </div>

                            <div class="post-meta">
                                <span class="post-user">@<?= htmlspecialchars($item["username"], ENT_QUOTES, "UTF-8") ?></span>
                            </div>
                        </div>

                        <h3 class="post-title"><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></h3>

                        <p class="post-info">
                            <strong>Ort:</strong> <?= htmlspecialchars($item["location"], ENT_QUOTES, "UTF-8") ?>
                            &nbsp;|&nbsp;
                            <strong>Datum:</strong> <?= date("d.m.Y", strtotime($item["event_date"])) ?>
                        </p>

                        <p class="post-desc">
                            <?= nl2br(htmlspecialchars($item["description"], ENT_QUOTES, "UTF-8")) ?>
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
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="explore.php?page=<?= $page - 1 ?>">Zurück</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="explore.php?page=<?= $p ?>" class="<?= $p === $page ? "active" : "" ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="explore.php?page=<?= $page + 1 ?>">Weiter</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Rechte Spalte: Formular zum Erstellen eines Posts -->
    <aside class="post-form-column">
        <h2 class="post-form-heading">Neuen Gegenstand melden</h2>

        <form class="post-form" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Titel</label>
                <input type="text" id="title" name="title" placeholder="z.B. 'Schlüsselbund gefunden'" required>
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
                <textarea id="description" name="description" rows="5" placeholder="Was wurde gefunden/verloren? Wo? Wann?" required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Bild oder PDF hinzufügen (optional)</label>
                <input type="file" id="image" name="image" accept=".pdf, image/*">
            </div>

            <button type="submit" class="post-submit-btn">Posten</button>
        </form>
    </aside>
</section>

<?php
// Footer einbinden
include __DIR__ . "/includes/footer.php";
?>
