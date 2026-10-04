<?php

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin();

$message = "";
$error = "";

$currentFeaturedId = "";

$result = $conn->query(
    "SELECT setting_value
     FROM site_settings
     WHERE setting_name = 'featured_story_id'
     LIMIT 1"
);

if ($result && ($row = $result->fetch_assoc())) {
    $currentFeaturedId = (string)($row["setting_value"] ?? "");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($token)) {
        $error = "Invalid security token. Please try again.";
    } else {
        $selectedId = trim((string)($_POST["featured_story_id"] ?? ""));

        if ($selectedId !== "" && !ctype_digit($selectedId)) {
            $error = "Please select a valid story.";
        } elseif ($selectedId !== "") {
            $storyStmt = $conn->prepare(
                "SELECT id
                 FROM stories
                 WHERE id = ?
                   AND published = TRUE
                 LIMIT 1"
            );

            $storyId = (int)$selectedId;
            $storyStmt->bind_param("i", $storyId);
            $storyStmt->execute();
            $storyResult = $storyStmt->get_result();

            if (!$storyResult->fetch_assoc()) {
                $error = "That story is not available as a published story.";
            }

            $storyStmt->close();
        }

        if ($error === "") {
            $stmt = $conn->prepare(
                "INSERT INTO site_settings (setting_name, setting_value)
                 VALUES ('featured_story_id', ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );

            $stmt->bind_param("s", $selectedId);

            if ($stmt->execute()) {
                $currentFeaturedId = $selectedId;
                $message = "Featured story updated.";
            } else {
                $error = "Unable to save the featured story.";
            }

            $stmt->close();
        }
    }
}

$stories = [];

$result = $conn->query(
    "SELECT id, title, author, slug
     FROM stories
     WHERE published = TRUE
     ORDER BY title ASC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Featured Story - Admin - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css">
  <script src="../script.js" defer></script>
</head>
<body>

<header class="header">
  <a href="../" class="brand">
    <img src="../images/logo.png" alt="Share Our Story logo" class="logo-mark">
    <span class="brand-text">Share Our Story</span>
  </a>

  <nav class="nav">
    <a href="index.php">Admin Home</a>
    <a href="add-story.php">Add a new story</a>
    <a href="folders.php">Story Folders</a>
    <a href="featured-story.php">Featured Story</a>
    <a href="supporters.php">Supporters</a>
    <a href="logout.php">Log out</a>
  </nav>
</header>

<main class="wrap">
  <section class="card">
    <h1>Featured Story</h1>

    <p>
      Select the story that should appear as the featured story on the homepage.
      You can change this whenever you want.
    </p>

    <?php if ($message !== ""): ?>
      <p class="success-message">
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
      </p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
      <p class="error-message">
        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
      </p>
    <?php endif; ?>

    <form method="post">
      <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
      >

      <label for="featured_story_id">
        <strong>Featured story</strong>
      </label>

      <select
        id="featured_story_id"
        name="featured_story_id"
        required
      >
        <option value="">Select a story...</option>

        <?php foreach ($stories as $story): ?>
          <option
            value="<?= (int)$story["id"] ?>"
            <?= (string)$story["id"] === $currentFeaturedId ? "selected" : "" ?>
          >
            <?= htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8") ?>
            <?php if (!empty($story["author"])): ?>
              — <?= htmlspecialchars($story["author"], ENT_QUOTES, "UTF-8") ?>
            <?php endif; ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Save Featured Story</button>
    </form>

    <?php if (empty($stories)): ?>
      <p class="small">
        There are currently no published stories available to feature.
      </p>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
