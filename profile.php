<?php
require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user ID from URL
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profile_id <= 0) {
    header('Location: index.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Fetch user profile data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit;
}

// Check if viewing own profile
$is_own_profile = ($current_user_id == $profile_id);

// Check if current user is following this profile
$is_following = false;
if (!$is_own_profile) {
    $checkFollowStmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $checkFollowStmt->execute([$current_user_id, $profile_id]);
    $is_following = $checkFollowStmt->fetch() ? true : false;
}

// Handle follow/unfollow action
$follow_action_success = false;
if (!$is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_action'])) {
    if ($_POST['follow_action'] === 'follow') {
        // Follow the user
        $followStmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        if ($followStmt->execute([$current_user_id, $profile_id])) {
            $is_following = true;
            $follow_action_success = 'followed';
        }
    } elseif ($_POST['follow_action'] === 'unfollow') {
        // Unfollow the user
        $unfollowStmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        if ($unfollowStmt->execute([$current_user_id, $profile_id])) {
            $is_following = false;
            $follow_action_success = 'unfollowed';
        }
    }
}

// Get followers count
$followersStmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followersStmt->execute([$profile_id]);
$followers_count = $followersStmt->fetchColumn();

// Get following count
$followingStmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$followingStmt->execute([$profile_id]);
$following_count = $followingStmt->fetchColumn();

// Fetch user's posts count
$postStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$postStmt->execute([$profile_id]);
$post_count = $postStmt->fetchColumn();

// Fetch user's recent posts
$recentStmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$recentStmt->execute([$profile_id]);
$recent_posts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
$update_success = false;
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio'])) {
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // Handle profile picture upload
    $profile_picture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['profile_picture']['type'], $allowed_types) && 
            $_FILES['profile_picture']['size'] <= $max_size) {
            
            // Create uploads/profiles directory if it doesn't exist
            if (!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $profile_id . '_' . time() . '.' . $extension;
            $upload_path = 'uploads/profiles/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if ($profile_picture && file_exists($profile_picture)) {
                    unlink($profile_picture);
                }
                $profile_picture = $upload_path;
            }
        }
    }
    
    // Update user profile
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET bio = ?, location = ?, website = ?, profile_picture = ? 
        WHERE id = ?
    ");
    
    if ($updateStmt->execute([$bio, $location, $website, $profile_picture, $profile_id])) {
        $update_success = true;
        // Refresh user data
        $stmt->execute([$profile_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Update session
        $_SESSION['profile_picture'] = $user['profile_picture'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
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
            --text-dark: #333333;
            --text-light: #666666;
            --border-light: #ffddcc;
            --shadow-light: 0 10px 40px rgba(255, 68, 68, 0.08);
            --shadow-hover: 0 25px 80px rgba(255, 68, 68, 0.15);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fffaf8 0%, #fff0f0 50%, #fff8f0 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
        }
        
        .profile-info {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(255, 68, 68, 0.15);
            background: linear-gradient(135deg, var(--red-light), var(--orange-light));
        }
        
        .profile-details {
            flex: 1;
        }
        
        .profile-username {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .profile-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
        }
        
        .profile-bio {
            font-size: 1.1rem;
            line-height: 1.6;
            color: var(--text-dark);
            margin: 20px 0;
        }
        
        .profile-stats {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        
        .stat-item {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-red);
            display: block;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.2);
            color: white;
        }
        
        .btn-back {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: var(--primary-red);
            color: white;
        }
        
        .recent-posts {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
        }
        
        .post-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .post-item:hover {
            background: var(--red-light);
            border-radius: 10px;
        }
        
        .post-item:last-child {
            border-bottom: none;
        }
        
        /* Follow button styles */
        .btn-follow {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .btn-follow:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.25);
        }
        
        .btn-unfollow {
            background: white !important;
            color: var(--primary-red) !important;
            border: 2px solid var(--primary-red) !important;
        }
        
        .btn-unfollow:hover {
            background: var(--red-light) !important;
        }
        
        /* Modal for followers/following lists */
        .list-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }
        
        .list-item:hover {
            background-color: var(--red-light);
            border-radius: 10px;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.1);
        }
        
        .follow-success {
            animation: fadeInOut 3s ease;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-meta {
                justify-content: center;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .btn-follow {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <?php if ($update_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="max-width: 500px; margin: 0 auto 30px;">
                <i class="bi bi-check-circle-fill"></i> Profile updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($follow_action_success): ?>
            <div class="alert alert-success alert-dismissible fade show follow-success" role="alert" style="max-width: 500px; margin: 0 auto 30px;">
                <i class="bi bi-check-circle-fill"></i> Successfully <?php echo $follow_action_success; ?> <?php echo htmlspecialchars($user['username']); ?>!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <a href="index.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                
                <?php if ($is_own_profile): ?>
                    <button type="button" class="btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="bi bi-pencil"></i> Edit Profile
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <div>
                    <img src="<?php 
                        if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                            echo htmlspecialchars($user['profile_picture']);
                        } else {
                            echo 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=ff4444&color=fff&size=150';
                        }
                    ?>" alt="Profile Picture" class="profile-picture">
                </div>
                
                <div class="profile-details">
                    <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
                    
                    <div class="profile-meta">
                        <?php if (!empty($user['location'])): ?>
                            <div class="meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?php echo htmlspecialchars($user['location']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                            <div class="meta-item">
                                <i class="bi bi-link-45deg"></i>
                                <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" style="color: var(--primary-red); text-decoration: none;">
                                    Website
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>Joined <?php echo date('F Y', strtotime($user['joined_at'])); ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="bi bi-person-badge"></i>
                            <span><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <div class="profile-bio">
                            <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="profile-bio" style="color: var(--text-light); font-style: italic;">
                            <?php echo $is_own_profile ? 'No bio yet. Click "Edit Profile" to add one!' : 'No bio available.'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $post_count; ?></span>
                    <span class="stat-label">Posts</span>
                </div>
                
                <div class="stat-item" data-bs-toggle="modal" data-bs-target="#followersModal">
                    <span class="stat-number"><?php echo $followers_count; ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                
                <div class="stat-item" data-bs-toggle="modal" data-bs-target="#followingModal">
                    <span class="stat-number"><?php echo $following_count; ?></span>
                    <span class="stat-label">Following</span>
                </div>
            </div>
            
            <?php if (!$is_own_profile): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="follow_action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                    <button type="submit" class="btn-follow <?php echo $is_following ? 'btn-unfollow' : ''; ?>">
                        <i class="bi <?php echo $is_following ? 'bi-person-dash' : 'bi-person-plus'; ?>"></i>
                        <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($recent_posts)): ?>
            <div class="recent-posts">
                <h3 style="margin-bottom: 20px; color: var(--text-dark);">
                    <i class="bi bi-file-text" style="color: var(--primary-red);"></i>
                    Recent Posts (<?php echo $post_count; ?>)
                </h3>
                
                <?php foreach ($recent_posts as $post): ?>
                    <div class="post-item">
                        <a href="index.php?search=<?php echo urlencode($post['title']); ?>" 
                           style="text-decoration: none; color: var(--text-dark);">
                            <h5 style="margin-bottom: 5px;"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <small style="color: var(--text-light);">
                                <i class="bi bi-clock"></i>
                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </small>
                        </a>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($post_count > 6): ?>
                    <div class="text-center mt-3">
                        <a href="index.php?user=<?php echo $profile_id; ?>" class="btn-edit">
                            <i class="bi bi-eye"></i> View All Posts
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Followers Modal -->
    <div class="modal fade" id="followersModal" tabindex="-1" aria-labelledby="followersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(90deg, var(--primary-red), var(--primary-orange)); color: white;">
                    <h5 class="modal-title" id="followersModalLabel">
                        <i class="bi bi-people"></i> Followers (<?php echo $followers_count; ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto; padding: 0;">
                    <?php 
                    // Fetch followers list
                    $followersListStmt = $pdo->prepare("
                        SELECT u.id, u.username, u.profile_picture 
                        FROM follows f 
                        JOIN users u ON f.follower_id = u.id 
                        WHERE f.following_id = ? 
                        ORDER BY f.created_at DESC
                    ");
                    $followersListStmt->execute([$profile_id]);
                    $followers_list = $followersListStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($followers_list)): ?>
                        <div class="text-center py-5" style="color: var(--text-light);">
                            <i class="bi bi-people display-4 mb-3"></i>
                            <p>No followers yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($followers_list as $follower): ?>
                            <div class="list-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?php 
                                        echo !empty($follower['profile_picture']) && file_exists($follower['profile_picture']) 
                                            ? htmlspecialchars($follower['profile_picture']) 
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($follower['username']) . '&background=ff4444&color=fff&size=50';
                                    ?>" alt="Profile" class="list-profile-pic me-3">
                                    <div>
                                        <a href="profile.php?id=<?php echo $follower['id']; ?>" 
                                           style="text-decoration: none; color: var(--text-dark); font-weight: 500;">
                                            <?php echo htmlspecialchars($follower['username']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Following Modal -->
    <div class="modal fade" id="followingModal" tabindex="-1" aria-labelledby="followingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(90deg, var(--primary-red), var(--primary-orange)); color: white;">
                    <h5 class="modal-title" id="followingModalLabel">
                        <i class="bi bi-person-check"></i> Following (<?php echo $following_count; ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto; padding: 0;">
                    <?php 
                    // Fetch following list
                    $followingListStmt = $pdo->prepare("
                        SELECT u.id, u.username, u.profile_picture 
                        FROM follows f 
                        JOIN users u ON f.following_id = u.id 
                        WHERE f.follower_id = ? 
                        ORDER BY f.created_at DESC
                    ");
                    $followingListStmt->execute([$profile_id]);
                    $following_list = $followingListStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($following_list)): ?>
                        <div class="text-center py-5" style="color: var(--text-light);">
                            <i class="bi bi-person-check display-4 mb-3"></i>
                            <p>Not following anyone yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($following_list as $following): ?>
                            <div class="list-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?php 
                                        echo !empty($following['profile_picture']) && file_exists($following['profile_picture']) 
                                            ? htmlspecialchars($following['profile_picture']) 
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($following['username']) . '&background=ff4444&color=fff&size=50';
                                    ?>" alt="Profile" class="list-profile-pic me-3">
                                    <div>
                                        <a href="profile.php?id=<?php echo $following['id']; ?>" 
                                           style="text-decoration: none; color: var(--text-dark); font-weight: 500;">
                                            <?php echo htmlspecialchars($following['username']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <?php if ($is_own_profile): ?>
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(90deg, var(--primary-red), var(--primary-orange)); color: white;">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body" style="padding: 30px;">
                        <div class="form-group text-center mb-4">
                            <label class="form-label" style="font-weight: 600; color: var(--text-dark);">Profile Picture</label>
                            <div class="mb-3">
                                <img src="<?php 
                                    if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                                        echo htmlspecialchars($user['profile_picture']);
                                    } else {
                                        echo 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=ff4444&color=fff&size=150';
                                    }
                                ?>" alt="Current Picture" class="profile-picture" style="width: 120px; height: 120px;">
                            </div>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            <small class="text-muted">Max 2MB. JPG, PNG, GIF, or WebP.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--text-dark);">Bio</label>
                            <textarea name="bio" class="form-control" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 600; color: var(--text-dark);">Location</label>
                                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="Your city or country">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 600; color: var(--text-dark);">Website</label>
                                    <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" placeholder="https://example.com">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 20px 30px; background: var(--light-bg);">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-edit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview profile picture before upload
        document.querySelector('input[name="profile_picture"]')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.modal .profile-picture').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Auto-dismiss success messages after 3 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);
        
        // Prevent form submission on Enter key in modals
        document.querySelectorAll('.modal form').forEach(function(form) {
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>