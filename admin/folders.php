<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/folders.php";

require_admin();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "rename") {
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($name === "") {
            $error = "Folder name is required.";
        } else {
            $slug = unique_folder_slug(
                $conn,
                $name,
                $action === "rename" ? $id : null
            );

            if ($action === "create") {
                $stmt = $conn->prepare(
                    "INSERT INTO folders (name, slug, description)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $name, $slug, $description);

                if ($stmt->execute()) {
                    $success = "Folder created successfully.";
                } else {
                    $error = "Unable to create the folder.";
                }
            } elseif ($id) {
                $stmt = $conn->prepare(
                    "UPDATE folders
                     SET name = ?, slug = ?, description = ?
                     WHERE id = ?"
                );
                $stmt->bind_param("sssi", $name, $slug, $description, $id);

                if ($stmt->execute()) {
                    $success = "Folder updated successfully.";
                } else {
                    $error = "Unable to update the folder.";
                }
            }
        }
    } elseif ($action === "delete") {
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $conn->prepare(
                "DELETE FROM folders WHERE id = ?"
            );
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success = "Folder deleted. Its stories are now standalone.";
            } else {
                $error = "Unable to delete the folder.";
            }
        }
    }
}

$result = $conn->query(
    "SELECT f.id, f.name, f.slug, f.description,
            COUNT(s.id) AS story_count
     FROM folders f
     LEFT JOIN stories s ON s.folder_id = f.id
     GROUP BY f.id, f.name, f.slug, f.description
     ORDER BY f.name"
);

$folders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Story Folders - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css">

  <style>
    .folder-card {
      border: 1px solid #777;
      border-radius: 10px;
      padding: 1rem;
      margin: 1rem 0;
    }

    .folder-card input,
    .folder-card textarea {
      font: inherit;
      max-width: 100%;
      box-sizing: border-box;
    }

    .folder-card input {
      min-width: 240px;
    }

    .folder-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }
  </style>
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Story Folders</h1>

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

      <h2>Create a folder</h2>

      <form method="post">

        <input
          type="hidden"
          name="csrf_token"
          value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <input type="hidden" name="action" value="create">

        <p>
          <label for="name">Folder name</label><br>
          <input id="name" name="name" type="text" required>
        </p>

        <p>
          <label for="description">Description (optional)</label><br>
          <textarea
            id="description"
            name="description"
            rows="3"
          ></textarea>
        </p>

        <button type="submit">Create Folder</button>

      </form>

      <h2>Existing folders</h2>

      <?php if (!$folders): ?>

        <p>No folders have been created yet.</p>

      <?php else: ?>

        <?php foreach ($folders as $folder): ?>

          <div class="folder-card">

            <form method="post">

              <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
              >

              <input type="hidden" name="action" value="rename">

              <input
                type="hidden"
                name="id"
                value="<?= (int)$folder["id"] ?>"
              >

              <p>
                <label for="n<?= (int)$folder["id"] ?>">
                  Folder name
                </label><br>

                <input
                  id="n<?= (int)$folder["id"] ?>"
                  name="name"
                  type="text"
                  value="<?= htmlspecialchars($folder["name"], ENT_QUOTES, "UTF-8") ?>"
                  required
                >
              </p>

              <p>
                <label for="d<?= (int)$folder["id"] ?>">
                  Description
                </label><br>

                <textarea
                  id="d<?= (int)$folder["id"] ?>"
                  name="description"
                  rows="3"
                ><?= htmlspecialchars($folder["description"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
              </p>

              <div class="folder-actions">

                <button type="submit">Save Folder</button>

                <a href="../folder.php?slug=<?= urlencode($folder["slug"]) ?>">
                  View folder
                </a>

                <span>
                  <?= (int)$folder["story_count"] ?>
                  <?= (int)$folder["story_count"] === 1 ? "story" : "stories" ?>
                </span>

              </div>

            </form>

            <form
              method="post"
              onsubmit="return confirm('Delete this folder? Its stories will become standalone stories.');"
            >

              <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
              >

              <input type="hidden" name="action" value="delete">

              <input
                type="hidden"
                name="id"
                value="<?= (int)$folder["id"] ?>"
              >

              <button type="submit">Delete Folder</button>

            </form>

          </div>

        <?php endforeach; ?>

      <?php endif; ?>

      <p>
        <a href="index.php">← Back to Admin</a>
      </p>

    </section>
  </main>

</body>
</html>
