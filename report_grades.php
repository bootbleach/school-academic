<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();

// --- จัดการการค้นหาและกรอง ---
$search_term = $_GET['search'] ?? '';
$filter_ay_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
$sort_column_whitelist = ['class_code', 'class_name', 'teacher_name', 'academic_year_name'];
$sort_column = $_GET['sort'] ?? 'class_code';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'class_code';
}

$academic_years_filter = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");

$classes = [];
$params = [];
$types = '';
$where_conditions = [];

$sql_fetch = "SELECT c.class_id, c.class_code, c.class_name, t.fullname AS teacher_name, ay.year AS academic_year_name
            FROM classes c
            LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id";

if (!empty($search_term)) {
    $where_conditions[] = "(c.class_name LIKE ? OR c.class_code LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params, $like_term, $like_term);
    $types .= 'ss';
}
if (!empty($filter_ay_id)) {
    $where_conditions[] = "c.academic_year_id = ?";
    $params[] = $filter_ay_id;
    $types .= 'i';
}

if (!empty($where_conditions)) {
    $sql_fetch .= " WHERE " . implode(' AND ', $where_conditions);
}

$order_by_map = ['class_code' => 'c.class_code', 'class_name' => 'c.class_name', 'teacher_name' => 't.fullname', 'academic_year_name' => 'ay.year'];
$order_by_column = $order_by_map[$sort_column] ?? 'c.class_code';
$sql_fetch .= " ORDER BY $order_by_column $sort_order";

$stmt_fetch = $mysqli->prepare($sql_fetch);
if (!empty($params)) {
    $stmt_fetch->bind_param($types, ...$params);
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt_fetch->close();

$page_title = "เลือกห้องเรียนเพื่อดูคะแนนรวม";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <h1><i class="fas fa-file-invoice me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <p>ใช้ตัวกรองและแถบค้นหาเพื่อค้นหาห้องเรียนที่ต้องการ จากนั้นคลิก "ดูรายงาน"</p>
            <hr>

            <div class="card">
                <div class="card-header">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6"><label for="search" class="form-label">ค้นหาห้องเรียน (รหัส หรือ ชื่อ)</label><input type="text" name="search" id="search" class="form-control" placeholder="ค้นหา..." value="<?php echo htmlspecialchars($search_term); ?>"></div>
                        <div class="col-md-4"><label for="academic_year_id" class="form-label">ปีการศึกษา</label><select name="academic_year_id" id="academic_year_id" class="form-select"><option value="">-- แสดงทั้งหมด --</option><?php foreach($academic_years_filter as $ay): ?><option value="<?php echo $ay['acad_year_id']; ?>" <?php if($filter_ay_id == $ay['acad_year_id']) echo 'selected'; ?>><?php echo htmlspecialchars($ay['year']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2 d-grid"><button type="submit" class="btn btn-primary">กรอง</button></div>
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
                                        
                                        // **แก้ไข:** เปลี่ยน $params เป็น $query_params
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
                                    <th><?php echo sort_link('class_code', 'รหัสคลาส', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('class_name', 'ชื่อคลาส/ห้องเรียน', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('teacher_name', 'ครูประจำชั้น', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('academic_year_name', 'ปีการศึกษา', $sort_column, $sort_order); ?></th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($classes)): ?>
                                    <tr><td colspan="5" class="text-center py-3">ไม่พบข้อมูลคลาสเรียนตามเงื่อนไข</td></tr>
                                <?php else: ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($class['academic_year_name'] ?? 'N/A'); ?></td>
                                            <td class="text-end">
                                                <a href="view_score_details.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-dark btn-sm"><i class="fas fa-print me-1"></i> ดูรายงาน</a>
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