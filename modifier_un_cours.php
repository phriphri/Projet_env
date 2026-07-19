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
    
    // Récupération de l'URL Cloudinary envoyée par le formulaire s'il y a un nouveau fichier
    if (!empty($_POST['cloudinary_url'])) {
        $url = $_POST['cloudinary_url'];
        
        // Vérification de sécurité de l'URL
        if (strpos($url, 'https://') === 0 && strpos($url, 'res.cloudinary.com') !== false && strpos($url, 'prqlqorh') !== false) {
            $chemin_du_fichier = $url;
        } else {
            $message_erreur = "L'URL du fichier uploadé n'est pas valide ou sécurisée.";
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

<form id="form_modif_cours" method="POST" action="" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
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
        <input type="file" id="fichier_upload" accept="image/*,video/*,.pdf,.doc,.docx">
        <input type="hidden" name="cloudinary_url" id="cloudinary_url">
        <p id="upload_status" style="font-size: 0.85rem; color: var(--primary-color); font-weight: bold; margin-top: 0.5rem; display: none;">Envoi en cours...</p>
        <p id="upload_error" style="font-size: 0.85rem; color: #e74c3c; font-weight: bold; margin-top: 0.5rem; display: none;"></p>
    </div>
    
    <label style="font-weight: bold;">Source / Auteur :</label>
    <input type="text" name="source" value="<?php echo htmlspecialchars($le_cours['source']); ?>" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" id="btn_submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Sauvegarder les modifications</button>
</form>

<p style="margin-top: 1.5rem;"><a href="liste_des_cours.php" style="color: var(--text-color); font-weight: bold;">Annuler</a></p>

<script>
document.getElementById('form_modif_cours').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('fichier_upload');
    const submitBtn = document.getElementById('btn_submit');
    const statusText = document.getElementById('upload_status');
    const errorText = document.getElementById('upload_error');
    
    errorText.style.display = 'none';
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        
        // Vérification de taille (ex: max 100MB pour vidéo)
        if (file.size > 100 * 1024 * 1024) {
            errorText.innerText = "Le fichier est trop lourd (max 100MB).";
            errorText.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.innerText = 'Envoi en cours...';
        statusText.style.display = 'block';
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('upload_preset', 'kin_la_verte_unsigned');
        formData.append('folder', 'kin-la-verte/education');
        
        try {
            const response = await fetch('https://api.cloudinary.com/v1_1/prqlqorh/auto/upload', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error("Erreur lors de l'envoi vers Cloudinary.");
            }
            
            const data = await response.json();
            document.getElementById('cloudinary_url').value = data.secure_url;
            
            // Soumission du formulaire
            this.submit();
        } catch (error) {
            console.error(error);
            errorText.innerText = "Échec de l'upload. Veuillez réessayer.";
            errorText.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.innerText = 'Sauvegarder les modifications';
            statusText.style.display = 'none';
        }
    } else {
        // Pas de fichier, soumission directe
        this.submit();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
