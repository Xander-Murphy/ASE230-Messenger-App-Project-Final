<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

  $conn = new mysqli("localhost", "root", "", "230messengerredone");

  $msgID  = intval($_GET['id']);
  $userID = $_SESSION['user_id'];

  // Verify ownership
  $check = $conn->prepare("SELECT author_ID FROM messages WHERE id = ?");
  $check->bind_param("i", $msgID);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows === 0) { header("Location: chat.php"); exit; }

  $row = $result->fetch_assoc();
  if ($row['author_ID'] != $userID) { header("Location: chat.php"); exit; }

  // Delete message
  $delete = $conn->prepare("DELETE FROM messages WHERE id = ?");
  $delete->bind_param("i", $msgID);
  $delete->execute();

  header("Location: chat.php");
  exit;
?>
