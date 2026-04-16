<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
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

$query = "INSERT INTO messages (id_expediteur, id_destinataire, contenu, date_envoi, lu) 
          VALUES (:exp, :dest, :contenu, NOW(), 0)";
$stmt = $db->prepare($query);
$stmt->bindParam(':exp', $user_id);
$stmt->bindParam(':dest', $destinataire_id);
$stmt->bindParam(':contenu', $contenu);
$stmt->execute();

$new_id = $db->lastInsertId();

// Return the new message data
$query2 = "SELECT m.*, 
           u1.nom as exp_nom, u1.prenom as exp_prenom,
           u2.nom as dest_nom, u2.prenom as dest_prenom
           FROM messages m
           JOIN utilisateurs u1 ON m.id_expediteur = u1.id_utilisateur
           JOIN utilisateurs u2 ON m.id_destinataire = u2.id_utilisateur
           WHERE m.id_message = :id";
$stmt2 = $db->prepare($query2);
$stmt2->bindParam(':id', $new_id);
$stmt2->execute();
$message = $stmt2->fetch(PDO::FETCH_ASSOC);

echo json_encode($message);
?>