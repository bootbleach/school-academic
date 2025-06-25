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

// --- POST Request Handling (เพิ่ม & อัปเดต) - (แก้ไขโครงสร้างตรงนี้) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $year_value = trim($_POST['year'] ?? '');
    
    // 1. ตรวจสอบก่อนว่ากรอกข้อมูลมาหรือไม่
    if (empty($year_value)) {
        set_session_message('กรุณากรอกปีการศึกษา/เทอม', 'danger');
    } else {
        // 2. ถ้ากรอกข้อมูลมาแล้ว ให้ตรวจสอบว่าเป็น action 'add' หรือ 'update'
        if ($action === 'add') {
            if (!is_value_unique('academic_years', 'year', $year_value, null, 'acad_year_id')) {
                set_session_message('ข้อมูลปีการศึกษานี้มีอยู่แล้ว', 'danger');
            } else {
                $sql = "INSERT INTO academic_years (year) VALUES (?)";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("s", $year_value);
                    if ($stmt->execute()) {
                        set_session_message('เพิ่มข้อมูลสำเร็จ!', 'success');
                    }
                    $stmt->close();
                }
            }
        } 
        // **elseif จะต้องต่อกับ if ($action === 'add') แบบนี้**
        elseif ($action === 'update') {
            $id = $_POST['acad_year_id'];
            if (!is_value_unique('academic_years', 'year', $year_value, $id, 'acad_year_id')) {
                 set_session_message('ข้อมูลปีการศึกษานี้มีอยู่แล้ว', 'danger');
            } else {
                $sql = "UPDATE academic_years SET year = ? WHERE acad_year_id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("si", $year_value, $id);
                    if ($stmt->execute()) {
                        set_session_message('อัปเดตข้อมูลสำเร็จ!', 'success');
                    }
                    $stmt->close();
                }
            }
        }
    }
    header("Location: academic_years.php");
    exit();
}

// --- GET Request Handling (ส่วนนี้เหมือนเดิม) ---
$year_to_edit = '';
$id_to_edit = null;
$form_action = 'add';
$button_text = 'เพิ่มข้อมูล';

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql_del = "DELETE FROM academic_years WHERE acad_year_id = ?";
    if ($stmt_del = $mysqli->prepare($sql_del)) {
        $stmt_del->bind_param("i", $id);
        if ($stmt_del->execute()) {
            set_session_message('ลบข้อมูลสำเร็จ!', 'success');
        } else {
            if ($stmt_del->errno == 1451) {
                set_session_message('ไม่สามารถลบได้ เพราะมีการใช้งานอยู่', 'danger');
            } else {
                set_session_message('เกิดข้อผิดพลาดในการลบ', 'danger');
            }
        }
        $stmt_del->close();
    }
    header("Location: academic_years.php");
    exit();
}

if (isset($_GET['edit_id'])) {
    $id_to_edit = $_GET['edit_id'];
    $sql_edit = "SELECT year FROM academic_years WHERE acad_year_id = ?";
    if ($stmt_edit = $mysqli->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $id_to_edit);
        $stmt_edit->execute();
        $result = $stmt_edit->get_result();
        if ($row = $result->fetch_assoc()) {
            $year_to_edit = $row['year'];
            $form_action = 'update';
            $button_text = 'อัปเดตข้อมูล';
        }
        $stmt_edit->close();
    }
}

$academic_years = $mysqli->query("SELECT * FROM academic_years ORDER BY year DESC");

$page_title = "จัดการปีการศึกษา";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-light" type="button" id="sidebarToggleMobile"><i class="fas fa-bars"></i></button>
            <h5 class="navbar-brand mb-0 ms-2"><?php echo htmlspecialchars($page_title); ?></h5>
        </div>
    </nav>
    
    <main class="p-4">
        <div class="container-fluid">
            <h1 class="d-none d-lg-block"><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <hr class="d-none d-lg-block">
            
            <?php if(!empty($message)) echo $message; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-edit me-2"></i><?php echo isset($id_to_edit) ? 'แก้ไข' : 'เพิ่ม'; ?>ปีการศึกษา/เทอม</h5>
                        </div>
                        <div class="card-body">
                            <form action="academic_years.php" method="post">
                                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                                <?php if (isset($id_to_edit)): ?>
                                    <input type="hidden" name="acad_year_id" value="<?php echo $id_to_edit; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="year" class="form-label">ปีการศึกษา/เทอม</label>
                                    <input type="text" name="year" id="year" class="form-control" 
                                           placeholder="เช่น 1/2568" 
                                           value="<?php echo htmlspecialchars($year_to_edit); ?>" required>
                                    <small class="form-text text-muted">รูปแบบ: เทอม/ปีการศึกษา (พ.ศ.)</small>
                                </div>
                                <div class="d-grid gap-2">
                                     <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo $button_text; ?>
                                    </button>
                                    <?php if (isset($id_to_edit)): ?>
                                        <a href="academic_years.php" class="btn btn-secondary">ยกเลิกการแก้ไข</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                         <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>รายการทั้งหมด</h5>
                        </div>
                        <div class="card-body">
                             <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ปีการศึกษา/เทอม</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($academic_years->num_rows > 0): ?>
                                            <?php foreach ($academic_years as $index => $ay): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($ay['year']); ?></td>
                                                    <td class="text-end">
                                                        <a href="academic_years.php?edit_id=<?php echo $ay['acad_year_id']; ?>" class="btn btn-warning btn-sm" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="academic_years.php?delete_id=<?php echo $ay['acad_year_id']; ?>" class="btn btn-danger btn-sm" title="ลบ" onclick="return confirm('ยืนยันการลบ?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-3">ไม่พบข้อมูล</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once 'includes/admin_footer.php'; ?>