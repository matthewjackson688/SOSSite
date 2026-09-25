<?php

require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";

if (!empty($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, username, password_hash
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin["password_hash"])) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: index.php");
            exit;
        }

        $error = "Invalid username or password.";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css" />
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Admin Login</h1>

      <?php if ($error): ?>
        <p role="alert">
          <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
      <?php endif; ?>

      <form method="post" action="">

        <p>
          <label for="username">Username</label><br>
          <input
            type="text"
            id="username"
            name="username"
            autocomplete="username"
            required
          >
        </p>

        <p>
          <label for="password">Password</label><br>
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
          >
        </p>

        <p>
          <button type="submit">Log In</button>
        </p>

      </form>

      <p>
        <a href="../">← Back to Share Our Story</a>
      </p>

    </section>
  </main>

</body>
</html>
