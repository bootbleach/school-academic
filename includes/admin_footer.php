</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script สำหรับเปิด-ปิดเมนูบนมือถือ
        if(document.getElementById('sidebarToggleMobile')){
            document.getElementById('sidebarToggleMobile').addEventListener('click', function() {
                document.getElementById('adminSidebar').classList.toggle('toggled');
            });
        }
        
        // Script สำหรับทำให้เมนูปัจจุบัน Active
        document.addEventListener("DOMContentLoaded", function() {
            const currentPath = window.location.pathname.split("/").pop();
            const sidebarLinks = document.querySelectorAll(".sidebar .list-group-item");
            
            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
             if (currentPath === '' || currentPath === 'admin_menu.php') {
                 document.querySelector('.sidebar .list-group-item[href="admin_menu.php"]').classList.add('active');
            }
        });
    </script>
</body>
</html>