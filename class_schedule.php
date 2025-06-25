<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin() && !isset($_SESSION['teacher_loggedin'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี class_id ส่งมาหรือไม่
if (!isset($_GET['class_id']) || !filter_var($_GET['class_id'], FILTER_VALIDATE_INT)) {
    header("Location: class_list.php");
    exit();
}
$class_id = $_GET['class_id'];

// --- จัดการการบันทึกตารางสอน ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_subject'])) {
    $schedule_subjects = $_POST['schedule_subject'];
    $schedule_teachers = $_POST['schedule_teacher'] ?? [];

    $mysqli->begin_transaction();
    try {
        $stmt_delete = $mysqli->prepare("DELETE FROM class_schedules WHERE class_id = ?");
        $stmt_delete->bind_param("i", $class_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        $sql_insert = "INSERT INTO class_schedules (class_id, day_of_week, period, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $mysqli->prepare($sql_insert);

        foreach ($schedule_subjects as $day => $periods) {
            foreach ($periods as $period => $subject_id) {
                if (!empty($subject_id)) {
                    $teacher_id = $schedule_teachers[$day][$period] ?? null;
                    $teacher_id = !empty($teacher_id) ? $teacher_id : null; 
                    $stmt_insert->bind_param("iiiii", $class_id, $day, $period, $subject_id, $teacher_id);
                    $stmt_insert->execute();
                }
            }
        }
        $stmt_insert->close();
        $mysqli->commit();
        set_session_message('บันทึกตารางสอนเรียบร้อยแล้ว!', 'success');
    } catch (Exception $e) {
        $mysqli->rollback();
        set_session_message('เกิดข้อผิดพลาด: ' . $e->getMessage(), 'danger');
    }
    header("Location: class_schedule.php?class_id=" . $class_id);
    exit();
}


// --- ดึงข้อมูลสำหรับแสดงผล ---
$stmt_class = $mysqli->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt_class->bind_param("i", $class_id);
$stmt_class->execute();
$class_info = $stmt_class->get_result()->fetch_assoc();
$stmt_class->close();
if (!$class_info) { header("Location: class_list.php"); exit(); }

$subjects_result = $mysqli->query("SELECT subject_id, subject_name, subject_code FROM subjects ORDER BY subject_code ASC");
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);

$teachers_result = $mysqli->query("SELECT teacher_id, fullname FROM teachers ORDER BY fullname ASC");
$teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);

$schedules_raw = $mysqli->query("SELECT * FROM class_schedules WHERE class_id = $class_id");
$schedule_map = [];
foreach ($schedules_raw as $s) {
    $schedule_map[$s['day_of_week']][$s['period']] = ['subject_id' => $s['subject_id'], 'teacher_id' => $s['teacher_id']];
}

$page_title = "จัดการตารางสอน";
// ไม่ต้องเรียก Header เพราะเราจะสร้าง HTML ทั้งหมดในไฟล์นี้
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <style>
        body { background-color: #f0f2f5; }
        .sidebar { width: 280px; background-color: #2c3e50; height: 100vh; position: sticky; top: 0; }
        .sidebar .list-group-item { background-color: transparent; color: #ecf0f1; border: none; }
        .sidebar .list-group-item.active, .sidebar .list-group-item:hover { background-color: #34495e; color: #ffffff; }
        .main-content { flex-grow: 1; }
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { height: 31px; padding: .25rem .5rem; display: flex; align-items: center; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { top: 50%; transform: translateY(-50%); }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="sidebar text-white p-3 d-flex flex-column">
        <div>
            <h4 class="mb-4 text-center"><i class="fas fa-book-reader me-2"></i>Metta Academic</h4>
            <div class="list-group list-group-flush">
                 <a href="admin_menu.php" class="list-group-item list-group-item-action text-white"><i class="fas fa-tachometer-alt fa-fw me-2"></i>แดชบอร์ด</a>
                 <a href="class_list.php" class="list-group-item list-group-item-action text-white active"><i class="fas fa-school fa-fw me-2"></i>จัดการคลาสเรียน</a>
                 </div>
        </div>
        <div class="mt-auto"><a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <main class="p-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-table me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการ</a>
                </div>
                <?php echo get_session_message(); ?>
                <div class="card mb-4">
                    <div class="card-header"><h4>ตารางสอนสำหรับ: <?php echo htmlspecialchars($class_info['class_name'] . " (" . $class_info['class_code'] . ")"); ?></h4></div>
                    <div class="card-body">
                        <form action="class_schedule.php?class_id=<?php echo $class_id; ?>" method="post">
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="align-middle" style="width: 8%;">คาบที่</th>
                                            <?php foreach(['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์'] as $day_name) echo "<th class='align-middle'>$day_name</th>"; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($period = 1; $period <= 7; $period++): ?>
                                        <tr>
                                            <td class="align-middle"><strong>คาบ <?php echo $period; ?></strong></td>
                                            <?php for ($day = 1; $day <= 5; $day++): ?>
                                            <td class="p-1" style="vertical-align: top;">
                                                <div class="d-flex flex-column gap-1">
                                                    <select name="schedule_subject[<?php echo $day; ?>][<?php echo $period; ?>]" class="form-select form-select-sm select2">
                                                        <option value="">-- วิชา --</option>
                                                        <?php 
                                                        $selected_subject_id = $schedule_map[$day][$period]['subject_id'] ?? null;
                                                        foreach($subjects as $subject): 
                                                            $selected = ($subject['subject_id'] == $selected_subject_id) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $subject['subject_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select name="schedule_teacher[<?php echo $day; ?>][<?php echo $period; ?>]" class="form-select form-select-sm select2">
                                                        <option value="">-- ครู --</option>
                                                         <?php 
                                                            $selected_teacher_id = $schedule_map[$day][$period]['teacher_id'] ?? null;
                                                            foreach($teachers as $teacher): 
                                                                $selected = ($teacher['teacher_id'] == $selected_teacher_id) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($teacher['fullname']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </td>
                                            <?php endfor; ?>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>บันทึกตารางสอน</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: '-- กรุณาเลือก --'
    });
});
</script>

</body>
</html>