<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'includes/header.php';
?>
<h1>Administration</h1>
<p>Bienvenue <?php echo htmlspecialchars($_SESSION['user_nom']); ?> ! Vous êtes connecté en tant qu'administrateur.</p>

<ul style="list-style: none; margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <li>
        <a href="liste_des_cours.php" style="display: block; padding: 1.5rem; background: var(--white); border: 1px solid #ccc; border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📚</div>
            Gérer l'Éducation
        </a>
    </li>
    <li>
        <a href="admin_opendata.php" style="display: block; padding: 1.5rem; background: var(--white); border: 1px solid #ccc; border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
            Gérer l'Open Data
        </a>
    </li>
    <li>
        <a href="gerer_signalements.php" style="display: block; padding: 1.5rem; background: var(--white); border: 1px solid #ccc; border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📍</div>
            Gérer les Signalements
        </a>
    </li>
    <li>
        <a href="admin_acteurs.php" style="display: block; padding: 1.5rem; background: var(--white); border: 1px solid #ccc; border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
            Gérer les Acteurs
        </a>
    </li>
</ul>

<?php include 'includes/footer.php'; ?>
