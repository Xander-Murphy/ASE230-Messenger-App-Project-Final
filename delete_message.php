<?php
  session_start();
  if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) { header("Location: login.php"); 
    exit; 
}

  $conn = new mysqli("localhost", "root", "", "jarredupdate");

  $msgID  = intval($_GET['id']);
  $userID = $_SESSION['user_id'];
  $userRole = $_SESSION['role'];

  // Verify ownership
  $check = $conn->prepare("SELECT author_ID FROM messages WHERE id = ?");
  $check->bind_param("i", $msgID);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows === 0) { header("Location: chat.php"); exit; }

  $row = $result->fetch_assoc();
  $authorID = $row['author_ID'];

  // Delete message
  if ($authorID == $userID || $userRole === "admin") {
  $delete = $conn->prepare("DELETE FROM messages WHERE id = ?");
  $delete->bind_param("i", $msgID);
  $delete->execute();
  }

  header("Location: chat.php");
  exit;
?>
