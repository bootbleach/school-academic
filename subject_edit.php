<?php
session_start();
require_once 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี ID ส่งมาใน URL หรือไม่
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: subject_list.php");
    exit();
}
$subject_id = trim($_GET['id']);

// ดึงข้อมูลวิชาปัจจุบันเพื่อมาแสดงในฟอร์ม
$sql_fetch = "SELECT subject_code, subject_name, credits FROM subjects WHERE subject_id = ?";
if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $subject_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows == 1) {
        $subject = $result->fetch_assoc();
        $subject_code = $subject['subject_code'];
        $subject_name = $subject['subject_name'];
        $credits = $subject['credits'];
    } else {
        header("Location: subject_list.php");
        exit();
    }
    $stmt_fetch->close();
}

// กำหนดตัวแปรสำหรับ error
$subject_code_err = $subject_name_err = $credits_err = "";
$message = "";

// ประมวลผลเมื่อมีการส่งฟอร์มแก้ไข
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าใหม่จากฟอร์ม
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $credits = trim($_POST['credits']);
    
    // --- Validation ---
    if (empty($subject_code)) { $subject_code_err = "กรุณากรอกรหัสวิชา"; }
    else {
        // ตรวจสอบรหัสวิชาซ้ำ (ยกเว้นของตัวเอง)
        $sql_check = "SELECT subject_id FROM subjects WHERE subject_code = ? AND subject_id != ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("si", $subject_code, $subject_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $subject_code_err = "รหัสวิชานี้ถูกใช้งานแล้ว";
            }
            $stmt_check->close();
        }
    }
    if (empty($subject_name)) { $subject_name_err = "กรุณากรอกชื่อวิชา"; }
    if (trim($credits) === '') { $credits_err = "กรุณากรอกหน่วยกิต"; }
    
    // --- ถ้าไม่มีข้อผิดพลาด ---
    if (empty($subject_code_err) && empty($subject_name_err) && empty($credits_err)) {
        $sql_update = "UPDATE subjects SET subject_code = ?, subject_name = ?, credits = ? WHERE subject_id = ?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("ssdi", $subject_code, $subject_name, $credits, $subject_id);
            if ($stmt_update->execute()) {
                $_SESSION['message'] = '<div class="alert alert-success">แก้ไขข้อมูลวิชาสำเร็จ!</div>';
                header("location: subject_list.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก</div>';
            }
            $stmt_update->close();
        }
    } else {
         $message = '<div class="alert alert-danger">โปรดกรอกข้อมูลให้ถูกต้อง</div>';
    }
}

// --- กำหนด Title สำหรับหน้านี้ ---
$page_title = "แก้ไขข้อมูลวิชา"; 

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
                <a href="subject_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php if(!empty($message)) echo $message; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $subject_id); ?>" method="post" novalidate>
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
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
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