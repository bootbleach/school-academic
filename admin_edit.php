<?php
session_start();
require_once 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- ส่วน PHP ของหน้านี้ (คงไว้เหมือนเดิม) ---
$admin_id = $username = $fullname = $email = "";
$username_err = $password_err = $fullname_err = "";
$message = "";

// 1. ดึงข้อมูล Admin มาแสดงในฟอร์ม (เมื่อเข้ามาครั้งแรก)
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $admin_id = trim($_GET['id']);

    $sql = "SELECT username, fullname, email FROM admins WHERE admin_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $admin_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $admin_data = $result->fetch_assoc();
                $username = $admin_data['username'];
                $fullname = $admin_data['fullname'];
                $email = $admin_data['email'];
            } else {
                header("Location: admin_list.php");
                exit();
            }
        } else {
            $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูล.</div>';
        }
        $stmt->close();
    }
} else {
    header("Location: admin_list.php");
    exit();
}

// 2. ประมวลผลเมื่อมีการส่งฟอร์มแก้ไข
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin'])) {
    $admin_id_post = $_POST['admin_id'];
    $username_post = trim($_POST['username']);
    $fullname_post = trim($_POST['fullname']);
    $email_post = trim($_POST['email']);
    $password_new = $_POST['password_new'];

    if (empty($username_post)) { $username_err = "กรุณากรอก Username."; }
    if (empty($fullname_post)) { $fullname_err = "กรุณากรอกชื่อ-นามสกุล."; }
    // (ควรเพิ่มการตรวจสอบ username ซ้ำที่นี่)

    if (empty($username_err) && empty($fullname_err)) {
        if (!empty($password_new)) { // ถ้ามีการกรอกรหัสผ่านใหม่
            $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
            $sql_update = "UPDATE admins SET username = ?, password = ?, fullname = ?, email = ? WHERE admin_id = ?";
            if ($stmt_update = $mysqli->prepare($sql_update)) {
                $stmt_update->bind_param("ssssi", $username_post, $hashed_password, $fullname_post, $email_post, $admin_id_post);
            }
        } else { // ถ้าไม่ได้กรอกรหัสผ่านใหม่
            $sql_update = "UPDATE admins SET username = ?, fullname = ?, email = ? WHERE admin_id = ?";
            if ($stmt_update = $mysqli->prepare($sql_update)) {
                $stmt_update->bind_param("sssi", $username_post, $fullname_post, $email_post, $admin_id_post);
            }
        }

        if (isset($stmt_update) && $stmt_update->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success">แก้ไขข้อมูล Admin สำเร็จ!</div>';
            header("location: admin_list.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการแก้ไขข้อมูล.</div>';
        }
        if(isset($stmt_update)) $stmt_update->close();
    }
}
// ---------------- จบส่วน PHP ของหน้านี้ --------------------

// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "แก้ไขข้อมูล Admin";

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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="admin_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $admin_id); ?>" method="post">
                        <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin_id); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" id="fullname" class="form-control <?php echo (!empty($fullname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fullname); ?>" required>
                            <div class="invalid-feedback"><?php echo $fullname_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password_new" class="form-label">รหัสผ่านใหม่ (กรอกหากต้องการเปลี่ยน)</label>
                            <input type="password" name="password_new" id="password_new" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="admin_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                            <button type="submit" name="update_admin" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php
// --- เรียกใช้ Footer ---
require_once 'includes/admin_footer.php';
?>