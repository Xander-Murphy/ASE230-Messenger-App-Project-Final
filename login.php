<?php
session_start();
date_default_timezone_set('America/New_York');

$conn = new mysqli("localhost", "root", "", "230messengerredone");

if ($conn->connect_error) {
    header("Location: login.php?error=db");
    exit;
}

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
                header("Location: admin/admin.php");
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
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

<div class="container mt-5" style="max-width: 400px;">
    <h2 class="text-center mb-4">Login</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            Invalid username or password.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input class="form-control mb-3" type="text" name="username" placeholder="Username" required>
        <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>

        <button class="btn btn-primary w-100">Login</button>
    </form>

</div>

</body>
</html>

