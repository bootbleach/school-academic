<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();

// --- การจัดการลบข้อมูล ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // การใช้ Transaction เพื่อความปลอดภัย
    $mysqli->begin_transaction();
    try {
        // ลบตารางสอนที่เกี่ยวข้องก่อน
        $stmt_schedule = $mysqli->prepare("DELETE FROM class_schedules WHERE class_id = ?");
        $stmt_schedule->bind_param("i", $id);
        $stmt_schedule->execute();
        $stmt_schedule->close();

        // ลบคลาสหลัก
        $stmt_class = $mysqli->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt_class->bind_param("i", $id);
        $stmt_class->execute();
        $stmt_class->close();

        $mysqli->commit();
        set_session_message('ลบคลาสเรียนและตารางสอนที่เกี่ยวข้องสำเร็จ!', 'success');
    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage(), 'danger');
    }
    header("Location: class_list.php");
    exit();
}

// --- ดึงข้อมูลคลาสทั้งหมด (แก้ไข SQL ไม่ให้มี subject_name) ---
$sql_fetch = "SELECT 
                c.class_id, c.class_code, c.class_name, c.room_number,
                t.fullname AS teacher_name,
                ay.year AS academic_year_name
            FROM classes c
            LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id
            ORDER BY ay.year DESC, c.class_code ASC";
$classes = $mysqli->query($sql_fetch);

$page_title = "จัดการคลาสเรียน (ห้องเรียน)";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_add.php" class="btn btn-primary ms-auto"><i class="fas fa-plus-circle me-2"></i>สร้างห้องเรียนใหม่</a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสคลาส</th>
                                    <th>ชื่อคลาส/ห้องเรียน</th>
                                    <th>ครูประจำชั้น</th>
                                    <th>ปีการศึกษา/เทอม</th>
                                    <th>ห้องเรียน</th>
                                    <th class="text-end" style="width: 220px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($classes && $classes->num_rows > 0): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($class['academic_year_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($class['room_number'] ?? '-'); ?></td>
                                            <td class="text-end">
                                                <a href="class_schedule.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-info btn-sm" title="จัดการตารางสอน">
                                                    <i class="fas fa-table"></i> ตารางสอน
                                                </a>
                                                <a href="class_edit.php?id=<?php echo $class['class_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="class_list.php?delete_id=<?php echo $class['class_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบ? ข้อมูลตารางสอนของคลาสนี้จะถูกลบไปด้วย')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-3">ยังไม่มีข้อมูลคลาสเรียน</td></tr>
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