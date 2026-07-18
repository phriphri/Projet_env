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

<h1>Portail Acteurs - Connexion</h1>
<p>Espace réservé aux acteurs environnementaux (ONG, entreprises, institutions).</p>

<?php if($message): ?>
    <p style="color: red; font-weight: bold; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="POST" action="" style="max-width: 400px; display: flex; flex-direction: column; gap: 1rem;">
    <input type="email" name="email" placeholder="Email" required style="padding: 0.5rem;">
    <input type="password" name="mot_de_passe" placeholder="Mot de passe" required style="padding: 0.5rem;">
    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
        <input type="checkbox" name="remember_me" style="width: auto;"> Se souvenir de moi
    </label>
    <button type="submit" style="padding: 0.5rem; background: var(--secondary-color); color: white; border: none; cursor: pointer;">Connexion Acteur</button>
</form>
<p style="margin-top: 1rem;"><a href="login.php">&larr; Retour à la connexion citoyen</a></p>

<?php include 'includes/footer.php'; ?>
