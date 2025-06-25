<?php
session_start();
require_once 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// กำหนดค่าเริ่มต้น
$subject_code = $subject_name = $credits = "";
$subject_code_err = $subject_name_err = $credits_err = "";
$message = "";

// ประมวลผลเมื่อมีการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- Validation ---
    // 1. ตรวจสอบรหัสวิชา
    if (empty(trim($_POST["subject_code"]))) {
        $subject_code_err = "กรุณากรอกรหัสวิชา";
    } else {
        // ตรวจสอบว่ารหัสวิชานี้มีอยู่แล้วหรือไม่
        $sql_check = "SELECT subject_id FROM subjects WHERE subject_code = ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("s", $param_code);
            $param_code = trim($_POST["subject_code"]);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $subject_code_err = "รหัสวิชานี้ถูกใช้งานแล้ว";
            } else {
                $subject_code = trim($_POST["subject_code"]);
            }
            $stmt_check->close();
        }
    }

    // 2. ตรวจสอบชื่อวิชา
    if (empty(trim($_POST["subject_name"]))) {
        $subject_name_err = "กรุณากรอกชื่อวิชา";
    } else {
        $subject_name = trim($_POST["subject_name"]);
    }

    // 3. ตรวจสอบหน่วยกิต
    if (trim($_POST["credits"]) === '') {
        $credits_err = "กรุณากรอกหน่วยกิต";
    } elseif (!is_numeric($_POST["credits"]) || $_POST["credits"] < 0) {
        $credits_err = "หน่วยกิตต้องเป็นตัวเลขและไม่ติดลบ";
    } else {
        $credits = trim($_POST["credits"]);
    }
    
    // --- ถ้าไม่มีข้อผิดพลาด ให้เพิ่มข้อมูลลงฐานข้อมูล ---
    if (empty($subject_code_err) && empty($subject_name_err) && empty($credits_err)) {
        
        $sql_insert = "INSERT INTO subjects (subject_code, subject_name, credits) VALUES (?, ?, ?)";
        
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssd", $subject_code, $subject_name, $credits);
            
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">เพิ่มข้อมูลวิชาใหม่สำเร็จ!</div>';
                header("location: subject_list.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger" role="alert">เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt_insert->error . '</div>';
            }
            $stmt_insert->close();
        }
    } else {
        $message = '<div class="alert alert-danger" role="alert">โปรดกรอกข้อมูลให้ถูกต้อง</div>';
    }
}

// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "เพิ่มวิชาใหม่";

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
                <h1 class="d-none d-lg-block"><i class="fas fa-plus-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="subject_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">รหัสวิชา <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" id="subject_code" class="form-control <?php echo (!empty($subject_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($subject_code); ?>" required>
                            <div class="invalid-feedback"><?php echo $subject_code_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">ชื่อวิชา <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" id="subject_name" class="form-control <?php echo (!empty($subject_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($subject_name); ?>" required>
                            <div class="invalid-feedback"><?php echo $subject_name_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="credits" class="form-label">หน่วยกิต <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" name="credits" id="credits" class="form-control <?php echo (!empty($credits_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($credits); ?>" required>
                            <div class="invalid-feedback"><?php echo $credits_err; ?></div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="subject_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึก</button>
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