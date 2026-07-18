<?php
/**
 * SESSION DIAGNOSTIC — supprimer ce fichier en production
 * Accès : http://localhost/projet_env_vibe/session_test.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Test Session</title>
<style>body{font-family:monospace;padding:2rem;background:#1a1a1a;color:#eee;} .ok{color:#2ecc71;} .ko{color:#e74c3c;} .box{background:#2a2a2a;padding:1.5rem;border-radius:8px;margin-bottom:1rem;}</style>
</head>
<body>
<h2>🔍 Diagnostic de Session PHP</h2>

<div class="box">
    <h3>📋 Informations Serveur</h3>
    <p>PHP Version : <?php echo phpversion(); ?></p>
    <p>session.save_handler : <strong><?php echo ini_get('session.save_handler'); ?></strong></p>
    <p>session.save_path : <strong><?php echo ini_get('session.save_path') ?: '(par défaut)'; ?></strong></p>
    <p>session.gc_maxlifetime : <strong><?php echo ini_get('session.gc_maxlifetime'); ?> secondes</strong></p>
    <p>session.cookie_lifetime : <strong><?php echo ini_get('session.cookie_lifetime'); ?></strong> (0 = jusqu'à fermeture du navigateur)</p>
</div>

<div class="box">
    <h3>🔑 État de la Session</h3>
    <p>Statut : 
        <?php if(session_status() === PHP_SESSION_ACTIVE): ?>
            <span class="ok">✔ SESSION ACTIVE</span>
        <?php else: ?>
            <span class="ko">✘ SESSION INACTIVE</span>
        <?php endif; ?>
    </p>
    <p>Session ID : <strong><?php echo session_id(); ?></strong></p>
    <p>Session Name : <strong><?php echo session_name(); ?></strong></p>
</div>

<div class="box">
    <h3>👤 Variables de session ($_SESSION)</h3>
    <?php if (empty($_SESSION)): ?>
        <p class="ko">⚠ La session est vide. Vous n'êtes pas connecté.</p>
    <?php else: ?>
        <p class="ok">✔ <?php echo count($_SESSION); ?> variable(s) de session trouvée(s) :</p>
        <ul>
        <?php foreach($_SESSION as $key => $val): ?>
            <li><strong><?php echo htmlspecialchars($key); ?></strong> = <?php echo htmlspecialchars((string)$val); ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="box">
    <h3>🍪 Cookie de session</h3>
    <?php if(isset($_COOKIE[session_name()])): ?>
        <p class="ok">✔ Cookie "<?php echo session_name(); ?>" présent dans le navigateur.</p>
        <p>Valeur : <?php echo htmlspecialchars($_COOKIE[session_name()]); ?></p>
    <?php else: ?>
        <p class="ko">✘ Aucun cookie de session trouvé dans le navigateur.</p>
    <?php endif; ?>
</div>

<p style="margin-top:2rem;font-size:0.9rem;color:#888;">⚠ Supprimer ce fichier avant la mise en production.</p>
</body>
</html>
