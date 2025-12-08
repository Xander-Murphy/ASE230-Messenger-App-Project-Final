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
$conn = new mysqli("localhost", "root", "", "230messengerredone");
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// --- ENSURE DEFAULT CHAT ROOMS EXIST ---
$defaultRooms = ["Chat Room 1", "Chat Room 2", "Chat Room 3"];

foreach ($defaultRooms as $index => $roomName) {
	$roomID = $index + 1;

	$check = $conn->prepare("SELECT id FROM chat_rooms WHERE id = ?");
	$check->bind_param("i", $roomID);
	$check->execute();
	$result = $check->get_result();

	if ($result->num_rows === 0) {
		$insert = $conn->prepare("INSERT INTO chat_rooms (id, name) VALUES (?, ?)");
		$insert->bind_param("is", $roomID, $roomName);
		$insert->execute();
		$insert->close();
	}

	$check->close();
}

$username = $_SESSION['username'];
$userID = $_SESSION['user_id'];

// --- HANDLE CHAT ROOM SWITCHING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chatID'])) {
  $_SESSION['chatID'] = intval($_POST['chatID']);
}

// --- HANDLE MESSAGE EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {

    $msgID = intval($_POST['edit_id']);
    $newContent = trim($_POST['new_content']);
    $role = $_SESSION['role'];
    $userID = $_SESSION['user_id'];

    if ($newContent !== "") {

        if ($role === 'admin') {
            // Admin can edit ANY message
            $stmt = $conn->prepare("UPDATE messages SET content = ? WHERE id = ?");
            $stmt->bind_param("si", $newContent, $msgID);
        } else {
            // Users can only edit their own messages
            $stmt = $conn->prepare("UPDATE messages SET content = ? WHERE id = ? AND author_ID = ?");
            $stmt->bind_param("sii", $newContent, $msgID, $userID);
        }

        $stmt->execute();
        $stmt->close();
    }

    header("Location: chat.php");
    exit;
}

$chatID = $_SESSION['chatID'] ?? 1;

// --- HANDLE MESSAGE SUBMISSION ---
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

	header("Location: chat.php");
	exit;
}

// --- LOAD MESSAGES ---
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
</head>

<body class="d-flex flex-column min-vh-100 text-light bg-dark">

<main class="container-fluid">
	<div class="row">

		<aside class="col-2 bg-secondary p-3 min-vh-100 text-center">
			<h4 class="mb-3">Welcome, <?= htmlspecialchars($username); ?></h4>

			<nav class="d-grid gap-2">
				<a class="btn btn-primary" href="index.php">Index</a>
				<a class="btn btn-primary" href="chat.php">Refresh Chat</a>

				<form action="logout.php" method="POST">
					<button class="btn btn-danger w-100" type="submit">Sign Out</button>
				</form>
			</nav>

			<hr class="my-4">

			<h5>Chat Rooms</h5>

			<form method="POST" class="d-grid gap-2">
				<input type="hidden" name="chatID" value="1">
				<button class="btn <?= ($chatID == 1 ? 'btn-light' : 'btn-outline-light') ?>">Chat Room 1</button>
			</form>

			<form method="POST" class="d-grid gap-2 mt-2">
				<input type="hidden" name="chatID" value="2">
				<button class="btn <?= ($chatID == 2 ? 'btn-light' : 'btn-outline-light') ?>">Chat Room 2</button>
			</form>

			<form method="POST" class="d-grid gap-2 mt-2">
				<input type="hidden" name="chatID" value="3">
				<button class="btn <?= ($chatID == 3 ? 'btn-light' : 'btn-outline-light') ?>">Chat Room 3</button>
			</form>
		</aside>

		<section class="col-10 d-flex flex-column" style="height: calc(100vh - 10px);">

			<h3 class="text-center mt-3">Chat Room <?= htmlspecialchars($chatID) ?></h3>

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
									<button type="button" class="btn btn-sm btn-info" onclick="editMessage(<?= $msg['msg_id'] ?>)">Edit</button>
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
</script>

<script>
	function editMessage(id) {
		document.getElementById("view-" + id).classList.add("d-none");
		document.getElementById("edit-" + id).classList.remove("d-none");
	}

	function cancelEdit(id) {
		document.getElementById("edit-" + id).classList.add("d-none");
		document.getElementById("view-" + id).classList.remove("d-none");
	}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
