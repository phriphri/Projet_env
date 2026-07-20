<?php
/**
 * cours.php — Contenu éducatif environnemental
 */
require_once 'config/database.php';

$recherche_mot   = trim($_GET['search'] ?? '');
$recherche_theme = trim($_GET['theme'] ?? '');
$recherche_type  = trim($_GET['type'] ?? '');

$sql    = "SELECT * FROM contenus_educatifs WHERE 1=1";
$params = [];

if ($recherche_mot !== '') {
    $sql     .= " AND (titre LIKE ? OR description LIKE ?)";
    $params[] = "%$recherche_mot%";
    $params[] = "%$recherche_mot%";
}
if ($recherche_theme !== '') {
    $sql     .= " AND theme = ?";
    $params[] = $recherche_theme;
}
if ($recherche_type !== '') {
    $sql     .= " AND type = ?";
    $params[] = $recherche_type;
}
$sql .= " ORDER BY date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$themes = $pdo->query("SELECT DISTINCT theme FROM contenus_educatifs ORDER BY theme")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<style>
.edu-wrap {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.edu-head {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-100);
}
.edu-head h1 { font-size: 1.4rem; color: var(--green-700); margin: 0 0 0.25rem; }
.edu-head p  { font-size: 0.875rem; color: var(--gray-500); margin: 0; }

/* Barre de filtre */
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
.filter-row input { flex: 2; min-width: 180px; }
.filter-row select { flex: 1; min-width: 140px; }
.filter-row button {
    padding: 0 1rem;
    height: 36px;
    background: var(--green-700);
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

/* Liste de contenus */
.edu-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    border: 1px solid var(--gray-100);
    border-radius: var(--radius-lg);
    background: var(--white);
    overflow: hidden;
}
.edu-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.5rem;
    border-top: 1px solid var(--gray-100);
}
.edu-item:first-child { border-top: none; }
.edu-item:hover { background: #fafafa; }

.edu-item-left { flex: 1; min-width: 0; }
.edu-item-meta {
    display: flex;
    gap: 0.4rem;
    align-items: center;
    margin-bottom: 0.3rem;
    flex-wrap: wrap;
}
.edu-tag {
    font-size: 0.72rem;
    font-weight: 500;
    padding: 0.15rem 0.5rem;
    border-radius: 99px;
    white-space: nowrap;
}
.edu-tag.theme { background: var(--green-50); color: var(--green-700); border: 1px solid var(--green-400); }
.edu-tag.type-pdf   { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
.edu-tag.type-video { background: #EDE9FE; color: #5B21B6; border: 1px solid #c4b5fd; }
.edu-tag.type-image { background: #E0F2FE; color: #0369a1; border: 1px solid #7dd3fc; }

.edu-item-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.edu-item-desc {
    font-size: 0.82rem;
    color: var(--gray-500);
    line-height: 1.45;
    margin-bottom: 0.25rem;
}
.edu-item-source {
    font-size: 0.75rem;
    color: var(--gray-400);
}
.edu-item-right { flex-shrink: 0; }

.btn-consult {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.4rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 500;
    background: var(--white);
    color: var(--green-700);
    border: 1px solid var(--green-400);
    border-radius: var(--radius-sm);
    text-decoration: none;
    white-space: nowrap;
    transition: all var(--transition);
}
.btn-consult:hover { background: var(--green-700); color: white; }

.empty-state {
    padding: 3rem;
    text-align: center;
    color: var(--gray-400);
    font-size: 0.9rem;
}

.result-count {
    font-size: 0.8rem;
    color: var(--gray-400);
    margin-bottom: 0.75rem;
}

@media (max-width: 600px) {
    .edu-item {
        flex-direction: column;
        align-items: stretch;
    }
    .edu-item-right {
        margin-top: 1rem;
        align-items: flex-start !important;
    }
    .edu-item-right > div,
    .edu-item-right > button,
    .edu-item-right > a {
        width: 100% !important;
        max-width: 250px;
    }
}
</style>

<div class="edu-wrap">
    <div class="edu-head">
        <h1>Contenu éducatif</h1>
        <p>Documentation et ressources pour la protection de l'environnement à Kinshasa.</p>
    </div>

    <!-- Filtres -->
    <form method="GET" action="cours.php" class="filter-row">
        <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($recherche_mot); ?>">
        <select name="theme">
            <option value="">Tous les thèmes</option>
            <?php foreach ($themes as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $recherche_theme === $t ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="type">
            <option value="">Tous les formats</option>
            <option value="PDF"   <?php echo $recherche_type === 'PDF'   ? 'selected' : ''; ?>>PDF</option>
            <option value="Vidéo" <?php echo $recherche_type === 'Vidéo' ? 'selected' : ''; ?>>Vidéo</option>
            <option value="Image" <?php echo $recherche_type === 'Image' ? 'selected' : ''; ?>>Image / Infographie</option>
        </select>
        <button type="submit">Filtrer</button>
        <?php if ($recherche_mot || $recherche_theme || $recherche_type): ?>
            <a href="cours.php" class="reset-link">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <p class="result-count"><?php echo count($contenus); ?> résultat(s)</p>

    <!-- Liste -->
    <div class="edu-list">
        <?php if (empty($contenus)): ?>
            <div class="empty-state">Aucun contenu ne correspond à vos critères.</div>
        <?php else: ?>
            <?php foreach ($contenus as $c): ?>
                <div class="edu-item">
                    <div class="edu-item-left">
                        <div class="edu-item-meta">
                            <?php if ($c['theme']): ?>
                                <span class="edu-tag theme"><?php echo htmlspecialchars($c['theme']); ?></span>
                            <?php endif; ?>
                            <?php if ($c['type']): ?>
                                <span class="edu-tag type-<?php echo strtolower($c['type']); ?>">
                                    <?php echo htmlspecialchars($c['type']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="edu-item-title"><?php echo htmlspecialchars($c['titre']); ?></div>
                        <?php if ($c['description']): ?>
                            <div class="edu-item-desc"><?php echo nl2br(htmlspecialchars($c['description'])); ?></div>
                        <?php endif; ?>
                        <?php if ($c['source']): ?>
                            <div class="edu-item-source">Source : <?php echo htmlspecialchars($c['source']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="edu-item-right" style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                        <?php if ($c['fichier']): ?>
                            
                            <?php 
                            // Générer un aperçu visuel selon le type (local ou distant)
                            $is_image = (strtolower($c['type']) === 'image');
                            $is_video = (strtolower($c['type']) === 'vidéo' || strtolower($c['type']) === 'video');
                            $url = htmlspecialchars($c['fichier']);
                            $yt_id = '';
                            
                            // Extraction ID Youtube
                            if ($is_video && preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
                                $yt_id = $match[1];
                            }
                            ?>
                            
                            <?php if ($is_image): ?>
                                <div onclick="openModal('image', '<?php echo $url; ?>')" style="cursor: pointer; display: block; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden; width: 140px; height: 90px; background:#f0f0f0;">
                                    <img src="<?php echo $url; ?>" alt="Aperçu" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <button type="button" onclick="openModal('image', '<?php echo $url; ?>')" class="btn-consult" style="width: 100%; justify-content: center; border: 1px solid var(--green-400); background: var(--white); cursor:pointer;">
                                    Consulter →
                                </button>
                            <?php elseif ($is_video && $yt_id): ?>
                                <div onclick="openModal('youtube', '<?php echo $yt_id; ?>')" style="cursor: pointer; display: block; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden; width: 140px; height: 90px; position: relative;">
                                    <img src="https://img.youtube.com/vi/<?php echo $yt_id; ?>/mqdefault.jpg" alt="Miniature vidéo" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">▶</div>
                                </div>
                                <button type="button" onclick="openModal('youtube', '<?php echo $yt_id; ?>')" class="btn-consult" style="width: 100%; justify-content: center; border: 1px solid var(--green-400); background: var(--white); cursor:pointer;">
                                    Consulter →
                                </button>
                            <?php elseif ($is_video): ?>
                                <div onclick="openModal('video', '<?php echo $url; ?>')" style="cursor: pointer; width: 140px; height: 90px; background: #000; border-radius: var(--radius-sm); overflow: hidden; position: relative;">
                                    <video src="<?php echo $url; ?>" style="width:100%; height:100%; object-fit:cover;" preload="metadata"></video>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 1.5rem; text-shadow: 0 1px 3px rgba(0,0,0,0.8);">▶</div>
                                </div>
                                <button type="button" onclick="openModal('video', '<?php echo $url; ?>')" class="btn-consult" style="width: 100%; justify-content: center; border: 1px solid var(--green-400); background: var(--white); cursor:pointer;">
                                    Consulter →
                                </button>
                            <?php else: ?>
                                <a href="<?php echo $url; ?>" target="_blank" class="btn-consult" style="width: 100%; justify-content: center;">
                                    Consulter →
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size: 0.78rem; color: var(--gray-300);">— Aucun fichier associé —</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal / Lightbox -->
<div id="mediaModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center;">
    <span onclick="closeModal()" style="position: absolute; top: 20px; right: 30px; color: white; font-size: 2rem; font-weight: bold; cursor: pointer;">&times;</span>
    <div id="modalContent" style="max-width: 90%; max-height: 90%;"></div>
</div>

<script>
function openModal(type, url) {
    const modal = document.getElementById('mediaModal');
    const content = document.getElementById('modalContent');
    modal.style.display = 'flex';
    
    if (type === 'image') {
        content.innerHTML = `<img src="${url}" style="max-width: 100%; max-height: 90vh; border-radius: 8px;">`;
    } else if (type === 'video') {
        content.innerHTML = `<video src="${url}" controls autoplay style="max-width: 100%; max-height: 90vh; border-radius: 8px;"></video>`;
    } else if (type === 'youtube') {
        content.innerHTML = `<iframe width="800" height="450" src="https://www.youtube.com/embed/${url}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="max-width: 100%; border-radius: 8px;"></iframe>`;
    }
}

function closeModal() {
    const modal = document.getElementById('mediaModal');
    const content = document.getElementById('modalContent');
    modal.style.display = 'none';
    content.innerHTML = ''; // Arrête la lecture de la vidéo
}

// Fermer au clic en dehors
document.getElementById('mediaModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
