<?php
session_start();

$error = "";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_otp = implode('', $_POST['otp'] ?? []);


    if (empty($input_otp)) {
        $error = "Please enter the OTP.";
    } elseif (!ctype_digit($input_otp) || strlen($input_otp) !== 6) {
        $error = "OTP must be a 6-digit number.";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $error = "OTP expired. Please login again.";
        session_unset(); 
        session_destroy();
        header("Refresh: 3; URL=index.php"); 
    } elseif ($input_otp == $_SESSION['otp']) {
        unset($_SESSION['otp'], $_SESSION['otp_expiry']);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OTP Verification</title>
  <link rel="stylesheet" href="./style/otp.css" />
</head>
<body>
<div class="container">
  <form class="login-form" method="post" action="otp_verification.php" autocomplete="off">
    <h2>OTP Verification</h2>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <p>Please enter the 6-digit OTP sent to your email.</p>
    <?php endif; ?>

    <div class="otp-boxes">
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
  <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
</div>
    <input type="submit" value="Verify OTP" />
  </form>
</div><script>
  const inputs = document.querySelectorAll('.otp-input');

  inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      if (input.value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === "Backspace" && !input.value && index > 0) {
        inputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, inputs.length);
      [...pasted].forEach((char, i) => {
        if (inputs[i]) {
          inputs[i].value = char;
        }
      });
      if (inputs[pasted.length - 1]) {
        inputs[pasted.length - 1].focus();
      }
    });
  });
</script>

</body>
</html>
