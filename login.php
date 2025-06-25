<!DOCTYPE html> 
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-purple: #5e2ab1;
            --golden-yellow: #fccb0b;
            --dark-bg-start: #0f0c29;
            --dark-bg-mid: #302b63;
            --dark-bg-end: #24243e;
            --text-light: #f0f0f0;
            --text-dark: #333;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--dark-bg-start), var(--dark-bg-mid), var(--dark-bg-end));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Sarabun', sans-serif;
            position: relative;
            overflow: hidden;
        }

        /* --- Animated Particle Background --- */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .particle {
            position: absolute;
            background-color: var(--golden-yellow);
            border-radius: 50%;
            opacity: 0.3;
            animation: floatParticles 25s infinite ease-in-out;
        }
        @keyframes floatParticles {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-100vh) translateX(50vw); }
        }
        /* สร้าง particles แบบสุ่มด้วย JS เพื่อประสิทธิภาพที่ดีกว่า */

        /* --- Enhanced Glassmorphism Login Container --- */
        .login-container {
            max-width: 450px;
            width: 95%;
            padding: 3rem;
            border-radius: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            z-index: 1;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .login-container:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.25);
        }

        .logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.5rem;
            display: block;
            border: 3px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 0 30px rgba(var(--rgb-golden-yellow), 0.5);
        }
        
        .login-container h2 {
            color: var(--text-light);
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            margin-bottom: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--deep-purple);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            border: 2px solid transparent;
            border-radius: 0.75rem;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            background: white;
            border-color: var(--deep-purple);
            box-shadow: 0 0 0 4px rgba(94, 42, 177, 0.2);
            outline: none;
        }
        .form-control:focus + .input-icon { color: var(--deep-purple); }
        .form-control::placeholder { color: #aaa; }

        /* --- Login Button with Shine Effect --- */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--deep-purple) 0%, #a855f7 100%);
            border: none;
            border-radius: 0.75rem;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
            box-shadow: 0 8px 20px rgba(94, 42, 177, 0.4);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(94, 42, 177, 0.5);
        }
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transform: skewX(-30deg);
            transition: left 0.7s ease-in-out;
        }
        .btn-login:hover::before { left: 150%; }

        .btn-outline-secondary {
            border-color: rgba(255,255,255,0.3);
            color: rgba(255,255,255,0.7);
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background-color: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        
        .footer-text {
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
        }
        
        /* --- Alert and Loading Spinner --- */
        .alert-danger {
            background: rgba(224, 36, 36, 0.1);
            color: #ff8a8a;
            border: 1px solid rgba(224, 36, 36, 0.3);
            border-radius: 0.75rem;
            backdrop-filter: blur(5px);
        }
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 12, 41, 0.8);
            backdrop-filter: blur(10px);
            display: flex; justify-content: center; align-items: center;
            z-index: 9999; opacity: 0; visibility: hidden;
            transition: all 0.3s ease;
        }
        .loading-overlay.show { opacity: 1; visibility: visible; }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--golden-yellow);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* --- Responsive Design --- */
        @media (max-width: 480px) {
            .login-container { padding: 2rem 1.5rem; }
            .login-container h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles-js"></div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <img src="https://img5.pic.in.th/file/secure-sv1/418317294_877252587526540_4302785761296289314_n.jpg" alt="Metta Academic Logo" class="logo">
        
        <h2>Metta Academic</h2>
        <p class="subtitle">ระบบบริหารจัดการข้อมูลการเรียนการสอน</p>
        
        <?php if (isset($message)) echo $message; ?>
        
        <form action="authenticate.php" method="post" id="loginForm">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="text" name="username" id="username" class="form-control" placeholder="ชื่อผู้ใช้ (Username)" required autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="รหัสผ่าน (Password)" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <span><i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ</span>
            </button>

            <a href="index.php" class="btn btn-outline-secondary mt-3">
                <i class="fas fa-home me-1"></i> กลับหน้าหลัก
            </a>
        </form>
        
        <p class="footer-text mt-4">
            &copy; <?php echo date('Y'); ?> Metta Academic System. All rights reserved.
        </p>
    </div>

    <script>
        // --- Form Submit Loading ---
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').classList.add('show');
        });

        // --- Entry Animation ---
        window.addEventListener('load', function() {
            const container = document.querySelector('.login-container');
            container.style.transition = 'opacity 0.6s ease-out, transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0) scale(1)';
            
            // --- Random Particle Generation ---
            const particlesContainer = document.getElementById('particles-js');
            const particleCount = 30;
            for (let i = 0; i < particleCount; i++) {
                let particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 5 + 1;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                particle.style.top = `${Math.random() * 100}%`;
                particle.style.left = `${Math.random() * 100}%`;
                
                const animationDuration = Math.random() * 20 + 15; // 15-35 seconds
                const animationDelay = Math.random() * 15;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `-${animationDelay}s`;

                particlesContainer.appendChild(particle);
            }
        });
        
        // Initial state for entry animation
        document.addEventListener('DOMContentLoaded', () => {
             const container = document.querySelector('.login-container');
             container.style.opacity = '0';
             container.style.transform = 'translateY(50px) scale(0.95)';
        });
    </script>
</body>
</html>