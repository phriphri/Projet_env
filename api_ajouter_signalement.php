<?php
/**
 * API Endpoint pour la soumission d'un signalement via AJAX
 */
require_once 'config/database.php';

header('Content-Type: application/json');

// Auth
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous connecter.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode HTTP non autorisée.']);
    exit;
}

// CSRF
$submitted_csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($submitted_csrf) || !hash_equals(csrf_token(), $submitted_csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide. Rechargez la page.']);
    exit;
}

$type        = trim($_POST['type_incident'] ?? '');
$description = trim($_POST['description']   ?? '');
$lat         = $_POST['latitude']  ?? null;
$lng         = $_POST['longitude'] ?? null;
$urgence     = $_POST['gravite']   ?? 'Moyenne';
$citoyen_id  = (int) $_SESSION['user_id'];

// Validation GPS
if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'message' => 'Coordonnées GPS invalides.']);
    exit;
}
$lat = (float) $lat;
$lng = (float) $lng;

// Validation urgence
if (!in_array($urgence, ['Faible', 'Moyenne', 'Haute'])) {
    $urgence = 'Moyenne';
}

$titre        = "Signalement : " . $type;
$photo_chemin = null;

// Upload avec validation MIME stricte
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image trop volumineuse (max 5 Mo).']);
        exit;
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime_reel = $finfo->file($_FILES['photo']['tmp_name']);
    $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

    $mime_ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $ext_ok  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($mime_reel, $mime_ok) || !in_array($ext, $ext_ok)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (JPG, PNG, WebP, GIF uniquement).']);
        exit;
    }

    $dossier = 'assets/uploads/signalements/';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }

    $nom = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dossier . $nom)) {
        $photo_chemin = $dossier . $nom;
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de l'enregistrement du fichier."]);
        exit;
    }
}

if (empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Le type de signalement est requis.']);
    exit;
}

try {
    $pdo->prepare(
        "INSERT INTO signalements (citoyen_id, type, titre, description, photo, latitude, longitude, urgence, statut)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Envoyé')"
    )->execute([$citoyen_id, $type, $titre, $description, $photo_chemin, $lat, $lng, $urgence]);

    echo json_encode(['success' => true, 'message' => 'Le signalement a été enregistré avec succès.']);
} catch (PDOException $e) {
    error_log('[Kin La Verte] Erreur signalement : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
}
