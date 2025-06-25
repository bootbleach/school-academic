<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$message = get_session_message();

// --- ส่วนที่ 1: จัดการการค้นหา, กรอง และเรียงลำดับ ---
$search_term = $_GET['search'] ?? '';
$filter_ay_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
$sort_column_whitelist = ['class_name', 'subject_name', 'academic_year_name'];
$sort_column = $_GET['sort'] ?? 'class_name';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'class_name';
}

// --- ดึงข้อมูลสำหรับ Filter Dropdown ---
$academic_years_filter = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");

// --- แก้ไข SQL ให้รองรับทุกเงื่อนไข ---
$teaching_load = [];
$params = [$teacher_id, $teacher_id];
$types = 'ii';
$where_conditions = [];

// เงื่อนไขพื้นฐานคือต้องเป็นครูผู้สอนหรือครูประจำชั้น
$sql_fetch = "SELECT DISTINCT
                c.class_id, c.class_name, c.class_code,
                s.subject_id, s.subject_name, s.subject_code,
                ay.year AS academic_year_name
            FROM class_schedules cs
            JOIN classes c ON cs.class_id = c.class_id
            JOIN subjects s ON cs.subject_id = s.subject_id
            JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id
            WHERE (cs.teacher_id = ? OR c.teacher_id = ?)";

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search_term)) {
    $where_conditions[] = "(c.class_name LIKE ? OR s.subject_name LIKE ? OR s.subject_code LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params, $like_term, $like_term, $like_term);
    $types .= 'sss';
}
// เพิ่มเงื่อนไขการกรองตามปีการศึกษา
if (!empty($filter_ay_id)) {
    $where_conditions[] = "c.academic_year_id = ?";
    $params[] = $filter_ay_id;
    $types .= 'i';
}

if (!empty($where_conditions)) {
    $sql_fetch .= " AND " . implode(' AND ', $where_conditions);
}

// จัดการการเรียงลำดับ
$order_by_map = ['class_name' => 'c.class_name', 'subject_name' => 's.subject_name', 'academic_year_name' => 'ay.year'];
$order_by_column = $order_by_map[$sort_column] ?? 'c.class_name';
$sql_fetch .= " ORDER BY $order_by_column $sort_order, s.subject_code ASC";

$stmt_fetch = $mysqli->prepare($sql_fetch);
$stmt_fetch->bind_param($types, ...$params);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $teaching_load[] = $row;
}
$stmt_fetch->close();

$page_title = "เลือกวิชาเพื่อกรอกคะแนน";
require_once 'includes/teacher_header.php';
require_once 'includes/teacher_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <p>เลือกคลาสเรียนและวิชาที่คุณมีสิทธิ์ เพื่อเข้าไปจัดการคะแนน</p>
            <hr>
            <?php echo $message; ?>
            <div class="card">
                <div class="card-header">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label for="search" class="visually-hidden">ค้นหา</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="ค้นหาชื่อคลาส, ชื่อวิชา, รหัสวิชา..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="academic_year_id" class="visually-hidden">ปีการศึกษา</label>
                            <select name="academic_year_id" id="academic_year_id" class="form-select">
                                <option value="">-- กรองตามปีการศึกษาทั้งหมด --</option>
                                <?php foreach($academic_years_filter as $ay): ?>
                                    <option value="<?php echo $ay['acad_year_id']; ?>" <?php if($filter_ay_id == $ay['acad_year_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($ay['year']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> กรอง</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php
                                    function sort_link($column, $display_name, $current_sort, $current_order) {
                                        $order = ($column == $current_sort && $current_order == 'ASC') ? 'desc' : 'asc';
                                        $icon = ($column == $current_sort) ? ($current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : '';
                                        $query_params = ['sort' => $column, 'order' => $order, 'search' => $_GET['search'] ?? '', 'academic_year_id' => $_GET['academic_year_id'] ?? ''];
                                        return "<a href=\"?".http_build_query(array_filter($query_params))."\" class='text-decoration-none text-dark'>$display_name$icon</a>";
                                    }
                                    ?>
                                    <th><?php echo sort_link('class_name', 'ชื่อคลาส/ห้องเรียน', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('subject_name', 'วิชาที่สอน', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('academic_year_name', 'ปีการศึกษา', $sort_column, $sort_order); ?></th>
                                    <th class="text-end">เมนู</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teaching_load)): ?>
                                    <tr><td colspan="4" class="text-center py-3">ไม่พบวิชาที่สอนตามเงื่อนไขที่กำหนด</td></tr>
                                <?php else: ?>
                                    <?php foreach ($teaching_load as $load): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($load['class_name']); ?> (<?php echo htmlspecialchars($load['class_code']); ?>)</td>
                                            <td><?php echo htmlspecialchars($load['subject_name']); ?> (<?php echo htmlspecialchars($load['subject_code']); ?>)</td>
                                            <td><?php echo htmlspecialchars($load['academic_year_name']); ?></td>
                                            <td class="text-end">
                                                <a href="teacher_enter_scores.php?class_id=<?php echo $load['class_id']; ?>&subject_id=<?php echo $load['subject_id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-edit me-1"></i> กรอกคะแนน
                                                </a>
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

<?php require_once 'includes/teacher_footer.php'; ?>