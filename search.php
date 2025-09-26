<?php
/**
 * Tệp tìm kiếm tài liệu cho dự án tailieuUNETI
 * Phiên bản được cải tiến để hoạt động ổn định và báo lỗi tốt hơn.
 */

// Bật báo lỗi PHP (chỉ dùng khi đang phát triển, hãy xóa hoặc bình luận dòng này khi đưa lên server thật)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Thiết lập header để luôn trả về dữ liệu dạng JSON
header('Content-Type: application/json; charset=utf-8');

// --- BƯỚC 1: LẤY VÀ KIỂM TRA TỪ KHÓA ---
$keyword = trim($_GET['q'] ?? '');

if (empty($keyword)) {
    // Nếu không có từ khóa, trả về một mảng rỗng và kết thúc
    echo json_encode([]);
    exit;
}

// --- BƯỚC 2: KẾT NỐI CƠ SỞ DỮ LIỆU (CSDL) ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "uneti_login";

// Tạo kết nối
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Nếu kết nối thất bại, trả về một JSON chứa thông báo lỗi
    echo json_encode(['error' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]);
    exit;
}

// CẢI TIẾN QUAN TRỌNG: Thiết lập bảng mã UTF-8 để làm việc với tiếng Việt
$conn->set_charset("utf8mb4");


// --- BƯỚC 3: TRUY VẤN TÌM KIẾM AN TOÀN ---
try {
    // Chuẩn bị từ khóa để dùng với lệnh LIKE
    $searchTerm = "%" . $keyword . "%";

    // Câu lệnh SQL tìm kiếm từ khóa trong 3 cột: tieu_de, mo_ta, và ten_file
    $sql = "SELECT tieu_de, mo_ta, the_loai, ten_file, duong_dan_file, ngay_tai_len 
            FROM tai_lieu 
            WHERE tieu_de LIKE ? OR mo_ta LIKE ? OR ten_file LIKE ?";
    
    // Chuẩn bị câu lệnh
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error);
    }

    // Gán biến vào các dấu ?
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    
    // Thực thi câu lệnh
    if (!$stmt->execute()) {
        throw new Exception('Lỗi thực thi câu lệnh SQL: ' . $stmt->error);
    }

    // Lấy kết quả
    $result = $stmt->get_result();
    $results = [];

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    // Đóng câu lệnh
    $stmt->close();

    // Trả về kết quả dưới dạng JSON
    echo json_encode($results);

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào xảy ra trong quá trình truy vấn, trả về JSON lỗi
    http_response_code(500); // Báo lỗi Server Internal Error
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Luôn đóng kết nối CSDL dù thành công hay thất bại
    if (isset($conn)) {
        $conn->close();
    }
}
?>