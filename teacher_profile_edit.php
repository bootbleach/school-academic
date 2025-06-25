<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบว่าครูล็อกอินอยู่หรือไม่
if (!isset($_SESSION['teacher_loggedin']) || $_SESSION['teacher_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ดึง ID ของครูที่ล็อกอินอยู่จาก Session
$teacher_id = $_SESSION['teacher_id'];

// ดึงข้อมูลปัจจุบันมาแสดง
$stmt_fetch = $mysqli->prepare("SELECT username, fullname, email, phone FROM teachers WHERE teacher_id = ?");
$stmt_fetch->bind_param("i", $teacher_id);
$stmt_fetch->execute();
$teacher = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

// กำหนดค่าเริ่มต้นสำหรับฟอร์ม
$fullname = $teacher['fullname'];
$email = $teacher['email'];
$phone = $teacher['phone'];
$fullname_err = "";

// จัดการการบันทึกข้อมูล
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];

    if (empty($fullname)) {
        $fullname_err = "กรุณากรอกชื่อ-นามสกุล";
    }

    if (empty($fullname_err)) {
        if (!empty($new_password)) {
            // อัปเดตพร้อมรหัสผ่านใหม่
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE teachers SET password=?, fullname=?, email=?, phone=? WHERE teacher_id=?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("ssssi", $hashed_password, $fullname, $email, $phone, $teacher_id);
        } else {
            // อัปเดตโดยไม่เปลี่ยนรหัสผ่าน
            $sql_update = "UPDATE teachers SET fullname=?, email=?, phone=? WHERE teacher_id=?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("sssi", $fullname, $email, $phone, $teacher_id);
        }

        if (isset($stmt) && $stmt->execute()) {
            // อัปเดตชื่อใน Session ด้วย
            $_SESSION['fullname'] = $fullname;
            set_session_message('แก้ไขข้อมูลส่วนตัวสำเร็จ!', 'success');
            header("location: teacher_dashboard.php");
            exit();
        } else {
            set_session_message('เกิดข้อผิดพลาดในการแก้ไขข้อมูล', 'danger');
        }
    }
}

$page_title = "แก้ไขข้อมูลส่วนตัว";
require_once 'includes/teacher_header.php';
require_once 'includes/teacher_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-user-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="teacher_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก</a>
            </div>

            <?php echo get_session_message(); ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="teacher_profile_edit.php" method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['username']); ?>" disabled readonly>
                            <small class="form-text text-muted">ไม่สามารถแก้ไข Username ได้</small>
                        </div>
                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" id="fullname" class="form-control <?php echo !empty($fullname_err) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fullname); ?>" required>
                             <div class="invalid-feedback"><?php echo $fullname_err; ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">อีเมล</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่ (กรอกหากต้องการเปลี่ยน)</label>
                            <input type="password" name="new_password" id="new_password" class="form-control">
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                             <a href="teacher_dashboard.php" class="btn btn-secondary px-4">ยกเลิก</a>
                             <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/teacher_footer.php'; ?>