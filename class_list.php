<?php
session_start();
// ต้องแน่ใจว่า config.php มีการเชื่อมต่อ $mysqli
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();

// --- การจัดการลบข้อมูลคลาสพื้นฐาน ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']); // ตรวจสอบว่าเป็น int เพื่อความปลอดภัย
    $mysqli->begin_transaction();
    try {
        // ลบคลาสหลักจากตาราง `classes`
        // การลบนี้จะทริกเกอร์การลบข้อมูลที่เกี่ยวข้องใน `academic_class_instances`
        // และต่อเนื่องไปถึง `class_schedules`, `enrollments`, `student_classes`
        // โดยอัตโนมัติผ่าน Foreign Key ที่ตั้งค่า ON DELETE CASCADE ไว้
        $stmt_class = $mysqli->prepare("DELETE FROM classes WHERE class_id = ?");
        if (!$stmt_class) {
            throw new Exception("Error preparing statement: " . $mysqli->error);
        }
        $stmt_class->bind_param("i", $id);
        if (!$stmt_class->execute()) {
            throw new Exception("Error executing delete statement: " . $stmt_class->error);
        }
        $stmt_class->close();

        $mysqli->commit();
        set_session_message('ลบคลาสเรียนพื้นฐานและข้อมูลที่เกี่ยวข้องทั้งหมดสำเร็จ!', 'success');
    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage(), 'danger');
        error_log("Delete class error: " . $e->getMessage()); // บันทึกข้อผิดพลาดสำหรับการดีบัก
    }
    header("Location: class_list.php");
    exit();
}

// --- ดึงข้อมูลคลาสพื้นฐานทั้งหมด ---
// ดึงข้อมูลจากตาราง `classes` เท่านั้น ไม่ต้อง JOIN กับ `teachers` หรือดึง `room_number`
$sql_fetch = "SELECT
                    c.class_id, c.class_code, c.class_name
                FROM classes c
                ORDER BY c.class_code ASC"; // เรียงตามรหัสคลาส
$classes = $mysqli->query($sql_fetch);

// --- ดึง ID ของปีการศึกษาปัจจุบัน (ยังคงเก็บไว้หากต้องการใช้ในอนาคต เช่น ลิงก์ไปยัง manage_class_subjects) ---
$current_acad_year_id = null;
$stmt_current_year = $mysqli->prepare("SELECT acad_year_id FROM academic_years WHERE is_current_year = 1 LIMIT 1");
if ($stmt_current_year) {
    $stmt_current_year->execute();
    $result_current_year = $stmt_current_year->get_result();
    if ($result_current_year->num_rows > 0) {
        $current_acad_year_id = $result_current_year->fetch_assoc()['acad_year_id'];
    } else {
        // หากไม่พบปีการศึกษาปัจจุบัน อาจแสดงข้อความเตือน หรือจัดการตามความเหมาะสม
        // set_session_message('ไม่พบปีการศึกษาปัจจุบัน เพื่อจัดการวิชาและตารางสอน.', 'warning');
    }
    $stmt_current_year->close();
} else {
    error_log("Error preparing current academic year statement: " . $mysqli->error);
}


$page_title = "จัดการคลาสเรียนพื้นฐาน"; // เปลี่ยนชื่อหน้าให้ชัดเจนขึ้น
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_add.php" class="btn btn-primary ms-auto"><i class="fas fa-plus-circle me-2"></i>สร้างห้องเรียนพื้นฐานใหม่</a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสคลาส (Class ID)</th> <th>รหัสห้อง (Class Code)</th>
                                    <th>ชื่อคลาส/ห้องเรียน (Class Name)</th>
                                    <th class="text-end" style="width: 200px;">จัดการ</th> </tr>
                            </thead>
                            <tbody>
                                <?php if ($classes && $classes->num_rows > 0): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_id']); ?></td>
                                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td class="text-end">
                                                <a href="class_edit.php?id=<?php echo $class['class_id']; ?>" class="btn btn-warning btn-sm me-1" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="class_list.php?delete_id=<?php echo $class['class_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบ? การลบห้องเรียนพื้นฐานนี้จะส่งผลให้ข้อมูลห้องเรียนรายปีทั้งหมด (Academic Class Instances) และข้อมูลที่เกี่ยวข้อง (เช่น ตารางสอน, การลงทะเบียน) ที่อ้างอิงถึงห้องเรียนนี้ถูกลบออกทั้งหมด คุณแน่ใจหรือไม่?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">ยังไม่มีข้อมูลคลาสเรียนพื้นฐาน</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>