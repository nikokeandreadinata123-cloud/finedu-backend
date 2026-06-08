<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi"]);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, phone, password, streak, last_login_date FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Email tidak ditemukan"]);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Password salah"]);
    exit();
}

// ✅ Logika streak
$today          = date('Y-m-d');
$last_login     = $user['last_login_date'];
$current_streak = (int)($user['streak'] ?? 0);

if ($last_login === null) {
    $new_streak = 1;
} elseif ($last_login === $today) {
    $new_streak = $current_streak; // sudah login hari ini
} else {
    $last_date  = new DateTime($last_login);
    $today_date = new DateTime($today);
    $diff_days  = (int)$today_date->diff($last_date)->days;

    if ($diff_days === 1) {
        $new_streak = $current_streak + 1; // hari berturut-turut
    } else {
        $new_streak = 1; // putus streak
    }
}

// Update streak dan last_login_date
$stmt2 = $conn->prepare("UPDATE users SET streak = ?, last_login_date = ? WHERE id = ?");
$stmt2->bind_param("isi", $new_streak, $today, $user['id']);
$stmt2->execute();
$stmt2->close();

$token = bin2hex(random_bytes(32));

echo json_encode([
    "status"          => "success",
    "message"         => "Login berhasil",
    "token"           => $token,
    "streak"          => $new_streak,
    "last_login_date" => $today,   // ✅ dikirim ke frontend untuk disimpan di localStorage
    "user"            => [
        "id"    => $user['id'],
        "name"  => $user['name'],
        "email" => $user['email'],
        "phone" => $user['phone'],
    ]
]);

$conn->close();
