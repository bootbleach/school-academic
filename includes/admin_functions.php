<?php
// ฟังก์ชันสำหรับตรวจสอบการล็อกอินของ Admin
function is_admin_loggedin() {
    return isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true && $_SESSION['role'] === 'admin';
}

// ===== เพิ่มฟังก์ชันสำหรับครู =====
function is_teacher_loggedin() {
    return isset($_SESSION['teacher_loggedin']) && $_SESSION['teacher_loggedin'] === true && $_SESSION['role'] === 'teacher';
}

// ===== เพิ่มฟังก์ชันสำหรับนักเรียน =====
function is_student_loggedin() {
    return isset($_SESSION['student_loggedin']) && $_SESSION['student_loggedin'] === true && $_SESSION['role'] === 'student';
}


// ฟังก์ชันสำหรับตั้งค่าข้อความแจ้งเตือน
function set_session_message($message, $type = 'info') {
    $_SESSION['message'] = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                              ' . $message . '
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
}

// ฟังก์ชันสำหรับดึงและล้างข้อความแจ้งเตือน
function get_session_message() {
    $message = '';
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
    }
    return $message;
}

// ฟังก์ชันสำหรับตรวจสอบข้อมูลซ้ำในฐานข้อมูล
function is_value_unique($table, $column, $value, $ignore_id = null, $id_column = 'id') {
    global $mysqli;
    
    $sql = "SELECT `$id_column` FROM `$table` WHERE `$column` = ?";
    $params = ['s', $value];

    if ($ignore_id !== null) {
        $sql .= " AND `$id_column` != ?";
        $params[0] .= 'i';
        $params[] = $ignore_id;
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $is_unique = $stmt->get_result()->num_rows === 0;
    $stmt->close();
    
    return $is_unique;
}

/**
 * คำนวณเกรด (แก้ไขใหม่ให้รองรับวิชา 0 หน่วยกิต)
 * @param int|float|null $score คะแนนดิบ
 * @param int|float $credits หน่วยกิตของวิชา
 * @return array|null
 */
function calculate_grade_from_score($score, $credits = 1.0) { // เพิ่ม $credits
    if ($score === null || $score < 0 || $score > 100) {
        return ['grade' => null, 'grade_point' => null];
    }

    // --- ตรวจสอบว่าเป็นวิชา 0 หน่วยกิต (ผ/มผ) หรือไม่ ---
    if ((float)$credits == 0) {
        if ($score >= 50) {
            return ['grade' => 'ผ', 'grade_point' => null]; // ผ่าน
        } else {
            return ['grade' => 'มผ', 'grade_point' => null]; // ไม่ผ่าน
        }
    } 
    // --- ถ้าไม่ใช่ 0 หน่วยกิต ให้ใช้เกณฑ์ปกติ ---
    else {
        if ($score >= 80) {
            return ['grade' => 'A', 'grade_point' => 4.00];
        } elseif ($score >= 75) {
            return ['grade' => 'B+', 'grade_point' => 3.50];
        } elseif ($score >= 70) {
            return ['grade' => 'B', 'grade_point' => 3.00];
        } elseif ($score >= 65) {
            return ['grade' => 'C+', 'grade_point' => 2.50];
        } elseif ($score >= 60) {
            return ['grade' => 'C', 'grade_point' => 2.00];
        } elseif ($score >= 55) {
            return ['grade' => 'D+', 'grade_point' => 1.50];
        } elseif ($score >= 50) {
            return ['grade' => 'D', 'grade_point' => 1.00];
        } else {
            return ['grade' => 'ร', 'grade_point' => 0.00];
        }
    }
}