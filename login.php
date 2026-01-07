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
    $login = trim($_POST['login'] ?? ''); // username ODER email
    $password = $_POST['password'] ?? '';

    if ($login === "" || $password === "") {
        $error = "Bitte Username/E-Mail und Passwort eingeben.";
    } else {
        try {
            $db = getDb();

            $stmt = $db->prepare(
                "SELECT id, username, password_hash, role, is_blocked
                 FROM users
                 WHERE username = ? OR email = ?
                 LIMIT 1"
            );
            if (!$stmt) {
                throw new RuntimeException($db->error);
            }

            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = "Username/E-Mail oder Passwort falsch.";
            } elseif ((int)$user["is_blocked"] === 1) {
                $error = "Dieser Account ist gesperrt.";
            } elseif (!password_verify($password, $user["password_hash"])) {
                $error = "Username/E-Mail oder Passwort falsch.";
            } else {
                // Erfolgreicher Login
                session_regenerate_id(true); // Best Practice (Slides)

                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];
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

    <div class="login-box">
        <h1>Login</h1>

        <?php if (isset($error)): ?>
            <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
        <?php endif; ?>

        <form method="post" class="login-form">
            <label for="login">Username oder E-Mail</label>
            <input type="text"
                   id="login"
                   name="login"
                   required
                   value="<?= htmlspecialchars($login ?? "", ENT_QUOTES, "UTF-8") ?>">

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="login-btn">Einloggen</button>
        </form>
    </div>

    <div class="login-image-box">
        <img src="resources/images/StartseiteFoto.jpg"
             alt="FH Bild"
             class="login-image">
    </div>

</section>

<p class="register-link">
    Noch keinen Account?
    <a href="register.php">Jetzt registrieren</a>
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
