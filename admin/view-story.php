<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");


require __DIR__ . "/../includes/auth.php";
require_admin();

require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/story-content.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit("Invalid story ID.");
}

$stmt = $conn->prepare(
    "SELECT id, title, author, summary, content, published, created_at, updated_at
     FROM stories
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$story = $result->fetch_assoc();

$stmt->close();

if (!$story) {
    http_response_code(404);
    exit("Story not found.");
}

$content = sanitize_story_content($story["content"]);
$is_hidden = !$story["published"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>
    <?= htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8") ?>
    - Admin View - Share Our Story
  </title>

  <link rel="stylesheet" href="../styles.css" />

  <style>
    .admin-preview-notice {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border: 2px solid currentColor;
      border-radius: 4px;
    }

    .story-view img {
      max-width: 1100px;
      width: 100%;
      height: auto;
      display: block;
      margin: 1.5rem 0;
    }

    .story-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-top: 2rem;
    }
  </style>
</head>

<body>

  <header class="wrap header">
    <a href="../" class="brand" aria-label="Share Our Story home">
      <picture class="logo-mark">
        <source srcset="../images/SOSLogo.webp" type="image/webp">
        <img
          src="../images/SOSLogo.png"
          alt="Site Logo"
          class="logo"
          width="160"
          height="160"
        >
      </picture>

      <h1>Share Our Story</h1>
    </a>

    <nav class="nav">
      <a href="../">Home</a>
      <a href="../about/">About Us</a>
      <a href="../contact/">Contact Us</a>

      <div class="dropdown">
        <button class="dropbtn active" type="button">Resources ▾</button>

        <div class="dropdown-content">
          <a href="../our-stories/" class="active">Our Stories</a>
          <a href="../supporters/">Supporters</a>
          <a href="../media/">Media</a>
          <a href="../publications/">Publications</a>
        </div>
      </div>

      <a href="../donate/">Donate</a>
    </nav>
  </header>

  <main class="wrap">

    <section>

      <?php if ($is_hidden): ?>
        <div class="admin-preview-notice">
          <strong>Admin preview:</strong>
          This story is currently hidden from the public.
        </div>
      <?php else: ?>
        <div class="admin-preview-notice">
          <strong>Admin view:</strong>
          This story is currently live on the public website.
        </div>
      <?php endif; ?>

      <article>

        <h2>
          <?= htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8") ?>
        </h2>

        <?php if (trim($story["author"]) !== ""): ?>
          <p>
            <strong>By <?= htmlspecialchars($story["author"], ENT_QUOTES, "UTF-8") ?></strong>
          </p>
        <?php endif; ?>

        <?php if (trim($story["summary"]) !== ""): ?>
          <p>
            <?= htmlspecialchars($story["summary"], ENT_QUOTES, "UTF-8") ?>
          </p>
        <?php endif; ?>

        <div class="story-view">
          <?= $content ?>
        </div>

      </article>

      <div class="story-actions">
        <a href="index.php">← Back to Stories</a>

        <a href="/admin/edit-story.php?id=<?= (int)$story["id"] ?>">
          Edit Story
        </a>
      </div>

    </section>

  </main>

  <footer class="wrap small footer">
    <div class="social-row">
      <a href="mailto:shareourstoryinfo@gmail.com" aria-label="Email">
        <img src="../images/envelope-circle.png" alt="Email">
      </a>

      <a
        href="https://www.facebook.com/profile.php?id=100089873220193"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Facebook"
      >
        <img src="../images/facebook-circle.png" alt="Facebook">
      </a>

      <a
        href="#" data-coming-soon-social="Instagram"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Instagram"
      >
        <img src="../images/instagram-circle.png" alt="Instagram">
      </a>
    </div>
  </footer>

</body>
</html>
