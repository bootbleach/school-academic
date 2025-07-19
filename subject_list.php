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

// --- ส่วนที่ 1: จัดการการค้นหาและการเรียงลำดับ ---
$search_term = $_GET['search'] ?? '';

$sort_column_whitelist = ['subject_code', 'subject_name', 'credits'];
$sort_column = $_GET['sort'] ?? 'subject_code';
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

if (!in_array($sort_column, $sort_column_whitelist)) {
    $sort_column = 'subject_code';
}

// --- ส่วนที่ 2: การจัดการลบข้อมูล ---
if (isset($_GET['delete_id']) && !empty(trim($_GET['delete_id']))) {
    $subject_id_to_delete = trim($_GET['delete_id']);
    $sql_del = "DELETE FROM subjects WHERE subject_id = ?";
    if ($stmt_del = $mysqli->prepare($sql_del)) {
        $stmt_del->bind_param("i", $subject_id_to_delete);
        if ($stmt_del->execute()) {
            set_session_message('ลบข้อมูลวิชาสำเร็จ!', 'success');
        } else {
            if ($stmt_del->errno == 1451) {
                set_session_message('ไม่สามารถลบได้ เพราะมีการใช้งานในคลาสเรียนแล้ว', 'danger');
            } else {
                set_session_message('เกิดข้อผิดพลาดในการลบ', 'danger');
            }
        }
        $stmt_del->close();
    }
    header("Location: subject_list.php");
    exit();
}

// --- ส่วนที่ 3: แก้ไขการดึงข้อมูลให้รองรับ Search & Sort ---
$subjects = [];
$params = [];
$types = '';

$sql_fetch = "SELECT subject_id, subject_code, subject_name, credits FROM subjects";

// เพิ่มเงื่อนไขการค้นหาถ้ามี
if (!empty($search_term)) {
    $sql_fetch .= " WHERE subject_code LIKE ? OR subject_name LIKE ?";
    $like_term = "%{$search_term}%";
    $params = [$like_term, $like_term];
    $types = 'ss';
}

// เพิ่มการเรียงลำดับ
$sql_fetch .= " ORDER BY $sort_column $sort_order";

$stmt_fetch = $mysqli->prepare($sql_fetch);
if (!empty($search_term)) {
    $stmt_fetch->bind_param($types, ...$params);
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt_fetch->close();


$page_title = "จัดการข้อมูลวิชา";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="subject_add.php" class="btn btn-primary ms-auto"><i class="fas fa-plus-circle me-2"></i> เพิ่มวิชาใหม่</a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-header">
                    <form method="get" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="ค้นหารหัสวิชา, ชื่อวิชา..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn btn-info"><i class="fas fa-search"></i></button>
                        <a href="subject_list.php" class="btn btn-secondary ms-2" title="ล้างการค้นหา"><i class="fas fa-times"></i></a>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php
                                    function sort_link($column, $display_name, $current_sort, $current_order)
                                    {
                                        $order = ($column == $current_sort && $current_order == 'ASC') ? 'desc' : 'asc';
                                        $icon = ($column == $current_sort) ? ($current_order == 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : '';
                                        $search_param = !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                                        return "<a href=\"?sort=$column&order=$order$search_param\" class='text-decoration-none text-dark'>$display_name$icon</a>";
                                    }
                                    ?>
                                    <th>#</th>
                                    <th><?php echo sort_link('subject_code', 'รหัสวิชา', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('subject_name', 'ชื่อวิชา', $sort_column, $sort_order); ?></th>
                                    <th><?php echo sort_link('credits', 'หน่วยกิต', $sort_column, $sort_order); ?></th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">ไม่พบข้อมูลวิชา<?php if (!empty($search_term)) echo " ที่ตรงกับคำค้นหา"; ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $index => $subject): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['credits']); ?></td>
                                            <td class="text-end">
                                                <a href="subject_edit.php?id=<?php echo $subject['subject_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                                <a href="subject_list.php?delete_id=<?php echo $subject['subject_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบ?')"><i class="fas fa-trash-alt"></i></a>
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