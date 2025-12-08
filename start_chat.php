<?php
session_start();
$conn = new mysqli("localhost", "root", "", "jarredupdate");

if (!isset($_SESSION['user_id'])) {
  die("Not logged in");
}

$me = $_SESSION['user_id'];
$friend = $_GET['user_id']; // the person you clicked "Message" on

// 1. CHECK IF ROOM ALREADY EXISTS
$sql = "
  SELECT room_id
  FROM room_members
  WHERE user_id IN (?, ?)
  GROUP BY room_id
  HAVING COUNT(*) = 2
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $me, $friend);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($existing_room);

if ($stmt->num_rows > 0) {
    // ROOM EXISTS → redirect to it
    $stmt->fetch();
    header("Location: chat.php?room_id=" . $existing_room);
    exit;
}

// 2. CREATE NEW ROOM
$createRoom = $conn->prepare("INSERT INTO chat_rooms (name) VALUES (?)");
$roomName = "Private Chat";
$createRoom->bind_param("s", $roomName);
$createRoom->execute();
$newRoomID = $createRoom->insert_id;

// 3. INSERT BOTH USERS INTO room_members
$addMembers = $conn->prepare("
    INSERT INTO room_members (room_id, user_id)
    VALUES (?, ?), (?, ?)
");
$addMembers->bind_param("iiii", $newRoomID, $me, $newRoomID, $friend);
$addMembers->execute();

// 4. REDIRECT TO NEW ROOM
header("Location: chat.php?room_id=" . $newRoomID);
exit;
?>
