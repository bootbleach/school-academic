<?php
// ตั้งค่าส่วนหัวของ HTTP สำหรับดาวน์โหลดไฟล์
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student_template.csv"');

// เปิด output buffer เพื่อให้สามารถเขียนข้อมูลลงไฟล์ได้
$output = fopen('php://output', 'w');

// กำหนดหัวข้อคอลัมน์ (Headers) ที่ถูกต้อง
$headers = [
    'student_code',
    'username',
    'password',
    'prefix',
    'first_name',
    'last_name',
    'id_card_number'
];

// เขียนหัวข้อคอลัมน์ลงในไฟล์ CSV
fputcsv($output, $headers);

// คุณสามารถเพิ่มข้อมูลตัวอย่างหนึ่งหรือสองแถวที่นี่ได้ หากต้องการ
// ตัวอย่างเช่น:
// fputcsv($output, ['S001', 'std001', 'pass123', 'นาย', 'สมชาย', 'ดีเยี่ยม', '1234567890123']);
// fputcsv($output, ['S002', 'std002', 'pass456', 'ด.ญ.', 'มาลี', 'ใจดี', '9876543210987']);

// ปิด output buffer
fclose($output);
exit();
?>