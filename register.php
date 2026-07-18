<?php
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mot_de_passe'] ?? '';
    $role = 'citoyen'; // L'inscription publique est uniquement pour les citoyens
    
    if (!empty($nom) && !empty($email) && !empty($mdp)) {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $message = "Cet email est déjà utilisé.";
        } else {
            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
            
            $insert = $pdo->prepare("INSERT INTO users (nom, email, mot_de_passe, role, statut) VALUES (?, ?, ?, ?, 'actif')");
            if ($insert->execute([$nom, $email, $mdp_hash, $role])) {
                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
include 'includes/header.php'; 
?>

<h1>Inscription</h1>
<p>Créez votre compte pour rejoindre la plateforme.</p>

<?php if($message): ?>
    <p style="color: var(--primary-color); font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="POST" action="" style="max-width: 400px; display: flex; flex-direction: column; gap: 1rem;">
    <input type="text" name="nom" placeholder="Nom complet" required style="padding: 0.5rem;">
    <input type="email" name="email" placeholder="Email" required style="padding: 0.5rem;">
    <input type="password" name="mot_de_passe" placeholder="Mot de passe" required style="padding: 0.5rem;">
    <button type="submit" style="padding: 0.5rem; background: var(--primary-color); color: white; border: none; cursor: pointer;">S'inscrire</button>
</form>
<p style="margin-top: 1rem;"><a href="login.php">Déjà un compte ? Se connecter</a></p>

<?php include 'includes/footer.php'; ?>
