<?php
// Démarrer la session si elle n'est pas encore active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'kinshasa_ecolo';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
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
