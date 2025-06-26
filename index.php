<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โรงเรียนเมตตาวิทยา | Metta Wittaya School</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* CSS Reset & Basic Setup */
        :root {
            --primary-color: #5D3A9B; /* ม่วง */
            --secondary-color: #FFD700; /* เหลือง */
            --dark-color: #333;
            --light-color: #f9f9f9;
            --white-color: #ffffff;
            --text-color: #4A4A4A;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--white-color);
            color: var(--text-color);
            line-height: 1.7;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            padding: 80px 0;
        }

        h1, h3 {
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Enhanced H2 Styling */
        h2 {
            font-size: 2.8rem; /* Slightly larger */
            text-align: center;
            margin-bottom: 70px; /* More space below heading */
            position: relative;
            padding-bottom: 10px; /* Space for the underline */
            color: var(--primary-color);
        }

        h2::after {
            content: '';
            position: absolute;
            width: 80px; /* Length of the underline */
            height: 4px;
            background-color: var(--secondary-color); /* Yellow underline */
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            border-radius: 2px;
        }
        
        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 10px 50px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 5px 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar .logo img {
            height: 50px;
            transition: height 0.3s ease;
        }

        .navbar.scrolled .logo img {
            height: 40px;
        }

        .navbar .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
        }

        .navbar .nav-links li {
            margin-left: 30px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s ease, transform 0.2s ease; /* Add transform to transition */
        }

        /* Enhanced Navigation Links Hover */
        .navbar .nav-links a:hover,
        .navbar .nav-links a.active {
            color: var(--primary-color);
            transform: translateY(-2px); /* Lift slightly on hover */
        }

        /* Enhanced CTA Button */
        .cta-button {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 12px 25px; /* Slightly more padding */
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700; /* Bolder font */
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease; /* Add box-shadow to transition */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* Initial shadow */
            letter-spacing: 0.5px; /* Improve readability */
        }
        .cta-button:hover {
            background-color: #4a2d7a; /* Darker shade on hover */
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Larger shadow on hover */
        }
        
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        #hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white-color);
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://scontent.fphs1-1.fna.fbcdn.net/v/t39.30808-6/485309028_1166797811905348_3763208893204337953_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=86c6b0&_nc_ohc=6BT0RbsvPPQQ7kNvwHvrOIe&_nc_oc=AdkbBlAtgNRBw8lSZZ-RFXDqgFnUi8cm_0dMN0lglbIm0cCqZRYDZwvJWLgUgCEBGfU&_nc_zt=23&_nc_ht=scontent.fphs1-1.fna&_nc_gid=uwOP7etx8w696gvwFfqqkw&oh=00_AfNpnKJvFqf57Wp9f-ofX8r8had1agemryyRiGjw2pVN1w&oe=68613030') no-repeat center center/cover;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }

        /* Enhanced Hero Text */
        .hero-content h1 {
            font-size: 4.5rem; /* Slightly larger for impact */
            margin-bottom: 25px;
            color: var(--white-color);
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.6); /* Stronger shadow */
            line-height: 1.2;
        }
        
        .hero-content .motto {
            font-size: 2.2rem; /* Larger motto */
            font-weight: 600; /* Bolder motto */
            color: var(--secondary-color);
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.4); /* Add shadow to motto */
        }

        /* About Section */
        #about {
            background: var(--light-color);
        }
        .about-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            align-items: center;
            gap: 50px;
        }
        .about-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .about-content h3 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .about-content p {
            margin-bottom: 15px;
        }
        .about-content .founder {
            font-style: italic;
            color: #555;
            border-left: 3px solid var(--secondary-color);
            padding-left: 15px;
            margin-top: 20px;
        }

        /* Journey (Timeline) Section */
        #journey {
            position: relative;
        }
        .timeline {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: var(--secondary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -2px;
            z-index: -1;
            animation: grow-line 2s ease-out forwards;
        }

        @keyframes grow-line {
            from { height: 0; }
            to { height: 100%; }
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            right: -10px;
            background-color: var(--white-color);
            border: 4px solid var(--primary-color);
            top: 25px;
            border-radius: 50%;
            z-index: 1;
        }
        .timeline-item.left {
            left: 0;
        }
        .timeline-item.right {
            left: 50%;
        }
        .timeline-item.right::after {
            left: -10px;
        }

        /* Enhanced Timeline Content */
        .timeline-item.left .timeline-content {
            border-left: 4px solid var(--primary-color); /* Emphasize left items */
            border-right: none;
        }

        .timeline-item.right .timeline-content {
            border-right: 4px solid var(--primary-color); /* Emphasize right items */
            border-left: none;
        }

        .timeline-content {
            padding: 20px 30px;
            background-color: var(--white-color);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1); /* Stronger shadow */
            position: relative;
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .timeline-content:hover {
            transform: translateY(-5px); /* Lift on hover */
            box-shadow: 0 10px 25px rgba(93, 58, 155, 0.15); /* More pronounced shadow */
        }

        .timeline-content h3 {
            font-size: 1.6rem; /* Slightly larger heading */
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        /* News Section */
        #news { background: var(--light-color); }
        .news-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .news-card { background: var(--white-color); border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .news-card:hover { transform: translateY(-10px); box-shadow: 0 8px 25px rgba(93, 58, 155, 0.1); }
        .news-card img { width: 100%; height: 200px; object-fit: cover; }
        .news-content { padding: 25px; }
        .news-content h3 { font-size: 1.3rem; margin-bottom: 10px; }
        .news-content .date { color: #999; font-size: 0.9rem; margin-bottom: 15px; }
        .news-content a { text-decoration: none; color: var(--primary-color); font-weight: 700; }

        /* Facebook Feed Styling */
        .fb-page,
        .fb-page span,
        .fb-page span iframe[style] {
            width: 100% !important;
        }

        #facebook-feed .container {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
        }


        /* Contact Section */
        .contact-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; }
        .contact-info h3 { font-size: 2rem; margin-bottom: 20px; }
        .contact-info p { margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: flex-start; }
        
        /* Enhanced Contact Info Icons */
        .contact-info i {
            color: var(--primary-color);
            margin-right: 18px; /* Slightly more space */
            width: 25px; /* Ensure consistent alignment */
            margin-top: 3px;
            font-size: 1.3rem; /* Larger icon size */
            text-align: center;
        }
        .map-container iframe { width: 100%; height: 400px; border: 0; border-radius: 10px; }

        /* Enhanced Footer */
        footer {
            background-color: var(--primary-color);
            color: var(--white-color);
            text-align: center;
            padding: 40px 20px; /* More padding */
            font-size: 0.95rem;
        }
        footer p {
            margin-bottom: 8px; /* Space between paragraphs */
        }
        footer .credit {
            margin-top: 15px; /* More space above credit */
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .navbar { padding: 10px 20px; }
            .about-wrapper { grid-template-columns: 1fr; }
            .about-image { display: none; } /* Hide image on smaller screens to save space */
            .contact-wrapper { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            h1 { font-size: 3rem; }
            h2 { font-size: 2rem; }
            .hero-content .motto { font-size: 1.5rem; }

            .menu-toggle { display: block; }
            .navbar .nav-links { display: none; flex-direction: column; position: absolute; top: 70px; left: 0; width: 100%; background: var(--white-color); box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 20px 0; }
            .navbar .nav-links.active { display: flex; }
            .navbar .nav-links li { margin: 15px 0; text-align: center; }
            .navbar .nav-links .cta-button { margin: 15px auto 0 auto; display: block; width: fit-content; }
            
            /* Timeline Responsive */
            .timeline::after { left: 20px; }
            .timeline-item { width: 100%; padding-left: 60px; padding-right: 10px; }
            .timeline-item.left, .timeline-item.right { left: 0; }
            .timeline-item::after { left: 10px; }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="#hero" class="logo" style="display: flex; align-items: center; gap: 5px; text-decoration: none;">
                <img src="https://img5.pic.in.th/file/secure-sv1/loho.png" alt="โลโก้โรงเรียนเมตตาวิทยา" style="height: 40px;">
                <span style="color: black; font-weight: bold; font-family: Arial, sans-serif; text-decoration: none;">METTAWITTAYA SCHOOL</span>
            </a>
            
            <div class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <ul class="nav-links" id="nav-links">
                <li><a href="#hero" class="active">หน้าแรก</a></li>
                <li><a href="#about">เกี่ยวกับเรา</a></li>
                <li><a href="#journey">เส้นทางของเรา</a></li>
                <li><a href="#news">ข่าวสาร</a></li>
                <li><a href="#contact">ติดต่อเรา</a></li>
                <li>
                    <a href="academic_index.php" class="cta-button" style="background-color: #6a1b9a; color: white; padding: 12px 24px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block;">
                        <i class="fas fa-sign-in-alt"></i> ระบบ Metta Academic
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <section id="hero">
        <div class="hero-content" data-aos="fade-up">
            <h1>โรงเรียนเมตตาวิทยา</h1>
            <p class="motto">"ต้นแบบคนดี ศักดิ์ศรีแห่งเมตตา"</p>
        </div>
    </section>

    <main>
        <section id="about">
            <div class="container">
                <h2 data-aos="fade-up">เกี่ยวกับเมตตาวิทยา</h2>
                <div class="about-wrapper">
                    <div class="about-image" data-aos="fade-right">
                        <img src="https://img5.pic.in.th/file/secure-sv1/loho.png" alt="โรงเรียนเมตตาวิทยา">
                    </div>
                    <div class="about-content" data-aos="fade-left">
                        <h3>สถาบันแห่งการเรียนรู้ คู่คุณธรรม</h3>
                        <p>
                            โรงเรียนเมตตาวิทยา จังหวัดเพชรบูรณ์ เป็นโรงเรียนเอกชน ประเภทสามัญศึกษาขนาดใหญ่ สังกัดสำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน เปิดทำการเรียนการสอนตั้งแต่ระดับเตรียมปฐมวัย, ปฐมวัย, ประถมศึกษา และมัธยมศึกษา (อ.1 - ม.6)
                        </p>
                        <p>
                            เรามุ่งมั่นที่จะเป็นสถาบันการศึกษาที่สามารถรองรับและให้บริการนักเรียนในพื้นที่และอำเภอใกล้เคียงให้ได้รับการศึกษาขั้นพื้นฐานอย่างมีคุณภาพและต่อเนื่อง
                        </p>
                        <div class="founder">
                            ก่อตั้งเมื่อวันที่ 1 พฤษภาคม 2545<br>
                            โดยมี <strong>อาจารย์จิตรา ชนะวาที</strong> เป็นผู้รับใบอนุญาตและผู้อำนวยการ
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="journey">
            <div class="container">
                <h2 data-aos="fade-up">เส้นทางของเรา</h2>
                <div class="timeline">
                    <div class="timeline-item left" data-aos="fade-right">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2545</h3>
                            <p>ก่อตั้งโรงเรียนบนที่ดิน 8 ไร่ เปิดสอนตั้งแต่ระดับปฐมวัยถึงมัธยมศึกษาปีที่ 1</p>
                        </div>
                    </div>
                    <div class="timeline-item right" data-aos="fade-left">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2549</h3>                           
                            <p>สร้างสระว่ายน้ำ และผ่านการประกันคุณภาพการศึกษาจาก สมศ. เป็นครั้งแรก</p>
                        </div>
                    </div>
                    <div class="timeline-item left" data-aos="fade-right">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2551</h3>
                            <p>ได้รับรางวัลโรงเรียนส่งเสริมสุขภาพระดับทอง และเริ่มเปิดสอนระดับมัธยมศึกษาตอนปลาย</p>
                        </div>
                    </div>
                    <div class="timeline-item right" data-aos="fade-left">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2552</h3>
                            <p>ได้รับรางวัลพระราชทาน "เสาเสมาธรรมจักร" สถานศึกษาที่ทำคุณประโยชน์ต่อพระพุทธศาสนา</p>
                        </div>
                    </div>
                    <div class="timeline-item left" data-aos="fade-right">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2555</h3>
                            <p>ฉลอง "1 ทศวรรษ เมตตาวิทยา" นักเรียนได้รับรางวัลระดับโลกและระดับชาติมากมาย</p>
                        </div>
                    </div>
                    <div class="timeline-item right" data-aos="fade-left">
                        <div class="timeline-content">
                            <h3>พ.ศ. 2556 - ปัจจุบัน</h3>
                            <p>พัฒนาอย่างไม่หยุดยั้ง สร้างอาคารเพิ่มเติม คว้ารางวัลนักเรียนพระราชทาน และรางวัลระดับประเทศต่อเนื่อง</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <section id="news">
            <div class="container">
                <h2 data-aos="fade-up">ข่าวสารและกิจกรรม</h2>
                <body>

                <div id="fb-root"></div>
                <script async defer crossorigin="anonymous" 
                    src="https://connect.facebook.net/th_TH/sdk.js#xfbml=1&version=v19.0&appId=YOUR_APP_ID" 
                    nonce="yourNonceHere"></script>

                <main>
                    <section id="facebook-feed">
                        <div class="container">
                            <div class="fb-page" 
                                data-href="https://www.facebook.com/p/โรงเรียนเมตตาวิทยา-100057254208065/" 
                                data-tabs="timeline" 
                                data-width="1000" 
                                data-height="700" 
                                data-small-header="false" 
                                data-adapt-container-width="false" 
                                data-hide-cover="false" 
                                data-show-facepile="true">
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </section>
    </main>

    <section id="contact">
        <div class="container">
            <h2 data-aos="fade-up">ติดต่อเรา</h2>
            <div class="contact-wrapper">
                <div class="contact-info" data-aos="fade-right">
                    <h3>โรงเรียนเมตตาวิทยา (หล่มสัก)</h3>
                    <p><i class="fas fa-map-marker-alt"></i><span>52/2 หมู่ 1 ถนนสระบุรี-หล่มสัก ตำบลหนองไขว่ อำเภอหล่มสัก จังหวัดเพชรบูรณ์ 67110</span></p>
                    <p><i class="fas fa-phone"></i><span>โทรศัพท์: 056-704304</span></p>
                    <p><i class="fas fa-fax"></i><span>โทรสาร: 056-704305</span></p>
                    <p><i class="fab fa-facebook-square"></i><a href="https://www.facebook.com/p/โรงเรียนเมตตาวิทยา-100057254208065/?locale=th_TH" target="_blank" style="color:var(--text-color); text-decoration:none;">ติดตามเราบน Facebook</a></p>
                </div>
                <div class="map-container" data-aos="fade-left">
                   <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1350.606986298609!2d101.22823317197673!3d16.772026417147554!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31204e3eff77c979%3A0x9b9c1dbc696fd8b6!2z4LmC4Lij4LiH4LmA4Lij4Li14Lii4LiZ4LmA4Lih4LiV4LiV4Liy4Lin4Li04LiX4Lii4Liy!5e0!3m2!1sth!2sth!4v1749701590628!5m2!1sth!2sth" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> โรงเรียนเมตตาวิทยา. สงวนลิขสิทธิ์.</p>
            <p class="credit">ออกแบบและพัฒนาโดย: wuttichai matha</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
        });
        
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Add shadow to navbar on scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>

</body>
</html>
