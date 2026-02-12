<?php
// navigation.php
?>
<nav class="main-nav">
    <div class="nav-left">
        <a href="index.php" class="nav-item">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="nav-item">
                <i class="bi bi-plus-circle"></i>
                <span>Create Post</span>
            </a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Admin Panel</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="nav-user">
                <i class="bi bi-person-circle"></i>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                <?php if (isset($_SESSION['role'])): ?>
                    <span class="role-badge">
                        <?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?>
                    </span>
                <?php endif; ?>
            </span>
            <a href="logout.php" class="nav-item btn-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-item">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login</span>
            </a>
            <a href="register.php" class="nav-item">
                <i class="bi bi-person-plus"></i>
                <span>Register</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<style>
    /* Navigation Styles */
    .main-nav {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 20px 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-light);
        border: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .nav-left, .nav-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-dark);
        text-decoration: none;
        font-weight: 500;
        padding: 10px 16px;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: white;
        border: 2px solid transparent;
    }
    
    .nav-item:hover {
        background: var(--light-bg);
        border-color: var(--border-light);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 68, 68, 0.1);
    }
    
    .nav-user {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-dark);
        font-weight: 500;
        padding: 8px 16px;
        background: var(--light-bg);
        border-radius: 10px;
    }
    
    .role-badge {
        background: var(--red-light);
        color: var(--primary-red);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .btn-logout {
        background: white;
        color: var(--primary-red);
        border: 2px solid var(--primary-red) !important;
    }
    
    .btn-logout:hover {
        background: var(--primary-red) !important;
        color: white !important;
    }
    
    @media (max-width: 768px) {
        .main-nav {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        
        .nav-left, .nav-right {
            flex-direction: column;
            width: 100%;
        }
        
        .nav-item {
            width: 100%;
            justify-content: center;
        }
        
        .nav-user {
            width: 100%;
            justify-content: center;
            margin-bottom: 10px;
        }
    }
</style>