<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าล็อกอินแล้ว และมีบทบาทเป็น Admin เท่านั้น
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ---- ส่วน PHP สำหรับจัดการข้อมูลของหน้านี้ (คงไว้เหมือนเดิม) ----
$message = "";
if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// 3. ลบ Admin
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $admin_id = $_GET['delete_id'];
    
    // ป้องกันการลบ Admin ที่กำลังล็อกอินอยู่
    if ($admin_id == $_SESSION['admin_id']) {
        $_SESSION['message'] = '<div class="alert alert-danger">ไม่สามารถลบผู้ใช้ที่กำลังล็อกอินอยู่ได้!</div>';
    } else {
        $sql = "DELETE FROM admins WHERE admin_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $admin_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = '<div class="alert alert-success">ลบข้อมูล Admin สำเร็จ!</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการลบ: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
    header("Location: admin_list.php");
    exit();
}

// 4. ดึงข้อมูล Admin ทั้งหมดเพื่อแสดง
$admins = [];
$sql_fetch = "SELECT admin_id, username, fullname, email FROM admins ORDER BY admin_id ASC";
if ($result = $mysqli->query($sql_fetch)) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $result->free();
} else {
    $message = '<div class="alert alert-danger">ไม่สามารถดึงข้อมูล Admin ได้: ' . $mysqli->error . '</div>';
}
// ---------------- จบส่วน PHP ของหน้านี้ --------------------


// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "จัดการข้อมูล Admin";

// --- เรียกใช้ Header ---
require_once 'includes/admin_header.php';

// --- เรียกใช้ Sidebar ---
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-light" type="button" id="sidebarToggleMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="navbar-brand mb-0 ms-2"><?php echo htmlspecialchars($page_title); ?></h5>
        </div>
    </nav>
    
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
                <h1 class="d-none d-lg-block mb-3 mb-md-0"><i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="admin_add.php" class="btn btn-primary w-100 w-md-auto"><i class="fas fa-plus-circle me-2"></i> เพิ่ม Admin ใหม่</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter"> <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-nowrap">Username</th>
                                    <th class="text-nowrap">ชื่อ-นามสกุล</th>
                                    <th>อีเมล</th>
                                    <th class="text-end text-nowrap">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admins)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">ไม่พบข้อมูล Admin</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($admins as $index => $admin): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($admin['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-2">
                                                    <a href="admin_edit.php?id=<?php echo $admin['admin_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                                    <a href="admin_list.php?delete_id=<?php echo $admin['admin_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Admin นี้?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                    <?php endif; ?>
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
<?php
// --- เรียกใช้ Footer ---
require_once 'includes/admin_footer.php';
?>