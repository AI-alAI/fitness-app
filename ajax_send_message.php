<?php
session_start();
require_once '../config/database.php';
require_once '../include/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$destinataire_id = isset($_POST['destinataire_id']) ? intval($_POST['destinataire_id']) : 0;
$contenu = trim($_POST['contenu'] ?? '');

if (!$destinataire_id || empty($contenu)) {
    echo json_encode(['error' => 'Message invalide']);
    exit();
}

$query = "INSERT INTO messages (id_expediteur, id_destinataire, contenu, date_envoi) VALUES (:exp, :dest, :contenu, NOW())";
$stmt = $db->prepare($query);
$stmt->bindParam(':exp', $user_id);
$stmt->bindParam(':dest', $destinataire_id);
$stmt->bindParam(':contenu', $contenu);
$stmt->execute();

echo json_encode(['success' => true]);
?>