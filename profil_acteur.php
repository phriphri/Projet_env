<?php
require_once 'config/database.php';
// La session est déjà gérée par config/database.php

$acteur_id = $_GET['id'] ?? null;
if (!$acteur_id) {
    header('Location: portail_acteurs.php');
    exit;
}

// Récupérer les infos de l'acteur
$stmt = $pdo->prepare("SELECT a.*, u.email, u.nom FROM acteurs a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.statut = 'valide'");
$stmt->execute([$acteur_id]);
$acteur = $stmt->fetch();

if (!$acteur) {
    echo "Acteur introuvable ou non validé.";
    exit;
}

// Récupérer les publications de l'acteur
$stmtPubs = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes_publication WHERE publication_id = p.id) as likes_count
    FROM publications_acteur p
    WHERE p.acteur_id = ?
    ORDER BY p.date_publication DESC
");
$stmtPubs->execute([$acteur_id]);
$pubs = $stmtPubs->fetchAll();

$user_id = $_SESSION['user_id'] ?? null;

?>
<?php include 'includes/header.php'; ?>

<div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                <?php echo htmlspecialchars($acteur['organisation']); ?> 
                <span title="Acteur validé">✅</span>
            </h1>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($acteur['type_acteur']); ?> • <?php echo htmlspecialchars($acteur['domaine_action']); ?> • 📍 <?php echo htmlspecialchars($acteur['commune']); ?>
            </p>
        </div>
        
        <?php if ($user_id && $user_id != $acteur['user_id']): ?>
            <a href="messagerie.php?to=<?php echo $acteur['user_id']; ?>" style="padding: 0.8rem 1.5rem; background: var(--secondary-color); color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Contacter</a>
        <?php endif; ?>
    </div>

    <div style="margin-top: 1.5rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: #333;">À propos de nous</h3>
        <p style="line-height: 1.6; margin-bottom: 1.5rem;"><?php echo nl2br(htmlspecialchars($acteur['description'])); ?></p>
        
        <?php if ($acteur['motivation']): ?>
            <h3 style="margin-bottom: 1rem; color: #333;">Notre Motivation</h3>
            <p style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($acteur['motivation'])); ?></p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 3rem;">
        <h2 style="margin-bottom: 1.5rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">Publications (<?php echo count($pubs); ?>)</h2>
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($pubs as $pub): ?>
                <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px;">
                    <div style="margin-bottom: 1rem; color: #666; font-size: 0.9em;">
                        Publié le <?php echo date('d/m/Y à H:i', strtotime($pub['date_publication'])); ?>
                    </div>
                    <p style="line-height: 1.5; margin-bottom: 1rem;"><?php echo nl2br(htmlspecialchars($pub['contenu'])); ?></p>
                    <div style="color: #666; font-weight: bold;">
                        ❤️ <?php echo $pub['likes_count']; ?> Likes
                        • 
                        <a href="portail_acteurs.php#pub-<?php echo $pub['id']; ?>" style="color: var(--secondary-color); text-decoration: none;">Voir les commentaires</a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($pubs)): ?>
                <p style="color: #666; font-style: italic;">Cet acteur n'a pas encore publié de contenu.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="portail_acteurs.php" style="color: var(--text-color); font-weight: bold; text-decoration: none;">&larr; Retour au portail</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
