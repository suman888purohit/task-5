<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all users (excluding current user)
$query = "SELECT id, username, profile_picture, bio, location 
          FROM users 
          WHERE id != ?";
$params = [$current_user_id];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR bio LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY username ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-red: #ff4444;
            --primary-orange: #ff8844;
            --red-light: #ffeeee;
            --orange-light: #fff4ee;
            --light-bg: #fffaf8;
            --card-bg: rgba(255, 255, 255, 0.97);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fffaf8 0%, #fff0f0 50%, #fff8f0 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .users-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .users-header {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(255, 68, 68, 0.08);
            border: 1px solid #ffddcc;
        }
        
        .user-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ffddcc;
            transition: all 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(255, 68, 68, 0.15);
            border-color: var(--primary-red);
        }
        
        .btn-message {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 68, 68, 0.2);
            color: white;
        }
        
        .search-box {
            background: var(--light-bg);
            border: 2px solid #ffddcc;
            border-radius: 15px;
            padding: 15px 20px;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(255, 68, 68, 0.1);
        }
        
        .search-btn {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <div class="users-container">
        <div class="users-header">
            <h1 class="mb-4" style="color: var(--text-dark);">
                <i class="bi bi-people" style="color: var(--primary-red);"></i>
                Find Users
            </h1>
            
            <form method="get" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-10">
                        <input type="text" 
                               name="search" 
                               class="form-control search-box" 
                               placeholder="Search by username or bio..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn search-btn w-100" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0 text-muted">
                    <?php echo count($users); ?> users found
                </p>
                <a href="messages.php" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-left"></i> Back to Messages
                </a>
            </div>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-4 text-muted mb-3"></i>
                <h4 class="text-muted">No users found</h4>
                <p class="text-muted">Try a different search term</p>
                <a href="users.php" class="btn-message">
                    <i class="bi bi-arrow-clockwise"></i> View All Users
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($users as $user): ?>
                    <div class="col-md-4">
                        <div class="user-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php 
                                    echo !empty($user['profile_picture']) && file_exists($user['profile_picture']) 
                                        ? htmlspecialchars($user['profile_picture']) 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=ff4444&color=fff&size=50';
                                ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                                    <?php if (!empty($user['location'])): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($user['location']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($user['bio'])): ?>
                                <p class="text-muted small mb-3">
                                    <?php echo mb_strlen($user['bio']) > 100 
                                        ? mb_substr($user['bio'], 0, 100) . '...' 
                                        : $user['bio']; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <a href="profile.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                                <a href="messages.php?to=<?php echo $user['id']; ?>" 
                                   class="btn-message btn-sm">
                                    <i class="bi bi-chat-dots"></i> Message
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>