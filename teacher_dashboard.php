<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_teacher_loggedin()) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "แดชบอร์ดครู";
$message = get_session_message();

// ดึงข้อมูลครู
$stmt = $mysqli->prepare("SELECT username, fullname, email, phone FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once 'includes/teacher_header.php';
require_once 'includes/teacher_sidebar.php';
?>

<!-- เริ่มเนื้อหาหลัก -->
<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">

            <!-- ส่วนหัวต้อนรับ -->
            <div class="p-4 mb-4 rounded text-white" style="background: linear-gradient(135deg, #16a085, #1abc9c);">
                <h3>ยินดีต้อนรับ, ครู<?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h3>
                <p class="mb-0">แดชบอร์ดระบบจัดการข้อมูลสำหรับครูผู้สอน</p>
            </div>

            <?php echo $message; ?>

            <!-- การ์ดข้อมูลครู -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว</h5>
                    <a href="teacher_profile_edit.php" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-user-edit me-1"></i> แก้ไข
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>ชื่อผู้ใช้ (Username):</strong><br>
                            <span><?php echo htmlspecialchars($teacher['username']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>ชื่อ-สกุล:</strong><br>
                            <span><?php echo htmlspecialchars($teacher['fullname']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>อีเมล:</strong><br>
                            <span><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>เบอร์โทร:</strong><br>
                            <span><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- JS toggle sidebar -->
<script>
    document.getElementById("sidebarToggle").addEventListener("click", function () {
        document.getElementById("teacherSidebar").classList.toggle("toggled");
    });
</script>

<?php require_once 'includes/teacher_footer.php'; ?>
