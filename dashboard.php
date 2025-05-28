<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT 
            m.id, m.model, m.description, m.image_path, m.video_path, m.created_at, u.username,
            SUM(CASE WHEN mr.rating = 'like' THEN 1 ELSE 0 END) AS likes,
            SUM(CASE WHEN mr.rating = 'dislike' THEN 1 ELSE 0 END) AS dislikes,
            (SELECT rating FROM motorcycle_ratings WHERE user_id = :user_id AND motorcycle_id = m.id LIMIT 1) AS user_rating
        FROM motorcycles m
        JOIN users u ON m.user_id = u.id
        LEFT JOIN motorcycle_ratings mr ON m.id = mr.motorcycle_id
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $motors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Motorcycle Rating Dashboard</title>
<link rel="stylesheet" href="./style/dashboard.css" />
<style>
  .motor-card {
    border: 1px solid #ddd; 
    padding: 1em; 
    margin-bottom: 1em; 
    border-radius: 8px;
  }
  .motor-details {
    margin-top: 0.5em;
  }
  video {
    max-width: 100%;
    height: auto;
    display: block;
    margin-top: 0.5em;
  }
  .success-message {
    color: green;
    font-weight: bold;
    margin: 1em 0;
  }
  .error-message {
    color: red;
    font-weight: bold;
    margin: 1em 0;
  }
</style>
</head>
<body>

<div class="navbar">
  <div>Welcome, <strong><?= htmlspecialchars($user['username'] ?? $user['email']) ?></strong></div>
  <div class="navbar-actions">
    <a href="dashboard.php" class="nav-link">🏍️ Dashboard</a>
    <a href="notifications.php" class="nav-link">🔔 Notifications</a>
    <a href="profile.php" class="nav-link">👤 Profile</a>
   
    <a href="logout.php" class="nav-link logout-link">Logout</a>
  </div>
</div>

<div class="container">
  <section class="motor-upload">
      <h2>Upload a Motorcycle</h2>
      <form action="upload_motorcycle.php" method="post" enctype="multipart/form-data">
        <label for="model">Model (optional):</label><br />
        <input type="text" id="model" name="model" /><br /><br />

        <label for="description">Description (optional):</label><br />
        <textarea id="description" name="description" rows="4"></textarea><br /><br />

        <div class="media-inputs">
          <div class="media-field">
            <label for="image">📸 Image (required):</label>
            <input type="file" id="image" name="image" accept="image/*" required />
          </div>
          <div class="media-field">
            <label for="video">🎥 Video (optional):</label>
            <input type="file" id="video" name="video" accept="video/*" />
          </div>
          <button type="submit" class="upload-btn">🚀 Upload Motorcycle</button>
        </div>
      </form>

    <h2>Motorcycles</h2>

    <?php if (empty($motors)): ?>
      <p>No motorcycles posted yet.</p>
    <?php else: ?>
      <?php foreach ($motors as $motor): ?>
        <div class="motor-card" data-motor-id="<?= $motor['id'] ?>">
          <img src="<?= htmlspecialchars($motor['image_path']) ?>" alt="Image of <?= htmlspecialchars($motor['model'] ?: 'Motorcycle') ?>" />
          
          <?php if (!empty($motor['video_path'])): ?>
            <video controls>
              <source src="<?= htmlspecialchars($motor['video_path']) ?>" type="video/mp4" />
              Your browser does not support the video tag.
            </video>
          <?php endif; ?>

          <div class="motor-details">
            <h3><?= htmlspecialchars($motor['model'] ?: "Untitled Motorcycle") ?></h3>

            <?php if (trim($motor['description']) === ''): ?>
              <p><em>No description provided.</em></p>
            <?php else: ?>
              <p><?= nl2br(htmlspecialchars($motor['description'])) ?></p>
            <?php endif; ?>

            <small>Uploaded by: <?= htmlspecialchars($motor['username']) ?> on <?= date("M d, Y", strtotime($motor['created_at'])) ?></small>

            <div class="buttons">
              <button class="like-btn <?= $motor['user_rating'] === 'like' ? 'liked' : '' ?>" data-action="like" <?= $motor['user_rating'] ? 'disabled' : '' ?>>👍 <?= $motor['likes'] ?? 0 ?></button>
              <button class="dislike-btn <?= $motor['user_rating'] === 'dislike' ? 'disliked' : '' ?>" data-action="dislike" <?= $motor['user_rating'] ? 'disabled' : '' ?>>👎 <?= $motor['dislikes'] ?? 0 ?></button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</div>

<?php
if (isset($_SESSION['upload_success'])) {
    echo "<p class='success-message'>" . htmlspecialchars($_SESSION['upload_success']) . "</p>";
    unset($_SESSION['upload_success']);
}
if (isset($_SESSION['upload_error'])) {
    echo "<p class='error-message'>" . htmlspecialchars($_SESSION['upload_error']) . "</p>";
    unset($_SESSION['upload_error']);
}
?>

<script>
  document.querySelectorAll('.like-btn, .dislike-btn').forEach(button => {
    button.addEventListener('click', function() {
      const card = this.closest('.motor-card');
      const motorId = card.getAttribute('data-motor-id');
      const action = this.getAttribute('data-action');

      fetch('rate_motorcycle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ motorcycle_id: motorId, rating: action })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const likeBtn = card.querySelector('.like-btn');
          const dislikeBtn = card.querySelector('.dislike-btn');

          likeBtn.classList.remove('liked');
          dislikeBtn.classList.remove('disliked');

          if (data.user_rating === 'like') {
            likeBtn.classList.add('liked');
          } else if (data.user_rating === 'dislike') {
            dislikeBtn.classList.add('disliked');
          }

          likeBtn.textContent = `👍 ${data.likes}`;
          dislikeBtn.textContent = `👎 ${data.dislikes}`;
        } else {
          alert(data.message || 'Failed to rate motorcycle.');
        }
      })
      .catch(() => alert('Error rating motorcycle'));
    });
  });
</script>

</body>
</html>
