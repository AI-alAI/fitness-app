<?php
// ajax_get_chat.php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $userId = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT role, message FROM user_chat_memory WHERE id_utilisateur = :id ORDER BY created_at ASC");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($history);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>