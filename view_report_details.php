<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php'; // ตรวจสอบว่ามีฟังก์ชันนี้อยู่จริง

// 1. ตรวจสอบสิทธิ์การเข้าถึง (Admin หรือ Teacher)
if (!is_admin_loggedin() && !is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}

// 2. ตรวจสอบว่ามี class_id ส่งมาหรือไม่
if (!isset($_GET['class_id']) || !filter_var($_GET['class_id'], FILTER_VALIDATE_INT)) {
    $redirect_page = is_admin_loggedin() ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    set_session_message('ไม่พบข้อมูลห้องเรียน', 'danger');
    header("Location: " . $redirect_page);
    exit();
}

$class_id = (int)$_GET['class_id'];
$is_admin = is_admin_loggedin();
$teacher_id = $_SESSION['teacher_id'] ?? null;

// 3. ตรวจสอบสิทธิ์การเข้าถึงหน้ารายงาน
$has_permission = false;
if ($is_admin) {
    $has_permission = true; // Admin เข้าได้เสมอ
} else {
    // ครูจะเข้าได้ก็ต่อเมื่อเป็นครูประจำชั้นของห้องนี้
    $stmt_perm = $mysqli->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    if ($stmt_perm) {
        $stmt_perm->bind_param("ii", $class_id, $teacher_id);
        $stmt_perm->execute();
        if ($stmt_perm->get_result()->num_rows > 0) {
            $has_permission = true;
        }
        $stmt_perm->close();
    }
}

if (!$has_permission) {
    set_session_message('คุณไม่มีสิทธิ์ดูรายงานของห้องเรียนนี้', 'danger');
    $redirect_page = $is_admin ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    header("Location: " . $redirect_page);
    exit();
}

// 4. ดึงข้อมูลสำหรับสร้างรายงาน (ใช้ Prepared Statements)
$class_info = null;
$stmt_class_info = $mysqli->prepare("SELECT c.*, ay.year as academic_year_name FROM classes c JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id WHERE c.class_id = ?");
if ($stmt_class_info) {
    $stmt_class_info->bind_param("i", $class_id);
    $stmt_class_info->execute();
    $class_info = $stmt_class_info->get_result()->fetch_assoc();
    $stmt_class_info->close();
}

if (!$class_info) {
    set_session_message('ไม่พบข้อมูลห้องเรียนที่ระบุ', 'danger');
    $redirect_page = $is_admin ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    header("Location: " . $redirect_page);
    exit();
}

// ดึงรายชื่อนักเรียนในคลาสปัจจุบัน
$students = [];
$stmt_students = $mysqli->prepare("SELECT s.student_id, s.student_code, s.prefix, s.first_name, s.last_name
                                   FROM students s
                                   JOIN student_classes sc ON s.student_id = sc.student_id
                                   JOIN academic_years ay ON sc.academic_year_id = ay.acad_year_id
                                   WHERE sc.class_id = ? AND ay.is_current_year = 1
                                   ORDER BY CAST(s.student_code AS UNSIGNED) ASC, s.student_code ASC");
if ($stmt_students) {
    $stmt_students->bind_param("i", $class_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    $students = $result_students->fetch_all(MYSQLI_ASSOC);
    $stmt_students->close();
}


// ดึงวิชาทั้งหมดที่สอนในคลาสนี้
$subjects_in_class = [];
$stmt_subjects = $mysqli->prepare("SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name, s.credits
                                   FROM subjects s
                                   JOIN class_schedules cs ON s.subject_id = cs.subject_id
                                   WHERE cs.class_id = ?
                                   ORDER BY s.subject_code ASC");
if ($stmt_subjects) {
    $stmt_subjects->bind_param("i", $class_id);
    $stmt_subjects->execute();
    $result_subjects = $stmt_subjects->get_result();
    $subjects_in_class = $result_subjects->fetch_all(MYSQLI_ASSOC);
    $stmt_subjects->close();
}

// ดึงคะแนนและเกรดของนักเรียนทุกคนในคลาสนี้
$grades_map = [];
$stmt_grades = $mysqli->prepare("SELECT student_id, subject_id, score, grade, grade_point FROM enrollments WHERE class_id = ?");
if ($stmt_grades) {
    $stmt_grades->bind_param("i", $class_id);
    $stmt_grades->execute();
    $grade_result = $stmt_grades->get_result();
    while($row = $grade_result->fetch_assoc()) {
        $grades_map[$row['student_id']][$row['subject_id']] = $row;
    }
    $stmt_grades->close();
}


$page_title = "รายงานคะแนนนักเรียน";
if ($is_admin) { require_once 'includes/admin_header.php'; require_once 'includes/admin_sidebar.php'; }
else { require_once 'includes/teacher_header.php'; require_once 'includes/teacher_sidebar.php'; }
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');

    body {
        font-family: 'Sarabun', sans-serif;
    }

    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    @media print {
        body * { visibility: hidden; }
        .printable-area, .printable-area * { visibility: visible; }
        .printable-area { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 100%; 
            margin: 0; 
            padding: 0; 
        }
        .content-wrapper, .main-container { 
            margin: 0 !important; 
            padding: 0 !important; 
            overflow: visible !important; 
            max-width: none !important;
            width: 100% !important;
        }
        .table-container { 
            overflow: visible !important; 
            box-shadow: none !important; 
            border: none !important; 
            width: 100% !important;
            max-width: none !important;
        }
        .card { 
            border: none !important; 
            box-shadow: none !important; 
        }
        
        /* ปรับขนาดฟอนต์สำหรับการพิมพ์ */
        .grade-table {
            font-size: 10pt !important;
        }
        .grade-table th, .grade-table td {
            padding: 0.2rem 0.3rem !important;
            line-height: 1.2 !important;
        }
        
        /* ปรับขนาดคอลัมน์สำหรับการพิมพ์ */
        .col-number { width: 30px !important; }
        .col-student-code { width: 80px !important; }
        .col-student-name { width: 150px !important; }
        .col-subject-data { width: 60px !important; }
        
        .d-print-none { display: none !important; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid !important; }
        .grade-table th, .grade-table td { 
            border: 1px solid #000 !important; 
        }
        
        /* ซ่อนคำอธิบายตอนพิมพ์ */
        .stats-section { display: none !important; }
    }
    
    /* ปรับแต่งเฉพาะการแสดงผลหน้าจอ */
    @media screen {
        .content-wrapper {
            max-width: calc(100vw - 280px);
            overflow-x: auto;
        }

        .main-container {
            max-width: 100%;
            padding-right: 15px;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }
    }
    
    .grade-table {
        font-size: 14px;
        font-family: 'Sarabun', sans-serif;
        border-collapse: collapse;
        width: 100%;
        margin: 0;
        table-layout: fixed;
        min-width: 800px; /* ลดความกว้างเนื่องจากมีคอลัมน์น้อยลง */
    }
    
    .table-bordered th, .table-bordered td {
        vertical-align: middle;
        padding: 0.5rem 0.4rem;
        border: 1px solid #dee2e6;
        text-align: center;
    }
    
    /* กำหนดความกว้างคอลัมน์ */
    .col-number { 
        width: 50px; 
        min-width: 50px;
        max-width: 50px;
    }
    
    .col-student-code { 
        width: 120px;
        min-width: 120px;
        max-width: 120px;
    }
    
    .col-student-name { 
        width: 200px;
        min-width: 200px;
        max-width: 200px;
        text-align: left !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-left: 0.6rem !important;
    }
    
    .col-subject-data { 
        width: 80px;
        min-width: 80px;
        max-width: 80px;
        font-weight: bold;
    }

    /* จัดการ header วิชา */
    .subject-header {
        padding: 0.4rem 0.3rem !important;
        font-size: 13px;
        line-height: 1.3;
    }

    .subject-name {
        display: block;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        line-height: 1.2;
        padding: 0;
        margin: 0;
    }
    
    /* ปรับแต่ง thead */
    thead th {
        background-color: #f8f9fa !important;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    /* จัดการข้อความที่ยาวในช่องชื่อ */
    .student-name-cell {
        position: relative;
        cursor: help;
    }
    
    .student-name-cell:hover::after {
        content: attr(data-full-name);
        position: absolute;
        left: 0;
        top: 100%;
        background: #333;
        color: white;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        margin-top: 2px;
    }
    
    /* ปรับแต่งการแสดงผลข้อมูลในตาราง */
    .grade-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    .grade-table tbody tr:hover {
        background-color: #e9ecef;
    }
    
   
    .btn-print:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* เพิ่มการ responsive */
    @media screen and (max-width: 1400px) {
        .content-wrapper {
            max-width: calc(100vw - 250px);
        }
    }
    
    @media screen and (max-width: 1200px) {
        .grade-table {
            font-size: 13px;
            min-width: 700px;
        }
        
        .col-student-name {
            width: 170px;
            min-width: 170px;
            max-width: 170px;
        }
        
        .col-subject-data {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }
        
        .content-wrapper {
            max-width: calc(100vw - 200px);
        }
    }
    
    @media screen and (max-width: 992px) {
        .grade-table {
            font-size: 12px;
            min-width: 600px;
        }
        
        .col-student-name {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
        }
        
        .col-subject-data {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
        }
        
        .content-wrapper {
            max-width: calc(100vw - 180px);
        }
    }

    @media screen and (max-width: 768px) {
        .content-wrapper {
            max-width: calc(100vw - 20px);
        }
        
        .main-container {
            padding-left: 10px;
            padding-right: 10px;
        }
    }
    
    /* สไตล์สำหรับข้อมูลคะแนน */
    .score-cell {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #495057;
    }
    
    .score-cell.has-score {
        background-color: #d4edda;
        color: #155724;
    }
    
    .score-cell.no-score {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
<div class="content-wrapper">
    <main class="p-4">
        <div class="main-container">
            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                <h1><i class="fas fa-clipboard-list me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <div>
                        <a href="<?php echo $is_admin ? 'report_class_grades.php' : 'teacher_homeroom_report.php'; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>กลับไปหน้ารายการ
                    </a>
                </div>
            </div>
            
            <div class="printable-area">
                <div class="text-center mb-4">
                    <h4>รายงานคะแนนนักเรียน ประจำภาคเรียน <?php echo htmlspecialchars($class_info['academic_year_name'] ?? ''); ?></h4>
                    <h5>ห้องเรียน <?php echo htmlspecialchars($class_info['class_name'] ?? ''); ?></h5>
                </div>
                <div class="card">
                    <div class="card-body p-2">
                        <div class="table-container">
                            <table class="table table-bordered grade-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th class="align-middle col-number">#</th>
                                        <th class="align-middle col-student-code">รหัสนักเรียน</th>
                                        <th class="align-middle col-student-name">ชื่อ-นามสกุล</th>
                                        <?php foreach($subjects_in_class as $subject): ?>
                                            <th class="subject-header col-subject-data">
                                                <div class="subject-name" title="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($students)): ?>
                                        <tr><td colspan="<?php echo count($subjects_in_class) + 3; ?>" class="text-center py-3">ไม่พบนักเรียนในห้องนี้</td></tr>
                                    <?php else: ?>
                                        <?php foreach($students as $index => $student): ?>
                                        <tr>
                                            <td class="text-center col-number"><?php echo $index + 1; ?></td>
                                            <td class="text-center col-student-code"><?php echo htmlspecialchars($student['student_code']); ?></td>
                                            <td class="text-start col-student-name student-name-cell" 
                                                data-full-name="<?php echo htmlspecialchars($student['prefix'].$student['first_name'].' '.$student['last_name']); ?>">
                                                <?php echo htmlspecialchars($student['prefix'].$student['first_name'].' '.$student['last_name']); ?>
                                            </td>
                                            <?php foreach($subjects_in_class as $subject): 
                                                $grade_info = $grades_map[$student['student_id']][$subject['subject_id']] ?? null;
                                                $display_score = '-';
                                                $cell_class = 'score-cell no-score';
                                                
                                                if ($grade_info && isset($grade_info['score']) && is_numeric($grade_info['score'])) {
                                                    $score = (float)$grade_info['score'];
                                                    $display_score = number_format($score, 0);
                                                    $cell_class = 'score-cell has-score';
                                                }
                                            ?>
                                                <td class="text-center col-subject-data <?php echo $cell_class; ?>">
                                                    <?php echo $display_score; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4 stats-section">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>สถิติข้อมูลคะแนน</h6>
                            </div>
                            <div class="card-body p-3">
                                <?php
                                $total_students = count($students);
                                $total_subjects = count($subjects_in_class);
                                $total_possible_scores = $total_students * $total_subjects;
                                $scores_entered = 0;
                                
                                foreach($students as $student) {
                                    foreach($subjects_in_class as $subject) {
                                        $grade_info = $grades_map[$student['student_id']][$subject['subject_id']] ?? null;
                                        if ($grade_info && isset($grade_info['score']) && is_numeric($grade_info['score'])) {
                                            $scores_entered++;
                                        }
                                    }
                                }
                                
                                $completion_percentage = ($total_possible_scores > 0) ? ($scores_entered / $total_possible_scores) * 100 : 0;
                                ?>
                                <div class="row">
                                    <div class="col-md-3">
                                        <small><strong>จำนวนนักเรียน:</strong> <?php echo $total_students; ?> คน</small>
                                    </div>
                                    <div class="col-md-3">
                                        <small><strong>จำนวนวิชา:</strong> <?php echo $total_subjects; ?> วิชา</small>
                                    </div>
                                    <div class="col-md-3">
                                        <small><strong>คะแนนที่บันทึกแล้ว:</strong> <?php echo $scores_entered; ?>/<?php echo $total_possible_scores; ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <small><strong>ความสมบูรณ์:</strong> <?php echo number_format($completion_percentage, 1); ?>%</small>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-success"><i class="fas fa-check-circle me-1"></i><strong>สีเขียว:</strong> มีคะแนน</small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-danger"><i class="fas fa-times-circle me-1"></i><strong>สีแดง:</strong> ยังไม่มีคะแนน</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
if ($is_admin) { require_once 'includes/admin_footer.php'; }
else { require_once 'includes/teacher_footer.php'; }
?>
