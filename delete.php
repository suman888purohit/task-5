<?php
require 'db.php';

// ✅ Only allow admins to delete posts
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Only admin can delete posts.');
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

// ✅ Fetch post details for confirmation
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    // Post doesn't exist, redirect
    header('Location: index.php');
    exit;
}

// ✅ Check if deletion is confirmed
$confirmed = $_GET['confirm'] ?? false;

if ($confirmed === 'yes') {
    // Actually delete the post
    if (!empty($post['file_path']) && file_exists($post['file_path'])) {
        unlink($post['file_path']);
    }
    
    // Clear session data
    if (isset($_SESSION['likes'][$id])) {
        unset($_SESSION['likes'][$id]);
    }
    if (isset($_SESSION['comments'][$id])) {
        unset($_SESSION['comments'][$id]);
    }
    
    // Delete from database
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    $_SESSION['delete_success'] = "Post deleted successfully!";
    header("Location: index.php");
    exit;
}

// If not confirmed, show confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Post | Blog Platform</title>
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
        
        .delete-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .warning-container {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-light);
            border: 2px solid var(--danger);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .warning-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ffeaea, #ffcccc);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--danger);
            font-size: 3rem;
            border: 3px solid var(--danger);
        }
        
        .warning-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 15px;
        }
        
        .post-preview {
            background: var(--light-bg);
            border-radius: 16px;
            padding: 25px;
            margin: 30px 0;
            border: 1px solid var(--border-light);
            text-align: left;
        }
        
        .post-preview-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--red-light);
        }
        
        .buttons-container {
            display: flex;
            gap: 20px;
            margin-top: 40px;
        }
        
        .btn-delete-confirm {
            background: linear-gradient(135deg, var(--danger), #ff6b6b);
            color: white;
            border: none;
            padding: 18px 40px;
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
        
        .btn-delete-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 68, 68, 0.35);
            background: linear-gradient(135deg, #ff3333, var(--danger));
        }
        
        .btn-cancel {
            background: white;
            color: var(--text-dark);
            border: 2px solid var(--border-light);
            padding: 18px 40px;
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
            background: var(--light-bg);
            border-color: var(--primary-orange);
            color: var(--primary-orange);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.1);
        }
        
        @media (max-width: 768px) {
            .buttons-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="bg-pattern"></div>
    <div class="float-shape shape-1"></div>
    <div class="float-shape shape-2"></div>
    
    <!-- Main container -->
    <div class="delete-container">
        <!-- Warning container -->
        <div class="warning-container">
            <div class="warning-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            
            <h2 class="warning-title">Delete Post Confirmation</h2>
            <p style="color: var(--text-dark); margin-bottom: 25px; font-size: 1.1rem;">
                Are you sure you want to delete this post? This action cannot be undone.
            </p>
            
            <!-- Post preview -->
            <div class="post-preview">
                <div class="post-preview-title">
                    "<?php echo htmlspecialchars($post['title']); ?>"
                </div>
                
                <div style="color: var(--text-light); font-size: 0.95rem;">
                    <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                    <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 20px; margin-top: 15px; font-size: 0.9rem; color: var(--text-light);">
                    <div><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>
                    <?php if (!empty($post['file_path'])): ?>
                    <div><i class="bi bi-paperclip"></i> Has attachment</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action buttons -->
            <div class="buttons-container">
                <a href="delete.php?id=<?php echo $id; ?>&confirm=yes" 
                   class="btn-delete-confirm"
                   onclick="showDeleting()">
                    <i class="bi bi-trash3"></i>
                    Yes, Delete Permanently
                </a>
                <a href="index.php" class="btn-cancel">
                    <i class="bi bi-x-lg"></i>
                    No, Cancel
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function showDeleting() {
            const btn = document.querySelector('.btn-delete-confirm');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Deleting...';
            btn.disabled = true;
            
            // Add spinner animation
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
        }
    </script>
</body>
</html>