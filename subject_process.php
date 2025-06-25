<?php
// ... โค้ดเดิม (session_start(), require_once 'config.php', ตรวจสอบ admin login) ...

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- ส่วนสำหรับเพิ่มรายวิชา (โค้ดที่คุณมีอยู่แล้ว) ---
    if ($action == "add") {
        // ... โค้ดเพิ่มรายวิชา ...
    }
    // --- ส่วนสำหรับแก้ไขรายวิชา ---
    elseif ($action == "edit") {
        $subject_id = (int)$_POST['subject_id'];
        $subject_code = trim($_POST['subject_code']);
        $subject_name = trim($_POST['subject_name']);
        $credits = (int)$_POST['credits'];
        $description = trim($_POST['description']);
        $updated_at = date("Y-m-d H:i:s"); // กำหนดวันที่แก้ไข

        // ตรวจสอบข้อมูลไม่ให้ว่างเปล่า
        if (empty($subject_id) || empty($subject_code) || empty($subject_name) || empty($credits)) {
            $_SESSION['message'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
            $_SESSION['message_type'] = "danger";
            header("Location: subject_list.php");
            exit();
        }

        // ตรวจสอบรหัสวิชาซ้ำ ยกเว้นรหัสวิชาของตัวเอง (optional)
        $sql_check = "SELECT subject_id FROM subjects WHERE subject_code = ? AND subject_id != ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("si", $subject_code, $subject_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $_SESSION['message'] = "รหัสวิชานี้มีอยู่แล้วสำหรับวิชาอื่น";
                $_SESSION['message_type'] = "warning";
                header("Location: subject_list.php");
                exit();
            }
            $stmt_check->close();
        }

        // เตรียมคำสั่ง SQL สำหรับ UPDATE
        $sql = "UPDATE subjects SET subject_code = ?, subject_name = ?, credits = ?, description = ?, updated_at = ? WHERE subject_id = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssisss", $subject_code, $subject_name, $credits, $description, $updated_at, $subject_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "แก้ไขรายวิชาเรียบร้อยแล้ว!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "เกิดข้อผิดพลาดในการแก้ไขรายวิชา: " . $stmt->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $mysqli->error;
            $_SESSION['message_type'] = "danger";
        }
        header("Location: subject_list.php"); // Redirect กลับไปหน้าแสดงรายการ
        exit();
    }
} else {
    // ... โค้ดเดิม (การร้องขอไม่ถูกต้อง) ...
}
?>