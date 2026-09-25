<?php

function slugify_folder(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'folder';
}

function unique_folder_slug(mysqli $conn, string $name, ?int $exclude_id = null): string
{
    $base = slugify_folder($name);
    $slug = $base;
    $n = 2;

    while (true) {
        if ($exclude_id !== null) {
            $stmt = $conn->prepare(
                "SELECT id
                 FROM folders
                 WHERE slug = ? AND id <> ?
                 LIMIT 1"
            );
            $stmt->bind_param("si", $slug, $exclude_id);
        } else {
            $stmt = $conn->prepare(
                "SELECT id
                 FROM folders
                 WHERE slug = ?
                 LIMIT 1"
            );
            $stmt->bind_param("s", $slug);
        }

        $stmt->execute();

        if (!$stmt->get_result()->fetch_assoc()) {
            return $slug;
        }

        $slug = $base . "-" . $n++;
    }
}
