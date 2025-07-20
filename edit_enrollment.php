<?php
session_start();
require_once 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล (ควรใช้ PDO เหมือนที่แนะนำไป)
require_once 'includes/admin_functions.php'; // ไฟล์ฟังก์ชันเกี่ยวกับการตรวจสอบสิทธิ์และอื่นๆ

// *** NOTE: It's highly recommended to use PDO for database connections for consistency and security.
// The provided code uses $mysqli, while my previous examples used $pdo.
// Ensure your 'config.php' is setting up $pdo for consistency or adapt the queries below to $mysqli.
// For this example, I'll assume $pdo is available after config.php, or you've adapted config.php to mysqli.
// For mysqli, replace $pdo->prepare, $stmt->execute, $stmt->fetchAll, etc. with mysqli equivalents.
// For example:
// $mysqli = new mysqli($host, $user, $pass, $db);
// if ($mysqli->connect_error) { die("Connection failed: " . $mysqli->connect_error); }
// $mysqli->set_charset("utf8mb4");

// If calculate_grade_from_score is not in admin_functions.php, define it here
if (!function_exists('calculate_grade_from_score')) {
    function calculate_grade_from_score($score, $credits = 0) {
        $grade = '';
        $grade_point = null;

        if ((float)$credits == 0 || $credits === null) {
            if ($score >= 50) {
                $grade = 'ผ'; // ผ่าน
            } else {
                $grade = 'มผ'; // ไม่ผ่าน
            }
            $grade_point = null;
        } else {
            if ($score >= 80) { $grade = 'A'; $grade_point = 4.00; }
            else if ($score >= 75) { $grade = 'B+'; $grade_point = 3.50; }
            else if ($score >= 70) { $grade = 'B'; $grade_point = 3.00; }
            else if ($score >= 65) { $grade = 'C+'; $grade_point = 2.50; }
            else if ($score >= 60) { $grade = 'C'; $grade_point = 2.00; }
            else if ($score >= 55) { $grade = 'D+'; $grade_point = 1.50; }
            else if ($score >= 50) { $grade = 'D'; $grade_point = 1.00; }
            else { $grade = 'ร'; $grade_point = 0.00; }
        }
        return ['grade' => $grade, 'grade_point' => $grade_point];
    }
}

// ตรวจสอบสิทธิ์การเข้าถึง (Admin เท่านั้นที่ควรแก้ไขข้อมูลย้อนหลังได้)
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$is_admin = true; // หน้านี้สำหรับ Admin เท่านั้น
$teacher_id = $_SESSION['teacher_id'] ?? null; // อาจไม่จำเป็นต้องใช้ในหน้านี้

// 1. รับค่าจากฟอร์มค้นหา (ถ้ามี)
$search_student_name = $_GET['student_name'] ?? '';
$search_academic_year_id = $_GET['academic_year_id'] ?? '';
$search_class_id = $_GET['class_id'] ?? '';
$search_subject_id = $_GET['subject_id'] ?? '';

// 2. เตรียม SQL Query พื้นฐาน
$sql = "
    SELECT
        e.enrollment_id,
        s.student_id,
        s.student_code,
        s.prefix,
        s.first_name,
        s.last_name,
        sub.subject_code,
        sub.subject_name,
        sub.credits,
        ay.year AS academic_year_name,
        c.class_name,
        e.score,
        e.grade,
        e.status
    FROM
        enrollments e
    JOIN
        students s ON e.student_id = s.student_id
    JOIN
        subjects sub ON e.subject_id = sub.subject_id
    JOIN
        classes c ON e.class_id = c.class_id
    JOIN
        academic_years ay ON e.academic_year_id = ay.acad_year_id
    WHERE 1=1 -- เริ่มต้นด้วยเงื่อนไขที่เป็นจริงเสมอ เพื่อให้ง่ายต่อการเพิ่มเงื่อนไขอื่นๆ
";

$params = [];
$param_types = ""; // For mysqli bind_param

// 3. เพิ่มเงื่อนไขการค้นหาตาม input ของผู้ใช้
if (!empty($search_student_name)) {
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ?)";
    $params[] = '%' . $search_student_name . '%';
    $params[] = '%' . $search_student_name . '%';
    $param_types .= "ss"; // For mysqli
}
if (!empty($search_academic_year_id)) {
    $sql .= " AND ay.acad_year_id = ?";
    $params[] = $search_academic_year_id;
    $param_types .= "i"; // For mysqli
}
if (!empty($search_class_id)) {
    $sql .= " AND c.class_id = ?";
    $params[] = $search_class_id;
    $param_types .= "i"; // For mysqli
}
if (!empty($search_subject_id)) {
    $sql .= " AND sub.subject_id = ?";
    $params[] = $search_subject_id;
    $param_types .= "i"; // For mysqli
}

// 4. จัดเรียงผลลัพธ์
$sql .= " ORDER BY ay.year DESC, c.class_name ASC, s.first_name ASC, sub.subject_name ASC";

// 5. ดึงข้อมูลการลงทะเบียน
$enrollments = [];
// Using mysqli prepared statement
$stmt = $mysqli->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for enrollments: " . $mysqli->error);
}

// 6. ดึงข้อมูลสำหรับ Dropdown Filters (ดึงมาให้ครบ เพื่อใช้แสดงใน Form)
$academic_years = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$classes = $mysqli->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetch_all(MYSQLI_ASSOC);
$subjects = $mysqli->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);

$page_title = "ค้นหาและแก้ไขผลการเรียนนักเรียน";
require_once 'includes/admin_header.php'; // Assuming admin_header.php exists
?>
<style>
    /* ใช้ CSS ที่มีอยู่ของคุณได้เลย */
    /* เพิ่มหรือปรับแต่ง CSS ที่จำเป็นสำหรับตารางแก้ไข */
    .edit-link {
        display: inline-block;
        padding: 5px 10px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        font-size: 0.85em;
    }
    .edit-link:hover {
        background-color: #0056b3;
    }
    .filter-form label {
        white-space: nowrap; /* Prevent labels from wrapping */
    }
</style>
<?php require_once 'includes/admin_sidebar.php'; // Assuming admin_sidebar.php exists ?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="main-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            </div>

            <div class="filter-form card p-3 mb-4">
                <h5 class="mb-3">ตัวกรองข้อมูล</h5>
                <form method="GET" action="edit_enrollment.php" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="student_name" class="form-label">ชื่อนักเรียน:</label>
                        <input type="text" id="student_name" name="student_name" class="form-control" value="<?= htmlspecialchars($search_student_name) ?>" placeholder="ชื่อ/นามสกุล">
                    </div>

                    <div class="col-md-3">
                        <label for="academic_year_id" class="form-label">ปีการศึกษา:</label>
                        <select id="academic_year_id" name="academic_year_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?= $year['acad_year_id'] ?>" <?= ($search_academic_year_id == $year['acad_year_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="class_id" class="form-label">ชั้นเรียน:</label>
                        <select id="class_id" name="class_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>" <?= ($search_class_id == $class['class_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">วิชา:</label>
                        <select id="subject_id" name="subject_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>" <?= ($search_subject_id == $subject['subject_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>ค้นหาข้อมูล</button>
                        <a href="edit_enrollment.php" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>ล้างการค้นหา</a>
                    </div>
                </form>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">รายการผลการเรียนที่ค้นพบ</h5>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover grade-table">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>#</th>
                                    <th>รหัสนักเรียน</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ปีการศึกษา</th>
                                    <th>ชั้นเรียน</th>
                                    <th>วิชา</th>
                                    <th>คะแนน</th>
                                    <th>เกรด</th>
                                    <th>สถานะ</th>
                                    <th>ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrollments)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-3">ไม่พบข้อมูลการลงทะเบียนที่ตรงกับเงื่อนไข</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $counter = 1; foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= $counter++ ?></td>
                                            <td><?= htmlspecialchars($enrollment['student_code']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['prefix'] . $enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['academic_year_name']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['class_name']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['subject_name']) ?> (<?= htmlspecialchars($enrollment['subject_code']) ?>)</td>
                                            <td><?= htmlspecialchars($enrollment['score']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['grade']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['status']) ?></td>
                                            <td>
                                                <a href="edit_enrollment_detail.php?id=<?= $enrollment['enrollment_id'] ?>" class="edit-link">แก้ไข</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
require_once 'includes/admin_footer.php'; // Assuming admin_footer.php exists

// ปิดการเชื่อมต่อฐานข้อมูล
$mysqli->close();
?>