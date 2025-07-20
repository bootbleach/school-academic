<?php
session_start();
require_once 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/admin_functions.php'; // ไฟล์ฟังก์ชันเกี่ยวกับการตรวจสอบสิทธิ์และอื่นๆ

// ฟังก์ชันสำหรับคำนวณเกรด (ต้องมั่นใจว่ามีใน admin_functions.php หรือกำหนดตรงนี้)
// หากไม่มีฟังก์ชันนี้ใน admin_functions.php ต้องนำโค้ดมาใส่ตรงนี้
if (!function_exists('calculate_grade_from_score')) {
    function calculate_grade_from_score($score, $credits = 0) {
        $grade = '';
        $grade_point = null;

        // สำหรับวิชาที่ไม่มีหน่วยกิต (credits = 0 หรือ null)
        if ((float)$credits == 0 || $credits === null) {
            if ($score >= 50) {
                $grade = 'ผ'; // ผ่าน
            } else {
                $grade = 'มผ'; // ไม่ผ่าน
            }
            $grade_point = null; // ไม่มีเกรดเฉลี่ยสำหรับวิชาที่ไม่มีหน่วยกิต
        } else {
            // สำหรับวิชาที่มีหน่วยกิต
            if ($score >= 80) { $grade = 'A'; $grade_point = 4.00; }
            else if ($score >= 75) { $grade = 'B+'; $grade_point = 3.50; }
            else if ($score >= 70) { $grade = 'B'; $grade_point = 3.00; }
            else if ($score >= 65) { $grade = 'C+'; $grade_point = 2.50; }
            else if ($score >= 60) { $grade = 'C'; $grade_point = 2.00; }
            else if ($score >= 55) { $grade = 'D+'; $grade_point = 1.50; }
            else if ($score >= 50) { $grade = 'D'; $grade_point = 1.00; }
            else { $grade = 'ร'; $grade_point = 0.00; } // ร. คือ ติด "ร" ไม่ผ่าน
        }
        return ['grade' => $grade, 'grade_point' => $grade_point];
    }
}

// 1. ตรวจสอบสิทธิ์การเข้าถึง (Admin หรือ Teacher)
if (!is_admin_loggedin() && !is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}

// 2. ตรวจสอบว่ามี class_id ส่งมาหรือไม่ และเป็นตัวเลขที่ถูกต้อง
if (!isset($_GET['class_id']) || !filter_var($_GET['class_id'], FILTER_VALIDATE_INT)) {
    $redirect_page = is_admin_loggedin() ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    set_session_message('โปรดระบุรหัสห้องเรียนที่ถูกต้อง.', 'danger'); // เพิ่มข้อความแจ้งเตือน
    header("Location: $redirect_page");
    exit();
}

$class_id = (int)$_GET['class_id'];
$is_admin = is_admin_loggedin();
$teacher_id = $_SESSION['teacher_id'] ?? null;

// 3. ตรวจสอบสิทธิ์การเข้าถึงหน้ารายงาน (ใช้ Prepared Statement)
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
    } else {
        error_log("Failed to prepare statement for teacher permission check: " . $mysqli->error);
    }
}

if (!$has_permission) {
    set_session_message('คุณไม่มีสิทธิ์ดูรายงานของห้องเรียนนี้', 'danger');
    $redirect_page = $is_admin ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    header("Location: $redirect_page");
    exit();
}

// 4. ดึงข้อมูลสำหรับสร้างรายงาน (ทั้งหมดใช้ Prepared Statements)
$class_info = null;
$stmt_class_info = $mysqli->prepare("SELECT c.*, ay.year as academic_year_name FROM classes c JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id WHERE c.class_id = ?");
if ($stmt_class_info) {
    $stmt_class_info->bind_param("i", $class_id);
    $stmt_class_info->execute();
    $result_class_info = $stmt_class_info->get_result();
    if ($row = $result_class_info->fetch_assoc()) {
        $class_info = $row;
    }
    $stmt_class_info->close();
} else {
    error_log("Failed to prepare statement for class info: " . $mysqli->error);
}

if (!$class_info) {
    set_session_message('ไม่พบข้อมูลห้องเรียนที่ระบุ.', 'danger');
    $redirect_page = is_admin_loggedin() ? 'report_class_grades.php' : 'teacher_homeroom_report.php';
    header("Location: $redirect_page");
    exit();
}

// **ปรับปรุงตรงนี้: ดึงนักเรียนโดย JOIN กับ student_classes**
$students = [];
$stmt_students = $mysqli->prepare("SELECT s.student_id, s.student_code, s.prefix, s.first_name, s.last_name FROM students s JOIN student_classes sc ON s.student_id = sc.student_id WHERE sc.class_id = ? ORDER BY CAST(s.student_code AS UNSIGNED) ASC, s.student_code ASC");
if ($stmt_students) {
    $stmt_students->bind_param("i", $class_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt_students->close();
} else {
    error_log("Failed to prepare statement for students: " . $mysqli->error);
}

// **ปรับปรุงตรงนี้: ดึงรายวิชาจาก enrollments (วิชาที่มีการลงทะเบียนและมีคะแนนจริงในห้องนี้)**
$subjects_in_class = [];
$stmt_subjects = $mysqli->prepare("SELECT DISTINCT sub.subject_id, sub.subject_code, sub.subject_name, sub.credits FROM subjects sub JOIN enrollments e ON sub.subject_id = e.subject_id WHERE e.class_id = ? ORDER BY sub.subject_code ASC");
if ($stmt_subjects) {
    $stmt_subjects->bind_param("i", $class_id);
    $stmt_subjects->execute();
    $result_subjects = $stmt_subjects->get_result();
    while ($row = $result_subjects->fetch_assoc()) {
        $subjects_in_class[] = $row;
    }
    $stmt_subjects->close();
} else {
    error_log("Failed to prepare statement for subjects in class: " . $mysqli->error);
}

// ดึงคะแนนและเกรดทั้งหมดสำหรับห้องนี้ จัดเก็บใน $grades_map[student_id][subject_id]
$grades_map = [];
$stmt_grades = $mysqli->prepare("SELECT student_id, subject_id, score, grade, grade_point FROM enrollments WHERE class_id = ?");
if ($stmt_grades) {
    $stmt_grades->bind_param("i", $class_id);
    $stmt_grades->execute();
    $result_grades = $stmt_grades->get_result();
    while($row = $result_grades->fetch_assoc()) {
        $grades_map[$row['student_id']][$row['subject_id']] = $row;
    }
    $stmt_grades->close();
} else {
    error_log("Failed to prepare statement for grades map: " . $mysqli->error);
}

$page_title = "รายงานผลการเรียน";
if ($is_admin) { require_once 'includes/admin_header.php'; }
else { require_once 'includes/teacher_header.php'; }
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
            font-size: 9pt !important;
        }
        .grade-table th, .grade-table td {
            padding: 0.15rem 0.2rem !important;
            line-height: 1.2 !important;
        }
        
        /* ปรับขนาดคอลัมน์สำหรับการพิมพ์ */
        .col-number { width: 30px !important; }
        .col-student-code { width: 70px !important; }
        .col-student-name { width: 140px !important; }
        .col-subject-data { width: 35px !important; }
        .col-summary { width: 50px !important; }
        
        .d-print-none { display: none !important; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid !important; }
        .grade-table th, .grade-table td { 
            border: 1px solid #000 !important; 
        }
        .table-primary, .table-success, .table-warning { 
            background-color: transparent !important; 
            color: #000 !important; 
        }
        
        /* ซ่อนคำอธิบายและสถิติตอนพิมพ์เพื่อประหยัดพื้นที่ */
        .stats-section { display: none !important; }
    }
    
    /* ปรับแต่งเฉพาะการแสดงผลหน้าจอ - ไม่ใช่ print */
    @media screen {
        .content-wrapper {
            max-width: calc(100vw - 280px); /* ลบ sidebar width + padding */
            overflow-x: auto; /* เพิ่ม horizontal scroll แทน */
        }

        .main-container {
            max-width: 100%;
            padding-right: 15px; /* เพิ่ม padding ด้านขวา */
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-bottom: 20px;
            /* ลบ position: relative หรือ absolute ที่อาจทำให้เบียด */
        }
    }
    
    .grade-table {
        font-size: 13px;
        font-family: 'Sarabun', sans-serif;
        border-collapse: collapse;
        width: 100%;
        margin: 0;
        table-layout: fixed; /* ใช้ fixed layout เพื่อควบคุมความกว้างคอลัมน์ */
        min-width: 1200px; /* กำหนดความกว้างขั้นต่ำเพื่อไม่ให้แคบเกินไป */
    }
    
    .table-bordered th, .table-bordered td {
        vertical-align: middle;
        padding: 0.4rem 0.3rem;
        border: 1px solid #dee2e6;
        text-align: center;
    }
    
    /* กำหนดความกว้างคอลัมน์แบบแน่นอน */
    .col-number { 
        width: 40px; 
        min-width: 40px;
        max-width: 40px;
    }
    
    .col-student-code { 
        width: 100px;
        min-width: 100px;
        max-width: 100px;
    }
    
    .col-student-name { 
        width: 180px;
        min-width: 180px;
        max-width: 180px;
        text-align: left !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-left: 0.5rem !important;
    }
    
    .col-subject-data { 
        width: 50px;
        min-width: 50px;
        max-width: 50px;
    }
    
    .col-summary { 
        width: 70px;
        min-width: 70px;
        max-width: 70px;
        font-weight: bold;
    }

    /* จัดการ header วิชา */
    .subject-header {
        padding: 0.3rem 0.2rem !important;
        font-size: 12px;
        line-height: 1.2;
    }

    .subject-name {
        display: block;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 11px;
        line-height: 1.1;
        padding: 0;
        margin: 0;
    }
    
    /* สีพื้นหลังสำหรับคอลัมน์สรุป */
    .table-primary { 
        background-color: #cfe2ff !important; 
        color: #0c4085 !important;
    }
    .table-success { 
        background-color: #d1e7dd !important; 
        color: #0f5132 !important;
    }
    .table-warning { 
        background-color: #fff3cd !important; 
        color: #664d03 !important;
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
    
    /* จัดการข้อความที่ยาวในช่องชื่อ - เพิ่ม tooltip */
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
    
    /* ปรับแต่งส่วนสถิติ */
    .stats-section .card {
        height: 100%;
    }
    
    .stats-section .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    /* ปรับปุ่มพิมพ์ */
    .btn-print {
        background: linear-gradient(45deg, #28a745, #20c997);
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .btn-print:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* เพิ่มการ responsive */
    @media screen and (max-width: 1400px) {
        .content-wrapper {
            max-width: calc(100vw - 250px); /* ปรับให้เหมาะกับหน้าจอเล็กลง */
        }
    }
    
    @media screen and (max-width: 1200px) {
        .grade-table {
            font-size: 12px;
            min-width: 1000px; /* ลดความกว้างขั้นต่ำ */
        }
        
        .col-student-name {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
        }
        
        .col-subject-data {
            width: 45px;
            min-width: 45px;
            max-width: 45px;
        }
        
        .col-summary {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
        }
        
        .content-wrapper {
            max-width: calc(100vw - 200px); /* ปรับให้เหมาะกับ sidebar ที่เล็กลง */
        }
    }
    
    @media screen and (max-width: 992px) {
        .grade-table {
            font-size: 11px;
            min-width: 900px;
        }
        
        .col-student-name {
            width: 130px;
            min-width: 130px;
            max-width: 130px;
        }
        
        .col-subject-data {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }
        
        .col-summary {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
        }
        
        .content-wrapper {
            max-width: calc(100vw - 180px);
        }
    }

    /* สำหรับหน้าจอที่เล็กมาก */
    @media screen and (max-width: 768px) {
        .content-wrapper {
            max-width: calc(100vw - 20px); /* เผื่อ padding */
        }
        
        .main-container {
            padding-left: 10px;
            padding-right: 10px;
        }
    }
</style>
<?php
// ส่วนนี้คือการเรียกใช้ sidebar และ header/footer ซึ่งอยู่ใน includes
if ($is_admin) { require_once 'includes/admin_sidebar.php'; }
else { require_once 'includes/teacher_sidebar.php'; }
?>
<div class="content-wrapper">
    <main class="p-4">
        <div class="main-container">
            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                <h1><i class="fas fa-print me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <div>
                    <button onclick="window.print();" class="btn btn-success btn-print">
                        <i class="fas fa-print me-1"></i>พิมพ์รายงาน
                    </button>
                    <a href="<?php echo $is_admin ? 'report_grades.php' : 'report_grades.php'; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>กลับไปหน้ารายการ
                    </a>
                </div>
            </div>
            
            <div class="printable-area">
                <div class="text-center mb-4">
                    <h4>ผลการเรียนประจำภาคเรียน <?php echo htmlspecialchars($class_info['academic_year_name'] ?? 'ไม่ระบุปีการศึกษา'); ?></h4>
                    <h5>ห้องเรียน <?php echo htmlspecialchars($class_info['class_name'] ?? 'ไม่ระบุห้องเรียน'); ?></h5>
                </div>
                <div class="card">
                    <div class="card-body p-2">
                        <div class="table-container">
                            <table class="table table-bordered grade-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th class="align-middle col-number" rowspan="2">#</th>
                                        <th class="align-middle col-student-code" rowspan="2">รหัสนักเรียน</th>
                                        <th class="align-middle col-student-name" rowspan="2">ชื่อ-นามสกุล</th>
                                        <?php 
                                        // สร้าง Header สำหรับแต่ละวิชาแบบ Dynamic
                                        foreach($subjects_in_class as $subject): ?>
                                            <th colspan="2" class="subject-header">
                                                <div class="subject-name" title="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                                    <?php echo htmlspecialchars($subject['subject_code']); ?><br>
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                        <th class="align-middle col-summary" rowspan="2">คะแนนรวม</th>
                                        <th class="align-middle col-summary" rowspan="2">ร้อยละ (%)</th>
                                        <th class="align-middle col-summary" rowspan="2">เกรดเฉลี่ย</th>
                                    </tr>
                                    <tr>
                                        <?php 
                                        // สร้าง Header "คะแนน" และ "เกรด" ใต้วิชา
                                        foreach($subjects_in_class as $subject): ?>
                                            <th class="subject-header col-subject-data">คะแนน</th>
                                            <th class="subject-header col-subject-data">เกรด</th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($students)): ?>
                                        <tr><td colspan="<?php echo (count($subjects_in_class) * 2) + 5; ?>" class="text-center py-3">ไม่พบนักเรียนในห้องนี้</td></tr>
                                    <?php else: ?>
                                        <?php foreach($students as $index => $student): ?>
                                        <tr>
                                            <td class="text-center col-number"><?php echo $index + 1; ?></td>
                                            <td class="text-center col-student-code"><?php echo htmlspecialchars($student['student_code']); ?></td>
                                            <td class="text-start col-student-name student-name-cell" 
                                                data-full-name="<?php echo htmlspecialchars($student['prefix'].$student['first_name'].' '.$student['last_name']); ?>">
                                                <?php echo htmlspecialchars($student['prefix'].$student['first_name'].' '.$student['last_name']); ?>
                                            </td>
                                            <?php
                                                $student_total_credits = 0; 
                                                $student_total_points = 0; 
                                                $student_total_score = 0; 
                                                $subjects_with_scores_count = 0; // นับเฉพาะวิชาที่มีคะแนน

                                                foreach($subjects_in_class as $subject): 
                                                    $grade_info = $grades_map[$student['student_id']][$subject['subject_id']] ?? null;
                                                    $display_score = '-'; 
                                                    $display_grade = '-';
                                                    
                                                    if ($grade_info && isset($grade_info['score']) && is_numeric($grade_info['score'])) {
                                                        $score = (float)$grade_info['score'];
                                                        $display_score = number_format($score, 0);
                                                        
                                                        // เพิ่มคะแนนดิบที่ได้ (ไม่รวมวิชาที่ไม่ได้ลงทะเบียน/ไม่มีคะแนน)
                                                        $student_total_score += $score;
                                                        $subjects_with_scores_count++; // นับวิชาที่มีคะแนน

                                                        $calculated_grade = calculate_grade_from_score($score, $subject['credits']);
                                                        if ($calculated_grade['grade_point'] !== null) {
                                                            // ถ้ามี grade_point แสดงเป็นตัวเลขเกรด, ถ้าเป็น 0.00 ให้แสดง 'ร'
                                                            $display_grade = ($calculated_grade['grade_point'] == 0.00) ? 'ร' : number_format($calculated_grade['grade_point'], 2);
                                                        } else {
                                                            // ถ้าไม่มี grade_point (เช่น วิชาไม่มีหน่วยกิต) ให้แสดง 'ผ' หรือ 'มผ'
                                                            $display_grade = $calculated_grade['grade'];
                                                        }
                                                        
                                                        // สะสมหน่วยกิตและคะแนนสำหรับคำนวณ GPA (เฉพาะวิชาที่มีหน่วยกิตและ grade_point ไม่ใช่ null)
                                                        if ((float)$subject['credits'] > 0 && $calculated_grade['grade_point'] !== null) {
                                                            $student_total_credits += $subject['credits'];
                                                            $student_total_points += ($calculated_grade['grade_point'] * $subject['credits']);
                                                        }
                                                    }
                                                ?>
                                                    <td class="text-center col-subject-data"><?php echo $display_score; ?></td>
                                                    <td class="text-center col-subject-data fw-bold"><?php echo $display_grade; ?></td>
                                                <?php endforeach; 
                                                // คำนวณร้อยละและเกรดเฉลี่ยรวมของนักเรียนคนนี้
                                                $student_percentage = ($subjects_with_scores_count > 0) ? ($student_total_score / $subjects_with_scores_count) : 0;
                                                $student_gpa = ($student_total_credits > 0) ? ($student_total_points / $student_total_credits) : 0;
                                                ?>
                                                <td class="text-center col-summary table-primary fw-bold"><?php echo number_format($student_total_score, 0); ?></td>
                                                <td class="text-center col-summary table-success fw-bold"><?php echo number_format($student_percentage, 2); ?></td>
                                                <td class="text-center col-summary table-warning fw-bold"><?php echo number_format($student_gpa, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4 stats-section">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>เกณฑ์การให้เกรด</h6></div>
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-6"><small>A = 80-100 (4.00)<br>B+ = 75-79 (3.50)<br>B = 70-74 (3.00)<br>C+ = 65-69 (2.50)</small></div>
                                    <div class="col-6"><small>C = 60-64 (2.00)<br>D+ = 55-59 (1.50)<br>D = 50-54 (1.00)<br>ร = 0-49 (0.00)</small></div>
                                </div>
                                <hr class="my-2">
                                <small class="text-muted"><strong>หมายเหตุ:</strong> วิชาที่ไม่มีหน่วยกิต จะแสดงผล "ผ" (ผ่าน) หรือ "มผ" (ไม่ผ่าน)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>สถิติห้องเรียน</h6></div>
                            <div class="card-body p-3">
                                <?php
                                $total_students = count($students); 
                                $total_subjects_in_class = count($subjects_in_class); // จำนวนวิชาที่มีในห้องนี้จริงๆ

                                $class_total_percentage_sum = 0; // ผลรวมร้อยละของนักเรียนทุกคน
                                $class_total_gpa_sum = 0;      // ผลรวม GPA ของนักเรียนทุกคน
                                $students_eligible_for_gpa = 0; // จำนวนนักเรียนที่มีวิชาให้คำนวณ GPA

                                foreach($students as $student) {
                                    $student_current_credits = 0; 
                                    $student_current_points = 0; 
                                    $student_current_score_sum = 0;
                                    $student_current_subjects_with_scores_count = 0;

                                    foreach($subjects_in_class as $subject) {
                                        $grade_info = $grades_map[$student['student_id']][$subject['subject_id']] ?? null;
                                        if ($grade_info && isset($grade_info['score']) && is_numeric($grade_info['score'])) {
                                            $score = (float)$grade_info['score'];
                                            $student_current_score_sum += $score;
                                            $student_current_subjects_with_scores_count++;
                                            
                                            $calculated_grade = calculate_grade_from_score($score, $subject['credits']);
                                            if ((float)$subject['credits'] > 0 && $calculated_grade['grade_point'] !== null) {
                                                $student_current_credits += $subject['credits'];
                                                $student_current_points += ($calculated_grade['grade_point'] * $subject['credits']);
                                            }
                                        }
                                    }
                                    
                                    // คำนวณร้อยละของนักเรียนแต่ละคนสำหรับสถิติห้อง
                                    if ($student_current_subjects_with_scores_count > 0) {
                                        $class_total_percentage_sum += ($student_current_score_sum / $student_current_subjects_with_scores_count);
                                    }

                                    // คำนวณ GPA ของนักเรียนแต่ละคนสำหรับสถิติห้อง
                                    if ($student_current_credits > 0) {
                                        $class_total_gpa_sum += ($student_current_points / $student_current_credits);
                                        $students_eligible_for_gpa++;
                                    }
                                }

                                $class_avg_percentage = ($total_students > 0) ? ($class_total_percentage_sum / $total_students) : 0;
                                $class_avg_gpa = ($students_eligible_for_gpa > 0) ? ($class_total_gpa_sum / $students_eligible_for_gpa) : 0;
                                ?>
                                <small>
                                    <strong>จำนวนนักเรียน:</strong> <?php echo $total_students; ?> คน<br>
                                    <strong>จำนวนวิชาในห้องนี้:</strong> <?php echo $total_subjects_in_class; ?> วิชา<br>
                                    <strong>ร้อยละเฉลี่ยห้อง:</strong> <?php echo number_format($class_avg_percentage, 2); ?>%<br>
                                    <strong>เกรดเฉลี่ยห้อง:</strong> <?php echo number_format($class_avg_gpa, 2); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
// ส่วนนี้คือการเรียกใช้ footer
if ($is_admin) { require_once 'includes/admin_footer.php'; }
else { require_once 'includes/teacher_footer.php'; }

// ปิดการเชื่อมต่อฐานข้อมูล
$mysqli->close();
?>