<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: index.php");
        exit();
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
  <title>Profile</title>
  <link rel="stylesheet" href="./style/dashboard.css" />
  <style>
body {
  font-family: 'Segoe UI', sans-serif;
  background-color: #f4f4f4;
  color: #333;
  margin: 0;
  padding: 0;
}

.profile-container, .password-container {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 2em;
  margin: 2em auto;
  max-width: 600px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease-in-out;
}

.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1em;
}

.profile-header h2 {
  font-size: 1.6em;
  margin: 0;
  color: #007bff;
  letter-spacing: 0.5px;
}

.edit-icon {
  font-size: 1em;
  cursor: pointer;
  color: #007bff;
  padding: 6px 12px;
  border: 1px solid #007bff;
  border-radius: 6px;
  transition: all 0.3s ease;
  font-weight: bold;
}

.edit-icon:hover {
  background-color: #007bff;
  color: #fff;
}

.form-group {
  margin-bottom: 1.2em;
}

label {
  display: block;
  margin-bottom: 0.4em;
  font-weight: bold;
  color: #555;
}

input {
  width: 100%;
  padding: 0.7em;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fff;
  color: #333;
  transition: border 0.3s ease, box-shadow 0.3s ease;
}

input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 4px rgba(0, 123, 255, 0.4);
}

input[readonly] {
  background-color: #f9f9f9;
  color: #666;
}

.save-btn {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
  padding: 0.7em 1.5em;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 1em;
  display: none;
  transition: background 0.3s ease, transform 0.2s ease;
}

.save-btn:hover {
  background: linear-gradient(135deg, #0056b3, #003f88);
  transform: scale(1.03);
}

.password-container h3 {
  font-size: 1.4em;
  margin-bottom: 1em;
  color: #007bff;
}

.message {
  margin-top: 1em;
  font-weight: bold;
  padding: 0.6em 1em;
  border-radius: 5px;
}

.success {
  color: #28a745;
  background-color: #e9f8ec;
  border: 1px solid #28a745;
}

.error {
  color: #dc3545;
  background-color: #fcebea;
  border: 1px solid #dc3545;
}

input:hover {
  border-color: #007bff;
}
</style>


</head>
<body>

<div class="navbar">
  <div>Welcome, <strong><?= htmlspecialchars($user['username']) ?></strong></div>
  <div class="navbar-actions">
    <a href="dashboard.php" class="nav-link">🏍️ Dashboard</a>
    <a href="notifications.php" class="nav-link">🔔 Notifications</a>
    <a href="profile.php" class="nav-link">👤 Profile</a>

    <a href="logout.php" class="nav-link logout-link">Logout</a>
  </div>
</div>

<div class="profile-container">
  <div class="profile-header">
    <h2>My Profile</h2>
    <span class="edit-icon" title="Click to edit profile" id="edit-icon">✏️ Edit</span>
  </div>

  <form id="profile-form">
    <div class="form-group">
      <label>Username:</label>
      <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly />
    </div>
    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly />
    </div>
    <div class="form-group">
      <label>Member Since:</label>
      <input type="text" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" readonly />
    </div>
    <button type="submit" class="save-btn">💾 Save Changes</button>
    <div id="profile-message" class="message"></div>
  </form>
</div>

<div class="password-container">
  <h3>Change Password</h3>
  <form id="password-form">
    <div class="form-group">
      <label>Current Password:</label>
      <input type="password" name="current_password" required />
    </div>
    <div class="form-group">
      <label>New Password:</label>
      <input type="password" name="new_password" required />
    </div>
    <div class="form-group">
      <label>Confirm New Password:</label>
      <input type="password" name="confirm_password" required />
    </div>
    <button type="submit" class="save-btn">🔒 Update Password</button>
    <div id="password-message" class="message"></div>
  </form>
</div>
<script>
  const editIcon = document.getElementById('edit-icon');
  const formInputs = document.querySelectorAll('#profile-form input');
  const saveBtn = document.querySelector('#profile-form .save-btn');
  const profileMessage = document.getElementById('profile-message');
  const passwordMessage = document.getElementById('password-message');

  let isEditing = false;
  let originalValues = {};

  editIcon.addEventListener('click', () => {
    isEditing = !isEditing;

    if (isEditing) {
      // Save original values
      formInputs.forEach(input => {
        if (input.name === 'username' || input.name === 'email') {
          originalValues[input.name] = input.value;
          input.removeAttribute('readonly');
        }
      });
      saveBtn.style.display = 'inline-block';
      editIcon.textContent = '🔙 Back';
      editIcon.title = 'Cancel editing and restore values';
    } else {
      // Cancel changes and revert values
      formInputs.forEach(input => {
        if (input.name === 'username' || input.name === 'email') {
          input.value = originalValues[input.name] || '';
          input.setAttribute('readonly', true);
        }
      });
      saveBtn.style.display = 'none';
      editIcon.textContent = '✏️ Edit';
      editIcon.title = 'Click to edit profile';
    }
  });

  document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const response = await fetch('update_profile.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    profileMessage.textContent = data.message;
    profileMessage.className = `message ${data.success ? 'success' : 'error'}`;

    if (data.success) {
      formInputs.forEach(input => input.setAttribute('readonly', true));
      saveBtn.style.display = 'none';
      editIcon.textContent = '✏️ Edit';
      editIcon.title = 'Click to edit profile';
      isEditing = false;
    }
  });

  document.getElementById('password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const response = await fetch('change_password.php', {
      method: 'POST',
      body: formData
    }); 

    const data = await response.json();
    passwordMessage.textContent = data.message;
    passwordMessage.className = `message ${data.success ? 'success' : 'error'}`;

    if (data.success) {
      this.reset();
    }
  });
</script>

</body>
</html>
