<?php

function sanitize($value) {
    return htmlspecialchars(trim($value));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['role']) && !empty($_SESSION['role']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect("../login.php");
    }
}

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan formatTanggal ada
if (!function_exists('formatTanggal')) {
    function formatTanggal($date) {
        return date('d-m-Y', strtotime($date));
    }
}
?>