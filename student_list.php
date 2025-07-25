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

// --- ส่วนที่ 1: จัดการการค้นหาและเรียงลำดับ ---
$search_term = $_GET['search'] ?? '';

$sort_column_whitelist = ['student_code', 'username', 'first_name']; // ลบ class_name ออก
$sort_column = $_GET['sort'] ?? 'student_code';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'student_code';
}

// --- ส่วนที่ 2: การจัดการลบข้อมูล ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // เริ่ม Transaction เพื่อให้การลบข้อมูลทั้งหมดเป็นไปอย่างสมบูรณ์ หรือยกเลิกทั้งหมด
    $mysqli->begin_transaction();
    try {
        // ลบข้อมูลนักเรียนจากตาราง students
        $stmt_delete_student = $mysqli->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt_delete_student->bind_param("i", $delete_id);
        $stmt_delete_student->execute();

        if ($stmt_delete_student->affected_rows > 0) {
            // เนื่องจาก student_classes และ enrollments มี ON DELETE CASCADE
            // การลบนักเรียนในตาราง students จะทำให้ข้อมูลที่เกี่ยวข้องถูกลบโดยอัตโนมัติ
            $mysqli->commit();
            set_session_message("success", "ลบข้อมูลนักเรียนรหัส #" . htmlspecialchars($delete_id) . " เรียบร้อยแล้ว.");
        } else {
            throw new Exception("ไม่พบข้อมูลนักเรียนรหัส #" . htmlspecialchars($delete_id) . " ที่จะลบ หรือเกิดข้อผิดพลาด");
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message("danger", "เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน: " . $e->getMessage());
    }
    header("Location: student_list.php");
    exit();
}


// --- ส่วนที่ 3: ดึงข้อมูลนักเรียนเท่านั้น ---
$students = [];
$params = [];
$types = '';
$where_conditions = [];

// กำหนด JOIN พื้นฐาน
// ดึงข้อมูลจากตาราง students โดยตรง ไม่ต้อง Join กับ student_classes และ classes แล้ว
$sql_fetch = "SELECT s.student_id, s.student_code, s.username, s.prefix, s.first_name, s.last_name
              FROM students s";


// สร้างเงื่อนไข WHERE แบบไดนามิก
if (!empty($search_term)) {
    $where_conditions[] = "(s.student_code LIKE ? OR s.username LIKE ? OR CONCAT(s.prefix, s.first_name, ' ', s.last_name) LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params, $like_term, $like_term, $like_term);
    $types .= 'sss';
}

if (!empty($where_conditions)) {
    $sql_fetch .= " WHERE " . implode(' AND ', $where_conditions);
}

// จัดการการเรียงลำดับ (ORDER BY)
$order_by_map = [
    'student_code' => 's.student_code',
    'username' => 's.username',
    'first_name' => 's.first_name'
];
$order_by_column = $order_by_map[$sort_column];
if ($sort_column === 'student_code') {
    $sql_fetch .= " ORDER BY CAST(SUBSTRING(s.student_code, 2) AS UNSIGNED) $sort_order, s.student_code $sort_order";
} else {
    $sql_fetch .= " ORDER BY $order_by_column $sort_order";
}

$stmt_fetch = $mysqli->prepare($sql_fetch);
if (!empty($params)) {
    // ใช้ ...$params เพื่อส่งอาร์เรย์เป็น Argument list ให้ bind_param
    $stmt_fetch->bind_param($types, ...$params);
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt_fetch->close();


$page_title = "จัดการข้อมูลนักเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
           <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="d-none d-lg-block"><i class="fas fa-user-graduate me-2"></i>จัดการข้อมูลนักเรียน</h1>
    <div class="ms-auto d-flex gap-2">
        <a href="student_bulk_add.php" class="btn btn-success"><i class="fas fa-file-csv me-2"></i> เพิ่มทีละหลายคน</a>
        <a href="student_add.php" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i> เพิ่มทีละคน</a>
    </div>
</div>
            
            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-header">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-10"> <label for="search" class="visually-hidden">ค้นหา</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="ค้นหา รหัสนักเรียน, Username, ชื่อ..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2 d-grid"> <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> ค้นหา</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php
                                    // ฟังก์ชัน sort_link ให้เก็บค่า search ไปด้วย
                                    function sort_link($column, $display_name, $current_sort, $current_order) {
                                        $order = ($column == $current_sort && $current_order == 'ASC') ? 'desc' : 'asc';
                                        $icon = ($column == $current_sort) ? ($current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : '';
                                        
                                        $query_params = [
                                            'sort' => $column,
                                            'order' => $order,
                                            'search' => $_GET['search'] ?? ''
                                        ];
                                        $link = http_build_query(array_filter($query_params));
                                        
                                        return "<a href=\"?$link\" class='text-decoration-none text-dark'>$display_name$icon</a>";
                                    }
                                    ?>
                                    <th>#</th>
                                    <th><?php echo sort_link('student_code', 'รหัสนักเรียน', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('username', 'Username', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('first_name', 'ชื่อ-นามสกุล', $sort_column, $sort_order); ?></th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="5" class="text-center py-3">ไม่พบข้อมูลนักเรียน<?php if(!empty($search_term)) echo " ตามเงื่อนไขที่กำหนด"; ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['prefix'] . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td class="text-end">
                                            <a href="student_edit.php?id=<?php echo $student['student_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                            <a href="student_list.php?delete_id=<?php echo $student['student_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบ?')"><i class="fas fa-trash-alt"></i></a>
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