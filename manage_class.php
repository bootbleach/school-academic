<?php
session_start();
// ต้องแน่ใจว่า config.php มีการเชื่อมต่อ $mysqli
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();
$errors = [];

// --- กำหนดค่าเริ่มต้นสำหรับฟอร์ม (สำหรับโหมดเพิ่มข้อมูล) ---
$acad_class_instance_id = null;
$class_id = '';
$academic_year_id = '';
$homeroom_teacher_id = '';
$room_number = '';
$max_students = '';
$selected_subjects_from_db = []; // วิชาที่ถูกกำหนดไว้แล้วสำหรับคลาสนี้ (สำหรับโหมดแก้ไข)
$selected_preset_id = ''; // ชุดวิชาที่ถูกเลือก (หากมีการเลือกไว้)

// --- ดึงข้อมูลสำหรับ Dropdowns และตารางแสดงผล ---
// ดึงข้อมูลคลาสพื้นฐานทั้งหมด
$all_classes = $mysqli->query("SELECT class_id, class_code, class_name FROM classes ORDER BY class_code ASC");
// ดึงข้อมูลปีการศึกษาทั้งหมด
$all_academic_years = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");
// ดึงข้อมูลครูทั้งหมด
$all_teachers = $mysqli->query("SELECT teacher_id, first_name, last_name FROM teachers ORDER BY first_name ASC");
// ดึงข้อมูลวิชาทั้งหมด
$all_subjects = $mysqli->query("SELECT subject_id, subject_code, subject_name FROM subjects ORDER BY subject_code ASC");
// ดึงข้อมูลชุดวิชาสำเร็จรูปทั้งหมด
$all_subject_presets = $mysqli->query("SELECT preset_id, preset_name FROM subject_presets ORDER BY preset_name ASC");

// --- จัดการคำขอแก้ไข (GET request with edit_id) ---
if (isset($_GET['edit_id'])) {
    $acad_class_instance_id = intval($_GET['edit_id']);

    // ดึงข้อมูลคลาสเรียนสำหรับปีการศึกษาที่ต้องการแก้ไข พร้อม subject_preset_id
    $stmt_instance = $mysqli->prepare("SELECT acad_class_instance_id, class_id, academic_year_id, homeroom_teacher_id, room_number, max_students, subject_preset_id FROM academic_class_instances WHERE acad_class_instance_id = ?");
    if ($stmt_instance) {
        $stmt_instance->bind_param("i", $acad_class_instance_id);
        $stmt_instance->execute();
        $result_instance = $stmt_instance->get_result();
        if ($result_instance->num_rows > 0) {
            $instance_data = $result_instance->fetch_assoc();
            $class_id = $instance_data['class_id'];
            $academic_year_id = $instance_data['academic_year_id'];
            $homeroom_teacher_id = $instance_data['homeroom_teacher_id'];
            $room_number = $instance_data['room_number'];
            $max_students = $instance_data['max_students'];
            $selected_preset_id = $instance_data['subject_preset_id']; // ดึงค่า preset_id ที่บันทึกไว้

            // ดึงวิชาที่ถูกกำหนดให้กับคลาสนี้แล้วจากตาราง academic_class_offered_subjects (ตารางใหม่)
            $stmt_assigned_subjects = $mysqli->prepare("SELECT subject_id FROM academic_class_offered_subjects WHERE acad_class_instance_id = ?");
            if ($stmt_assigned_subjects) {
                $stmt_assigned_subjects->bind_param("i", $acad_class_instance_id);
                $stmt_assigned_subjects->execute();
                $result_assigned_subjects = $stmt_assigned_subjects->get_result();
                while ($row = $result_assigned_subjects->fetch_assoc()) {
                    $selected_subjects_from_db[] = $row['subject_id'];
                }
                $stmt_assigned_subjects->close();
            } else {
                error_log("Error preparing assigned subjects statement: " . $mysqli->error);
            }

        } else {
            set_session_message('ไม่พบคลาสเรียนสำหรับปีการศึกษานี้.', 'danger');
            header("Location: manage_class.php");
            exit();
        }
        $stmt_instance->close();
    } else {
        error_log("Error preparing academic class instance statement: " . $mysqli->error);
    }
}

// --- จัดการคำขอลบ (GET request with delete_id) ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']);
    $mysqli->begin_transaction();
    try {
        // ลบข้อมูลที่เกี่ยวข้องใน academic_class_offered_subjects ก่อน (หากไม่มี ON DELETE CASCADE)
        // ถ้าคุณเพิ่ม FK และตั้งค่า ON DELETE CASCADE บน academic_class_offered_subjects คุณไม่จำเป็นต้องมีส่วนนี้
        $stmt_delete_offered_subjects = $mysqli->prepare("DELETE FROM academic_class_offered_subjects WHERE acad_class_instance_id = ?");
        if ($stmt_delete_offered_subjects) {
            $stmt_delete_offered_subjects->bind_param("i", $id_to_delete);
            $stmt_delete_offered_subjects->execute();
            $stmt_delete_offered_subjects->close();
        } else {
            throw new Exception("Error preparing delete offered subjects statement: " . $mysqli->error);
        }

        // ลบข้อมูลที่เกี่ยวข้องใน class_schedules ก่อน (หากไม่มี ON DELETE CASCADE จาก academic_class_instances ไป class_schedules)
        $stmt_delete_schedules = $mysqli->prepare("DELETE FROM class_schedules WHERE acad_class_instance_id = ?");
        if ($stmt_delete_schedules) {
            $stmt_delete_schedules->bind_param("i", $id_to_delete);
            $stmt_delete_schedules->execute();
            $stmt_delete_schedules->close();
        } else {
            throw new Exception("Error preparing delete schedules statement: " . $mysqli->error);
        }

        // ลบคลาสเรียนสำหรับปีการศึกษา
        $stmt_delete_instance = $mysqli->prepare("DELETE FROM academic_class_instances WHERE acad_class_instance_id = ?");
        if (!$stmt_delete_instance) {
            throw new Exception("Error preparing delete instance statement: " . $mysqli->error);
        }
        $stmt_delete_instance->bind_param("i", $id_to_delete);
        if (!$stmt_delete_instance->execute()) {
            throw new Exception("Error executing delete instance statement: " . $stmt_delete_instance->error);
        }
        $stmt_delete_instance->close();

        $mysqli->commit();
        set_session_message('ลบคลาสเรียนสำหรับปีการศึกษาและข้อมูลที่เกี่ยวข้องทั้งหมดสำเร็จ!', 'success');
    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage(), 'danger');
        error_log("Delete academic class instance error: " . $e->getMessage());
    }
    header("Location: manage_class.php");
    exit();
}

// --- จัดการการส่งฟอร์ม (POST request: เพิ่ม/แก้ไข) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acad_class_instance_id = intval($_POST['acad_class_instance_id'] ?? 0); // 0 หากเป็นการเพิ่มใหม่
    $class_id = trim($_POST['class_id'] ?? '');
    $academic_year_id = trim($_POST['academic_year_id'] ?? '');
    $homeroom_teacher_id = trim($_POST['homeroom_teacher_id'] ?? '');
    $room_number = trim($_POST['room_number'] ?? '');
    $max_students = trim($_POST['max_students'] ?? '');
    $selected_subjects_post = $_POST['selected_subjects'] ?? []; // Array ของ subject_id ที่เลือกในฟอร์ม
    $selected_preset_id_post = trim($_POST['subject_preset_id'] ?? ''); // preset_id ที่เลือกจากฟอร์ม

    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($class_id)) { $errors['class_id'] = 'กรุณาเลือกคลาสพื้นฐาน'; }
    if (empty($academic_year_id)) { $errors['academic_year_id'] = 'กรุณาเลือกปีการศึกษา'; }
    if (empty($homeroom_teacher_id)) { $errors['homeroom_teacher_id'] = 'กรุณาเลือกครูประจำชั้น'; }
    if (empty($room_number)) { $errors['room_number'] = 'กรุณากรอกหมายเลขห้อง'; }
    if (!is_numeric($max_students) || $max_students <= 0) { $errors['max_students'] = 'กรุณากรอกจำนวนนักเรียนสูงสุดที่ถูกต้อง'; }

    // ตรวจสอบความซ้ำซ้อนของคลาสเรียนสำหรับปีการศึกษา (class_id + academic_year_id ต้องไม่ซ้ำกัน)
    $check_sql = "SELECT COUNT(*) FROM academic_class_instances WHERE class_id = ? AND academic_year_id = ?";
    if ($acad_class_instance_id) { // หากเป็นการแก้ไข ให้ยกเว้น current instance
        $check_sql .= " AND acad_class_instance_id != ?";
    }
    $stmt_check = $mysqli->prepare($check_sql);
    if ($acad_class_instance_id) {
        $stmt_check->bind_param("iii", $class_id, $academic_year_id, $acad_class_instance_id);
    } else {
        $stmt_check->bind_param("ii", $class_id, $academic_year_id);
    }
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_row();
    if ($result_check[0] > 0) {
        $errors['duplicate_instance'] = 'คลาสเรียนนี้ถูกสร้างสำหรับปีการศึกษานี้แล้ว';
    }
    $stmt_check->close();

    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            if ($acad_class_instance_id) {
                // อัปเดตข้อมูลคลาสเรียนสำหรับปีการศึกษาที่มีอยู่ พร้อมบันทึก subject_preset_id
                $sql_instance = "UPDATE academic_class_instances SET class_id = ?, academic_year_id = ?, homeroom_teacher_id = ?, room_number = ?, max_students = ?, subject_preset_id = ? WHERE acad_class_instance_id = ?";
                $stmt_instance = $mysqli->prepare($sql_instance);
                // s for string (room_number), i for integer (all others including subject_preset_id and acad_class_instance_id)
                $stmt_instance->bind_param("iiisiii", $class_id, $academic_year_id, $homeroom_teacher_id, $room_number, $max_students, $selected_preset_id_post, $acad_class_instance_id);
                $stmt_instance->execute();
                $stmt_instance->close();
                $insert_id = $acad_class_instance_id; // ใช้ ID เดิมสำหรับการกำหนดวิชา

                set_session_message('อัปเดตข้อมูลคลาสเรียนสำหรับปีการศึกษาสำเร็จ!', 'success');
            } else {
                // เพิ่มคลาสเรียนสำหรับปีการศึกษาใหม่ พร้อมบันทึก subject_preset_id
                $sql_instance = "INSERT INTO academic_class_instances (class_id, academic_year_id, homeroom_teacher_id, room_number, max_students, subject_preset_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_instance = $mysqli->prepare($sql_instance);
                $stmt_instance->bind_param("iiisii", $class_id, $academic_year_id, $homeroom_teacher_id, $room_number, $max_students, $selected_preset_id_post);
                $stmt_instance->execute();
                $stmt_instance->close();
                $insert_id = $mysqli->insert_id;

                if ($insert_id == 0) {
                    throw new Exception("ไม่สามารถสร้างคลาสเรียนสำหรับปีการศึกษาได้");
                }
                set_session_message('สร้างคลาสเรียนสำหรับปีการศึกษาสำเร็จ!', 'success');
            }

            // --- ส่วนการจัดการวิชาที่เปิดสอนสำหรับคลาสเรียน (บันทึกลงในตาราง academic_class_offered_subjects) ---
            // 1. ลบวิชาที่เคยถูกกำหนดไว้สำหรับคลาสเรียนนี้ออกจากตารางใหม่ academic_class_offered_subjects
            $stmt_delete_offered = $mysqli->prepare("DELETE FROM academic_class_offered_subjects WHERE acad_class_instance_id = ?");
            if (!$stmt_delete_offered) {
                throw new Exception("Error preparing delete offered subjects statement: " . $mysqli->error);
            }
            $stmt_delete_offered->bind_param("i", $insert_id);
            $stmt_delete_offered->execute();
            $stmt_delete_offered->close();

            // 2. เพิ่มวิชาที่เลือกใหม่เข้าไปในตาราง academic_class_offered_subjects
            if (!empty($selected_subjects_post)) {
                $stmt_insert_offered = $mysqli->prepare("INSERT INTO academic_class_offered_subjects (acad_class_instance_id, subject_id) VALUES (?, ?)");
                if (!$stmt_insert_offered) {
                    throw new Exception("Error preparing insert offered subjects statement: " . $mysqli->error);
                }
                foreach ($selected_subjects_post as $subject_id) {
                    $stmt_insert_offered->bind_param("ii", $insert_id, $subject_id);
                    $stmt_insert_offered->execute();
                }
                $stmt_insert_offered->close();
            }

            $mysqli->commit();
            header("Location: manage_class.php");
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            set_session_message('เกิดข้อผิดพลาด: ' . $e->getMessage(), 'danger');
            error_log("Manage academic class error: " . $e->getMessage());
        }
    } else {
        set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
    }
}

$page_title = ($acad_class_instance_id ? "แก้ไข" : "สร้าง") . "คลาสเรียนสำหรับปีการศึกษา";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="d-none d-lg-block"><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="manage_class.php" class="btn btn-secondary ms-auto"><i class="fas fa-plus-circle me-2"></i>สร้างใหม่ / ดูทั้งหมด</a>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo ($acad_class_instance_id ? "แก้ไข" : "สร้าง") . "คลาสเรียนสำหรับปีการศึกษา"; ?></h5>
                </div>
                <div class="card-body">
                    <form action="manage_class.php" method="post" novalidate>
                        <input type="hidden" name="acad_class_instance_id" value="<?php echo htmlspecialchars($acad_class_instance_id); ?>">
                        
                        <?php if (isset($errors['duplicate_instance'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['duplicate_instance']; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="class_id" class="form-label">คลาสพื้นฐาน <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-select <?php echo isset($errors['class_id']) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">-- เลือกคลาสพื้นฐาน --</option>
                                    <?php $all_classes->data_seek(0); // Reset pointer ?>
                                    <?php while ($class = $all_classes->fetch_assoc()): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo ($class['class_id'] == $class_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['class_id'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="academic_year_id" class="form-label">ปีการศึกษา <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="academic_year_id" class="form-select <?php echo isset($errors['academic_year_id']) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">-- เลือกปีการศึกษา --</option>
                                    <?php $all_academic_years->data_seek(0); // Reset pointer ?>
                                    <?php while ($year = $all_academic_years->fetch_assoc()): ?>
                                        <option value="<?php echo $year['acad_year_id']; ?>" <?php echo ($year['acad_year_id'] == $academic_year_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year['year']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['academic_year_id'] ?? ''; ?></div>
                                <div class="form-text">
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="homeroom_teacher_id" class="form-label">ครูประจำชั้น <span class="text-danger">*</span></label>
                                <select name="homeroom_teacher_id" id="homeroom_teacher_id" class="form-select <?php echo isset($errors['homeroom_teacher_id']) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">-- เลือกครูประจำชั้น --</option>
                                    <?php $all_teachers->data_seek(0); // Reset pointer ?>
                                    <?php while ($teacher = $all_teachers->fetch_assoc()): ?>
                                        <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo ($teacher['teacher_id'] == $homeroom_teacher_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['homeroom_teacher_id'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="room_number" class="form-label">หมายเลขห้อง <span class="text-danger">*</span></label>
                                <input type="text" name="room_number" id="room_number" class="form-control <?php echo isset($errors['room_number']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($room_number); ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['room_number'] ?? ''; ?></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="max_students" class="form-label">จำนวนนักเรียนสูงสุด <span class="text-danger">*</span></label>
                                <input type="number" name="max_students" id="max_students" class="form-control <?php echo isset($errors['max_students']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($max_students); ?>" min="1" required>
                                <div class="invalid-feedback"><?php echo $errors['max_students'] ?? ''; ?></div>
                            </div>
                        </div>

                        <hr>
                        <h5>การกำหนดวิชาเรียน (สำหรับข้อมูลเบื้องต้น)</h5>
                        <div class="alert alert-info small">
                            <strong>หมายเหตุ:</strong> ส่วนนี้ใช้สำหรับการระบุวิชาที่ 'ควรจะ' มีในคลาสนี้ หรือใช้ชุดวิชาสำเร็จรูปเพื่อความสะดวกในการจัดการ<br>
                            การเลือกวิชาจากส่วนนี้จะถูกใช้เพื่อแสดงผลบนหน้าจอ และบันทึกลงในตาราง **`academic_class_offered_subjects`** (ตารางใหม่) <br>
                            การจัดตารางสอนที่สมบูรณ์ (พร้อมข้อมูลครู, วัน, เวลา) จะต้องทำในส่วนอื่นที่เกี่ยวข้องกับตาราง **`class_schedules`**
                        </div>

                        <div class="mb-3">
                            <label for="subject_preset_id" class="form-label">เลือกชุดวิชาสำเร็จรูป</label>
                            <select name="subject_preset_id" id="subject_preset_id" class="form-select">
                                <option value="">-- เลือกชุดวิชา --</option>
                                <?php $all_subject_presets->data_seek(0); // Reset pointer ?>
                                <?php while ($preset = $all_subject_presets->fetch_assoc()): ?>
                                    <option value="<?php echo $preset['preset_id']; ?>" <?php echo ($preset['preset_id'] == $selected_preset_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($preset['preset_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">การเลือกชุดวิชาจะช่วยเลือกวิชาในรายการด้านล่างโดยอัตโนมัติ</div>
                        </div>

                        <div class="mb-3">
                            <label for="selected_subjects" class="form-label">เลือกวิชาที่ต้องการ</label>
                            <select name="selected_subjects[]" id="selected_subjects" class="form-select" multiple size="10">
                                <?php $all_subjects->data_seek(0); // Reset pointer for dropdown ?>
                                <?php while ($subject = $all_subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>"
                                        <?php echo in_array($subject['subject_id'], $selected_subjects_from_db) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">กด Ctrl (หรือ Cmd บน Mac) ค้างไว้เพื่อเลือกหลายวิชา</div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกข้อมูล</button>
                        <?php if ($acad_class_instance_id): ?>
                            <a href="manage_class.php" class="btn btn-secondary">ยกเลิกการแก้ไข</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list-alt me-2"></i>รายการคลาสเรียนสำหรับปีการศึกษา</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>รหัสคลาส</th>
                                    <th>ชื่อคลาส</th>
                                    <th>ปีการศึกษา</th>
                                    <th>ครูประจำชั้น</th>
                                    <th>ห้อง</th>
                                    <th>จำนวน นร. สูงสุด</th>
                                    <th>วิชาที่กำหนด (ตามแผน)</th>
                                    <th class="text-end" style="width: 150px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // ดึงข้อมูลคลาสเรียนสำหรับปีการศึกษาทั้งหมด พร้อม JOIN ข้อมูลที่เกี่ยวข้อง
                                // และดึงวิชาที่เปิดสอนจากตาราง academic_class_offered_subjects (ตารางใหม่)
                                $sql_fetch_instances = "
                                    SELECT 
                                        aci.acad_class_instance_id,
                                        c.class_code,
                                        c.class_name,
                                        ay.year AS academic_year_name,
                                        CONCAT(t.first_name, ' ', t.last_name) AS homeroom_teacher_name,
                                        aci.room_number,
                                        aci.max_students,
                                        GROUP_CONCAT(DISTINCT s.subject_code ORDER BY s.subject_code ASC SEPARATOR ', ') AS offered_subjects_list
                                    FROM 
                                        academic_class_instances aci
                                    JOIN 
                                        classes c ON aci.class_id = c.class_id
                                    JOIN 
                                        academic_years ay ON aci.academic_year_id = ay.acad_year_id
                                    JOIN 
                                        teachers t ON aci.homeroom_teacher_id = t.teacher_id
                                    LEFT JOIN
                                        academic_class_offered_subjects acos ON aci.acad_class_instance_id = acos.acad_class_instance_id
                                    LEFT JOIN
                                        subjects s ON acos.subject_id = s.subject_id
                                    GROUP BY
                                        aci.acad_class_instance_id
                                    ORDER BY 
                                        ay.year DESC, c.class_code ASC";
                                $academic_instances = $mysqli->query($sql_fetch_instances);
                                ?>
                                <?php if ($academic_instances && $academic_instances->num_rows > 0): ?>
                                    <?php while ($instance = $academic_instances->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($instance['acad_class_instance_id']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['class_code']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['academic_year_name']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['homeroom_teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['room_number']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['max_students']); ?></td>
                                            <td><?php echo htmlspecialchars($instance['offered_subjects_list'] ?? 'ยังไม่มีวิชา'); ?></td>
                                            <td class="text-end">
                                                <a href="manage_class.php?edit_id=<?php echo $instance['acad_class_instance_id']; ?>" class="btn btn-warning btn-sm me-1" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage_class.php?delete_id=<?php echo $instance['acad_class_instance_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบคลาสเรียนสำหรับปีการศึกษานี้และข้อมูลที่เกี่ยวข้องทั้งหมดหรือไม่?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-3">ยังไม่มีข้อมูลคลาสเรียนสำหรับปีการศึกษา</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectPresetSelect = document.getElementById('subject_preset_id');
    const selectedSubjectsMultiSelect = document.getElementById('selected_subjects');
    const allSubjectsOptions = Array.from(selectedSubjectsMultiSelect.options);

    // เตรียมข้อมูลชุดวิชาสำหรับ JavaScript
    const presetSubjectsMap = {};
    <?php
    // ดึงข้อมูลวิชาในแต่ละชุดวิชา เพื่อสร้าง JavaScript map
    $all_subject_presets->data_seek(0); // เลื่อน pointer กลับไปเริ่มต้น
    while ($preset = $all_subject_presets->fetch_assoc()) {
        $preset_id = $preset['preset_id'];
        $stmt_preset_subjects = $mysqli->prepare("SELECT subject_id FROM preset_subjects WHERE preset_id = ?");
        if ($stmt_preset_subjects) {
            $stmt_preset_subjects->bind_param("i", $preset_id);
            $stmt_preset_subjects->execute();
            $result_preset_subjects = $stmt_preset_subjects->get_result();
            $subjects_in_preset = [];
            while ($row = $result_preset_subjects->fetch_assoc()) {
                $subjects_in_preset[] = $row['subject_id'];
            }
            // แปลง PHP array เป็น JavaScript object
            echo "presetSubjectsMap['{$preset_id}'] = " . json_encode($subjects_in_preset) . ";\n";
            $stmt_preset_subjects->close();
        }
    }
    ?>

    subjectPresetSelect.addEventListener('change', function() {
        const selectedPreset = this.value;
        
        // ยกเลิกการเลือกวิชาทั้งหมดก่อน
        allSubjectsOptions.forEach(option => {
            option.selected = false;
        });

        // หากมีการเลือกชุดวิชา และมีข้อมูลวิชาในชุดนั้น
        if (selectedPreset && presetSubjectsMap[selectedPreset]) {
            const subjectsToSelect = presetSubjectsMap[selectedPreset];
            allSubjectsOptions.forEach(option => {
                // เลือกวิชาที่อยู่ในชุดวิชาที่เลือก
                if (subjectsToSelect.includes(parseInt(option.value))) {
                    option.selected = true;
                }
            });
        }
    });

    // ส่วนนี้จะพยายามเลือก preset ที่ตรงกับวิชาที่ถูกเลือกไว้แล้ว
    // เมื่อหน้าโหลดครั้งแรก (ในโหมดแก้ไข)
    // ตรวจสอบว่ามี preset_id ที่เลือกไว้แล้วหรือไม่ และมีวิชาที่ถูกเลือกมาจาก DB หรือไม่
    <?php if ($acad_class_instance_id && !empty($selected_subjects_from_db)): ?>
        const selectedSubjectsOnLoad = <?php echo json_encode($selected_subjects_from_db); ?>;
        
        // หากมี preset_id ถูกเลือกไว้แล้วจาก DB ให้เลือก preset นั้น
        const presetIdFromDb = '<?php echo $selected_preset_id; ?>';
        if (presetIdFromDb && presetSubjectsMap[presetIdFromDb]) {
            subjectPresetSelect.value = presetIdFromDb;
            // เนื่องจาก script ด้านบนจะทำงานเมื่อมีการเปลี่ยนค่าใน dropdown เท่านั้น
            // เราจึงต้องจำลองการเลือก (trigger change event) หรือเรียกฟังก์ชันเลือกเอง
            // หรือในกรณีนี้คือปล่อยให้ PHP เลือก initial value แล้ว JS จะมาแก้ไขถ้าเปลี่ยน
            // ถ้าต้องการให้ JS เลือกตาม preset จาก DB ตั้งแต่แรก ต้องมั่นใจว่า selected_subjects_from_db
            // สอดคล้องกับ preset นั้น หรือเรียก event change ด้วยตัวเอง
            
            // เพื่อให้แน่ใจว่าวิชาถูกเลือกตาม preset ที่มาจาก DB
            // ไม่ต้องทำอะไรเพิ่มที่นี่ เพราะค่าใน dropdown selected_subjects จะถูกตั้งค่าโดย PHP แล้ว
            // จาก $selected_subjects_from_db
        } else {
            // หากไม่มี preset_id ถูกเลือกไว้ หรือ preset นั้นไม่มีข้อมูลวิชา
            // เราอาจต้องตรวจสอบว่าวิชาที่ถูกเลือก (selected_subjects_from_db) ตรงกับ preset ใดบ้าง
            // เพื่อเลือก preset นั้นโดยอัตโนมัติ
            for (const presetId in presetSubjectsMap) {
                if (presetSubjectsMap.hasOwnProperty(presetId)) {
                    const presetSubjects = presetSubjectsMap[presetId].sort();
                    const currentSelected = selectedSubjectsOnLoad.map(String).sort(); // Convert to string for comparison

                    if (JSON.stringify(presetSubjects) === JSON.stringify(currentSelected)) {
                        subjectPresetSelect.value = presetId;
                        break; 
                    }
                }
            }
        }
    <?php endif; ?>

    // ตรวจสอบฟอร์ม Validation (เพิ่ม Bootstrap validation feedback)
    const forms = document.querySelectorAll('form.needs-validation');
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>