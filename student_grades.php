<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php'; 

if (!is_student_loggedin()) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// ... โค้ด PHP ส่วนดึงข้อมูลเหมือนเดิมทั้งหมด ไม่ต้องแก้ไข ...
$ay_filter_sql = "SELECT DISTINCT ay.acad_year_id, ay.year FROM academic_years ay JOIN classes c ON ay.acad_year_id = c.academic_year_id JOIN enrollments e ON c.class_id = e.class_id WHERE e.student_id = ? ORDER BY ay.year DESC";
$stmt_ay = $mysqli->prepare($ay_filter_sql);
$stmt_ay->bind_param("i", $student_id);
$stmt_ay->execute();
$academic_years = $stmt_ay->get_result();
$stmt_ay->close();

$filter_ay_id = $_GET['academic_year_id'] ?? null;
if ($filter_ay_id === null && $academic_years->num_rows > 0) {
    $first_ay = $academic_years->fetch_assoc();
    $filter_ay_id = $first_ay['acad_year_id'];
    $academic_years->data_seek(0);
}

$enrollments_data = [];
if (!empty($filter_ay_id)) {
    $sql_enrollments = "SELECT s.subject_code, s.subject_name, s.credits, e.score, e.grade
                        FROM enrollments e
                        JOIN subjects s ON e.subject_id = s.subject_id
                        JOIN classes c ON e.class_id = c.class_id
                        WHERE e.student_id = ? AND c.academic_year_id = ?
                        ORDER BY s.subject_code ASC";
    $stmt_enroll = $mysqli->prepare($sql_enrollments);
    $stmt_enroll->bind_param("ii", $student_id, $filter_ay_id);
    $stmt_enroll->execute();
    $result_enroll = $stmt_enroll->get_result();
    while ($row = $result_enroll->fetch_assoc()) {
        $enrollments_data[] = $row;
    }
    $stmt_enroll->close();
}

$page_title = "รายละเอียดผลการเรียน";
require_once 'includes/student_header.php';
require_once 'includes/student_sidebar.php';
?>
<style>
    /* CSS Animations and Base Styles */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animation-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
    .content-wrapper { background-color: #f4f7f6; }

    /* Custom Styles for Grades Page */
    .grades-card {
        border: none;
        box-shadow: 0 4px 25px rgba(0,0,0,0.05);
    }
    .filter-form {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    /* Custom Table Styles */
    .grades-table {
        border-collapse: separate;
        border-spacing: 0 8px; /* เพิ่มช่องว่างระหว่างแถว */
    }
    .grades-table thead th {
        background-color: #e9ecef;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
    }
    .grades-table tbody tr {
        background-color: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border-radius: .5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .grades-table tbody tr:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .grades-table tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        border: none;
    }
    /* ทำให้ขอบของ cell สวยงามเมื่อใช้ border-spacing */
    .grades-table tbody td:first-child { border-radius: .5rem 0 0 .5rem; }
    .grades-table tbody td:last-child { border-radius: 0 .5rem .5rem 0; }

    /* Grade Badge Styles */
    .grade-badge {
        padding: 0.5em 1em;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 50px;
    }

    /* No Data Placeholder */
    .no-data-placeholder {
        text-align: center;
        padding: 4rem 1rem;
    }
    .no-data-placeholder .icon {
        font-size: 4rem;
        color: #e0e0e0;
        margin-bottom: 1rem;
    }
</style>

<div class="content-wrapper">
    <main class="p-md-4 p-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0 fw-bold"><i class="fas fa-book-open me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_dashboard.php" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex"><i class="fas fa-arrow-left me-2"></i>กลับแดชบอร์ด</a>
            </div>

            <div class="card grades-card animation-fadeInUp">
                <div class="card-header filter-form p-3">
                    <form method="get" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="academic_year_id" class="form-label fw-bold">เลือกปีการศึกษา</label>
                            <select name="academic_year_id" id="academic_year_id" class="form-select form-select-lg">
                                <?php if ($academic_years->num_rows === 0): ?>
                                    <option value="" disabled selected>ไม่พบปีการศึกษา</option>
                                <?php else: ?>
                                    <?php foreach($academic_years as $ay): ?>
                                        <option value="<?php echo $ay['acad_year_id']; ?>" <?php echo ($filter_ay_id == $ay['acad_year_id']) ? 'selected' : ''; ?>>
                                            ภาคเรียน <?php echo htmlspecialchars($ay['year']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100 btn-lg">แสดงผล</button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                           <table class="table grades-table">
                                <thead>
                                    <tr>
                                        <th>รหัสวิชา</th>
                                        <th>ชื่อวิชา</th>
                                        <th class="text-center">หน่วยกิต</th>
                                        <th class="text-center">คะแนนรวม</th>
                                        <th class="text-center">เกรด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($enrollments_data)): ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="no-data-placeholder">
                                                    <div class="icon"><i class="fas fa-cloud-moon"></i></div>
                                                    <h5 class="text-muted">ไม่พบข้อมูลผลการเรียน</h5>
                                                    <p>โปรดเลือกปีการศึกษาอื่น หรือรอการอัปเดตจากอาจารย์</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($enrollments_data as $enroll): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($enroll['subject_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($enroll['subject_name']); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars(number_format($enroll['credits'], 1)); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($enroll['score'] ?? '-'); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($enroll['grade'] ?? '-'); ?></td>
                                                   
                                                    </span>
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

<?php require_once 'includes/student_footer.php'; ?>