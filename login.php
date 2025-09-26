<?php
// Bắt đầu session để lưu trạng thái đăng nhập
session_start();

// Thiết lập header để trả về kiểu dữ liệu JSON
header('Content-Type: application/json; charset=utf-8');

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
    exit();
}

// === XỬ LÝ DỮ LIỆU TỪ FORM ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // --- KIỂM TRA DỮ LIỆU ĐẦU VÀO ---
    if (empty($username) || empty($password)) {
        $response['status'] = 'error';
        $response['message'] = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.';
    } else {
        // --- CHUẨN BỊ CÂU LỆNH SQL AN TOÀN ---
        // Tìm kiếm người dùng bằng cả username hoặc email
        $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // --- KIỂM TRA KẾT QUẢ ---
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // So sánh mật khẩu đã mã hóa
                if (password_verify($password, $user['password'])) {
                    // Đăng nhập thành công
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    $response['status'] = 'success';
                    $response['message'] = 'Đăng nhập thành công!';
                    $response['redirect'] = 'index.html'; // Chuyển hướng về trang chủ
                } else {
                    // Sai mật khẩu
                    $response['status'] = 'error';
                    $response['message'] = 'Tài khoản hoặc mật khẩu không chính xác.';
                }
            } else {
                // Không tìm thấy tài khoản
                $response['status'] = 'error';
                $response['message'] = 'Tài khoản hoặc mật khẩu không chính xác.';
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