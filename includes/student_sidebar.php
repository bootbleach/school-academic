<div class="sidebar text-white p-3 vh-100 d-flex flex-column" id="studentSidebar">
    <div>
        <h4 class="mb-4 text-center"><i class="fas fa-graduation-cap me-2"></i>นักเรียน</h4>
        <div class="list-group list-group-flush">
            <a href="student_dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt fa-fw me-2"></i>แดชบอร์ด</a>
            <a href="student_profile.php" class="list-group-item list-group-item-action"><i class="fas fa-user-circle fa-fw me-2"></i>ข้อมูลส่วนตัว</a>
            <a href="student_grades.php" class="list-group-item list-group-item-action"><i class="fas fa-book-open fa-fw me-2"></i>ผลการเรียน</a>
        </div>
    </div>
    <div class="mt-auto">
       <div class="text-center mb-3">
    <small class="text-light">ผู้ใช้งาน: <?php echo htmlspecialchars($_SESSION['student_name'] ?? ''); ?></small>
</div>

         <a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>