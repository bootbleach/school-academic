<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

$student_info = null;
$transcript_data = []; // สำหรับเก็บข้อมูลเกรดที่จัดกลุ่มตามเทอมแล้ว
$error_message = '';

if (isset($_GET['id_card_number']) && !empty(trim($_GET['id_card_number']))) {
    $id_card_number = trim($_GET['id_card_number']);

    if (!preg_match('/^[0-9]{13}$/', $id_card_number)) {
        $error_message = "รูปแบบเลขประจำตัวประชาชนไม่ถูกต้อง";
    } else {
        // 1. ค้นหานักเรียนจากเลขบัตร
        $stmt_student = $mysqli->prepare("SELECT * FROM students WHERE id_card_number = ?");
        $stmt_student->bind_param("s", $id_card_number);
        $stmt_student->execute();
        $student_info = $stmt_student->get_result()->fetch_assoc();
        $stmt_student->close();

        if ($student_info) {
            $student_id = $student_info['student_id'];
            
            // 2. ดึงข้อมูลผลการเรียนทั้งหมดของนักเรียนคนนี้
            $sql_enrollments = "SELECT 
                                    ay.year AS academic_year_name,
                                    s.subject_code, s.subject_name, s.credits,
                                    e.score, e.grade, e.grade_point
                                FROM enrollments e
                                JOIN classes c ON e.class_id = c.class_id
                                JOIN subjects s ON e.subject_id = s.subject_id
                                JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id
                                WHERE e.student_id = ? 
                                ORDER BY ay.year ASC, s.subject_code ASC";

            if ($stmt_enroll = $mysqli->prepare($sql_enrollments)) {
                $stmt_enroll->bind_param("i", $student_id);
                $stmt_enroll->execute();
                $result_enroll = $stmt_enroll->get_result();
                
                // 3. จัดกลุ่มข้อมูลตามภาคเรียน
                while ($row = $result_enroll->fetch_assoc()) {
                    $transcript_data[$row['academic_year_name']][] = $row;
                }
                $stmt_enroll->close();
            }
        } else {
            $error_message = "ไม่พบข้อมูลนักเรียนสำหรับเลขประจำตัวประชาชนนี้";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระเบียนแสดงผลการเรียน - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { background-color: #f0f2f5; } 
        .transcript-container { background-color: #fff; border: 1px solid #dee2e6; padding: 2rem; max-width: 900px; margin: auto; }
        @media print {
            body { background-color: #fff; }
            .d-print-none { display: none !important; }
            .transcript-container { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-end mb-3 d-print-none">
            <button onclick="window.print();" class="btn btn-success me-2"><i class="fas fa-print me-2"></i>พิมพ์</button>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>กลับหน้าแรก</a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center"><h4><i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาด</h4><p><?php echo $error_message; ?></p></div>
        <?php elseif ($student_info): ?>
            <div class="transcript-container shadow-sm">
                <div class="text-center mb-4">
                    <h4>ระเบียนแสดงผลการเรียน (Transcript)</h4>
                    <h5>โรงเรียนเมตตาวิทยา</h5>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-6"><strong>ชื่อ-สกุล:</strong> <?php echo htmlspecialchars($student_info['prefix'].$student_info['first_name'].' '.$student_info['last_name']); ?></div>
                    <div class="col-6"><strong>รหัสนักเรียน:</strong> <?php echo htmlspecialchars($student_info['student_code']); ?></div>
                    <div class="col-6"><strong>เลขประจำตัวประชาชน:</strong> <?php echo htmlspecialchars($student_info['id_card_number']); ?></div>
                </div>
                <hr>

                <?php 
                    $cumulative_credits = 0;
                    $cumulative_points = 0;
                    if (empty($transcript_data)): 
                ?>
                    <p class="text-center text-muted">ยังไม่มีข้อมูลผลการเรียน</p>
                <?php else: ?>
                    <?php foreach($transcript_data as $term => $subjects): ?>
                        <h6 class="mt-4"><strong>ภาคเรียนที่ <?php echo htmlspecialchars($term); ?></strong></h6>
                        <table class="table table-sm table-bordered" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อรายวิชา</th>
                                    <th class="text-center">หน่วยกิต</th>
                                    <th class="text-center">เกรด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $term_credits = 0;
                                    $term_points = 0;
                                    foreach($subjects as $subject): 
                                        if (is_numeric($subject['credits']) && is_numeric($subject['grade_point'])) {
                                            $term_credits += $subject['credits'];
                                            $term_points += ($subject['grade_point'] * $subject['credits']);
                                        }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars(number_format($subject['credits'], 1)); ?></td>
                                    <td class="text-center fw-bold"><?php echo htmlspecialchars($subject['grade'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr>
                                    <td colspan="2" class="text-end"><strong>สรุปผลการเรียนประจำภาคเรียน</strong></td>
                                    <td class="text-center"><strong><?php echo number_format($term_credits, 1); ?></strong></td>
                                    <td class="text-center"><strong>GPA: <?php echo number_format(($term_credits > 0 ? $term_points / $term_credits : 0), 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php 
                        $cumulative_credits += $term_credits;
                        $cumulative_points += $term_points;
                    endforeach; 
                    $gpax = ($cumulative_credits > 0) ? ($cumulative_points / $cumulative_credits) : 0;
                    ?>
                    <hr class="my-4">
                    <div class="row fs-5">
                        <div class="col-6 text-end"><strong>หน่วยกิตสะสมตลอดหลักสูตร:</strong></div>
                        <div class="col-6"><strong><?php echo number_format($cumulative_credits, 1); ?></strong></div>
                        <div class="col-6 text-end"><strong>ผลการเรียนเฉลี่ยสะสม (GPAX):</strong></div>
                        <div class="col-6"><strong class="text-success"><?php echo number_format($gpax, 2); ?></strong></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>