<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'kinshasa_ecolo';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Création de la BD
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    // Création de la table users
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        mot_de_passe VARCHAR(255) NOT NULL,
        role ENUM('citoyen', 'acteur', 'admin') NOT NULL DEFAULT 'citoyen',
        statut ENUM('actif', 'en_attente', 'suspendu') NOT NULL DEFAULT 'actif',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Création de la table acteurs
    $sqlActeurs = "CREATE TABLE IF NOT EXISTS acteurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        organisation VARCHAR(255) NOT NULL,
        type_acteur VARCHAR(100) NOT NULL,
        telephone VARCHAR(50),
        commune VARCHAR(100),
        description TEXT,
        motivation TEXT,
        domaine_action VARCHAR(100),
        statut ENUM('en_attente', 'valide', 'rejete') NOT NULL DEFAULT 'en_attente',
        date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlActeurs);

    // Création de la table publications_acteur
    $sqlPubs = "CREATE TABLE IF NOT EXISTS publications_acteur (
        id INT AUTO_INCREMENT PRIMARY KEY,
        acteur_id INT NOT NULL,
        contenu TEXT NOT NULL,
        date_publication TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (acteur_id) REFERENCES acteurs(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlPubs);

    // Création de la table likes_publication
    $sqlLikes = "CREATE TABLE IF NOT EXISTS likes_publication (
        id INT AUTO_INCREMENT PRIMARY KEY,
        publication_id INT NOT NULL,
        user_id INT NOT NULL,
        UNIQUE KEY unique_like (publication_id, user_id),
        FOREIGN KEY (publication_id) REFERENCES publications_acteur(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlLikes);

    // Création de la table commentaires_publication
    $sqlComms = "CREATE TABLE IF NOT EXISTS commentaires_publication (
        id INT AUTO_INCREMENT PRIMARY KEY,
        publication_id INT NOT NULL,
        user_id INT NOT NULL,
        contenu TEXT NOT NULL,
        date_commentaire TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (publication_id) REFERENCES publications_acteur(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlComms);

    // Création de la table messages_acteur
    $sqlMessages = "CREATE TABLE IF NOT EXISTS messages_acteur (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expediteur_id INT NOT NULL,
        destinataire_id INT NOT NULL,
        message TEXT NOT NULL,
        lu BOOLEAN DEFAULT FALSE,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (expediteur_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (destinataire_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlMessages);
    
    // Insertion de l'admin par défaut
    $email_admin = 'elite@admin.com';
    $mdp_admin = password_hash('1234', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email_admin]);
    if($stmt->rowCount() == 0) {
        $insert = $pdo->prepare("INSERT INTO users (nom, email, mot_de_passe, role, statut) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['Administrateur', $email_admin, $mdp_admin, 'admin', 'actif']);
        echo "<p>Administrateur créé avec succès.</p>";
    }
    
    echo "<p>Base de données et table initialisées avec succès !</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
