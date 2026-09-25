<?php

function story_slugify(string $text): string
{
    $text = trim($text);

    if ($text === "") {
        return "";
    }

    $text = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text);
    $text = strtolower($text);
    $text = preg_replace("/[^a-z0-9]+/", "-", $text);
    $text = trim($text, "-");

    return $text;
}

function story_reserved_slugs(): array
{
    return [
        "",
        "admin",
        "about",
        "contact",
        "donate",
        "media",
        "our-stories",
        "publications",
        "supporters",
        "support-form",
        "thank-you",
        "folder",
        "story",
        "uploads",
        "images",
        "includes",
        "css",
        "js",
    ];
}

function story_slug_is_reserved(string $slug): bool
{
    return in_array($slug, story_reserved_slugs(), true);
}

function story_slug_is_valid(string $slug): bool
{
    return preg_match("/^[a-z0-9]+(?:-[a-z0-9]+)*$/", $slug) === 1;
}

function story_slug_exists(mysqli $conn, string $slug, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        $stmt = $conn->prepare(
            "SELECT id FROM stories WHERE slug = ? AND id <> ? LIMIT 1"
        );
        $stmt->bind_param("si", $slug, $excludeId);
    } else {
        $stmt = $conn->prepare(
            "SELECT id FROM stories WHERE slug = ? LIMIT 1"
        );
        $stmt->bind_param("s", $slug);
    }

    $stmt->execute();

    return (bool)$stmt->get_result()->fetch_assoc();
}
