<?php
/**
 * messagerie.php — Messagerie Privée Simplifiée entre Acteurs Validés
 */
require_once 'config/database.php';

// Restriction d'accès : Seuls les acteurs validés peuvent accéder à la messagerie
require_login();
$user = current_user();
if ($user['role'] !== 'acteur') {
    header('Location: index.php');
    exit;
}

$user_id = $user['id'];
$message_info = '';
$error_info = '';

// ── 1. Gérer l'envoi de message ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    $destinataire_id = (int)($_POST['destinataire_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if ($destinataire_id === $user_id) {
        $error_info = "Vous ne pouvez pas vous envoyer de message à vous-même.";
    } elseif ($destinataire_id && $message !== '') {
        // Vérifier que le destinataire existe et est validé
        $stmtCheck = $pdo->prepare("SELECT u.id FROM users u JOIN acteurs a ON u.id = a.user_id WHERE u.id = ? AND a.statut = 'valide'");
        $stmtCheck->execute([$destinataire_id]);
        if ($stmtCheck->fetch()) {
            $insert = $pdo->prepare("INSERT INTO messages_acteur (expediteur_id, destinataire_id, message, lu) VALUES (?, ?, ?, 0)");
            $insert->execute([$user_id, $destinataire_id, $message]);
            
            // Rediriger pour éviter le renvoi au rafraîchissement
            header("Location: messagerie.php?to=" . $destinataire_id);
            exit;
        } else {
            $error_info = "Le destinataire est invalide ou n'est pas un acteur validé.";
        }
    }
}

// ── 2. Récupérer l'interlocuteur actif (si ?to=ID) ───────────────────────────
$active_to_id = isset($_GET['to']) ? (int)$_GET['to'] : null;
$active_interlocuteur = null;

if ($active_to_id && $active_to_id !== $user_id) {
    $stmtDest = $pdo->prepare("
        SELECT u.id, u.nom, a.organisation 
        FROM users u 
        JOIN acteurs a ON u.id = a.user_id 
        WHERE u.id = ? AND a.statut = 'valide'
    ");
    $stmtDest->execute([$active_to_id]);
    $active_interlocuteur = $stmtDest->fetch(PDO::FETCH_ASSOC);

    // Si trouvé, marquer les messages reçus de cet utilisateur comme lus
    if ($active_interlocuteur) {
        $updateLu = $pdo->prepare("UPDATE messages_acteur SET lu = 1 WHERE destinataire_id = ? AND expediteur_id = ?");
        $updateLu->execute([$user_id, $active_to_id]);
    }
}

// ── 3. Récupérer la liste des conversations (utilisateurs avec qui on a échangé) ──
$stmtConversations = $pdo->prepare("
    SELECT DISTINCT 
        CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END as interlocuteur_id,
        u.nom as interlocuteur_nom,
        a.organisation as interlocuteur_orga,
        (SELECT message FROM messages_acteur 
         WHERE (expediteur_id = ? AND destinataire_id = interlocuteur_id) 
            OR (expediteur_id = interlocuteur_id AND destinataire_id = ?) 
         ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
        (SELECT date_envoi FROM messages_acteur 
         WHERE (expediteur_id = ? AND destinataire_id = interlocuteur_id) 
            OR (expediteur_id = interlocuteur_id AND destinataire_id = ?) 
         ORDER BY date_envoi DESC LIMIT 1) as date_dernier,
        (SELECT COUNT(*) FROM messages_acteur 
         WHERE destinataire_id = ? AND expediteur_id = interlocuteur_id AND lu = 0) as non_lus
    FROM messages_acteur m
    JOIN users u ON u.id = CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END
    JOIN acteurs a ON u.id = a.user_id
    WHERE (m.expediteur_id = ? OR m.destinataire_id = ?) 
      AND a.statut = 'valide'
      AND m.expediteur_id != m.destinataire_id
    ORDER BY date_dernier DESC
");
$stmtConversations->execute([
    $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
]);
$conversations = $stmtConversations->fetchAll(PDO::FETCH_ASSOC);

// Liste de tous les autres acteurs validés pour pouvoir démarrer une nouvelle conversation
$stmtTousActeurs = $pdo->prepare("
    SELECT u.id, u.nom, a.organisation 
    FROM acteurs a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.statut = 'valide' AND u.id != ? 
    ORDER BY a.organisation ASC
");
$stmtTousActeurs->execute([$user_id]);
$tous_acteurs = $stmtTousActeurs->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les messages de la conversation active
$messages = [];
if ($active_interlocuteur) {
    $stmtMessages = $pdo->prepare("
        SELECT * FROM messages_acteur 
        WHERE (expediteur_id = ? AND destinataire_id = ?) 
           OR (expediteur_id = ? AND destinataire_id = ?) 
        ORDER BY date_envoi ASC
    ");
    $stmtMessages->execute([$user_id, $active_to_id, $active_to_id, $user_id]);
    $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div style="max-width: 1100px; margin: 1.5rem auto; padding: 0 1rem;">
    
    <?php if ($error_info): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_info); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; background: var(--white); border: 1px solid var(--gray-100); border-radius: var(--radius-lg); overflow: hidden; height: 600px; box-shadow: var(--shadow-sm);">
        
        <!-- ── Colonne Gauche : Liste des conversations ───────────────────── -->
        <div style="border-right: 1px solid var(--gray-100); display: flex; flex-direction: column; background: #fdfdfd;">
            
            <div style="padding: 1rem; border-bottom: 1px solid var(--gray-100); background: var(--white);">
                <h2 style="font-size: 1.1rem; margin-bottom: 0.75rem; color: var(--green-700);">💬 Conversations</h2>
                
                <!-- Démarrer une nouvelle discussion -->
                <form action="messagerie.php" method="GET" style="display: flex; gap: 0.25rem;">
                    <select name="to" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 0.4rem; height: auto;">
                        <option value="">+ Écrire à un acteur...</option>
                        <?php foreach ($tous_acteurs as $act): ?>
                            <option value="<?php echo $act['id']; ?>" <?php echo $active_to_id === (int)$act['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($act['organisation'] ?: $act['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Liste des discussions existantes -->
            <div style="flex: 1; overflow-y: auto;">
                <?php if (empty($conversations)): ?>
                    <p style="text-align: center; color: var(--gray-400); font-size: 0.85rem; padding: 2rem;">Aucune conversation en cours.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <?php $est_actif = ($active_to_id === (int)$conv['interlocuteur_id']); ?>
                        <a href="messagerie.php?to=<?php echo $conv['interlocuteur_id']; ?>" 
                           style="display: block; padding: 0.85rem 1rem; text-decoration: none; border-bottom: 1px solid var(--gray-50); transition: background var(--transition);
                                  background: <?php echo $est_actif ? 'var(--green-50)' : 'transparent'; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.15rem;">
                                <strong style="font-size: 0.875rem; color: <?php echo $est_actif ? 'var(--green-700)' : 'var(--gray-900)'; ?>;">
                                    <?php echo htmlspecialchars($conv['interlocuteur_orga'] ?: $conv['interlocuteur_nom']); ?>
                                </strong>
                                <small style="font-size: 0.7rem; color: var(--gray-400);">
                                    <?php echo date('d/m H:i', strtotime($conv['date_dernier'])); ?>
                                </small>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <p style="font-size: 0.78rem; color: var(--gray-500); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                                    <?php echo htmlspecialchars($conv['dernier_message']); ?>
                                </p>
                                <?php if ($conv['non_lus'] > 0): ?>
                                    <span style="background: var(--blue-500); color: white; font-size: 0.7rem; font-weight: bold; padding: 1px 6px; border-radius: 10px;">
                                        <?php echo $conv['non_lus']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Colonne Droite : Fil de discussion actif ────────────────────── -->
        <div style="display: flex; flex-direction: column; background: var(--white);">
            <?php if ($active_interlocuteur): ?>
                
                <!-- En-tête de la conversation -->
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; background: #fafafa;">
                    <div>
                        <strong style="color: var(--green-700); font-size: 1rem;">
                            <?php echo htmlspecialchars($active_interlocuteur['organisation'] ?: $active_interlocuteur['nom']); ?>
                        </strong>
                        <p style="margin: 0; font-size: 0.78rem; color: var(--gray-400);">Acteur environnemental validé</p>
                    </div>
                    <a href="profil_acteur.php?id=<?php 
                        // Récupérer l'acteur ID de cet interlocuteur
                        $stmtActId = $pdo->prepare("SELECT id FROM acteurs WHERE user_id = ?");
                        $stmtActId->execute([$active_interlocuteur['id']]);
                        echo $stmtActId->fetchColumn();
                     ?>" style="font-size: 0.85rem; font-weight: 500; text-decoration: none; color: var(--blue-500);">Voir profil</a>
                </div>

                <!-- Zone des messages scrollable -->
                <div id="messages-container" style="flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; background: #fdfdfd;">
                    <?php if (empty($messages)): ?>
                        <p style="text-align: center; color: var(--gray-400); font-size: 0.85rem; margin: auto;">Aucun message dans cette discussion. Envoyez le premier message !</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php $est_moi = ($msg['expediteur_id'] === $user_id); ?>
                            <div style="display: flex; flex-direction: column; align-items: <?php echo $est_moi ? 'flex-end' : 'flex-start'; ?>;">
                                <div style="max-width: 70%; padding: 0.65rem 0.95rem; border-radius: var(--radius-md); font-size: 0.875rem; line-height: 1.45;
                                            background: <?php echo $est_moi ? 'var(--green-700)' : 'var(--gray-100)'; ?>;
                                            color: <?php echo $est_moi ? 'white' : 'var(--gray-900)'; ?>;
                                            border-top-right-radius: <?php echo $est_moi ? '2px' : 'var(--radius-md)'; ?>;
                                            border-top-left-radius: <?php echo $est_moi ? 'var(--radius-md)' : '2px'; ?>;">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <span style="font-size: 0.68rem; color: var(--gray-400); margin-top: 0.2rem; padding: 0 0.2rem;">
                                    <?php echo date('d/m H:i', strtotime($msg['date_envoi'])); ?>
                                    <?php if ($est_moi): ?>
                                        • <?php echo $msg['lu'] ? 'Lu' : 'Envoyé'; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Formulaire d'envoi -->
                <div style="padding: 1rem; border-top: 1px solid var(--gray-100); background: var(--white);">
                    <form method="POST" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                        <input type="hidden" name="action" value="envoyer">
                        <input type="hidden" name="destinataire_id" value="<?php echo $active_interlocuteur['id']; ?>">
                        <textarea name="message" placeholder="Votre message..." required rows="1" 
                                  style="flex: 1; resize: none; min-height: 40px; max-height: 120px; font-size: 0.875rem; padding: 0.55rem 0.8rem;"
                                  oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"></textarea>
                        <button type="submit" class="btn btn-primary" style="height: 40px; padding: 0 1.25rem;">Envoyer</button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Aucun interlocuteur sélectionné -->
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; color: var(--gray-400); padding: 2rem; text-align: center;">
                    <span style="font-size: 3.5rem; margin-bottom: 1rem;">✉️</span>
                    <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">Messagerie Privée</h3>
                    <p style="font-size: 0.85rem; max-width: 320px; margin: 0;">Sélectionnez une discussion ou écrivez à un nouvel acteur pour démarrer la collaboration.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Scroll automatique vers le bas de la discussion
const container = document.getElementById('messages-container');
if (container) {
    container.scrollTop = container.scrollHeight;
}
</script>

<?php include 'includes/footer.php'; ?>
