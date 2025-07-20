<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php'; // ตรวจสอบว่ามีฟังก์ชัน is_value_unique ในไฟล์นี้

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

// --- ไม่จำเป็นต้องดึงข้อมูลครูผู้สอนสำหรับหน้านี้แล้ว เพราะจะไม่มีฟิลด์ teacher_id ในฟอร์มนี้ ---
// $teachers_result = $mysqli->query("SELECT teacher_id, fullname FROM teachers ORDER BY fullname ASC");
// if (!$teachers_result) {
//     error_log("Error fetching teachers: " . $mysqli->error);
//     set_session_message('เกิดข้อผิดพลาดในการดึงข้อมูลครูผู้สอน: ' . $mysqli->error, 'danger');
//     $teachers = [];
// } else {
//     $teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);
// }

// --- กำหนดค่าเริ่มต้นสำหรับฟอร์ม (เฉพาะคอลัมน์ที่มีในตาราง classes) ---
$class_code = '';
$class_name = '';
$errors = [];

// --- จัดการการ POST เพื่อเพิ่มคลาสเรียนใหม่ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $class_code = trim($_POST['class_code'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');

    // --- Validation logic ---
    if (empty($class_name)) {
        $errors['class_name'] = 'กรุณากรอกชื่อคลาส/ห้องเรียน';
    } elseif (!is_value_unique('classes', 'class_name', $class_name, null, 'class_id')) {
        $errors['class_name'] = 'ชื่อคลาส/ห้องเรียนนี้มีอยู่แล้ว';
    }

    if (empty($class_code)) {
        $errors['class_code'] = 'กรุณากรอกรหัสคลาส';
    } elseif (!is_value_unique('classes', 'class_code', $class_code, null, 'class_id')) {
        $errors['class_code'] = 'รหัสคลาสนี้มีอยู่แล้ว';
    }

    // --- End Validation logic ---

    if (empty($errors)) {
        // --- บันทึกข้อมูลคลาสเรียนใหม่ลงฐานข้อมูล (เฉพาะ class_code และ class_name) ---
        // ตาราง classes มี created_at และ updated_at ที่ตั้ง DEFAULT เป็น CURRENT_TIMESTAMP() แล้ว
        $sql = "INSERT INTO classes (class_code, class_name) VALUES (?, ?)";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ss", $class_code, $class_name);
            
            if ($stmt->execute()) {
                set_session_message('เพิ่มคลาสเรียนใหม่สำเร็จ!', 'success');
                header("Location: class_list.php"); // ไปยังหน้ารายการคลาสเรียน
                exit();
            } else {
                set_session_message('เกิดข้อผิดพลาดในการเพิ่มคลาสเรียน: ' . $stmt->error, 'danger');
            }
            $stmt->close();
        } else {
            set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $mysqli->error, 'danger');
        }
    } else {
        set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
    }
}

$page_title = "เพิ่มคลาสเรียนใหม่";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-plus-square me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php echo get_session_message(); // แสดงข้อความแจ้งเตือนจาก session ?>

            <div class="card">
                <div class="card-header">
                    <h5>ข้อมูลคลาสเรียนใหม่</h5>
                </div>
                <div class="card-body p-4">
                    <form action="class_add.php" method="post" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="class_name" class="form-label">ชื่อคลาส/ห้องเรียน <span class="text-danger">*</span></label>
                                <input type="text" name="class_name" id="class_name" class="form-control <?php echo isset($errors['class_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($class_name); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['class_name'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="class_code" class="form-label">รหัสคลาส <span class="text-danger">*</span></label>
                                <input type="text" name="class_code" id="class_code" class="form-control <?php echo isset($errors['class_code']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($class_code); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['class_code'] ?? ''; ?></div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-plus-circle me-2"></i>เพิ่มคลาสเรียน</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> <strong>หมายเหตุ:</strong> การกำหนดครูผู้สอนประจำคลาส หมายเลขห้อง และจำนวนนักเรียนสูงสุด สำหรับคลาสนี้ในแต่ละปีการศึกษา จะทำในส่วนของการจัดการ **<a href="class_schedules.php" class="alert-link">ตารางเรียน</a>** หรือส่วนที่เกี่ยวข้องกับ `academic_class_instances` ซึ่งเป็นที่ที่คุณเชื่อมโยงคลาสกับวิชาและปีการศึกษาครับ
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>