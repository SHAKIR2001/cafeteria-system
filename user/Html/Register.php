<?php
/**
 * Register.php — New Student Registration
 */
require_once '../../config.php';
require_once '../../db.php';

redirect_if_logged_in();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['fullName']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $student_id = trim($_POST['studentId']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $password   = $_POST['registerPassword']  ?? '';
    $confirm    = $_POST['confirmPassword']    ?? '';

    // Validation
    if (!$name || !$email || !$student_id || !$phone || !$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email uniqueness
        $chk = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($chk, 's', $email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins    = mysqli_prepare($conn,
                'INSERT INTO users (name, email, password, student_id, phone, role) VALUES (?, ?, ?, ?, ?, "student")'
            );
            mysqli_stmt_bind_param($ins, 'sssss', $name, $email, $hashed, $student_id, $phone);

            if (mysqli_stmt_execute($ins)) {
                $success = 'Registration successful! Redirecting to login…';
                header('Refresh: 2; URL=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create New Account</title>
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
  <main class="register-page">
    <section class="register-image-frame">
      <img src="../Images/register-food.jpg" alt="Cafeteria meal image" />
    </section>

    <section class="register-frame">
      <div class="register-box">
        <h1>Create New Account</h1>
        <p class="register-subtitle">Please fill in the details to register</p>

        <?php if ($error):   ?><p id="registerMessage" class="error"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p id="registerMessage" class="success"><?= e($success) ?></p><?php endif; ?>

        <form id="registerForm" method="POST" action="Register.php">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="fullName"
                 placeholder="Enter your full name"
                 value="<?= e($_POST['fullName'] ?? '') ?>" />

          <label for="email">Email</label>
          <input type="email" id="email" name="email"
                 placeholder="Enter your email"
                 value="<?= e($_POST['email'] ?? '') ?>" />

          <label for="studentId">Student ID</label>
          <input type="text" id="studentId" name="studentId"
                 placeholder="Enter your student ID"
                 value="<?= e($_POST['studentId'] ?? '') ?>" />

          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone"
                 placeholder="Enter your phone number"
                 value="<?= e($_POST['phone'] ?? '') ?>" />

          <label for="registerPassword">Password</label>
          <div class="register-password-box">
            <input type="password" id="registerPassword" name="registerPassword"
                   placeholder="Create a password" />
            <button type="button" class="register-eye-btn" data-target="registerPassword" aria-label="Toggle password">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>

          <label for="confirmPassword">Confirm Password</label>
          <div class="register-password-box">
            <input type="password" id="confirmPassword" name="confirmPassword"
                   placeholder="Confirm your password" />
            <button type="button" class="register-eye-btn" data-target="confirmPassword" aria-label="Toggle password">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>

          <button type="submit" class="register-btn">Register</button>

          <p class="login-link">
            Already have an account?
            <a href="login.php">Login Here</a>
          </p>
        </form>
      </div>
    </section>
  </main>

  <script>
    document.querySelectorAll('.register-eye-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var target = document.getElementById(btn.getAttribute('data-target'));
        var icon = btn.querySelector('i');
        if (target.type === 'password') {
          target.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          target.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    });
  </script>
</body>
</html>
