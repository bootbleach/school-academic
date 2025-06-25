<?php
session_start();
require_once 'config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

// --- ดึงข้อมูลสำหรับ Dropdowns ---
$subjects = $mysqli->query("SELECT subject_id, subject_name, subject_code FROM subjects ORDER BY subject_code ASC");
$teachers = $mysqli->query("SELECT teacher_id, fullname FROM teachers ORDER BY fullname ASC");
$academic_years = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");

// --- กำหนดค่าเริ่มต้น ---
$teacher_id = $academic_year_id = $class_code = $class_name = $room_number = $max_students = '';
$errors = [];

// --- จัดการการ POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $teacher_id = $_POST['teacher_id'] ?? '';
    $academic_year_id = $_POST['academic_year_id'] ?? '';
    $class_code = trim($_POST['class_code'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');
    $room_number = trim($_POST['room_number'] ?? '');
    $max_students = !empty($_POST['max_students']) ? trim($_POST['max_students']) : null;
    $schedule_data = $_POST['schedule'] ?? [];

    // *** [เพิ่ม] รับข้อมูลครูผู้สอนในตาราง ***
    $schedule_teacher_data = $_POST['schedule_teacher'] ?? [];

    // Validation ข้อมูลหลัก
    if (empty($class_code)) $errors['class_code'] = 'กรุณากรอกรหัสคลาส';
    elseif (!is_value_unique('classes', 'class_code', $class_code, null, 'class_id')) $errors['class_code'] = 'รหัสคลาสนี้มีอยู่แล้ว';
    if (empty($class_name)) $errors['class_name'] = 'กรุณากรอกชื่อคลาส';
    if (empty($teacher_id)) $errors['teacher_id'] = 'กรุณาเลือกครูประจำชั้น';
    if (empty($academic_year_id)) $errors['academic_year_id'] = 'กรุณาเลือกปีการศึกษา';

    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            // บันทึกข้อมูลคลาสหลัก
            $sql_class = "INSERT INTO classes (teacher_id, academic_year_id, class_code, class_name, room_number, max_students) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_class = $mysqli->prepare($sql_class);
            $stmt_class->bind_param("iisssi", $teacher_id, $academic_year_id, $class_code, $class_name, $room_number, $max_students);
            $stmt_class->execute();
            
            $new_class_id = $mysqli->insert_id;
            if ($new_class_id == 0) throw new Exception("ไม่สามารถสร้างคลาสได้");

            // บันทึกตารางสอน
            if (!empty($schedule_data)) {
                // *** [แก้ไข] เพิ่ม teacher_id ใน SQL ***
                $sql_schedule = "INSERT INTO class_schedules (class_id, day_of_week, period, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?)";
                $stmt_schedule = $mysqli->prepare($sql_schedule);
                
                foreach ($schedule_data as $day => $periods) {
                    foreach ($periods as $period => $subject_id) {
                        if (!empty($subject_id)) {
                            // *** [เพิ่ม] ดึง teacher_id ของคาบนั้นๆ ***
                            $period_teacher_id = $schedule_teacher_data[$day][$period] ?? null;
                            if (empty($period_teacher_id)) {
                                $period_teacher_id = null; // ถ้าไม่เลือก ให้เป็น NULL
                            }

                            // *** [แก้ไข] เพิ่ม type 'i' สำหรับ teacher_id ***
                            $stmt_schedule->bind_param("iiiii", $new_class_id, $day, $period, $subject_id, $period_teacher_id);
                            $stmt_schedule->execute();
                        }
                    }
                }
                $stmt_schedule->close();
            }
            
            $mysqli->commit();
            set_session_message('สร้างคลาสเรียนและตารางสอนสำเร็จ!', 'success');
            header("Location: class_list.php");
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            set_session_message('เกิดข้อผิดพลาด: ' . $e->getMessage(), 'danger');
        }
    } else {
        set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
    }
}

$page_title = "สร้างคลาสเรียนใหม่";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>
<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-plus-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>
            
            <?php echo get_session_message(); ?>
            
            <form action="class_add.php" method="post" novalidate>
                <div class="card mb-4">
                    <div class="card-header"><h5>ข้อมูลหลักของคลาสเรียน (ห้องเรียน)</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="class_name" class="form-label">ชื่อคลาส/ห้องเรียน <span class="text-danger">*</span></label>
                                <input type="text" name="class_name" id="class_name" class="form-control <?php echo isset($errors['class_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($class_name); ?>">
                                <div class="invalid-feedback"><?php echo $errors['class_name'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="class_code" class="form-label">รหัสคลาส <span class="text-danger">*</span></label>
                                <input type="text" name="class_code" id="class_code" class="form-control <?php echo isset($errors['class_code']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($class_code); ?>">
                                <div class="invalid-feedback"><?php echo $errors['class_code'] ?? ''; ?></div>
                            </div>
                             <div class="col-md-4 mb-3">
                                <label for="academic_year_id" class="form-label">ปีการศึกษา/เทอม <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="academic_year_id" class="form-select <?php echo isset($errors['academic_year_id']) ? 'is-invalid' : ''; ?>">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach($academic_years as $ay): ?>
                                        <option value="<?php echo $ay['acad_year_id']; ?>" <?php if($academic_year_id == $ay['acad_year_id']) echo 'selected'; ?>><?php echo htmlspecialchars($ay['year']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['academic_year_id'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="teacher_id" class="form-label">ครูประจำชั้น <span class="text-danger">*</span></label>
                                <select name="teacher_id" id="teacher_id" class="form-select <?php echo isset($errors['teacher_id']) ? 'is-invalid' : ''; ?>">
                                     <option value="">-- เลือก --</option>
                                    <?php foreach($teachers as $t): ?>
                                        <option value="<?php echo $t['teacher_id']; ?>" <?php if($teacher_id == $t['teacher_id']) echo 'selected'; ?>><?php echo htmlspecialchars($t['fullname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['teacher_id'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="room_number" class="form-label">หมายเลขห้อง</label>
                                <input type="text" name="room_number" id="room_number" class="form-control" value="<?php echo htmlspecialchars($room_number); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_students" class="form-label">จำนวนนักเรียนสูงสุด</label>
                                <input type="number" name="max_students" id="max_students" class="form-control" value="<?php echo htmlspecialchars($max_students); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                     <div class="card-header"><h5><i class="fas fa-table me-2"></i>กำหนดตารางสอน</h5></div>
                     <div class="card-body">
                         <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>คาบที่</th><th>จันทร์</th><th>อังคาร</th><th>พุธ</th><th>พฤหัสบดี</th><th>ศุกร์</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($period = 1; $period <= 7; $period++): ?>
                                    <tr>
                                        <td class="align-middle"><strong><?php echo $period; ?></strong></td>
                                        <?php for ($day = 1; $day <= 5; $day++): ?>
                                        <td>
                                            <select name="schedule[<?php echo $day; ?>][<?php echo $period; ?>]" class="form-select form-select-sm mb-1">
                                                <option value="">-- เลือกวิชา --</option>
                                                <?php $subjects->data_seek(0); foreach($subjects as $subject): ?>
                                                    <option value="<?php echo $subject['subject_id']; ?>">
                                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . mb_substr($subject['subject_name'], 0, 20)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <select name="schedule_teacher[<?php echo $day; ?>][<?php echo $period; ?>]" class="form-select form-select-sm">
                                                <option value="">-- เลือกครู --</option>
                                                <?php $teachers->data_seek(0); foreach($teachers as $t): ?>
                                                    <option value="<?php echo $t['teacher_id']; ?>">
                                                        <?php echo htmlspecialchars($t['fullname']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                     </div>
                </div>

                 <div class="text-end mt-4">
                    <a href="class_list.php" class="btn btn-secondary btn-lg">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>สร้างคลาสและตารางสอน</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once 'includes/admin_footer.php'; ?>