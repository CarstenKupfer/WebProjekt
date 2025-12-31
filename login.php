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
// LOGIN LOGIK (DB)
// -------------------------------------------
require_once __DIR__ . "/util/dbUtil.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Benutzer-Eingaben holen
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $error = "Bitte E-Mail und Passwort eingeben.";
    } else {
        try {
            $db = getDb();

            $stmt = $db->prepare("SELECT id, password_hash, role, is_blocked FROM users WHERE email = ? LIMIT 1");
            if (!$stmt) {
                throw new RuntimeException($db->error);
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = "Login fehlgeschlagen. <br> Bitte erneut versuchen.";
            } elseif ((int)$user["is_blocked"] === 1) {
                $error = "Dieser Account ist gesperrt.";
            } elseif (!password_verify($password, $user["password_hash"])) {
                $error = "Login fehlgeschlagen. <br> Bitte erneut versuchen.";
            } else {
                // Bei erfolgreichem Login
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["role"] = $user["role"];

                // Session-Timing für inaktiviät tracken setzten
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                header("Location: index.php");
                exit;
            }

        } catch (Throwable $e) {
            $error = "DB-Fehler: " . $e->getMessage();
        }
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
