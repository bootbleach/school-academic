</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarToggle = document.getElementById("sidebarToggle");
        const teacherSidebar = document.getElementById("teacherSidebar");
        const sidebarOverlay = document.getElementById("sidebarOverlay");

        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function () {
                teacherSidebar.classList.toggle("toggled");
                // ถ้าต้องการให้ overlay ทำงานพร้อมกัน
                if(sidebarOverlay) {
                    // การคลิกที่ overlay จะปิด sidebar
                    if (teacherSidebar.classList.contains("toggled")) {
                        sidebarOverlay.style.display = 'block';
                    } else {
                        sidebarOverlay.style.display = 'none';
                    }
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                teacherSidebar.classList.remove('toggled');
                this.style.display = 'none';
            });
        }
    });
</script>

</body>
</html>