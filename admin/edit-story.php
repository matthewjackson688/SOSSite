<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

require __DIR__ . "/../includes/database.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/story-content.php";
require __DIR__ . "/../includes/folders.php";
require __DIR__ . "/../includes/story-slugs.php";

require_admin();

$folders_result = $conn->query(
    "SELECT id, name FROM folders ORDER BY name"
);
$folders = $folders_result
    ? $folders_result->fetch_all(MYSQLI_ASSOC)
    : [];

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit("Story not found.");
}

$stmt = $conn->prepare(
    "SELECT id, title, slug, author, summary, content, published, folder_id
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

$error = "";
$success = "";

$title = $story["title"];
$slug = $story["slug"];
$author = $story["author"];
$summary = $story["summary"] ?? "";
$content = $story["content"];
$published = (bool)$story["published"];
$folder_id = $story["folder_id"] !== null
    ? (int)$story["folder_id"]
    : null;

if (
    strpos($content, "<p>") === false &&
    strpos($content, "<br") === false &&
    strpos($content, "<img") === false &&
    strpos($content, "<h3>") === false &&
    strpos($content, "<h4>") === false
) {
    $paragraphs = preg_split(
        "/\R\s*\R+/",
        trim(strip_tags($content))
    );

    $content = "";

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);

        if ($paragraph !== "") {
            $content .= "<p>" .
                htmlspecialchars($paragraph, ENT_QUOTES, "UTF-8") .
                "</p>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $slug = trim($_POST["slug"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $summary = trim($_POST["summary"] ?? "");
    $content = $_POST["content"] ?? "";
    $folder_id = filter_var(
        $_POST["folder_id"] ?? "",
        FILTER_VALIDATE_INT
    ) ?: null;

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $content = sanitize_story_content($content);
    $published = isset($_POST["published"]);

    if ($title === "" || trim(strip_tags($content)) === "") {
        $error = "Title and story content are required.";
    } elseif (!story_slug_is_valid($slug)) {
        $error = "The story URL slug may contain lowercase letters, numbers and hyphens only.";
    } elseif (story_slug_is_reserved($slug)) {
        $error = "That story URL is reserved for another part of the website.";
    } elseif (story_slug_exists($conn, $slug, $id)) {
        $error = "That story URL is already in use. Please choose another.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE stories
             SET title = ?, slug = ?, author = ?, summary = ?, content = ?,
                 published = ?, folder_id = ?
             WHERE id = ?"
        );

        $published_value = $published ? 1 : 0;

        $stmt->bind_param(
            "sssssiii",
            $title,
            $slug,
            $author,
            $summary,
            $content,
            $published_value,
            $folder_id,
            $id
        );

        if ($stmt->execute()) {
            $success = "Story updated successfully.";
        } else {
            $error = "Unable to update the story.";
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Story - Share Our Story</title>
  <link rel="stylesheet" href="../styles.css" />

  <style>
    .story-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 4rem;
      margin-bottom: 0.5rem;
    }

    .text-size-control {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
    }

    .text-size-control input {
      width: 4.5rem;
    }

    .story-toolbar button {
      margin: 0;
    }

    .toolbar-group {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .story-editor > p,
.story-editor > h3,
.story-editor > h4 {
  margin-top: 0;
  margin-bottom: 0;
}

.story-editor {
      min-height: 400px;
      padding: 1rem;
      border: 1px solid #777;
      border-radius: 4px;
      background: #fff;
      color: #111;
      overflow-y: auto;
    }

    .story-editor:focus {
      outline: 3px solid #005fcc;
      outline-offset: 2px;
    }

    .story-editor img {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 1rem 0;
    }

    .photo-help {
      font-size: 0.95rem;
    }
  </style>
</head>

<body>

  <main class="wrap">
    <section class="card">

      <h1>Edit Story</h1>

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

      <form method="post" action="" id="story-form">

        <input
          type="hidden"
          name="csrf_token"
          value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <p>
          <label for="title">Story title</label><br>
          <input
            type="text"
            id="title"
            name="title"
            value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>"
            required
          >
        </p>

        <p>
          <label for="slug">Story URL slug</label><br>
          <input
            type="text"
            id="slug"
            name="slug"
            value="<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>"
            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
            maxlength="255"
            autocomplete="off"
            spellcheck="false"
            required
          >
          <br>
          <small>
            Use lowercase letters, numbers and hyphens only. This becomes the page address after <code>shareourstory.uk/</code>.
          </small>
        </p>

        <p>
          <label for="author">Author <span>(optional)</span></label><br>
          <input
            type="text"
            id="author"
            name="author"
            value="<?= htmlspecialchars($author, ENT_QUOTES, "UTF-8") ?>"
          >
        </p>

        <p>
          <label for="summary">Summary</label><br>
          <textarea
            id="summary"
            name="summary"
            rows="4"
          ><?= htmlspecialchars($summary, ENT_QUOTES, "UTF-8") ?></textarea>
        </p>

        <p>
          <label for="folder_id">Folder</label><br>
          <select id="folder_id" name="folder_id">
            <option value="">No folder (standalone story)</option>
            <?php foreach ($folders as $folder): ?>
              <option
                value="<?= (int)$folder["id"] ?>"
                <?= $folder_id === (int)$folder["id"] ? "selected" : "" ?>
              >
                <?= htmlspecialchars($folder["name"], ENT_QUOTES, "UTF-8") ?>
              </option>
            <?php endforeach; ?>
          </select>
        </p>

        <p>
          <strong>Full story</strong>
        </p>

        <div class="story-toolbar" role="toolbar" aria-label="Story formatting">

          <span class="toolbar-group toolbar-inline-actions">
            <button type="button" data-command="bold">Bold</button>
            <button type="button" data-command="italic">Italic</button>
          </span>
          <span class="toolbar-group toolbar-size-actions">
            <label class="text-size-control">
              Text size
              <input type="number" id="text-size" min="8" max="72" step="1" value="16" inputmode="numeric" aria-label="Text size in pixels">
              px
            </label>
            <button type="button" id="apply-text-size">Apply</button>
          </span>
          <span class="toolbar-group toolbar-photo-action">
            <button type="button" id="insert-photo">Insert Photo</button>
          </span>

        </div>

        <p class="photo-help">
          When adding a photo, you will be asked for alternative text
          so the story remains accessible to people using screen readers.
        </p>

        <div
          id="story-editor"
          class="story-editor"
          contenteditable="true"
          role="textbox"
          aria-multiline="true"
          aria-label="Full story"
          tabindex="0"
        ><?= $content ?></div>

        <textarea id="content" name="content" hidden></textarea>

        <input
          type="file"
          id="photo-upload"
          accept="image/jpeg,image/png,image/webp,image/gif"
          hidden
        >

        <p>
          <label>
            <input
              type="checkbox"
              name="published"
              <?= $published ? "checked" : "" ?>
            >
            Publish this story
          </label>
        </p>

        <p>
          <button type="submit" name="action" value="save">Save Changes</button>
        </p>

      </form>

      <p>
        <a href="index.php">← Back to Admin</a>
      </p>

    </section>
  </main>

  <script>
  const editor = document.getElementById("story-editor");
  const contentField = document.getElementById("content");
  const photoUpload = document.getElementById("photo-upload");
  const insertPhotoButton = document.getElementById("insert-photo");
  const textSizeInput = document.getElementById("text-size");
  const applyTextSizeButton = document.getElementById("apply-text-size");
  const form = document.getElementById("story-form");

  let savedRange = null;

  function selectionIsInsideEditor(range) {
    return range && editor.contains(range.commonAncestorContainer);
  }

  function saveSelection() {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    if (selectionIsInsideEditor(range)) savedRange = range.cloneRange();
  }

  function restoreSelection() {
    if (!savedRange) {
      editor.focus();
      return null;
    }
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedRange);
    editor.focus();
    return selection.getRangeAt(0);
  }

  function removeEditorMarkers() {
    const walker = document.createTreeWalker(editor, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      node.nodeValue = node.nodeValue.replace(/\u200b/g, "");
    });
  }


  document.querySelectorAll("[data-command]").forEach(button => {
    button.addEventListener("mousedown", event => {
      event.preventDefault();
      saveSelection();
    });

    button.addEventListener("click", () => {
      const selection = restoreSelection();
      if (!selection) return;
      const command = button.dataset.command;
      if (command === "bold") {
        document.execCommand("bold", false, null);
      } else if (command === "italic") {
        document.execCommand("italic", false, null);
      }
      saveSelection();
      editor.focus();
    });
  });

  editor.addEventListener("keyup", saveSelection);
  editor.addEventListener("mouseup", saveSelection);
  editor.addEventListener("input", saveSelection);
  editor.addEventListener("focus", saveSelection);

  function applyTextSize() {
    const selection = restoreSelection();
    if (!selection || selection.isCollapsed || !selectionIsInsideEditor(selection)) {
      window.alert("Highlight the text you want to resize first.");
      return;
    }

    let size = Number.parseInt(textSizeInput.value, 10);
    if (!Number.isFinite(size)) size = 16;
    size = Math.max(8, Math.min(72, size));
    textSizeInput.value = String(size);

    const span = document.createElement("span");
    span.style.fontSize = size + "px";
    span.appendChild(selection.extractContents());
    selection.insertNode(span);

    const newRange = document.createRange();
    newRange.selectNodeContents(span);
    selection.removeAllRanges();
    selection.addRange(newRange);
    savedRange = newRange.cloneRange();
    editor.focus();
  }

  applyTextSizeButton.addEventListener("mousedown", event => {
    event.preventDefault();
    saveSelection();
  });

  applyTextSizeButton.addEventListener("click", applyTextSize);

  textSizeInput.addEventListener("mousedown", event => {
    saveSelection();
  });

  textSizeInput.addEventListener("keydown", event => {
    if (event.key === "Enter") {
      event.preventDefault();
      applyTextSize();
    }
  });

  insertPhotoButton.addEventListener("mousedown", event => {
    event.preventDefault();
    saveSelection();
  });

  insertPhotoButton.addEventListener("click", () => {
    restoreSelection();
    photoUpload.click();
  });

  photoUpload.addEventListener("change", async () => {
    const file = photoUpload.files[0];
    if (!file) return;

    const altText = window.prompt("Describe this photo for someone who cannot see it:");
    if (altText === null) {
      photoUpload.value = "";
      return;
    }
    if (altText.trim() === "") {
      window.alert("Please provide alternative text for the photo.");
      photoUpload.value = "";
      return;
    }

    const formData = new FormData();
    formData.append("image", file);

    try {
      insertPhotoButton.disabled = true;
      insertPhotoButton.textContent = "Uploading...";
      const response = await fetch("upload-story-image.php", { method: "POST", body: formData });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.error || "Photo upload failed.");

      const selection = window.getSelection();
      const range = selection && selection.rangeCount ? selection.getRangeAt(0) : null;
      const image = document.createElement("img");
      image.src = data.url;
      image.alt = altText.trim();
      image.loading = "lazy";

      if (range && selectionIsInsideEditor(range)) {
        range.deleteContents();
        range.insertNode(image);
        range.setStartAfter(image);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
      } else {
        const p = document.createElement("p");
        p.appendChild(image);
        editor.appendChild(p);
        placeCaret(p, false);
      }
      saveSelection();
    } catch (error) {
      window.alert(error.message);
    } finally {
      insertPhotoButton.disabled = false;
      insertPhotoButton.textContent = "Insert Photo";
      photoUpload.value = "";
    }
  });

  function serializeEditor() {
    const clone = editor.cloneNode(true);
    const walker = document.createTreeWalker(clone, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      node.nodeValue = node.nodeValue.replace(/\u200b/g, "");
    });
    return clone.innerHTML.trim();
  }

form.addEventListener("submit", () => {
  removeEditorMarkers();
  contentField.value = serializeEditor();
});
</script>

</body>
</html>
