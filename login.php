<?php
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mot_de_passe'] ?? '';
    $remember = isset($_POST['remember_me']);
    
    if (!empty($email) && !empty($mdp)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            if ($user['statut'] === 'suspendu') {
                $message = "Votre compte a été suspendu.";
            } else {
                login_user($user, $remember);
                
                // Sauvegarder la session sur disque avant la redirection
                session_write_close();

                if ($user['role'] === 'admin') {
                    header("Location: administration.php");
                } elseif ($user['role'] === 'acteur') {
                    header("Location: portail_acteurs.php");
                } else {
                    header("Location: dashboard_citoyen.php");
                }
                exit;
            }
        } else {
            $message = "Email ou mot de passe incorrect.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
include 'includes/header.php'; 
?>

<h1>Se connecter</h1>
<p>Connectez-vous à votre compte citoyen.</p>

<?php if($message): ?>
    <p style="color: red; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="POST" action="" style="max-width: 400px; display: flex; flex-direction: column; gap: 1rem;">
    <input type="email" name="email" placeholder="Email" required style="padding: 0.5rem;">
    <input type="password" name="mot_de_passe" placeholder="Mot de passe" required style="padding: 0.5rem;">
    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
        <input type="checkbox" name="remember_me" style="width: auto;"> Se souvenir de moi
    </label>
    <button type="submit" style="padding: 0.5rem; background: var(--primary-color); color: white; border: none; cursor: pointer;">Se connecter</button>
</form>

<p style="margin-top: 1rem;"><a href="register.php">Pas encore de compte ? S'inscrire</a></p>

<div style="margin-top: 2rem; max-width: 400px;">
    <div style="padding: 1.2rem 1.5rem; background: linear-gradient(135deg, #e8f5e9, #f1f8e9); border: 1px solid #a5d6a7; border-radius: 8px;">
        <p style="font-weight: bold; color: #2e7d32; margin-bottom: 0.8rem;">🌿 Vous êtes un acteur environnemental ?</p>
        <p style="font-size: 0.9rem; color: #555; margin-bottom: 1rem;">ONG, association, entreprise... Rejoignez le portail collaboratif ou connectez-vous à votre espace dédié.</p>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="login_acteur.php" style="display: block; text-align: center; padding: 0.6rem 1rem; background: var(--secondary-color); color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.95rem;">Se connecter comme acteur &rarr;</a>
            <a href="devenir_acteur.php" style="display: block; text-align: center; padding: 0.6rem 1rem; background: white; color: var(--secondary-color); border: 1px solid var(--secondary-color); text-decoration: none; border-radius: 4px; font-size: 0.9rem;">Faire une demande pour devenir acteur</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
