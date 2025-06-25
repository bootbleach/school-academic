<?php
// ไฟล์นี้ควรมีการตรวจสอบ session หรือการตั้งค่าพื้นฐานอื่นๆ ถ้าจำเป็น
// ตัวแปร $page_title ควรถูกกำหนดค่าก่อนที่จะ include ไฟล์นี้
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Teacher Panel'; ?> - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background-color: #f0f2f5; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background-color: #2c3e50;
            transition: margin-left 0.3s ease;
            position: relative; /* สำหรับ Desktop */
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
                margin-left: -280px; /* ซ่อน Sidebar ไว้ด้านซ้าย */
            }
            .sidebar.toggled {
                margin-left: 0; /* แสดง Sidebar เมื่อมี class 'toggled' */
            }
            /* เพิ่ม overlay เพื่อทำให้เนื้อหาด้านหลังมืดลงเมื่อเปิด sidebar (ทางเลือก) */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 1040;
            }
            .sidebar.toggled + .content-wrapper .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm d-lg-none px-3 sticky-top">
    <button class="btn btn-outline-secondary" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand ms-3"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Menu'; ?></span>
</nav>

<div class="main-wrapper">