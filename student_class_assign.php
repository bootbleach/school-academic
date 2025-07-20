<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php'; // ตรวจสอบว่ามีฟังก์ชัน is_admin_loggedin(), set_session_message(), get_session_message()

// ตรวจสอบสิทธิ์ Admin
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();
$errors = [];

// --- ส่วนที่ 1: รับค่าจาก URL และตรวจสอบความถูกต้อง ---
$student_id = $_GET['student_id'] ?? null;
$acad_year_id = $_GET['acad_year_id'] ?? null;

// ตรวจสอบว่าค่า student_id และ acad_year_id ถูกต้อง
if (!filter_var($student_id, FILTER_VALIDATE_INT) || !filter_var($acad_year_id, FILTER_VALIDATE_INT)) {
    set_session_message("danger", "ข้อมูลไม่ถูกต้อง กรุณาระบุนักเรียนและปีการศึกษาให้ถูกต้อง.");
    header("Location: student_class.php");
    exit();
}

// --- ส่วนที่ 2: ดึงข้อมูลที่จำเป็นสำหรับการแสดงผล ---

// ดึงข้อมูลนักเรียน
$student_info = null;
$stmt_student = $mysqli->prepare("SELECT student_code, prefix, first_name, last_name FROM students WHERE student_id = ?");
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
if ($result_student->num_rows > 0) {
    $student_info = $result_student->fetch_assoc();
} else {
    set_session_message("danger", "ไม่พบข้อมูลนักเรียนที่ระบุ.");
    header("Location: student_class.php");
    exit();
}
$stmt_student->close();

// ดึงข้อมูลปีการศึกษา
$academic_year_info = null;
$stmt_year = $mysqli->prepare("SELECT year FROM academic_years WHERE acad_year_id = ?");
$stmt_year->bind_param("i", $acad_year_id);
$stmt_year->execute();
$result_year = $stmt_year->get_result();
if ($result_year->num_rows > 0) {
    $academic_year_info = $result_year->fetch_assoc();
} else {
    set_session_message("danger", "ไม่พบข้อมูลปีการศึกษาที่ระบุ.");
    header("Location: student_class.php");
    exit();
}
$stmt_year->close();

// ดึงรายการห้องเรียนทั้งหมด
$all_classes = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");

// ดึงข้อมูลห้องเรียนปัจจุบันของนักเรียนสำหรับปีการศึกษานี้
$current_class_id = null;
$stmt_current_class = $mysqli->prepare("SELECT class_id FROM student_classes WHERE student_id = ? AND academic_year_id = ?");
$stmt_current_class->bind_param("ii", $student_id, $acad_year_id);
$stmt_current_class->execute();
$result_current_class = $stmt_current_class->get_result();
if ($result_current_class->num_rows > 0) {
    $row_current_class = $result_current_class->fetch_assoc();
    $current_class_id = $row_current_class['class_id'];
}
$stmt_current_class->close();

// --- ส่วนที่ 3: ประมวลผลเมื่อมีการส่งฟอร์ม (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_class_id = $_POST['class_id'] ?? null; // อาจเป็นค่าว่างถ้าเลือก "ไม่กำหนดห้องเรียน"

    $mysqli->begin_transaction(); // เริ่ม Transaction
    try {
        if (!empty($selected_class_id)) {
            // กรณีมีการเลือกห้องเรียน: ตรวจสอบและ INSERT/UPDATE
            $stmt_check_exist = $mysqli->prepare("SELECT COUNT(*) FROM student_classes WHERE student_id = ? AND academic_year_id = ?");
            $stmt_check_exist->bind_param("ii", $student_id, $acad_year_id);
            $stmt_check_exist->execute();
            $row_count = $stmt_check_exist->get_result()->fetch_row()[0];
            $stmt_check_exist->close();

            if ($row_count > 0) {
                // ถ้ามีอยู่แล้ว: UPDATE
                $stmt_update = $mysqli->prepare("UPDATE student_classes SET class_id = ? WHERE student_id = ? AND academic_year_id = ?");
                $stmt_update->bind_param("iii", $selected_class_id, $student_id, $acad_year_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // ถ้ายังไม่มี: INSERT
                $stmt_insert = $mysqli->prepare("INSERT INTO student_classes (student_id, class_id, academic_year_id, enrollment_date) VALUES (?, ?, ?, CURDATE())");
                $stmt_insert->bind_param("iii", $student_id, $selected_class_id, $acad_year_id);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            set_session_message("success", "กำหนดห้องเรียนของ " . htmlspecialchars($student_info['prefix'] . $student_info['first_name'] . ' ' . $student_info['last_name']) . " สำหรับปีการศึกษา " . htmlspecialchars($academic_year_info['year']) . " เรียบร้อยแล้ว.");

        } else {
            // กรณีเลือก "ไม่กำหนดห้องเรียน": DELETE record
            $stmt_delete = $mysqli->prepare("DELETE FROM student_classes WHERE student_id = ? AND academic_year_id = ?");
            $stmt_delete->bind_param("ii", $student_id, $acad_year_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            set_session_message("success", "ยกเลิกการกำหนดห้องเรียนของ " . htmlspecialchars($student_info['prefix'] . $student_info['first_name'] . ' ' . $student_info['last_name']) . " สำหรับปีการศึกษา " . htmlspecialchars($academic_year_info['year']) . " เรียบร้อยแล้ว.");
        }

        $mysqli->commit(); // Commit Transaction
        header("Location: student_class.php?acad_year_id=" . htmlspecialchars($acad_year_id)); // กลับไปหน้า student_class.php พร้อมปีการศึกษาที่เลือกไว้
        exit();

    } catch (mysqli_sql_exception $e) {
        $mysqli->rollback(); // Rollback ถ้ามีข้อผิดพลาด
        set_session_message("danger", "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
        // ไม่ต้อง redirect เพราะต้องการให้แสดง error บนหน้าเดิม
    }
}


$page_title = "จัดการห้องเรียนนักเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-clipboard-user me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_class.php?acad_year_id=<?php echo htmlspecialchars($acad_year_id); ?>" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">กำหนดห้องเรียนสำหรับ:</h5>
                    <h4 class="mb-0 text-primary">
                        <?php echo htmlspecialchars($student_info['prefix'] . $student_info['first_name'] . ' ' . $student_info['last_name']); ?> 
                        (รหัส: <?php echo htmlspecialchars($student_info['student_code']); ?>)
                    </h4>
                    <h5 class="mb-0">
                        สำหรับปีการศึกษา: <span class="text-info"><?php echo htmlspecialchars($academic_year_info['year']); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="student_class_assign.php?student_id=<?php echo htmlspecialchars($student_id); ?>&acad_year_id=<?php echo htmlspecialchars($acad_year_id); ?>" method="post">
                        <div class="mb-3">
                            <label for="class_id" class="form-label">เลือกห้องเรียน</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">-- ไม่กำหนดห้องเรียน --</option>
                                <?php foreach ($all_classes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class['class_id']); ?>" 
                                            <?php if ($current_class_id == $class['class_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="student_class.php?acad_year_id=<?php echo htmlspecialchars($acad_year_id); ?>" class="btn btn-secondary px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึกการกำหนดห้องเรียน</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>