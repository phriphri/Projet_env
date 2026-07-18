<?php
/**
 * open-data.php — Portail Open Data Environnemental
 */
require_once 'config/database.php';

$search    = trim($_GET['search']    ?? '');
$categorie = trim($_GET['categorie'] ?? '');

$query  = "SELECT * FROM open_data WHERE 1=1";
$params = [];

if ($search !== '') {
    $query   .= " AND (titre LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categorie !== '') {
    $query   .= " AND categorie = ?";
    $params[] = $categorie;
}
$query .= " ORDER BY date_creation DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT DISTINCT categorie FROM open_data ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<style>
.od-wrap {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.od-head {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-100);
}
.od-head h1 { font-size: 1.4rem; color: var(--gray-900); margin: 0 0 0.25rem; }
.od-head p  { font-size: 0.875rem; color: var(--gray-500); margin: 0; }

/* Filtres */
.filter-row {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.filter-row input,
.filter-row select {
    font-size: 0.85rem;
    padding: 0.45rem 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    color: var(--gray-900);
    height: 36px;
}
.filter-row input  { flex: 2; min-width: 180px; }
.filter-row select { flex: 1; min-width: 150px; }
.filter-row button {
    padding: 0 1rem;
    height: 36px;
    background: var(--gray-900);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
}
.filter-row a.reset-link {
    display: inline-flex;
    align-items: center;
    height: 36px;
    padding: 0 0.75rem;
    font-size: 0.82rem;
    color: var(--gray-500);
    text-decoration: none;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
}
.filter-row a.reset-link:hover { color: var(--gray-900); }

/* Tableau de données */
.od-table-wrap {
    background: var(--white);
    border: 1px solid var(--gray-100);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.od-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.od-table thead th {
    background: var(--gray-50);
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--gray-400);
    border-bottom: 1px solid var(--gray-100);
    white-space: nowrap;
}
.od-table tbody tr { border-top: 1px solid var(--gray-50); }
.od-table tbody tr:hover { background: #fafafa; }
.od-table tbody td { padding: 0.9rem 1rem; vertical-align: top; }

.od-cat-tag {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 500;
    padding: 0.15rem 0.5rem;
    border-radius: 99px;
    background: #E0F2FE;
    color: #0369a1;
    border: 1px solid #7dd3fc;
    white-space: nowrap;
}

.od-title { font-weight: 600; color: var(--gray-900); margin-bottom: 0.2rem; }
.od-desc  { color: var(--gray-500); font-size: 0.82rem; line-height: 1.45;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.od-source { font-size: 0.75rem; color: var(--gray-400); margin-top: 0.2rem; }

.btn-dl {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    text-decoration: none;
    white-space: nowrap;
    transition: all var(--transition);
}
.btn-dl:hover { background: var(--gray-900); color: white; border-color: var(--gray-900); }

.empty-state { padding: 3rem; text-align: center; color: var(--gray-400); font-size: 0.9rem; }
.result-count { font-size: 0.8rem; color: var(--gray-400); margin-bottom: 0.75rem; }

/* Responsive : table → liste sur mobile */
@media (max-width: 600px) {
    .od-table thead { display: none; }
    .od-table tbody tr { display: block; padding: 0.75rem 1rem; }
    .od-table tbody td { display: block; padding: 0.25rem 0; border: none; }
}
</style>

<div class="od-wrap">
    <div class="od-head">
        <h1>Open Data</h1>
        <p>Données environnementales ouvertes de Kinshasa — consultez, étudiez et téléchargez librement.</p>
    </div>

    <!-- Filtres -->
    <form method="GET" action="open-data.php" class="filter-row">
        <input type="text" name="search" placeholder="Rechercher un jeu de données..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="categorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $categorie === $c ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrer</button>
        <?php if ($search || $categorie): ?>
            <a href="open-data.php" class="reset-link">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <p class="result-count"><?php echo count($datasets); ?> jeu(x) de données</p>

    <!-- Tableau -->
    <div class="od-table-wrap">
        <?php if (empty($datasets)): ?>
            <div class="empty-state">Aucun jeu de données disponible pour le moment.</div>
        <?php else: ?>
            <table class="od-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Jeu de données</th>
                        <th>Source</th>
                        <th style="text-align:right;">Accès</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datasets as $d): ?>
                        <tr>
                            <td style="width: 130px;">
                                <span class="od-cat-tag"><?php echo htmlspecialchars($d['categorie']); ?></span>
                            </td>
                            <td>
                                <div class="od-title"><?php echo htmlspecialchars($d['titre']); ?></div>
                                <?php if ($d['description']): ?>
                                    <div class="od-desc"><?php echo htmlspecialchars($d['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="width: 130px;">
                                <div class="od-source"><?php echo htmlspecialchars($d['source'] ?: '—'); ?></div>
                            </td>
                            <td style="width: 110px; text-align: right;">
                                <?php if ($d['fichier']): ?>
                                    <a href="<?php echo htmlspecialchars($d['fichier']); ?>" target="_blank" class="btn-dl">
                                        ↓ Télécharger
                                    </a>
                                <?php else: ?>
                                    <span style="font-size:0.78rem; color: var(--gray-300);">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
