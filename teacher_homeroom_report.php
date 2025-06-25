<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบว่าเป็นครูที่ล็อกอินอยู่หรือไม่
if (!is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// ดึงข้อมูลห้องเรียนที่ครูคนนี้เป็น "ครูประจำชั้น"
$homerooms = [];
$sql = "SELECT c.class_id, c.class_code, c.class_name, ay.year AS academic_year_name
        FROM classes c
        JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id
        WHERE c.teacher_id = ?
        ORDER BY ay.year DESC, c.class_code ASC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $homerooms[] = $row;
    }
    $stmt->close();
}

$page_title = "รายงานห้องเรียนประจำชั้น";
require_once 'includes/teacher_header.php';
require_once 'includes/teacher_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <h1><i class="fas fa-print me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <p>เลือกห้องเรียนที่คุณเป็นครูประจำชั้นเพื่อดูรายงานผลการเรียนสรุป</p>
            <hr>

            <div class="list-group">
                <?php if (empty($homerooms)): ?>
                    <div class="alert alert-info">คุณไม่ได้เป็นครูประจำชั้นของห้องใดๆ</div>
                <?php else: ?>
                    <?php foreach ($homerooms as $room): ?>
                        <a href="view_report_details.php?class_id=<?php echo $room['class_id']; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($room['class_name']); ?> (<?php echo htmlspecialchars($room['class_code']); ?>)</h5>
                                <small class="text-muted">ปีการศึกษา <?php echo htmlspecialchars($room['academic_year_name']); ?></small>
                            </div>
                            <span class="badge bg-dark p-2">ดูรายงาน <i class="fas fa-chevron-right ms-1"></i></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/teacher_footer.php'; ?>