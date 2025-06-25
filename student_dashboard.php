<?php
session_start();
require_once 'config.php';
require_once 'includes/admin_functions.php';

if (!is_student_loggedin()) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// 1. ดึงข้อมูลนักเรียนเบื้องต้น รวมรูปโปรไฟล์
$student_info_stmt = $mysqli->prepare("SELECT s.username, s.profile_image, s.prefix, s.first_name, s.last_name, s.student_code, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = ?");
$student_info_stmt->bind_param("i", $student_id);
$student_info_stmt->execute();
$student_info = $student_info_stmt->get_result()->fetch_assoc();
$student_info_stmt->close();

// 2. คำนวณ GPA รวมทุกเทอม
$total_credits_all = 0;
$total_points_all = 0;
$gpa_sql = "SELECT s.credits, e.grade_point FROM enrollments e JOIN subjects s ON e.subject_id = s.subject_id WHERE e.student_id = ? AND e.grade_point IS NOT NULL";
if($stmt_gpa = $mysqli->prepare($gpa_sql)) {
    $stmt_gpa->bind_param("i", $student_id);
    $stmt_gpa->execute();
    $gpa_result = $stmt_gpa->get_result();
    while($row = $gpa_result->fetch_assoc()){
        $total_credits_all += $row['credits'];
        $total_points_all += ($row['grade_point'] * $row['credits']);
    }
    $stmt_gpa->close();
}
$cumulative_gpa = ($total_credits_all > 0) ? ($total_points_all / $total_credits_all) : 0;

// 3. ดึง GPA แยกรายเทอม
$term_gpa_sql = "SELECT 
                    ay.year AS term_name,
                    SUM(s.credits) AS term_credits,
                    SUM(e.grade_point * s.credits) AS term_points
                 FROM enrollments e
                 JOIN subjects s ON e.subject_id = s.subject_id
                 JOIN classes c ON e.class_id = c.class_id
                 JOIN academic_years ay ON c.academic_year_id = ay.acad_year_id
                 WHERE e.student_id = ? AND e.grade_point IS NOT NULL
                 GROUP BY c.academic_year_id
                 ORDER BY ay.year DESC";
$stmt_term = $mysqli->prepare($term_gpa_sql);
$stmt_term->bind_param("i", $student_id);
$stmt_term->execute();
$term_results = $stmt_term->get_result();
$stmt_term->close();

// 4. นับจำนวนวิชาทั้งหมดและวิชาที่ผ่าน
$subject_count_sql = "SELECT 
                        COUNT(*) as total_subjects,
                        SUM(CASE WHEN e.grade_point >= 1.0 THEN 1 ELSE 0 END) as passed_subjects
                      FROM enrollments e 
                      WHERE e.student_id = ? AND e.grade_point IS NOT NULL";
$stmt_count = $mysqli->prepare($subject_count_sql);
$stmt_count->bind_param("i", $student_id);
$stmt_count->execute();
$subject_count = $stmt_count->get_result()->fetch_assoc();
$stmt_count->close();

$page_title = "แดชบอร์ดนักเรียน";
require_once 'includes/student_header.php';
require_once 'includes/student_sidebar.php';
?>

<style>
    /* กำหนด Animation สำหรับการโหลดหน้า */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animation-fadeInUp {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* ปรับปรุง Body และ Content Wrapper */
    .content-wrapper {
        background-color: #f4f7f6; /* สีพื้นหลังใหม่ที่สบายตา */
    }

    /* สไตล์การ์ดโปรไฟล์ใหม่ */
    .welcome-profile-card {
        background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
        color: white;
        border: none;
        position: relative;
        overflow: hidden;
    }
    .welcome-profile-card::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        opacity: 0.5;
    }
    .profile-image-container img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    .profile-details h1 {
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
    }
    .profile-details p {
        opacity: 0.9;
    }

    /* สไตล์การ์ดข้อมูลสรุป (Stat Cards) ใหม่ */
    .stat-card {
        border: none;
        border-left: 5px solid;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .stat-card .stat-icon {
        font-size: 2rem;
        padding: 20px;
        border-radius: 50%;
        color: white;
        transition: transform 0.3s ease;
    }
    .stat-card:hover .stat-icon {
        transform: scale(1.1) rotate(5deg);
    }
    .stat-card.border-primary .stat-icon { background: linear-gradient(45deg, #0d6efd, #0a58ca); }
    .stat-card.border-success .stat-icon { background: linear-gradient(45deg, #198754, #146c43); }
    .stat-card.border-info .stat-icon { background: linear-gradient(45deg, #0dcaf0, #0aa3c2); }
    .stat-card.border-warning .stat-icon { background: linear-gradient(45deg, #ffc107, #d39e00); }

    /* สไตล์การ์ดผลการเรียนรายเทอมใหม่ */
    .term-gpa-card {
        border: 1px solid #e0e0e0;
        border-top-width: 4px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
     .term-gpa-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }
    .gpa-progress-bar-container {
        background-color: #e9ecef;
        border-radius: .25rem;
        height: 8px;
        overflow: hidden;
    }
    .gpa-progress-bar {
        height: 100%;
        transition: width 0.5s ease-in-out;
    }
    .gpa-value {
        font-weight: 700;
    }
    
    /* Responsive Adjustments สำหรับมือถือ */
    @media (max-width: 767.98px) {
        .profile-image-container { text-align: center; margin-bottom: 1rem; }
        .profile-image-container img { width: 90px; height: 90px; }
        .profile-details { text-align: center; }
        .profile-details h1 { font-size: 1.75rem; }
        .profile-details p { font-size: 0.9rem; }
        .stats-container .col-md-6 { flex: 0 0 50%; max-width: 50%; } /* ทำให้การ์ดสถิติเป็น 2 คอลัมน์บนมือถือ */
        .term-gpa-card { font-size: 0.9rem; }
    }
</style>
<div class="content-wrapper">
    <main class="p-md-4 p-3">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card welcome-profile-card animation-fadeInUp">
                        <div class="card-body p-4">
                            <div class="row align-items-center gy-3">
                                <div class="col-md-auto profile-image-container">
                                    <img src="<?php echo !empty($student_info['profile_image']) ? htmlspecialchars($student_info['profile_image']) : 'assets/images/default_avatar.png'; ?>" 
                                         class="rounded-circle" alt="Profile Picture">
                                </div>
                                <div class="col-md profile-details">
                                    <h1 class="mb-1">ยินดีต้อนรับ, <?php echo htmlspecialchars($student_info['first_name']); ?>!</h1>
                                    <p class="mb-2 opacity-75">เริ่มต้นวันใหม่กับการเรียนรู้ไปด้วยกัน</p>
                                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start small text-white-50">
                                        <span class="me-3"><i class="fas fa-id-card fa-fw me-1"></i><?php echo htmlspecialchars($student_info['student_code']); ?></span>
                                        <span class="me-3"><i class="fas fa-users fa-fw me-1"></i><?php echo htmlspecialchars($student_info['class_name'] ?? 'ไม่ได้ระบุ'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-auto text-center text-md-end mt-3 mt-md-0">
                                    <a href="student_profile.php" class="btn btn-light shadow-sm">
                                        <i class="fas fa-user-edit me-2"></i>แก้ไขโปรไฟล์
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 stats-container gy-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card border-primary h-100 animation-fadeInUp" style="animation-delay: 0.1s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon me-3"><i class="fas fa-graduation-cap"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo number_format($cumulative_gpa, 2); ?></h3>
                                <p class="text-muted mb-0">เกรดเฉลี่ยสะสม (GPAX)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card border-success h-100 animation-fadeInUp" style="animation-delay: 0.2s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon me-3"><i class="fas fa-book"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($total_credits_all); ?></h3>
                                <p class="text-muted mb-0">หน่วยกิตสะสม</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card border-info h-100 animation-fadeInUp" style="animation-delay: 0.3s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon me-3"><i class="fas fa-clipboard-list"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($subject_count['total_subjects'] ?? 0); ?></h3>
                                <p class="text-muted mb-0">วิชาที่ลงทะเบียน</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                     <div class="card stat-card border-warning h-100 animation-fadeInUp" style="animation-delay: 0.4s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon me-3"><i class="fas fa-trophy"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($subject_count['passed_subjects'] ?? 0); ?></h3>
                                <p class="text-muted mb-0">วิชาที่ผ่าน</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card animation-fadeInUp" style="animation-delay: 0.5s;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>ผลการเรียนรายภาคเรียน (GPA)</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($term_results->num_rows > 0): ?>
                                <div class="row gy-4">
                                    <?php 
                                    $term_results->data_seek(0);
                                    foreach($term_results as $index => $term): 
                                        $term_gpa = ($term['term_credits'] > 0) ? ($term['term_points'] / $term['term_credits']) : 0;
                                        $gpa_color_class = 'primary';
                                        if ($term_gpa >= 3.5) $gpa_color_class = 'success';
                                        elseif ($term_gpa >= 2.5) $gpa_color_class = 'info';
                                        elseif ($term_gpa >= 1.5) $gpa_color_class = 'warning';
                                        else $gpa_color_class = 'danger';
                                        
                                        $gpa_percentage = ($term_gpa / 4.0) * 100;
                                    ?>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card term-gpa-card border-<?php echo $gpa_color_class; ?> h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="card-title mb-0">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        เทอม <?php echo htmlspecialchars($term['term_name']); ?>
                                                    </h6>
                                                    <span class="badge bg-<?php echo $gpa_color_class; ?> gpa-value"><?php echo number_format($term_gpa, 2); ?></span>
                                                </div>
                                                <div class="gpa-progress-bar-container mb-2">
                                                    <div class="gpa-progress-bar bg-<?php echo $gpa_color_class; ?>" style="width: <?php echo $gpa_percentage; ?>%;"></div>
                                                </div>
                                                <small class="text-muted">หน่วยกิต: <?php echo htmlspecialchars($term['term_credits']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar fa-3x text-light mb-3"></i>
                                    <h5 class="text-muted">ยังไม่มีข้อมูลผลการเรียน</h5>
                                    <p class="text-muted mb-0">เมื่อมีผลการเรียนแล้ว ข้อมูลจะปรากฏในส่วนนี้</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/student_footer.php'; ?>