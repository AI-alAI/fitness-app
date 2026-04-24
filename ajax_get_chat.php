<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']); exit;
}

$database = new Database();
$db       = $database->getConnection();
$userId   = $_SESSION['user_id'];

$stmt = $db->prepare(
    "SELECT role, message, created_at 
     FROM user_chat_memory 
     WHERE id_utilisateur = :id 
     ORDER BY created_at ASC 
     LIMIT 50"
);
$stmt->bindParam(':id', $userId);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>