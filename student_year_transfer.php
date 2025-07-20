<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบสิทธิ์ Admin
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();
$errors = [];

// ดึงปีการศึกษาทั้งหมดและจัดเตรียมข้อมูลสำหรับ dropdown
$academic_years_raw = $mysqli->query("SELECT acad_year_id, year, is_current_year FROM academic_years ORDER BY year DESC");
$academic_years_arr = [];
$academic_years_map = []; // Map acad_year_id to full year string for display
while ($row = $academic_years_raw->fetch_assoc()) {
    $academic_years_arr[] = $row;
    $academic_years_map[$row['acad_year_id']] = $row['year'];
}

// ดึงชั้นเรียนทั้งหมดสำหรับ Dropdown การกำหนดห้องใหม่และ Filter ห้องเรียนต้นทาง
$all_classes_result = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");
$all_classes_arr = [];
while ($row = $all_classes_result->fetch_assoc()) {
    $all_classes_arr[] = $row;
}

// รับค่าจาก POST/GET สำหรับปีการศึกษาและ Filter
$source_acad_year_id = $_POST['source_acad_year_id'] ?? ($_GET['source_acad_year_id'] ?? null);
$target_acad_year_id = $_POST['target_acad_year_id'] ?? ($_GET['target_acad_year_id'] ?? null);
$source_class_filter_id = $_POST['source_class_filter_id'] ?? ($_GET['source_class_filter_id'] ?? ''); // New filter

$display_student_assignments = false; // Flag to control displaying student assignment table

// --- ส่วนที่ 1: ประมวลผลการเลือกปีการศึกษาและการกรอง (เมื่อกดปุ่ม "เลือกปีการศึกษา / กรองข้อมูล") ---
if (isset($_POST['select_years']) || (isset($_GET['source_acad_year_id']) && isset($_GET['target_acad_year_id']))) { // Check for initial load with GET params or POST submit
    if (empty($source_acad_year_id) || empty($target_acad_year_id)) {
        set_session_message("danger", "กรุณาเลือกปีการศึกษาต้นทางและปลายทาง");
        // Clear variables to prevent displaying table
        $source_acad_year_id = null;
        $target_acad_year_id = null;
    } elseif ($source_acad_year_id == $target_acad_year_id) {
        set_session_message("danger", "ปีการศึกษาต้นทางและปลายทางต้องแตกต่างกัน");
        // Clear variables
        $source_acad_year_id = null;
        $target_acad_year_id = null;
    } else {
        $display_student_assignments = true;
    }
}

// --- ส่วนที่ 2: ประมวลผลการบันทึกการย้ายชั้นปี (เมื่อกดปุ่ม "บันทึกการย้ายชั้นปี") ---
if (isset($_POST['transfer_students']) && !empty($source_acad_year_id) && !empty($target_acad_year_id)) {
    if ($source_acad_year_id == $target_acad_year_id) {
        set_session_message("danger", "ปีการศึกษาต้นทางและปลายทางต้องแตกต่างกัน");
        $display_student_assignments = true; // Stay on the assignment view
    } else {
        $mysqli->begin_transaction();
        try {
            $successful_transfers = 0;
            if (isset($_POST['student_new_class']) && is_array($_POST['student_new_class'])) {
                foreach ($_POST['student_new_class'] as $student_id_for_transfer => $new_class_id) {
                    $student_id_for_transfer = (int)$student_id_for_transfer;
                    // ถ้า $new_class_id เป็นสตริงว่าง (จาก option value="") ให้ถือว่าเป็น null เพื่อการลบหรือไม่ได้กำหนด
                    $new_class_id = empty($new_class_id) ? null : (int)$new_class_id;

                    $target_acad_class_instance_id = null;
                    if ($new_class_id !== null) {
                        // 1. ค้นหา acad_class_instance_id จาก class_id และ target_acad_year_id
                        $stmt_get_aci = $mysqli->prepare("SELECT acad_class_instance_id FROM academic_class_instances WHERE class_id = ? AND academic_year_id = ? LIMIT 1");
                        $stmt_get_aci->bind_param("ii", $new_class_id, $target_acad_year_id);
                        $stmt_get_aci->execute();
                        $stmt_get_aci->bind_result($target_acad_class_instance_id);
                        $stmt_get_aci->fetch();
                        $stmt_get_aci->close();

                        if ($target_acad_class_instance_id === null) {
                            // ถ้าไม่พบ academic_class_instance ให้สร้างใหม่
                            $stmt_insert_aci = $mysqli->prepare("INSERT INTO academic_class_instances (class_id, academic_year_id) VALUES (?, ?)");
                            $stmt_insert_aci->bind_param("ii", $new_class_id, $target_acad_year_id);
                            $stmt_insert_aci->execute();
                            $target_acad_class_instance_id = $mysqli->insert_id;
                            $stmt_insert_aci->close();
                        }
                    }

                    // 2. ตรวจสอบว่ามี record สำหรับนักเรียนคนนี้ในปีปลายทางอยู่แล้วหรือไม่
                    $stmt_check_exist = $mysqli->prepare("
                        SELECT sc.acad_class_instance_id
                        FROM student_classes sc
                        JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                        WHERE sc.student_id = ? AND aci.academic_year_id = ?
                    ");
                    $stmt_check_exist->bind_param("ii", $student_id_for_transfer, $target_acad_year_id);
                    $stmt_check_exist->execute();
                    $current_assignment = $stmt_check_exist->get_result()->fetch_assoc();
                    $stmt_check_exist->close();


                    if ($target_acad_class_instance_id !== null) { // ถ้ามีการเลือกห้องเรียนใหม่ (ไม่ว่างเปล่า)
                        if ($current_assignment) {
                            // มีอยู่แล้ว: UPDATE หาก acad_class_instance_id เปลี่ยนไป
                            if ($current_assignment['acad_class_instance_id'] !== $target_acad_class_instance_id) {
                                $stmt_update = $mysqli->prepare("
                                    UPDATE student_classes sc
                                    JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                                    SET sc.acad_class_instance_id = ?
                                    WHERE sc.student_id = ? AND aci.academic_year_id = ?
                                ");
                                $stmt_update->bind_param("iii", $target_acad_class_instance_id, $student_id_for_transfer, $target_acad_year_id);
                                $stmt_update->execute();
                                $stmt_update->close();
                            }
                        } else {
                            // ยังไม่มี: INSERT
                            $stmt_insert = $mysqli->prepare("INSERT INTO student_classes (student_id, acad_class_instance_id, enrollment_date) VALUES (?, ?, CURDATE())");
                            $stmt_insert->bind_param("ii", $student_id_for_transfer, $target_acad_class_instance_id);
                            $stmt_insert->execute();
                            $stmt_insert->close();
                        }
                    } else { // ถ้าเลือก "ไม่กำหนดห้องเรียน" (new_class_id เป็น null)
                        if ($current_assignment) { // ลบเฉพาะถ้ามีอยู่แล้ว
                            $stmt_delete = $mysqli->prepare("
                                DELETE sc FROM student_classes sc
                                JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                                WHERE sc.student_id = ? AND aci.academic_year_id = ?
                            ");
                            $stmt_delete->bind_param("ii", $student_id_for_transfer, $target_acad_year_id);
                            $stmt_delete->execute();
                            $stmt_delete->close();
                        }
                        // ถ้าไม่มีอยู่แล้วก็ไม่ต้องทำอะไร
                    }
                    $successful_transfers++; // นับจำนวนรายการที่ถูกประมวลผล (เพิ่ม/อัปเดต/ลบ)
                }
            }
            
            $mysqli->commit();
            set_session_message("success", "ย้ายนักเรียนสำเร็จ $successful_transfers คน!");
            // Redirect to student_class.php with the target academic year pre-selected
            header("Location: student_class.php?acad_year_id=" . htmlspecialchars($target_acad_year_id));
            exit();

        } catch (mysqli_sql_exception $e) { // Catch specific MySQLi exceptions
            $mysqli->rollback();
            set_session_message("danger", "เกิดข้อผิดพลาดในการย้ายนักเรียน: " . $e->getMessage());
            $display_student_assignments = true; // Stay on the assignment view to show error
        }
    }
}


// --- ส่วนที่ 3: ดึงข้อมูลนักเรียนพร้อมห้องเรียนในปีต้นทาง เพื่อแสดงในตารางกำหนดห้องใหม่ ---
$students_for_assignment = [];
if ($display_student_assignments && !empty($source_acad_year_id) && !empty($target_acad_year_id)) {
    $params = [$source_acad_year_id, $target_acad_year_id];
    $types = 'ii';
    $where_condition_student = "";

    if (!empty($source_class_filter_id)) {
        if ($source_class_filter_id === 'unassigned') { // Filter for students NOT assigned a class in the source year
            $where_condition_student = " AND aci_source.class_id IS NULL";
        } else {
            $where_condition_student = " AND aci_source.class_id = ?";
            $params[] = $source_class_filter_id;
            $types .= 'i';
        }
    }

    $stmt_students_in_source_year = $mysqli->prepare("
        SELECT 
            s.student_id, 
            s.student_code, 
            s.prefix, 
            s.first_name, 
            s.last_name,
            c_source.class_name as source_class_name,
            c_source.class_code as source_class_code,
            aci_target.class_id as current_target_class_id
        FROM students s
        LEFT JOIN student_classes sc_source ON s.student_id = sc_source.student_id
        LEFT JOIN academic_class_instances aci_source ON sc_source.acad_class_instance_id = aci_source.acad_class_instance_id AND aci_source.academic_year_id = ?
        LEFT JOIN classes c_source ON aci_source.class_id = c_source.class_id
        LEFT JOIN student_classes sc_target ON s.student_id = sc_target.student_id
        LEFT JOIN academic_class_instances aci_target ON sc_target.acad_class_instance_id = aci_target.acad_class_instance_id AND aci_target.academic_year_id = ?
        WHERE 1=1 " . $where_condition_student . "
        ORDER BY s.student_code ASC
    ");

    if ($stmt_students_in_source_year === false) {
        // Handle SQL prepare error more robustly
        set_session_message("danger", "SQL Prepare Error: " . $mysqli->error);
        $display_student_assignments = false;
    } else {
        $stmt_students_in_source_year->bind_param($types, ...$params);
        $stmt_students_in_source_year->execute();
        $result_students_in_source_year = $stmt_students_in_source_year->get_result();
        while ($row = $result_students_in_source_year->fetch_assoc()) {
            $students_for_assignment[] = $row;
        }
        $stmt_students_in_source_year->close();
    }
}


$page_title = "ย้ายชั้นปีนักเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-exchange-alt me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_class.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ขั้นตอนที่ 1: เลือกปีการศึกษาและกรองนักเรียน</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="student_year_transfer.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="source_acad_year_id" class="form-label">ย้ายนักเรียนจากปีการศึกษา (ต้นทาง)</label>
                                <select name="source_acad_year_id" id="source_acad_year_id" class="form-select" required>
                                    <option value="">-- เลือกปีการศึกษาต้นทาง --</option>
                                    <?php foreach($academic_years_arr as $year_option): ?>
                                        <option value="<?php echo $year_option['acad_year_id']; ?>" 
                                                <?php if($source_acad_year_id == $year_option['acad_year_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($year_option['year']); ?>
                                            <?php if($year_option['is_current_year']) echo ' (ปัจจุบัน)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="target_acad_year_id" class="form-label">ย้ายนักเรียนไปยังปีการศึกษา (ปลายทาง)</label>
                                <select name="target_acad_year_id" id="target_acad_year_id" class="form-select" required>
                                    <option value="">-- เลือกปีการศึกษาปลายทาง --</option>
                                    <?php foreach($academic_years_arr as $year_option): ?>
                                        <option value="<?php echo $year_option['acad_year_id']; ?>" 
                                                <?php if($target_acad_year_id == $year_option['acad_year_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($year_option['year']); ?>
                                            <?php if($year_option['is_current_year']) echo ' (ปัจจุบัน)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="source_class_filter_id" class="form-label">กรองตามห้องเรียน (ปีต้นทาง)</label>
                                <select name="source_class_filter_id" id="source_class_filter_id" class="form-select">
                                    <option value="">-- แสดงทุกห้อง --</option>
                                    <option value="unassigned" <?php if($source_class_filter_id == 'unassigned') echo 'selected'; ?>>-- ยังไม่ได้กำหนดห้องเรียน --</option>
                                    <?php foreach($all_classes_arr as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php if($source_class_filter_id == $class['class_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" name="select_years" class="btn btn-primary"><i class="fas fa-filter me-1"></i> เลือกปี/กรอง</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($display_student_assignments && !empty($students_for_assignment)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ขั้นตอนที่ 2: กำหนดห้องเรียนใหม่สำหรับปีการศึกษา <span class="text-info"><?php echo htmlspecialchars($academic_years_map[$target_acad_year_id] ?? 'N/A'); ?></span></h5>
                    <p class="text-muted mb-0">นักเรียนจากปีการศึกษา <span class="text-info"><?php echo htmlspecialchars($academic_years_map[$source_acad_year_id] ?? 'N/A'); ?></span></p>
                    <p class="text-danger mb-0">⚠️ โปรดตรวจสอบให้แน่ใจว่าการเลือกปีถูกต้องก่อนบันทึก</p>
                </div>
                <div class="card-body">
                    <form method="post" action="student_year_transfer.php">
                        <input type="hidden" name="source_acad_year_id" value="<?php echo htmlspecialchars($source_acad_year_id); ?>">
                        <input type="hidden" name="target_acad_year_id" value="<?php echo htmlspecialchars($target_acad_year_id); ?>">
                        <input type="hidden" name="source_class_filter_id" value="<?php echo htmlspecialchars($source_class_filter_id); ?>">
                        <input type="hidden" name="transfer_students" value="1">

                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>รหัสนักเรียน</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>ห้องเรียน (ปีต้นทาง)</th>
                                        <th>ห้องเรียนใหม่ (ปีปลายทาง)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_for_assignment as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($student['prefix'] . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['source_class_code'] . ' - ' . $student['source_class_name'] ?? 'ไม่ได้กำหนด'); ?></td>
                                        <td>
                                            <select name="student_new_class[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                                <option value="">-- ไม่กำหนดห้องเรียน --</option>
                                                <?php foreach ($all_classes_arr as $class): ?>
                                                    <option value="<?php echo htmlspecialchars($class['class_id']); ?>"
                                                            <?php if ($student['current_target_class_id'] == $class['class_id']) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3 gap-2">
                            <button type="button" class="btn btn-secondary px-4" onclick="window.location.href='student_year_transfer.php'">ยกเลิก/กลับไปเลือกปี</button>
                            <button type="submit" class="btn btn-success px-4" onclick="return confirm('ยืนยันการย้ายนักเรียนตามการกำหนดห้องเรียนใหม่นี้? โปรดตรวจสอบความถูกต้องอีกครั้งก่อนยืนยัน!')"><i class="fas fa-save me-2"></i>บันทึกการย้ายชั้นปี</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($display_student_assignments && empty($students_for_assignment)): ?>
                <div class="alert alert-warning text-center">ไม่พบนักเรียนตามเงื่อนไขที่กำหนดในปีการศึกษาต้นทาง (<?php echo htmlspecialchars($academic_years_map[$source_acad_year_id] ?? 'N/A'); ?>)</div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>