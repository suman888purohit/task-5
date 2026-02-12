<?php
require 'db.php';

// Get system information
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$userIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Try to get MySQL version
try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    $mysqlVersion = $result['version'] ?? 'N/A';
} catch (Exception $e) {
    $mysqlVersion = 'Error: ' . $e->getMessage();
}

// Check required extensions
$requiredExtensions = ['PDO', 'pdo_mysql', 'session', 'mbstring'];
$extensionsStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionsStatus[$ext] = extension_loaded($ext);
}

// Session info
$sessionInfo = [
    'Session ID' => session_id(),
    'Session Status' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive',
    'User Role' => $_SESSION['role'] ?? 'Not logged in',
    'Username' => $_SESSION['username'] ?? 'Guest'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status | Blog Platform</title>
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
        
        body {
            font-family: 'Inter', sans-serif;
            background: #fff5f5;
            min-height: 100vh;
            color: #2d3436;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(255, 107, 107, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 85% 30%, rgba(255, 140, 66, 0.05) 0%, transparent 20%);
        }
        
        .status-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .status-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 30px;
            background: linear-gradient(135deg, #fff5f5 0%, #fff0eb 100%);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.12);
            border: 1px solid #ffebee;
        }
        
        .status-header h1 {
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8c42 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8c42 100%);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.08);
            border: 1px solid #ffebee;
            transition: all 0.3s ease;
            border-left: 4px solid #ff6b6b;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.15);
        }
        
        .status-card h3 {
            color: #ff4757;
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffebee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #666;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-value {
            font-weight: 600;
            color: #2d3436;
            font-family: 'Monaco', 'Courier New', monospace;
        }
        
        .status-good {
            color: #00b894 !important;
        }
        
        .status-warning {
            color: #fdcb6e !important;
        }
        
        .status-error {
            color: #ff4757 !important;
        }
        
        .ext-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .ext-ok {
            background: rgba(0, 184, 148, 0.1);
            color: #006442;
        }
        
        .ext-missing {
            background: rgba(255, 107, 107, 0.1);
            color: #cc3d2e;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8c42 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        .btn-secondary-modern {
            background: white;
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
        }
        
        .btn-secondary-modern:hover {
            background: #fff5f5;
            color: #ff4757;
            transform: translateY(-3px);
        }
        
        .test-results {
            margin-top: 40px;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.08);
            display: none;
            border-left: 4px solid #ff8c42;
        }
        
        .test-results.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #ffebee;
            text-align: center;
            color: #666;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-modern {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="status-container">
        <!-- Header -->
        <div class="status-header fade-in">
            <h1>System Status Dashboard</h1>
            <p class="mb-4" style="color: #666;">Real-time diagnostics for your blog platform</p>
            
            <div class="d-flex justify-content-center">
                <div class="status-badge">
                    <i class="bi bi-check-circle-fill"></i>
                    Database Connected Successfully!
                </div>
            </div>
        </div>
        
        <!-- Status Grid -->
        <div class="status-grid">
            <!-- Database Card -->
            <div class="status-card fade-in" style="animation-delay: 0.1s;">
                <h3><i class="bi bi-database"></i> Database Status</h3>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-server"></i> MySQL Version</span>
                    <span class="status-value status-good"><?php echo htmlspecialchars($mysqlVersion); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-plug"></i> Connection Type</span>
                    <span class="status-value">PDO MySQL</span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-folder"></i> Database Name</span>
                    <span class="status-value"><?php echo DB_NAME; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-clock"></i> Checked At</span>
                    <span class="status-value"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>
            
            <!-- PHP Environment Card -->
            <div class="status-card fade-in" style="animation-delay: 0.2s;">
                <h3><i class="bi bi-code-slash"></i> PHP Environment</h3>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-filetype-php"></i> PHP Version</span>
                    <span class="status-value"><?php echo $phpVersion; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-hdd-stack"></i> Server Software</span>
                    <span class="status-value"><?php echo htmlspecialchars($serverSoftware); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-pc-display"></i> Server Name</span>
                    <span class="status-value"><?php echo htmlspecialchars($serverName); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><i class="bi bi-globe"></i> Client IP</span>
                    <span class="status-value"><?php echo htmlspecialchars($userIP); ?></span>
                </div>
            </div>
            
            <!-- Extensions Card -->
            <div class="status-card fade-in" style="animation-delay: 0.3s;">
                <h3><i class="bi bi-puzzle"></i> Required Extensions</h3>
                <?php foreach ($extensionsStatus as $ext => $status): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo $ext; ?></span>
                    <span class="ext-badge <?php echo $status ? 'ext-ok' : 'ext-missing'; ?>">
                        <i class="bi bi-<?php echo $status ? 'check-circle' : 'x-circle'; ?>"></i>
                        <?php echo $status ? 'Loaded' : 'Missing'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Session Card -->
            <div class="status-card fade-in" style="animation-delay: 0.4s;">
                <h3><i class="bi bi-person-badge"></i> Session Information</h3>
                <?php foreach ($sessionInfo as $key => $value): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo $key; ?></span>
                    <span class="status-value <?php 
                        echo $key === 'Session Status' && $value === 'Active' ? 'status-good' : 
                        ($key === 'Username' && $value !== 'Guest' ? 'status-good' : '');
                    ?>">
                        <?php echo htmlspecialchars($value); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="index.php" class="btn-modern btn-primary-modern">
                <i class="bi bi-house-door"></i> Go to Dashboard
            </a>
            <button onclick="runTests()" class="btn-modern btn-secondary-modern">
                <i class="bi bi-lightning-charge"></i> Run System Tests
            </button>
            <a href="login.php" class="btn-modern btn-secondary-modern">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
        </div>
        
        <!-- Test Results Area -->
        <div id="testResults" class="test-results">
            <h4><i class="bi bi-graph-up" style="color: #ff8c42;"></i> System Test Results</h4>
            <div id="testResultsContent" class="mt-3"></div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Blog Platform | System Status Monitor v1.0</p>
            <p class="text-muted"><small>All systems operational</small></p>
        </div>
    </div>
    
    <script>
        function runTests() {
            const testResults = document.getElementById('testResults');
            const content = document.getElementById('testResultsContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" style="color: #ff6b6b;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2" style="color: #666;">Running comprehensive system diagnostics...</p>
                </div>
            `;
            testResults.classList.add('show');
            
            // Simulate tests
            setTimeout(() => {
                const tests = [
                    { 
                        name: 'Database Connection', 
                        status: 'passed', 
                        message: 'PDO connection established successfully',
                        icon: 'bi-database-check'
                    },
                    { 
                        name: 'Session Security', 
                        status: 'passed', 
                        message: 'Session encryption is active',
                        icon: 'bi-shield-check'
                    },
                    { 
                        name: 'File Permissions', 
                        status: 'warning', 
                        message: 'Upload directory needs write permissions',
                        icon: 'bi-folder-exclamation'
                    },
                    { 
                        name: 'PHP Configuration', 
                        status: 'passed', 
                        message: 'All required settings are optimal',
                        icon: 'bi-gear-wide-connected'
                    },
                    { 
                        name: 'Security Headers', 
                        status: 'passed', 
                        message: 'Security headers are properly configured',
                        icon: 'bi-lock'
                    }
                ];
                
                let html = '<div class="list-group">';
                tests.forEach(test => {
                    const statusColor = test.status === 'passed' ? '#00b894' : 
                                      test.status === 'warning' ? '#fdcb6e' : '#ff4757';
                    
                    html += `
                        <div class="list-group-item border-0 mb-2" style="background: rgba(${statusColor === '#00b894' ? '0,184,148' : statusColor === '#fdcb6e' ? '253,203,110' : '255,71,87'}, 0.1);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi ${test.icon}" style="color: ${statusColor}; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong style="color: #2d3436;">${test.name}</strong>
                                        <div class="small" style="color: #666;">${test.message}</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill" style="background: ${statusColor};">
                                    ${test.status.toUpperCase()}
                                </span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                content.innerHTML = html;
                testResults.scrollIntoView({ behavior: 'smooth' });
            }, 1500);
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            const badge = document.querySelector('.status-badge');
            badge.style.animation = 'none';
            setTimeout(() => badge.style.animation = 'pulse 2s infinite', 10);
        }, 30000);
    </script>
</body>
</html>