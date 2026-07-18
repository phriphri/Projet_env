<?php
require_once 'config/database.php';
// La session est déjà gérée par config/database.php

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'citoyen';

// Vérifier si l'utilisateur est un acteur validé
$is_acteur = false;
$mon_acteur_id = null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, statut FROM acteurs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $acteur_data = $stmt->fetch();
    
    if ($acteur_data && $acteur_data['statut'] === 'valide') {
        $is_acteur = true;
        $mon_acteur_id = $acteur_data['id'];
    }
}

// Gérer la publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'publier' && $is_acteur) {
        $contenu = trim($_POST['contenu']);
        if ($contenu) {
            $insert = $pdo->prepare("INSERT INTO publications_acteur (acteur_id, contenu) VALUES (?, ?)");
            $insert->execute([$mon_acteur_id, $contenu]);
            header('Location: portail_acteurs.php');
            exit;
        }
    } elseif ($_POST['action'] === 'like' && $user_id) {
        $pub_id = (int)$_POST['publication_id'];
        try {
            $insert = $pdo->prepare("INSERT IGNORE INTO likes_publication (publication_id, user_id) VALUES (?, ?)");
            $insert->execute([$pub_id, $user_id]);
        } catch (PDOException $e) {}
        header('Location: portail_acteurs.php#pub-' . $pub_id);
        exit;
    } elseif ($_POST['action'] === 'commenter' && $user_id) {
        $pub_id = (int)$_POST['publication_id'];
        $commentaire = trim($_POST['commentaire']);
        if ($commentaire) {
            $insert = $pdo->prepare("INSERT INTO commentaires_publication (publication_id, user_id, contenu) VALUES (?, ?, ?)");
            $insert->execute([$pub_id, $user_id, $commentaire]);
        }
        header('Location: portail_acteurs.php#pub-' . $pub_id);
        exit;
    }
}

// Filtres des acteurs
$filter_commune = $_GET['commune'] ?? '';
$filter_type = $_GET['type_acteur'] ?? '';
$filter_domaine = $_GET['domaine_action'] ?? '';

$where_clauses = ["statut = 'valide'"];
$params = [];

if ($filter_commune) {
    $where_clauses[] = "commune LIKE ?";
    $params[] = "%$filter_commune%";
}
if ($filter_type) {
    $where_clauses[] = "type_acteur = ?";
    $params[] = $filter_type;
}
if ($filter_domaine) {
    $where_clauses[] = "domaine_action = ?";
    $params[] = $filter_domaine;
}

$where_sql = implode(' AND ', $where_clauses);
$stmtActeurs = $pdo->prepare("SELECT * FROM acteurs WHERE $where_sql ORDER BY organisation ASC");
$stmtActeurs->execute($params);
$acteurs = $stmtActeurs->fetchAll();

// Récupérer les publications avec le compte de likes
$pubs = $pdo->query("
    SELECT p.*, a.organisation, a.type_acteur,
           (SELECT COUNT(*) FROM likes_publication WHERE publication_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM likes_publication WHERE publication_id = p.id AND user_id = " . ($user_id ?: 0) . ") as user_liked
    FROM publications_acteur p
    JOIN acteurs a ON p.acteur_id = a.id
    ORDER BY p.date_publication DESC
    LIMIT 50
")->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <!-- Colonne Principale: Fil d'actualité -->
    <div>
        <h1 style="color: var(--primary-color); margin-bottom: 1.5rem;">Actualités des Acteurs</h1>

        <?php if ($is_acteur): ?>
            <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                <form method="POST" action="portail_acteurs.php">
                    <input type="hidden" name="action" value="publier">
                    <textarea name="contenu" rows="3" placeholder="Partagez une activité, un succès, ou une campagne..." required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 1rem; resize: vertical;"></textarea>
                    <div style="text-align: right;">
                        <button type="submit" style="padding: 0.8rem 1.5rem; background: var(--primary-color); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Publier</button>
                    </div>
                </form>
            </div>
        <?php elseif (!$user_id): ?>
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin-bottom: 2rem; color: #0c5460;">
                <a href="login.php" style="font-weight: bold; color: #0c5460; text-decoration: underline;">Connectez-vous</a> pour interagir avec les publications ou rejoindre les acteurs.
            </div>
        <?php else: ?>
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin-bottom: 2rem; color: #0c5460;">
                Vous souhaitez publier ? <a href="devenir_acteur.php" style="font-weight: bold; color: #0c5460; text-decoration: underline;">Faites une demande pour devenir acteur !</a>
            </div>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($pubs as $pub): ?>
                <div id="pub-<?php echo $pub['id']; ?>" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <strong><a href="profil_acteur.php?id=<?php echo $pub['acteur_id']; ?>" style="color: #333; text-decoration: none; font-size: 1.1em;"><?php echo htmlspecialchars($pub['organisation']); ?></a></strong>
                        <small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($pub['date_publication'])); ?></small>
                    </div>
                    <p style="margin-bottom: 1.5rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($pub['contenu'])); ?></p>
                    
                    <div style="display: flex; align-items: center; gap: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="like">
                            <input type="hidden" name="publication_id" value="<?php echo $pub['id']; ?>">
                            <button type="submit" <?php echo !$user_id ? 'disabled' : ''; ?> style="background: none; border: none; cursor: <?php echo $user_id ? 'pointer' : 'default'; ?>; color: <?php echo $pub['user_liked'] ? '#e74c3c' : '#666'; ?>; font-weight: bold; display: flex; align-items: center; gap: 0.3rem;">
                                <?php echo $pub['user_liked'] ? '❤️' : '🤍'; ?> <?php echo $pub['likes_count']; ?>
                            </button>
                        </form>
                        
                        <?php
                        // Récupérer les commentaires
                        $stmtComm = $pdo->prepare("SELECT c.*, u.nom FROM commentaires_publication c JOIN users u ON c.user_id = u.id WHERE publication_id = ? ORDER BY date_commentaire ASC");
                        $stmtComm->execute([$pub['id']]);
                        $commentaires = $stmtComm->fetchAll();
                        ?>
                        <span style="color: #666; font-weight: bold;">💬 <?php echo count($commentaires); ?></span>
                    </div>

                    <!-- Section commentaires -->
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                        <?php foreach ($commentaires as $c): ?>
                            <div style="margin-bottom: 0.5rem; font-size: 0.9em;">
                                <strong><?php echo htmlspecialchars($c['nom']); ?> :</strong>
                                <span><?php echo htmlspecialchars($c['contenu']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($user_id): ?>
                            <form method="POST" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <input type="hidden" name="action" value="commenter">
                                <input type="hidden" name="publication_id" value="<?php echo $pub['id']; ?>">
                                <input type="text" name="commentaire" placeholder="Ajouter un commentaire..." required style="flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                                <button type="submit" style="padding: 0.5rem 1rem; background: var(--secondary-color); color: white; border: none; border-radius: 4px; cursor: pointer;">Envoyer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($pubs)): ?>
                <p style="text-align: center; color: #666;">Aucune publication pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne Latérale: Annuaire des acteurs -->
    <div>
        <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 2rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem; font-size: 1.2rem;">Annuaire des Acteurs</h2>
            
            <form method="GET" action="portail_acteurs.php" style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #eee;">
                <input type="text" name="commune" placeholder="Commune..." value="<?php echo htmlspecialchars($filter_commune); ?>" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                
                <select name="type_acteur" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">Tous les types</option>
                    <option value="ONG" <?php if($filter_type=='ONG') echo 'selected'; ?>>ONG</option>
                    <option value="Association" <?php if($filter_type=='Association') echo 'selected'; ?>>Association</option>
                    <option value="Entreprise" <?php if($filter_type=='Entreprise') echo 'selected'; ?>>Entreprise</option>
                </select>
                
                <select name="domaine_action" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">Tous les domaines</option>
                    <option value="Dechets" <?php if($filter_domaine=='Dechets') echo 'selected'; ?>>Gestion des déchets</option>
                    <option value="Education" <?php if($filter_domaine=='Education') echo 'selected'; ?>>Éducation</option>
                    <option value="Climat" <?php if($filter_domaine=='Climat') echo 'selected'; ?>>Climat</option>
                </select>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="flex: 1; padding: 0.5rem; background: var(--secondary-color); color: white; border: none; border-radius: 4px; cursor: pointer;">Filtrer</button>
                    <a href="portail_acteurs.php" style="padding: 0.5rem; background: #ccc; color: #333; text-decoration: none; border-radius: 4px; text-align: center;" title="Réinitialiser">↺</a>
                </div>
            </form>

            <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 500px; overflow-y: auto;">
                <?php foreach ($acteurs as $acteur): ?>
                    <div style="padding: 1rem; border: 1px solid #eee; border-radius: 4px;">
                        <h3 style="margin-bottom: 0.5rem; font-size: 1rem;"><a href="profil_acteur.php?id=<?php echo $acteur['id']; ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($acteur['organisation']); ?></a> ✅</h3>
                        <p style="font-size: 0.85em; color: #666; margin-bottom: 0.3rem;">📍 <?php echo htmlspecialchars($acteur['commune']); ?></p>
                        <p style="font-size: 0.85em; color: #666; margin-bottom: 0.3rem;">🏷️ <?php echo htmlspecialchars($acteur['type_acteur']); ?></p>
                        <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; color: #0d47a1; font-size: 0.8em; border-radius: 12px; font-weight: bold; margin-top: 0.5rem;">
                            <?php echo htmlspecialchars($acteur['domaine_action']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($acteurs)): ?>
                    <p style="color: #666; font-size: 0.9em;">Aucun acteur trouvé.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
