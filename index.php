<?php
// Header + Navigation laden
include __DIR__ . '/includes/header.php';
?>

<!-- Hero-Bereich mit Titel & Beschreibung -->
<section class="hero">
    <h1>Verloren? Gefunden! Deine Uni-Plattform für Fundsachen.</h1>
    <p class="claim">
        Melde verlorene oder gefundene Gegenstände an der FH und finde sie schneller wieder.
    </p>
</section>

<!-- Logo + (falls eingeloggt) Explore-Symbol nebeneinander -->
<div class="hero-row">
    <!-- Hauptlogo -->
    <img src="resources/images/TransparentLogo.png"
         class="img-medium hero-logo img-border-medium"
         alt="Lost and Found FHTW">

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Explore-Symbol als Button -->
        <a href="explore.php" class="explore-link">
            <img src="resources/images/splash2.png"
                 class="explore-icon"
                 alt="Zur Gegenstandsübersicht">
            <span class="explore-label">Gegenstände durchsuchen</span>
        </a>
    <?php endif; ?>
</div>

<?php
// Footer einbinden
include __DIR__ . '/includes/footer.php';
?>
