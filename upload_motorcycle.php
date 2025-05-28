<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model = trim($_POST['model']);
    $description = trim($_POST['description']);
    $image = $_FILES['image'];
    $video = $_FILES['video'];

    // Upload directories
    $imageDir = 'uploads/images/';
    $videoDir = 'uploads/videos/';
    if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);
    if (!is_dir($videoDir)) mkdir($videoDir, 0777, true);

    // Handle image upload
    if ($image['error'] === UPLOAD_ERR_OK) {
        $imageExt = pathinfo($image['name'], PATHINFO_EXTENSION);
        $imageFilename = uniqid('img_', true) . '.' . $imageExt;
        $imagePath = $imageDir . $imageFilename;

        if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
            $_SESSION['upload_error'] = 'Failed to upload image.';
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $_SESSION['upload_error'] = 'Image upload failed.';
        header("Location: dashboard.php");
        exit();
    }

    // Optional video upload
    $videoPath = null;
    if (!empty($video['name']) && $video['error'] === UPLOAD_ERR_OK) {
        $videoExt = pathinfo($video['name'], PATHINFO_EXTENSION);
        $videoFilename = uniqid('vid_', true) . '.' . $videoExt;
        $videoPath = $videoDir . $videoFilename;

        if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
            $_SESSION['upload_error'] = 'Video upload failed.';
            header("Location: dashboard.php");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO motorcycles (user_id, model, description, image_path, video_path, created_at) VALUES (:user_id, :model, :description, :image_path, :video_path, NOW())");
        $stmt->execute([
            'user_id' => $user_id,
            'model' => $model,
            'description' => $description,
            'image_path' => $imagePath,
            'video_path' => $videoPath,
        ]);

        $_SESSION['upload_success'] = 'Motorcycle uploaded successfully!';
    } catch (PDOException $e) {
        $_SESSION['upload_error'] = 'Database error: ' . $e->getMessage();
    }

    header("Location: dashboard.php");
    exit();
}
?>
