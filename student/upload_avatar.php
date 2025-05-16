<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed) && $file['size'] < 2 * 1024 * 1024) { // <2MB
        $newName = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $uploadDir = '../uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Xóa ảnh cũ nếu có
            $stmt = $conn->prepare("SELECT avatar FROM sinhvien WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old = $result->fetch_assoc();
            
            if ($old && !empty($old['avatar']) && file_exists($uploadDir . $old['avatar'])) {
                unlink($uploadDir . $old['avatar']);
            }
            $stmt->close();

            // Cập nhật tên file vào DB
            $stmt = $conn->prepare("UPDATE sinhvien SET avatar = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newName, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = "Cập nhật ảnh đại diện thành công!";
        } else {
            $_SESSION['error'] = "Không thể tải ảnh lên. Vui lòng thử lại!";
        }
    } else {
        $_SESSION['error'] = "File không hợp lệ. Chỉ chấp nhận ảnh JPG, JPEG, PNG, GIF và kích thước < 2MB!";
    }
}

$conn->close();
header('Location: dashboard.php');
exit; 