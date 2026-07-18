<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $categorie = $_POST['categorie'] ?? '';
    $source = $_POST['source'] ?? '';
    
    // Gérer l'upload
    $fichier_chemin = null;
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
        $upload_dir = 'assets/uploads/opendata/';
        $nom_fichier = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['fichier']['name']));
        $chemin_final = $upload_dir . $nom_fichier;
        
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_final)) {
            $fichier_chemin = $chemin_final;
        } else {
            $message = "Erreur lors de l'upload du fichier.";
        }
    }
    
    if (empty($message) && !empty($titre)) {
        $stmt = $pdo->prepare("INSERT INTO open_data (titre, description, categorie, fichier, source) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$titre, $description, $categorie, $fichier_chemin, $source])) {
            header("Location: admin_opendata.php");
            exit;
        } else {
            $message = "Erreur lors de l'enregistrement dans la base.";
        }
    }
}

include 'includes/header.php';
?>

<h1>Ajouter un Jeu de Données (Open Data)</h1>
<?php if($message): ?><p style="color: red; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <input type="text" name="titre" placeholder="Titre du jeu de données" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <textarea name="description" placeholder="Description..." rows="4" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"></textarea>
    
    <input type="text" name="categorie" placeholder="Catégorie (ex: Qualité de l'air, Météo...)" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <div style="border: 1px dashed #ccc; padding: 1rem; border-radius: 4px;">
        <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Uploader le fichier (CSV, JSON, XLS, etc.) :</label>
        <input type="file" name="fichier">
    </div>
    
    <input type="text" name="source" placeholder="Source de la donnée (ex: Institut Météorologique)" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Ajouter la donnée</button>
</form>

<p style="margin-top: 1.5rem;"><a href="admin_opendata.php" style="color: var(--text-color); font-weight: bold;">&larr; Annuler</a></p>

<?php include 'includes/footer.php'; ?>
