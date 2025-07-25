<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$classes = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");

// กำหนดค่าเริ่มต้น
$student_code = $username = $password = $prefix = $first_name = $last_name = $id_card_number = $class_id = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $student_code = isset($_POST['student_code']) ? trim($_POST['student_code']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $id_card_number = isset($_POST['id_card_number']) ? trim($_POST['id_card_number']) : '';
    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;

    // --- Validation ---
    if (empty($student_code)) $errors['student_code'] = "กรุณากรอกรหัสนักเรียน";
    elseif (!is_value_unique('students', 'student_code', $student_code, null, 'student_id')) $errors['student_code'] = "รหัสนักเรียนนี้ถูกใช้งานแล้ว";

    if (empty($id_card_number)) $errors['id_card_number'] = "กรุณากรอกเลขประจำตัวประชาชน";
    elseif (!is_value_unique('students', 'id_card_number', $id_card_number, null, 'student_id')) $errors['id_card_number'] = "เลขประจำตัวประชาชนนี้ถูกใช้งานแล้ว";

    if (empty($username)) $errors['username'] = "กรุณากรอก Username";
    elseif (!is_value_unique('students', 'username', $username, null, 'student_id')) $errors['username'] = "Username นี้ถูกใช้งานแล้ว";
    
    if (empty($password)) $errors['password'] = "กรุณากรอกรหัสผ่าน";
    if (empty($first_name)) $errors['first_name'] = "กรุณากรอกชื่อจริง";
    
    // Retrieve the current academic year ID
    $current_academic_year_id = null;
    $academic_year_query = $mysqli->query("SELECT acad_year_id FROM academic_years WHERE is_current_year = 1 LIMIT 1");
    if ($academic_year_query && $academic_year_row = $academic_year_query->fetch_assoc()) {
        $current_academic_year_id = $academic_year_row['acad_year_id'];
    } else {
        $errors['academic_year'] = "ไม่พบปีการศึกษาปัจจุบัน. กรุณากำหนดปีการศึกษาปัจจุบัน.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // SQL for students table (without class_id)
        $sql_student = "INSERT INTO students (student_code, username, password, prefix, first_name, last_name, id_card_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt_student = $mysqli->prepare($sql_student)) {
            $stmt_student->bind_param("sssssss", $student_code, $username, $hashed_password, $prefix, $first_name, $last_name, $id_card_number);
            
            if ($stmt_student->execute()) {
                $new_student_id = $mysqli->insert_id; // Get the ID of the newly inserted student
                
                // If class_id is provided and current academic year is found, insert into student_classes
                if ($class_id !== null && $current_academic_year_id !== null) {
                    $enrollment_date = date('Y-m-d'); // Current date for enrollment
                    $status = 'Active'; // Default status
                    
                    $sql_student_class = "INSERT INTO student_classes (student_id, class_id, academic_year_id, enrollment_date, status) VALUES (?, ?, ?, ?, ?)";
                    
                    if ($stmt_student_class = $mysqli->prepare($sql_student_class)) {
                        $stmt_student_class->bind_param("iisss", $new_student_id, $class_id, $current_academic_year_id, $enrollment_date, $status);
                        if ($stmt_student_class->execute()) {
                            set_session_message('เพิ่มข้อมูลนักเรียนและกำหนดห้องเรียนสำเร็จ!', 'success');
                            header("location: student_list.php");
                            exit();
                        } else {
                            set_session_message('เพิ่มข้อมูลนักเรียนสำเร็จ แต่เกิดข้อผิดพลาดในการกำหนดห้องเรียน: '.$stmt_student_class->error, 'warning');
                            header("location: student_list.php");
                            exit();
                        }
                        $stmt_student_class->close();
                    } else {
                        set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่งกำหนดห้องเรียน: '.$mysqli->error, 'danger');
                        header("location: student_list.php");
                        exit();
                    }
                } else {
                    set_session_message('เพิ่มข้อมูลนักเรียนสำเร็จ! (ไม่ได้กำหนดห้องเรียน หรือไม่พบปีการศึกษาปัจจุบัน)', 'success');
                    header("location: student_list.php");
                    exit();
                }
            } else {
                set_session_message('เกิดข้อผิดพลาดในการบันทึกข้อมูลนักเรียน: '.$stmt_student->error, 'danger');
            }
            $stmt_student->close();
        } else {
            set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่งบันทึกข้อมูลนักเรียน: '.$mysqli->error, 'danger');
        }
    } else {
        set_session_message('โปรดตรวจสอบข้อมูลในฟอร์มให้ถูกต้อง', 'warning');
    }
}

$page_title = "เพิ่มข้อมูลนักเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-user-plus me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_list.php" class="btn btn-secondary ms-auto"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>
            <?php echo get_session_message(); ?>
            <div class="card">
                <div class="card-body p-4">
                    <form action="student_add.php" method="post" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="student_code" class="form-label">รหัสนักเรียน <span class="text-danger">*</span></label><input type="text" name="student_code" id="student_code" class="form-control <?php echo isset($errors['student_code']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($student_code); ?>" required><div class="invalid-feedback"><?php echo $errors['student_code'] ?? ''; ?></div></div>
                            <div class="col-md-6"><label for="id_card_number" class="form-label">เลขประจำตัวประชาชน <span class="text-danger">*</span></label><input type="text" name="id_card_number" id="id_card_number" class="form-control <?php echo isset($errors['id_card_number']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($id_card_number); ?>" maxlength="13" required><div class="invalid-feedback"><?php echo $errors['id_card_number'] ?? ''; ?></div></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="username" class="form-label">Username <span class="text-danger">*</span></label><input type="text" name="username" id="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required><div class="invalid-feedback"><?php echo $errors['username'] ?? ''; ?></div></div>
                            <div class="col-md-6"><label for="password" class="form-label">Password <span class="text-danger">*</span></label><input type="password" name="password" id="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" required><div class="invalid-feedback"><?php echo $errors['password'] ?? ''; ?></div></div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-2"><label class="form-label">คำนำหน้า <span class="text-danger">*</span></label><select name="prefix" class="form-select"><option value="ด.ช." <?php if($prefix == 'ด.ช.') echo 'selected'; ?>>ด.ช.</option><option value="ด.ญ." <?php if($prefix == 'ด.ญ.') echo 'selected'; ?>>ด.ญ.</option><option value="นาย" <?php if($prefix == 'นาย') echo 'selected'; ?>>นาย</option><option value="น.ส." <?php if($prefix == 'น.ส.') echo 'selected'; ?>>น.ส.</option></select></div>
                            <div class="col-md-5"><label class="form-label">ชื่อจริง <span class="text-danger">*</span></label><input type="text" name="first_name" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name); ?>" required><div class="invalid-feedback"><?php echo $errors['first_name'] ?? ''; ?></div></div>
                            <div class="col-md-5"><label class="form-label">นามสกุล</label><input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ห้องเรียน</label>
                            <select name="class_id" class="form-select"><option value="">-- ไม่กำหนดห้องเรียน --</option><?php foreach ($classes as $class): ?><option value="<?php echo $class['class_id']; ?>" <?php if($class_id == $class['class_id']) echo 'selected'; ?>><?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?></option><?php endforeach; ?></select>
                        </div>
                        <?php if (isset($errors['academic_year'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $errors['academic_year']; ?>
                            </div>
                        <?php endif; ?>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2"><a href="student_list.php" class="btn btn-secondary px-4">ยกเลิก</a><button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>บันทึก</button></div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once 'includes/admin_footer.php'; ?>