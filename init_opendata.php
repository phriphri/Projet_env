<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS open_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        categorie VARCHAR(100),
        fichier VARCHAR(255),
        source VARCHAR(255),
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>Table 'open_data' créée avec succès.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
