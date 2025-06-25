<?php
session_start();
require_once 'config.php';

function authenticate_user($mysqli, $username, $password, $table, $role, $id_field, $name_fields) {
    $sql = "SELECT " . implode(', ', $name_fields) . ", password FROM {$table} WHERE username = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION[$role . '_loggedin'] = true;
                    $_SESSION[$role . '_id'] = $user[$id_field];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $role;

                    if ($role === 'student') {
                        $_SESSION['student_name'] = $user['prefix'] . $user['first_name'] . ' ' . $user['last_name'];
                    } else {
                        $_SESSION['fullname'] = $user['fullname'];
                    }
                    return true;
                }
            }
        }
        $stmt->close();
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        $_SESSION['login_message'] = "กรุณากรอก Username และ Password ให้ครบถ้วน.";
        header("Location: login.php");
        exit();
    }
    
    // ตรวจสอบ Admin
    if (authenticate_user($mysqli, $username, $password, 'admins', 'admin', 'admin_id', ['admin_id', 'username', 'fullname'])) {
        header("Location: admin_menu.php");
        exit();
    }

    // ตรวจสอบ Teacher
    if (authenticate_user($mysqli, $username, $password, 'teachers', 'teacher', 'teacher_id', ['teacher_id', 'username', 'fullname'])) {
        header("Location: teacher_dashboard.php");
        exit();
    }

    // ตรวจสอบ Student
    if (authenticate_user($mysqli, $username, $password, 'students', 'student', 'student_id', ['student_id', 'username', 'prefix', 'first_name', 'last_name'])) {
        header("Location: student_dashboard.php");
        exit();
    }

    // ถ้าไม่สำเร็จในทุกบทบาท
    $_SESSION['login_message'] = "Username หรือ Password ไม่ถูกต้อง.";
    header("Location: login.php");
    exit();
}
?>