<?php
session_start();
require_once 'config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

// --- ดึงข้อมูลสำหรับ Dropdowns ---
// ดึงข้อมูลวิชาทั้งหมดสำหรับใช้ใน Multi-select
$all_subjects = $mysqli->query("SELECT subject_id, subject_name, subject_code FROM subjects ORDER BY subject_code ASC");

// --- กำหนดค่าเริ่มต้น ---
$preset_name = '';
$selected_subjects = [];
$errors = [];

// --- จัดการการ POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_preset':
                $preset_name = trim($_POST['preset_name'] ?? '');
                $selected_subjects = $_POST['selected_subjects'] ?? []; // Array ของ subject_id

                // Validation
                if (empty($preset_name)) {
                    $errors['preset_name'] = 'กรุณากรอกชื่อชุดวิชา';
                } elseif (!is_value_unique('subject_presets', 'preset_name', $preset_name, null, 'preset_id')) {
                    $errors['preset_name'] = 'ชื่อชุดวิชานี้มีอยู่แล้ว';
                }
                if (empty($selected_subjects)) {
                    $errors['selected_subjects'] = 'กรุณาเลือกอย่างน้อย 1 วิชาสำหรับชุดวิชานี้';
                }

                if (empty($errors)) {
                    $mysqli->begin_transaction();
                    try {
                        // 1. บันทึกชื่อชุดวิชาลงในตาราง subject_presets
                        $sql_preset = "INSERT INTO subject_presets (preset_name) VALUES (?)";
                        $stmt_preset = $mysqli->prepare($sql_preset);
                        $stmt_preset->bind_param("s", $preset_name);
                        $stmt_preset->execute();
                        
                        $new_preset_id = $mysqli->insert_id;
                        if ($new_preset_id == 0) throw new Exception("ไม่สามารถสร้างชุดวิชาหลักได้");

                        // 2. บันทึกวิชาที่เลือกสำหรับชุดวิชานี้ลงในตาราง preset_subjects
                        $sql_preset_subject = "INSERT INTO preset_subjects (preset_id, subject_id) VALUES (?, ?)";
                        $stmt_preset_subject = $mysqli->prepare($sql_preset_subject);

                        foreach ($selected_subjects as $subject_id) {
                            if (is_numeric($subject_id)) {
                                $stmt_preset_subject->bind_param("ii", $new_preset_id, $subject_id);
                                $stmt_preset_subject->execute();
                            }
                        }
                        $stmt_preset_subject->close();
                        $stmt_preset->close();

                        $mysqli->commit();
                        set_session_message('สร้างชุดวิชาสำเร็จ!', 'success');
                        header("Location: subject_presets_manage.php");
                        exit();

                    } catch (Exception $e) {
                        $mysqli->rollback();
                        set_session_message('เกิดข้อผิดพลาดในการสร้างชุดวิชา: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    set_session_message('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'warning');
                }
                break;

            case 'delete_preset':
                $preset_id_to_delete = $_POST['preset_id'] ?? null;
                if ($preset_id_to_delete && is_numeric($preset_id_to_delete)) {
                    // --- เพิ่มการตรวจสอบว่าชุดวิชานั้นมีอยู่จริงหรือไม่ ---
                    $check_sql = "SELECT COUNT(*) FROM subject_presets WHERE preset_id = ?";
                    $check_stmt = $mysqli->prepare($check_sql);
                    if ($check_stmt) {
                        $check_stmt->bind_param("i", $preset_id_to_delete);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result()->fetch_row();
                        $exists = $check_result[0] > 0;
                        $check_stmt->close();
                    } else {
                        set_session_message('เกิดข้อผิดพลาดในการตรวจสอบชุดวิชา: ' . $mysqli->error, 'danger');
                        header("Location: subject_presets_manage.php");
                        exit();
                    }
                    
                    if (!$exists) {
                        set_session_message('ไม่พบชุดวิชาที่ต้องการลบ (ID: ' . htmlspecialchars($preset_id_to_delete) . ') ในระบบ!', 'warning');
                        header("Location: subject_presets_manage.php");
                        exit();
                    }
                    // --- สิ้นสุดการตรวจสอบ ---

                    $mysqli->begin_transaction();
                    try {
                        // การลบ preset_subjects จะถูกจัดการโดย CASCADE DELETE (ถ้าตั้งค่า FK ไว้)
                        $sql_delete = "DELETE FROM subject_presets WHERE preset_id = ?";
                        $stmt_delete = $mysqli->prepare($sql_delete);
                        $stmt_delete->bind_param("i", $preset_id_to_delete);
                        $stmt_delete->execute();
                        
                        // ตรวจสอบ affected_rows จาก stmt_delete โดยตรง
                        if ($stmt_delete->affected_rows > 0) {
                            $mysqli->commit();
                            set_session_message('ลบชุดวิชาสำเร็จ!', 'success');
                        } else {
                            $mysqli->rollback();
                            set_session_message('ไม่สามารถลบชุดวิชาได้: ไม่มีการเปลี่ยนแปลงเกิดขึ้น!', 'warning');
                        }
                        $stmt_delete->close();
                        header("Location: subject_presets_manage.php");
                        exit();
                    } catch (Exception $e) {
                        $mysqli->rollback();
                        set_session_message('เกิดข้อผิดพลาดในการลบชุดวิชา: ' . $e->getMessage(), 'danger');
                        header("Location: subject_presets_manage.php"); // Ensure redirect on error
                        exit();
                    }
                } else {
                    set_session_message('ไม่สามารถลบชุดวิชาได้: ID ไม่ถูกต้องหรือไม่ระบุ', 'danger');
                    header("Location: subject_presets_manage.php"); // Ensure redirect on error
                    exit();
                }
                break;
        }
    }
}

// --- ดึงข้อมูลชุดวิชาที่มีอยู่แล้วเพื่อแสดงผล ---
$presets_query = "
    SELECT 
        sp.preset_id, 
        sp.preset_name,
        GROUP_CONCAT(s.subject_code ORDER BY s.subject_code ASC SEPARATOR ', ') AS subject_codes_list
    FROM 
        subject_presets sp
    LEFT JOIN 
        preset_subjects ps ON sp.preset_id = ps.preset_id
    LEFT JOIN 
        subjects s ON ps.subject_id = s.subject_id
    GROUP BY 
        sp.preset_id, sp.preset_name
    ORDER BY 
        sp.preset_name ASC
";
$existing_presets = $mysqli->query($presets_query);

$page_title = "จัดการชุดวิชา (Templates)";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-cubes me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            
            <?php echo get_session_message(); ?>
            
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-plus-circle me-2"></i>เพิ่มชุดวิชาใหม่</h5></div>
                <div class="card-body">
                    <form action="subject_presets_manage.php" method="post" novalidate>
                        <input type="hidden" name="action" value="add_preset">
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
                                        <?php echo in_array($subject['subject_id'], $selected_subjects) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">กด Ctrl (หรือ Cmd บน Mac) ค้างไว้เพื่อเลือกหลายวิชา</div>
                            <div class="invalid-feedback"><?php echo $errors['selected_subjects'] ?? ''; ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกชุดวิชา</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="fas fa-list me-2"></i>รายการชุดวิชาที่มีอยู่</h5></div>
                <div class="card-body">
                    <?php if ($existing_presets->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>ชื่อชุดวิชา</th>
                                        <th>รายวิชาที่อยู่ในชุด</th>
                                        <th style="width: 15%;">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($preset = $existing_presets->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($preset['preset_id']); ?></td>
                                            <td><?php echo htmlspecialchars($preset['preset_name']); ?></td>
                                            <td><?php echo htmlspecialchars($preset['subject_codes_list'] ?? 'ยังไม่มีวิชา'); ?></td>
                                            <td>
                                                <a href="subject_preset_edit.php?id=<?php echo $preset['preset_id']; ?>" class="btn btn-warning btn-sm me-1" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                                <form action="subject_presets_manage.php" method="post" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบชุดวิชา \'<?php echo htmlspecialchars($preset['preset_name']); ?>\'?');">
                                                    <input type="hidden" name="action" value="delete_preset">
                                                    <input type="hidden" name="preset_id" value="<?php echo $preset['preset_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="ลบ"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            ยังไม่มีชุดวิชาที่ถูกบันทึกไว้.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>