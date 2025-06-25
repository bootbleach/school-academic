<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();

// --- ส่วนที่ 1: จัดการการค้นหา, กรอง และเรียงลำดับ ---
$search_term = $_GET['search'] ?? '';
$filter_ay_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0; // รับค่าปีการศึกษาที่ใช้กรอง

$sort_column_whitelist = ['class_name', 'subject_name', 'academic_year_name'];
$sort_column = $_GET['sort'] ?? 'academic_year_name';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'academic_year_name';
}

// --- ดึงข้อมูลสำหรับ Filter Dropdown ---
$academic_years_filter = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");

// --- ส่วนที่ 2: แก้ไขการดึงข้อมูลให้รองรับทุกเงื่อนไข ---
$all_sections = [];
$params = [];
$types = '';
$where_conditions = [];

$sql_fetch = "SELECT DISTINCT
                c.class_id, c.class_name, c.class_code,
                s.subject_id, s.subject_name, s.subject_code,
                ay.year AS academic_year_name
            FROM class_schedules cs
            JOIN classes c ON cs.class_id = c.class_id
            JOIN subjects s ON cs.subject_id = s.subject_id
            JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id";

// สร้างเงื่อนไข WHERE แบบไดนามิก
if (!empty($search_term)) {
    $where_conditions[] = "(c.class_name LIKE ? OR s.subject_name LIKE ? OR s.subject_code LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params, $like_term, $like_term, $like_term);
    $types .= 'sss';
}
if (!empty($filter_ay_id)) {
    $where_conditions[] = "c.academic_year_id = ?";
    $params[] = $filter_ay_id;
    $types .= 'i';
}

if (!empty($where_conditions)) {
    $sql_fetch .= " WHERE " . implode(' AND ', $where_conditions);
}

// ... (ส่วน ORDER BY เหมือนเดิม) ...

$stmt_fetch = $mysqli->prepare($sql_fetch);
if (!empty($params)) {
    $stmt_fetch->bind_param($types, ...$params);
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $all_sections[] = $row;
}
$stmt_fetch->close();

$page_title = "จัดการคะแนนทั้งหมด";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <p>เลือกวิชาและห้องเรียนที่ต้องการเพื่อเข้าไปจัดการคะแนน</p>
            <hr>
            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-header">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-5">
                             <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อคลาส, ชื่อวิชา..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-5">
                            <select name="academic_year_id" class="form-select">
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
                                        $order = ($column == $current_sort && $current_order == 'DESC') ? 'asc' : 'desc';
                                        $icon = ($column == $current_sort) ? ($current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : '';
                                        // **แก้ไข:** เพิ่มพารามิเตอร์ของ filter เข้าไปในลิงก์ด้วย
                                        $query_params = [
                                            'sort' => $column,
                                            'order' => $order,
                                            'search' => $_GET['search'] ?? '',
                                            'academic_year_id' => $_GET['academic_year_id'] ?? ''
                                        ];
                                        $link = http_build_query(array_filter($query_params));
                                        return "<a href=\"?$link\" class='text-decoration-none text-dark'>$display_name$icon</a>";
                                    }
                                    ?>
                                    <th><?php echo sort_link('class_name', 'ชื่อคลาส/ห้องเรียน', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('subject_name', 'ชื่อวิชา', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('academic_year_name', 'ปีการศึกษา/เทอม', $sort_column, $sort_order); ?></th>
                                    <th class="text-end">เมนู</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_sections)): ?>
                                    <tr><td colspan="4" class="text-center py-3">ไม่พบข้อมูลตามเงื่อนไขที่กำหนด</td></tr>
                                <?php else: ?>
                                    <?php foreach ($all_sections as $section): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($section['class_name']); ?> (<?php echo htmlspecialchars($section['class_code']); ?>)</td>
                                            <td><?php echo htmlspecialchars($section['subject_name']); ?> (<?php echo htmlspecialchars($section['subject_code']); ?>)</td>
                                            <td><?php echo htmlspecialchars($section['academic_year_name']); ?></td>
                                            <td class="text-end">
                                                <a href="teacher_enter_scores.php?class_id=<?php echo $section['class_id']; ?>&subject_id=<?php echo $section['subject_id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-edit me-1"></i> เข้าไปจัดการคะแนน
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

<?php require_once 'includes/admin_footer.php'; ?>