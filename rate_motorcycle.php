<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['motorcycle_id'], $input['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$motorcycle_id = (int)$input['motorcycle_id'];
$rating = $input['rating'] === 'like' ? 'like' : ($input['rating'] === 'dislike' ? 'dislike' : null);

if ($rating === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

try {
    // Check if rating exists
    $stmt = $pdo->prepare("SELECT id FROM motorcycle_ratings WHERE user_id = :user_id AND motorcycle_id = :motorcycle_id");
    $stmt->execute(['user_id' => $user_id, 'motorcycle_id' => $motorcycle_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing rating
        $stmt = $pdo->prepare("UPDATE motorcycle_ratings SET rating = :rating, rated_at = NOW() WHERE id = :id");
        $stmt->execute(['rating' => $rating, 'id' => $existing['id']]);
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("INSERT INTO motorcycle_ratings (user_id, motorcycle_id, rating) VALUES (:user_id, :motorcycle_id, :rating)");
        $stmt->execute(['user_id' => $user_id, 'motorcycle_id' => $motorcycle_id, 'rating' => $rating]);
    }

    // Get updated counts
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN rating = 'like' THEN 1 ELSE 0 END) AS likes,
            SUM(CASE WHEN rating = 'dislike' THEN 1 ELSE 0 END) AS dislikes
        FROM motorcycle_ratings
        WHERE motorcycle_id = :motorcycle_id
    ");
    $stmt->execute(['motorcycle_id' => $motorcycle_id]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user_rating' => $rating,
        'likes' => (int)$counts['likes'],
        'dislikes' => (int)$counts['dislikes']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
