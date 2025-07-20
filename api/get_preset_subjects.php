<?php
// api/get_preset_subjects.php

header('Content-Type: application/json'); // กำหนด Header ให้เป็น JSON

require_once '../config.php'; // Include ไฟล์เชื่อมต่อฐานข้อมูล (ปรับ path ให้ถูกต้อง)
// ไม่ต้อง require admin_functions.php หรือเช็ค session_start() เพราะเป็น API ที่อาจถูกเรียกใช้โดย JS

$response = [
    'success' => false,
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ',
    'subjects' => []
];

if (isset($_GET['preset_id']) && is_numeric($_GET['preset_id'])) {
    $preset_id = (int)$_GET['preset_id'];

    // ดึงรายวิชาที่อยู่ใน preset_id นี้
    $sql = "
        SELECT 
            s.subject_id, 
            s.subject_code, 
            s.subject_name
        FROM 
            preset_subjects ps
        JOIN 
            subjects s ON ps.subject_id = s.subject_id
        WHERE 
            ps.preset_id = ?
        ORDER BY 
            s.subject_code ASC
    ";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $preset_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $subjects_data = [];
            while ($row = $result->fetch_assoc()) {
                $subjects_data[] = $row;
            }
            $response['success'] = true;
            $response['message'] = 'ดึงข้อมูลวิชาสำเร็จ';
            $response['subjects'] = $subjects_data;
        } else {
            $response['success'] = true; // ยังคงเป็น true เพราะ query สำเร็จแต่ไม่มีข้อมูล
            $response['message'] = 'ไม่พบวิชาสำหรับชุดวิชานี้ หรือชุดวิชาไม่ถูกต้อง';
        }
        $stmt->close();
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'ไม่ได้รับ Preset ID ที่ถูกต้อง';
}

echo json_encode($response);
$mysqli->close();
?>