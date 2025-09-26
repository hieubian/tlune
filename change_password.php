<?php
session_start();
header('Content-Type: application/json');

// --- THAY THẾ BẰNG THÔNG TIN DATABASE CỦA BẠN ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "uneti_login";
// ---------------------------------------------

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn phải đăng nhập để thực hiện việc này.']);
    exit;
}

// Lấy thông tin từ form
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';
$user_id = $_SESSION['id']; // Lấy ID người dùng từ session

// --- VALIDATION ---
if (empty($old_password) || empty($new_password) || empty($confirm_new_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin.']);
    exit;
}

if ($new_password !== $confirm_new_password) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới không khớp.']);
    exit;
}

if (strlen($new_password) < 6) { // Ví dụ: yêu cầu mật khẩu tối thiểu 6 ký tự
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
    exit;
}

// Kết nối CSDL
$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối CSDL.']);
    exit;
}

// Lấy mật khẩu đã hash hiện tại của người dùng
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng.']);
    exit;
}

// So sánh mật khẩu cũ người dùng nhập với mật khẩu trong CSDL
if (!password_verify($old_password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu cũ không chính xác.']);
    exit;
}

// --- MỌI THỨ HỢP LỆ, TIẾN HÀNH ĐỔI MẬT KHẨU ---
// Hash mật khẩu mới
$new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Cập nhật mật khẩu mới vào CSDL
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $new_password_hashed, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Đổi mật khẩu thành công!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Đã xảy ra lỗi khi cập nhật mật khẩu.']);
}

$stmt->close();
$conn->close();
?>