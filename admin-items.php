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
// FILTER / SORT
// ----------------------------
$typeFilter = $_GET["type"] ?? "";          // "", "lost", "found"
$sort = $_GET["sort"] ?? "newest";          // "newest" | "oldest"

if (!in_array($typeFilter, ["", "lost", "found"], true)) $typeFilter = "";
if (!in_array($sort, ["newest", "oldest"], true)) $sort = "newest";

$orderBy = ($sort === "oldest") ? "i.created_at ASC" : "i.created_at DESC";

// ----------------------------
// DELETE ITEM
// ----------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_item_id"])) {
    $deleteId = (int)$_POST["delete_item_id"];

    try {
        $db = getDb();

        // image_path holen
        $stmt = $db->prepare("SELECT image_path FROM items WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // optional: falls FK ohne CASCADE, claims vorher löschen
        // $stmt = $db->prepare("DELETE FROM claims WHERE item_id = ?");
        // $stmt->bind_param("i", $deleteId);
        // $stmt->execute();
        // $stmt->close();

        // item löschen
        $stmt = $db->prepare("DELETE FROM items WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();

        // Datei löschen (uploads/items/..)
        if (!empty($row["image_path"])) {
            $file = __DIR__ . "/" . $row["image_path"];
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // redirect mit aktuellen filtern + page
        $page = (int)($_GET["page"] ?? 1);
        $qs = http_build_query([
            "type" => $typeFilter,
            "sort" => $sort,
            "page" => max(1, $page)
        ]);
        header("Location: admin-items.php?$qs");
        exit;

    } catch (Throwable $e) {
        $adminError = "Löschen fehlgeschlagen: " . $e->getMessage();
    }
}

// ----------------------------
// PAGINATION
// ----------------------------
$itemsPerPage = 10;
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $itemsPerPage;

// ----------------------------
// FEED LADEN
// ----------------------------
try {
    $db = getDb();

    $where = "1=1";
    $params = [];
    $types = "";

    if ($typeFilter !== "") {
        $where .= " AND i.type = ?";
        $params[] = $typeFilter;
        $types .= "s";
    }

    // Items query
    $sql = "SELECT i.id, i.type, i.title, i.location, i.event_date, i.image_path, i.created_at,
                   u.username, u.email
            FROM items i
            JOIN users u ON u.id = i.user_id
            WHERE $where
            ORDER BY $orderBy
            LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);

    // bind dynamic
    $types2 = $types . "ii";
    $params2 = array_merge($params, [$itemsPerPage, $offset]);
    $stmt->bind_param($types2, ...$params2);

    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Count query (für totalPages)
    $sqlCount = "SELECT COUNT(*) AS cnt FROM items i WHERE $where";
    $stmt = $db->prepare($sqlCount);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalItems = (int)$stmt->get_result()->fetch_assoc()["cnt"];
    $stmt->close();

    $totalPages = (int)ceil($totalItems / $itemsPerPage);

} catch (Throwable $e) {
    $adminError = "Beiträge konnten nicht geladen werden: " . $e->getMessage();
    $items = [];
    $totalPages = 1;
}

include __DIR__ . "/includes/header.php";
?>

<section class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <h1>Admin – Beiträge</h1>
        <a class="post-file" href="admin.php">← Zurück zum Admin Panel</a>
    </div>

    <?php if (isset($adminError)): ?>
        <p class="login-error"><?= htmlspecialchars($adminError, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <form method="get" class="post-form" style="margin: 12px 0; max-width: 520px;">
        <div class="form-group">
            <label for="type">Typ</label>
            <select name="type" id="type">
                <option value="" <?= $typeFilter === "" ? "selected" : "" ?>>Alle</option>
                <option value="lost" <?= $typeFilter === "lost" ? "selected" : "" ?>>Verloren</option>
                <option value="found" <?= $typeFilter === "found" ? "selected" : "" ?>>Gefunden</option>
            </select>
        </div>

        <div class="form-group">
            <label for="sort">Sortierung</label>
            <select name="sort" id="sort">
                <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Neueste zuerst</option>
                <option value="oldest" <?= $sort === "oldest" ? "selected" : "" ?>>Älteste zuerst</option>
            </select>
        </div>

        <button type="submit" class="post-submit-btn">Filter anwenden</button>
    </form>

    <?php if (empty($items)): ?>
        <p class="no-posts-hint">Keine Beiträge gefunden.</p>
    <?php else: ?>
        <div class="posts-list">
            <?php foreach ($items as $item): ?>
                <article class="post-card">
                    <div class="post-card-top">
                        <div class="post-badge <?= $item["type"] === "lost" ? "badge-lost" : "badge-found" ?>">
                            <?= $item["type"] === "lost" ? "VERLOREN" : "GEFUNDEN" ?>
                        </div>
                        <div class="post-meta">
                            <span class="post-user">@<?= htmlspecialchars($item["username"], ENT_QUOTES, "UTF-8") ?></span>
                            <span class="no-posts-hint" style="margin-left:8px;">
                                (<?= htmlspecialchars($item["email"], ENT_QUOTES, "UTF-8") ?>)
                            </span>
                        </div>
                    </div>

                    <h3 class="post-title"><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></h3>

                    <p class="post-info">
                        <strong>Ort:</strong> <?= htmlspecialchars($item["location"], ENT_QUOTES, "UTF-8") ?>
                        &nbsp;|&nbsp;
                        <strong>Datum:</strong> <?= date("d.m.Y", strtotime($item["event_date"])) ?>
                    </p>

                    <?php if (!empty($item["image_path"])): ?>
                        <?php $path = $item["image_path"]; $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); ?>
                        <?php if ($ext === "pdf"): ?>
                            <a class="post-file" href="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">
                                PDF ansehen
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">
                                <img class="post-image" src="<?= htmlspecialchars($path, ENT_QUOTES, "UTF-8") ?>" alt="Item Bild">
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="item-actions" style="margin-top:10px;">
                        <form method="post" class="inline-form" onsubmit="return confirm('Beitrag wirklich löschen?');">
                            <input type="hidden" name="delete_item_id" value="<?= (int)$item["id"] ?>">
                            <button type="submit" class="btn-small btn-danger">Löschen</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
                // pagination links behalten filter
                $base = "admin-items.php?" . http_build_query(["type" => $typeFilter, "sort" => $sort]) . "&page=";
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= $base . ($page - 1) ?>">Zurück</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= $base . $p ?>" class="<?= $p === $page ? "active" : "" ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $base . ($page + 1) ?>">Weiter</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
