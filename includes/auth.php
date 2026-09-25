<?php

session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);

session_start();

function require_admin(): void
{
    if (empty($_SESSION["admin_id"])) {
        header("Location: login.php");
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verify_csrf_token(string $token): bool
{
    return !empty($_SESSION["csrf_token"])
        && hash_equals($_SESSION["csrf_token"], $token);
}
