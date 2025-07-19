<?php
session_start();
require_once 'config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล

// (ส่วน PHP ด้านบนยังคงเหมือนเดิมทุกประการ)
// ตรวจสอบว่า Admin ล็อกอินอยู่หรือไม่ และมีบทบาทเป็น Admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // ถ้าไม่ใช่ Admin ให้กลับไปหน้า Login
    exit();
}

$username = $password = $fullname = $email = "";
$username_err = $password_err = $fullname_err = $email_err = "";
$message = ""; // สำหรับแสดงข้อความแจ้งเตือน

// ประมวลผลเมื่อมีการส่งฟอร์ม (เมื่อกดปุ่มบันทึก)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {

    // 1. ตรวจสอบข้อมูลรับเข้า
    // ตรวจสอบ Username
    if (empty(trim($_POST["username"]))) {
        $username_err = "กรุณากรอก Username.";
    } else {
        // ตรวจสอบว่า Username ซ้ำหรือไม่
        $sql = "SELECT admin_id FROM admins WHERE username = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $username_err = "Username นี้ถูกใช้งานแล้ว.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "เกิดข้อผิดพลาดในการตรวจสอบ Username.";
            }
            $stmt->close();
        }
    }

    // ตรวจสอบ Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "กรุณากรอกรหัสผ่าน.";
    } else {
        $password = $_POST["password"]; // ยังไม่ Hash ในขั้นตอนนี้
    }

    // ตรวจสอบ Fullname
    if (empty(trim($_POST["fullname"]))) {
        $fullname_err = "กรุณากรอกชื่อ-นามสกุล.";
    } else {
        $fullname = trim($_POST["fullname"]);
    }

    // ตรวจสอบ Email (ไม่บังคับ)
    $email = trim($_POST["email"]);

    // 2. ถ้าไม่มี Error จากการตรวจสอบเบื้องต้น ให้บันทึกข้อมูล
    if (empty($username_err) && empty($password_err) && empty($fullname_err)) {
        // Hash รหัสผ่านก่อนบันทึกลงฐานข้อมูล
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // เตรียมคำสั่ง SQL สำหรับ INSERT
        $sql = "INSERT INTO admins (username, password, fullname, email) VALUES (?, ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssss", $username, $hashed_password, $fullname, $email);

            // รันคำสั่ง SQL
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">เพิ่มข้อมูล Admin ใหม่สำเร็จ!</div>';
                // เคลียร์ฟอร์มหลังจากเพิ่มสำเร็จ
                $username = $password = $fullname = $email = "";
            } else {
                $message = '<div class="alert alert-danger" role="alert">เกิดข้อผิดพลาดในการเพิ่มข้อมูล Admin: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
    if (isset($mysqli)) $mysqli->close(); // ปิดการเชื่อมต่อฐานข้อมูล
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่ม Admin - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #f0f2f5;
        }

        .sidebar {
            background-color: #2c3e50;
            color: #ecf0f1;
        }

        .sidebar .list-group-item {
            background-color: transparent;
            color: #ecf0f1;
            border: none;
            border-left: 4px solid transparent;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
        }

        .sidebar .list-group-item:hover,
        .sidebar .list-group-item.active {
            background-color: #34495e;
            color: #ffffff;
            border-left-color: #3498db;
        }

        /* Responsive Layout */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-wrapper {
            width: 280px;
            transition: margin-left 0.3s ease;
        }

        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* สำหรับจอเล็ก (Mobile) */
        @media (max-width: 991.98px) {
            .sidebar-wrapper {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 1050;
                /* สูงกว่า navbar */
                margin-left: -280px;
                /* ซ่อนไว้ด้านซ้าย */
            }

            .content-wrapper.sidebar-toggled .sidebar-wrapper {
                margin-left: 0;
                /* แสดง sidebar */
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <div class="sidebar-wrapper sidebar p-3">
            <h4 class="sidebar-heading mb-4 text-center">
                <i class="fas fa-book-reader me-2"></i>Metta Academic
            </h4>
            <div class="list-group list-group-flush">
                <a href="admin_menu.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt fa-fw me-2"></i>แดชบอร์ด</a>
                <a href="admin_list.php" class="list-group-item list-group-item-action active"><i class="fas fa-user-shield fa-fw me-2"></i>จัดการข้อมูล Admin</a>
                <a href="teacher_list.php" class="list-group-item list-group-item-action"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i>จัดการข้อมูลครู</a>
                <a href="student_list.php" class="list-group-item list-group-item-action"><i class="fas fa-user-graduate fa-fw me-2"></i>จัดการข้อมูลนักเรียน</a>
                <a href="subject_list.php" class="list-group-item list-group-item-action"><i class="fas fa-book fa-fw me-2"></i>จัดการวิชา</a>
                <a href="class_list.php" class="list-group-item list-group-item-action"><i class="fas fa-school fa-fw me-2"></i>จัดการคลาสเรียน</a>
                <a href="academic_years.php" class="list-group-item list-group-item-action"><i class="fas fa-calendar-alt fa-fw me-2"></i>จัดการปีการศึกษา</a>
            </div>
            <div class="mt-auto p-3">
                <a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a>
            </div>
        </div>

        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-light d-lg-none" type="button" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="navbar-brand mb-0 ms-2">เพิ่มข้อมูล Admin ใหม่</h5>
                    <a href="admin_list.php" class="btn btn-secondary ms-auto">
                        <i class="fas fa-arrow-alt-circle-left me-2"></i>ย้อนกลับ
                    </a>
                </div>
            </nav>

            <main class="flex-grow-1 p-4">
                <div class="container-fluid">
                    <?php echo $message; ?>
                    <div class="card">
                        <div class="card-body p-4">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="fullname" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" id="fullname" class="form-control <?php echo (!empty($fullname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fullname); ?>" required>
                                    <div class="invalid-feedback"><?php echo $fullname_err; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">อีเมล</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                                    <small class="form-text text-muted">ไม่จำเป็นต้องกรอก</small>
                                </div>
                                <hr class="my-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="admin_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                                    <button type="submit" name="add_admin" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>บันทึกข้อมูล
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script สำหรับเปิด-ปิด sidebar บนมือถือ
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.main-wrapper').classList.toggle('sidebar-toggled');
        });
    </script>
</body>

</html>