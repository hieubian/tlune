<?php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Có lỗi xảy ra.'];

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'Bạn phải đăng nhập để tải lên tài liệu.';
    echo json_encode($response);
    exit;
}

// 2. Kiểm tra phương thức POST và sự tồn tại của file, tiêu đề và thể loại
if ($_SERVER["REQUEST_METHOD"] == "POST" 
    && isset($_FILES["documentFile"]) 
    && !empty($_POST['the_loai'])) {
    
    $ma_nguoi_dung = $_SESSION['id'];
    $tieu_de = trim($_POST['title'] ?? 'Chưa có tiêu đề');
    $mo_ta = trim($_POST['description'] ?? '');
    $the_loai = $_POST['the_loai'];

    // 3. AN TOÀN: Xác thực thể loại đầu vào
    $allowed_categories = ['daicuong', 'tailieungoaingu', 'tailieuchuyennganh'];
    if (!in_array($the_loai, $allowed_categories)) {
        $response['message'] = 'Thể loại tài liệu không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    // 4. Xây dựng đường dẫn upload động dựa trên thể loại
    $upload_dir = 'uploads/' . $the_loai . '/'; // Ví dụ: 'uploads/daicuong/'

    $file = $_FILES["documentFile"];
    $ten_file_goc = basename($file["name"]);
    $file_tmp_name = $file["tmp_name"];
    $kich_thuoc_file = $file["size"];
    $loai_file = $file["type"];
    $file_error = $file["error"];

    if ($file_error !== UPLOAD_ERR_OK) {
        $response['message'] = 'Lỗi trong quá trình tải file lên.';
        echo json_encode($response);
        exit;
    }

    // 5. Tạo tên file mới, duy nhất
    $file_extension = strtolower(pathinfo($ten_file_goc, PATHINFO_EXTENSION));
    $ten_file_moi = uniqid('doc_', true) . '.' . $file_extension;
    $duong_dan_file = $upload_dir . $ten_file_moi;

    // 6. Di chuyển file vào thư mục con chính xác
    if (move_uploaded_file($file_tmp_name, $duong_dan_file)) {
        // 7. Lưu thông tin vào database
        $servername = "localhost";
        $username_db = "root";
        $password_db = "";
        $dbname = "uneti_login";

        $conn = new mysqli($servername, $username_db, $password_db, $dbname);
        if ($conn->connect_error) {
            $response['message'] = 'Lỗi kết nối CSDL.';
        } else {
            // Thêm a_loai vào câu lệnh INSERT
            $sql = "INSERT INTO tai_lieu (ma_nguoi_dung, tieu_de, mo_ta, the_loai, ten_file, duong_dan_file, kich_thuoc_file, loai_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            // Thêm 's' cho a_loai và biến $a_loai
            $stmt->bind_param("isssssis", $ma_nguoi_dung, $tieu_de, $mo_ta, $the_loai, $ten_file_goc, $duong_dan_file, $kich_thuoc_file, $loai_file);

            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Tải lên tài liệu thành công!';
            } else {
                $response['message'] = 'Lỗi: Không thể lưu thông tin vào CSDL.';
                unlink($duong_dan_file);
            }
            $stmt->close();
            $conn->close();
        }
    } else {
        $response['message'] = 'Lỗi: Không thể di chuyển file đã tải lên. Kiểm tra lại quyền của thư mục.';
    }
} else {
    $response['message'] = 'Yêu cầu không hợp lệ hoặc thiếu thông tin.';
}

echo json_encode($response);
?>