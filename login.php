<?php
session_start();
date_default_timezone_set('America/New_York');

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Signup success banner
$signupSuccess = isset($_GET['signup']) && $_GET['signup'] === "success";

// Error messages
$loginError = "";
if (isset($_GET['error']) && $_GET['error'] === "invalid") {
    $loginError = "Invalid username or password.";
}
if (isset($_GET['error']) && $_GET['error'] === "db") {
    $loginError = "Database error. Try again later.";
}

// Database connection
$conn = new mysqli("localhost", "root", "", "jarredupdate");

if ($conn->connect_error) {
    header("Location: login.php?error=db");
    exit;
}

// Handle POST (login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === "admin") {
                header("Location: admin.php");
                exit;
            }

            header("Location: index.php");
            exit;
        }
    }

    header("Location: login.php?error=invalid");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark">

<header class="text-center text-light mt-3">
    <h1>Login</h1>
</header>

<nav class="text-center mb-4">
    <a class="btn btn-primary" href="index.php">Index</a>
    <a class="btn btn-primary" href="signup.php">Sign Up</a>
</nav>

<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Account Login</h3>

                    <!-- Signup success alert -->
                    <?php if ($signupSuccess): ?>
                        <div class="alert alert-success text-center">
                            Account created successfully! Please log in.
                        </div>
                    <?php endif; ?>

                    <!-- Login error -->
                    <?php if (!empty($loginError)): ?>
                        <div class="alert alert-danger text-center">
                            <?= htmlspecialchars($loginError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <div class="form-text mb-3">
                            Don't have an account?
                            <a href="signup.php">Sign up here</a>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Log In</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
