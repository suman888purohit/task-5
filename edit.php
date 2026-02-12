<?php
require 'db.php';

// ✅ Check role before allowing edit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Only admin can edit posts.');
            window.location.href = 'index.php';
          </script>";
    exit;
}

// ✅ Get post ID from query string
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// ✅ Fetch post data
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    die("Post not found.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $message = "Title and content are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);

        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post | Blog Platform</title>
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
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .edit-header {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 35px 40px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }
        
        .edit-header::before {
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
        
        .form-control, .form-textarea {
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-dark);
        }
        
        .form-control:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 4px rgba(255, 136, 68, 0.1);
        }
        
        .form-textarea {
            min-height: 250px;
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
        
        /* Buttons container */
        .buttons-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-update {
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
            flex: 1;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.25);
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 68, 68, 0.35);
            color: white;
        }
        
        .btn-cancel {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex: 1;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
        
        /* Post info */
        .post-info {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--border-light);
        }
        
        .post-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .post-info-item i {
            color: var(--primary-red);
            width: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .edit-header {
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
            
            .buttons-container {
                flex-direction: column;
            }
            
            .btn-update, .btn-cancel {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px 15px;
            }
            
            .edit-header {
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
    <div class="edit-container">
        <!-- Header -->
        <div class="edit-header">
            <div class="header-top">
                <div class="welcome-section">
                    <h1>Blog Dashboard</h1>
                    <p>Edit existing post</p>
                </div>
                
                <div class="user-info">
                    <div class="user-badge">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div class="role-badge">
                        <i class="bi bi-shield-check"></i>
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?>
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
                <h2><i class="bi bi-pencil-square"></i> Edit Post</h2>
                <p>Update your post content and save changes</p>
            </div>
            
            <!-- Post information -->
            <div class="post-info">
                <div class="post-info-item">
                    <i class="bi bi-hash"></i>
                    <span>Post ID: <strong><?php echo htmlspecialchars($post['id']); ?></strong></span>
                </div>
                <div class="post-info-item">
                    <i class="bi bi-calendar"></i>
                    <span>Created: <?php echo date('M d, Y - H:i', strtotime($post['created_at'])); ?></span>
                </div>
                <?php if (!empty($post['file_path'])): ?>
                <div class="post-info-item">
                    <i class="bi bi-paperclip"></i>
                    <span>Attachment: <a href="<?php echo htmlspecialchars($post['file_path']); ?>" target="_blank">View File</a></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($message): ?>
                <div class="alert-message">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="editForm">
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-card-heading"></i>
                        Title
                    </label>
                    <input type="text" 
                           name="title" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($post['title']); ?>" 
                           required
                           maxlength="200"
                           id="titleInput">
                    <div class="char-counter" id="titleCounter"><?php echo strlen($post['title']); ?>/200</div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-chat-text"></i>
                        Content
                    </label>
                    <textarea name="content" 
                              class="form-control form-textarea" 
                              required
                              maxlength="5000"
                              id="contentInput"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    <div class="char-counter" id="contentCounter"><?php echo strlen($post['content']); ?>/5000</div>
                </div>
                
                <div class="buttons-container">
                    <button type="submit" class="btn-update">
                        <i class="bi bi-check-circle"></i>
                        Update Post
                    </button>
                    <a href="index.php" class="btn-cancel">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Character counters
        const titleInput = document.getElementById('titleInput');
        const contentInput = document.getElementById('contentInput');
        const titleCounter = document.getElementById('titleCounter');
        const contentCounter = document.getElementById('contentCounter');
        
        // Initialize counters with current values
        updateCounterColor(titleCounter, titleInput.value.length, 200);
        updateCounterColor(contentCounter, contentInput.value.length, 5000);
        
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
        
        // Form submission confirmation if changes were made
        const editForm = document.getElementById('editForm');
        const originalTitle = titleInput.value;
        const originalContent = contentInput.value;
        
        editForm.addEventListener('submit', function(e) {
            const currentTitle = titleInput.value;
            const currentContent = contentInput.value;
            
            if (currentTitle === originalTitle && currentContent === originalContent) {
                if (!confirm('No changes were made. Are you sure you want to continue?')) {
                    e.preventDefault();
                }
            } else {
                // Show loading state
                const submitBtn = this.querySelector('.btn-update');
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Updating...';
                submitBtn.disabled = true;
                
                // Add spinner CSS if not exists
                if (!document.querySelector('#updateSpinner')) {
                    const style = document.createElement('style');
                    style.id = 'updateSpinner';
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
                }
            }
        });
        
        // Auto-save draft (optional feature)
        let autoSaveTimer;
        
        function autoSaveDraft() {
            const title = titleInput.value;
            const content = contentInput.value;
            
            // Save to localStorage
            localStorage.setItem('edit_draft_title', title);
            localStorage.setItem('edit_draft_content', content);
            localStorage.setItem('edit_draft_time', new Date().toLocaleTimeString());
            
            // Show notification
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed bottom-0 end-0 m-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <i class="bi bi-save"></i> Draft saved at ${new Date().toLocaleTimeString()}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Auto-save every 30 seconds if changes were made
        titleInput.addEventListener('input', () => {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(autoSaveDraft, 30000);
        });
        
        contentInput.addEventListener('input', () => {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(autoSaveDraft, 30000);
        });
        
        // Check for saved draft on page load
        window.addEventListener('load', function() {
            const savedTitle = localStorage.getItem('edit_draft_title');
            const savedContent = localStorage.getItem('edit_draft_content');
            const savedTime = localStorage.getItem('edit_draft_time');
            
            if (savedTitle && savedContent && 
                (savedTitle !== originalTitle || savedContent !== originalContent)) {
                if (confirm(`A draft was saved at ${savedTime}. Do you want to restore it?`)) {
                    titleInput.value = savedTitle;
                    contentInput.value = savedContent;
                    
                    // Update counters
                    titleCounter.textContent = `${savedTitle.length}/200`;
                    contentCounter.textContent = `${savedContent.length}/5000`;
                    updateCounterColor(titleCounter, savedTitle.length, 200);
                    updateCounterColor(contentCounter, savedContent.length, 5000);
                }
                
                // Clear saved draft
                localStorage.removeItem('edit_draft_title');
                localStorage.removeItem('edit_draft_content');
                localStorage.removeItem('edit_draft_time');
            }
        });
        
        // Clear draft on successful form submission
        editForm.addEventListener('submit', function() {
            localStorage.removeItem('edit_draft_title');
            localStorage.removeItem('edit_draft_content');
            localStorage.removeItem('edit_draft_time');
        });
    </script>
</body>
</html>