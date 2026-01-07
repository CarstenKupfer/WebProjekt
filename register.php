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
// Registrierung LOGIK (DB)
// -------------------------------------------
require_once __DIR__ . "/util/dbUtil.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST["username"] ?? "");
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if($email === "" || $username === "" || $password === "" || $password2 === ""){
        $error = "Bitte alle Felder ausfüllen.";
    }elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Bitte eine gültige E-Mail-Adresse eingeben.";
    }elseif (strlen($username) < 3 || strlen($username) > 30){
        $error = "Username muss zwischen 3 und 30 Zeichen lang sein.";
    }elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)){
        $error = "Username darf nur Buchstaben, Zahlen und _ enthalten.";
    }elseif($password !== $password2){
        $error = "Passwörter stimmen nicht überein.";
    }else {
        try{
            $db = getDb();

            // Check ob Email schon vorhanden
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            if(!$stmt){
                throw new RuntimeException($db->error);
            }

            $stmt->bind_param("s",$email);
            $stmt->execute();
            $res = $stmt->get_result();
            $emailExists = $res->fetch_assoc();
            $stmt->close();

            if($emailExists){
                $error = "Diese E-Mail ist bereits registriert.";
            }else{
                // Check ob Username schon vorhanden
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                if(!$stmt){
                    throw new RuntimeException($db->error);
                }

                $stmt->bind_param("s",$username);
                $stmt->execute();
                $res = $stmt->get_result();
                $usernameExists = $res->fetch_assoc();
                $stmt->close();
                if($usernameExists){
                    $error = "Dieser Username ist bereits registriert.";
                }else{
                // Neuen User anlegen
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
                if(!$stmt){
                    throw new RuntimeException($db->error);
                }

                $stmt->bind_param("sss",$username, $email, $passwordHash);
                $stmt->execute();
                $stmt->close();

                // Direkt einloggen nach abgeschlossener Registrierung
                $_SESSION["user_id"] = (int)$db->insert_id;
                $_SESSION["role"] = "user";
                $_SESSION["login_time"] = time();
                $_SESSION["last_activity"] = time();

                header("Location: index.php");
                exit();
                }
            }
        }catch (Throwable $e){
            $error = "DB-FEHLER: " . $e->getMessage();
        }
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
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username ?? "", ENT_QUOTES, "UTF-8") ?>">

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required>

            <label for="password2">Passwort wiederholen</label>
            <input type="password" id="password2" name="password2" required>

            <button type="submit" class="login-btn">Account erstellen</button>
        </form>
    </div>

    <!-- Rechte Seite: Bild -->
    <div class="login-image-box">
        <img src="resources/images/StartseiteFoto.jpg"
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
