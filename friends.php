<?php
session_start();

$siteName = "230-Messenger";

$hour = date("H");
$greeting = ($hour < 12) ? "Good Morning" : "Good Evening";
// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$conn = new mysqli("localhost", "root", "", "230messengerredone");
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

$userID = $_SESSION['user_id'];
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : null;
$userRole = $isLoggedIn ? $_SESSION['role'] : null;

// --- SEND FRIEND REQUEST ---
if (isset($_POST['add_friend_username'])) {
  $inputUsername = trim($_POST['add_friend_username']);

	// Look up the target user
	$u = $conn->prepare("SELECT id FROM users WHERE username = ?");
	$u->bind_param("s", $inputUsername);
	$u->execute();
	$res = $u->get_result();

	if ($res->num_rows === 1) {
		$targetID = $res->fetch_assoc()['id'];

		// Prevent duplicates
		$check = $conn->prepare("
			SELECT id 
			FROM friendships
			WHERE (requester_id=? AND receiver_id=?)
				OR (requester_id=? AND receiver_id=?)
			LIMIT 1
		");
		$check->bind_param("iiii", $userID, $targetID, $targetID, $userID);
		$check->execute();
		$exists = $check->get_result();

		if ($exists->num_rows === 0) {
			$stmt = $conn->prepare("
				INSERT INTO friendships (requester_id, receiver_id, status)
				VALUES (?, ?, 'pending')
				");
			$stmt->bind_param("ii", $userID, $targetID);
			$stmt->execute();
			$stmt->close();
		}
	} else {
		echo "<div class='alert alert-danger mt-3'>User not found.</div>";
	}
}


// --- ACCEPT REQUEST ---
if (isset($_POST['accept'])) {
  $reqID = intval($_POST['accept']);

  $stmt = $conn->prepare("
    UPDATE friendships
    SET status='accepted'
    WHERE id=? AND receiver_id=?
    ");
  $stmt->bind_param("ii", $reqID, $userID);
  $stmt->execute();
}


// --- REJECT REQUEST ---
if (isset($_POST['reject'])) {
  $reqID = intval($_POST['reject']);

  $stmt = $conn->prepare("
    UPDATE friendships
    SET status='rejected'
    WHERE id=? AND receiver_id=?
    ");
    $stmt->bind_param("ii", $reqID, $userID);
    $stmt->execute();
}


// --- GET PENDING REQUESTS ---
$pending = $conn->prepare("
  SELECT friendships.id, users.username, users.id AS userid
  FROM friendships
  JOIN users ON friendships.requester_id = users.id
  WHERE friendships.receiver_id = ? AND friendships.status='pending'
");
$pending->bind_param("i", $userID);
$pending->execute();
$pendingRequests = $pending->get_result();


// --- GET FRIEND LIST (ACCEPTED) ---
$friends = $conn->prepare("
  SELECT 
    users.username, users.id 
  FROM friendships 
  JOIN users 
    ON users.id = 
      CASE 
        WHEN requester_id = ? THEN receiver_id 
        ELSE requester_id 
      END
    WHERE (requester_id = ? OR receiver_id = ?) 
    AND status='accepted'
");
$friends->bind_param("iii", $userID, $userID, $userID);
$friends->execute();
$friendList = $friends->get_result();

?>

<!DOCTYPE html>
<html lang ="en">
<head>
  <meta charset="UTF-8">
  <title><?= $siteName; ?> | Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100 text-light bg-dark">

  <main class="container-fluid">
    <div class="row">

    	<!-- Sidebar -->
      <aside class="col-2 bg-secondary p-3 min-vh-100 text-center">
        <h4 class="mb-3"><?= $siteName; ?></h4>

        <nav class="mb-4">
          <!-- Always visible -->
          <a class="btn btn-primary w-100 mb-2" href="index.php">Home</a>

          <?php if ($isLoggedIn): ?>
            <a class="btn btn-primary w-100 mb-2" href="chat.php">Chat</a>
            <a class="btn btn-primary w-100 mb-2" href="friends.php">Friends</a>

            <form action="logout.php" method="POST">
              <button class="btn btn-danger w-100 mt-2" type="submit">Sign Out</button>
            </form>

            <p class="text-light mt-3 small">
              Logged in as <strong><?= htmlspecialchars($username); ?></strong>
            </p>

          <?php else: ?>

            <a class="btn btn-primary w-100 mb-2" href="signup.php">Sign Up</a>
            <a class="btn btn-primary w-100 mb-2" href="login.php">Login</a>

          <?php endif; ?>
          <?php if ($userRole == "admin")
            echo '<a class="btn btn-primary w-100 mb-2" href="admin.php">Admin Panel</a>';
          ?>
        </nav>

        <p class="text-light small">
          <?= $greeting; ?>!<br>
          <?= date("l, F jS, Y"); ?>
        </p>
      </aside>

      <section class="col-10 py-4 px-5">
      	<h2>Your Friends</h2>
    
				<!-- Friend List -->
				<ul class="list-group mb-4">
					<?php while ($f = $friendList->fetch_assoc()): ?>
						<li class="list-group-item list-group-item-dark d-flex justify-content-between">
							<?= htmlspecialchars($f['username']) ?>
										
							<a class="btn btn-primary btn-sm" href="chat.php?friend=<?= $f['id'] ?>">
								Message
							</a>
						</li>
					<?php endwhile; ?>
				</ul>


				<h2>Pending Requests</h2>

				<ul class="list-group mb-4">
					<?php while ($p = $pendingRequests->fetch_assoc()): ?>
						<li class="list-group-item list-group-item-dark d-flex justify-content-between">
							<?= htmlspecialchars($p['username']) ?>

							<form method="POST">
								<button class="btn btn-success btn-sm" name="accept" value="<?= $p['id'] ?>">Accept</button>
								<button class="btn btn-danger btn-sm" name="reject" value="<?= $p['id'] ?>">Reject</button>
							</form>
						</li>
					<?php endwhile; ?>
				</ul>


				<h2>Add Friend</h2>

				<!-- Simple username search -->
				<form method="POST" class="d-flex">
					<input class="form-control me-2" type="text" name="add_friend_username" placeholder="Username…" required>
					<button class="btn btn-secondary">Send Request</button>
				</form>
			</section>
    </div>
	</main>
</body>
</html>
