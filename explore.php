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
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Was wurde gefunden/verloren? Wo? Wann?" required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Bild hinzufügen (optional)</label>
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
