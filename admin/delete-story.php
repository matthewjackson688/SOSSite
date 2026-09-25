<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");


require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";

require_admin();

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit("Story not found.");
}

$stmt = $conn->prepare(
    "SELECT id, title, author
     FROM stories
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$story = $result->fetch_assoc();

if (!$story) {
    http_response_code(404);
    exit("Story not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $delete = $conn->prepare(
        "DELETE FROM stories
         WHERE id = ?"
    );

    $delete->bind_param("i", $id);

    if ($delete->execute()) {
        header("Location: index.php");
        exit;
    }

    $error = "Unable to delete the story.";
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Delete Story - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css" />
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Delete Story</h1>

      <?php if (!empty($error)): ?>
        <p role="alert">
          <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
      <?php endif; ?>

      <p>
        Are you sure you want to permanently delete this story?
      </p>

      <p>
        <strong>
          <?= htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8") ?>
        </strong>
      </p>

      <?php if (trim($story["author"]) !== ""): ?>
        <p>
          By <?= htmlspecialchars($story["author"], ENT_QUOTES, "UTF-8") ?>
        </p>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

        <p>
          <button type="submit">Yes, Delete This Story</button>
          &nbsp;
          <a href="index.php">Cancel</a>
        </p>
      </form>

    </section>
  </main>

</body>
</html>
