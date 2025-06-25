<?php
session_start();
require_once 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// กำหนดค่าเริ่มต้น
$username = $fullname = $email = $phone = "";
$username_err = $password_err = $fullname_err = "";
$message = "";

// ประมวลผลเมื่อมีการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    
    // ตรวจสอบและรับค่าจากฟอร์ม
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);

    // --- Validation ---
    if (empty($username)) {
        $username_err = "กรุณากรอก Username";
    } else {
        // ตรวจสอบ Username ซ้ำ
        $sql_check = "SELECT teacher_id FROM teachers WHERE username = ?";
        if($stmt_check = $mysqli->prepare($sql_check)){
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();
            if($stmt_check->num_rows > 0){
                $username_err = "Username นี้ถูกใช้งานแล้ว";
            }
            $stmt_check->close();
        }
    }

    if (empty($password)) { $password_err = "กรุณากรอกรหัสผ่าน"; }
    if (empty($fullname)) { $fullname_err = "กรุณากรอกชื่อ-นามสกุล"; }

    // --- ถ้าไม่มี Error ---
    if (empty($username_err) && empty($password_err) && empty($fullname_err)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO teachers (username, password, fullname, email, phone) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $email, $phone);
            
            if ($stmt->execute()) {
                // **ปรับปรุงใหม่:** ใช้ session message แล้ว redirect
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">เพิ่มข้อมูลครูใหม่สำเร็จ!</div>';
                header("location: teacher_list.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger" role="alert">เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
}

// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "เพิ่มข้อมูลครู";

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
                <h1 class="d-none d-lg-block"><i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="teacher_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
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
                            <button type="submit" name="add_teacher" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>บันทึกข้อมูล
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