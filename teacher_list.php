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

// --- ส่วนที่ 1: จัดการการค้นหาและการเรียงลำดับ (โค้ดเดิมดีอยู่แล้ว) ---
$search_term = $_GET['search'] ?? '';
$sort_column_whitelist = ['username', 'fullname', 'email', 'phone'];
$sort_column = $_GET['sort'] ?? 'username';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'username';
}

// --- ส่วนที่ 2: การจัดการลบข้อมูล (เหมือนเดิม) ---
if (isset($_GET['delete_id']) && !empty(trim($_GET['delete_id']))) {
    $delete_id = trim($_GET['delete_id']);
    $sql_delete = "DELETE FROM teachers WHERE teacher_id = ?";
    if ($stmt_delete = $mysqli->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            set_session_message('<div class="alert alert-success">ลบข้อมูลครูสำเร็จ!</div>');
        } else {
            set_session_message('<div class="alert alert-danger">เกิดข้อผิดพลาดในการลบข้อมูล</div>');
        }
        $stmt_delete->close();
    }
    header("Location: teacher_list.php"); // Redirect เพื่อล้างค่า GET
    exit();
}


// --- ส่วนที่ 3: แก้ไขการดึงข้อมูลให้รองรับ Search & Sort (โค้ดเดิมดีอยู่แล้ว) ---
$teachers = [];
$params = [];
$types = '';
$sql_fetch = "SELECT teacher_id, username, fullname, email, phone FROM teachers";

if (!empty($search_term)) {
    $sql_fetch .= " WHERE username LIKE ? OR fullname LIKE ? OR email LIKE ?";
    $like_term = "%{$search_term}%";
    $params = [$like_term, $like_term, $like_term];
    $types = 'sss';
}

if ($sort_column === 'username') {
    $sql_fetch .= " ORDER BY CAST(SUBSTRING(username, 2) AS UNSIGNED) $sort_order, username $sort_order";
} else {
    $sql_fetch .= " ORDER BY $sort_column $sort_order";
}

$stmt_fetch = $mysqli->prepare($sql_fetch);

if (!empty($search_term)) {
    $stmt_fetch->bind_param($types, ...$params);
}

$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}
$stmt_fetch->close();


$page_title = "จัดการข้อมูลครู";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php'; // โค้ดเรียก sidebar ถูกต้องแล้ว
?>

<div class="content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="navbar-brand mb-0 ms-2"><?php echo htmlspecialchars($page_title); ?></h5>
        </div>
    </nav>

    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
                <h1 class="d-none d-lg-block mb-3 mb-md-0"><i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="teacher_add.php" class="btn btn-primary ms-auto w-100 w-md-auto"><i class="fas fa-plus-circle me-2"></i> เพิ่มข้อมูลครู</a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-header bg-light">
                    <form method="get" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="ค้นหา..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn btn-info flex-shrink-0" title="ค้นหา"><i class="fas fa-search"></i></button>
                        <a href="teacher_list.php" class="btn btn-secondary flex-shrink-0" title="ล้างการค้นหา"><i class="fas fa-times"></i></a>
                    </form>
                </div>
                <div class="card-body p-0"> <div class="table-responsive">
                        <table class="table table-hover table-vcenter mb-0"> <thead class="table-light">
                                <tr>
                                    <?php
                                    function sort_link($column, $display_name, $current_sort, $current_order) {
                                        $order = ($column == $current_sort && $current_order == 'ASC') ? 'desc' : 'asc';
                                        $icon = '';
                                        if ($column == $current_sort) {
                                            $icon = $current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
                                        }
                                        $search_param = !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                                        return "<a href=\"?sort=$column&order=$order$search_param\" class='text-decoration-none text-dark'>$display_name$icon</a>";
                                    }
                                    ?>
                                    <th class="text-center">#</th>
                                    <th><?php echo sort_link('username', 'Username', $sort_column, $sort_order); ?></th>
                                    <th class="text-nowrap"><?php echo sort_link('fullname', 'ชื่อ-นามสกุล', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('email', 'อีเมล', $sort_column, $sort_order); ?></th>
                                    <th class="text-nowrap"><?php echo sort_link('phone', 'เบอร์โทรศัพท์', $sort_column, $sort_order); ?></th>
                                    <th class="text-end text-nowrap">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teachers)): ?>
                                    <tr><td colspan="6" class="text-center py-4">ไม่พบข้อมูลครู<?php if(!empty($search_term)) echo " ที่ตรงกับคำค้นหา"; ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($teachers as $index => $teacher): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($teacher['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-2">
                                                    <a href="teacher_edit.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                                    <a href="teacher_list.php?delete_id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลครูท่านนี้?')"><i class="fas fa-trash-alt"></i></a>
                                                </div>
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