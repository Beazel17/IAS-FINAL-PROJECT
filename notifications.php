<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return "$diff seconds ago";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return "$minutes minutes ago";
    $hours = floor($minutes / 60);
    if ($hours < 24) return "$hours hours ago";
    $days = floor($hours / 24);
    return "$days days ago";
}

try {
    $stmt = $pdo->prepare("SELECT username, email, warnings FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    $notifications = [
        'warnings' => [],
        'uploads' => [],
        'ratings' => []
    ];
    if ($user['warnings']) {
        $decoded = json_decode($user['warnings'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $notifications['warnings'] = $decoded;
        } else {
            $notifications['warnings'][] = $user['warnings'];
        }
    }

    $stmt = $pdo->prepare("
        SELECT m.model, u.username, m.created_at 
        FROM motorcycles m 
        JOIN users u ON m.user_id = u.id
        WHERE m.user_id != :user_id
          AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($uploads as $bike) {
        $notifications['uploads'][] = [
            'text' => "User '{$bike['username']}' uploaded a new motorcycle: '{$bike['model']}'",
            'time' => timeAgo($bike['created_at'])
        ];
    }

    $stmt = $pdo->prepare("
       SELECT r.rating, r.rated_at, u.username AS rater_username, m.model 
FROM motorcycle_ratings r

        JOIN motorcycles m ON r.motorcycle_id = m.id
        JOIN users u ON r.user_id = u.id
        WHERE m.user_id = :user_id
          AND r.user_id != :user_id
       AND r.rated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY r.rated_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ratings as $rating) {
        $action = ($rating['rating'] === 'like') ? 'liked' : 'disliked';
        $notifications['ratings'][] = [
            'text' => "User '{$rating['rater_username']}' {$action} your motorcycle: '{$rating['model']}'",
            'time' => timeAgo($rating['created_at'])
        ];
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Notifications</title>
<link rel="stylesheet" href="./style/dashboard.css" />
<style>
  .notifications-container {
    max-width: 700px;
    margin: 3rem auto;
    background: rgba(255, 255, 255, 0.95);
    padding: 2rem 2.5rem;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    color: #4e342e;
  }
  .notifications-container h1 {
    margin-bottom: 1.5rem;
    color: #6d4c41;
  }
  .notification-section {
    margin-bottom: 2rem;
  }
  .notification-section h2 {
    font-size: 1.2rem;
    margin-bottom: 0.75rem;
    color: #5d4037;
  }
  .notification {
    background: #fff3cd;
    border-left: 6px solid #ffecb5;
    padding: 1rem 1.5rem;
    margin-bottom: 0.75rem;
    border-radius: 6px;
    font-size: 1rem;
    color: #856404;
    position: relative;
  }
  .notification small {
    position: absolute;
    top: 8px;
    right: 15px;
    color: #a88b3c;
    font-size: 0.85rem;
  }
  .no-notifications {
    font-style: italic;
    color: #6c757d;
  }
  .btn-change-password {
  display: inline-block;
  padding: 6px 14px;
  margin-top: 5px;
  background-color: #d9534f; /* Bootstrap danger red */
  color: white;
  text-decoration: none;
  font-weight: bold;
  border-radius: 5px;
  transition: background-color 0.3s ease;
}

.btn-change-password:hover {
  background-color: #c9302c;
}

</style>
</head><body>

<div class="navbar">
  <div>Welcome, <strong><?= htmlspecialchars($user['username'] ?? $user['email']) ?></strong></div>
  <div class="navbar-actions">
    <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
    <a href="notifications.php" class="nav-link">🔔 Notifications</a>
    <a href="profile.php" class="nav-link">👤 Profile</a>
    <a href="logout.php" class="nav-link logout-link">Logout</a>
  </div>
</div>

<?php if (empty($notifications['warnings']) && empty($notifications['uploads']) && empty($notifications['ratings'])): ?>
  <div class="notifications-container">
    <h1>Your Notifications</h1>
    <p class="no-notifications">You have no notifications.</p>
  </div>
<?php else: ?>

  <?php if (!empty($notifications['warnings'])): ?>
  <div class="notifications-container">
    <h1>⚠️ Warnings</h1>
    <?php foreach ($notifications['warnings'] as $warning): ?>
      <div class="notification">
        <?= htmlspecialchars($warning) ?>
        <div style="margin-top: 10px;">
          <a href="profile.php" class="btn-change-password">Try to Change Password</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


  <?php if (!empty($notifications['uploads'])): ?>
    <div class="notifications-container">
      <h1>📦 New Motorcycles</h1>
      <?php foreach ($notifications['uploads'] as $upload): ?>
        <div class="notification">
          <?= htmlspecialchars($upload['text']) ?>
          <small><?= $upload['time'] ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($notifications['ratings'])): ?>
    <div class="notifications-container">
      <h1>👍 Likes / 👎 Dislikes</h1>
      <?php foreach ($notifications['ratings'] as $rating): ?>
        <div class="notification">
          <?= htmlspecialchars($rating['text']) ?>
          <small><?= $rating['time'] ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

</body>
