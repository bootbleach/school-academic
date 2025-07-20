<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Student Dashboard'; ?> - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px; background-color: #2c3e50;
            transition: margin-left 0.3s ease;
        }
        .sidebar .list-group-item {
            background-color: transparent; color: #ecf0f1; border: none;
            border-left: 4px solid transparent; padding: 1rem 1.25rem; transition: all 0.3s ease;
        }
        .sidebar .list-group-item:hover, .sidebar .list-group-item.active {
            background-color: #34495e; color: #ffffff; border-left-color: #3498db; /* สีฟ้าสำหรับ Active */
        }
        .content-wrapper { flex-grow: 1; }
        
        /* --- CSS สำหรับ Responsive --- */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed; top: 0; bottom: 0; left: 0; z-index: 1050;
                margin-left: -280px; /* ซ่อน Sidebar เริ่มต้น */
            }
            .sidebar.toggled { margin-left: 0; /* แสดง Sidebar */ }
            .content-wrapper {
                padding-top: 56px; /* เพิ่ม padding ด้านบนของเนื้อหาหลักเพื่อไม่ให้ถูก Navbar บัง */
            }
        }

        /* --- CSS สำหรับ Overlay --- */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1040; /* อยู่หลัง Sidebar แต่อยู่หน้า Content */
        }
        /* --- End of CSS for Responsive --- */

        .welcome-card { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
        .card-header-custom { background-color: #34495e; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm d-lg-none fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary" id="sidebarToggleMobile">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand ms-2"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'เมนู'; ?></span>
        </div>
    </nav>

    <div class="main-wrapper">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#selected_subjects').select2({
            placeholder: "ค้นหาและเลือกวิชา",
            allowClear: true // อนุญาตให้ล้างค่าที่เลือก
        });
    });
</script>