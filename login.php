<?php
// -------------------------------------------
// SESSION STARTEN
// -------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wenn man bereits eingeloggt ist dann direkt zur Startseite
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// -------------------------------------------
// LOGIN LOGIK (Pseudo User)
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Benutzer-Eingaben holen
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Pseudo-User für Testzwecke
    $pseudoEmail = "test@fh.at";
    $pseudoPassword = "1234";

    // Check ob Daten korrekt
    if ($email === $pseudoEmail && $password === $pseudoPassword) {

        // Session setzen → Benutzer ist eingeloggt
        $_SESSION['user_id'] = 1;

        // Session setzten wenn eingeloggt = user
        $_SESSION['role'] = 'user';

        // Weiterleitung zur Startseite
        header("Location: index.php");
        exit;

    } else {
        $error = "Login fehlgeschlagen. Bitte versuche es erneut.";
    }
}

// Header laden
include __DIR__ . '/includes/header.php';
?>

<section class="login-wrapper">

    <!-- Linke Seite: Login Formular -->
    <div class="login-box">
        <h1>Login</h1>

        <?php if (isset($error)): ?>
            <p class="login-error"><?= $error ?></p>
        <?php endif; ?>

        <form method="post" class="login-form">
            <label for="email">E-Mail</label>
            <input type="text" id="email" name="email" required>

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="login-btn">Einloggen</button>
        </form>
    </div>

    <!-- Rechte Seite: Bild -->
    <div class="login-image-box">
        <img src="uploads/items/StartseiteFoto.jpg"
             alt="FH Bild"
             class="login-image">
    </div>

</section>

<p class="register-link">
    Noch keinen Account?
    <a href="register.php">Jetzt registrieren</a>
</p>

<?php
// Footer laden
include __DIR__ . '/includes/footer.php';
?>
