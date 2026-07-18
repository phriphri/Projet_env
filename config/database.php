<?php
// Démarrer la session si elle n'est pas encore active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'kinshasa_ecolo';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Activer le SSL uniquement pour la connexion distante Aiven
if (getenv('DB_HOST') && getenv('DB_HOST') !== 'localhost') {
    $cert_path = __DIR__ . '/certs/aiven-ca.pem';
    
    if (!is_readable($cert_path)) {
        error_log("[Kin La Verte] Certificat SSL introuvable ou illisible : $cert_path");
        http_response_code(500);
        die("Erreur critique : Le certificat SSL de la base de données est introuvable. Veuillez contacter l'administrateur.");
    }
    
    $options[PDO::MYSQL_ATTR_SSL_CA] = $cert_path;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Ne jamais exposer les détails de connexion en production
    error_log('[Kin La Verte] Connexion BDD échouée : ' . $e->getMessage());
    http_response_code(503);
    die("Le service est temporairement indisponible. Veuillez réessayer plus tard.");
}

// Charger le système d'authentification persistant
require_once __DIR__ . '/auth.php';
?>
