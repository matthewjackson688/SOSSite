<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";

require_admin();

$error = "";
$success = "";

$uploadDir = __DIR__ . "/../uploads/supporters";
$uploadUrl = "/uploads/supporters";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function supporter_upload_logo(string $field, string $uploadDir, string $uploadUrl): array
{
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE) {
        return ["", ""];
    }

    if ($_FILES[$field]["error"] !== UPLOAD_ERR_OK) {
        return ["", "Unable to upload the logo."];
    }

    if ($_FILES[$field]["size"] > 20 * 1024 * 1024) {
        return ["", "The logo must be 20 MB or smaller."];
    }

    $tmp = $_FILES[$field]["tmp_name"];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
    ];

    if (!isset($allowed[$mime])) {
        return ["", "Logo must be a JPEG, PNG or WebP image."];
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        return ["", "The uploaded file is not a valid image."];
    }

    $source = match ($mime) {
        "image/jpeg" => @imagecreatefromjpeg($tmp),
        "image/png" => @imagecreatefrompng($tmp),
        "image/webp" => @imagecreatefromwebp($tmp),
        default => false,
    };

    if ($source === false) {
        return ["", "Unable to process the uploaded logo."];
    }

    $width = imagesx($source);
    $height = imagesy($source);

    /*
     * Keep logos at a sensible maximum size while preserving
     * their original proportions.
     */
    $maxDimension = 1100;
    $scale = min(1, $maxDimension / max($width, $height));

    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $destination = imagecreatetruecolor($newWidth, $newHeight);

    imagealphablending($destination, false);
    imagesavealpha($destination, true);
    $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
    imagefill($destination, 0, 0, $transparent);

    imagecopyresampled(
        $destination,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    $filename = bin2hex(random_bytes(16)) . ".webp";
    $path = $uploadDir . "/" . $filename;

    $saved = imagewebp($destination, $path, 88);

    imagedestroy($source);
    imagedestroy($destination);

    if (!$saved) {
        return ["", "Unable to save the uploaded logo."];
    }

    return [$uploadUrl . "/" . $filename, ""];
}

function delete_supporter_logo(string $logo): void
{
    if (strpos($logo, "/uploads/supporters/") !== 0) {
        return;
    }

    $filename = basename($logo);
    $path = __DIR__ . "/../uploads/supporters/" . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $companyName = trim($_POST["company_name"] ?? "");
        $websiteUrl = trim($_POST["website_url"] ?? "");
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($companyName === "") {
            $error = "Company name is required.";
        } elseif ($websiteUrl === "" || !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            $error = "Please enter a valid website address.";
        } elseif (!preg_match("#^https?://#i", $websiteUrl)) {
            $error = "Website address must begin with http:// or https://.";
        } elseif ($action === "create" && (!isset($_FILES["logo"]) || $_FILES["logo"]["error"] === UPLOAD_ERR_NO_FILE)) {
            $error = "A logo is required.";
        } else {
            [$logo, $uploadError] = supporter_upload_logo(
                "logo",
                $uploadDir,
                $uploadUrl
            );

            if ($uploadError !== "") {
                $error = $uploadError;
            } elseif ($action === "create") {
                $stmt = $conn->prepare(
                    "INSERT INTO supporters (company_name, website_url, logo)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $companyName, $websiteUrl, $logo);

                if ($stmt->execute()) {
                    $success = "Supporter added successfully.";
                } else {
                    delete_supporter_logo($logo);
                    $error = "Unable to add the supporter.";
                }
            } elseif ($id) {
                $stmt = $conn->prepare(
                    "SELECT logo FROM supporters WHERE id = ?"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();

                if (!$existing) {
                    delete_supporter_logo($logo);
                    $error = "Supporter not found.";
                } else {
                    if ($logo === "") {
                        $logo = $existing["logo"];
                    }

                    $stmt = $conn->prepare(
                        "UPDATE supporters
                         SET company_name = ?, website_url = ?, logo = ?
                         WHERE id = ?"
                    );
                    $stmt->bind_param("sssi", $companyName, $websiteUrl, $logo, $id);

                    if ($stmt->execute()) {
                        if ($logo !== $existing["logo"]) {
                            delete_supporter_logo($existing["logo"]);
                        }
                        $success = "Supporter updated successfully.";
                    } else {
                        delete_supporter_logo($logo);
                        $error = "Unable to update the supporter.";
                    }
                }
            }
        }
    } elseif ($action === "delete") {
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $conn->prepare(
                "SELECT logo FROM supporters WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $supporter = $stmt->get_result()->fetch_assoc();

            if (!$supporter) {
                $error = "Supporter not found.";
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM supporters WHERE id = ?"
                );
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    delete_supporter_logo($supporter["logo"]);
                    $success = "Supporter deleted successfully.";
                } else {
                    $error = "Unable to delete the supporter.";
                }
            }
        }
    }
}

$result = $conn->query(
    "SELECT id, company_name, website_url, logo
     FROM supporters
     ORDER BY company_name"
);

$supporters = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Supporters - Share Our Story Admin</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .supporter-admin-card {
      border: 1px solid #777;
      border-radius: 10px;
      padding: 1rem;
      margin: 1rem 0;
    }

    .supporter-admin-logo {
      display: block;
      width: 220px;
      height: 140px;
      object-fit: contain;
      margin-bottom: 1rem;
    }

    .supporter-admin-card input {
      font: inherit;
      max-width: 100%;
      box-sizing: border-box;
    }

    .supporter-admin-card input[type="text"],
    .supporter-admin-card input[type="url"] {
      width: min(100%, 600px);
    }

    .supporter-admin-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .supporter-logo-help {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-left: 0.5rem;
      font-size: 0.9rem;
    }

    .supporter-info {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.25rem;
      height: 1.25rem;
      border: 1px solid currentColor;
      border-radius: 50%;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: help;
    }

    .supporter-info-tooltip {
      position: absolute;
      z-index: 10;
      left: 50%;
      bottom: calc(100% + 0.5rem);
      width: 280px;
      padding: 0.75rem;
      border: 1px solid #777;
      border-radius: 6px;
      background: #fff;
      color: #111;
      font-size: 0.85rem;
      font-weight: 400;
      line-height: 1.4;
      text-align: left;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      transform: translateX(-50%);
      visibility: hidden;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.15s ease;
    }

    .supporter-info:hover .supporter-info-tooltip,
    .supporter-info:focus .supporter-info-tooltip {
      visibility: visible;
      opacity: 1;
    }
  </style>
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Supporters</h1>

      <p>
        <a href="/admin/">← Back to Admin</a>
      </p>

      <?php if ($error): ?>
        <p role="alert">
          <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
      <?php endif; ?>

      <?php if ($success): ?>
        <p role="status">
          <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
        </p>
      <?php endif; ?>

      <h2>Add a supporter</h2>

      <form method="post" enctype="multipart/form-data">

        <input
          type="hidden"
          name="csrf_token"
          value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <input type="hidden" name="action" value="create">

        <p>
          <label for="company_name">Company / organisation name</label><br>
          <input id="company_name" name="company_name" type="text" required>
        </p>

        <p>
          <label for="website_url">Website address</label><br>
          <input
            id="website_url"
            name="website_url"
            type="url"
            placeholder="https://example.com"
            required
          >
        </p>

        <p>
          <label for="logo">Logo</label><br>
          <input
            id="logo"
            name="logo"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            required
          >

          <span class="supporter-logo-help">
            <a
              href="https://www.remove.bg/"
              target="_blank"
              rel="noopener noreferrer"
            >For removing logo backgrounds</a>

            <span
              class="supporter-info"
              tabindex="0"
              role="img"
              aria-label="Logo filename information"
            >i<span class="supporter-info-tooltip" role="tooltip">Make sure you name the logo files accordingly to the website. It looks unprofessional when someone looks at the image properties and the file is called 'image41234.png', for example</span></span>
          </span>
        </p>

        <button type="submit">Add Supporter</button>

      </form>

      <hr>

      <h2>Existing supporters</h2>

      <?php if (!$supporters): ?>

        <p>No supporters have been added yet.</p>

      <?php else: ?>

        <?php foreach ($supporters as $supporter): ?>

          <div class="supporter-admin-card">

            <img
              src="<?= htmlspecialchars($supporter["logo"], ENT_QUOTES, "UTF-8") ?>"
              alt=""
              class="supporter-admin-logo"
            >

            <form method="post" enctype="multipart/form-data">

              <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
              >

              <input type="hidden" name="action" value="update">

              <input
                type="hidden"
                name="id"
                value="<?= (int)$supporter["id"] ?>"
              >

              <p>
                <label for="name<?= (int)$supporter["id"] ?>">
                  Company / organisation name
                </label><br>
                <input
                  id="name<?= (int)$supporter["id"] ?>"
                  name="company_name"
                  type="text"
                  value="<?= htmlspecialchars($supporter["company_name"], ENT_QUOTES, "UTF-8") ?>"
                  required
                >
              </p>

              <p>
                <label for="url<?= (int)$supporter["id"] ?>">
                  Website address
                </label><br>
                <input
                  id="url<?= (int)$supporter["id"] ?>"
                  name="website_url"
                  type="url"
                  value="<?= htmlspecialchars($supporter["website_url"], ENT_QUOTES, "UTF-8") ?>"
                  required
                >
              </p>

              <p>
                <label for="logo<?= (int)$supporter["id"] ?>">
                  Replace logo (optional)
                </label><br>
                <input
                  id="logo<?= (int)$supporter["id"] ?>"
                  name="logo"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                >
              </p>

              <div class="supporter-admin-actions">
                <button type="submit">Save Changes</button>
              </div>

            </form>

            <form method="post" onsubmit="return confirm('Delete this supporter?');">

              <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
              >

              <input type="hidden" name="action" value="delete">

              <input
                type="hidden"
                name="id"
                value="<?= (int)$supporter["id"] ?>"
              >

              <button type="submit">Delete</button>

            </form>

          </div>

        <?php endforeach; ?>

      <?php endif; ?>

    </section>
  </main>

</body>
</html>
