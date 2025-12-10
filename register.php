<?php
// -------------------------------------------
// SESSION STARTEN
// -------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wenn man eingeloggt ist → redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// -------------------------------------------
// Registrierung LOGIK (Pseudo-Version)
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($password !== $password2) {
        $error = "Passwörter stimmen nicht überein.";
    } else {
        // TODO später: echte Speicherung in DB

        // Registrierung erfolgreich → automatisch einloggen
        $_SESSION['user_id'] = 2;  // Beispiel-ID
        $_SESSION['role'] = 'user';

        header("Location: index.php");
        exit;
    }
}

// Header laden
include __DIR__ . '/includes/header.php';
?>

<section class="login-wrapper">

    <!-- Linke Seite: Register Formular -->
    <div class="login-box">
        <h1>Registrieren</h1>

        <?php if (isset($error)): ?>
            <p class="login-error"><?= $error ?></p>
        <?php endif; ?>

        <form method="post" class="login-form">

            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required>

            <label for="password2">Passwort wiederholen</label>
            <input type="password" id="password2" name="password2" required>

            <button type="submit" class="login-btn">Account erstellen</button>
        </form>
    </div>

    <!-- Rechte Seite: Bild -->
    <div class="login-image-box">
        <img src="uploads/items/StartseiteFoto.jpg"
             alt="Registrieren Bild"
             class="login-image">
    </div>

</section>

<p class="register-link">
    Bereits einen Account?
    <a href="login.php">Hier einloggen</a>
</p>

<?php
include __DIR__ . '/includes/footer.php';
?>
