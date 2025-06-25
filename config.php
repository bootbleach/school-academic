<?php
// config.php - สำหรับเก็บค่าการเชื่อมต่อฐานข้อมูล

define('DB_SERVER', 'localhost'); // หรือ IP Address ของ Database Server
define('DB_USERNAME', 'root');    // ชื่อผู้ใช้งานฐานข้อมูล (เปลี่ยนเป็นชื่อผู้ใช้จริงของคุณ)
define('DB_PASSWORD', '');        // รหัสผ่านฐานข้อมูล (เปลี่ยนเป็นรหัสผ่านจริงของคุณ)
define('DB_NAME', 'metta_academic_db'); // ชื่อ Database ที่สร้างเมื่อกี้

// สร้าง Connection
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// ตรวจสอบ Connection
if ($mysqli->connect_errno) {
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// ตั้งค่า Character Set เป็น UTF-8 เพื่อรองรับภาษาไทย
$mysqli->set_charset("utf8mb4");

// คุณสามารถเพิ่มฟังก์ชันสำหรับ Query ได้ที่นี่ หรือแยกไปทำในไฟล์อื่น

?>