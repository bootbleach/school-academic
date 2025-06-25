<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!isset($_SESSION['teacher_loggedin']) || $_SESSION['teacher_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$stmt = $mysqli->prepare("SELECT username, fullname, email, phone FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$page_title = "แดชบอร์ดครู";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background-color: #f0f2f5; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background-color: #2c3e50;
            transition: margin-left 0.3s ease;
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
            border-left-color: #16a085;
        }
        .content-wrapper { flex-grow: 1; }

        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 1050;
                margin-left: -280px;
            }
            .sidebar.toggled {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Navbar บนมือถือ -->
<nav class="navbar navbar-light bg-white shadow-sm d-lg-none px-3">
    <button class="btn btn-outline-secondary" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand ms-3"><?php echo htmlspecialchars($page_title); ?></span>
</nav>

<div class="main-wrapper">

    <!-- Sidebar -->
    <div class="sidebar text-white p-3 vh-100 d-flex flex-column" id="teacherSidebar">
        <div>
            <h4 class="mb-4 text-center"><i class="fas fa-chalkboard-teacher me-2"></i>ครูผู้สอน</h4>
            <div class="list-group list-group-flush">
                <a href="teacher_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-home fa-fw me-2"></i>หน้าหลัก
                </a>
                <a href="teacher_classes.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-school fa-fw me-2"></i>คลาสเรียนของฉัน
                </a>
                <a href="teacher_profile_edit.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-circle fa-fw me-2"></i>ข้อมูลส่วนตัว
                </a>
                <a href="teacher_homeroom_report.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-print fa-fw me-2"></i>รายงานห้องประจำชั้น
                </a>
            </div>
        </div>
        <div class="mt-auto">
            <a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <main class="p-4">
            <div class="container-fluid">
                <div class="p-4 mb-4 rounded text-white" style="background: linear-gradient(135deg, #16a085, #1abc9c);">
                    <h3>ยินดีต้อนรับ, ครู<?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h3>
                    <p class="mb-0">ระบบจัดการข้อมูลสำหรับครูผู้สอน</p>
                </div>

                <?php echo get_session_message(); ?>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ข้อมูลส่วนตัว</h5>
                                <a href="teacher_profile_edit.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-user-edit me-1"></i> แก้ไข
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="info-item mb-2"><i class="fas fa-user fa-fw me-2 text-muted"></i> <strong>Username:</strong> <?php echo htmlspecialchars($teacher_data['username']); ?></div>
                                <div class="info-item mb-2"><i class="fas fa-id-badge fa-fw me-2 text-muted"></i> <strong>ชื่อ-สกุล:</strong> <?php echo htmlspecialchars($teacher_data['fullname']); ?></div>
                                <div class="info-item mb-2"><i class="fas fa-envelope fa-fw me-2 text-muted"></i> <strong>อีเมล:</strong> <?php echo htmlspecialchars($teacher_data['email'] ?? '-'); ?></div>
                                <div class="info-item"><i class="fas fa-phone fa-fw me-2 text-muted"></i> <strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($teacher_data['phone'] ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.getElementById("sidebarToggle").addEventListener("click", function () {
        document.getElementById("teacherSidebar").classList.toggle("toggled");
    });
</script>

</body>
</html>
