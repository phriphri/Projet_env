<?php
/**
 * Interface de modification d'un cours existant
 */
require_once 'config/database.php';

// Vérification de la session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id_cours = $_GET['id'] ?? null;
if (!$id_cours) {
    header("Location: liste_des_cours.php");
    exit;
}

// Récupération des informations actuelles
$requete = $pdo->prepare("SELECT * FROM contenus_educatifs WHERE id = ?");
$requete->execute([$id_cours]);
$le_cours = $requete->fetch(PDO::FETCH_ASSOC);

if (!$le_cours) {
    header("Location: liste_des_cours.php");
    exit;
}

$message_erreur = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre_cours = $_POST['titre'] ?? '';
    $description_cours = $_POST['description'] ?? '';
    $theme_cours = $_POST['theme'] ?? '';
    $type_cours = $_POST['type'] ?? 'PDF';
    $source_cours = $_POST['source'] ?? '';
    
    $chemin_du_fichier = $le_cours['fichier'];
    
    // Remplacement du fichier si un nouveau est fourni
    if (isset($_FILES['fichier']) && $_FILES['fichier']['name'] !== '') {
        if ($_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $dossier_destination = 'assets/uploads/education/';
            if (!is_dir($dossier_destination)) {
                mkdir($dossier_destination, 0777, true);
            }
            
            $nom_du_fichier = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['fichier']['name']));
            $chemin_final = $dossier_destination . $nom_du_fichier;
            
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_final)) {
                if ($chemin_du_fichier && file_exists($chemin_du_fichier)) {
                    unlink($chemin_du_fichier);
                }
                $chemin_du_fichier = $chemin_final;
            } else {
                $message_erreur = "Erreur de permission : impossible d'enregistrer le fichier.";
            }
        } else {
            $err = $_FILES['fichier']['error'];
            $message_erreur = "Erreur d'upload (Code : $err). Le fichier est probablement trop volumineux.";
        }
    }
    
    // Mise à jour de la base de données
    if (empty($message_erreur)) {
        $requete_maj = $pdo->prepare("UPDATE contenus_educatifs SET titre=?, description=?, theme=?, type=?, fichier=?, source=? WHERE id=?");
        if ($requete_maj->execute([$titre_cours, $description_cours, $theme_cours, $type_cours, $chemin_du_fichier, $source_cours, $id_cours])) {
            header("Location: liste_des_cours.php");
            exit;
        } else {
            $message_erreur = "Une erreur est survenue lors de la mise à jour.";
        }
    }
}

include 'includes/header.php';
?>

<h1>Modifier le cours</h1>

<?php if($message_erreur): ?>
    <p style="color: #e74c3c; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message_erreur); ?></p>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <label style="font-weight: bold;">Titre :</label>
    <input type="text" name="titre" value="<?php echo htmlspecialchars($le_cours['titre']); ?>" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <label style="font-weight: bold;">Description :</label>
    <textarea name="description" rows="4" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"><?php echo htmlspecialchars($le_cours['description']); ?></textarea>
    
    <label style="font-weight: bold;">Thème :</label>
    <input type="text" name="theme" value="<?php echo htmlspecialchars($le_cours['theme']); ?>" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <label style="font-weight: bold;">Format du document :</label>
    <select name="type" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
        <option value="PDF" <?php if($le_cours['type']=='PDF') echo 'selected'; ?>>Document PDF</option>
        <option value="Vidéo" <?php if($le_cours['type']=='Vidéo') echo 'selected'; ?>>Fichier Vidéo (MP4)</option>
        <option value="Image" <?php if($le_cours['type']=='Image') echo 'selected'; ?>>Infographie (Image)</option>
    </select>
    
    <div style="border: 1px dashed #ccc; padding: 1rem; border-radius: 4px;">
        <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Remplacer le fichier (Fichier actuel : <?php echo $le_cours['fichier'] ? basename($le_cours['fichier']) : 'Aucun'; ?>) :</label>
        <input type="file" name="fichier">
    </div>
    
    <label style="font-weight: bold;">Source / Auteur :</label>
    <input type="text" name="source" value="<?php echo htmlspecialchars($le_cours['source']); ?>" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Sauvegarder les modifications</button>
</form>

<p style="margin-top: 1.5rem;"><a href="liste_des_cours.php" style="color: var(--text-color); font-weight: bold;">Annuler</a></p>

<?php include 'includes/footer.php'; ?>
