<?php
session_start();
require_once 'config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/admin_functions.php'; // Include ฟังก์ชันสำหรับ Admin

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$page_title = "แก้ไขชุดวิชา";

// --- ดึงข้อมูลสำหรับ Dropdowns และ Preset ที่จะแก้ไข ---
$all_subjects = $mysqli->query("SELECT subject_id, subject_name, subject_code FROM subjects ORDER BY subject_code ASC");

$preset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preset_name = '';
$current_selected_subjects = []; // Array of subject_ids currently in this preset
$errors = [];

// ตรวจสอบว่ามี preset_id ที่ถูกต้องหรือไม่
if ($preset_id <= 0) {
    set_session_message('ไม่พบ ID ชุดวิชาที่ต้องการแก้ไข', 'danger');
    header("Location: subject_presets_manage.php");
    exit();
}

// ดึงข้อมูลชุดวิชาปัจจุบัน
$sql_preset = "SELECT preset_id, preset_name FROM subject_presets WHERE preset_id = ?";
if ($stmt_preset = $mysqli->prepare($sql_preset)) {
    $stmt_preset->bind_param("i", $preset_id);
    $stmt_preset->execute();
    $result_preset = $stmt_preset->get_result();
    if ($result_preset->num_rows === 0) {
        set_session_message('ไม่พบชุดวิชาที่ระบุ', 'danger');
        header("Location: subject_presets_manage.php");
        exit();
    }
    $current_preset_data = $result_preset->fetch_assoc();
    $preset_name = $current_preset_data['preset_name'];
    $stmt_preset->close();
} else {
    set_session_message("เกิดข้อผิดพลาดในการดึงข้อมูลชุดวิชาหลัก: " . $mysqli->error, 'danger');
    header("Location: subject_presets_manage.php");
    exit();
}

// ดึงวิชาทั้งหมดที่อยู่ในชุดวิชานี้
$sql_preset_subjects = "SELECT subject_id FROM preset_subjects WHERE preset_id = ?";
if ($stmt_ps = $mysqli->prepare($sql_preset_subjects)) {
    $stmt_ps->bind_param("i", $preset_id);
    $stmt_ps->execute();
    $result_ps = $stmt_ps->get_result();
    while ($row = $result_ps->fetch_assoc()) {
        $current_selected_subjects[] = $row['subject_id'];
    }
    $stmt_ps->close();
} else {
    set_session_message("เกิดข้อผิดพลาดในการดึงรายวิชาในชุด: " . $mysqli->error, 'danger');
}


// --- จัดการการ POST เพื่ออัปเดตชุดวิชา ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preset_name = trim($_POST['preset_name'] ?? '');
    $selected_subjects = $_POST['selected_subjects'] ?? []; // Array ของ subject_id

    // Validation
    if (empty($preset_name)) {
        $errors['preset_name'] = 'กรุณากรอกชื่อชุดวิชา';
    } elseif (!is_value_unique('subject_presets', 'preset_name', $preset_name, $preset_id, 'preset_id')) {
        $errors['preset_name'] = 'ชื่อชุดวิชานี้มีอยู่แล้ว';
    }
    if (empty($selected_subjects)) {
        $errors['selected_subjects'] = 'กรุณาเลือกอย่างน้อย 1 วิชาสำหรับชุดวิชานี้';
    }

    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            // 1. อัปเดตชื่อชุดวิชาในตาราง subject_presets
            $sql_update_preset = "UPDATE subject_presets SET preset_name = ? WHERE preset_id = ?";
            $stmt_update_preset = $mysqli->prepare($sql_update_preset);
            $stmt_update_preset->bind_param("si", $preset_name, $preset_id);
            $stmt_update_preset->execute();
            $stmt_update_preset->close();

            // 2. ลบวิชาเก่าทั้งหมดที่อยู่ในชุดวิชานี้จากตาราง preset_subjects
            $sql_delete_subjects = "DELETE FROM preset_subjects WHERE preset_id = ?";
            $stmt_delete_subjects = $mysqli->prepare($sql_delete_subjects);
            $stmt_delete_subjects->bind_param("i", $preset_id);
            $stmt_delete_subjects->execute();
            $stmt_delete_subjects->close();

            // 3. เพิ่มวิชาใหม่ที่เลือกสำหรับชุดวิชานี้ลงในตาราง preset_subjects
            $sql_insert_subject = "INSERT INTO preset_subjects (preset_id, subject_id) VALUES (?, ?)";
            $stmt_insert_subject = $mysqli->prepare($sql_insert_subject);

            foreach ($selected_subjects as $subject_id) {
                if (is_numeric($subject_id)) {
                    $stmt_insert_subject->bind_param("ii", $preset_id, $subject_id);
                    $stmt_insert_subject->execute();
                }
            }
            $stmt_insert_subject->close();

            $mysqli->commit();
            set_session_message('แก้ไขชุดวิชาสำเร็จ!', 'success');
            header("Location: subject_presets_manage.php");
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            set_session_message('เกิดข้อผิดพลาดในการแก้ไขชุดวิชา: ' . $e->getMessage(), 'danger');
        }
    } else {
        set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
    }
}


require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            
            <?php echo get_session_message(); ?>
            
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-info-circle me-2"></i>แก้ไขชุดวิชา: <?php echo htmlspecialchars($current_preset_data['preset_name']); ?></h5></div>
                <div class="card-body">
                    <form action="subject_preset_edit.php?id=<?php echo $preset_id; ?>" method="post" novalidate>
                        <input type="hidden" name="preset_id" value="<?php echo $preset_id; ?>">
                        <div class="mb-3">
                            <label for="preset_name" class="form-label">ชื่อชุดวิชา <span class="text-danger">*</span></label>
                            <input type="text" name="preset_name" id="preset_name" class="form-control <?php echo isset($errors['preset_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($preset_name); ?>" required>
                            <div class="invalid-feedback"><?php echo $errors['preset_name'] ?? ''; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="selected_subjects" class="form-label">เลือกวิชาที่ต้องการ <span class="text-danger">*</span></label>
                            <select name="selected_subjects[]" id="selected_subjects" class="form-select <?php echo isset($errors['selected_subjects']) ? 'is-invalid' : ''; ?>" multiple size="10" required>
                                <?php $all_subjects->data_seek(0); // Reset pointer for dropdown ?>
                                <?php while ($subject = $all_subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>"
                                        <?php echo in_array($subject['subject_id'], $current_selected_subjects) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">กด Ctrl (หรือ Cmd บน Mac) ค้างไว้เพื่อเลือกหลายวิชา</div>
                            <div class="invalid-feedback"><?php echo $errors['selected_subjects'] ?? ''; ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกการแก้ไข</button>
                        <a href="subject_presets_manage.php" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left me-2"></i>กลับ</a>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // ต้องแน่ใจว่าได้รวม jQuery และ Select2 JS/CSS ใน admin_header/footer.php แล้ว
    $('#selected_subjects').select2({
        placeholder: "ค้นหาและเลือกวิชา...",
        allowClear: true,
        width: 'resolve'
    });
});
</script>