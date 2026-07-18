<?php
require_once 'config/database.php';
// La session est déjà gérée par config/database.php

// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Vérifier si l'utilisateur a déjà une demande
$stmt = $pdo->prepare("SELECT * FROM acteurs WHERE user_id = ?");
$stmt->execute([$user_id]);
$demande = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$demande) {
    $organisation = trim($_POST['organisation'] ?? '');
    $type_acteur = trim($_POST['type_acteur'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $commune = trim($_POST['commune'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $motivation = trim($_POST['motivation'] ?? '');
    $domaine_action = trim($_POST['domaine_action'] ?? '');

    if ($organisation && $type_acteur && $commune && $domaine_action) {
        try {
            $insert = $pdo->prepare("INSERT INTO acteurs (user_id, organisation, type_acteur, telephone, commune, description, motivation, domaine_action, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
            $insert->execute([$user_id, $organisation, $type_acteur, $telephone, $commune, $description, $motivation, $domaine_action]);
            $message = "Votre demande a été envoyée avec succès. Elle est en attente de validation.";
            $message_type = "success";
            
            // Rafraîchir la demande
            $stmt->execute([$user_id]);
            $demande = $stmt->fetch();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'envoi de la demande.";
            $message_type = "error";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $message_type = "error";
    }
}
?>
<?php include 'includes/header.php'; ?>

<div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h1 style="color: var(--primary-color); margin-bottom: 1.5rem; text-align: center;">Devenir Acteur Environnemental</h1>

    <?php if ($message): ?>
        <div style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; color: white; text-align: center; background-color: <?php echo $message_type === 'success' ? '#2ecc71' : '#e74c3c'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($demande): ?>
        <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
            <h2 style="margin-bottom: 1rem;">Statut de votre demande</h2>
            <p style="font-size: 1.2rem; font-weight: bold; padding: 0.5rem 1rem; display: inline-block; border-radius: 20px; 
                <?php 
                if ($demande['statut'] === 'valide') echo 'background: #2ecc71; color: white;';
                elseif ($demande['statut'] === 'rejete') echo 'background: #e74c3c; color: white;';
                else echo 'background: #f1c40f; color: #333;'; 
                ?>">
                <?php 
                if ($demande['statut'] === 'valide') echo '✅ Demande Validée ! Vous êtes maintenant un acteur.';
                elseif ($demande['statut'] === 'rejete') echo '❌ Demande Rejetée.';
                else echo '⏳ En attente de validation par l\'administration.';
                ?>
            </p>
            <?php if ($demande['statut'] === 'valide'): ?>
                <div style="margin-top: 2rem;">
                    <a href="portail_acteurs.php" style="display: inline-block; padding: 0.8rem 1.5rem; background: var(--primary-color); color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Accéder au Portail Acteurs</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">
            Rejoignez la communauté des acteurs environnementaux de Kinshasa et participez activement à la protection de notre ville.
        </p>

        <form method="POST" action="devenir_acteur.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Nom de l'acteur / organisation *</label>
                <input type="text" name="organisation" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Type d'acteur *</label>
                    <select name="type_acteur" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Sélectionnez...</option>
                        <option value="ONG">ONG</option>
                        <option value="Association">Association</option>
                        <option value="Entreprise">Entreprise</option>
                        <option value="Ecole">École / Université</option>
                        <option value="Communaute">Communauté locale</option>
                        <option value="Personnalite">Personnalité / Influenceur</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Domaine d'action *</label>
                    <select name="domaine_action" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Sélectionnez...</option>
                        <option value="Dechets">Gestion des déchets</option>
                        <option value="Education">Éducation environnementale</option>
                        <option value="Climat">Climat & Air</option>
                        <option value="Recyclage">Recyclage</option>
                        <option value="Assainissement">Assainissement & Eau</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Téléphone</label>
                    <input type="tel" name="telephone" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Commune d'activité principale *</label>
                    <input type="text" name="commune" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div>
                <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Qui sommes-nous ? (Description bref)</label>
                <textarea name="description" rows="3" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;"></textarea>
            </div>

            <div>
                <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Motivation : pourquoi voulons-nous devenir acteur ?</label>
                <textarea name="motivation" rows="3" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px;"></textarea>
            </div>

            <button type="submit" style="padding: 1rem; background: var(--primary-color); color: white; border: none; border-radius: 4px; font-weight: bold; font-size: 1.1rem; cursor: pointer; margin-top: 1rem; transition: background 0.3s;">
                Soumettre la demande
            </button>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
