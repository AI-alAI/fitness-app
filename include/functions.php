<?php
// include/functions.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Redirect if user does not have one of the allowed roles
function redirectIfNotRole($allowedRoles = ['utilisateur']) {
    redirectIfNotLoggedIn();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../auth/login.php?error=access_denied");
        exit();
    }
}

// Get user information from database
function getUserInfo($db, $userId) {
    $query = "SELECT * FROM utilisateurs WHERE id_utilisateur = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get statistics for user dashboard
function getStats($db, $userId) {
    // Total sessions
    $stmt = $db->prepare("SELECT COUNT(*) as total_seances FROM seances WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $seances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total calories
    $stmt = $db->prepare("SELECT SUM(calories) as total_calories FROM seances WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $calories = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Achieved goals
    $stmt = $db->prepare("SELECT COUNT(*) as goals_achieved FROM objectifs WHERE id_utilisateur = :id AND statut = 'atteint'");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $goals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Unread messages
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

// Get coach ID from user ID
function getCoachId($db, $userId) {
    $stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id_coach'] : null;
}

// Get programmes purchased by user
function getUserProgrammes($db, $userId) {
    $stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Get YouTube embed URL from regular YouTube URL
function getYouTubeEmbedUrl($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : null;
}
?>