<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT fichier FROM open_data WHERE id = ?");
    $stmt->execute([$id]);
    $donnee = $stmt->fetch();
    
    if ($donnee && $donnee['fichier'] && file_exists($donnee['fichier'])) {
        unlink($donnee['fichier']);
    }
    
    $del = $pdo->prepare("DELETE FROM open_data WHERE id = ?");
    $del->execute([$id]);
    header("Location: admin_opendata.php?msg=deleted");
    exit;
}

$stmt = $pdo->query("SELECT * FROM open_data ORDER BY date_creation DESC");
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Gestion Open Data</h1>
    <a href="admin_opendata_add.php" style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: bold;">+ Ajouter un jeu de données</a>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <p style="color: red; font-weight: bold; margin-top: 1rem;">Jeu de données supprimé.</p>
<?php endif; ?>

<div style="overflow-x: auto; margin-top: 1.5rem;">
    <table style="width: 100%; border-collapse: collapse; background: var(--white);">
        <thead>
            <tr style="background: var(--secondary-color); color: white; text-align: left;">
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Titre</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Catégorie</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Date</th>
                <th style="padding: 0.8rem; border: 1px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($datasets as $d): ?>
            <tr>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($d['titre']); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($d['categorie']); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;"><?php echo date('d/m/Y', strtotime($d['date_creation'])); ?></td>
                <td style="padding: 0.8rem; border: 1px solid #ddd;">
                    <a href="admin_opendata_edit.php?id=<?php echo $d['id']; ?>" style="color: var(--secondary-color); font-weight: bold; margin-right: 10px;">Modifier</a>
                    <a href="admin_opendata.php?delete=<?php echo $d['id']; ?>" style="color: #ff4757; font-weight: bold;" onclick="return confirm('Sûr de vouloir supprimer ?');">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(count($datasets) === 0): ?>
            <tr>
                <td colspan="4" style="padding: 1.5rem; text-align: center; border: 1px solid #ddd; color: #777;">Aucun jeu de données.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="margin-top: 2rem;"><a href="administration.php" style="color: var(--text-color); font-weight: bold;">&larr; Retour à l'administration</a></p>

<?php include 'includes/footer.php'; ?>
