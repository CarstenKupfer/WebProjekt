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
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if($email === "" || $password === "" || $password2 === ""){
        $error = "Bitte E-Mail und Passwort eingeben.";
    }elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Bitte eine gültige E-Mail-Adresse eingeben";
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
            $existing = $res->fetch_assoc();
            $stmt->close();

            if($existing){
                $error = "Diese E-Mail ist bereits registriert.";
            }else{
                // Neuen User anlegen
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $name = strstr($email, "@", true);

                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
                if(!$stmt){
                    throw new RuntimeException($db->error);
                }

                $stmt->bind_param("sss",$name, $email, $passwordHash);
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
