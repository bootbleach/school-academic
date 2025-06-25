<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_admin_loggedin() || !isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: class_list.php");
    exit();
}
$class_id = $_GET['id'];

$teachers = $mysqli->query("SELECT teacher_id, fullname FROM teachers ORDER BY fullname ASC");
$academic_years = $mysqli->query("SELECT acad_year_id, year FROM academic_years ORDER BY year DESC");

// ดึงข้อมูลคลาสปัจจุบัน
$stmt_fetch = $mysqli->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt_fetch->bind_param("i", $class_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
if ($result->num_rows === 0) {
    header("Location: class_list.php");
    exit();
}
$class_data_from_db = $result->fetch_assoc(); // ใช้ชื่อตัวแปรที่ชัดเจน
$stmt_fetch->close();

// กำหนดค่าเริ่มต้นสำหรับแสดงในฟอร์ม
$teacher_id = $class_data_from_db['teacher_id'];
$academic_year_id = $class_data_from_db['academic_year_id'];
$class_code = $class_data_from_db['class_code'];
$class_name = $class_data_from_db['class_name'];
$room_number = $class_data_from_db['room_number'];
$max_students = $class_data_from_db['max_students'];
$errors = [];

// จัดการการ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าใหม่จากฟอร์มมาใส่ในตัวแปรเดิม เพื่อให้ฟอร์มจำค่า
    $teacher_id = $_POST['teacher_id'] ?? '';
    $academic_year_id = $_POST['academic_year_id'] ?? '';
    $class_code = trim($_POST['class_code'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');
    $room_number = trim($_POST['room_number'] ?? '');
    $max_students = !empty($_POST['max_students']) ? trim($_POST['max_students']) : null;

    // ... (Validation logic) ...

    if (empty($errors)) {
        $sql = "UPDATE classes SET teacher_id=?, academic_year_id=?, class_code=?, class_name=?, room_number=?, max_students=? WHERE class_id=?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iisssii", $teacher_id, $academic_year_id, $class_code, $class_name, $room_number, $max_students, $class_id);
            if ($stmt->execute()) {
                set_session_message('อัปเดตข้อมูลคลาสเรียนสำเร็จ!', 'success');
                header("Location: class_list.php");
                exit();
            }
        }
    }
}

$page_title = "แก้ไขคลาสเรียน";
require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <main class="p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="class_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>ข้อมูลคลาส: <?php echo htmlspecialchars($class_name); ?></h5>
                </div>
                <div class="card-body p-4">
                    <form action="class_edit.php?id=<?php echo $class_id; ?>" method="post" novalidate>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ชื่อคลาส/ห้องเรียน</label>
                                <input type="text" name="class_name" class="form-control" value="<?php echo htmlspecialchars($class_name); ?>">
                            </div>
                             <div class="col-md-4 mb-3">
                                <label class="form-label">รหัสคลาส</label>
                                <input type="text" name="class_code" class="form-control" value="<?php echo htmlspecialchars($class_code); ?>">
                            </div>
                             <div class="col-md-4 mb-3">
                                <label class="form-label">ปีการศึกษา/เทอม</label>
                                <select name="academic_year_id" class="form-select">
                                     <?php foreach($academic_years as $ay): ?>
                                        <option value="<?php echo $ay['acad_year_id']; ?>" <?php if($academic_year_id == $ay['acad_year_id']) echo 'selected'; ?>><?php echo htmlspecialchars($ay['year']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
             <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> <strong>หมายเหตุ:</strong> การแก้ไขตารางสอนของคลาสนี้ สามารถทำได้ที่หน้า <a href="class_schedule.php?class_id=<?php echo $class_id; ?>" class="alert-link">จัดการตารางสอน</a>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/admin_footer.php'; ?>