<?php
// CORS Headers - harus sebelum apapun
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
    exit();
}

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
    exit();
}

$name     = trim($data["name"]     ?? "");
$email    = trim($data["email"]    ?? "");
$phone    = trim($data["phone"]    ?? "");
$password = trim($data["password"] ?? "");

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
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "Email sudah digunakan"]);
    $stmt->close();
    exit();
}
$stmt->close();

// Hash password & insert user baru, streak langsung 1 karena hari pertama
$hash  = password_hash($password, PASSWORD_BCRYPT);
$today = date('Y-m-d');

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, streak, last_login_date) VALUES (?, ?, ?, ?, 1, ?)");
$stmt->bind_param("sssss", $name, $email, $phone, $hash, $today);

if ($stmt->execute()) {
    $new_user_id = $conn->insert_id;
    $stmt->close();

    // Generate token & SIMPAN ke database
    $token = bin2hex(random_bytes(32));
    $stmtToken = $conn->prepare("UPDATE users SET token = ? WHERE id = ?");
    $stmtToken->bind_param("si", $token, $new_user_id);
    $stmtToken->execute();
    $stmtToken->close();

    echo json_encode([
        "status"  => "success",
        "message" => "Registrasi berhasil. Selamat datang di FinEdu!",
        "token"   => $token,
        "streak"  => 1,
        "user"    => [
            "id"    => (int)$new_user_id,
            "name"  => $name,
            "email" => $email,
            "phone" => $phone,
        ]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menyimpan data: " . $conn->error
    ]);
    $stmt->close();
}

$conn->close();
