<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบสิทธิ์ Admin
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี id นักเรียนส่งมาหรือไม่
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: student_list.php");
    exit();
}
$student_id = $_GET['id'];

// ดึงข้อมูลนักเรียนปัจจุบัน
$stmt_fetch = $mysqli->prepare("SELECT student_id, student_code, username, prefix, first_name, last_name, id_card_number FROM students WHERE student_id = ?");
$stmt_fetch->bind_param("i", $student_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
if ($result->num_rows === 0) {
    header("Location: student_list.php");
    exit();
}
$student = $result->fetch_assoc();
$stmt_fetch->close();

// กำหนดค่าเริ่มต้นสำหรับแสดงในฟอร์ม
$student_code = $student['student_code'];
$username = $student['username'];
$prefix = $student['prefix'];
$first_name = $student['first_name'];
$last_name = $student['last_name'];
$id_card_number = $student['id_card_number'];
$errors = [];

// ประมวลผลเมื่อมีการส่งฟอร์มแก้ไข
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_code = trim($_POST['student_code']);
    $username = trim($_POST['username']);
    $prefix = $_POST['prefix'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $id_card_number = trim($_POST['id_card_number']);
    $new_password = $_POST['new_password'];

    // Validation
    if (empty($student_code)) $errors['student_code'] = "กรุณากรอกรหัสนักเรียน";
    elseif (!is_value_unique('students', 'student_code', $student_code, $student_id, 'student_id')) $errors['student_code'] = "รหัสนักเรียนนี้ถูกใช้งานแล้ว";

    if (empty($id_card_number)) $errors['id_card_number'] = "กรุณากรอกเลขประจำตัวประชาชน";
    elseif (!is_value_unique('students', 'id_card_number', $id_card_number, $student_id, 'student_id')) $errors['id_card_number'] = "เลขประจำตัวประชาชนนี้ถูกใช้งานแล้ว";

    if (empty($username)) $errors['username'] = "กรุณากรอก Username";
    elseif (!is_value_unique('students', 'username', $username, $student_id, 'student_id')) $errors['username'] = "Username นี้ถูกใช้งานแล้ว";

    if (empty($first_name)) $errors['first_name'] = "กรุณากรอกชื่อจริง";

    if (empty($errors)) {
        // ปรับปรุงข้อมูลนักเรียนในตาราง students เท่านั้น
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE students SET student_code=?, username=?, password=?, prefix=?, first_name=?, last_name=?, id_card_number=? WHERE student_id=?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("sssssssi", $student_code, $username, $hashed_password, $prefix, $first_name, $last_name, $id_card_number, $student_id);
        } else {
            $sql_update = "UPDATE students SET student_code=?, username=?, prefix=?, first_name=?, last_name=?, id_card_number=? WHERE student_id=?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("ssssssi", $student_code, $username, $prefix, $first_name, $last_name, $id_card_number, $student_id);
        }

        if (isset($stmt) && $stmt->execute()) {
            set_session_message('แก้ไขข้อมูลนักเรียนสำเร็จ!', 'success');
            header("location: student_list.php");
            exit();
        } else {
            set_session_message('เกิดข้อผิดพลาดในการแก้ไขข้อมูลนักเรียน: ' . $mysqli->error, 'danger');
        }
    } else {
        set_session_message('โปรดตรวจสอบข้อมูลในฟอร์มให้ถูกต้อง', 'warning');
    }
}

$page_title = "แก้ไขข้อมูลนักเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-user-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php echo get_session_message(); ?>

            <div class="card">
                <div class="card-body p-4">
                    <form action="student_edit.php?id=<?php echo $student_id; ?>" method="post" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_code" class="form-label">รหัสนักเรียน <span class="text-danger">*</span></label>
                                <input type="text" name="student_code" id="student_code" class="form-control <?php echo isset($errors['student_code']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($student_code); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['student_code'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="id_card_number" class="form-label">เลขประจำตัวประชาชน <span class="text-danger">*</span></label>
                                <input type="text" name="id_card_number" id="id_card_number" class="form-control <?php echo isset($errors['id_card_number']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($id_card_number); ?>" maxlength="13" required>
                                <div class="invalid-feedback"><?php echo $errors['id_card_number'] ?? ''; ?></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['username'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password (กรอกหากต้องการเปลี่ยน)</label>
                                <input type="password" name="new_password" id="new_password" class="form-control">
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                <select name="prefix" class="form-select">
                                    <option value="ด.ช." <?php if ($prefix == 'ด.ช.') echo 'selected'; ?>>ด.ช.</option>
                                    <option value="ด.ญ." <?php if ($prefix == 'ด.ญ.') echo 'selected'; ?>>ด.ญ.</option>
                                    <option value="นาย" <?php if ($prefix == 'นาย') echo 'selected'; ?>>นาย</option>
                                    <option value="น.ส." <?php if ($prefix == 'น.ส.') echo 'selected'; ?>>น.ส.</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['first_name'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>">
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="student_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once 'includes/admin_footer.php'; ?>