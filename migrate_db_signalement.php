<?php
require_once 'config/database.php';

try {
    // 1. Vider la table pour éviter les conflits d'ENUM (on est en mode développement, c'est plus simple)
    // Mais au cas où, on va plutôt juste ALTER la table, les anciennes données prendront la première valeur de l'ENUM si elles ne correspondent plus, ou on peut les mettre à jour avant.
    
    // Mettre à jour les anciennes données pour qu'elles rentrent dans les nouveaux ENUM
    $pdo->exec("UPDATE signalements SET type = 'Déchets' WHERE type = 'Demande de collecte'");
    $pdo->exec("UPDATE signalements SET type = 'Pollution' WHERE type = 'Problème environnemental'");
    
    $pdo->exec("UPDATE signalements SET statut = 'Envoyé' WHERE statut = 'En attente'");
    $pdo->exec("UPDATE signalements SET statut = 'Traité' WHERE statut = 'Résolu'");
    $pdo->exec("UPDATE signalements SET statut = 'Vu' WHERE statut = 'En cours'");

    // 2. Modifier la structure de la table
    $sql = "ALTER TABLE signalements 
            MODIFY COLUMN type ENUM('Déchets', 'Inondation', 'Érosion', 'Pollution') NOT NULL,
            MODIFY COLUMN statut ENUM('Envoyé', 'Vu', 'Validé', 'Traité') DEFAULT 'Envoyé'";
            
    $pdo->exec($sql);
    
    echo "Migration réussie !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
