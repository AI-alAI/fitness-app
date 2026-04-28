<?php
// ajax_get_chat_memory.php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$db = (new Database())->getConnection();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT role, message, created_at FROM user_chat_memory WHERE id_utilisateur = ? ORDER BY created_at ASC");
$stmt->execute([$userId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>