<?php
require 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'danger'; // danger, success, warning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'user';

    // Server-side validation
    if ($username === '' || $password === '') {
        $message = 'Username and password are required.';
    } elseif (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $message = 'Username already exists.';
        } else {
            // Hash password before storing
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert with chosen role
            $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            try {
                $stmt->execute([$username, $hash, $role]);
                $message = 'Registration successful! You can now login.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Blog Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-light: #fff5f5;
            --primary-soft: #ffebee;
            --primary: #ff6b6b;
            --primary-dark: #ff4757;
            --secondary: #ff8c42;
            --accent: #ff9a76;
            --dark: #2d3436;
            --light: #f8f9fa;
            --gray-light: #e9ecef;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #e17055;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #fff5f5 0%, #fff0eb 100%);
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background elements */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.15;
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: var(--primary);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 250px;
            height: 250px;
            background: var(--secondary);
            bottom: -80px;
            right: -80px;
            animation-delay: 5s;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            background: var(--accent);
            top: 50%;
            left: 80%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 40px) rotate(240deg); }
        }
        
        /* Main container */
        .register-container {
            width: 100%;
            max-width: 450px;
            z-index: 1;
            position: relative;
        }
        
        /* Card styling */
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(255, 107, 107, 0.15);
            border: 1px solid rgba(255, 235, 238, 0.8);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(255, 107, 107, 0.2);
        }
        
        /* Header */
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }
        
        .register-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .register-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        /* Message alert */
        .alert-custom {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(0, 184, 148, 0.1);
            color: #006442;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: rgba(255, 107, 107, 0.1);
            color: #cc3d2e;
            border-left: 4px solid var(--danger);
        }
        
        .alert-warning {
            background: rgba(253, 203, 110, 0.1);
            color: #8d6a00;
            border-left: 4px solid var(--warning);
        }
        
        /* Form styling */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        
        /* Role selector */
        .role-selector {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #666;
        }
        
        .role-option:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .role-option.active {
            border-color: var(--primary);
            background: var(--primary-soft);
            color: var(--primary);
        }
        
        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        /* Footer links */
        .register-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--gray-light);
        }
        
        .register-footer p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .link-login {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }
        
        .link-login:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: var(--gray-light);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .register-card {
                padding: 30px 25px;
            }
            
            .register-header h1 {
                font-size: 1.5rem;
            }
            
            .logo-circle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background shapes -->
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    <div class="bg-shape shape-3"></div>
    
    <!-- Main container -->
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="logo-circle">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h1>Create Account</h1>
                <p>Join our blogging community</p>
            </div>
            
            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert-custom alert-<?php echo $messageType; ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="post" id="registerForm">
                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="bi bi-person me-1"></i> Username
                    </label>
                    <div class="input-with-icon">
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control" 
                               placeholder="Enter username"
                               required 
                               minlength="3"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <span class="input-icon">
                            <i class="bi bi-at"></i>
                        </span>
                    </div>
                    <small class="text-muted">Minimum 3 characters</small>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock me-1"></i> Password
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               placeholder="Create password"
                               required 
                               minlength="8">
                        <span class="input-icon">
                            <i class="bi bi-key"></i>
                        </span>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="text-muted">Minimum 8 characters with letters and numbers</small>
                </div>
                
                <!-- Role Selection -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-person-badge me-1"></i> Account Type
                    </label>
                    <select name="role" class="form-control">
                        <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : ''; ?>>👤 User - Read posts & comment</option>
                        <option value="editor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'editor') ? 'selected' : ''; ?>>✏️ Editor - Create & edit posts</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>⚡ Admin - Full access</option>
                    </select>
                    <small class="text-muted">Choose your role in the platform</small>
                </div>
                
                <!-- Terms Checkbox -->
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" style="color: var(--primary);">Terms of Service</a> and <a href="#" style="color: var(--primary);">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="bi bi-person-plus"></i>
                    <span>Create Account</span>
                </button>
            </form>
            
            <!-- Footer -->
            <div class="register-footer">
                <p>Already have an account?</p>
                <a href="login.php" class="link-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Sign in to your account
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 15;
            
            // Character variety checks
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Update strength bar
            strengthBar.style.width = Math.min(strength, 100) + '%';
            
            // Update color based on strength
            if (strength < 40) {
                strengthBar.style.background = '#ff4757';
            } else if (strength < 70) {
                strengthBar.style.background = '#fdcb6e';
            } else {
                strengthBar.style.background = '#00b894';
            }
        });
        
        // Form submission animation
        const submitBtn = document.getElementById('submitBtn');
        const registerForm = document.getElementById('registerForm');
        
        registerForm.addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Creating Account...';
            submitBtn.disabled = true;
        });
        
        // Role selector interaction
        const roleOptions = document.querySelectorAll('.role-option');
        const roleSelect = document.querySelector('select[name="role"]');
        
        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Update visual selection
                roleOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                // Update hidden select
                const role = this.getAttribute('data-role');
                roleSelect.value = role;
            });
        });
        
        // Initialize active role
        roleSelect.addEventListener('change', function() {
            roleOptions.forEach(opt => {
                opt.classList.remove('active');
                if (opt.getAttribute('data-role') === this.value) {
                    opt.classList.add('active');
                }
            });
        });
        
        // CSS for spinner
        const style = document.createElement('style');
        style.textContent = `
            .spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>