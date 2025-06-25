<?php
session_start();
require_once 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี ID ส่งมาใน URL หรือไม่
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: teacher_list.php");
    exit();
}
$teacher_id = trim($_GET['id']);

// ดึงข้อมูลครูปัจจุบัน
$sql_fetch = "SELECT username, fullname, email, phone FROM teachers WHERE teacher_id = ?";
if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $teacher_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows == 1) {
        $teacher = $result->fetch_assoc();
    } else {
        header("Location: teacher_list.php");
        exit();
    }
    $stmt_fetch->close();
}

$username = $teacher['username'];
$fullname = $teacher['fullname'];
$email = $teacher['email'];
$phone = $teacher['phone'];

$username_err = $fullname_err = "";
$message = "";

// ประมวลผลเมื่อมีการส่งฟอร์มแก้ไข
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_teacher'])) {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password_new = $_POST['password_new'];

    // --- Validation ---
    if (empty($username)) {
        $username_err = "กรุณากรอก Username";
    } else {
        // ตรวจสอบ Username ซ้ำ (ยกเว้นของตัวเอง)
        $sql_check = "SELECT teacher_id FROM teachers WHERE username = ? AND teacher_id != ?";
        if($stmt_check = $mysqli->prepare($sql_check)){
            $stmt_check->bind_param("si", $username, $teacher_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if($stmt_check->num_rows > 0){
                $username_err = "Username นี้ถูกใช้งานแล้ว";
            }
            $stmt_check->close();
        }
    }
    if (empty($fullname)) { $fullname_err = "กรุณากรอกชื่อ-นามสกุล"; }

    // --- ถ้าไม่มี Error ---
    if (empty($username_err) && empty($fullname_err)) {
        if (!empty($password_new)) { // ถ้ามีการกรอกรหัสผ่านใหม่
            $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
            $sql_update = "UPDATE teachers SET username = ?, password = ?, fullname = ?, email = ?, phone = ? WHERE teacher_id = ?";
            if ($stmt_update = $mysqli->prepare($sql_update)) {
                $stmt_update->bind_param("sssssi", $username, $hashed_password, $fullname, $email, $phone, $teacher_id);
            }
        } else { // ถ้าไม่ได้กรอกรหัสผ่านใหม่
            $sql_update = "UPDATE teachers SET username = ?, fullname = ?, email = ?, phone = ? WHERE teacher_id = ?";
            if ($stmt_update = $mysqli->prepare($sql_update)) {
                $stmt_update->bind_param("ssssi", $username, $fullname, $email, $phone, $teacher_id);
            }
        }

        if (isset($stmt_update) && $stmt_update->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success">แก้ไขข้อมูลครูสำเร็จ!</div>';
            header("location: teacher_list.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการแก้ไขข้อมูล.</div>';
        }
        if(isset($stmt_update)) $stmt_update->close();
    }
}

// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "แก้ไขข้อมูลครู";

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
                <a href="teacher_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $teacher_id); ?>" method="post">
                        <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_new" class="form-label">รหัสผ่านใหม่ (กรอกหากต้องการเปลี่ยน)</label>
                                <input type="password" name="password_new" id="password_new" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" id="fullname" class="form-control <?php echo (!empty($fullname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fullname); ?>" required>
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

                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="teacher_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                            <button type="submit" name="update_teacher" class="btn btn-primary px-4">
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