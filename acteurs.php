<?php
/**
 * acteurs.php — Annuaire et Réseau simple des Acteurs Environnementaux
 */
require_once 'config/database.php';

// Restriction d'accès : Seuls les acteurs validés et les admins
require_login();
$user = current_user();
if ($user['role'] !== 'acteur') {
    header('Location: index.php');
    exit;
}

// Récupérer les filtres et recherche
$search = trim($_GET['search'] ?? '');
$filter_commune = trim($_GET['commune'] ?? '');
$filter_type = trim($_GET['type_acteur'] ?? '');

$where_clauses = ["statut = 'valide'"];
$params = [];

if ($search) {
    $where_clauses[] = "organisation LIKE ?";
    $params[] = "%$search%";
}
if ($filter_commune) {
    $where_clauses[] = "commune = ?";
    $params[] = $filter_commune;
}
if ($filter_type) {
    $where_clauses[] = "type_acteur = ?";
    $params[] = $filter_type;
}

$where_sql = implode(' AND ', $where_clauses);
$query = "SELECT * FROM acteurs WHERE $where_sql ORDER BY organisation ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$acteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les listes pour les filtres de recherche
$communes = $pdo->query("SELECT DISTINCT commune FROM acteurs WHERE statut = 'valide' AND commune IS NOT NULL AND commune != '' ORDER BY commune ASC")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT type_acteur FROM acteurs WHERE statut = 'valide' ORDER BY type_acteur ASC")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--gray-100); padding-bottom: 1rem;">
        <h1 style="color: var(--primary-color); margin: 0;">👥 Réseau des Acteurs</h1>
        <a href="portail_acteurs.php" class="btn btn-secondary btn-sm">💬 Fil d'actualité</a>
    </div>

    <!-- Barre de recherche et filtres -->
    <form method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; background: var(--white); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--gray-100); margin-bottom: 1.5rem;">
        <input type="text" name="search" placeholder="Rechercher par nom..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 2; min-width: 200px;">
        
        <select name="commune" style="flex: 1; min-width: 150px;">
            <option value="">Toutes les communes</option>
            <?php foreach ($communes as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filter_commune === $c ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="type_acteur" style="flex: 1; min-width: 150px;">
            <option value="">Tous les types</option>
            <?php foreach ($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $filter_type === $t ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($search || $filter_commune || $filter_type): ?>
            <a href="acteurs.php" class="btn btn-secondary" style="display: inline-flex; align-items: center;">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <!-- Liste des Acteurs -->
    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-100); overflow: hidden; box-shadow: var(--shadow-sm);">
        <?php if (empty($acteurs)): ?>
            <p style="text-align: center; padding: 3rem; color: var(--gray-500); margin: 0;">Aucun acteur ne correspond à votre recherche.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($acteurs as $index => $act): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.5rem; <?php echo $index > 0 ? 'border-top: 1px solid var(--gray-100);' : ''; ?> gap: 1rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <h3 style="margin: 0; font-size: 1.05rem;">
                                    <a href="profil_acteur.php?id=<?php echo $act['id']; ?>" style="color: var(--primary-color); font-weight: bold; text-decoration: none;">
                                        <?php echo htmlspecialchars($act['organisation']); ?>
                                    </a>
                                </h3>
                                <span style="font-size: 0.72rem; padding: 0.15rem 0.5rem; background: var(--green-50); color: var(--green-700); border-radius: 99px; font-weight: 500;">
                                    <?php echo htmlspecialchars($act['type_acteur']); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.82rem; color: var(--gray-500); margin-bottom: 0.4rem;">
                                📍 Commune : <strong><?php echo htmlspecialchars($act['commune']); ?></strong> 
                                <?php if ($act['telephone']): ?>
                                    • 📞 <?php echo htmlspecialchars($act['telephone']); ?>
                                <?php endif; ?>
                            </p>
                            <p style="font-size: 0.85rem; color: var(--gray-700); margin: 0; line-height: 1.45;">
                                <?php echo htmlspecialchars(mb_strimwidth($act['description'], 0, 140, "...")); ?>
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="profil_acteur.php?id=<?php echo $act['id']; ?>" class="btn btn-secondary btn-sm">Voir le profil</a>
                            <!-- Contacter via messagerie (to = user_id de l'acteur) -->
                            <?php if ($act['user_id'] != $user['id']): ?>
                                <a href="messagerie.php?to=<?php echo $act['user_id']; ?>" class="btn btn-primary btn-sm">✉️ Envoyer un message</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
