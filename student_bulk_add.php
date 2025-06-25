<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบสิทธิ์ Admin
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();

// --- ดึงข้อมูลคลาสทั้งหมดสำหรับ Dropdown ---
$classes = $mysqli->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_code ASC");

// --- จัดการการอัปโหลดและประมวลผลไฟล์ CSV ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["student_csv"])) {
    $class_id = $_POST['class_id'] ?? null;

    if (empty($class_id)) {
        set_session_message('กรุณาเลือกห้องเรียนที่ต้องการเพิ่มนักเรียน', 'danger');
    } elseif ($_FILES["student_csv"]["error"] > 0) {
        set_session_message('เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $_FILES["student_csv"]["error"], 'danger');
    } else {
        $file_name = $_FILES["student_csv"]["tmp_name"];
        
        // ตรวจสอบว่าเป็นไฟล์ CSV จริงๆ
        $file_type = mime_content_type($file_name);
        if ($file_type !== 'text/plain' && $file_type !== 'text/csv') {
             set_session_message('รูปแบบไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์ .csv เท่านั้น', 'danger');
        } else {
            $mysqli->begin_transaction(); // << เริ่ม Transaction
            try {
                $handle = fopen($file_name, "r");
                $is_header = true;
                $row_number = 1;
                $success_count = 0;

                // เตรียมคำสั่ง SQL แค่ครั้งเดียวเพื่อประสิทธิภาพ
                $sql = "INSERT INTO students (student_code, username, password, prefix, first_name, last_name, id_card_number, class_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($is_header) { $is_header = false; continue; } // ข้ามบรรทัดแรก (Header)
                    
                    $row_number++;
                    if (count($data) < 7) {
                        throw new Exception("ข้อมูลในแถวที่ $row_number ไม่ครบถ้วน");
                    }

                    $student_code = $data[0];
                    $username = $data[1];
                    $password = $data[2];
                    $prefix = $data[3];
                    $first_name = $data[4];
                    $last_name = $data[5];
                    $id_card_number = $data[6];
                    
                    // Validation ในแต่ละแถว
                    if (empty($student_code) || empty($username) || empty($password) || empty($first_name)) {
                        throw new Exception("ข้อมูลที่จำเป็น (รหัสนักเรียน, username, password, ชื่อจริง) ในแถวที่ $row_number เป็นค่าว่าง");
                    }

                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt->bind_param("sssssssi", $student_code, $username, $hashed_password, $prefix, $first_name, $last_name, $id_card_number, $class_id);
                    if (!$stmt->execute()) {
                        throw new Exception("ไม่สามารถบันทึกข้อมูลแถวที่ $row_number ได้: " . $stmt->error);
                    }
                    $success_count++;
                }
                
                fclose($handle);
                $stmt->close();

                $mysqli->commit(); // << ยืนยันการบันทึกทั้งหมดเมื่อไม่มี Error
                set_session_message("นำเข้าข้อมูลนักเรียนสำเร็จ $success_count รายการ", 'success');

            } catch (Exception $e) {
                $mysqli->rollback(); // << ยกเลิกการบันทึกทั้งหมดหากมีข้อผิดพลาด
                set_session_message("เกิดข้อผิดพลาด: " . $e->getMessage(), 'danger');
            }
        }
        header("Location: student_bulk_add.php");
        exit();
    }
}

$page_title = "เพิ่มนักเรียนทีละหลายคน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-file-csv me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการ</a>
            </div>

            <?php echo get_session_message(); ?>

            <div class="card">
                <div class="card-header">
                    <h5>ขั้นตอนการนำเข้าข้อมูล</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>เตรียมข้อมูลใน Excel หรือ Google Sheets โดยมีหัวข้อคอลัมน์และเรียงตามลำดับดังนี้: <br>
                            <code class="user-select-all">student_code,username,password,prefix,first_name,last_name,id_card_number</code>
                        </li>
                        <li>บันทึกไฟล์เป็นชนิด **CSV UTF-8 (Comma delimited) (.csv)**</li>
                        <li>เลือกห้องเรียนที่ต้องการด้านล่าง และอัปโหลดไฟล์ที่เตรียมไว้</li>
                    </ol>
                    <hr>
                    <form action="student_bulk_add.php" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="class_id" class="form-label">1. เลือกห้องเรียนที่จะเพิ่มนักเรียน <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-select" required>
                                    <option value="">-- กรุณาเลือกห้องเรียน --</option>
                                    <?php foreach($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="student_csv" class="form-label">2. เลือกไฟล์ CSV ที่ต้องการอัปโหลด <span class="text-danger">*</span></label>
                                <input type="file" name="student_csv" id="student_csv" class="form-control" accept=".csv" required>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-upload me-2"></i>เริ่มนำเข้าข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>