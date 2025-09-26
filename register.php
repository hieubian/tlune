<?php
// Thiết lập header để trả về kiểu dữ liệu JSON
header('Content-Type: application/json; charset=utf-f8');

// === THÔNG TIN KẾT NỐI DATABASE ===
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "uneti_login";

// Tạo một mảng để chứa kết quả trả về
$response = [];

// === TẠO KẾT NỐI ===
$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    $response['status'] = 'error';
    $response['message'] = 'Lỗi kết nối database: ' . $conn->connect_error;
    echo json_encode($response);
    exit(); // Dừng chương trình
}

// === XỬ LÝ DỮ LIỆU TỪ FORM ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // --- KIỂM TRA DỮ LIỆU ĐẦU VÀO ---
    if ($password !== $confirm_password) {
        $response['status'] = 'error';
        $response['message'] = 'Mật khẩu nhập lại không khớp!';
    } elseif (empty($username) || empty($email) || empty($password)) {
        $response['status'] = 'error';
        $response['message'] = 'Vui lòng điền đầy đủ thông tin.';
    } else {
        // --- MÃ HÓA MẬT KHẨU ---
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // --- CHUẨN BỊ CÂU LỆNH SQL AN TOÀN ---
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            // --- THỰC THI VÀ TRẢ KẾT QUẢ ---
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Tài khoản <strong>' . htmlspecialchars($username) . '</strong> đã được tạo thành công!';
            } else {
                if ($conn->errno == 1062) {
                    $response['status'] = 'error';
                    $response['message'] = 'Tên người dùng hoặc Email đã tồn tại.';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Lỗi không xác định. Vui lòng thử lại.';
                }
            }
            $stmt->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Lỗi hệ thống, không thể chuẩn bị câu lệnh.';
        }
    }
    $conn->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Yêu cầu không hợp lệ.';
}

// Trả về kết quả dưới dạng chuỗi JSON
echo json_encode($response);
?>