<?php
// ============================================================
//  KONFIGURASI DATABASE (AUTO-DETECT)
// ============================================================

// 1. Cek apakah berjalan di Railway
if (getenv('MYSQLHOST')) {
    $db_host = getenv('MYSQLHOST');
    $db_user = getenv('MYSQLUSER');
    $db_pass = getenv('MYSQLPASSWORD');
    $db_name = getenv('MYSQLDATABASE');
    $db_port = getenv('MYSQLPORT') ?: "3306";
} else {
    // 2. Lokal (XAMPP)
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "finedu_db";
    $db_port = "3306";
}

// ============================================================
//  KONEKSI - jangan ubah bagian ini
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// ✅ CORS dan Content-Type sudah dihandle di masing-masing file PHP
// Tidak perlu duplikat di sini

// Koneksi ke database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);

if ($conn->connect_error) {
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
    }
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Koneksi ke database gagal: " . $conn->connect_error
    ]);
    exit();
}

$conn->set_charset("utf8mb4");
?>
