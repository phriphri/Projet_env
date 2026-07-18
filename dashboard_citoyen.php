<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'citoyen') {
    header("Location: login.php");
    exit;
}
include 'includes/header.php';
?>
<h1>Dashboard Citoyen</h1>
<p>Bienvenue <?php echo htmlspecialchars($_SESSION['user_nom']); ?> ! Vous êtes connecté en tant que citoyen.</p>

<div style="margin: 2rem 0; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Agir pour mon quartier</h2>
    <p style="margin-bottom: 1rem;">Vous avez repéré une décharge sauvage ou vous souhaitez demander une collecte de déchets ?</p>
    <a href="signalement.php" style="display: inline-block; padding: 0.8rem 1.5rem; background: var(--secondary-color); color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">📍 Faire un signalement</a>
</div>

<p><a href="logout.php" style="color: var(--secondary-color);">Se déconnecter</a></p>
<?php include 'includes/footer.php'; ?>
