<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โรงเรียนเมตตาวิทยา</title>
    <style>
        :root {
            --primary-color: #6a0dad; /* สีม่วงหลัก */
            --secondary-color: #ffd700; /* สีเหลืองทอง */
            --light-color: #f5e6ff; /* ม่วงอ่อน */
            --dark-color: #4b0082; /* ม่วงเข้ม */
            --text-color: #333;
            --white: #ffffff;
            --pastel-bg: #f8f5ff; /* สีพาสเทลม่วงอ่อนสำหรับพื้นหลัง */
            --pastel-section: #f0e9ff; /* สีพาสเทลสำหรับส่วนต่างๆ */
            --nav-bg: #f9f0ff; /* สีพาสเทลสำหรับ Navigation */
            --button-color: #d4b8ff; /* สีพาสเทลสำหรับปุ่ม */
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Kanit', 'Sarabun', sans-serif;
        }
        
        body {
            background-color: var(--pastel-bg);
            color: var(--text-color);
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Navigation */
        header {
            background-color: var(--nav-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo {
            height: 60px;
            margin-right: 15px;
        }
        
        .school-name {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 15px;
        }
        
        .nav-links li {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
            padding: 8px 12px;
            border-radius: 8px;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
            background-color: rgba(106, 13, 173, 0.1);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            bottom: -5px;
            left: 0;
            transition: width 0.3s;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        /* ปุ่มพิเศษสำหรับระบบวัดผล */
        .login-button {
            background: linear-gradient(45deg, var(--button-color), #a38cf2);
            background-size: 200% 200%;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            animation: GradientBackground 4s ease infinite;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(45deg, #a38cf2, var(--button-color));
        }
        
        .hamburger {
            display: none;
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(135deg, var(--pastel-section), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0 2rem;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://img5.pic.in.th/file/secure-sv1/loho.png') no-repeat;
            background-size: contain;
            background-position: center;
            opacity: 0.05;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
            animation: fadeInDown 1s ease;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: var(--text-color);
            animation: fadeInUp 1s ease;
        }
        
        .btn {
            display: inline-block;
            background: var(--secondary-color);
            color: var(--dark-color);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            animation: fadeIn 1.5s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        /* About Section */
        .section {
            padding: 5rem 5%;
            max-width: 1400px;
            margin: 0 auto;
            background-color: var(--pastel-bg);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--secondary-color);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .about-content {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }
        
        .about-text {
            flex: 1;
            min-width: 300px;
        }
        
        .about-text h3 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .about-text p {
            margin-bottom: 1.5rem;
        }
        
        .about-image {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        
        .about-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* History Section */
        .history {
            background: linear-gradient(135deg, var(--pastel-section), var(--pastel-bg));
        }
        
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: var(--secondary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            background-color: var(--white);
            border: 4px solid var(--primary-color);
            border-radius: 50%;
            top: 15px;
            z-index: 1;
        }
        
        .left {
            left: 0;
        }
        
        .right {
            left: 50%;
        }
        
        .left::after {
            right: -12px;
        }
        
        .right::after {
            left: -12px;
        }
        
        .timeline-content {
            padding: 20px 30px;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .timeline-content h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .timeline-content p {
            margin-bottom: 0;
        }
        
        /* Facilities Section */
        .facilities {
            background-color: var(--pastel-bg);
        }
        
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .facility-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .facility-card:hover {
            transform: translateY(-10px);
        }
        
        .facility-img {
            height: 200px;
            overflow: hidden;
        }
        
        .facility-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .facility-card:hover .facility-img img {
            transform: scale(1.1);
        }
        
        .facility-info {
            padding: 1.5rem;
        }
        
        .facility-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
            color: var(--white);
            padding: 3rem 5%;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
        }
        
        .footer-section {
            flex: 1;
            min-width: 250px;
            text-align: left;
        }
        
        .footer-section h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: var(--secondary-color);
            bottom: -8px;
            left: 0;
        }
        
        .footer-section p, .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.8rem;
            display: block;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: var(--secondary-color);
        }
        
        .contact-info {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.8rem;
        }
        
        .contact-info i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--secondary-color);
            color: var(--dark-color);
        }
        
        .copyright {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
        }
        
        /* เพิ่มสไตล์สำหรับโลโก้ใน Hero Section */
        .hero-logo {
            width: 400px;
            height: auto;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite, fadeIn 1.5s ease;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
        }

        /* เพิ่มอนิเมชั่นการลอย */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes GradientBackground {
            0% { background-position: 0% 50% }
            50% { background-position: 100% 50% }
            100% { background-position: 0% 50% }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item::after {
                left: 18px;
            }
            
            .left::after, .right::after {
                left: 18px;
            }
            
            .right {
                left: 0%;
            }
        }
        
        @media (max-width: 768px) {
            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 80px);
                background: var(--nav-bg);
                flex-direction: column;
                align-items: center;
                padding: 2rem 0;
                transition: all 0.5s ease;
            }
            
            .nav-links.active {
                left: 0;
            }
            
            .nav-links li {
                margin: 1rem 0;
            }
            
            .hamburger {
                display: block;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .hero-logo {
                width: 120px;
            }
        }
        
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .btn {
                padding: 0.6rem 1.5rem;
            }
            
            .section {
                padding: 3rem 5%;
            }
            
            .hero-logo {
                width: 100px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <header>
        <nav>
            <div class="logo-container">
                <img src="https://img5.pic.in.th/file/secure-sv1/loho.png" alt="โลโก้โรงเรียนเมตตาวิทยา" class="logo">
                <div class="school-name">โรงเรียนเมตตาวิทยา</div>
            </div>
            <ul class="nav-links">
                <li><a href="#home">หน้าแรก</a></li>
                <li><a href="#about">เกี่ยวกับโรงเรียน</a></li>
                <li><a href="#history">ประวัติ</a></li>
                <li><a href="#facilities">อาคารสถานที่</a></li>
                <li><a href="#contact">ติดต่อเรา</a></li>
                <li><a href="academic_index.php" class="login-button">ระบบวัดผลประเมินผลนักเรียน</a></li>
            </ul>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <img src="https://img5.pic.in.th/file/secure-sv1/loho.png" alt="โลโก้โรงเรียนเมตตาวิทยา" class="hero-logo">
            <h1>โรงเรียนเมตตาวิทยา</h1>
            <p>จังหวัดเพชรบูรณ์ เป็นโรงเรียนเอกชน ประเภทสามัญศึกษาขนาดใหญ่ สังกัดสำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน กระทรวงศึกษาธิการ</p>
            <a href="#about" class="btn">เรียนรู้เพิ่มเติม</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="section-title">
            <h2>เกี่ยวกับโรงเรียน</h2>
        </div>
        <div class="about-content">
            <div class="about-text">
                <h3>โรงเรียนเมตตาวิทยา</h3>
                <p>ตั้งอยู่ในพื้นที่ของสำนักงานเขตพื้นที่การศึกษาประถมศึกษาเพชรบูรณ์เขต ๒ เปิดสอนตั้งแต่ระดับเตรียมปฐมวัย ระดับปฐมวัย ระดับประถมศึกษาและมัธยมศึกษา (อ.1-ม.6) ก่อตั้งเมื่อวันที่ 1 พฤษภาคม 2545 โดยมีอาจารย์เรือน ชนะวาที เป็นผู้จัดการ และอาจารย์จิตรา ชนะวาที เป็นผู้รับใบอนุญาตและผู้อำนวยการ</p>
                <p>โรงเรียนเมตตาวิทยา มีการจัดการเรียนการสอนตั้งแต่ชั้นเตรียมอนุบาล จนถึงชั้นมัธยมศึกษาปีที่ 6 โดยในระดับชั้นมัธยมศึกษาตอนปลายมีการจัดการเรียนการสอนในสายสามัญ ซึ่งมี 2 สาย คือ วิทย์-คณิต และศิลป์-สังคม</p>
                <a href="#history" class="btn">ประวัติโรงเรียน</a>
            </div>
            <div class="about-image">
                <img src="https://scontent.fphs1-1.fna.fbcdn.net/v/t39.30808-6/416299918_842506054340005_3794648078164921189_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=1CZbQCXdwHEQ7kNvwFECo9K&_nc_oc=AdkwRuJULug_Yt-xS40jTJ2ikcUkUY1boqMUJ6FJAUaogFw5bLv2CwiEzqkmRqdf8V0&_nc_zt=23&_nc_ht=scontent.fphs1-1.fna&_nc_gid=XAznFh-0mlyd0Gxl_1PJvg&oh=00_AfRB1Clg8JwVZnbQbm4nTArJzG3C7jWIhrqKu5bhh4_C7g&oe=6873E072" alt="โรงเรียนเมตตาวิทยา">
            </div>
        </div>
    </section>

    <!-- History Section -->
    <section class="section history" id="history">
        <div class="section-title">
            <h2>ประวัติโรงเรียน</h2>
        </div>
        <div class="timeline">
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2545</h3>
                    <p>ผู้บริหารได้ซื้อที่ดินจำนวน 8 ไร่ และได้สร้างอาคารเรียนหลังแรก โดยเปิดทำการเรียนการสอนตั้งแต่ระดับปฐมวัยจนถึงระดับมัธยมศึกษาปีที่ 1</p>
                </div>
            </div>
            <div class="timeline-item right">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2546</h3>
                    <p>ได้สร้างอาคารเรียนอีก 1 หลัง เพื่อรองรับนักเรียนที่มีจำนวนมากขึ้น</p>
                </div>
            </div>
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2547</h3>
                    <p>ได้ซื้อที่ดินเพิ่มอีกจำนวน 8 ไร่ และสร้างอาคารเรียนอีก 1 หลัง อาคารอำนวยการ 1 หลัง นักเรียนได้รับรางวัลชนะเลิศการประกวดบรรยายธรรมระดับประเทศ นักเรียนได้รับรางวัลเด็กและเยาวชนนำชื่อเสียงมาสู่ประเทศชาติ จำนวน 1 คน</p>
                </div>
            </div>
            <div class="timeline-item right">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2548</h3>
                    <p>ได้สร้างอาคารเรียนเพิ่มอีก 2 หลัง และสร้างอาคารเอนกประสงค์เพื่อใช้จัดกิจกรรมต่าง ๆ</p>
                </div>
            </div>
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2549</h3>
                    <p>ได้สร้างอาคารสระว่ายน้ำเพื่อให้นักเรียนได้มีทักษะในการว่ายน้ำ นักเรียนได้รับรางวัลชนะเลิศการประกวดพูดสุนทรพจน์ระดับประเทศ เรื่องคุณค่าจากสารานุกรมไทย และได้ผ่านการประกันคุณภาพการศึกษาจาก สมศ.</p>
                </div>
            </div>
            <div class="timeline-item right">
                <div class="timeline-content">
                    <h3>ปีการศึกษา 2550</h3>
                    <p>ได้สร้างอาคารเรียน 3 ชั้น 1 หลัง เพื่อเป็นอาคารเรียนระดับชั้นมัธยมศึกษาอย่างเป็นสัดส่วน นักเรียนได้รับรางวัลเด็กและเยาวชนเข้าคารวะนายกรัฐมนตรี จำนวน 1 คน</p>
                </div>
            </div>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="#" class="btn">ดูประวัติทั้งหมด</a>
        </div>
    </section>

    <!-- Facilities Section -->
    <section class="section facilities" id="facilities">
        <div class="section-title">
            <h2>อาคารสถานที่</h2>
        </div>
        <div class="facilities-grid">
            <div class="facility-card">
                <div class="facility-img">
                    <img src="https://via.placeholder.com/400x300/6a0dad/ffffff?text=อาคาร 1" alt="อาคาร 1">
                </div>
                <div class="facility-info">
                    <h3>อาคาร 1</h3>
                    <p>อาคารคอนกรีต 1 ชั้น ใช้เป็นห้องธุรการการเงิน ห้องผู้บริหาร ห้องแนะแนว ห้องประชุมราชาวดี</p>
                </div>
            </div>
            <div class="facility-card">
                <div class="facility-img">
                    <img src="https://via.placeholder.com/400x300/ffd700/ffffff?text=อาคาร 2-4" alt="อาคาร 2-4">
                </div>
                <div class="facility-info">
                    <h3>อาคาร 2-4</h3>
                    <p>อาคารคอนกรีต 2 ชั้น ใช้เป็นอาคารเรียนระดับประถมศึกษา</p>
                </div>
            </div>
            <div class="facility-card">
                <div class="facility-img">
                    <img src="https://via.placeholder.com/400x300/6a0dad/ffffff?text=อาคาร 5" alt="อาคาร 5">
                </div>
                <div class="facility-info">
                    <h3>อาคาร 5</h3>
                    <p>อาคารคอนกรีต 2 ชั้น ใช้เป็นอาคารเรียนระดับมัธยมศึกษา ห้องสมุดประถม ห้องนาฏศิลป์ ห้องสภานักเรียน ห้องศิลปศึกษา</p>
                </div>
            </div>
            <div class="facility-card">
                <div class="facility-img">
                    <img src="https://via.placeholder.com/400x300/ffd700/ffffff?text=อาคาร 6" alt="อาคาร 6">
                </div>
                <div class="facility-info">
                    <h3>อาคาร 6</h3>
                    <p>อาคารคอนกรีต 1 ชั้น ใช้เป็นอาคารเรียนฝ่ายปฐมวัย ห้องสื่อการเรียนรู้ปฐมวัย</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>เกี่ยวกับเรา</h3>
                <p>โรงเรียนเมตตาวิทยา จังหวัดเพชรบูรณ์ เป็นโรงเรียนเอกชน ประเภทสามัญศึกษาขนาดใหญ่ สังกัดสำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน กระทรวงศึกษาธิการ</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>ลิงก์ด่วน</h3>
                <a href="#home">หน้าแรก</a>
                <a href="#about">เกี่ยวกับโรงเรียน</a>
                <a href="#history">ประวัติโรงเรียน</a>
                <a href="#facilities">อาคารสถานที่</a>
                <a href="#">หลักสูตรการเรียน</a>
            </div>
            <div class="footer-section">
                <h3>ติดต่อเรา</h3>
                <div class="contact-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>ที่อยู่: โรงเรียนเมตตาวิทยา จังหวัดเพชรบูรณ์</p>
                </div>
                <div class="contact-info">
                    <i class="fas fa-phone"></i>
                    <p>โทรศัพท์: 0-5678-1234</p>
                </div>
                <div class="contact-info">
                    <i class="fas fa-envelope"></i>
                    <p>อีเมล: info@metta.ac.th</p>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 โรงเรียนเมตตาวิทยา. สงวนลิขสิทธิ์ทุกประการ.</p>
        </div>
    </footer>

    <script>
        // Mobile Navigation
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.innerHTML = navLinks.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
        
        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                    hamburger.innerHTML = '<i class="fas fa-bars"></i>';
                }
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Header Scroll Effect
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            header.style.background = window.scrollY > 50 ? 'rgba(249, 240, 255, 0.95)' : 'var(--nav-bg)';
            header.style.boxShadow = window.scrollY > 50 ? '0 2px 10px rgba(0, 0, 0, 0.1)' : 'none';
        });
        
        // Animation on Scroll
        const animateOnScroll = () => {
            const elements = document.querySelectorAll('.timeline-item, .facility-card, .about-content > div');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        };
        
        // Set initial state for animation
        document.querySelectorAll('.timeline-item, .facility-card, .about-content > div').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s ease';
        });
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
    </script>
</body>
</html>