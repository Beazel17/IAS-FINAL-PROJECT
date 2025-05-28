<?php
require './vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
require_once 'config.php';

$error = "";
$email = "";
$lockTime = 10; 
if (!isset($_SESSION['lock_time'])) {
    $_SESSION['lock_time'] = 0;
}

if (time() >= $_SESSION['lock_time'] && $_SESSION['lock_time'] != 0) {
    $lockedEmail = $_SESSION['locked_email'] ?? '';
    $reset = $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE email = :email");
    $reset->bindParam(":email", $lockedEmail);
    $reset->execute();

    $warningMsg = "WARNING: A suspicious login attempt was detected for your account ($lockedEmail). Please be cautious and change your password if this wasn't you.";
    $warn = $pdo->prepare("UPDATE users SET warnings = CONCAT(IFNULL(warnings, ''), :warningMsg, '\n') WHERE email = :email");
    $warn->bindParam(":warningMsg", $warningMsg);
    $warn->bindParam(":email", $lockedEmail);
    $warn->execute();

    $_SESSION['lock_time'] = 0;
    unset($_SESSION['locked_email']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (time() < $_SESSION['lock_time']) {
        $error = "Too many failed attempts. Please wait <span id='countdown'>" . ($_SESSION['lock_time'] - time()) . "</span> seconds.";
    } else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (empty($password)) {
            $error = "Password is required.";
        } else {
            $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['failed_attempts'] >= 3) {
                    $_SESSION['lock_time'] = time() + $lockTime;
                    $_SESSION['locked_email'] = $email;
                    $error = "Too many failed login attempts. Please wait <span id='countdown'>$lockTime</span> seconds.";
                } elseif (password_verify($password, $user['password'])) {
        
                    $update = $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE id = :id");
                    $update->bindParam(":id", $user['id']);
                    $update->execute();

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];

                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_expiry'] = time() + 300; 
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = '20221937@nbsc.edu.ph';
                        $mail->Password   = 'syoe oejr fksm jfci'; 
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('20221937@nbsc.edu.ph', 'CycleSpot');
                        $mail->addAddress($user['email']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Your Login OTP Code';
                        $mail->Body = "
                        <p>Dear User,</p>
                        <p>Thank you for choosing <strong>The BigPost</strong>.</p>
                        <p>Please use the following One-Time Password (OTP) to proceed with your action:</p>
                        <h2 style='color:#2a5298;'>$otp</h2>
                        <p><em>This code is valid for the next <strong>5 minutes</strong>.</em></p>
                        <p>If you did not request this code, please ignore this email — no further action is required.</p>
                        <br>
                        <p>Thank you for your attention and trust.</p>
                        <p>Best regards,<br>
                        <strong>The BigPost Team</strong></p>
                    ";
                    

                        $mail->send();

                        header("Location: otp_verification.php");
                        exit();
                    } catch (Exception $e) {
                        $error = "Failed to send OTP email. Please try again later.";
                    }

                } else {
                    $update = $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = :id");
                    $update->bindParam(":id", $user['id']);
                    $update->execute();

                    $remaining = 3 - ($user['failed_attempts'] + 1);
                    if ($remaining <= 0) {
                        $_SESSION['lock_time'] = time() + $lockTime;
                        $_SESSION['locked_email'] = $email;
                        $error = "Too many failed login attempts. Please wait <span id='countdown'>$lockTime</span> seconds.";
                    } else {
                        $error = "Invalid email or password. You have $remaining attempt(s) left.";
                    }
                }
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Page</title>
  <link rel="stylesheet" href="./style/index.css" />
  <style>
    #countdown { font-weight: bold; color: red; }
    .disabled-btn { background-color: #aaa; pointer-events: none; }
  </style>
</head>
<body>
<div class="container">
  <form class="login-form" method="post" action="index.php" autocomplete="off">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
      <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>" />
    <input type="password" name="password" placeholder="Password" required />
    <input type="submit" value="Login" id="login-btn" />

    <p><a href="register.php">Don't have an account? Register</a></p>
  </form>
</div>

<script>
  const countdownEl = document.getElementById('countdown');
  if (countdownEl) {
    let seconds = parseInt(countdownEl.innerText);
    const loginBtn = document.getElementById('login-btn');
    loginBtn.classList.add('disabled-btn');
    loginBtn.disabled = true;

    const interval = setInterval(() => {
      seconds--;
      countdownEl.innerText = seconds;
      if (seconds <= 0) {
        clearInterval(interval);
        location.reload();
      }
    }, 1000);
  }
</script>
</body>
</html>
