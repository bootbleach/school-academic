<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Metta Academic</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --yellow: #FFD700;
            --purple: #6A0DAD;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Sarabun', sans-serif; }
        body {
            background: linear-gradient(135deg, var(--yellow), var(--purple));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            animation: fadeIn 1s ease-in;
            text-align: center;
            padding: 20px;
        }
        .main-container {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 600px;
            width: 100%;
        }
        .logo { width: 180px; height: auto; margin-bottom: 2rem; animation: pulse 2.5s infinite ease-in-out; }
        h1 { color: white; font-size: 3rem; margin-bottom: 1rem; font-weight: 800; text-shadow: 2px 2px 6px rgba(0,0,0,0.35); }
        p.lead { color: #ffffffee; font-size: 1.3rem; margin-bottom: 2rem; text-shadow: 1px 1px 4px rgba(0,0,0,0.25); }
        
        /* ปรับปรุงฟอร์มสำหรับมือถือ */
        .id-form {
            margin-bottom: 2rem;
        }
        .id-input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            font-size: 1.1rem;
            padding: 0.8rem 1.2rem;
            text-align: center;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .id-input:focus {
            background: white;
            border-color: var(--purple);
            box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
            outline: none;
        }
        .id-input::placeholder {
            color: #666;
            font-size: 0.95rem;
        }
        .check-btn {
            background: var(--purple);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.4);
            width: 100%;
        }
        .check-btn:hover {
            background: #5a0b94;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.5);
        }
        
        a.button {
            background: white; color: var(--purple); text-decoration: none; padding: 0.8rem 2.5rem; border-radius: 999px;
            font-weight: bold; font-size: 1.2rem; transition: all 0.3s ease; box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
            display: inline-block; margin: 0.5rem;
        }
        a.button:hover { background: var(--purple); color: white; transform: scale(1.05); }
        
        /* ปุ่มกลับหน้าหลัก */
        a.home-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-decoration: none;
            padding: 0.8rem 2.5rem;
            border-radius: 999px;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 0.5rem;
        }
        a.home-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }
        
        .credit { margin-top: 2rem; color: #ffffffaa; font-size: 0.9rem; }
        .divider {
            width: 80%; height: 1px; background-color: rgba(255,255,255,0.3); margin: 2.5rem auto;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        
        /* Mobile Responsive */
        @media (max-width: 576px) { 
            .main-container {
                padding: 1.5rem;
                margin: 10px;
            }
            h1 { font-size: 2.2rem; }
            p.lead { font-size: 1rem; }
            .logo { width: 130px; }
            .id-input {
                font-size: 1rem;
                padding: 0.7rem 1rem;
            }
            .check-btn {
                padding: 0.7rem 1.5rem;
                font-size: 1rem;
            }
            a.button, a.home-btn {
                padding: 0.7rem 2rem;
                font-size: 1.1rem;
                margin: 0.3rem;
                display: block;
                width: 100%;
                text-align: center;
            }
        }
        
        @media (max-width: 400px) {
            .main-container {
                padding: 1rem;
            }
            h1 { font-size: 1.8rem; }
            .logo { width: 110px; }
            .id-input {
                font-size: 0.95rem;
                padding: 0.6rem 0.8rem;
            }
            .id-input::placeholder {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <img src="https://img5.pic.in.th/file/secure-sv1/loho.png" alt="โลโก้โรงเรียนเมตตาวิทยา" class="logo">
        <h1>Metta Academic</h1>
        <p class="lead">ระบบบริหารจัดการและตรวจสอบผลการเรียน</p>
        
        <form action="public_grade_check.php" method="get" class="id-form">
            <input type="text" name="id_card_number" class="form-control id-input" 
                   placeholder="กรอกเลขประจำตัวประชาชน 13 หลัก" 
                   required maxlength="13" pattern="\d{13}">
            <button class="check-btn" type="submit">
                <i class="fas fa-search me-2"></i>ตรวจสอบผลการเรียน
            </button>
        </form>

        <div class="divider"></div>

        <p class="mb-3" style="font-size: 1rem;">สำหรับนักเรียน, ครู และผู้ดูแลระบบ</p>
        <a href="login.php" class="button">เข้าสู่ระบบ</a>
        <br>
        <a href="index.php" class="home-btn">
            <i class="fas fa-home me-2"></i>กลับหน้าหลัก
        </a>
    </div>
    <div class="credit">© WUTTICHAI MATHA</div>
</body>
</html>