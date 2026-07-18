<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_opendata.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM open_data WHERE id = ?");
$stmt->execute([$id]);
$donnee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donnee) {
    header("Location: admin_opendata.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $categorie = $_POST['categorie'] ?? '';
    $source = $_POST['source'] ?? '';
    
    $fichier_chemin = $donnee['fichier'];
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
        $upload_dir = 'assets/uploads/opendata/';
        $nom_fichier = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['fichier']['name']));
        $chemin_final = $upload_dir . $nom_fichier;
        
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_final)) {
            if ($fichier_chemin && file_exists($fichier_chemin)) {
                unlink($fichier_chemin);
            }
            $fichier_chemin = $chemin_final;
        }
    }
    
    $update = $pdo->prepare("UPDATE open_data SET titre=?, description=?, categorie=?, fichier=?, source=? WHERE id=?");
    if ($update->execute([$titre, $description, $categorie, $fichier_chemin, $source, $id])) {
        header("Location: admin_opendata.php");
        exit;
    } else {
        $message = "Erreur lors de la modification.";
    }
}

include 'includes/header.php';
?>

<h1>Modifier le Jeu de Données</h1>
<?php if($message): ?><p style="color: red; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <input type="text" name="titre" value="<?php echo htmlspecialchars($donnee['titre']); ?>" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <textarea name="description" rows="4" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"><?php echo htmlspecialchars($donnee['description']); ?></textarea>
    
    <input type="text" name="categorie" value="<?php echo htmlspecialchars($donnee['categorie']); ?>" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <div style="border: 1px dashed #ccc; padding: 1rem; border-radius: 4px;">
        <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Remplacer le fichier (actuel: <?php echo $donnee['fichier'] ? basename($donnee['fichier']) : 'Aucun'; ?>) :</label>
        <input type="file" name="fichier">
    </div>
    
    <input type="text" name="source" value="<?php echo htmlspecialchars($donnee['source']); ?>" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Enregistrer les modifications</button>
</form>

<p style="margin-top: 1.5rem;"><a href="admin_opendata.php" style="color: var(--text-color); font-weight: bold;">&larr; Annuler</a></p>

<?php include 'includes/footer.php'; ?>
