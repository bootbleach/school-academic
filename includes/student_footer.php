</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('studentSidebar');
        const sidebarToggle = document.getElementById('sidebarToggleMobile');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // --- 1. จัดการการเปิด-ปิด Sidebar ---
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('toggled');
                // แสดง/ซ่อน overlay พร้อมกับ sidebar
                if (sidebar.classList.contains('toggled')) {
                    sidebarOverlay.style.display = 'block';
                } else {
                    sidebarOverlay.style.display = 'none';
                }
            });
        }
        
        // --- 2. จัดการการคลิกที่ Overlay เพื่อปิด Sidebar ---
        if (sidebarOverlay) {
             sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('toggled');
                this.style.display = 'none';
            });
        }

        // --- 3. จัดการ Active Link บน Sidebar ---
        const currentPath = window.location.pathname.split("/").pop();
        const sidebarLinks = document.querySelectorAll(".sidebar .list-group-item");
        
        let foundActive = false;
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
            // ตรวจสอบว่า href ของลิงก์ตรงกับชื่อไฟล์ปัจจุบันหรือไม่
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
                foundActive = true;
            }
        });

        // กรณีพิเศษ: ถ้าอยู่ที่หน้าแรก (index) หรือ student_dashboard.php ให้แดชบอร์ด active
        if (!foundActive && (currentPath === '' || currentPath === 'student_dashboard.php')) {
             const dashboardLink = document.querySelector('.sidebar .list-group-item[href="student_dashboard.php"]');
             if(dashboardLink) dashboardLink.classList.add('active');
        }
    });
</script>
</body>
</html>