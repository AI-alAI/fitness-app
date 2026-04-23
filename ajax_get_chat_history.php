<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT role, message FROM user_chat_memory WHERE id_utilisateur = :id ORDER BY created_at ASC");
$stmt->bindParam(':id', $user_id);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));