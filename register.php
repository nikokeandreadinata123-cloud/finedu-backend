<?php
// CORS Headers - harus sebelum apapun
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
    exit();
}

// Ambil JSON
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

// VALIDASI JSON
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
    exit();
}

$name     = trim($data["name"]     ?? "");
$email    = trim($data["email"]    ?? "");
$phone    = trim($data["phone"]    ?? "");
$password = trim($data["password"] ?? "");

// Validasi field wajib
if (!$name || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Field wajib tidak boleh kosong"]);
    exit();
}

// Cek email sudah dipakai
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email sudah digunakan"]);
    $stmt->close();
    exit();
}
$stmt->close();

// Hash password & insert user baru
// ✅ streak = 0 dan last_login_date = NULL otomatis karena DEFAULT di kolom
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, streak, last_login_date) VALUES (?, ?, ?, ?, 0, NULL)");
$stmt->bind_param("ssss", $name, $email, $phone, $hash);

if ($stmt->execute()) {
    // User baru berhasil dibuat, streak mulai dari 0
    echo json_encode([
        "status"  => "success",
        "message" => "Registrasi berhasil. Selamat datang di FinEdu!"
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menyimpan data: " . $conn->error
    ]);
}

$stmt->close();
$conn->close();
