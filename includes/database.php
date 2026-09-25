<?php

$config = require __DIR__ . "/database-config.php";

$conn = new mysqli(
    $config["host"],
    $config["username"],
    $config["password"],
    $config["dbname"]
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
