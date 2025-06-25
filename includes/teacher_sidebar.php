<?php
// ดึงชื่อไฟล์ปัจจุบันเพื่อใช้ในการกำหนด active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar text-white p-3 vh-100 d-flex flex-column" id="teacherSidebar">
    <div>
        <h4 class="mb-4 text-center"><i class="fas fa-chalkboard-teacher me-2"></i>ครูผู้สอน</h4>
        <div class="list-group list-group-flush">
            <a href="teacher_dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'teacher_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home fa-fw me-2"></i>หน้าหลัก
            </a>
            <a href="teacher_classes.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'teacher_classes.php' || $current_page == 'teacher_enter_scores.php') ? 'active' : ''; ?>">
                <i class="fas fa-edit fa-fw me-2"></i>กรอกคะแนน
            </a>
             <a href="teacher_homeroom_report.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'teacher_homeroom_report.php') ? 'active' : ''; ?>">
                <i class="fas fa-print fa-fw me-2"></i>รายงานห้องประจำชั้น
            </a>
            <a href="teacher_profile_edit.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'teacher_profile_edit.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle fa-fw me-2"></i>ข้อมูลส่วนตัว
            </a>
        </div>
    </div>
    <div class="mt-auto">
        <div class="text-center mb-3">
             <small class="text-muted">ผู้ใช้งาน: <?php echo htmlspecialchars($_SESSION['fullname']); ?></small>
        </div>
        <a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>