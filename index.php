<?php
session_start();
date_default_timezone_set('America/New_York');

$siteName = "230-Messenger";
$tagline = "A simple and secure chat application";

// Determine greeting
$hour = date("H");
$greeting = ($hour < 12) ? "Good Morning" : "Good Evening";

// Developer info
$developers = [
  ["name" => "Jarred Engleman", "role" => "NKU", "link" => "https://www.linkedin.com/in/jarred-engleman-799793267", "platform" => "LinkedIn"],
  ["name" => "Xander Murphy", "role" => "NKU", "link" => "https://www.linkedin.com/in/xander-murphy/", "platform" => "LinkedIn"],
  ["name" => "Jack Dixon", "role" => "NKU", "link" => "https://github.com/J4K20", "platform" => "GitHub"]
];

// Session
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : null;
$userRole = $isLoggedIn ? $_SESSION['role'] : null;
?>

<!DOCTYPE html>
<html lang="en">
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

      <!-- Main Content -->
      <section class="col-10 py-4 px-5">

        <header class="mb-4 text-center">
          <h1>Welcome to <?= $siteName; ?></h1>
          <p class="lead"><?= $tagline; ?></p>
        </header>

        <hr class="border-light">

        <section class="mb-5">
          <h2>About the App</h2>
          <p>
            <strong><?= $siteName; ?></strong> is a web-based messaging application developed for the ASE 230 midterm project.
            It demonstrates an understanding of PHP, MySQL database integration, user authentication, and Bootstrap-driven UI design.
          </p>
        </section>

        <section class="mb-5">
          <h2>Features</h2>
          <ul class="list-group list-group-flush text-start">
            <li class="list-group-item bg-dark text-light">Real-time styled chat interface</li>
            <li class="list-group-item bg-dark text-light">Secure login and registration system</li>
            <li class="list-group-item bg-dark text-light">Messages stored safely in a MySQL database</li>
            <li class="list-group-item bg-dark text-light">Selectable chat rooms</li>
            <li class="list-group-item bg-dark text-light">Clean, responsive UI with Bootstrap 5</li>
          </ul>
        </section>

        <section class="mb-5">
          <h2>Getting Started</h2>
          <ol class="text-start">
            <li>Click <strong>Sign Up</strong> to create your account.</li>
            <li>Login with your new credentials.</li>
            <li>Join a chat room and start messaging!</li>
          </ol>

          <?php if (!$isLoggedIn): ?>
            <a href="signup.php" class="btn btn-primary mt-3">Get Started</a>
          <?php else: ?>
            <a href="chat.php" class="btn btn-primary mt-3">Go to Chat</a>
          <?php endif; ?>
        </section>

        <section>
          <h2>Developers</h2>
          <p>Developed by:</p>
          <div class="row justify-content-center">

            <?php foreach ($developers as $dev): ?>
              <div class="col-md-3 m-2">
                <div class="card bg-secondary text-light border-0 shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title"><?= $dev["name"]; ?></h5>
                    <p class="card-text"><?= $dev["role"]; ?></p>
                    <a href="<?= $dev["link"]; ?>" target="_blank" class="btn btn-light btn-sm">
                      <?= $dev["platform"]; ?>
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

          </div>
        </section>

        <hr class="border-light my-5">

        <footer class="text-center">
          <p><?= $siteName; ?> | ASE 230 Midterm Project</p>
        </footer>

      </section>
    </div>
  </main>

</body>
</html>
