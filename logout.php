<?php
require 'db.php';

// Store username for goodbye message
$username = $_SESSION['username'] ?? 'User';

// Destroy session
session_unset();
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | Blog Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-red: #ff4444;
            --primary-orange: #ff8844;
            --red-light: #ffeeee;
            --orange-light: #fff4ee;
            --red-soft: #ffcccc;
            --orange-soft: #ffddcc;
            --light-bg: #fffaf8;
            --card-bg: rgba(255, 255, 255, 0.97);
            --text-dark: #333333;
            --text-light: #666666;
            --border-light: #ffddcc;
            --success: #44cc88;
            --danger: #ff4444;
            --warning: #ffbb44;
            --shadow-light: 0 10px 40px rgba(255, 68, 68, 0.08);
            --shadow-medium: 0 20px 60px rgba(255, 68, 68, 0.12);
            --shadow-hover: 0 25px 80px rgba(255, 68, 68, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #fffaf8 0%, #fff0f0 50%, #fff8f0 100%);
            color: var(--text-dark);
            padding: 30px 20px;
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Background pattern */
        .bg-pattern {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 68, 68, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(255, 136, 68, 0.03) 0%, transparent 50%);
            z-index: -1;
        }
        
        /* Floating shapes */
        .float-shape {
            position: fixed;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            opacity: 0.05;
            filter: blur(60px);
            z-index: -1;
            animation: float 30s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -100px;
            right: -100px;
            animation-delay: 10s;
            animation-direction: reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 40px) scale(0.9); }
        }
        
        /* Main container */
        .logout-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Logout card */
        .logout-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .logout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
        }
        
        /* Animation container */
        .animation-container {
            margin-bottom: 30px;
        }
        
        .logout-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--red-light), var(--orange-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: var(--primary-red);
            font-size: 3.5rem;
            border: 3px solid var(--border-light);
            position: relative;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .logout-icon::after {
            content: '';
            position: absolute;
            width: 140px;
            height: 140px;
            border: 2px solid var(--primary-orange);
            border-radius: 50%;
            opacity: 0.2;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0.2; }
            50% { opacity: 0.5; }
            100% { transform: scale(1.1); opacity: 0; }
        }
        
        /* Text content */
        .goodbye-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }
        
        .user-name {
            color: var(--primary-red);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: inline-block;
            padding: 8px 20px;
            background: var(--red-light);
            border-radius: 50px;
        }
        
        .message {
            color: var(--text-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .countdown {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-red);
            margin: 30px 0;
            height: 60px;
        }
        
        .redirect-info {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
        }
        
        .redirect-info a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 500;
        }
        
        .redirect-info a:hover {
            text-decoration: underline;
        }
        
        /* Progress bar */
        .progress-container {
            width: 100%;
            height: 6px;
            background: var(--red-light);
            border-radius: 3px;
            margin: 30px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            border-radius: 3px;
            width: 0%;
            animation: progress 5s linear forwards;
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
        
        /* Stats */
        .logout-stats {
            display: flex;
            justify-content: space-around;
            margin: 40px 0;
            padding: 25px;
            background: var(--light-bg);
            border-radius: 16px;
            border: 1px solid var(--border-light);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-red);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Footer */
        .logout-footer {
            margin-top: 40px;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .logout-card {
                padding: 40px 25px;
            }
            
            .goodbye-text h1 {
                font-size: 2rem;
            }
            
            .logout-icon {
                width: 100px;
                height: 100px;
                font-size: 2.8rem;
            }
            
            .logout-stats {
                flex-direction: column;
                gap: 20px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px 15px;
            }
            
            .logout-card {
                padding: 30px 20px;
            }
            
            .goodbye-text h1 {
                font-size: 1.8rem;
            }
            
            .logout-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--red-light);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary-red), var(--primary-orange));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(var(--primary-orange), var(--primary-red));
        }
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="bg-pattern"></div>
    <div class="float-shape shape-1"></div>
    <div class="float-shape shape-2"></div>
    
    <!-- Main container -->
    <div class="logout-container">
        <div class="logout-card">
            <!-- Animation container -->
            <div class="animation-container">
                <div class="logout-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
            </div>
            
            <!-- Text content -->
            <div class="goodbye-text">
                <h1>Goodbye!</h1>
                <div class="user-name">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($username); ?>
                </div>
                <p class="message">
                    You have been successfully logged out. Thank you for using our blog platform.
                    We hope to see you again soon!
                </p>
            </div>
            
            <!-- Stats (optional, can be removed if not needed) -->
            <div class="logout-stats">
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-label">Session Ended</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="stat-label">Securely Logged Out</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div class="stat-label">Safe Exit</div>
                </div>
            </div>
            
            <!-- Countdown -->
            <div class="countdown" id="countdown">5</div>
            
            <!-- Progress bar -->
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            
            <!-- Redirect information -->
            <div class="redirect-info">
                <p>
                    You will be automatically redirected to the login page in 
                    <span id="seconds">5</span> seconds.
                </p>
                <p class="mt-2">
                    If you are not redirected, <a href="login.php">click here to login</a>.
                </p>
            </div>
            
            <!-- Footer -->
            <div class="logout-footer">
                <p>
                    <i class="bi bi-info-circle me-1"></i>
                    For security reasons, please close your browser if you're on a shared computer.
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Countdown timer
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        const secondsElement = document.getElementById('seconds');
        
        const countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            secondsElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Redirect immediately if clicked
        document.querySelector('.logout-card').addEventListener('click', function() {
            clearInterval(countdownInterval);
            window.location.href = 'login.php';
        });
        
        // Keyboard shortcut for immediate redirect
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                clearInterval(countdownInterval);
                window.location.href = 'login.php';
            }
        });
        
        // Add visual effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add typing effect for goodbye message
            const message = document.querySelector('.message');
            const originalText = message.textContent;
            message.textContent = '';
            let i = 0;
            
            function typeWriter() {
                if (i < originalText.length) {
                    message.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 30);
                }
            }
            
            // Start typing effect after a short delay
            setTimeout(typeWriter, 500);
            
            // Add particle animation
            createParticles();
        });
        
        function createParticles() {
            const colors = ['#ff4444', '#ff8844', '#ffcccc', '#ffddcc'];
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.style.position = 'fixed';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.borderRadius = '50%';
                particle.style.top = Math.random() * 100 + 'vh';
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.opacity = '0.3';
                particle.style.zIndex = '0';
                particle.style.pointerEvents = 'none';
                
                // Animation
                particle.animate([
                    { transform: 'translateY(0px)', opacity: 0.3 },
                    { transform: `translateY(${Math.random() * 100 - 50}px) translateX(${Math.random() * 100 - 50}px)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    easing: 'ease-out'
                });
                
                document.body.appendChild(particle);
                
                // Remove particle after animation
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.remove();
                    }
                }, 3000);
            }
        }
    </script>
</body>
</html>