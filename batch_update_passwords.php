<?php
require_once 'config.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

echo "<h1>Force Password Reset and Re-hash Process</h1>";

try {
    // 1. ดึงข้อมูลนักเรียนทุกคนออกมา
    $stmt = $mysqli->prepare("SELECT student_id, username FROM students");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p style='color: orange;'>No students found in the database.</p>";
        exit;
    }

    echo "<p>Found " . $result->num_rows . " users to update.</p><hr>";

    $updated_count = 0;
    // --- รหัสผ่านใหม่ที่ต้องการตั้งค่า ---
    $new_password_to_set = '123'; 
    // ------------------------------------

    // 2. เตรียมคำสั่ง UPDATE
    $update_stmt = $mysqli->prepare("UPDATE students SET password = ? WHERE student_id = ?");

    // 3. วนลูปเพื่ออัปเดตทีละคน
    while ($user = $result->fetch_assoc()) {
        $student_id = $user['student_id'];
        $username = $user['username'];

        // สร้างค่าแฮชใหม่สำหรับรหัสผ่านในแต่ละรอบ ทำให้ hash ไม่ซ้ำกัน
        $new_hashed_password = password_hash($new_password_to_set, PASSWORD_BCRYPT);

        // ทำการอัปเดต
        $update_stmt->bind_param("si", $new_hashed_password, $student_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            echo "<p>Successfully updated password for user: {$username} (ID: {$student_id})</p>";
            $updated_count++;
        } else {
            echo "<p style='color: gray;'>Failed to update or password was already set for user: {$username} (ID: {$student_id})</p>";
        }
    }

    echo "<hr><h2 style='color: blue;'>Process Complete!</h2>";
    echo "<h3>Total passwords reset and re-hashed: " . $updated_count . "</h3>";

    $stmt->close();
    $update_stmt->close();

} catch (Exception $e) {
    die("An error occurred: " . $e->getMessage());
}

$mysqli->close();
?>