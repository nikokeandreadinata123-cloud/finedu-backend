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

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "user_id tidak valid"]);
    exit();
}

// ✅ Ambil slide selesai per modul untuk hitung progress
$stmt = $conn->prepare("
    SELECT modul_id, slide_id
    FROM slide_selesai
    WHERE user_id = ?
    ORDER BY modul_id ASC, slide_id ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data   = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "modul_id" => (int)$row['modul_id'],
        "slide_id" => (int)$row['slide_id'],
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["status" => "success", "data" => $data]);
