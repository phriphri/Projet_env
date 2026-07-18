<?php
/**
 * Interface d'administration pour la gestion des cours
 */
require_once 'config/database.php';

// Vérification de la session et des droits d'administration
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Traitement de la demande de suppression
if (isset($_GET['delete'])) {
    $id_cours = (int)$_GET['delete'];
    
    // Récupération du chemin du fichier associé pour suppression physique
    $requete_fichier = $pdo->prepare("SELECT fichier FROM contenus_educatifs WHERE id = ?");
    $requete_fichier->execute([$id_cours]);
    $le_cours = $requete_fichier->fetch();
    
    if ($le_cours && $le_cours['fichier'] && file_exists($le_cours['fichier'])) {
        unlink($le_cours['fichier']);
    }
    
    // Suppression de l'entrée dans la base de données
    $requete_suppression = $pdo->prepare("DELETE FROM contenus_educatifs WHERE id = ?");
    $requete_suppression->execute([$id_cours]);
    
    header("Location: liste_des_cours.php?msg=deleted");
    exit;
}

// Récupération de tous les cours
$requete_cours = $pdo->query("SELECT * FROM contenus_educatifs ORDER BY date_creation DESC");
$liste_cours = $requete_cours->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Gestion des Cours</h1>
    <a href="ajouter_un_cours.php" style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: bold;">Ajouter un contenu</a>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <p style="color: #27ae60; font-weight: bold; margin-top: 1rem;">Le cours a été supprimé avec succès.</p>
<?php endif; ?>

<div style="overflow-x: auto; margin-top: 1.5rem;">
    <table style="width: 100%; border-collapse: collapse; background: var(--white);">
        <thead>
            <tr style="background: var(--secondary-color); color: white; text-align: left;">
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Titre</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Thème</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Type</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Date de création</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($liste_cours as $un_cours): ?>
            <tr>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($un_cours['titre']); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($un_cours['theme']); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($un_cours['type']); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo date('d/m/Y', strtotime($un_cours['date_creation'])); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;">
                    <a href="modifier_un_cours.php?id=<?php echo $un_cours['id']; ?>" style="color: var(--secondary-color); font-weight: bold; margin-right: 10px;">Modifier</a>
                    <a href="liste_des_cours.php?delete=<?php echo $un_cours['id']; ?>" style="color: #ff4757; font-weight: bold;" onclick="return confirm('Confirmez-vous la suppression de cet élément ?');">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(count($liste_cours) === 0): ?>
            <tr>
                <td colspan="5" style="padding: 1.5rem; text-align: center; border: 1px solid #ddd; color: #777;">Aucun cours n'est disponible.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="margin-top: 2rem;"><a href="administration.php" style="color: var(--text-color); font-weight: bold;">Retour à l'administration</a></p>

<?php include 'includes/footer.php'; ?>
