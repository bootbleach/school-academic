<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ไฟล์นี้จะถูกเรียกโดย JavaScript (AJAX)
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$options = [];

// ดึงข้อมูลคลาสเรียนตามปีการศึกษา
if ($type === 'classes' && $id > 0) {
    $sql = "SELECT class_id, class_code, class_name FROM classes WHERE academic_year_id = ? ORDER BY class_code ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
} 
// ดึงข้อมูลวิชาตามคลาสเรียน (จากตารางสอน)
elseif ($type === 'subjects' && $id > 0) {
    // สำหรับ Admin จะเห็นทุกวิชาในคลาสนั้นๆ
    if (is_admin_loggedin()) {
        $sql = "SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name 
                FROM subjects s JOIN class_schedules cs ON s.subject_id = cs.subject_id 
                WHERE cs.class_id = ? ORDER BY s.subject_code ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
    } 
    // สำหรับครู จะเห็นเฉพาะวิชาที่ตัวเองมีสิทธิ์
    elseif (is_teacher_loggedin()) {
        $teacher_id = $_SESSION['teacher_id'];
        $sql = "SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name
                FROM class_schedules cs
                JOIN classes c ON cs.class_id = c.class_id
                JOIN subjects s ON cs.subject_id = s.subject_id
                WHERE (cs.teacher_id = ? OR c.teacher_id = ?) AND c.class_id = ?
                ORDER BY s.subject_code ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $teacher_id, $teacher_id, $id);
    }
}

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
header('Content-Type: application/json');
echo json_encode($options);
?>