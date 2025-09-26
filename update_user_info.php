<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập.']);
    exit;
}

// Lấy dữ liệu từ POST
$full_name = trim($_POST['full_name'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Địa chỉ email không hợp lệ.']);
    exit;
}

// --- THÔNG TIN DATABASE ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "uneti_login";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối CSDL.']);
    exit;
}

$user_id = $_SESSION['id'];

// Cập nhật thông tin
// Xử lý dob: nếu trống thì gán là NULL
$dob_to_db = !empty($dob) ? $dob : NULL;

$stmt = $conn->prepare("UPDATE users SET full_name = ?, dob = ?, email = ? WHERE id = ?");
$stmt->bind_param("sssi", $full_name, $dob_to_db, $email, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật thông tin thành công!']);
} else {
    // Kiểm tra lỗi email trùng lặp
    if ($conn->errno == 1062) {
         echo json_encode(['status' => 'error', 'message' => 'Email này đã được sử dụng bởi tài khoản khác.']);
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
    }
}

$stmt->close();
$conn->close();
?>