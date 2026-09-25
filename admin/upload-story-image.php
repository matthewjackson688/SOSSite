<?php

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");


require __DIR__ . "/../includes/auth.php";

require_admin();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit;
}

if (empty($_FILES["image"])) {
    http_response_code(400);
    echo json_encode(["error" => "No image was uploaded."]);
    exit;
}

$file = $_FILES["image"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        "error" => "Image upload failed. PHP upload error code: " . $file["error"]
    ]);
    exit;
}

$max_size = 20 * 1024 * 1024;

if ($file["size"] > $max_size) {
    http_response_code(400);
    echo json_encode([
        "error" => "Image is too large. Maximum upload size is 20 MB."
    ]);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$allowed_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

if (!in_array($mime, $allowed_types, true)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Unsupported image type. Please use JPG, PNG or WebP."
    ]);
    exit;
}

$image_info = @getimagesize($file["tmp_name"]);

if ($image_info === false) {
    http_response_code(400);
    echo json_encode([
        "error" => "The uploaded file is not a valid image."
    ]);
    exit;
}

$original_width = $image_info[0];
$original_height = $image_info[1];

$max_dimension = 1100;

$scale = min(
    1,
    $max_dimension / $original_width,
    $max_dimension / $original_height
);

$new_width = max(1, (int)round($original_width * $scale));
$new_height = max(1, (int)round($original_height * $scale));

switch ($mime) {
    case "image/jpeg":
        $source = @imagecreatefromjpeg($file["tmp_name"]);
        break;

    case "image/png":
        $source = @imagecreatefrompng($file["tmp_name"]);
        break;

    case "image/webp":
        $source = @imagecreatefromwebp($file["tmp_name"]);
        break;

    default:
        $source = false;
}

if (!$source) {
    http_response_code(400);
    echo json_encode([
        "error" => "Unable to process this image."
    ]);
    exit;
}

$destination_image = imagecreatetruecolor($new_width, $new_height);

if ($mime === "image/png" || $mime === "image/webp") {
    imagealphablending($destination_image, false);
    imagesavealpha($destination_image, true);

    $transparent = imagecolorallocatealpha(
        $destination_image,
        0,
        0,
        0,
        127
    );

    imagefilledrectangle(
        $destination_image,
        0,
        0,
        $new_width,
        $new_height,
        $transparent
    );
}

imagecopyresampled(
    $destination_image,
    $source,
    0,
    0,
    0,
    0,
    $new_width,
    $new_height,
    $original_width,
    $original_height
);

$filename = bin2hex(random_bytes(16)) . ".webp";

$upload_dir = __DIR__ . "/../uploads/stories";
$destination = $upload_dir . "/" . $filename;

if (!is_dir($upload_dir)) {
    imagedestroy($source);
    imagedestroy($destination_image);

    http_response_code(500);
    echo json_encode([
        "error" => "Upload directory is unavailable."
    ]);
    exit;
}

if (!imagewebp($destination_image, $destination, 82)) {
    imagedestroy($source);
    imagedestroy($destination_image);

    http_response_code(500);
    echo json_encode([
        "error" => "Unable to save the processed image."
    ]);
    exit;
}

imagedestroy($source);
imagedestroy($destination_image);

echo json_encode([
    "success" => true,
    "url" => "/uploads/stories/" . $filename,
    "width" => $new_width,
    "height" => $new_height
]);
