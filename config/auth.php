<?php
/**
 * config/auth.php — Système d'Authentification Persistant pour Kin La Verte
 */

// La session est déjà démarrée dans database.php — guard de sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Récupère l'utilisateur connecté ou tente de le reconnecter via cookie.
 * Ne détruit JAMAIS la session si l'utilisateur est déjà connecté.
 */
function current_user() {
    global $pdo;

    // ── Cas 1 : Session active (chemin normal, très rapide) ────────────────
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
        return [
            'id'   => (int)$_SESSION['user_id'],
            'nom'  => $_SESSION['user_nom'] ?? '',
            'role' => $_SESSION['user_role'],
        ];
    }

    // ── Cas 2 : Pas de session mais cookie "remember_me" présent ──────────
    if (!isset($_COOKIE['remember_me'])) {
        return null; // Pas connecté, on s'arrête là
    }

    $parts = explode(':', $_COOKIE['remember_me'], 2);
    if (count($parts) !== 2) {
        _clear_remember_cookie(); // Cookie mal formé
        return null;
    }

    [$selector, $validator] = $parts;

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW() LIMIT 1"
        );
        $stmt->execute([$selector]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // La table n'existe pas encore (avant migration) — on ignore
        return null;
    }

    if (!$token || !hash_equals($token['hashed_validator'], hash('sha256', $validator))) {
        _clear_remember_cookie(); // Token invalide ou expiré
        return null;
    }

    // Récupérer les infos de l'utilisateur (statut actif uniquement)
    $stmtUser = $pdo->prepare(
        "SELECT id, nom, role FROM users WHERE id = ? LIMIT 1"
    );
    $stmtUser->execute([$token['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        _clear_remember_cookie();
        return null;
    }

    // ✅ Reconnexion automatique réussie — recharger la session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_nom']  = $user['nom'];
    $_SESSION['user_role'] = $user['role'];

    session_regenerate_id(true);

    // Rotation du token (sécurité : invalider l'ancien)
    $new_validator    = bin2hex(random_bytes(32));
    $hashed_validator = hash('sha256', $new_validator);
    $expiry           = date('Y-m-d H:i:s', time() + 30 * 86400);

    $pdo->prepare(
        "UPDATE user_tokens SET hashed_validator = ?, expiry = ? WHERE id = ?"
    )->execute([$hashed_validator, $expiry, $token['id']]);

    $secure = _is_https();
    setcookie('remember_me', "$selector:$new_validator",
        time() + 30 * 86400, '/', '', $secure, true);

    return $user;
}

/**
 * Redirige vers login.php si l'utilisateur n'est pas connecté.
 */
function require_login() {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirige vers l'accueil si l'utilisateur n'a pas le bon rôle.
 * Accepte un rôle ou un tableau de rôles.
 */
function require_role($roles) {
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed)) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Connexion d'un utilisateur + option "Se souvenir de moi".
 */
function login_user(array $user, bool $remember = false): void {
    global $pdo;

    // Regen avant d'écrire les données (protection fixation de session)
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_nom']  = $user['nom'];
    $_SESSION['user_role'] = $user['role'];

    if ($remember) {
        $selector         = bin2hex(random_bytes(12));
        $validator        = bin2hex(random_bytes(32));
        $hashed_validator = hash('sha256', $validator);
        $expiry           = date('Y-m-d H:i:s', time() + 30 * 86400);

        try {
            $pdo->prepare(
                "INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry)
                 VALUES (?, ?, ?, ?)"
            )->execute([$user['id'], $selector, $hashed_validator, $expiry]);

            $secure = _is_https();
            setcookie('remember_me', "$selector:$validator",
                time() + 30 * 86400, '/', '', $secure, true);
        } catch (Exception $e) {
            // Table absente — fonctionnement sans persistance
        }
    }
}

/**
 * Déconnexion complète : session + cookie + token BDD.
 */
function logout_user(): void {
    global $pdo;

    // Supprimer le token persistant en BDD
    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me'], 2);
        if (count($parts) === 2) {
            try {
                $pdo->prepare(
                    "DELETE FROM user_tokens WHERE selector = ?"
                )->execute([$parts[0]]);
            } catch (Exception $e) { /* table absente */ }
        }
        _clear_remember_cookie();
    }

    // Vider et détruire la session PHP
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// ── Helpers privés ────────────────────────────────────────────────────────────
function _clear_remember_cookie(): void {
    $secure = _is_https();
    setcookie('remember_me', '', time() - 3600, '/', '', $secure, true);
    unset($_COOKIE['remember_me']);
}

function _is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

// ── CSRF Protection ───────────────────────────────────────────────────────────

/**
 * Génère (ou récupère) un token CSRF en session.
 * À inclure dans les formulaires : <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF soumis. Die avec 403 si invalide.
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        die('Requête invalide (CSRF). Veuillez recharger la page et réessayer.');
    }
}

