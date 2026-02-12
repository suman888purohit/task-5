<?php
require 'db.php';
session_start();

// Remove debug lines - they're not needed anymore
// TEMPORARY DEBUG - REMOVE LATER
// echo "<!-- DEBUG: User ID = " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->";
// echo "<!-- DEBUG: Username = " . ($_SESSION['username'] ?? 'NOT SET') . " -->";

// Protect page: only logged-in users can see
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Handle Like action (UPDATED TO DATABASE) ---
if (isset($_POST['like_post'])) {
    $postId = (int)$_POST['like_post'];
    $userId = $_SESSION['user_id'];
    
    // Check if already liked
    $checkStmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $checkStmt->execute([$postId, $userId]);
    
    if (!$checkStmt->fetch()) {
        // Add like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $userId]);
    } else {
        // Remove like (toggle)
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
    }
    
    header("Location: index.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// --- Handle Comment action (UPDATED TO DATABASE) ---
if (isset($_POST['comment_post'])) {
    $postId = (int)$_POST['comment_post'];
    $userId = $_SESSION['user_id'];
    $comment = trim($_POST['comment_text'] ?? '');
    
    if ($comment !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $userId, $comment]);
    }
    
    header("Location: index.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// Handle search
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE p.title LIKE ? OR p.content LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Pagination setup
$limit = 6; // show 6 posts per page
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total posts
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p $where");
$countStmt->execute($params);
$totalPosts = $countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $limit);

// Fetch posts with search + pagination
$sql = "
    SELECT p.*, u.username, u.profile_picture 
    FROM posts p 
    LEFT JOIN users u ON p.user_id = u.id 
    $where 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
";

// Prepare and execute with parameters
$stmt = $pdo->prepare($sql);
if (empty($params)) {
    $stmt->execute([$limit, $offset]);
} else {
    $stmt->execute(array_merge($params, [$limit, $offset]));
}
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total stats from database
$totalCommentsStmt = $pdo->query("SELECT COUNT(*) FROM comments");
$totalComments = $totalCommentsStmt->fetchColumn();

$totalLikesStmt = $pdo->query("SELECT COUNT(*) FROM likes");
$totalLikes = $totalLikesStmt->fetchColumn();

// Get unread message count for navigation
$unreadStmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count 
    FROM messages 
    WHERE receiver_id = ? AND is_read = FALSE
");
$unreadStmt->execute([$_SESSION['user_id']]);
$unread_count = $unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Blog Platform</title>
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
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .dashboard-header {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 35px 40px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
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
        
        /* NEW: My Profile Button */
        .btn-profile {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.2);
        }
        
        .btn-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.3);
            color: white;
        }
        
        /* NEW: Messages Button in Nav */
        .btn-messages {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .btn-messages:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
        
        /* Unread badge for messages */
        .unread-badge-nav {
            background: var(--primary-red);
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -5px;
            right: -5px;
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
        
        /* Stats section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-orange);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--red-light), var(--orange-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary-red);
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        /* Actions section */
        .actions-section {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .btn-create {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            padding: 16px 32px;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.25);
        }
        
        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 68, 68, 0.35);
            color: white;
        }
        
        /* Search box */
        .search-box {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 50px 16px 24px;
            background: white;
            border: 2px solid var(--border-light);
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-dark);
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.02);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 
                0 0 0 4px rgba(255, 136, 68, 0.1),
                inset 0 2px 6px rgba(0, 0, 0, 0.02);
        }
        
        .search-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        /* Posts grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .post-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            transition: all 0.4s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .post-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-orange);
        }
        
        .post-header {
            padding: 25px 25px 20px;
            border-bottom: 1px solid var(--border-light);
            position: relative;
            flex-shrink: 0;
        }
        
        .post-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* NEW: Post meta with author */
        .post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .post-time {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* NEW: Author link style */
        .post-author {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .post-author a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
        }
        
        .post-author a:hover {
            text-decoration: underline;
        }
        
        .post-content {
            padding: 25px;
            color: var(--text-dark);
            line-height: 1.6;
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .post-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, var(--card-bg), transparent);
        }
        
        .post-actions {
            padding: 20px 25px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            flex-shrink: 0;
        }
        
        /* Action buttons */
        .btn-action {
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        .btn-edit {
            background: var(--red-light);
            color: var(--primary-red);
        }
        
        .btn-edit:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #ffeaea;
            color: var(--danger);
        }
        
        .btn-delete:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-like {
            background: #ffeded;
            color: var(--primary-red);
            border: 1px solid #ffcccc;
        }
        
        .btn-like:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            border-color: var(--primary-red);
        }
        
        .likes-count {
            font-weight: 600;
            color: var(--primary-red);
            margin-left: 8px;
        }
        
        /* Comment section */
        .comment-section {
            padding: 0 25px 25px;
            flex-shrink: 0;
        }
        
        .comment-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: nowrap;
            align-items: center;
        }
        
        .comment-input {
            flex: 1;
            min-width: 0;
            padding: 12px 16px;
            background: white;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 136, 68, 0.1);
        }
        
        .btn-comment {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .btn-comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 68, 68, 0.2);
        }
        
        .comments-list {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .comment-item {
            background: white;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 10px;
            border: 1px solid var(--border-light);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .comment-user {
            font-weight: 600;
            color: var(--primary-red);
            font-size: 0.9rem;
        }
        
        .comment-user a {
            color: var(--primary-red);
            text-decoration: none;
        }
        
        .comment-user a:hover {
            text-decoration: underline;
        }
        
        .comment-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .comment-text {
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* No posts message */
        .no-posts {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 40px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 2px dashed var(--border-light);
        }
        
        .no-posts-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--red-light), var(--orange-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--primary-red);
            font-size: 2.5rem;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 60px;
        }
        
        .page-btn {
            padding: 12px 24px;
            background: white;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            color: var(--text-dark);
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .page-btn:hover {
            border-color: var(--primary-orange);
            color: var(--primary-orange);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 68, 68, 0.1);
        }
        
        .page-btn.active {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(255, 68, 68, 0.2);
        }
        
        .page-info {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        /* Footer */
        .footer {
            margin-top: 80px;
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            font-size: 0.9rem;
            border-top: 1px solid var(--border-light);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .footer-link {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-link:hover {
            color: var(--primary-red);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .posts-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
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
                width: 100%;
            }
            
            .btn-profile, .btn-messages, .role-badge, .btn-logout {
                width: 100%;
                justify-content: center;
            }
            
            .actions-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .comment-form {
                flex-wrap: wrap;
            }
            
            .comment-input {
                min-width: 100%;
            }
            
            .btn-comment {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px 15px;
            }
            
            .dashboard-header {
                padding: 20px;
            }
            
            .welcome-section h1 {
                font-size: 1.8rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .post-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
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
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="welcome-section">
                    <h1>Blog Dashboard</h1>
                    <p>Manage and interact with your posts</p>
                </div>
                
                <div class="user-info">
                    <!-- NEW: My Profile button -->
                    <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn-profile">
                        <i class="bi bi-person"></i>
                        My Profile
                    </a>
                    
                    <div class="role-badge">
                        <i class="bi bi-shield-check"></i>
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                    
                    <!-- NEW: Messages button -->
                    <a href="messages.php" class="btn-messages">
                        <i class="bi bi-chat-dots"></i>
                        Messages
                        <?php if ($unread_count > 0): ?>
                            <span class="unread-badge-nav"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="logout.php" class="btn-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalPosts; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalComments; ?></div>
                    <div class="stat-label">Total Comments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-heart"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalLikes; ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalPages; ?></div>
                    <div class="stat-label">Total Pages</div>
                </div>
            </div>
        </div>
        
        <!-- Actions section -->
        <div class="actions-section">
            <a href="create.php" class="btn-create">
                <i class="bi bi-plus-circle"></i>
                Create New Post
            </a>
            
            <!-- Search form -->
            <form method="get" class="search-box">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="Search posts by title or content..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        
        <!-- Posts grid -->
        <div class="posts-grid">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    // Get like count for this post
                    $likeStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                    $likeStmt->execute([$post['id']]);
                    $likeCount = $likeStmt->fetchColumn();
                    
                    // Check if current user liked this post
                    $userLikedStmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
                    $userLikedStmt->execute([$post['id'], $_SESSION['user_id']]);
                    $userLiked = $userLikedStmt->fetch();
                    
                    // Fetch comments for this post
                    $commentStmt = $pdo->prepare("
                        SELECT c.*, u.username 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = ? 
                        ORDER BY c.created_at ASC
                    ");
                    $commentStmt->execute([$post['id']]);
                    $comments = $commentStmt->fetchAll();
                    ?>
                    
                    <div class="post-card">
                        <div class="post-header">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <div class="post-meta">
                                <span class="post-time">
                                    <i class="bi bi-clock"></i>
                                    <?php echo date('M d, Y - H:i', strtotime($post['created_at'])); ?>
                                </span>
                                
                                <?php if (!empty($post['user_id'])): ?>
                                <span class="post-author">
                                    <i class="bi bi-person"></i>
                                    <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                                        <?php echo htmlspecialchars($post['username'] ?? 'Unknown'); ?>
                                    </a>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                        
                        <?php if (!empty($post['file_path'])): ?>
                            <div style="padding: 0 25px 15px; flex-shrink: 0;">
                                <a href="<?php echo htmlspecialchars($post['file_path']); ?>" 
                                   target="_blank" 
                                   class="btn-action" 
                                   style="background: #e8f4ff; color: #0066cc; width: 100%; justify-content: center;">
                                    <i class="bi bi-paperclip"></i>
                                    View Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-actions">
                            <?php if ($_SESSION['role'] === 'editor' || $_SESSION['role'] === 'admin'): ?>
                                <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i>
                                    Edit
                                </a>
                                <a href="delete.php?id=<?php echo $post['id']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this post?');">
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </a>
                            <?php endif; ?>
                            
                            <form method="post" class="d-inline">
                                <input type="hidden" name="like_post" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn-action btn-like">
                                    <i class="bi bi-heart<?php echo $userLiked ? '-fill' : ''; ?>"></i>
                                    Like
                                    <span class="likes-count"><?php echo $likeCount; ?></span>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Comment section -->
                        <div class="comment-section">
                            <form method="post" class="comment-form">
                                <input type="hidden" name="comment_post" value="<?php echo $post['id']; ?>">
                                <input type="text" 
                                       name="comment_text" 
                                       class="comment-input" 
                                       placeholder="Write a comment..."
                                       required>
                                <button type="submit" class="btn-comment">
                                    <i class="bi bi-send"></i>
                                    Comment
                                </button>
                            </form>
                            
                            <?php if (!empty($comments)): ?>
                                <div class="comments-list">
                                    <?php foreach ($comments as $c): ?>
                                        <div class="comment-item">
                                            <div class="comment-header">
                                                <span class="comment-user">
                                                    <i class="bi bi-person-circle"></i>
                                                    <a href="profile.php?id=<?php echo $c['user_id']; ?>">
                                                        <?php echo htmlspecialchars($c['username']); ?>
                                                    </a>
                                                </span>
                                                <span class="comment-time">
                                                    <?php echo date('H:i', strtotime($c['created_at'])); ?>
                                                </span>
                                            </div>
                                            <p class="comment-text"><?php echo htmlspecialchars($c['comment']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-posts">
                    <div class="no-posts-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 style="margin-bottom: 15px; color: var(--text-dark);">No Posts Found</h3>
                    <p style="color: var(--text-light); margin-bottom: 25px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        <?php echo $search ? 'No posts match your search. Try different keywords.' : 'No posts available. Create the first post!'; ?>
                    </p>
                    <a href="create.php" class="btn-create" style="display: inline-flex;">
                        <i class="bi bi-plus-circle"></i>
                        Create Your First Post
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>
                <?php endif; ?>
                
                <span class="page-info">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Blog Platform. All rights reserved.</p>
            <div class="footer-links">
                <a href="#" class="footer-link">About Us</a>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
                <a href="#" class="footer-link">Contact</a>
                <a href="#" class="footer-link">Support</a>
            </div>
        </footer>
    </div>
    
    <script>
        // Like button animation
        document.querySelectorAll('.btn-like').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const likesCount = this.querySelector('.likes-count');
                
                // Pulse animation
                this.style.transform = 'scale(0.95)';
                icon.style.transform = 'scale(1.3)';
                
                setTimeout(() => {
                    this.style.transform = '';
                    icon.style.transform = '';
                }, 300);
            });
        });
    </script>
</body>
</html>