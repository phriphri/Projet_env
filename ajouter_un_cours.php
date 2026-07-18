<?php
/**
 * Interface d'ajout d'un nouveau cours
 */
require_once 'config/database.php';

// Vérification de la session et des droits d'administration
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message_erreur = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre_cours = $_POST['titre'] ?? '';
    $description_cours = $_POST['description'] ?? '';
    $theme_cours = $_POST['theme'] ?? '';
    $type_cours = $_POST['type'] ?? 'PDF';
    $source_cours = $_POST['source'] ?? '';
    
    $chemin_du_fichier = null;
    
    // Gestion de l'upload de fichier
    if (isset($_FILES['fichier']) && $_FILES['fichier']['name'] !== '') {
        if ($_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $dossier_destination = 'assets/uploads/education/';
            if (!is_dir($dossier_destination)) {
                mkdir($dossier_destination, 0777, true);
            }
            
            $nom_du_fichier = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['fichier']['name']));
            $chemin_final = $dossier_destination . $nom_du_fichier;
            
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_final)) {
                $chemin_du_fichier = $chemin_final;
            } else {
                $message_erreur = "Une erreur est survenue lors de l'écriture du fichier (vérifiez les permissions).";
            }
        } else {
            $err = $_FILES['fichier']['error'];
            $message_erreur = "Erreur d'upload (Code : $err). Le fichier est probablement trop volumineux.";
        }
    }
    
    // Insertion dans la base de données
    if (empty($message_erreur) && !empty($titre_cours)) {
        $requete_insertion = $pdo->prepare("INSERT INTO contenus_educatifs (titre, description, theme, type, fichier, source) VALUES (?, ?, ?, ?, ?, ?)");
        if ($requete_insertion->execute([$titre_cours, $description_cours, $theme_cours, $type_cours, $chemin_du_fichier, $source_cours])) {
            header("Location: liste_des_cours.php");
            exit;
        } else {
            $message_erreur = "Erreur lors de l'enregistrement dans la base de données.";
        }
    }
}

include 'includes/header.php';
?>

<h1>Ajouter un nouveau cours</h1>

<?php if($message_erreur): ?>
    <p style="color: #e74c3c; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message_erreur); ?></p>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <label style="font-weight: bold;">Titre :</label>
    <input type="text" name="titre" placeholder="Titre du cours" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <label style="font-weight: bold;">Description :</label>
    <textarea name="description" placeholder="Description détaillée" rows="4" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"></textarea>
    
    <label style="font-weight: bold;">Thème :</label>
    <input type="text" name="theme" placeholder="Thème principal" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <label style="font-weight: bold;">Format du document :</label>
    <select name="type" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
        <option value="PDF">Document PDF</option>
        <option value="Vidéo">Fichier Vidéo (MP4)</option>
        <option value="Image">Infographie (Image)</option>
    </select>
    
    <div style="border: 1px dashed #ccc; padding: 1rem; border-radius: 4px;">
        <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Fichier joint :</label>
        <input type="file" name="fichier">
    </div>
    
    <label style="font-weight: bold;">Source / Auteur :</label>
    <input type="text" name="source" placeholder="Auteur ou organisation source" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Enregistrer le contenu</button>
</form>

<p style="margin-top: 1.5rem;"><a href="liste_des_cours.php" style="color: var(--text-color); font-weight: bold;">Annuler et retourner à la liste</a></p>

<?php include 'includes/footer.php'; ?>
