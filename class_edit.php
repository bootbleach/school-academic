<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin() || !isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: class_list.php");
    exit();
}
$class_id = $_GET['id'];

// --- ดึงข้อมูลคลาสปัจจุบัน (เฉพาะคอลัมน์ที่มีในตาราง classes) ---
// แก้ไข SELECT ให้ดึงเฉพาะคอลัมน์ที่มีในตาราง classes
$stmt_fetch = $mysqli->prepare("SELECT class_id, class_code, class_name FROM classes WHERE class_id = ?");

// เพิ่มการตรวจสอบว่า prepare สำเร็จหรือไม่
if (!$stmt_fetch) {
    error_log("Error preparing fetch statement: " . $mysqli->error);
    set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับดึงข้อมูลคลาส: ' . $mysqli->error, 'danger');
    header("Location: class_list.php");
    exit();
}

$stmt_fetch->bind_param("i", $class_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();

if ($result->num_rows === 0) {
    set_session_message('ไม่พบคลาสเรียนที่ต้องการแก้ไข', 'danger');
    header("Location: class_list.php");
    exit();
}
$class_data_from_db = $result->fetch_assoc();
$stmt_fetch->close();

// กำหนดค่าเริ่มต้นสำหรับแสดงในฟอร์ม
$class_code = $class_data_from_db['class_code'] ?? '';
$class_name = $class_data_from_db['class_name'] ?? '';
// ลบคอลัมน์ที่ไม่มีในตาราง classes ออก
// $teacher_id = null;
// $room_number = '';
// $max_students = null;

$errors = [];

// จัดการการ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าใหม่จากฟอร์มมาใส่ในตัวแปรเดิม เพื่อให้ฟอร์มจำค่า
    $class_code = trim($_POST['class_code'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');

    // ลบการรับค่าจากคอลัมน์ที่ไม่มีในตาราง classes ออก
    // $teacher_id = $_POST['teacher_id'] ?? null;
    // $room_number = trim($_POST['room_number'] ?? '');
    // $max_students = !empty($_POST['max_students']) ? trim($_POST['max_students']) : null;

    // --- Validation logic ---
    if (empty($class_name)) {
        $errors['class_name'] = 'กรุณากรอกชื่อคลาส/ห้องเรียน';
    } elseif (!is_value_unique('classes', 'class_name', $class_name, $class_id, 'class_id')) {
        $errors['class_name'] = 'ชื่อคลาส/ห้องเรียนนี้มีอยู่แล้ว';
    }

    if (empty($class_code)) {
        $errors['class_code'] = 'กรุณากรอกรหัสคลาส';
    } elseif (!is_value_unique('classes', 'class_code', $class_code, $class_id, 'class_id')) {
        $errors['class_code'] = 'รหัสคลาสนี้มีอยู่แล้ว';
    }

    // ลบ Validation สำหรับคอลัมน์ที่ไม่มีในตาราง classes ออก
    // if (!empty($teacher_id) && !is_numeric($teacher_id)) {
    //     $errors['teacher_id'] = 'ครูผู้สอนไม่ถูกต้อง';
    // }
    // if (!empty($room_number) && !preg_match('/^[a-zA-Z0-9\s-]+$/', $room_number)) {
    //     $errors['room_number'] = 'หมายเลขห้องไม่ถูกต้อง (อนุญาตเฉพาะตัวอักษร, ตัวเลข, ช่องว่าง, ขีดกลาง)';
    // }
    // if (!is_null($max_students) && (!is_numeric($max_students) || $max_students <= 0)) {
    //     $errors['max_students'] = 'จำนวนนักเรียนสูงสุดต้องเป็นตัวเลขจำนวนเต็มบวก';
    // } elseif (is_numeric($max_students)) {
    //     $max_students = (int)$max_students;
    // }
    // --- End Validation logic ---

    if (empty($errors)) {
        // อัปเดตข้อมูลในฐานข้อมูล
        // เตรียม SQL query และ parameters สำหรับตาราง classes เท่านั้น
        $sql_parts = [];
        $params = [];
        $types = "";

        $sql_parts[] = "class_code = ?";
        $params[] = $class_code;
        $types .= "s";

        $sql_parts[] = "class_name = ?";
        $params[] = $class_name;
        $types .= "s";

        // เพิ่ม updated_at เพื่อบันทึกเวลาที่แก้ไข
        $sql_parts[] = "updated_at = CURRENT_TIMESTAMP()";

        $params[] = $class_id; // สำหรับ WHERE clause
        $types .= "i"; // สำหรับ class_id

        $sql = "UPDATE classes SET " . implode(", ", $sql_parts) . " WHERE class_id=?";

        if ($stmt = $mysqli->prepare($sql)) {
            // Bind parameters dynamically
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                set_session_message('อัปเดตข้อมูลคลาสเรียนสำเร็จ!', 'success');
                header("Location: class_list.php");
                exit();
            } else {
                set_session_message('เกิดข้อผิดพลาดในการอัปเดตข้อมูลคลาสเรียน: ' . $stmt->error, 'danger');
            }
            $stmt->close();
        } else {
            set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $mysqli->error, 'danger');
        }
    } else {
        set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
    }
}

$page_title = "แก้ไขคลาสเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php echo get_session_message(); // แสดงข้อความแจ้งเตือนจาก session ?>

            <div class="card">
                <div class="card-header">
                    <h5>ข้อมูลคลาส: <?php echo htmlspecialchars($class_name); ?></h5>
                </div>
                <div class="card-body p-4">
                    <form action="class_edit.php?id=<?php echo $class_id; ?>" method="post" novalidate>
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
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
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