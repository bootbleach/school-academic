<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

// ตรวจสอบว่า Admin ล็อกอินอยู่หรือไม่
if (!is_admin_loggedin()) {
    header("Location: login.php");
    exit();
}

$page_title = "แดชบอร์ดผู้ดูแลระบบ";
require_once 'includes/admin_header.php';
?>
<style>
    .card-link {
        text-decoration: none;
        color: inherit;
    }
    .menu-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: none;
        border-radius: 0.5rem;
    }
    .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>
<?php
require_once 'includes/admin_sidebar.php';
?>

<div class="content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-light" type="button" id="sidebarToggleMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="navbar-brand mb-0 ms-2"><?php echo htmlspecialchars($page_title); ?></h5>
        </div>
    </nav>
    
    <main class="p-4">
        <div class="container-fluid">
            <div class="p-4 mb-4 rounded text-white shadow-sm" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <h3>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h3>
                <p class="mb-0">เลือกเมนูการทำงานจากด้านล่าง หรือจากแถบเมนูด้านข้าง</p>
            </div>

            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <a href="admin_list.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                                <h5>จัดการ Admin</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="teacher_list.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                                <h5>จัดการข้อมูลครู</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="student_list.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-user-graduate fa-3x text-info mb-3"></i>
                                <h5>จัดการข้อมูลนักเรียน</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="subject_list.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-book fa-3x text-warning mb-3"></i>
                                <h5>จัดการวิชา</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="class_list.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-school fa-3x text-secondary mb-3"></i>
                                <h5>จัดการคลาสเรียน</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="academic_years.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-calendar-alt fa-3x text-danger mb-3"></i>
                                <h5>จัดการปีการศึกษา</h5>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="admin_enter_scores.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-edit fa-3x mb-3" style="color: #6f42c1;"></i>
                                <h5>จัดการคะแนน</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                     <a href="report_class_grades.php" class="card-link">
                        <div class="card menu-card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-print fa-3x mb-3" style="color: #fd7e14;"></i>
                                <h5>รายงานผลการเรียน</h5>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </main>
</div>
<?php
require_once 'includes/admin_footer.php';
?>