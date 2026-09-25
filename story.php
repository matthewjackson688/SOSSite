<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

require __DIR__ . "/includes/database.php";
require __DIR__ . "/includes/story-content.php";
require __DIR__ . "/includes/story-slugs.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$requestedSlug = isset($_GET["slug"]) ? trim((string)$_GET["slug"]) : "";

if ($id) {
    $stmt = $conn->prepare(
        "SELECT s.id, s.title, s.author, s.summary, s.content, s.image,
                s.slug, s.created_at, s.folder_id,
                f.name AS folder_name, f.slug AS folder_slug
         FROM stories s
         LEFT JOIN folders f ON f.id = s.folder_id
         WHERE s.id = ? AND s.published = TRUE
         LIMIT 1"
    );
    $stmt->bind_param("i", $id);
} elseif ($requestedSlug !== "") {
    $stmt = $conn->prepare(
        "SELECT s.id, s.title, s.author, s.summary, s.content, s.image,
                s.slug, s.created_at, s.folder_id,
                f.name AS folder_name, f.slug AS folder_slug
         FROM stories s
         LEFT JOIN folders f ON f.id = s.folder_id
         WHERE s.slug = ? AND s.published = TRUE
         LIMIT 1"
    );
    $stmt->bind_param("s", $requestedSlug);
} else {
    http_response_code(404);
    exit("Story not found.");
}

$stmt->execute();

$result = $stmt->get_result();
$story = $result->fetch_assoc();

if (!$story) {
    http_response_code(404);
    exit("Story not found.");
}

if ($id) {
    header(
        "Location: /" . rawurlencode($story["slug"]) . "/",
        true,
        301
    );
    exit;
}

$title = htmlspecialchars($story["title"], ENT_QUOTES, "UTF-8");
$author = htmlspecialchars($story["author"], ENT_QUOTES, "UTF-8");
$summary = htmlspecialchars($story["summary"] ?? "", ENT_QUOTES, "UTF-8");
$content = sanitize_story_content($story["content"]);
$image = htmlspecialchars($story["image"] ?? "", ENT_QUOTES, "UTF-8");
$canonicalUrl = "https://shareourstory.uk/" . rawurlencode($story["slug"]) . "/";

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $title ?> - Share Our Story</title>
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, "UTF-8") ?>">
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
  <link rel="stylesheet" href="/styles.css" />
</head>

<body>

  <header class="wrap header">
    <a href="/" class="brand" aria-label="Share Our Story home">
      <picture class="logo-mark">
        <source srcset="/images/SOSLogo.webp" type="image/webp">
        <img src="/images/SOSLogo.png" alt="Site Logo" class="logo" width="160" height="160">
      </picture>
      <h1>Share Our Story</h1>
    </a>

    <nav class="nav">
      <a href="/">Home</a>
      <a href="/about/">About Us</a>
      <a href="/contact/">Contact Us</a>

      <div class="dropdown">
        <button class="dropbtn active" type="button">Resources ▾</button>

        <div class="dropdown-content">
          <a href="/our-stories/" class="active">Our Stories</a>
          <a href="/supporters/">Supporters</a>
          <a href="/media/">Media</a>
          <a href="/publications/">Publications</a>
        </div>
      </div>

      <a href="/donate/">Donate</a>
    </nav>
  </header>

  <main class="wrap">
    <section class="card">

      <h2><?= $title ?></h2>

      <?php if (trim($author) !== ""): ?>
        <p class="story-link-meta">
          <em>By <?= $author ?></em>
        </p>
      <?php endif; ?>

      <?php if ($image): ?>
        <img src="<?= $image ?>" alt="" class="story-photo">
      <?php endif; ?>

      <?php if ($summary): ?>
        <p>
          <em><?= $summary ?></em>
        </p>
      <?php endif; ?>

      <div class="story-content">
        <?= $content ?>
      </div>

      <hr class="section-break">

      <p>
        <?php if (
            !empty($story["folder_id"]) &&
            !empty($story["folder_slug"]) &&
            !empty($story["folder_name"])
        ): ?>

          <a
            href="/folder.php?slug=<?= urlencode($story["folder_slug"]) ?>"
            class="story-link"
          >
            ← Back to <?= htmlspecialchars(
                $story["folder_name"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>
          </a>

        <?php else: ?>

          <a href="/our-stories/" class="story-link">
            ← Back to Our Stories
          </a>

        <?php endif; ?>
      </p>

    </section>
  </main>

  <footer class="wrap small footer">
    <div class="social-row">
      <a href="mailto:shareourstoryinfo@gmail.com" aria-label="Email">
        <img src="/images/envelope-circle.png" alt="Email">
      </a>
      <a href="https://www.facebook.com/profile.php?id=100089873220193" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
        <img src="/images/facebook-circle.png" alt="Facebook">
      </a>
      <a href="https://www.instagram.com/sos_shareourstory/" target="_blank" rel="noopener noreferrer" data-coming-soon-social="Instagram" aria-label="Instagram">
        <img src="/images/instagram-circle.png" alt="Instagram">
      </a>
      <a href="#" data-coming-soon-social="LinkedIn" aria-label="LinkedIn">
        <img src="/images/linkedin-circle.png" alt="LinkedIn">
      </a>
      <a href="#" data-coming-soon-social="Twitter / X" aria-label="Twitter / X">
        <img src="/images/twitter-circle.png" alt="Twitter / X">
      </a>
    </div>

    <p>© <span id="year"></span> Paul Lindoewood</p>
  </footer>

  <script src="/script.js"></script>

</body>
</html>
