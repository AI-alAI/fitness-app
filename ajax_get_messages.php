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
$role = $_SESSION['role'];
$other_id = isset($_GET['other_id']) ? intval($_GET['other_id']) : 0;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if (!$other_id) {
    echo json_encode([]);
    exit();
}

// Get new messages (sent by other user to current user, or current user to other user)
// We'll fetch messages with id > last_id and where (expediteur = current & destinataire = other) OR (expediteur = other & destinataire = current)
$query = "SELECT m.*, u1.nom as exp_nom, u1.prenom as exp_prenom, u2.nom as dest_nom, u2.prenom as dest_prenom
          FROM messages m
          JOIN utilisateurs u1 ON m.id_expediteur = u1.id_utilisateur
          JOIN utilisateurs u2 ON m.id_destinataire = u2.id_utilisateur
          WHERE ((m.id_expediteur = :user AND m.id_destinataire = :other) OR (m.id_expediteur = :other AND m.id_destinataire = :user))
          AND m.id_message > :last_id
          ORDER BY m.date_envoi ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user', $user_id);
$stmt->bindParam(':other', $other_id);
$stmt->bindParam(':last_id', $last_id);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark received messages as read
foreach ($messages as $msg) {
    if ($msg['id_destinataire'] == $user_id && !$msg['lu']) {
        $update = $db->prepare("UPDATE messages SET lu = 1 WHERE id_message = :id");
        $update->bindParam(':id', $msg['id_message']);
        $update->execute();
    }
}

echo json_encode($messages);
?>