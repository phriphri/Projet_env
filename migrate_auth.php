<?php
/**
 * migrate_auth.php — Script pour appliquer la migration de la table de tokens
 */
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS user_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(255) NOT NULL UNIQUE,
        hashed_validator VARCHAR(255) NOT NULL,
        expiry DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "<h1>Migration réussie !</h1>";
    echo "<p>La table <code>user_tokens</code> a été créée avec succès.</p>";
    echo '<p><a href="index.php">Retourner à l\'accueil</a></p>';
} catch (PDOException $e) {
    die("Erreur de migration : " . $e->getMessage());
}
?>
