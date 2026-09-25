<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Our Stories - Share Our Story</title>
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
  <link rel="stylesheet" href="../styles.css" />
  <link rel="canonical" href="https://shareourstory.uk/our-stories/">
</head>

<body>

  <header class="wrap header">
    <a href="../" class="brand" aria-label="Share Our Story home">
      <picture class="logo-mark"><source srcset="../images/SOSLogo.webp" type="image/webp"><img src="../images/SOSLogo.png" alt="Site Logo" class="logo" width="160" height="160"></picture>
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
    <section class="card">
      <h2>Our Stories</h2>
      <p>
        Whilst the content of the themes and ideas discussed within the #SOS WhatsApp group are available for public
        consumption, the identity of individual story tellers or discussion participants remain confidential unless stated.
      </p>
      <p>
        Do these stories and discussions resonate with you? If you have direct experience of disability, you are very
        welcome to contribute to debate. If not, then feel free to get in touch to see how your
        company/organisation/service could remove the barriers discussed and become more disability inclusive.
      </p>

      <hr class="section-break">

      <?php
      require __DIR__ . "/../includes/database.php";

      $folders_result = $conn->query(
          "SELECT f.id, f.name, f.slug, f.description,
                  COUNT(s.id) AS story_count
           FROM folders f
           LEFT JOIN stories s
             ON s.folder_id = f.id
            AND s.published = TRUE
           GROUP BY f.id, f.name, f.slug, f.description
           HAVING COUNT(s.id) > 0
           ORDER BY f.name"
      );

      $standalone_result = $conn->query(
          "SELECT id, title, author, slug
           FROM stories
           WHERE published = TRUE
             AND folder_id IS NULL
           ORDER BY created_at DESC"
      );
      ?>

      <?php if ($folders_result && $folders_result->num_rows > 0): ?>


        <ul class="story-list">

          <?php while ($folder = $folders_result->fetch_assoc()): ?>

            <li>
              <a
                href="../folder.php?slug=<?= urlencode($folder["slug"]) ?>"
                class="story-link folder-link"
              >
                <span class="story-link-type">Folder</span>

                <span class="story-link-title">
                  <?= htmlspecialchars(
                      $folder["name"],
                      ENT_QUOTES,
                      "UTF-8"
                  ) ?>
                </span>

                <span class="story-link-meta">
                  <?= (int)$folder["story_count"] ?>
                  <?= (int)$folder["story_count"] === 1
                      ? "story"
                      : "stories" ?>
                </span>
              </a>
            </li>

          <?php endwhile; ?>

        </ul>

      <?php endif; ?>

      <?php if ($standalone_result && $standalone_result->num_rows > 0): ?>


        <ul class="story-list">

          <?php while ($story = $standalone_result->fetch_assoc()): ?>

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

      <?php elseif (
          (!$folders_result || $folders_result->num_rows === 0)
      ): ?>

        <ul class="story-list">
          <li>
            <div class="story-link">
              <span class="story-link-type">Stories</span>
              <span class="story-link-title">
                No stories published yet
              </span>
              <span class="story-link-meta">
                New stories will appear here.
              </span>
            </div>
          </li>
        </ul>

      <?php endif; ?>
    </section>
  </main>
  <footer class="wrap small footer">
    <div class="social-row">
      <a href="mailto:shareourstoryinfo@gmail.com" aria-label="Email">
        <img src="../images/envelope-circle.png" alt="Email">
      </a>
      <a href="https://www.facebook.com/profile.php?id=100089873220193" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
        <img src="../images/facebook-circle.png" alt="Facebook">
      </a>
      <a href="https://www.instagram.com/sos_shareourstory/" target="_blank" rel="noopener noreferrer" data-coming-soon-social="Instagram" aria-label="Instagram">
        <img src="../images/instagram-circle.png" alt="Instagram">
      </a>
    </div>

    <p>© <span id="year"></span> Paul Lindoewood</p>
  </footer>

  <script src="../script.js"></script>
</body>
</html>
