<?php
require_once 'config/database.php';
// La session est déjà gérée par config/database.php

// Vérification de l'accès admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['acteur_id'])) {
    $action = $_POST['action'];
    $acteur_id = (int)$_POST['acteur_id'];

    if ($action === 'accepter' || $action === 'rejeter') {
        try {
            $nouveau_statut = ($action === 'accepter') ? 'valide' : 'rejete';
            
            // Démarrer une transaction
            $pdo->beginTransaction();

            // Mettre à jour le statut de la demande
            $updateActeur = $pdo->prepare("UPDATE acteurs SET statut = ? WHERE id = ?");
            $updateActeur->execute([$nouveau_statut, $acteur_id]);

            // Si accepté, changer le rôle de l'utilisateur
            if ($action === 'accepter') {
                // Récupérer le user_id de l'acteur
                $stmtUser = $pdo->prepare("SELECT user_id FROM acteurs WHERE id = ?");
                $stmtUser->execute([$acteur_id]);
                $acteur = $stmtUser->fetch();
                
                if ($acteur) {
                    $updateUser = $pdo->prepare("UPDATE users SET role = 'acteur' WHERE id = ?");
                    $updateUser->execute([$acteur['user_id']]);
                }
            }

            $pdo->commit();
            $message = "La demande a été " . ($action === 'accepter' ? "acceptée" : "rejetée") . " avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Erreur lors de la modification du statut.";
            $message_type = "error";
        }
    }
}

// Récupérer toutes les demandes (trier par statut: en_attente d'abord, puis par date)
$query = "SELECT a.*, u.nom as user_nom, u.email as user_email 
          FROM acteurs a 
          JOIN users u ON a.user_id = u.id 
          ORDER BY FIELD(a.statut, 'en_attente', 'valide', 'rejete'), a.date_demande DESC";
$demandes = $pdo->query($query)->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="color: var(--primary-color);">Gestion des Acteurs</h1>
        <a href="administration.php" style="padding: 0.5rem 1rem; background: #eee; color: #333; text-decoration: none; border-radius: 4px; font-weight: bold;">Retour à l'administration</a>
    </div>

    <?php if ($message): ?>
        <div style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; color: white; text-align: center; background-color: <?php echo $message_type === 'success' ? '#2ecc71' : '#e74c3c'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 1rem;">Organisation</th>
                    <th style="padding: 1rem;">Utilisateur</th>
                    <th style="padding: 1rem;">Type / Domaine</th>
                    <th style="padding: 1rem;">Commune</th>
                    <th style="padding: 1rem;">Date</th>
                    <th style="padding: 1rem;">Statut</th>
                    <th style="padding: 1rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <strong><?php echo htmlspecialchars($d['organisation']); ?></strong>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($d['telephone']); ?></small>
                        </td>
                        <td style="padding: 1rem;">
                            <?php echo htmlspecialchars($d['user_nom']); ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($d['user_email']); ?></small>
                        </td>
                        <td style="padding: 1rem;">
                            <?php echo htmlspecialchars($d['type_acteur']); ?>
                            <br><span style="color: var(--primary-color); font-size: 0.9em;"><?php echo htmlspecialchars($d['domaine_action']); ?></span>
                        </td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars($d['commune']); ?></td>
                        <td style="padding: 1rem;"><?php echo date('d/m/Y', strtotime($d['date_demande'])); ?></td>
                        <td style="padding: 1rem;">
                            <span style="padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.85em; font-weight: bold;
                                <?php 
                                if ($d['statut'] === 'valide') echo 'background: #d4edda; color: #155724;';
                                elseif ($d['statut'] === 'rejete') echo 'background: #f8d7da; color: #721c24;';
                                else echo 'background: #fff3cd; color: #856404;'; 
                                ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $d['statut'])); ?>
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if ($d['statut'] === 'en_attente'): ?>
                                <form method="POST" style="display: inline-flex; gap: 0.5rem;">
                                    <input type="hidden" name="acteur_id" value="<?php echo $d['id']; ?>">
                                    <button type="submit" name="action" value="accepter" style="padding: 0.4rem 0.8rem; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em;" title="Accepter">✅</button>
                                    <button type="submit" name="action" value="rejeter" style="padding: 0.4rem 0.8rem; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em;" title="Rejeter">❌</button>
                                </form>
                            <?php endif; ?>
                            <details style="margin-top: 0.5rem; cursor: pointer;">
                                <summary style="font-size: 0.85em; color: #666;">Détails</summary>
                                <div style="padding: 0.5rem; background: #f8f9fa; margin-top: 0.5rem; font-size: 0.9em; border-radius: 4px;">
                                    <strong>Description :</strong> <?php echo nl2br(htmlspecialchars($d['description'])); ?><br><br>
                                    <strong>Motivation :</strong> <?php echo nl2br(htmlspecialchars($d['motivation'])); ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($demandes)): ?>
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: #666;">Aucune demande d'acteur trouvée.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
