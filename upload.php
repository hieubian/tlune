<?php
/**
 * Tệp xử lý upload tài liệu cho dự án tailieuUNETI
 * PHIÊN BẢN HOÀN CHỈNH - Đã sửa lỗi và cập nhật theo yêu cầu
 * * Các tính năng chính:
 * 1. Sửa lỗi SQL: Sử dụng đúng tên cột `ten_file` khớp với CSDL.
 * 2. Giữ lại tên tệp gốc khi tải lên.
 * 3. Tự động thêm (1), (2)... vào tên tệp nếu bị trùng để tránh ghi đè.
 * 4. Hỗ trợ upload nhiều tệp tin cùng lúc.
 * 5. Tự động tạo thư mục thể loại nếu chưa tồn tại.
 * 6. Báo lỗi chi tiết để dễ dàng gỡ lỗi trong tương lai.
 */

// Bắt đầu Session và thiết lập Header JSON
session_start();
header('Content-Type: application/json; charset=utf-8');

// Phản hồi mặc định
$response = [
    'status' => 'error',
    'message' => 'Yêu cầu không hợp lệ hoặc thiếu thông tin.',
    'details' => [
        'success_files' => [],
        'failed_files' => []
    ]
];

// === BƯỚC 1: KIỂM TRA QUYỀN TRUY CẬP (ĐÃ ĐĂNG NHẬP) ===
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'Bạn phải đăng nhập để tải lên tài liệu.';
    echo json_encode($response);
    exit;
}

// === BƯỚC 2: KIỂM TRA DỮ LIỆU GỬI LÊN ===
if ($_SERVER["REQUEST_METHOD"] == "POST" 
    && isset($_FILES["documentFiles"]) 
    && !empty($_POST['the_loai'])
    && !empty($_POST['title'])) {

    // === BƯỚC 3: LẤY VÀ XÁC THỰC DỮ LIỆU ĐẦU VÀO ===
    $ma_nguoi_dung = $_SESSION['id'];
    $tieu_de = trim($_POST['title']);
    $mo_ta = trim($_POST['description'] ?? '');
    $the_loai = $_POST['the_loai'];
    
    $allowed_categories = ['daicuong', 'tailieungoaingu', 'tailieuchuyennganh'];
    if (!in_array($the_loai, $allowed_categories)) {
        $response['message'] = 'Thể loại tài liệu không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    // === BƯỚC 4: KẾT NỐI CƠ SỞ DỮ LIỆU (CSDL) ===
    $servername = "localhost";
    $username_db = "root";
    $password_db = "";
    $dbname = "uneti_login";

    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    if ($conn->connect_error) {
        $response['message'] = 'Lỗi kết nối CSDL: ' . $conn->connect_error;
        echo json_encode($response);
        exit;
    }

    // === BƯỚC 5: CHUẨN BỊ CÂU LỆNH SQL ===
    // Sửa 'ten_file_goc' thành 'ten_file' để khớp với CSDL của bạn
    // Bỏ qua cột 'ngay_tai_len' vì CSDL sẽ tự động thêm
    $sql = "INSERT INTO tai_lieu (ma_nguoi_dung, tieu_de, mo_ta, the_loai, ten_file, duong_dan_file, kich_thuoc_file, loai_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Thêm .$conn->error để báo lỗi chi tiết nếu có vấn đề
    if (!$stmt) {
        $response['message'] = 'Lỗi hệ thống khi chuẩn bị câu lệnh SQL: ' . $conn->error;
        $conn->close();
        echo json_encode($response);
        exit;
    }
    
    // === BƯỚC 6: XỬ LÝ LẶP QUA TỪNG TỆP TIN ===
    $files = $_FILES['documentFiles'];
    $file_count = count($files['name']);
    $successful_uploads = [];
    $failed_uploads = [];

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $failed_uploads[] = ['name' => $files['name'][$i], 'error' => 'Tệp bị lỗi trong quá trình tải lên.'];
            continue;
        }

        $ten_file_goc = basename($files['name'][$i]); // Giữ tên gốc để lưu vào CSDL
        $file_tmp_name = $files['tmp_name'][$i];
        $kich_thuoc_file = $files['size'][$i];
        $loai_file = $files['type'][$i];

        $upload_dir = 'uploads/' . $the_loai . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // --- Logic giữ tên gốc và xử lý trùng lặp ---
        $file_extension = strtolower(pathinfo($ten_file_goc, PATHINFO_EXTENSION));
        $file_name_only = pathinfo($ten_file_goc, PATHINFO_FILENAME);

        $ten_file_luu_tru = $ten_file_goc; // Tên file để lưu trên ổ đĩa
        $duong_dan_file = $upload_dir . $ten_file_luu_tru;
        $counter = 1;

        while (file_exists($duong_dan_file)) {
            $ten_file_luu_tru = $file_name_only . ' (' . $counter . ').' . $file_extension;
            $duong_dan_file = $upload_dir . $ten_file_luu_tru;
            $counter++;
        }
        
        // Di chuyển tệp với tên có thể đã được thay đổi
        if (move_uploaded_file($file_tmp_name, $duong_dan_file)) {
            // Luôn lưu TÊN GỐC vào CSDL, còn đường dẫn là đường dẫn thực tế
            $stmt->bind_param("isssssis", $ma_nguoi_dung, $tieu_de, $mo_ta, $the_loai, $ten_file_goc, $duong_dan_file, $kich_thuoc_file, $loai_file);
            
            if ($stmt->execute()) {
                $successful_uploads[] = $ten_file_luu_tru; 
            } else {
                $failed_uploads[] = ['name' => $ten_file_goc, 'error' => 'Lỗi khi lưu thông tin vào CSDL: ' . $stmt->error];
                unlink($duong_dan_file);
            }
        } else {
            $failed_uploads[] = ['name' => $ten_file_goc, 'error' => 'Không thể di chuyển tệp. Vui lòng kiểm tra quyền ghi của thư mục `uploads`.'];
        }
    }

    // === BƯỚC 7: ĐÓNG KẾT NỐI VÀ TẠO PHẢN HỒI CUỐI CÙNG ===
    $stmt->close();
    $conn->close();

    $success_count = count($successful_uploads);
    $fail_count = count($failed_uploads);

    if ($success_count > 0 && $fail_count == 0) {
        $response['status'] = 'success';
        $response['message'] = "Đã tải lên thành công tất cả {$success_count} tệp!";
    } elseif ($success_count > 0 && $fail_count > 0) {
        $response['status'] = 'partial_success';
        $response['message'] = "Hoàn tất: {$success_count} tệp thành công, {$fail_count} tệp thất bại.";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Tất cả {$fail_count} tệp đều không thể tải lên.";
    }
    
    $response['details']['success_files'] = $successful_uploads;
    $response['details']['failed_files'] = $failed_uploads;
}

// === BƯỚC 8: TRẢ KẾT QUẢ VỀ CHO JAVASCRIPT ===
echo json_encode($response);
?>