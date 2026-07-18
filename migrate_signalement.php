<?php
require_once 'config/database.php';

try {
    // Modifier l'ENUM du statut pour refléter le nouveau workflow
    $pdo->exec("ALTER TABLE signalements MODIFY COLUMN statut ENUM('En attente', 'Vérifié', 'Rejeté') DEFAULT 'En attente'");
    echo "<p style='font-family:sans-serif;'>✅ Table 'signalements' mise à jour avec les nouveaux statuts (En attente, Vérifié, Rejeté).</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>
