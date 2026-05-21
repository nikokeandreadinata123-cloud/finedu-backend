<?php
// ============================================================
//  KONFIGURASI DATABASE (AUTO-DETECT)
// ============================================================

// 1. Cek apakah berjalan di Railway (Railway menyediakan variabel environment otomatis)
if (getenv('MYSQLHOST')) {
    $db_host = getenv('MYSQLHOST');
    $db_user = getenv('MYSQLUSER');
    $db_pass = getenv('MYSQLPASSWORD');
    $db_name = getenv('MYSQLDATABASE');
    $db_port = getenv('MYSQLPORT') ?: "3306";
} else {
    // 2. Jika tidak di Railway, gunakan settingan manual / lokal (XAMPP)
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

// CORS Headers - Wajib untuk Vercel
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Koneksi ke database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    if (!headers_sent()) {
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
