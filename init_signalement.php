<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS signalements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        citoyen_id INT,
        type ENUM('Problème environnemental', 'Demande de collecte') NOT NULL,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        photo VARCHAR(255),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        adresse VARCHAR(255),
        urgence ENUM('Faible', 'Moyenne', 'Haute') DEFAULT 'Moyenne',
        statut ENUM('En attente', 'En cours', 'Résolu', 'Rejeté') DEFAULT 'En attente',
        ai_metadata TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (citoyen_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "<p>Table 'signalements' créée avec succès.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
