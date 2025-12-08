<?php
session_start();
date_default_timezone_set('America/New_York');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}
if (!isset($_SESSION['role'])) {
  $_SESSION['role'] = 'user';
}

// --- DATABASE CONNECTION ---
$conn = new mysqli("localhost", "root", "", "jarredupdate");
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$userID = $_SESSION['user_id'];


// -------------------------------------------
// ENSURE PUBLIC ROOMS 1, 2, 3 EXIST
// -------------------------------------------
$defaultRooms = ["Chat Room 1", "Chat Room 2", "Chat Room 3"];

foreach ($defaultRooms as $index => $roomName) {
	$roomID = $index + 1; // Rooms 1, 2, 3

	// Check if room ID exists
	$check = $conn->prepare("SELECT id FROM chat_rooms WHERE id = ?");
	$check->bind_param("i", $roomID);
	$check->execute();
	$result = $check->get_result();

	if ($result->num_rows === 0) {
		// Create missing room
		$insert = $conn->prepare("INSERT INTO chat_rooms (id, name) VALUES (?, ?)");
		$insert->bind_param("is", $roomID, $roomName);
		$insert->execute();
		$insert->close();
	}

	$check->close();
}


// -------------------------------------------
// DETERMINE CURRENT ROOM
// -------------------------------------------

// If URL has room_id (private OR public)
if (isset($_GET['room_id'])) {
  $_SESSION['chatID'] = intval($_GET['room_id']);
}

// If switching via POST
if (isset($_POST['chatID'])) {
  $_SESSION['chatID'] = intval($_POST['chatID']);
}

// Default to public room #1
$chatID = $_SESSION['chatID'] ?? 1;


// -------------------------------------------
// SECURITY CHECK — USER MUST BE A MEMBER!
// Except for public rooms (1, 2, 3)
// -------------------------------------------
if ($chatID > 3) {
  $check = $conn->prepare("
    SELECT id FROM room_members
    WHERE room_id = ? AND user_id = ?
    LIMIT 1
  ");
  $check->bind_param("ii", $chatID, $userID);
  $check->execute();
  $res = $check->get_result();
	$check->close();

  if ($res->num_rows === 0) {
    die("<h2 class='text-danger'>You do not have permission to access this chat room.</h2>");
  }
}


// -------------------------------------------
// GET ALL ROOMS USER IS PART OF (public + private)
// -------------------------------------------
$roomQuery = $conn->prepare("
	SELECT chat_rooms.id, chat_rooms.name
	FROM chat_rooms
	LEFT JOIN room_members
		ON room_members.room_id = chat_rooms.id
	WHERE chat_rooms.id <= 3        -- always include public rooms
		OR room_members.user_id = ?  -- include private rooms
	GROUP BY chat_rooms.id
	ORDER BY chat_rooms.id ASC
");
$roomQuery->bind_param("i", $userID);
$roomQuery->execute();

$roomResult = $roomQuery->get_result();

// FIX: Load result set into array to avoid pointer exhaustion
$rooms = [];
while ($r = $roomResult->fetch_assoc()) {

	// If this is a private chat (id > 3), generate dynamic name:
	if ($r['id'] > 3) {

		// Fetch both users in the room
		$userQuery = $conn->prepare("
			SELECT users.username 
			FROM room_members 
			JOIN users ON users.id = room_members.user_id
			WHERE room_members.room_id = ?
		");
		$userQuery->bind_param("i", $r['id']);
		$userQuery->execute();
		$userResult = $userQuery->get_result();

		$names = [];
		while ($u = $userResult->fetch_assoc()) {
			$names[] = $u['username'];
		}

		// Create readable private room name: "UserA & UserB"
		if (count($names) === 2) {
			$r['name'] = $names[0] . " & " . $names[1];
		} else {
			$r['name'] = "Private Chat";
		}
}

	$rooms[] = $r;
}



// -------------------------------------------
// MESSAGE EDITING
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {

    $msgID = intval($_POST['edit_id']);
    $newContent = trim($_POST['new_content']);
    $role = $_SESSION['role'];
    $userID = $_SESSION['user_id'];

    if ($newContent !== "") {
      $stmt = $conn->prepare("
    	UPDATE messages
    	SET content = ?
    	WHERE id = ? AND author_ID = ?
			");
				$stmt->bind_param("sii", $newContent, $msgID, $userID);

        $stmt->execute();
        $stmt->close();
    }
	if ($newContent !== "") {
		$stmt = $conn->prepare("
			UPDATE messages
			SET content = ?
			WHERE id = ? AND author_ID = ?
		");
		$stmt->bind_param("sii", $newContent, $msgID, $userID);
		$stmt->execute();
		$stmt->close();
	}

	header("Location: chat.php?room_id=" . $chatID);
	exit;
}


// -------------------------------------------
// MESSAGE SUBMISSION
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

	$content = trim($_POST['message']);

	if ($content !== "") {
		$stmt = $conn->prepare("
			INSERT INTO messages (author_ID, content, room_id, created_at)
			VALUES (?, ?, ?, NOW())
		");
		$stmt->bind_param("isi", $userID, $content, $chatID);
		$stmt->execute();
		$stmt->close();
	}

	header("Location: chat.php?room_id=" . $chatID);
	exit;
}

// --- LOAD MESSAGES WITH USERNAMES ---
$messages = [];

$stmt = $conn->prepare("
  SELECT messages.id AS msg_id, messages.author_ID, users.username, messages.content, messages.created_at
  FROM messages
  JOIN users ON messages.author_ID = users.id
  WHERE messages.room_id = ?
  ORDER BY messages.id ASC
");
$stmt->bind_param("i", $chatID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $messages[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat Application</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
    .rooms-scroll {
      max-height: 50vh;       /* prevents sidebar from getting too tall */
      overflow-y: auto;       /* enables vertical scrolling */
      padding-right: 4px;     /* avoid scrollbar overlap */
    }
	</style>
</head>

<body class="d-flex flex-column min-vh-100 text-light bg-dark">

<main class="container-fluid">
	<div class="row">

		<aside class="col-2 bg-secondary p-3 min-vh-100 text-center">
			<h4 class="mb-3">Welcome, <?= htmlspecialchars($username); ?></h4>

			<nav class="d-grid gap-2 mb-4">
				<a class="btn btn-primary" href="index.php">Index</a>
				<a class="btn btn-primary" href="chat.php">Refresh Chat</a>
				<a class="btn btn-primary" href="friends.php">Friends</a>

				<form action="logout.php" method="POST">
					<button class="btn btn-danger w-100" type="submit">Sign Out</button>
				</form>
			</nav>

			<hr>

			<h5>Chat Rooms</h5>

			<!-- FIX: Loop through stored room array -->
			<div class="rooms-scroll mt-2">
				<?php foreach ($rooms as $room): ?>
					<a href="chat.php?room_id=<?= $room['id'] ?>"
						class="btn d-block mt-2 <?= ($chatID == $room['id'] ? 'btn-light' : 'btn-outline-light') ?>">
						<?= htmlspecialchars($room['name']) ?>
					</a>
				<?php endforeach; ?>
			</div>
		</aside>

		<section class="col-10 d-flex flex-column" style="height: calc(100vh - 10px);">

			<h3 class="text-center mt-3">
				<?= htmlspecialchars(count($rooms) > 0 ? "Chat Room $chatID" : "Chat") ?>
			</h3>

			<!-- Messages -->
		<ul id="chatMessages" class="list-unstyled mb-3 mt-3 flex-grow-1 overflow-auto px-3">
			<?php foreach ($messages as $msg): ?>
				<li class="list-group-item bg-dark text-light text-break border-0 mb-2" id="msg-<?= $msg['msg_id'] ?>">
					<div id="view-<?= $msg['msg_id'] ?>">
						<strong><?= htmlspecialchars($msg['username']); ?></strong>
						<span class="text-secondary" style="font-size:0.75em;">
							<?= date("g:i A", strtotime($msg['created_at'])); ?>
						</span>

						<?php if ($msg['author_ID'] == $userID || $_SESSION['role'] === 'admin'): ?>
							<span class="float-end">
								<?php if ($msg['author_ID'] == $userID): ?>
    							<button type="button" class="btn btn-sm btn-info" onclick="editMessage(<?= $msg['msg_id'] ?>)">Edit</button>
								<?php endif; ?>
								<a href="delete_message.php?id=<?= $msg['msg_id'] ?>"
									class="btn btn-sm btn-danger"
									onclick="return confirm('Delete message?');">
									Delete
								</a>
							</span>
						<?php endif; ?>

						<br>
							<span id="content-<?= $msg['msg_id'] ?>">
								<?= htmlspecialchars($msg['content']); ?>
							</span>
					</div>

					<!-- Edit Form -->
					<form id="edit-<?= $msg['msg_id'] ?>" method="POST" class="d-none mt-2">
						<input type="hidden" name="edit_id" value="<?= $msg['msg_id'] ?>">
						<textarea name="new_content" class="form-control" required><?= htmlspecialchars($msg['content']); ?></textarea>
						<div class="mt-2">
							<button type="submit" class="btn btn-success btn-sm">Save</button>
							<button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit(<?= $msg['msg_id'] ?>)">Cancel</button>
						</div>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>

			<!-- Message Input -->
		<form method="POST" class="input-group mb-3 mt-auto px-3">
			<input type="text" name="message" class="form-control" placeholder="Type your message..." required autofocus>
			<button class="btn btn-primary" type="submit">Send</button>
		</form>

		</section>

	</div>
</main>

<script>
	const chatBox = document.getElementById("chatMessages");
	chatBox.scrollTop = chatBox.scrollHeight;

	function editMessage(id) {
		document.getElementById("view-" + id).classList.add("d-none");
		document.getElementById("edit-" + id).classList.remove("d-none");
	}

	function cancelEdit(id) {
		document.getElementById("edit-" + id).classList.add("d-none");
		document.getElementById("view-" + id).classList.remove("d-none");
	}
</script>

</body>
</html>
