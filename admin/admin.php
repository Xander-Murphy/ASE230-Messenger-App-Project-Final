<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('You are not the admin, please log into the admin account');
            window.location.href = '/ASE230-Messenger-App-Project-Final/login.php';
          </script>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "230messengerredone");

if ($conn->connect_error) {
    echo "<script>
            alert('Database connection failed');
            window.location.href = '/ASE230-Messenger-App-Project-Final/login.php';
          </script>";
    exit;
}

$stmt = $conn->prepare("SELECT id, username, role FROM users");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

<div class="container mt-5">
    <h2 class="text-center mb-4">User List</h2>

    <div class="mb-3 text-center">
        <a href="../index.php" class="btn btn-primary">Index</a>
        <a href="../chat.php" class="btn btn-primary">Chat</a>
    </div>

    <table class="table table-dark table-striped text-center">
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['role']) ?></td>
                    <td>
                        <a href="delete_user.php?id=<?= $row['id'] ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this user?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>

            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="3">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
