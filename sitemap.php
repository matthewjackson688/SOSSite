<?php

require __DIR__ . "/includes/database.php";

header("Content-Type: application/xml; charset=UTF-8");

$baseUrl = "https://shareourstory.uk";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$staticUrls = [
    "/",
    "/about/",
    "/contact/",
    "/our-stories/",
    "/donate/",
    "/media/",
    "/publications/",
    "/supporters/",
];

foreach ($staticUrls as $path) {
    echo "<url>";
    echo "<loc>" . htmlspecialchars($baseUrl . $path, ENT_XML1, "UTF-8") . "</loc>";
    echo "</url>";
}

$stmt = $conn->prepare(
    "SELECT slug
     FROM stories
     WHERE published = TRUE
     ORDER BY created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();

while ($story = $result->fetch_assoc()) {
    echo "<url>";
    echo "<loc>" .
        htmlspecialchars(
            $baseUrl . "/" . rawurlencode($story["slug"]) . "/",
            ENT_XML1,
            "UTF-8"
        ) .
        "</loc>";
    echo "</url>";
}

echo "</urlset>";
