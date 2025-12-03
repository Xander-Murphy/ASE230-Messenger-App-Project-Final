<?php
session_start();

// 1. Connect to MySQL
$conn = new mysqli("localhost", "root", "", "230messengerredone");

// Check the connection
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// 2. Receive & sanitize form data
$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$password = trim($_POST['password']);

// Basic backend validation (extra safety)
if (empty($username) || empty($email) || empty($password)) {
  die("Error: All fields are required.");
}

// 3. Check if username or email already exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo "<h2 style='color: red; text-align:center;'>Username or email already exists.</h2>";
  echo "<p style='text-align:center;'><a href='signup.php'>Go back</a></p>";
  exit;
}
$check->close();

// 4. Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 5. Insert the new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
  // Redirect to login page after successful signup
  header("Location: login.php?signup=success");
  exit;
} else {
  echo "<h2 style='color:red; text-align:center;'>Error: " . $stmt->error . "</h2>";
}

$stmt->close();
$conn->close();
?>
