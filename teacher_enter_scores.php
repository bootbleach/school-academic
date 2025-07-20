<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// 1. ตรวจสอบสิทธิ์ Admin หรือ Teacher และตรวจสอบว่ามีพารามิเตอร์ครบหรือไม่
if (!is_admin_loggedin() && !is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}
if (!isset($_GET['class_id']) || !filter_var($_GET['class_id'], FILTER_VALIDATE_INT) || !isset($_GET['subject_id']) || !filter_var($_GET['subject_id'], FILTER_VALIDATE_INT)) {
    header("Location: " . (is_admin_loggedin() ? "admin_enter_scores.php" : "teacher_classes.php"));
    exit();
}

$class_id = (int)$_GET['class_id'];
$subject_id = (int)$_GET['subject_id'];
$is_admin = is_admin_loggedin();
$teacher_id = $_SESSION['teacher_id'] ?? null;
$message = get_session_message();

// 2. ดึงข้อมูลวิชา, คลาส และตรวจสอบสิทธิ์
$stmt_info = $mysqli->prepare("SELECT c.class_name, c.class_code, s.subject_name, s.credits FROM classes c, subjects s WHERE c.class_id = ? AND s.subject_id = ?");
$stmt_info->bind_param("ii", $class_id, $subject_id);
$stmt_info->execute();
$class_subject_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$class_subject_info) {
    set_session_message('ไม่พบข้อมูลวิชาหรือห้องเรียนที่ระบุ', 'danger');
    header("Location: " . ($is_admin ? "admin_enter_scores.php" : "teacher_classes.php"));
    exit();
}

$subject_credits = $class_subject_info['credits'];
$is_pass_fail_subject = ((float)$subject_credits == 0);

// --- ส่วนที่เพิ่มเพื่อดึงข้อมูลปีการศึกษาปัจจุบัน ---
$current_academic_year_text = '';
$current_academic_year_id = null;
$academic_year_query = $mysqli->query("SELECT acad_year_id, year FROM academic_years WHERE is_current_year = 1 LIMIT 1");
if ($academic_year_query && $academic_year_row = $academic_year_query->fetch_assoc()) {
    $current_academic_year_text = $academic_year_row['year'];
    $current_academic_year_id = $academic_year_row['acad_year_id'];
} else {
    // หากไม่พบปีการศึกษาปัจจุบัน ให้ตั้งค่าข้อความแจ้งเตือน
    set_session_message('ไม่พบปีการศึกษาปัจจุบัน กรุณากำหนดปีการศึกษาปัจจุบันในระบบก่อน', 'danger');
}
// --- สิ้นสุดส่วนที่เพิ่ม ---


// 3. จัดการการบันทึกข้อมูล (POST Request) ให้สมบูรณ์
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli->begin_transaction();
    try {
        if ($is_pass_fail_subject) {
            // --- บันทึกเกรดแบบ ผ/มผ ---
            $grades = $_POST['grades'] ?? [];
            // โค้ดส่วนนี้ไม่ได้ถูกเปลี่ยนแปลงตามคำขอ
            $sql_upsert = "INSERT INTO enrollments (student_id, class_id, subject_id, grade, score, grade_point) VALUES (?, ?, ?, ?, NULL, NULL)
                           ON DUPLICATE KEY UPDATE grade=VALUES(grade), score=NULL, grade_point=NULL";
            $stmt = $mysqli->prepare($sql_upsert);

            foreach($grades as $student_id => $grade) {
                $grade_value = in_array($grade, ['ผ', 'มผ']) ? $grade : null;
                $stmt->bind_param("iiis", $student_id, $class_id, $subject_id, $grade_value);
                $stmt->execute();
            }
        } else {
            // --- บันทึกเกรดแบบคะแนนปกติ ---
            $scores = $_POST['scores'] ?? [];
            // โค้ดส่วนนี้ไม่ได้ถูกเปลี่ยนแปลงตามคำขอ
            $sql_upsert = "INSERT INTO enrollments (student_id, class_id, subject_id, score, grade, grade_point) VALUES (?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE score=VALUES(score), grade=VALUES(grade), grade_point=VALUES(grade_point)";
            $stmt = $mysqli->prepare($sql_upsert);

            foreach ($scores as $student_id => $score) {
                $score_value = (isset($score) && $score !== '') ? floatval($score) : null;
                // ส่งค่าหน่วยกิต ($subject_credits) เข้าไปในฟังก์ชันด้วย
                $grade_info = calculate_grade_from_score($score_value, $subject_credits); // ต้องมีฟังก์ชันนี้ใน admin_functions.php
                $grade = $grade_info['grade'] ?? null;
                $grade_point = $grade_info['grade_point'] ?? null;
                
                $stmt->bind_param("iiidss", $student_id, $class_id, $subject_id, $score_value, $grade, $grade_point);
                $stmt->execute();
            }
        }
        $stmt->close();
        $mysqli->commit();
        set_session_message('บันทึกผลการเรียนเรียบร้อย!', 'success');

    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage(), 'danger');
    }
    header("Location: teacher_enter_scores.php?class_id=$class_id&subject_id=$subject_id");
    exit();
}


// 4. ดึงรายชื่อนักเรียนและคะแนน/เกรดเดิม
$students = [];
// โค้ด SQL ส่วนนี้ไม่ได้ถูกเปลี่ยนแปลงตามคำขอ
$sql_students = "SELECT s.student_id, s.student_code, s.prefix, s.first_name, s.last_name, e.score, e.grade
                    FROM students s
                    JOIN student_classes sc ON s.student_id = sc.student_id
                    JOIN academic_years ay ON sc.academic_year_id = ay.acad_year_id
                    LEFT JOIN enrollments e ON s.student_id = e.student_id AND e.class_id = ? AND e.subject_id = ?
                    WHERE sc.class_id = ? AND ay.is_current_year = 1
                    ORDER BY s.student_code ASC";
if ($stmt_students = $mysqli->prepare($sql_students)) {
    $stmt_students->bind_param("iii", $class_id, $subject_id, $class_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    while($row = $result_students->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt_students->close();
}


$page_title = "กรอกผลการเรียน";
if ($is_admin) { require_once 'includes/admin_header.php'; require_once 'includes/admin_sidebar.php'; }
else { require_once 'includes/teacher_header.php'; require_once 'includes/teacher_sidebar.php'; }
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                    <h5 class="text-muted">
                        วิชา: <?php echo htmlspecialchars($class_subject_info['subject_name']); ?> | 
                        ห้องเรียน: <?php echo htmlspecialchars($class_subject_info['class_name']); ?>
                        <?php if ($current_academic_year_text): // เพิ่ม ปีการศึกษาตรงนี้ ?>
                        | ปีการศึกษา: <?php echo htmlspecialchars($current_academic_year_text); ?>
                        <?php endif; ?>
                    </h5>
                </div>
                <a href="<?php echo $is_admin ? 'admin_enter_scores.php' : 'teacher_classes.php'; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php echo $message; ?>

            <form method="post" action="teacher_enter_scores.php?class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject_id; ?>">
                <div class="card">
                    <div class="card-body">
                           <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>เลขที่</th>
                                            <th>รหัสนักเรียน</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th style="width: 25%;">
                                                <?php echo $is_pass_fail_subject ? 'ผลการเรียน (ผ/มผ)' : 'คะแนน (เต็ม 100)'; ?>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($students)): ?>
                                            <tr><td colspan="4" class="text-center py-4">ไม่พบนักเรียนในคลาสนี้</td></tr>
                                        <?php else: ?>
                                            <?php $row_number = 1; ?>
                                            <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo $row_number++; ?></td>
                                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                <td><?php echo htmlspecialchars($student['prefix'].$student['first_name'].' '.$student['last_name']); ?></td>
                                                <td>
                                                    <?php if ($is_pass_fail_subject): ?>
                                                        <select name="grades[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                                            <option value="" <?php if(empty($student['grade'])) echo 'selected'; ?>>-- เลือกผล --</option>
                                                            <option value="ผ" <?php if($student['grade'] == 'ผ') echo 'selected'; ?>>ผ (ผ่าน)</option>
                                                            <option value="มผ" <?php if($student['grade'] == 'มผ') echo 'selected'; ?>>มผ (ไม่ผ่าน)</option>
                                                        </select>
                                                    <?php else: ?>
                                                        <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                                               name="scores[<?php echo $student['student_id']; ?>]" 
                                                               value="<?php echo htmlspecialchars($student['score'] ?? ''); ?>">
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                           </div>
                    </div>
                    <?php if(!empty($students)): ?>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>บันทึกผลการเรียน</button>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</div>

<?php 
if($is_admin) { require_once 'includes/admin_footer.php'; }
else { require_once 'includes/teacher_footer.php'; } 
?>