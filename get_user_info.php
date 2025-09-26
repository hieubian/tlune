<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập.']);
    exit;
}

// --- THÔNG TIN DATABASE ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "uneti_login"; // Đảm bảo đúng tên database

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối CSDL.']);
    exit;
}

$user_id = $_SESSION['id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT username, email, full_name, dob FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode(['status' => 'success', 'data' => $user]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng.']);
}

$stmt->close();
$conn->close();
?>