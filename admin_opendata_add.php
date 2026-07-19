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
    
    $fichier_chemin = null;
    
    // Récupération de l'URL Cloudinary envoyée par le formulaire
    if (!empty($_POST['cloudinary_url'])) {
        $url = $_POST['cloudinary_url'];
        
        // Vérification de sécurité de l'URL
        if (strpos($url, 'https://') === 0 && strpos($url, 'res.cloudinary.com') !== false && strpos($url, 'prqlqorh') !== false) {
            $fichier_chemin = $url;
        } else {
            $message = "L'URL du fichier uploadé n'est pas valide ou sécurisée.";
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

<form id="form_ajout_opendata" method="POST" action="" style="max-width: 600px; display: flex; flex-direction: column; gap: 1rem; background: var(--white); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <input type="text" name="titre" placeholder="Titre du jeu de données" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <textarea name="description" placeholder="Description..." rows="4" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"></textarea>
    
    <input type="text" name="categorie" placeholder="Catégorie (ex: Qualité de l'air, Météo...)" required style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <div style="border: 1px dashed #ccc; padding: 1rem; border-radius: 4px;">
        <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Uploader le fichier (CSV, JSON, XLS, etc.) :</label>
        <input type="file" id="fichier_upload">
        <input type="hidden" name="cloudinary_url" id="cloudinary_url">
        <p id="upload_status" style="font-size: 0.85rem; color: var(--primary-color); font-weight: bold; margin-top: 0.5rem; display: none;">Envoi en cours...</p>
        <p id="upload_error" style="font-size: 0.85rem; color: #e74c3c; font-weight: bold; margin-top: 0.5rem; display: none;"></p>
    </div>
    
    <input type="text" name="source" placeholder="Source de la donnée (ex: Institut Météorologique)" style="padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
    
    <button type="submit" id="btn_submit" style="padding: 0.8rem; background: var(--primary-color); color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 1rem; margin-top: 1rem;">Ajouter la donnée</button>
</form>

<p style="margin-top: 1.5rem;"><a href="admin_opendata.php" style="color: var(--text-color); font-weight: bold;">&larr; Annuler</a></p>

<script>
document.getElementById('form_ajout_opendata').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('fichier_upload');
    const submitBtn = document.getElementById('btn_submit');
    const statusText = document.getElementById('upload_status');
    const errorText = document.getElementById('upload_error');
    
    errorText.style.display = 'none';
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        
        // Vérification de taille (ex: max 100MB)
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
        formData.append('folder', 'kin-la-verte/opendata');
        
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
            submitBtn.innerText = 'Ajouter la donnée';
            statusText.style.display = 'none';
        }
    } else {
        // Pas de fichier, soumission directe
        this.submit();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
