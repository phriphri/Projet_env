<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS contenus_educatifs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        theme VARCHAR(100),
        type ENUM('Vidéo', 'PDF', 'Image') NOT NULL,
        fichier VARCHAR(255),
        source VARCHAR(255),
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>Table 'contenus_educatifs' créée avec succès.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
