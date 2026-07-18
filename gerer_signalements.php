<?php
/**
 * Interface d'administration pour la gestion des signalements
 */
require_once 'config/database.php';

// Vérification des droits d'administration
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Traitement de la modification du statut d'un signalement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_statut'], $_POST['id'])) {
    $id_signalement = (int)$_POST['id'];
    $statuts_autorises = ['Envoyé', 'Vu', 'Validé', 'Traité'];
    
    if (in_array($_POST['nouveau_statut'], $statuts_autorises)) {
        $requete_maj = $pdo->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
        $requete_maj->execute([$_POST['nouveau_statut'], $id_signalement]);
    }
    
    $onglet_origine = $_POST['onglet_actuel'] ?? 'envoye';
    header("Location: gerer_signalements.php?onglet=" . $onglet_origine . "&msg=maj_ok");
    exit;
}

$onglet_actuel = $_GET['onglet'] ?? 'envoye';

$filtres = [
    'envoye' => 'Envoyé',
    'vu'     => 'Vu',
    'valide' => 'Validé',
    'traite' => 'Traité'
];

$statut_filtre = $filtres[$onglet_actuel] ?? 'Envoyé';

$requete = $pdo->prepare("
    SELECT s.*, u.nom as nom_citoyen 
    FROM signalements s 
    LEFT JOIN users u ON s.citoyen_id = u.id 
    WHERE s.statut = ? 
    ORDER BY s.date_creation DESC
");
$requete->execute([$statut_filtre]);
$liste_signalements = $requete->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques pour les onglets
$compteurs = [];
foreach ($filtres as $cle => $vrai_statut) {
    $req_compteur = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE statut = ?");
    $req_compteur->execute([$vrai_statut]);
    $compteurs[$cle] = $req_compteur->fetchColumn();
}

include 'includes/header.php';
?>

<style>
.onglets { display: flex; flex-wrap: wrap; border-bottom: 2px solid #ddd; margin-bottom: 1.5rem; }
.onglets a { padding: 0.8rem 1.2rem; text-decoration: none; color: #555; font-weight: bold; border: 1px solid transparent; border-bottom: none; border-radius: 6px 6px 0 0; transition: 0.2s; }
.onglets a:hover { background: #f5f5f5; }
.onglets a.actif { background: var(--white); border-color: #ddd; color: var(--primary-color); border-bottom: 2px solid white; margin-bottom: -2px; }

.badge-count { background: #aaa; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px; vertical-align: middle; }
.badge-envoye { background: #e74c3c; }
.badge-vu { background: #3498db; }
.badge-valide { background: #f39c12; }
.badge-traite { background: #2ecc71; }

.urgence-Faible { background: #2ecc71; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; }
.urgence-Moyenne { background: #f1c40f; color: #333; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; }
.urgence-Haute { background: #e67e22; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; }
.urgence-Critique { background: #e74c3c; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; }

.btn-action { padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 0.85rem; width: 100%; transition: 0.2s; }
.btn-action:hover { opacity: 0.8; }
</style>

<h1>Administration des Signalements</h1>
<p style="color: #666; margin-bottom: 1.5rem;">Espace de modération et de suivi de l'état des signalements citoyens.</p>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'maj_ok'): ?>
    <div style="background:#eafaf1; color:#155724; padding:1rem; border-radius:4px; margin-bottom:1rem; border-left:4px solid #2ecc71;">
        Le statut du signalement a été mis à jour avec succès.
    </div>
<?php endif; ?>

<div class="onglets">
    <a href="gerer_signalements.php?onglet=envoye" class="<?php echo $onglet_actuel === 'envoye' ? 'actif' : ''; ?>">
        Nouveaux (Envoyés) <span class="badge-count badge-envoye"><?php echo $compteurs['envoye']; ?></span>
    </a>
    <a href="gerer_signalements.php?onglet=vu" class="<?php echo $onglet_actuel === 'vu' ? 'actif' : ''; ?>">
        En cours d'étude (Vus) <span class="badge-count badge-vu"><?php echo $compteurs['vu']; ?></span>
    </a>
    <a href="gerer_signalements.php?onglet=valide" class="<?php echo $onglet_actuel === 'valide' ? 'actif' : ''; ?>">
        Approuvés (Validés) <span class="badge-count badge-valide"><?php echo $compteurs['valide']; ?></span>
    </a>
    <a href="gerer_signalements.php?onglet=traite" class="<?php echo $onglet_actuel === 'traite' ? 'actif' : ''; ?>">
        Résolus (Traités) <span class="badge-count badge-traite"><?php echo $compteurs['traite']; ?></span>
    </a>
</div>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <thead>
            <tr style="background: #f8f9fa; text-align: left;">
                <th style="padding: 1rem; border-bottom: 2px solid #ddd;">Date de soumission</th>
                <th style="padding: 1rem; border-bottom: 2px solid #ddd;">Détails de l'incident</th>
                <th style="padding: 1rem; border-bottom: 2px solid #ddd;">Niveau de gravité</th>
                <th style="padding: 1rem; border-bottom: 2px solid #ddd;">Gestion du statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($liste_signalements as $le_signalement): ?>
            <tr style="border-bottom: 1px solid #eee; vertical-align: top;">
                <td style="padding: 1rem; color: #555; white-space: nowrap;">
                    <strong><?php echo date('d/m/Y H:i', strtotime($le_signalement['date_creation'])); ?></strong><br>
                    <small>Auteur : <?php echo htmlspecialchars($le_signalement['nom_citoyen'] ?? 'Anonyme'); ?></small>
                </td>
                
                <td style="padding: 1rem; max-width: 350px;">
                    <span style="background: var(--secondary-color); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;"><?php echo htmlspecialchars($le_signalement['type']); ?></span>
                    <br><strong style="color: var(--text-color); font-size: 1.05rem; display: block; margin-top: 6px;"><?php echo htmlspecialchars($le_signalement['titre']); ?></strong>
                    <p style="color: #666; font-size: 0.9rem; margin: 6px 0;"><?php echo nl2br(htmlspecialchars($le_signalement['description'])); ?></p>
                    
                    <?php if($le_signalement['photo']): ?>
                        <div style="margin-top: 10px;">
                            <a href="<?php echo htmlspecialchars($le_signalement['photo']); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($le_signalement['photo']); ?>" alt="Preuve photographique" style="height: 60px; border-radius: 4px; border: 1px solid #ccc;">
                            </a>
                        </div>
                    <?php else: ?>
                        <em style="color: #999; font-size: 0.8rem;">Aucune photographie jointe</em>
                    <?php endif; ?>
                </td>
                
                <td style="padding: 1rem;">
                    <span class="urgence-<?php echo htmlspecialchars($le_signalement['urgence']); ?>"><?php echo htmlspecialchars($le_signalement['urgence']); ?></span>
                </td>
                
                <td style="padding: 1rem;">
                    <form method="POST" style="display: flex; flex-direction: column; gap: 5px;">
                        <input type="hidden" name="id" value="<?php echo $le_signalement['id']; ?>">
                        <input type="hidden" name="onglet_actuel" value="<?php echo $onglet_actuel; ?>">
                        
                        <?php if($statut_filtre === 'Envoyé'): ?>
                            <button type="submit" name="nouveau_statut" value="Vu" class="btn-action" style="background:#3498db; color:white;">Marquer comme "Vu"</button>
                            <button type="submit" name="nouveau_statut" value="Validé" class="btn-action" style="background:#f39c12; color:white;">Valider le signalement</button>
                        
                        <?php elseif($statut_filtre === 'Vu'): ?>
                            <button type="submit" name="nouveau_statut" value="Validé" class="btn-action" style="background:#f39c12; color:white;">Valider le signalement</button>
                            <button type="submit" name="nouveau_statut" value="Traité" class="btn-action" style="background:#2ecc71; color:white;">Marquer comme "Traité"</button>
                        
                        <?php elseif($statut_filtre === 'Validé'): ?>
                            <button type="submit" name="nouveau_statut" value="Traité" class="btn-action" style="background:#2ecc71; color:white;">Marquer comme "Traité"</button>
                        
                        <?php elseif($statut_filtre === 'Traité'): ?>
                            <span style="color: #2ecc71; font-weight: bold;">Statut : Traité</span>
                            <button type="submit" name="nouveau_statut" value="Validé" class="btn-action" style="background:#e74c3c; color:white; margin-top:5px; font-size:0.75rem;">Annuler le traitement</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(count($liste_signalements) === 0): ?>
            <tr>
                <td colspan="4" style="padding: 3rem; text-align: center; color: #888;">Aucun signalement n'est recensé dans cette section.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="margin-top: 2rem;"><a href="administration.php" style="color: var(--text-color); font-weight: bold;">Retour à l'administration</a></p>

<?php include 'includes/footer.php'; ?>
