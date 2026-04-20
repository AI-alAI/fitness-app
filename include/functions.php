<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function redirectIfNotRole($allowedRoles = ['utilisateur']) {
    redirectIfNotLoggedIn();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../auth/login.php?error=access_denied");
        exit();
    }
}

function getUserInfo($db, $userId) {
    $query = "SELECT * FROM utilisateurs WHERE id_utilisateur = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStats($db, $userId) {
    $stmt = $db->prepare("SELECT COUNT(*) as total_seances FROM seances WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $seances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT SUM(calories) as total_calories FROM seances WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $calories = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT COUNT(*) as goals_achieved FROM objectifs WHERE id_utilisateur = :id AND statut = 'atteint'");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $goals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT COUNT(*) as unread_messages FROM messages WHERE id_destinataire = :id AND lu = 0");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $messages = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_seances' => $seances['total_seances'],
        'total_calories' => $calories['total_calories'] ?: 0,
        'goals_achieved' => $goals['goals_achieved'],
        'unread_messages' => $messages['unread_messages']
    ];
}

// Helper: get coach ID from user ID
function getCoachId($db, $userId) {
    $stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id_coach'] : null;
}

// Helper: get user programmes purchased
function getUserProgrammes($db, $userId) {
    $stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
?>