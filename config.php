<?php
// Konfigurasi database
$host       = "localhost";
$user       = "root";        
$password   = "";           
$database   = "db_mercubuana";

// Set timezone default
date_default_timezone_set("Asia/Jakarta");

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// fungsi untuk mencegah SQL Injection
function input($data) {
    global $conn;
    return htmlspecialchars(mysqli_real_escape_string($conn, $data));
}

// Fungsi untuk format tanggal Indonesia

?>