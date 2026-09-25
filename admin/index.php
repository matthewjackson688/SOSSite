<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");


require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/folders.php";

require_admin();

$result = $conn->query(
    "SELECT s.id, s.title, s.author, s.published, s.created_at,
            f.name AS folder_name
     FROM stories s
     LEFT JOIN folders f ON f.id = s.folder_id
     ORDER BY s.created_at DESC"
);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css" />
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Share Our Story Admin</h1>

      <p>
        Logged in as
        <strong><?= htmlspecialchars($_SESSION["admin_username"], ENT_QUOTES, "UTF-8") ?></strong>
      </p>

      <p>
        <a href="/admin/add-story.php">Add a new story</a>
        &nbsp; | &nbsp;
        <a href="/admin/folders.php">Story Folders</a>
  &nbsp; | &nbsp;
  <a href="/admin/supporters.php">Supporters</a>
        &nbsp; | &nbsp;
        <a href="logout.php">Log out</a>
      </p>

      <hr>

      <h2>Stories</h2>

      <?php if ($result && $result->num_rows > 0): ?>

        <ul class="story-list">

          <?php while ($story = $result->fetch_assoc()): ?>

            <li>
              <div class="story-link">

                <span class="story-link-type">
                  <?= $story["published"] ? "Live" : "Hidden" ?>
                </span>

                <span class="story-link-title">
                  <?= htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8") ?>
                </span>

                <?php if (trim($story["author"]) !== ""): ?>
                  <span class="story-link-meta">
                    By <?= htmlspecialchars($story["author"], ENT_QUOTES, "UTF-8") ?>
                  </span>
                <?php endif; ?>

                <?php if (!empty($story["folder_name"])): ?>
                  <span class="story-link-meta">
                    Folder:
                    <?= htmlspecialchars($story["folder_name"], ENT_QUOTES, "UTF-8") ?>
                  </span>
                <?php else: ?>
                  <span class="story-link-meta">
                    Standalone story
                  </span>
                <?php endif; ?>

                <p>
                  <a href="/admin/view-story.php?id=<?= (int)$story["id"] ?>">View</a>
                  &nbsp; | &nbsp;
                  <a href="/admin/edit-story.php?id=<?= (int)$story["id"] ?>">
                    Edit
                  </a>
                  &nbsp; | &nbsp;
                  <a href="/admin/delete-story.php?id=<?= (int)$story["id"] ?>">
                    Delete
                  </a>
                </p>

              </div>
            </li>

          <?php endwhile; ?>

        </ul>

      <?php else: ?>

        <p>No stories found.</p>

      <?php endif; ?>

      <hr>

      <p>
        <a href="../">← Back to Share Our Story</a>
      </p>

    </section>
  </main>

</body>
</html>
