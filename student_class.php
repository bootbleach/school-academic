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

// --- ส่วนที่ 1: ดึงข้อมูลที่จำเป็นสำหรับการแสดงผลและ Filter ---

// ดึงปีการศึกษาทั้งหมดและจัดเตรียมข้อมูลสำหรับแยกปีและภาคเรียน
$academic_years_raw = $mysqli->query("SELECT acad_year_id, year, is_current_year FROM academic_years ORDER BY year DESC");
$academic_years_options = [];
$unique_years = [];
$unique_terms = [];
$acad_year_map = []; // Map "year-term" string to acad_year_id

$current_acad_year_id = '';
$current_year_only = '';
$current_term_number = '';

while ($row = $academic_years_raw->fetch_assoc()) {
    $full_year_string = $row['year']; // e.g., "1/2568"

    // แยก ภาคเรียน และ ปี
    $parts = explode('/', $full_year_string);
    $term_number = $parts[0] ?? '';
    $year_only = $parts[1] ?? '';

    $academic_years_options[] = [
        'acad_year_id' => $row['acad_year_id'],
        'full_year_string' => $full_year_string,
        'term_number' => $term_number,
        'year_only' => $year_only,
        'is_current_year' => $row['is_current_year']
    ];

    if (!empty($year_only) && !in_array($year_only, $unique_years)) {
        $unique_years[] = $year_only;
    }
    if (!empty($term_number) && !in_array($term_number, $unique_terms)) {
        $unique_terms[] = $term_number;
    }

    $acad_year_map[$year_only . '-' . $term_number] = $row['acad_year_id'];

    if ($row['is_current_year']) {
        $current_acad_year_id = $row['acad_year_id'];
        $current_year_only = $year_only;
        $current_term_number = $term_number;
    }
}
// เรียงลำดับปีและภาคเรียน
rsort($unique_years); // ปีล่าสุดขึ้นก่อน
sort($unique_terms); // ภาคเรียนน้อยไปมาก

// ดึงชั้นเรียนทั้งหมดสำหรับ Filter และสำหรับ Dropdown เลือกห้องเรียน
$classes_for_filter = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");
$all_classes_result = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");
$all_classes_arr = [];
while ($row = $all_classes_result->fetch_assoc()) {
    $all_classes_arr[] = $row;
}


// --- ส่วนที่ 2: จัดการการค้นหา, กรอง และเรียงลำดับ (รวม Pagination) ---

// รับค่าปีการศึกษาที่เลือกจาก URL หรือกำหนดเป็นปีปัจจุบัน
$selected_acad_year_id = $_GET['acad_year_id'] ?? ''; // Keep this for direct selection if user prefers
$selected_year_only = $_GET['selected_year_only'] ?? '';
$selected_term_number = $_GET['selected_term_number'] ?? '';

// ถ้ามีการเลือกปีและภาคเรียนใหม่ ให้หา acad_year_id ที่เกี่ยวข้อง
if (!empty($selected_year_only) && !empty($selected_term_number)) {
    $lookup_key = $selected_year_only . '-' . $selected_term_number;
    if (isset($acad_year_map[$lookup_key])) {
        $selected_acad_year_id = $acad_year_map[$lookup_key];
    } else {
        // หากไม่มีการจับคู่ที่ถูกต้อง ให้รีเซ็ตหรือจัดการข้อผิดพลาด
        $selected_acad_year_id = ''; // หรือกำหนดค่าเริ่มต้นอื่น
    }
} else if (empty($selected_acad_year_id)) {
    // ถ้าไม่มีการเลือกใดๆ ให้กำหนดเป็นปีปัจจุบันและภาคเรียนปัจจุบัน
    $selected_acad_year_id = $current_acad_year_id;
    $selected_year_only = $current_year_only;
    $selected_term_number = $current_term_number;
}

// รับค่า Filter/Search/Sort
$search_term = $_GET['search'] ?? '';
$filter_class_id = $_GET['class_id'] ?? '';

$sort_column_whitelist = ['student_code', 'username', 'first_name', 'class_name'];
$sort_column = $_GET['sort'] ?? 'student_code';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'student_code';
}

// --- การตั้งค่า Pagination ---
$records_per_page = 50;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;


// --- ส่วนที่ 3: ประมวลผลการบันทึกการกำหนดห้องเรียนพร้อมกันทีละหลายคน (เมื่อมีการส่งฟอร์ม POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_batch_update'])) {
    if (empty($selected_acad_year_id)) {
        set_session_message("danger", "ไม่สามารถบันทึกได้: ต้องเลือกปีการศึกษาและภาคเรียนก่อน.");
        header("Location: student_class.php");
        exit();
    }

    $mysqli->begin_transaction(); // เริ่ม Transaction
    try {
        $successful_updates = 0;
        if (isset($_POST['student_class_assignments']) && is_array($_POST['student_class_assignments'])) {
            foreach ($_POST['student_class_assignments'] as $student_id_to_update => $new_class_id) {
                $student_id_to_update = (int)$student_id_to_update;
                // หาก $new_class_id เป็นสตริงว่าง (จาก option value="") ให้ถือว่าเป็น null เพื่อการลบหรือไม่ได้กำหนด
                $new_class_id = empty($new_class_id) ? null : (int)$new_class_id;

                $target_acad_class_instance_id = null;
                if ($new_class_id !== null) {
                    // ค้นหา acad_class_instance_id จาก class_id และ acad_year_id (ซึ่งตอนนี้หมายถึง acad_term_id/ปีการศึกษา)
                    $stmt_get_aci = $mysqli->prepare("SELECT acad_class_instance_id FROM academic_class_instances WHERE class_id = ? AND academic_year_id = ? LIMIT 1");
                    $stmt_get_aci->bind_param("ii", $new_class_id, $selected_acad_year_id);
                    $stmt_get_aci->execute();
                    $stmt_get_aci->bind_result($target_acad_class_instance_id);
                    $stmt_get_aci->fetch();
                    $stmt_get_aci->close();

                    if ($target_acad_class_instance_id === null) {
                        // ถ้าไม่พบ academic_class_instance ให้สร้างใหม่
                        $stmt_insert_aci = $mysqli->prepare("INSERT INTO academic_class_instances (class_id, academic_year_id) VALUES (?, ?)");
                        $stmt_insert_aci->bind_param("ii", $new_class_id, $selected_acad_year_id);
                        $stmt_insert_aci->execute();
                        $target_acad_class_instance_id = $mysqli->insert_id;
                        $stmt_insert_aci->close();
                    }
                }

                // ตรวจสอบว่ามี record สำหรับนักเรียนคนนี้ในปีการศึกษาที่เลือกอยู่แล้วหรือไม่
                $stmt_check_exist = $mysqli->prepare("
                    SELECT COUNT(*)
                    FROM student_classes sc
                    JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                    WHERE sc.student_id = ? AND aci.academic_year_id = ?
                ");
                $stmt_check_exist->bind_param("ii", $student_id_to_update, $selected_acad_year_id);
                $stmt_check_exist->execute();
                $row_count = $stmt_check_exist->get_result()->fetch_row()[0];
                $stmt_check_exist->close();


                if ($target_acad_class_instance_id !== null) { // ถ้ามีการเลือกห้องเรียนใหม่ (ไม่ว่างเปล่า)
                    if ($row_count > 0) {
                        // มีอยู่แล้ว: UPDATE
                        $stmt_update = $mysqli->prepare("
                            UPDATE student_classes sc
                            JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                            SET sc.acad_class_instance_id = ?
                            WHERE sc.student_id = ? AND aci.academic_year_id = ?
                        ");
                        $stmt_update->bind_param("iii", $target_acad_class_instance_id, $student_id_to_update, $selected_acad_year_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        // ยังไม่มี: INSERT
                        $stmt_insert = $mysqli->prepare("INSERT INTO student_classes (student_id, acad_class_instance_id, enrollment_date) VALUES (?, ?, CURDATE())");
                        $stmt_insert->bind_param("ii", $student_id_to_update, $target_acad_class_instance_id);
                        $stmt_insert->execute();
                        $stmt_insert->close();
                    }
                } else { // ถ้าเลือก "ไม่กำหนดห้องเรียน" (new_class_id เป็น null)
                    if ($row_count > 0) {
                        // มีอยู่แล้ว: DELETE
                        $stmt_delete = $mysqli->prepare("
                            DELETE sc FROM student_classes sc
                            JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id
                            WHERE sc.student_id = ? AND aci.academic_year_id = ?
                        ");
                        $stmt_delete->bind_param("ii", $student_id_to_update, $selected_acad_year_id);
                        $stmt_delete->execute();
                        $stmt_delete->close();
                    }
                    // ถ้าไม่มีอยู่แล้วก็ไม่ต้องทำอะไร
                }
                $successful_updates++;
            }
        }

        $mysqli->commit(); // Commit Transaction
        set_session_message("success", "บันทึกการกำหนดห้องเรียนของนักเรียน " . $successful_updates . " คน เรียบร้อยแล้ว!");
        header("Location: student_class.php?" . http_build_query([
            'selected_year_only' => $selected_year_only,
            'selected_term_number' => $selected_term_number,
            'search' => $search_term,
            'class_id' => $filter_class_id,
            'sort' => $sort_column,
            'order' => $sort_order,
            'page' => $current_page // รักษาค่าหน้าปัจจุบันไว้
        ]));
        exit();
    } catch (mysqli_sql_exception $e) {
        $mysqli->rollback(); // Rollback ถ้ามีข้อผิดพลาด
        set_session_message("danger", "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
        // ไม่ต้อง redirect เพราะต้องการให้แสดง error บนหน้าเดิม
    }
}


// --- ส่วนที่ 4: ดึงข้อมูลนักเรียนพร้อมข้อมูลห้องเรียนสำหรับปีการศึกษาที่เลือก (รวม Pagination) ---
$students = [];
$params = [];
$types = '';
$where_conditions = [];

// Base SQL Query (without LIMIT/OFFSET) for counting total records
$sql_base = "FROM students s
             LEFT JOIN student_classes sc ON s.student_id = sc.student_id
             LEFT JOIN academic_class_instances aci ON sc.acad_class_instance_id = aci.acad_class_instance_id AND aci.academic_year_id = ?
             LEFT JOIN classes c ON aci.class_id = c.class_id";

// Add academic year to parameters for base query
$params_base = [$selected_acad_year_id];
$types_base = 'i';

// Build WHERE conditions for counting
if (!empty($search_term)) {
    $where_conditions[] = "(s.student_code LIKE ? OR s.username LIKE ? OR CONCAT(s.prefix, s.first_name, ' ', s.last_name) LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params_base, $like_term, $like_term, $like_term);
    $types_base .= 'sss';
}

if (!empty($filter_class_id)) {
    if ($filter_class_id == 'unassigned') {
        $where_conditions[] = "sc.acad_class_instance_id IS NULL";
    } else {
        $where_conditions[] = "aci.class_id = ?";
        $params_base[] = $filter_class_id;
        $types_base .= 'i';
    }
}

$sql_where_clause = '';
if (!empty($where_conditions)) {
    $sql_where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

// 1. Get total records for pagination
$sql_count = "SELECT COUNT(s.student_id) " . $sql_base . $sql_where_clause;
$stmt_count = $mysqli->prepare($sql_count);
if ($stmt_count === false) {
    echo "<pre>";
    echo "<h2>SQL Prepare Error (Count)!</h2>";
    echo "<b>MySQL Error:</b> " . $mysqli->error . "<br>";
    echo "<b>Failing Query:</b> <code style='display: block; white-space: pre-wrap; word-break: break-all;'>" . htmlspecialchars($sql_count) . "</code><br>";
    echo "<b>Parameters:</b> ";
    print_r($params_base);
    echo "<br><b>Types:</b> " . htmlspecialchars($types_base);
    echo "</pre>";
    exit();
}
$stmt_count->bind_param($types_base, ...$params_base);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);
// ตรวจสอบให้แน่ใจว่า current_page ไม่เกิน total_pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
}
if ($current_page < 1) { // ตรวจสอบไม่ให้ค่าน้อยกว่า 1
    $current_page = 1;
    $offset = 0;
}


// 2. Fetch actual student data with LIMIT and OFFSET
$sql_fetch = "SELECT s.student_id, s.student_code, s.username, s.prefix, s.first_name, s.last_name,
                         c.class_name, aci.class_id as student_current_class_id "
             . $sql_base . $sql_where_clause;


// Arrange ORDER BY clause
$order_by_map = [
    'student_code' => 's.student_code',
    'username' => 's.username',
    'first_name' => 's.first_name',
    'class_name' => 'c.class_name'
];
$order_by_column = $order_by_map[$sort_column];
if ($sort_column === 'student_code') {
    $sql_fetch .= " ORDER BY CAST(SUBSTRING(s.student_code, 2) AS UNSIGNED) $sort_order, s.student_code $sort_order";
} else {
    $sql_fetch .= " ORDER BY $order_by_column $sort_order";
}

$sql_fetch .= " LIMIT ? OFFSET ?"; // Add LIMIT and OFFSET for pagination

$params_fetch = $params_base; // Start with base parameters
$types_fetch = $types_base; // Start with base types

// Add LIMIT and OFFSET parameters
$params_fetch[] = $records_per_page;
$types_fetch .= 'i';
$params_fetch[] = $offset;
$types_fetch .= 'i';


$stmt_fetch = $mysqli->prepare($sql_fetch);
if ($stmt_fetch === false) {
    echo "<pre>"; // Use <pre> for better formatting of output
    echo "<h2>SQL Prepare Error!</h2>";
    echo "<b>MySQL Error:</b> " . $mysqli->error . "<br>";
    echo "<b>Failing Query:</b> <code style='display: block; white-space: pre-wrap; word-break: break-all;'>" . htmlspecialchars($sql_fetch) . "</code><br>";
    echo "<b>Parameters:</b> ";
    print_r($params_fetch);
    echo "<br><b>Types:</b> " . htmlspecialchars($types_fetch);
    echo "</pre>";
    exit();
}
$stmt_fetch->bind_param($types_fetch, ...$params_fetch);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt_fetch->close();


$page_title = "จัดการห้องเรียนนักเรียนแต่ละปี";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_year_transfer.php" class="btn btn-info">
                    <i class="fas fa-exchange-alt me-2"></i> ย้ายชั้นปี
                </a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="selected_year_only" class="form-label visually-hidden">ปีการศึกษา</label>
                            <select name="selected_year_only" id="selected_year_only" class="form-select">
                                <option value="">-- เลือกปีการศึกษา --</option>
                                <?php foreach ($unique_years as $year_option): ?>
                                    <option value="<?php echo htmlspecialchars($year_option); ?>"
                                        <?php if ($selected_year_only == $year_option) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($year_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="selected_term_number" class="form-label visually-hidden">ภาคเรียน</label>
                            <select name="selected_term_number" id="selected_term_number" class="form-select">
                                <option value="">-- เลือกภาคเรียน --</option>
                                <?php foreach ($unique_terms as $term_option): ?>
                                    <option value="<?php echo htmlspecialchars($term_option); ?>"
                                        <?php if ($selected_term_number == $term_option) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars("ภาคเรียนที่ " . $term_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="class_id" class="form-label visually-hidden">ห้องเรียน</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">-- กรองตามห้องเรียนทั้งหมด --</option>
                                <option value="unassigned" <?php if ($filter_class_id == 'unassigned') echo 'selected'; ?>>-- ยังไม่ได้กำหนดห้องเรียน --</option>
                                <?php foreach ($classes_for_filter as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>" <?php if ($filter_class_id == $class['class_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="search" class="form-label visually-hidden">ค้นหา</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="ค้นหา รหัสนักเรียน, Username, ชื่อ..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> กรอง</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <form method="post" action="student_class.php?<?php echo http_build_query([
                        'selected_year_only' => $selected_year_only,
                        'selected_term_number' => $selected_term_number,
                        'search' => $search_term,
                        'class_id' => $filter_class_id,
                        'sort' => $sort_column,
                        'order' => $sort_order,
                        'page' => $current_page // รักษาค่าหน้าปัจจุบันไว้เมื่อ Submit ฟอร์ม
                    ]); ?>">
                        <input type="hidden" name="submit_batch_update" value="1">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo sort_link('student_code', 'รหัสนักเรียน', $sort_column, $sort_order, $selected_year_only, $selected_term_number, $search_term, $filter_class_id, $current_page); ?></th>
                                        <th><?php echo sort_link('username', 'Username', $sort_column, $sort_order, $selected_year_only, $selected_term_number, $search_term, $filter_class_id, $current_page); ?></th>
                                        <th><?php echo sort_link('first_name', 'ชื่อ-นามสกุล', $sort_column, $sort_order, $selected_year_only, $selected_term_number, $search_term, $filter_class_id, $current_page); ?></th>
                                        <th>ห้องเรียน (<?php echo htmlspecialchars($selected_year_only . '/' . $selected_term_number); ?>)</th>
                                        <th class="text-end">กำหนดห้องเรียน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-3">ไม่พบข้อมูลนักเรียน<?php if (!empty($search_term) || !empty($filter_class_id) || !empty($selected_year_only) || !empty($selected_term_number)) echo " ตามเงื่อนไขที่กำหนด"; ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo (($current_page - 1) * $records_per_page) + $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                <td><?php echo htmlspecialchars($student['prefix'] . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_name'] ?? 'ยังไม่ได้กำหนด'); ?></td>
                                                <td class="text-end">
                                                    <select name="student_class_assignments[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                                        <option value="">-- ไม่กำหนดห้องเรียน --</option>
                                                        <?php foreach ($all_classes_arr as $class): ?>
                                                            <option value="<?php echo htmlspecialchars($class['class_id']); ?>"
                                                                <?php if ($student['student_current_class_id'] == $class['class_id']) echo 'selected'; ?>>
                                                                <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <?php if (!empty($students)): // แสดงปุ่ม Save เฉพาะเมื่อมีนักเรียนให้จัดการ
                            ?>
                                <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-2"></i>บันทึกการกำหนดห้องเรียนทั้งหมด</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" aria-label="First">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&lsaquo;</span>
                                    </a>
                                </li>
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" aria-label="Next">
                                        <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" aria-label="Last">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    </div>
            </div>
        </div>
    </main>
</div>

<?php
// ฟังก์ชัน sort_link ให้เก็บค่า filter ต่างๆ ไปด้วย (ย้ายมาไว้ข้างล่างเพื่อไม่ให้ซ้ำซ้อน)
function sort_link($column, $display_name, $current_sort, $current_order, $selected_year_only, $selected_term_number, $search_term, $filter_class_id, $current_page)
{
    $order = ($column == $current_sort && $current_order == 'ASC') ? 'desc' : 'asc';
    $icon = ($column == $current_sort) ? ($current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : '';

    $query_params = [
        'sort' => $column,
        'order' => $order,
        'search' => $search_term,
        'class_id' => $filter_class_id,
        'selected_year_only' => $selected_year_only,
        'selected_term_number' => $selected_term_number,
        'page' => $current_page // รักษาค่าหน้าปัจจุบันไว้เมื่อคลิก Sort
    ];
    $link = http_build_query(array_filter($query_params));

    return "<a href=\"?$link\" class='text-decoration-none text-dark'>$display_name$icon</a>";
}

require_once 'includes/admin_footer.php';
?>