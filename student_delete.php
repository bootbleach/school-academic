<?php
session_start();
require_once 'config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่า Admin ล็อกอินอยู่หรือไม่ และมีบทบาทเป็น Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งค่า ID มาหรือไม่
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $student_id_to_delete = trim($_GET["id"]);

    // เตรียมคำสั่ง SQL สำหรับ DELETE
    $sql = "DELETE FROM students WHERE student_id = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $student_id_to_delete;

        // รันคำสั่ง SQL
        if ($stmt->execute()) {
            $_SESSION['message'] = "ลบข้อมูลนักเรียนสำเร็จ!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "ไม่พบ ID นักเรียนที่ต้องการลบ.";
    $_SESSION['message_type'] = "danger";
}

$mysqli->close();
header("Location: student_list.php"); // Redirect กลับไปหน้ารายการนักเรียน
exit();
?>