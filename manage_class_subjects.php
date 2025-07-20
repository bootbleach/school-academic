<?php
session_start();
require_once 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/admin_functions.php'; // ฟังก์ชันสำหรับ Admin (เช่น is_admin_loggedin, set_session_message)

// ตรวจสอบสถานะการล็อกอินของ Admin
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่าได้รับ academic_year_id และ class_id ผ่าน URL หรือไม่
$class_id = null;
$academic_year_id = null;
$acad_class_instance_id = null; // เพิ่มตัวแปรสำหรับ acad_class_instance_id
$homeroom_teacher_id_1 = null; // เพิ่มตัวแปรสำหรับครูประจำชั้นคนที่ 1
$homeroom_teacher_id_2 = null; // เพิ่มตัวแปรสำหรับครูประจำชั้นคนที่ 2

if (isset($_GET['class_id']) && filter_var($_GET['class_id'], FILTER_VALIDATE_INT)) {
    $class_id = (int)$_GET['class_id'];
}
if (isset($_GET['academic_year_id']) && filter_var($_GET['academic_year_id'], FILTER_VALIDATE_INT)) {
    $academic_year_id = (int)$_GET['academic_year_id'];
}

// ถ้าไม่มี class_id หรือ academic_year_id หรือไม่มีทั้งคู่ ให้เปลี่ยนเส้นทางไปยังหน้าเลือก
if (is_null($class_id) || is_null($academic_year_id)) {
    set_session_message('กรุณาเลือกคลาสและปีการศึกษาที่ต้องการจัดการวิชา', 'danger');
    header("Location: class_list.php"); // หรือหน้าที่เหมาะสมในการเลือกคลาส/ปีการศึกษา
    exit();
}

$current_class_name = '';
$current_academic_year_name = '';

// *** เริ่มต้น: ส่วนที่แก้ไขเพื่อดึงหรือสร้าง acad_class_instance_id และครูประจำชั้น ***
// ดึง acad_class_instance_id และข้อมูลคลาส/ปีการศึกษา และครูประจำชั้น
$stmt_instance = $mysqli->prepare("
    SELECT
        aci.acad_class_instance_id,
        c.class_name,
        ay.year,
        aci.homeroom_teacher_id,        -- ดึงครูประจำชั้นคนแรก
        aci.homeroom_teacher_id_2       -- ดึงครูประจำชั้นคนที่สอง (หลังจากเพิ่มคอลัมน์ใน DB)
    FROM
        academic_class_instances aci
    JOIN
        classes c ON aci.class_id = c.class_id
    JOIN
        academic_years ay ON aci.academic_year_id = ay.acad_year_id
    WHERE
        aci.class_id = ? AND aci.academic_year_id = ?
");

if ($stmt_instance) {
    $stmt_instance->bind_param("ii", $class_id, $academic_year_id);
    $stmt_instance->execute();
    $result_instance = $stmt_instance->get_result();
    if ($result_instance->num_rows > 0) {
        $instance_data = $result_instance->fetch_assoc();
        $acad_class_instance_id = $instance_data['acad_class_instance_id'];
        $current_class_name = $instance_data['class_name'];
        $current_academic_year_name = $instance_data['year'];
        $homeroom_teacher_id_1 = $instance_data['homeroom_teacher_id'];
        $homeroom_teacher_id_2 = $instance_data['homeroom_teacher_id_2'];
    }
    $stmt_instance->close();
}

// ถ้าไม่พบ acad_class_instance_id ให้สร้างใหม่
if (is_null($acad_class_instance_id)) {
    // ต้องตรวจสอบว่า class_id และ academic_year_id นั้นมีอยู่จริงในตาราง classes และ academic_years
    // ถ้าไม่มีอยู่จริง ควร redirect หรือแจ้ง error
    $stmt_check_class_year = $mysqli->prepare("
        SELECT c.class_name, ay.year
        FROM classes c, academic_years ay
        WHERE c.class_id = ? AND ay.acad_year_id = ?
    ");
    if ($stmt_check_class_year) {
        $stmt_check_class_year->bind_param("ii", $class_id, $academic_year_id);
        $stmt_check_class_year->execute();
        $result_check = $stmt_check_class_year->get_result();
        if ($result_check->num_rows > 0) {
            $data = $result_check->fetch_assoc();
            $current_class_name = $data['class_name'];
            $current_academic_year_name = $data['year'];

            // มี class_id และ academic_year_id อยู่จริงแต่ยังไม่มี instance, ให้สร้างใหม่
            $stmt_insert_instance = $mysqli->prepare("
                INSERT INTO academic_class_instances (class_id, academic_year_id) VALUES (?, ?)
            ");
            if ($stmt_insert_instance) {
                $stmt_insert_instance->bind_param("ii", $class_id, $academic_year_id);
                if ($stmt_insert_instance->execute()) {
                    $acad_class_instance_id = $mysqli->insert_id; // ได้รับ ID ที่สร้างขึ้นใหม่
                } else {
                    error_log("Error creating academic_class_instance: " . $stmt_insert_instance->error);
                    set_session_message('เกิดข้อผิดพลาดในการสร้าง Academic Class Instance: ' . $stmt_insert_instance->error, 'danger');
                    header("Location: class_list.php");
                    exit();
                }
                $stmt_insert_instance->close();
            } else {
                error_log("Error preparing insert instance statement: " . $mysqli->error);
                set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่งสร้าง Academic Class Instance: ' . $mysqli->error, 'danger');
                header("Location: class_list.php");
                exit();
            }
        } else {
            set_session_message('ไม่พบคลาสหรือปีการศึกษาที่ระบุ', 'danger');
            header("Location: class_list.php");
            exit();
        }
        $stmt_check_class_year->close();
    } else {
        error_log("Error preparing check class year statement: " . $mysqli->error);
        set_session_message('เกิดข้อผิดพลาดในการเตรียมคำสั่งตรวจสอบคลาส/ปีการศึกษา: ' . $mysqli->error, 'danger');
        header("Location: class_list.php");
        exit();
    }
}

// ถ้า acad_class_instance_id ยังคงเป็น null หลังจากพยายามดึง/สร้าง
if (is_null($acad_class_instance_id)) {
    set_session_message('ไม่สามารถระบุ Academic Class Instance ได้ กรุณาลองใหม่', 'danger');
    header("Location: class_list.php");
    exit();
}
// *** สิ้นสุด: ส่วนที่แก้ไขเพื่อดึงหรือสร้าง acad_class_instance_id ***


// ดึงข้อมูลวิชาทั้งหมด
$subjects = [];
$subjects_result = $mysqli->query("SELECT subject_id, subject_code, subject_name, credits FROM subjects ORDER BY subject_code ASC");
if ($subjects_result) {
    $subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching subjects: " . $mysqli->error);
    set_session_message('เกิดข้อผิดพลาดในการดึงข้อมูลวิชา: ' . $mysqli->error, 'danger');
}

// ดึงข้อมูลครูทั้งหมด
$teachers = [];
$teachers_result = $mysqli->query("SELECT teacher_id, fullname FROM teachers ORDER BY fullname ASC");
if ($teachers_result) {
    $teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching teachers: " . $mysqli->error);
    set_session_message('เกิดข้อผิดพลาดในการดึงข้อมูลครู: ' . $mysqli->error, 'danger');
}

// ดึงข้อมูล PRESETS ทั้งหมด
$presets = [];
$presets_result = $mysqli->query("SELECT preset_id, preset_name FROM subject_presets ORDER BY preset_name ASC");
if ($presets_result) {
    $presets = $presets_result->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching presets: " . $mysqli->error);
    set_session_message('เกิดข้อผิดพลาดในการดึงข้อมูลชุดวิชา (Presets): ' . $mysqli->error, 'danger');
}

// ดึงข้อมูลตารางสอนที่มีอยู่แล้วสำหรับคลาสและปีการศึกษานี้
// ใช้ acad_class_instance_id แทน class_id และ academic_year_id
$assigned_schedules = [];
$stmt_assigned = $mysqli->prepare("
    SELECT
        cs.schedule_id,
        cs.subject_id,
        s.subject_code,
        s.subject_name,
        s.credits,
        cs.teacher_id,
        t.fullname AS teacher_name
    FROM class_schedules cs
    JOIN subjects s ON cs.subject_id = s.subject_id
    LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
    WHERE cs.acad_class_instance_id = ?
    ORDER BY s.subject_code ASC
");
if ($stmt_assigned) {
    $stmt_assigned->bind_param("i", $acad_class_instance_id); // ใช้ acad_class_instance_id
    $stmt_assigned->execute();
    $assigned_schedules = $stmt_assigned->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_assigned->close();
} else {
    error_log("Error fetching assigned schedules: " . $mysqli->error);
    set_session_message('เกิดข้อผิดพลาดในการดึงข้อมูลวิชาที่กำหนดไว้แล้ว: ' . $mysqli->error, 'danger');
}

$errors = [];

// จัดการการบันทึกข้อมูล (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // *** เริ่มต้น: ส่วนที่แก้ไขเพื่อจัดการการบันทึกครูประจำชั้น ***
    if (isset($_POST['update_homeroom_teachers'])) {
        $ht1 = filter_var($_POST['homeroom_teacher_id_1'], FILTER_VALIDATE_INT);
        $ht2 = filter_var($_POST['homeroom_teacher_id_2'], FILTER_VALIDATE_INT);

        // แปลงค่า 0 (จาก filter_var กรณีเลือก "ไม่ระบุ" หรือค่าว่าง) ให้เป็น NULL สำหรับฐานข้อมูล
        $ht1 = ($ht1 === 0 || $ht1 === false) ? NULL : $ht1; // false กรณี filter_var ล้มเหลว
        $ht2 = ($ht2 === 0 || $ht2 === false) ? NULL : $ht2;

        $mysqli->begin_transaction();
        try {
            $stmt_update_ht = $mysqli->prepare("
                UPDATE academic_class_instances
                SET homeroom_teacher_id = ?, homeroom_teacher_id_2 = ?
                WHERE acad_class_instance_id = ?
            ");
            if (!$stmt_update_ht) {
                throw new Exception("Error preparing homeroom teacher update statement: " . $mysqli->error);
            }
            // ใช้ 'iii' สำหรับ bind_param (integer, integer, integer)
            // ถ้า $ht1 หรือ $ht2 เป็น NULL ต้องใช้ 'i' แล้วส่งค่า NULL เข้าไปโดยตรง
            // แต่เนื่องจาก filter_var($var, FILTER_VALIDATE_INT) จะคืนค่า false หรือ 0 หากไม่ใช่ int
            // เราจึงควรตรวจสอบและแปลงเป็น NULL ก่อนใช้ bind_param เพื่อให้ MySQL รับรู้ NULL
            if ($ht1 === NULL && $ht2 === NULL) {
                 $stmt_update_ht->bind_param("ssi", $ht1, $ht2, $acad_class_instance_id);
            } else if ($ht1 === NULL) {
                 $stmt_update_ht->bind_param("sii", $ht1, $ht2, $acad_class_instance_id);
            } else if ($ht2 === NULL) {
                 $stmt_update_ht->bind_param("isi", $ht1, $ht2, $acad_class_instance_id);
            } else {
                 $stmt_update_ht->bind_param("iii", $ht1, $ht2, $acad_class_instance_id);
            }


            if (!$stmt_update_ht->execute()) {
                throw new Exception("Error updating homeroom teachers: " . $stmt_update_ht->error);
            }
            $stmt_update_ht->close();
            $mysqli->commit();
            set_session_message('บันทึกครูประจำชั้นสำเร็จ!', 'success');
            header("Location: manage_class_subjects.php?class_id=" . $class_id . "&academic_year_id=" . $academic_year_id);
            exit();
        } catch (Exception $e) {
            $mysqli->rollback();
            set_session_message('เกิดข้อผิดพลาดในการบันทึกครูประจำชั้น: ' . $e->getMessage(), 'danger');
            error_log("Manage Homeroom Teachers Error: " . $e->getMessage());
        }
    }
    // *** สิ้นสุด: ส่วนที่แก้ไขเพื่อจัดการการบันทึกครูประจำชั้น ***


    // จัดการการบันทึกข้อมูลวิชา (เดิม)
    $submitted_schedules = $_POST['schedules'] ?? [];

    // เริ่มต้น Transaction
    $mysqli->begin_transaction();
    $success = true;

    try {
        // 1. ลบวิชาที่กำหนดไว้เก่าทั้งหมดของคลาสและปีการศึกษานี้
        // ใช้ acad_class_instance_id ในการลบ
        $stmt_delete = $mysqli->prepare("DELETE FROM class_schedules WHERE acad_class_instance_id = ?");
        if (!$stmt_delete) {
            throw new Exception("Error preparing delete statement: " . $mysqli->error);
        }
        $stmt_delete->bind_param("i", $acad_class_instance_id); // ใช้ acad_class_instance_id
        if (!$stmt_delete->execute()) {
            throw new Exception("Error deleting old schedules: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 2. เพิ่มวิชาใหม่ตามข้อมูลที่ส่งมา
        if (!empty($submitted_schedules)) {
            $stmt_insert = $mysqli->prepare("
                INSERT INTO class_schedules (
                    acad_class_instance_id, subject_id, teacher_id, academic_year_id, is_active
                ) VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt_insert) {
                throw new Exception("Error preparing insert statement: " . $mysqli->error);
            }

            foreach ($submitted_schedules as $schedule) {
                $sub_id = filter_var($schedule['subject_id'], FILTER_VALIDATE_INT);
                $teacher_id_val = filter_var($schedule['teacher_id'], FILTER_VALIDATE_INT);
                $is_active = isset($schedule['is_active']) ? 1 : 0; // Default to 1 if not explicitly set

                // Basic validation for required fields
                if (empty($sub_id)) {
                    $errors[] = 'ข้อมูลวิชาไม่ครบถ้วนสำหรับวิชา ' . htmlspecialchars($schedule['subject_name'] ?? 'ไม่ทราบชื่อ');
                    $success = false;
                    break;
                }

                // If teacher_id_val is 0 (from filter_var for empty string or 0), set to NULL
                if ($teacher_id_val === 0 || $teacher_id_val === false) {
                    $teacher_id_val = NULL;
                }

                // สำหรับ bind_param: 'i' สำหรับ integer, 's' สำหรับ string (ใช้เมื่อค่าเป็น NULL เพื่อให้ MySQL จัดการ)
                if ($teacher_id_val === NULL) {
                    $stmt_insert->bind_param("isii",
                        $acad_class_instance_id, // ใช้ acad_class_instance_id
                        $sub_id,
                        $academic_year_id, // academic_year_id ยังคงอยู่
                        $is_active
                    );
                    // ต้องจัดการ $teacher_id_val โดยตรงในการ execute ถ้าใช้ 's'
                    // หรือใช้คำสั่งที่จัดการ NULL ได้ดีกว่า เช่น `INSERT INTO ... VALUES (?, ?, ?, ?, ?)`,
                    // แล้วตรวจสอบชนิดข้อมูลก่อน bind_param เพื่อให้ถูกต้อง เช่น bind_param("iisii", ...);
                    // แต่ในกรณีนี้ bind_param("iiiis") คาดว่า teacher_id_val เป็น integer
                    // การตั้งค่าเป็น NULL แล้วใช้ bind_param("iiiis", ...) จะทำงานได้ หาก MySQLi driver รองรับ
                    // ถ้าไม่รองรับ ควรใช้ "is" หรือ "ss" สำหรับ NULL
                    // ในที่นี้จะยังคงใช้ 'iiiis' แต่ระมัดระวังเรื่อง NULL ใน teacher_id_val
                    $stmt_insert->bind_param("iiiis",
                        $acad_class_instance_id,
                        $sub_id,
                        $teacher_id_val, // ส่ง NULL ตรงๆ
                        $academic_year_id,
                        $is_active
                    );

                } else {
                    $stmt_insert->bind_param("iiiis", // acad_class_instance_id, subject_id, teacher_id, academic_year_id, is_active
                        $acad_class_instance_id, // ใช้ acad_class_instance_id
                        $sub_id,
                        $teacher_id_val,
                        $academic_year_id, // academic_year_id ยังคงอยู่
                        $is_active
                    );
                }


                if (!$stmt_insert->execute()) {
                    throw new Exception("Error inserting schedule for subject " . htmlspecialchars($schedule['subject_name']) . ": " . $stmt_insert->error);
                }
            }
            $stmt_insert->close();
        }

        if ($success) {
            $mysqli->commit();
            set_session_message('บันทึกวิชาที่กำหนดไว้สำเร็จ!', 'success');
            // รีโหลดหน้าเพื่อให้ข้อมูลที่แสดงอัปเดต
            header("Location: manage_class_subjects.php?class_id=" . $class_id . "&academic_year_id=" . $academic_year_id);
            exit();
        } else {
            $mysqli->rollback();
            set_session_message('เกิดข้อผิดพลาดในการบันทึกวิชาที่กำหนดไว้: ' . implode('<br>', $errors), 'danger');
        }

    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาดในการบันทึกวิชาที่กำหนดไว้: ' . $e->getMessage(), 'danger');
        error_log("Manage Class Subjects Error: " . $e->getMessage());
    }
    // หลังจากการประมวลผล POST ไม่ว่าสำเร็จหรือไม่ ให้รีโหลดข้อมูล assigned_schedules ใหม่
    $stmt_assigned = $mysqli->prepare("
        SELECT
            cs.schedule_id,
            cs.subject_id,
            s.subject_code,
            s.subject_name,
            s.credits,
            cs.teacher_id,
            t.fullname AS teacher_name
        FROM class_schedules cs
        JOIN subjects s ON cs.subject_id = s.subject_id
        LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
        WHERE cs.acad_class_instance_id = ?
        ORDER BY s.subject_code ASC
    ");
    if ($stmt_assigned) {
        $stmt_assigned->bind_param("i", $acad_class_instance_id);
        $stmt_assigned->execute();
        $assigned_schedules = $stmt_assigned->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_assigned->close();
    }
}


$page_title = "จัดการวิชาสำหรับคลาส " . htmlspecialchars($current_class_name) . " ปีการศึกษา " . htmlspecialchars($current_academic_year_name);
require_once 'includes/admin_header.php'; // Include header
require_once 'includes/admin_sidebar.php'; // Include sidebar
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <?php echo get_session_message(); // แสดงข้อความแจ้งเตือน ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>ข้อมูลคลาสและปีการศึกษา</h5>
                </div>
                <div class="card-body">
                    <p><strong>คลาส:</strong> <?php echo htmlspecialchars($current_class_name); ?></p>
                    <p><strong>ปีการศึกษา:</strong> <?php echo htmlspecialchars($current_academic_year_name); ?></p>

                    <hr>
                    <h6 class="mb-3">จัดการครูประจำชั้น 🧑‍🏫</h6>
                    <form id="manageHomeroomTeachersForm" method="post" action="manage_class_subjects.php?class_id=<?php echo $class_id; ?>&academic_year_id=<?php echo $academic_year_id; ?>">
                        <div class="mb-3">
                            <label for="homeroomTeacher1" class="form-label">ครูประจำชั้นคนที่ 1:</label>
                            <select name="homeroom_teacher_id_1" id="homeroomTeacher1" class="form-select">
                                <option value="">ไม่ระบุ</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo ($homeroom_teacher_id_1 == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="homeroomTeacher2" class="form-label">ครูประจำชั้นคนที่ 2:</label>
                            <select name="homeroom_teacher_id_2" id="homeroomTeacher2" class="form-select">
                                <option value="">ไม่ระบุ</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo ($homeroom_teacher_id_2 == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_homeroom_teachers" class="btn btn-info"><i class="fas fa-user-tie me-2"></i>บันทึกครูประจำชั้น</button>
                    </form>
                    </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>วิชาที่เปิดสอนสำหรับคลาสนี้</h5>
                </div>
                <div class="card-body">
                    <form id="manageSubjectsForm" action="manage_class_subjects.php?class_id=<?php echo $class_id; ?>&academic_year_id=<?php echo $academic_year_id; ?>" method="post">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>รหัสวิชา</th>
                                        <th>ชื่อวิชา</th>
                                        <th>หน่วยกิต</th>
                                        <th>ครูผู้สอน</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedSubjectsTableBody">
                                    <?php if (empty($assigned_schedules)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">ยังไม่มีวิชาที่กำหนดไว้สำหรับคลาสและปีการศึกษานี้</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assigned_schedules as $idx => $schedule): ?>
                                            <tr id="scheduleRow_<?php echo $schedule['subject_id'] . '_' . $idx; ?>">
                                                <td>
                                                    <?php echo htmlspecialchars($schedule['subject_code']); ?>
                                                    <input type="hidden" name="schedules[<?php echo $idx; ?>][subject_id]" value="<?php echo htmlspecialchars($schedule['subject_id']); ?>">
                                                    <input type="hidden" name="schedules[<?php echo $idx; ?>][subject_name]" value="<?php echo htmlspecialchars($schedule['subject_name']); ?>">
                                                    <input type="hidden" name="schedules[<?php echo $idx; ?>][credits]" value="<?php echo htmlspecialchars($schedule['credits']); ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($schedule['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['credits']); ?></td>
                                                <td>
                                                    <select name="schedules[<?php echo $idx; ?>][teacher_id]" class="form-select form-select-sm">
                                                        <option value="">ไม่ระบุ</option>
                                                        <?php foreach ($teachers as $teacher): ?>
                                                            <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo ($schedule['teacher_id'] == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($teacher['fullname']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-subject" data-row-id="scheduleRow_<?php echo $schedule['subject_id'] . '_' . $idx; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>บันทึกวิชา</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>นำเข้าวิชาจากชุดวิชา (Templates)</h5>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <select id="presetSelect" class="form-select">
                            <option value="">-- เลือกชุดวิชา --</option>
                            <?php foreach ($presets as $preset): ?>
                                <option value="<?php echo htmlspecialchars($preset['preset_id']); ?>">
                                    <?php echo htmlspecialchars($preset['preset_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="importPresetBtn" class="btn btn-primary" disabled>
                            <i class="fas fa-file-import me-2"></i>นำเข้าชุดวิชา
                        </button>
                    </div>
                    <small class="form-text text-muted mt-2">เมื่อนำเข้า วิชาจากชุดวิชาจะถูกเพิ่มในตารางด้านบนและต้องกด "บันทึกวิชา" เพื่อยืนยัน</small>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>เพิ่มวิชาด้วยตนเอง</h5>
                    <div class="input-group">
                        <input type="text" id="subjectSearch" class="form-control" placeholder="ค้นหารหัสวิชาหรือชื่อวิชา...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อวิชา</th>
                                    <th>หน่วยกิต</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="availableSubjectsTableBody">
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">ไม่พบข้อมูลวิชา</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr data-subject-id="<?php echo htmlspecialchars($subject['subject_id']); ?>"
                                            data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                            data-subject-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                            data-credits="<?php echo htmlspecialchars($subject['credits']); ?>">
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['credits']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm add-subject">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
    let nextIndex = <?php echo count($assigned_schedules); ?>; // ใช้สำหรับกำหนด index ของ input name
    const assignedSubjectsTableBody = document.getElementById('assignedSubjectsTableBody');
    const availableSubjectsTableBody = document.getElementById('availableSubjectsTableBody');
    const subjectSearch = document.getElementById('subjectSearch');
    const presetSelect = document.getElementById('presetSelect');
    const importPresetBtn = document.getElementById('importPresetBtn');

    // สร้าง Teacher Dropdown Options HTML String
    const teacherOptionsHtml = `
        <option value="">ไม่ระบุ</option>
        <?php foreach ($teachers as $teacher): ?>
            <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo htmlspecialchars($teacher['fullname']); ?></option>
        <?php endforeach; ?>
    `;

    // Function to check if a subject is already in the assigned list (by subject_id)
    function isSubjectAssigned(subjectId) {
        const rows = assignedSubjectsTableBody.querySelectorAll('tr');
        for (const row of rows) {
            const hiddenInput = row.querySelector(`input[name$="[subject_id]"]`);
            if (hiddenInput && parseInt(hiddenInput.value) === parseInt(subjectId)) {
                return true;
            }
        }
        return false;
    }

    // Function to add a subject row to the assigned subjects table
    function addSubjectToAssignedList(subject) {
        if (isSubjectAssigned(subject.subject_id)) {
            // alert('วิชา ' + subject.subject_name + ' ถูกเพิ่มแล้ว'); // อาจจะแสดงหรือไม่แสดงก็ได้ ถ้าเป็น import preset
            return; // ไม่เพิ่มถ้ามีอยู่แล้ว
        }

        const newRowHtml = `
            <tr id="scheduleRow_${subject.subject_id}_${nextIndex}">
                <td>
                    ${subject.subject_code}
                    <input type="hidden" name="schedules[${nextIndex}][subject_id]" value="${subject.subject_id}">
                    <input type="hidden" name="schedules[${nextIndex}][subject_name]" value="${subject.subject_name}">
                    <input type="hidden" name="schedules[${nextIndex}][credits]" value="${subject.credits || '0'}">
                </td>
                <td>${subject.subject_name}</td>
                <td>${subject.credits || '0'}</td>
                <td>
                    <select name="schedules[${nextIndex}][teacher_id]" class="form-select form-select-sm">
                        ${teacherOptionsHtml}
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-subject" data-row-id="scheduleRow_${subject.subject_id}_${nextIndex}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        // ถ้าตารางว่างเปล่า ให้ลบแถว "ยังไม่มีวิชา" ออกก่อน
        const noSubjectsRow = assignedSubjectsTableBody.querySelector('tr td[colspan="5"]');
        if (noSubjectsRow) {
            noSubjectsRow.closest('tr').remove();
        }

        assignedSubjectsTableBody.insertAdjacentHTML('beforeend', newRowHtml);
        nextIndex++;
    }

    // Function to add a subject to the assigned list (from available subjects table)
    availableSubjectsTableBody.addEventListener('click', function(event) {
        if (event.target.classList.contains('add-subject') || event.target.closest('.add-subject')) {
            const button = event.target.closest('.add-subject');
            const row = button.closest('tr');
            const subject = {
                subject_id: row.dataset.subjectId,
                subject_code: row.dataset.subjectCode,
                subject_name: row.dataset.subjectName,
                credits: row.dataset.credits
            };
            addSubjectToAssignedList(subject);
        }
    });

    // Function to remove a subject from the assigned list
    assignedSubjectsTableBody.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-subject') || event.target.closest('.remove-subject')) {
            const button = event.target.closest('.remove-subject');
            const rowId = button.dataset.rowId;
            const rowToRemove = document.getElementById(rowId);
            if (rowToRemove) {
                rowToRemove.remove();
            }
            // ถ้าไม่มีวิชาเหลือในตารางแล้ว ให้เพิ่มแถว "ยังไม่มีวิชา" กลับเข้าไป
            if (assignedSubjectsTableBody.children.length === 0) {
                assignedSubjectsTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">ยังไม่มีวิชาที่กำหนดไว้สำหรับคลาสและปีการศึกษานี้</td>
                    </tr>
                `;
            }
        }
    });

    // Search functionality for available subjects
    subjectSearch.addEventListener('keyup', function() {
        const searchText = subjectSearch.value.toLowerCase();
        const rows = availableSubjectsTableBody.querySelectorAll('tr');

        rows.forEach(row => {
            // ตรวจสอบว่ามี dataset attribute หรือไม่ ก่อนเข้าถึง
            const subjectCode = row.dataset.subjectCode ? row.dataset.subjectCode.toLowerCase() : '';
            const subjectName = row.dataset.subjectName ? row.dataset.subjectName.toLowerCase() : '';

            if (subjectCode.includes(searchText) || subjectName.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Preset Selection and Import Logic
    presetSelect.addEventListener('change', function() {
        if (this.value) {
            importPresetBtn.disabled = false;
        } else {
            importPresetBtn.disabled = true;
        }
    });

    importPresetBtn.addEventListener('click', function() {
        const presetId = presetSelect.value;
        if (!presetId) {
            alert('กรุณาเลือกชุดวิชาที่ต้องการนำเข้า');
            return;
        }

        // Disable button to prevent multiple clicks
        importPresetBtn.disabled = true;
        importPresetBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังนำเข้า...';

        fetch(`api/get_preset_subjects.php?preset_id=${presetId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(subject => {
                            // API returns subject_id, subject_code, subject_name. Need credits for display.
                            // Find credits from the global subjects array or fetch if not present.
                            const fullSubjectData = <?php echo json_encode($subjects); ?>.find(s => s.subject_id == subject.subject_id);

                            addSubjectToAssignedList({
                                subject_id: subject.subject_id,
                                subject_code: subject.subject_code,
                                subject_name: subject.subject_name,
                                credits: fullSubjectData ? fullSubjectData.credits : 'N/A' // Use 'N/A' if credits not found
                            });
                        });
                        alert('นำเข้าวิชาจากชุดวิชาสำเร็จ! กรุณากด "บันทึกวิชา" เพื่อยืนยัน');
                    } else {
                        alert(data.message || 'ไม่พบวิชาในชุดวิชาที่เลือก');
                    }
                } else {
                    alert('เกิดข้อผิดพลาดในการนำเข้าชุดวิชา: ' + (data.message || ''));
                }
            })
            .catch(error => {
                console.error('Error fetching preset subjects:', error);
                alert('เกิดข้อผิดพลาดในการนำเข้าชุดวิชา: ' + error.message);
            })
            .finally(() => {
                importPresetBtn.disabled = false;
                importPresetBtn.innerHTML = '<i class="fas fa-file-import me-2"></i>นำเข้าชุดวิชา';
                presetSelect.value = ""; // Reset dropdown
            });
    });

    // Form submission confirmation (optional but recommended)
    document.getElementById('manageSubjectsForm').addEventListener('submit', function(event) {
        // Basic check to ensure there's at least one subject if needed
        const noSubjectsRow = assignedSubjectsTableBody.querySelector('tr td[colspan="5"]');
        if (noSubjectsRow) { // If "No subjects" message is present
            if (!confirm('คุณยังไม่ได้เพิ่มวิชาใดๆ ต้องการบันทึกโดยไม่มีวิชาหรือไม่?')) {
                event.preventDefault(); // Stop form submission
                return;
            }
        }
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; // Include footer ?>