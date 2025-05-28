<?php
require_once 'config.php';
session_start();

$message = "";
$warning = "";

$username = $email = $password = $confirm_password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $confirm_password = trim($_POST["confirm_password"] ?? '');
    $captcha = trim($_POST["captcha"] ?? '');

    if (!isset($_SESSION['captcha_text']) || $captcha !== $_SESSION['captcha_text']) {
        $warning = "Invalid CAPTCHA. Please try again.";
    } elseif (empty($username)) {
        $warning = "Username is required.";
    } elseif ($password !== $confirm_password) {
        $warning = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $warning = "Invalid email format.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);

            try {
                $stmt->execute();
                header("Location: index.php?success=Registration successful! You can now log in.&email=" . urlencode($email));
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $warning = "This email or username is already registered.";
                } else {
                    $warning = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

$captcha_text = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
$_SESSION['captcha_text'] = $captcha_text;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register</title>
  <link rel="stylesheet" href="./style/register.css" />
</head>
<body>

<div class="page-wrapper">
  <div class="form-wrapper">
    <div class="register-container">
      <form method="post" action="register.php" autocomplete="off" id="register-form">
        <h2>Register</h2>

        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>" />
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>" />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />

        <label for="captcha" class="captcha-label">Enter the text below:</label>
        <div class="captcha-box"><?php echo $captcha_text; ?></div>
        <input type="text" name="captcha" placeholder="CAPTCHA" required autocomplete="off" />

        <input type="submit" value="Register" />

        <?php if ($warning): ?>
          <p class="message warning"><?php echo htmlspecialchars($warning); ?></p>
        <?php endif; ?>

        <?php if ($message): ?>
          <p class="message success"><?php echo $message; ?></p>
        <?php endif; ?>

        <button type="button" class="go-back" onclick="window.location.href='index.php'">Go Back to Login</button>

      </form>
    </div>
  </div>
</div>

</body>
</html>
