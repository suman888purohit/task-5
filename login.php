<?php
require 'db.php';

// ✅ Prevent redirect loop if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'danger';

// Handle social login (simulated for now)
if (isset($_GET['social'])) {
    $social = $_GET['social'];
    
    if ($social === 'google' || $social === 'github') {
        // In a real app, you would:
        // 1. Redirect to Google/GitHub OAuth
        // 2. Handle callback
        // 3. Create/authenticate user
        
        // For now, show a demo message
        $message = "Social login with " . ucfirst($social) . " is not implemented yet. Use form login.";
        $messageType = 'info';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ✅ Server-side validation
    if ($username === '' || $password === '') {
        $message = 'Username and password are required.';
    } else {
        // ✅ Prepared statement with role included
        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ✅ Verify password securely
        if ($user && password_verify($password, $user['password'])) {
            // Secure session handling
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role']; // store role for access control

            header('Location: index.php'); // redirect to posts page
            exit;
        } else {
            $message = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Blog Platform</title>
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
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #333333;
            --text-light: #666666;
            --border-light: #ffddcc;
            --success: #44cc88;
            --danger: #ff4444;
            --info: #4499ff;
            --shadow-light: 0 10px 40px rgba(255, 68, 68, 0.08);
            --shadow-medium: 0 20px 60px rgba(255, 68, 68, 0.12);
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Subtle background pattern */
        .bg-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(255, 68, 68, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 136, 68, 0.03) 0%, transparent 50%);
            z-index: 0;
        }
        
        /* Elegant floating shapes */
        .float-shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            opacity: 0.05;
            filter: blur(40px);
            z-index: 0;
            animation: float 25s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 250px;
            height: 250px;
            bottom: 15%;
            right: 15%;
            animation-delay: 5s;
            animation-direction: reverse;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            top: 60%;
            left: 5%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -40px) scale(1.1); }
            66% { transform: translate(-20px, 30px) scale(0.9); }
        }
        
        /* Main container */
        .login-container {
            width: 100%;
            max-width: 420px;
            z-index: 1;
            position: relative;
        }
        
        /* Elegant Card */
        .login-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 50px 45px;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium), 0 30px 80px rgba(255, 68, 68, 0.15);
        }
        
        /* Accent border */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
        }
        
        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-wrapper {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 
                0 15px 35px rgba(255, 68, 68, 0.2),
                inset 0 4px 10px rgba(255, 255, 255, 0.3);
            position: relative;
        }
        
        .logo-wrapper::after {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            border-radius: 50%;
            z-index: -1;
            filter: blur(15px);
            opacity: 0.4;
        }
        
        .logo-icon {
            color: white;
            font-size: 2.2rem;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-header p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Message Alerts */
        .alert-custom {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-danger {
            background: rgba(255, 68, 68, 0.08);
            border-color: rgba(255, 68, 68, 0.2);
            color: #cc3333;
        }
        
        .alert-info {
            background: rgba(68, 153, 255, 0.08);
            border-color: rgba(68, 153, 255, 0.2);
            color: #3366cc;
        }
        
        .alert-success {
            background: rgba(68, 204, 136, 0.08);
            border-color: rgba(68, 204, 136, 0.2);
            color: #33aa66;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Form Styling */
        .form-group {
            margin-bottom: 28px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: white;
            border: 2px solid #ffddcc;
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-dark);
            font-family: 'Inter', sans-serif;
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.02);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 
                0 0 0 4px rgba(255, 136, 68, 0.1),
                inset 0 2px 6px rgba(0, 0, 0, 0.02);
            background: #fffcfc;
        }
        
        .form-control::placeholder {
            color: #ccbbaa;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffaa88;
            font-size: 1.1rem;
        }
        
        /* Remember Me & Forgot */
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 35px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #ffccaa;
            background: white;
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-orange);
            border-color: var(--primary-orange);
        }
        
        .form-check-label {
            color: var(--text-light);
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .forgot-link {
            color: var(--primary-red);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-link:hover {
            color: var(--primary-orange);
            text-decoration: underline;
        }
        
        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 
                0 8px 25px rgba(255, 68, 68, 0.25),
                0 4px 10px rgba(255, 136, 68, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.6s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 35px rgba(255, 68, 68, 0.35),
                0 8px 20px rgba(255, 136, 68, 0.25);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 35px 0;
            color: #ddbbaa;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #ffddcc, transparent);
        }
        
        .divider span {
            padding: 0 20px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Social Login */
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn-social {
            padding: 14px;
            background: white;
            border: 2px solid #ffddcc;
            border-radius: 12px;
            color: var(--text-light);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
        }
        
        .btn-social:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-google {
            border-color: #DB4437;
            color: #DB4437;
        }
        
        .btn-google:hover {
            background: #DB4437;
            color: white;
            box-shadow: 0 10px 25px rgba(219, 68, 55, 0.2);
        }
        
        .btn-github {
            border-color: #333;
            color: #333;
        }
        
        .btn-github:hover {
            background: #333;
            color: white;
            box-shadow: 0 10px 25px rgba(51, 51, 51, 0.2);
        }
        
        /* Social Login Info */
        .social-info {
            background: rgba(68, 153, 255, 0.05);
            border: 1px dashed rgba(68, 153, 255, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            color: #3366cc;
            font-size: 0.85rem;
        }
        
        .social-info i {
            margin-right: 8px;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 35px;
            padding-top: 30px;
            border-top: 1px solid #ffddcc;
        }
        
        .login-footer p {
            color: var(--text-light);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .link-register {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: white;
            border: 2px solid #ffddcc;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .link-register:hover {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                padding: 40px 30px;
            }
            
            .login-header h1 {
                font-size: 1.7rem;
            }
            
            .logo-wrapper {
                width: 75px;
                height: 75px;
            }
            
            .logo-icon {
                font-size: 1.8rem;
            }
            
            .social-login {
                grid-template-columns: 1fr;
            }
            
            .login-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        /* Loading Animation */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin {
            animation: spin 1s linear infinite;
        }
        
        /* Modal for social login */
        .social-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .social-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .social-modal.active .modal-content {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Background Elements -->
    <div class="bg-pattern"></div>
    <div class="float-shape shape-1"></div>
    <div class="float-shape shape-2"></div>
    <div class="float-shape shape-3"></div>
    
    <!-- Main Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-wrapper">
                    <i class="bi bi-lock logo-icon"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to access your dashboard and continue blogging</p>
            </div>
            
            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert-custom alert-<?php echo $messageType; ?>">
                    <i class="bi bi-<?php 
                        echo $messageType === 'danger' ? 'exclamation-circle' : 
                        ($messageType === 'info' ? 'info-circle' : 'check-circle');
                    ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="post" id="loginForm">
                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="bi bi-person-circle"></i> Username
                    </label>
                    <div class="input-with-icon">
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control" 
                               placeholder="Enter your username"
                               required
                               minlength="3"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <span class="input-icon">
                            <i class="bi bi-at"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="bi bi-key"></i> Password
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required
                               minlength="8">
                        <span class="input-icon">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="login-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="forgot-link" id="forgotPassword">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>Or continue with</span>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <a href="?social=google" class="btn-social btn-google">
                    <i class="bi bi-google"></i>
                    Google
                </a>
                <a href="?social=github" class="btn-social btn-github">
                    <i class="bi bi-github"></i>
                    GitHub
                </a>
            </div>
            
            <!-- Social Login Info -->
            <div class="social-info">
                <i class="bi bi-info-circle"></i>
                Note: Social login is a demo feature. In a real app, this would connect to actual OAuth providers.
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>Don't have an account yet?</p>
                <a href="register.php" class="link-register">
                    <i class="bi bi-person-plus"></i>
                    Create New Account
                </a>
            </div>
        </div>
    </div>
    
    <!-- Modal for Demo -->
    <div class="social-modal" id="socialModal">
        <div class="modal-content">
            <div class="logo-wrapper" style="width: 70px; height: 70px; margin-bottom: 20px;">
                <i class="bi bi-shield-check logo-icon"></i>
            </div>
            <h3 style="margin-bottom: 15px; color: var(--text-dark);">Social Login Demo</h3>
            <p style="color: var(--text-light); margin-bottom: 25px;">
                This is a demonstration of social login functionality. In a production environment, 
                this would redirect you to <span id="socialProvider">Google</span>'s OAuth page.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button class="btn-social btn-google" id="continueBtn" style="padding: 12px 24px;">
                    Continue Demo
                </button>
                <button class="btn-social" onclick="closeModal()" 
                        style="padding: 12px 24px; border-color: var(--primary-red); color: var(--primary-red);">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Form submission animation
        const submitBtn = document.getElementById('submitBtn');
        const loginForm = document.getElementById('loginForm');
        
        loginForm.addEventListener('submit', function(e) {
            // Show loading state
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Signing In...';
            submitBtn.disabled = true;
            
            // Visual feedback for form
            loginForm.style.opacity = '0.9';
            loginForm.style.transform = 'scale(0.995)';
            
            // Create spinner style if not exists
            if (!document.querySelector('#spinnerStyle')) {
                const style = document.createElement('style');
                style.id = 'spinnerStyle';
                style.textContent = `
                    .spin {
                        animation: spin 1s linear infinite;
                    }
                `;
                document.head.appendChild(style);
            }
        });
        
        // Social Login Functionality
        const googleBtn = document.querySelector('.btn-google');
        const githubBtn = document.querySelector('.btn-github');
        const socialModal = document.getElementById('socialModal');
        const socialProvider = document.getElementById('socialProvider');
        const continueBtn = document.getElementById('continueBtn');
        let currentSocial = '';
        
        googleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentSocial = 'Google';
            socialProvider.textContent = currentSocial;
            continueBtn.className = 'btn-social btn-google';
            continueBtn.style.padding = '12px 24px';
            openModal();
        });
        
        githubBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentSocial = 'GitHub';
            socialProvider.textContent = currentSocial;
            continueBtn.className = 'btn-social btn-github';
            continueBtn.style.padding = '12px 24px';
            openModal();
        });
        
        continueBtn.addEventListener('click', function() {
            // Simulate successful social login
            closeModal();
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-custom alert-info';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i>
                <span>Demo: Successfully authenticated with ${currentSocial}. In a real app, you would be redirected.</span>
            `;
            
            // Insert after the existing alert or at the top
            const existingAlert = document.querySelector('.alert-custom');
            if (existingAlert) {
                existingAlert.parentNode.insertBefore(alertDiv, existingAlert.nextSibling);
            } else {
                const header = document.querySelector('.login-header');
                header.parentNode.insertBefore(alertDiv, header.nextSibling);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-10px)';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        });
        
        function openModal() {
            socialModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            socialModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal on outside click
        socialModal.addEventListener('click', function(e) {
            if (e.target === socialModal) {
                closeModal();
            }
        });
        
        // Forgot Password Demo
        const forgotLink = document.getElementById('forgotPassword');
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show info message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-custom alert-info';
            alertDiv.innerHTML = `
                <i class="bi bi-envelope"></i>
                <span>Forgot password feature would send a reset link to your email.</span>
            `;
            
            const existingAlert = document.querySelector('.alert-custom');
            if (existingAlert) {
                existingAlert.parentNode.insertBefore(alertDiv, existingAlert.nextSibling);
            } else {
                const header = document.querySelector('.login-header');
                header.parentNode.insertBefore(alertDiv, header.nextSibling);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-10px)';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        });
        
        // Input focus effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Add validation feedback
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#44cc88';
                    this.style.boxShadow = '0 0 0 3px rgba(68, 204, 136, 0.1)';
                } else {
                    this.style.borderColor = '#ff4444';
                    this.style.boxShadow = '0 0 0 3px rgba(255, 68, 68, 0.1)';
                }
            });
        });
        
        // Auto-focus username field
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+G for Google login
            if (e.ctrlKey && e.key === 'g') {
                e.preventDefault();
                googleBtn.click();
            }
            
            // Ctrl+H for GitHub login
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                githubBtn.click();
            }
            
            // Escape to close modal
            if (e.key === 'Escape' && socialModal.classList.contains('active')) {
                closeModal();
            }
        });
    </script>
</body>
</html>