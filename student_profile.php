<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_student_loggedin()) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = get_session_message();

// --- จัดการการอัปเดตข้อมูล (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... โค้ด PHP ส่วนนี้เหมือนเดิมทุกประการ ไม่ต้องแก้ไข ...
    $prefix = $_POST['prefix'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $new_password = $_POST['new_password'];
    $new_image_path = null;
    $errors = [];

    if (empty($first_name)) $errors[] = "กรุณากรอกชื่อจริง";
    if (empty($last_name)) $errors[] = "กรุณากรอกนามสกุล";

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $upload_dir = 'uploads/student_profiles/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'student_' . $student_id . '_' . uniqid() . '.' . $file_extension;
            $new_image_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $new_image_path)) {
                $errors[] = "ไม่สามารถอัปโหลดรูปภาพได้";
                $new_image_path = null;
            }
        } else {
            $errors[] = "รูปแบบไฟล์รูปภาพไม่ถูกต้อง (อนุญาตเฉพาะ jpg, png, gif)";
        }
    }

    if (empty($errors)) {
        $old_image_path_stmt = $mysqli->prepare("SELECT profile_image FROM students WHERE student_id = ?");
        $old_image_path_stmt->bind_param("i", $student_id);
        $old_image_path_stmt->execute();
        $old_image_path = $old_image_path_stmt->get_result()->fetch_column();
        $old_image_path_stmt->close();

        $sql_parts = [];
        $params = [];
        $types = '';
        $sql_parts[] = "prefix = ?"; $params[] = $prefix; $types .= 's';
        $sql_parts[] = "first_name = ?"; $params[] = $first_name; $types .= 's';
        $sql_parts[] = "last_name = ?"; $params[] = $last_name; $types .= 's';
        if ($new_image_path) { $sql_parts[] = "profile_image = ?"; $params[] = $new_image_path; $types .= 's'; }
        if (!empty($new_password)) {
            $sql_parts[] = "password = ?"; 
            $params[] = password_hash($new_password, PASSWORD_DEFAULT); 
            $types .= 's';
        }
        $params[] = $student_id; $types .= 'i';
        $sql = "UPDATE students SET " . implode(', ', $sql_parts) . " WHERE student_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            if ($new_image_path && !empty($old_image_path) && file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
            $_SESSION['student_name'] = $prefix . $first_name . ' ' . $last_name;
            set_session_message('อัปเดตข้อมูลส่วนตัวสำเร็จ!', 'success');
        } else {
            set_session_message('เกิดข้อผิดพลาดในการอัปเดต: ' . $stmt->error, 'danger');
        }
        $stmt->close();
        header("Location: student_profile.php");
        exit();
    } else {
        set_session_message(implode('<br>', $errors), 'danger');
    }
}

// --- ดึงข้อมูลนักเรียนล่าสุดมาแสดงผล ---
$stmt_info = $mysqli->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt_info->bind_param("i", $student_id);
$stmt_info->execute();
$student_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();


$page_title = "ข้อมูลส่วนตัว";
require_once 'includes/student_header.php';
require_once 'includes/student_sidebar.php';
?>
<style>
    /* CSS Animations and Base Styles (เหมือนใน Dashboard) */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animation-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
    .content-wrapper { background-color: #f4f7f6; }

    /* Custom Styles for Profile Page */
    .profile-display-card {
        text-align: center;
        border: none;
        background: white;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .profile-display-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }
    .profile-display-card .profile-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-top: -60px; /* ทำให้รูปคร่อมขอบบนของการ์ด */
        background-color: #e9ecef;
    }
    .card-header-banner {
        height: 100px;
        background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
        border-radius: .375rem .375rem 0 0;
    }

    .form-card {
        border: none;
        background: white;
        transition: box-shadow 0.3s ease;
    }
    .form-card:hover {
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .form-control, .form-select {
        border-radius: .5rem;
        border: 1px solid #dee2e6;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #43cea2;
        box-shadow: 0 0 0 0.25rem rgba(67, 206, 162, 0.25);
    }
    .btn-primary {
        background-color: #185a9d;
        border-color: #185a9d;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #43cea2;
        border-color: #43cea2;
        transform: translateY(-2px);
    }
</style>

<div class="content-wrapper">
    <main class="p-md-4 p-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0 fw-bold"><i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="student_dashboard.php" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex"><i class="fas fa-arrow-left me-2"></i>กลับแดชบอร์ด</a>
            </div>
            
            <?php echo $message; // แสดงข้อความแจ้งเตือน ?>

            <div class="row gy-4">
                <div class="col-lg-4">
                    <div class="card profile-display-card animation-fadeInUp">
                        <div class="card-header-banner"></div>
                        <div class="card-body pt-0">
                            <img src="<?php echo !empty($student_info['profile_image']) ? htmlspecialchars($student_info['profile_image']) : 'assets/images/default_avatar.png'; ?>" 
                                 class="rounded-circle profile-image" alt="Profile Picture">
                            <h4 class="mt-3 mb-1 fw-bold"><?php echo htmlspecialchars($student_info['prefix'] . $student_info['first_name'] . ' ' . $student_info['last_name']); ?></h4>
                            <p class="text-muted mb-1">Username: <?php echo htmlspecialchars($student_info['username']); ?></p>
                            <p class="text-muted">รหัสนักเรียน: <?php echo htmlspecialchars($student_info['student_code']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card form-card animation-fadeInUp" style="animation-delay: 0.1s;">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2 text-primary"></i>แก้ไขข้อมูลส่วนตัว</h5>
                        </div>
                        <div class="card-body">
                            <form action="student_profile.php" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="prefix" class="form-label">คำนำหน้า</label>
                                        <select name="prefix" id="prefix" class="form-select">
                                            <option value="ด.ช." <?php if($student_info['prefix'] == 'ด.ช.') echo 'selected'; ?>>ด.ช.</option>
                                            <option value="ด.ญ." <?php if($student_info['prefix'] == 'ด.ญ.') echo 'selected'; ?>>ด.ญ.</option>
                                            <option value="นาย" <?php if($student_info['prefix'] == 'นาย') echo 'selected'; ?>>นาย</option>
                                            <option value="น.ส." <?php if($student_info['prefix'] == 'น.ส.') echo 'selected'; ?>>น.ส.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label for="first_name" class="form-label">ชื่อจริง</label>
                                        <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($student_info['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                         <label for="last_name" class="form-label">นามสกุล</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo htmlspecialchars($student_info['last_name']); ?>" required>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">เปลี่ยนรูปโปรไฟล์</label>
                                    <input type="file" name="profile_image" id="profile_image" class="form-control">
                                    <small class="form-text text-muted">ขนาดที่แนะนำ 300x300px (ไฟล์ .jpg, .png, .gif)</small>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">รหัสผ่านใหม่ (กรอกหากต้องการเปลี่ยน)</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••">
                                </div>
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/student_footer.php'; ?>