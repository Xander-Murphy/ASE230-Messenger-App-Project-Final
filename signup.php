<?php
session_start();

// Initialize message variables
$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Connect to MySQL
  $conn = new mysqli("localhost", "root", "", "230messengerredone");

  if ($conn->connect_error) {
    $error = "Database connection failed.";
  } else {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Backend validation
    if (empty($username) || empty($email) || empty($password)) {
      $error = "All fields are required.";
    } else {
      // Check if username or email already exists
      $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
      $check->bind_param("ss", $username, $email);
      $check->execute();
      $check->store_result();

        if ($check->num_rows > 0) {
          $error = "Username or email already exists.";
        } else {
          // Hash password
          $hashed = password_hash($password, PASSWORD_DEFAULT);

          // Insert new user
          $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, created_at) 
            VALUES (?, ?, ?, NOW())
          ");

          $stmt->bind_param("sss", $username, $email, $hashed);

          if ($stmt->execute()) {
            $success = "Account created successfully! You can now log in.";
          } else {
            $error = "Error creating account: " . $stmt->error;
          }
            $stmt->close();
        }

        $check->close();
    }

    $conn->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-dark text-light">

  <!-- Header -->
  <header class="text-center py-4">
    <h1>Create Your Account</h1>
    <p class="lead">Join 230-Messenger today</p>
  </header>

  <!-- Navigation -->
  <nav class="text-center mb-3">
    <a class="btn btn-primary mx-2" href="index.php">Home</a>
    <a class="btn btn-primary mx-2" href="login.php">Login</a>
    <a class="btn btn-primary mx-2" href="chat.php">Chat</a>
  </nav>

  <!-- Signup Container -->
  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-md-6">

        <!-- Bootstrap Alerts -->
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="card bg-secondary text-light shadow border-0">
          <div class="card-body p-4">

            <h3 class="text-center mb-4">Sign Up</h3>

            <!-- Signup Form -->
            <form action="" method="POST">
              <!-- Username -->
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required minlength="3"maxlength="30"placeholder="Choose a unique username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
              </div>

              <!-- Email -->
              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" requiredplaceholder="you@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
              </div>

              <!-- Password -->
              <div class="mb-3 position-relative">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password" requiredminlength="6" placeholder="Enter a secure password">
                  <button class="btn btn-outline-light" type="button" id="togglePassword"> Show </button>
                </div>
                <small class="form-text text-light">Must be at least 6 characters.</small>
              </div>

              <!-- Login Link -->
              <p class="form-text text-light">
                Already have an account? 
                <a href="login.php" class="text-info">Sign in here</a>.
              </p>

              <!-- Submit Button -->
              <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary">Create Account</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Password Visibility Script -->
  <script>
    document.getElementById("togglePassword").addEventListener("click", function() {
      const passwordField = document.getElementById("password");
      const type = passwordField.type === "password" ? "text" : "password";
      passwordField.type = type;
      this.textContent = type === "password" ? "Show" : "Hide";
    });
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
