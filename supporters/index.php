<?php
require __DIR__ . "/../includes/database.php";

$result = $conn->query(
    "SELECT company_name, website_url, logo
     FROM supporters
     ORDER BY company_name"
);

$supporters = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Supporters - Share Our Story</title>
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

  <style>
    .supporters-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 2rem;
      margin-top: 2.5rem;
    }

    .supporter-card {
      text-align: center;
    }

    .supporter-logo-box {
      width: 220px;
      height: 140px;
      max-width: 100%;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .supporter-logo-box img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .supporter-name {
      margin: 0.75rem 0 0;
      font-size: 0.95rem;
      font-weight: 700;
    }

    @media (max-width: 800px) {
      .supporters-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 520px) {
      .supporters-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <link rel="canonical" href="https://shareourstory.uk/supporters/">
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
          <a href="../our-stories/">Our Stories</a>
          <a href="../supporters/" class="active">Supporters</a>
          <a href="../media/">Media</a>
          <a href="../publications/">Publications</a>
        </div>
      </div>

      <a href="../donate/">Donate</a>
    </nav>
  </header>
  <main class="wrap">
    <section class="card supporters-info">
      <h2>Get Involved and Support Us</h2>
      <p>
        Have these stories resonated with you? #SOS is about understanding the direct experience of disability in
        Africa and Wales. There are several ways you can get involved in and support #SOS.
      </p>

      <ol class="supporters-list" type="i">
        <li>
          If you are a disabled person or an organisation run by disabled people, we invite you to join the #SOS
          WhatsApp chat. <a href="../support-form/?return=supporters/&intent=whatsapp">Click here</a> (form to complete name, phone, and country).
        </li>
        <li>
          Whoever you are, please find us on our current social media outlets: Facebook and Instagram.
          Follow and like to keep up to date.
        </li>
        <li>
          If you are an organisation, we invite you to share your logo on our webpage.
          <a href="../support-form/?return=supporters/&intent=logo">Click here</a> (form to complete name of organisation, contact email, and country).
        </li>
        <li>
          Get in touch to see how your company/organisation/service could remove barriers and become more disability
          inclusive. <a href="../support-form/?return=supporters/&intent=inclusive">Click here</a> (form to complete name of organisation, contact email, and country).
        </li>
        <li>
          If you would like to donate funds, materials, or in-kind support, please <a href="../support-form/?return=supporters/&intent=donate">click here</a>
          (form to complete name of organisation, contact email, and country).
        </li>
      </ol>

      <?php if ($supporters): ?>

        <div class="supporters-grid" aria-label="Our supporters">

          <?php foreach ($supporters as $supporter): ?>

            <article class="supporter-card">

              <a
                href="<?= htmlspecialchars($supporter["website_url"], ENT_QUOTES, "UTF-8") ?>"
                target="_blank"
                rel="noopener noreferrer"
              >
                <div class="supporter-logo-box">
                  <img
                    src="<?= htmlspecialchars($supporter["logo"], ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($supporter["company_name"], ENT_QUOTES, "UTF-8") ?> logo"
                  >
                </div>
              </a>

              <p class="supporter-name">
                <?= htmlspecialchars($supporter["company_name"], ENT_QUOTES, "UTF-8") ?>
              </p>

            </article>

          <?php endforeach; ?>

        </div>

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

