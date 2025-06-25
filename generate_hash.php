<?php
// generate_hash.php - ไฟล์สำหรับสร้าง Hash Password

// รหัสผ่านที่คุณต้องการสร้าง Hash
$plain_password = "your_admin_password"; // <--- **เปลี่ยน 'your_admin_password' เป็นรหัสผ่านที่คุณต้องการจริงๆ**

// สร้าง Hash Password
// PASSWORD_DEFAULT เป็นอัลกอริทึมที่แนะนำที่สุดในปัจจุบัน (ปัจจุบันคือ bcrypt)
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "Plain Password: <strong>" . htmlspecialchars($plain_password) . "</strong><br>";
echo "Hashed Password: <strong>" . htmlspecialchars($hashed_password) . "</strong><br><br>";
echo "คัดลอก Hashed Password นี้ไปใช้ในการ INSERT หรือ UPDATE ข้อมูลในฐานข้อมูล (ในตาราง `admins` field `password`)";

?>