<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

require __DIR__ . "/includes/database.php";
require __DIR__ . "/includes/story-content.php";

$slug = trim($_GET["slug"] ?? "");

if ($slug === "") {
    http_response_code(404);
    exit("Folder not found.");
}

$stmt = $conn->prepare(
    "SELECT id, name, description
     FROM folders
     WHERE slug = ?
     LIMIT 1"
);

$stmt->bind_param("s", $slug);
$stmt->execute();

$result = $stmt->get_result();
$folder = $result->fetch_assoc();

if (!$folder) {
    http_response_code(404);
    exit("Folder not found.");
}

$stmt = $conn->prepare(
    "SELECT id, title, author, slug
     FROM stories
     WHERE folder_id = ? AND published = TRUE
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $folder["id"]);
$stmt->execute();

$stories_result = $stmt->get_result();

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($folder["name"], ENT_QUOTES, "UTF-8") ?> - Share Our Story</title>
  <script>
    (function () {
      try {
        var theme = localStorage.getItem("sos-color-mode");
        var themes = ["default", "high-contrast", "protanopia-safe", "deuteranopia-safe", "tritanopia-safe"];
        if (themes.indexOf(theme) !== -1 && theme !== "default") {
          document.documentElement.setAttribute("data-sos-theme", theme);
        }
      } catch (e) {}
    })();
  </script>
  <link rel="stylesheet" href="styles.css" />
</head>

<body>

  <header class="wrap header">

    <a href="./" class="brand" aria-label="Share Our Story home">
      <picture class="logo-mark">
        <source srcset="images/SOSLogo.webp" type="image/webp">
        <img
          src="images/SOSLogo.png"
          alt="Site Logo"
          class="logo"
          width="160"
          height="160"
        >
      </picture>
      <h1>Share Our Story</h1>
    </a>

    <nav class="nav">

      <a href="./">Home</a>
      <a href="about/">About Us</a>
      <a href="contact/">Contact Us</a>

      <div class="dropdown">
        <button class="dropbtn active" type="button">
          Resources ▾
        </button>

        <div class="dropdown-content">
          <a href="our-stories/" class="active">Our Stories</a>
          <a href="supporters/">Supporters</a>
          <a href="media/">Media</a>
          <a href="publications/">Publications</a>
        </div>
      </div>

      <a href="donate/">Donate</a>

    </nav>

  </header>

  <main class="wrap">

    <section class="card">

      <h2><?= htmlspecialchars($folder["name"], ENT_QUOTES, "UTF-8") ?></h2>

      <?php if (trim($folder["description"] ?? "") !== ""): ?>
        <p>
          <?= nl2br(htmlspecialchars(
              $folder["description"],
              ENT_QUOTES,
              "UTF-8"
          )) ?>
        </p>
      <?php endif; ?>

      <hr class="section-break">

      <?php if ($stories_result->num_rows > 0): ?>

        <ul class="story-list">

          <?php while ($story = $stories_result->fetch_assoc()): ?>

            <li>
              <a
                href="/<?= rawurlencode($story["slug"]) ?>/"
                class="story-link"
              >

                <span class="story-link-type">Story</span>

                <span class="story-link-title">
                  <?= htmlspecialchars(
                      $story["title"],
                      ENT_QUOTES,
                      "UTF-8"
                  ) ?>
                </span>

                <?php if (trim($story["author"]) !== ""): ?>
                  <span class="story-link-meta">
                    By <?= htmlspecialchars(
                        $story["author"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                  </span>
                <?php endif; ?>

              </a>
            </li>

          <?php endwhile; ?>

        </ul>

      <?php else: ?>

        <p>No published stories are currently in this folder.</p>

      <?php endif; ?>

      <hr class="section-break">

      <p>
        <a href="our-stories/" class="story-link">
          ← Back to Our Stories
        </a>
      </p>

    </section>

  </main>

  <footer class="wrap small footer">

    <div class="social-row">

      <a
        href="mailto:shareourstoryinfo@gmail.com"
        aria-label="Email"
      >
        <img src="images/envelope-circle.png" alt="Email">
      </a>

      <a
        href="https://www.facebook.com/profile.php?id=100089873220193"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Facebook"
      >
        <img src="images/facebook-circle.png" alt="Facebook">
      </a>

      <a
        href="#" data-coming-soon-social="Instagram"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Instagram"
      >
        <img src="images/instagram-circle.png" alt="Instagram">
      </a>

    </div>

    <p>© <span id="year"></span> Paul Lindoewood</p>

  </footer>

  <script src="script.js"></script>

</body>
</html>
