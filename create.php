<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();  // SESSION START ADDED

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $filePath = null;

    if ($title === '' || $content === '') {
        $message = 'Title and content are required.';
    } else {
        // Handle file upload if provided
        if (!empty($_FILES['upload']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . "_" . basename($_FILES['upload']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['upload']['tmp_name'], $targetFile)) {
                $filePath = $targetFile;
            }
        }

        // Save post with optional file path AND USER ID
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, file_path, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $filePath, $_SESSION['user_id']]); // ADDED USER ID
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post | Blog Platform</title>
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
        .create-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .create-header {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 35px 40px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }
        
        .create-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .welcome-section h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .welcome-section p {
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 20px rgba(255, 68, 68, 0.2);
        }
        
        .role-badge {
            background: var(--orange-light);
            color: var(--primary-orange);
            padding: 6px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            border: 1px solid var(--border-light);
        }
        
        .btn-logout {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
        
        /* Form container */
        .form-container {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            margin-bottom: 40px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .form-header p {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        /* Form elements */
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control, .form-select, .form-textarea {
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-dark);
        }
        
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 4px rgba(255, 136, 68, 0.1);
        }
        
        .form-textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-textarea::-webkit-scrollbar {
            width: 8px;
        }
        
        .form-textarea::-webkit-scrollbar-track {
            background: var(--red-light);
            border-radius: 4px;
        }
        
        .form-textarea::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary-red), var(--primary-orange));
            border-radius: 4px;
        }
        
        /* File upload */
        .file-upload-container {
            border: 2px dashed var(--border-light);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            background: var(--light-bg);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary-orange);
            background: var(--orange-light);
        }
        
        .file-upload-container i {
            font-size: 2.5rem;
            color: var(--primary-red);
            margin-bottom: 15px;
        }
        
        .file-upload-container p {
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .file-info {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 10px;
        }
        
        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.25);
            cursor: pointer;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 68, 68, 0.35);
        }
        
        .btn-back {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
        
        /* Alert message */
        .alert-message {
            background: #ffeaea;
            border: 2px solid var(--danger);
            border-radius: 12px;
            padding: 16px 20px;
            color: var(--danger);
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }
        
        .alert-message i {
            font-size: 1.2rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Character counter */
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .char-counter.warning {
            color: var(--warning);
        }
        
        .char-counter.danger {
            color: var(--danger);
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .create-header {
                padding: 25px;
            }
            
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px 15px;
            }
            
            .create-header {
                padding: 20px;
            }
            
            .welcome-section h1 {
                font-size: 1.8rem;
            }
            
            .form-container {
                padding: 20px;
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
    <div class="create-container">
        <!-- Header -->
        <div class="create-header">
            <div class="header-top">
                <div class="welcome-section">
                    <h1>Blog Dashboard</h1>
                    <p>Create a new post</p>
                </div>
                
                <div class="user-info">
                    <div class="user-badge">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div class="role-badge">
                        <i class="bi bi-shield-check"></i>
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Form container -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-plus-circle"></i> Create New Post</h2>
                <p>Share your thoughts and ideas with the community</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert-message">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- enctype added for file upload -->
            <form method="post" enctype="multipart/form-data" id="postForm">
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-card-heading"></i>
                        Title
                    </label>
                    <input type="text" 
                           name="title" 
                           class="form-control" 
                           placeholder="Enter post title..." 
                           required
                           maxlength="200"
                           id="titleInput">
                    <div class="char-counter" id="titleCounter">0/200</div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-chat-text"></i>
                        Content
                    </label>
                    <textarea name="content" 
                              class="form-control form-textarea" 
                              placeholder="Write your post content here..." 
                              required
                              maxlength="5000"
                              id="contentInput"></textarea>
                    <div class="char-counter" id="contentCounter">0/5000</div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-paperclip"></i>
                        Upload File (Optional)
                    </label>
                    <div class="file-upload-container" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p>Click to upload or drag and drop</p>
                        <small class="file-info">Max file size: 10MB</small>
                        <input type="file" 
                               name="upload" 
                               class="form-control d-none" 
                               id="fileInput"
                               onchange="showFileName(this)">
                        <div id="fileName" class="mt-2"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-save"></i>
                    Publish Post
                </button>
                
                <a href="index.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </form>
        </div>
    </div>
    
    <script>
        // Character counters
        const titleInput = document.getElementById('titleInput');
        const contentInput = document.getElementById('contentInput');
        const titleCounter = document.getElementById('titleCounter');
        const contentCounter = document.getElementById('contentCounter');
        
        titleInput.addEventListener('input', function() {
            const length = this.value.length;
            titleCounter.textContent = `${length}/200`;
            updateCounterColor(titleCounter, length, 200);
        });
        
        contentInput.addEventListener('input', function() {
            const length = this.value.length;
            contentCounter.textContent = `${length}/5000`;
            updateCounterColor(contentCounter, length, 5000);
        });
        
        function updateCounterColor(counter, length, max) {
            counter.classList.remove('warning', 'danger');
            if (length > max * 0.9) {
                counter.classList.add('danger');
            } else if (length > max * 0.75) {
                counter.classList.add('warning');
            }
        }
        
        // File upload display
        function showFileName(input) {
            const fileNameDiv = document.getElementById('fileName');
            if (input.files.length > 0) {
                const fileName = input.files[0].name;
                const fileSize = (input.files[0].size / (1024 * 1024)).toFixed(2); // MB
                
                fileNameDiv.innerHTML = `
                    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>${fileName}</strong> (${fileSize} MB)
                        </div>
                    </div>
                `;
                
                // Validate file size (10MB limit)
                if (input.files[0].size > 10 * 1024 * 1024) {
                    fileNameDiv.innerHTML = `
                        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <div>
                                File is too large! Maximum size is 10MB.
                            </div>
                        </div>
                    `;
                    input.value = '';
                }
            } else {
                fileNameDiv.innerHTML = '';
            }
        }
        
        // Drag and drop for file upload
        const fileUploadContainer = document.querySelector('.file-upload-container');
        const fileInput = document.getElementById('fileInput');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileUploadContainer.style.borderColor = 'var(--primary-orange)';
            fileUploadContainer.style.background = 'var(--orange-light)';
        }
        
        function unhighlight() {
            fileUploadContainer.style.borderColor = '';
            fileUploadContainer.style.background = '';
        }
        
        fileUploadContainer.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            showFileName(fileInput);
        }
        
        // Initialize counters on page load
        updateCounterColor(titleCounter, titleInput.value.length, 200);
        updateCounterColor(contentCounter, contentInput.value.length, 5000);
    </script>
</body>
</html>