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

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Ne jamais exposer les détails de connexion en production
    error_log('[Kin La Verte] Connexion BDD échouée : ' . $e->getMessage());
    http_response_code(503);
    die("Le service est temporairement indisponible. Veuillez réessayer plus tard.");
}

// Charger le système d'authentification persistant
require_once __DIR__ . '/auth.php';
?>
