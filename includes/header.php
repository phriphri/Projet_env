<?php
// La session est démarrée par database.php. Si header.php est inclus seul, on démarre ici.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Raccourci pratique pour les vues
$_user_id   = $_SESSION['user_id']   ?? null;
$_user_nom  = $_SESSION['user_nom']  ?? '';
$_user_role = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kin La Verte</title>
    <meta name="description" content="Plateforme citoyenne de suivi environnemental pour Kinshasa.">
    <meta name="theme-color" content="#2E7D32">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Nom d'utilisateur & déconnexion ─────────────────────────── */
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.3rem 0.75rem;
            background: var(--green-50);
            border: 1px solid var(--green-400);
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--green-700);
            white-space: nowrap;
            text-decoration: none;
        }
        .user-chip .role-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--green-700);
            flex-shrink: 0;
        }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.7rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            background: var(--white);
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--gray-500);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition);
        }
        .btn-logout:hover {
            background: #FEE2E2;
            border-color: #fca5a5;
            color: #dc2626;
        }
        .role-label {
            font-size: 0.68rem;
            opacity: 0.75;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none; font-weight: bold; color: var(--green-700); font-size: 1.3rem;">
            <img src="assets/logo.png" alt="Kin La Verte Logo" style="height: 42px; width: auto; max-width: 140px; object-fit: contain; vertical-align: middle;">
        </a>
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu" aria-expanded="false" aria-controls="mainNav">
            <span></span><span></span><span></span>
        </button>
        <nav id="mainNav">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="carte_interactive.php">Cartographie</a></li>
                <li><a href="cours.php">Contenu éducatif</a></li>
                <li><a href="open-data.php">Open Data</a></li>
                <li><a href="eyano.php">Eyano</a></li>
                <li><a href="contact.php">Nous Contacter</a></li>

                <?php if ($__user_id = $_SESSION['user_id'] ?? null): ?>
                    <li class="user-separator" style="list-style: none;"></li>
                    <?php $__role = $_SESSION['user_role'] ?? ''; ?>
                    <?php $__nom  = $_SESSION['user_nom']  ?? 'Utilisateur'; ?>

                    <?php if ($__role === 'acteur'): ?>
                        <li><a href="acteurs.php">Acteurs</a></li>
                        <li>
                            <a href="messagerie.php" title="Messagerie">
                                ✉️
                                <?php
                                // Nombre de messages non lus (si PDO disponible)
                                if (isset($pdo)) {
                                    try {
                                        $__stmt = $pdo->prepare("SELECT COUNT(*) FROM messages_acteur WHERE destinataire_id = ? AND lu = 0");
                                        $__stmt->execute([$__user_id]);
                                        $__non_lus = (int)$__stmt->fetchColumn();
                                        if ($__non_lus > 0) {
                                            echo '<span style="background:#dc2626;color:white;font-size:0.65rem;padding:1px 5px;border-radius:99px;font-weight:700;vertical-align:top;">' . $__non_lus . '</span>';
                                        }
                                    } catch (Exception $e) {}
                                }
                                ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($__role === 'citoyen'): ?>
                        <li>
                            <a href="devenir_acteur.php" style="font-weight:600; color: var(--green-700);">Devenir Acteur</a>
                        </li>
                    <?php endif; ?>

                    <!-- Nom de l'utilisateur + Déconnexion -->
                    <li>
                        <a href="<?php
                            if ($__role === 'admin') echo 'administration.php';
                            elseif ($__role === 'acteur') echo 'portail_acteurs.php';
                            else echo 'dashboard_citoyen.php';
                        ?>" class="user-chip" title="Mon espace">
                            <span class="role-dot"></span>
                            <?php echo htmlspecialchars($__nom); ?>
                            <span class="role-label">(<?php echo htmlspecialchars($__role); ?>)</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="btn-logout" title="Se déconnecter">
                            ⎋ Déconnexion
                        </a>
                    </li>

                <?php else: ?>
                    <li><a href="login.php" class="btn-logout" style="background: var(--green-700); color: white; border-color: var(--green-700);">Se connecter</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>

<script>
(function() {
    var btn  = document.getElementById('hamburgerBtn');
    var nav  = document.getElementById('mainNav');
    if (!btn || !nav) return;

    function closeMenu() {
        btn.classList.remove('open');
        nav.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', function() {
        var isOpen = nav.classList.toggle('open');
        btn.classList.toggle('open', isOpen);
        btn.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Fermer au clic sur un lien
    nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', closeMenu);
    });

    // Fermer au clic en dehors
    document.addEventListener('click', function(e) {
        if (!nav.contains(e.target) && !btn.contains(e.target)) {
            closeMenu();
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMenu();
    });
})();
</script>
