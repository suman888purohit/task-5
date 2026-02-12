<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Get the conversation ID or user ID from URL
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$to_user_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = (int)$_POST['receiver_id'];
    $message_text = trim($_POST['message']);
    
    if ($receiver_id > 0 && !empty($message_text)) {
        // Find or create conversation
        $convStmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
        ");
        $convStmt->execute([$current_user_id, $receiver_id, $receiver_id, $current_user_id]);
        $conversation = $convStmt->fetch();
        
        if ($conversation) {
            $conversation_id = $conversation['id'];
        } else {
            // Create new conversation
            $createConvStmt = $pdo->prepare("
                INSERT INTO conversations (user1_id, user2_id) 
                VALUES (?, ?)
            ");
            $createConvStmt->execute([$current_user_id, $receiver_id]);
            $conversation_id = $pdo->lastInsertId();
        }
        
        // Insert message
        $msgStmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, receiver_id, message) 
            VALUES (?, ?, ?, ?)
        ");
        $msgStmt->execute([$conversation_id, $current_user_id, $receiver_id, $message_text]);
        
        // Update conversation last message time
        $updateConvStmt = $pdo->prepare("
            UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $updateConvStmt->execute([$conversation_id]);
        
        // Redirect to the conversation
        header("Location: messages.php?conversation_id=" . $conversation_id);
        exit;
    }
}

// Get user's conversations
$conversationsStmt = $pdo->prepare("
    SELECT c.*, 
           CASE 
               WHEN c.user1_id = ? THEN u2.id 
               ELSE u1.id 
           END as other_user_id,
           CASE 
               WHEN c.user1_id = ? THEN u2.username 
               ELSE u1.username 
           END as other_username,
           CASE 
               WHEN c.user1_id = ? THEN u2.profile_picture 
               ELSE u1.profile_picture 
           END as other_profile_picture,
           (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = FALSE) as unread_count
    FROM conversations c
    LEFT JOIN users u1 ON c.user1_id = u1.id
    LEFT JOIN users u2 ON c.user2_id = u2.id
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_at DESC
");
$conversationsStmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
$conversations = $conversationsStmt->fetchAll(PDO::FETCH_ASSOC);

// If a conversation is selected, get its messages
$messages = [];
$other_user = null;
$current_conversation = null;

if ($conversation_id > 0) {
    // Get conversation details
    $convDetailStmt = $pdo->prepare("
        SELECT c.*, 
               CASE 
                   WHEN c.user1_id = ? THEN u2.id 
                   ELSE u1.id 
               END as other_user_id,
               CASE 
                   WHEN c.user1_id = ? THEN u2.username 
                   ELSE u1.username 
               END as other_username,
               CASE 
                   WHEN c.user1_id = ? THEN u2.profile_picture 
                   ELSE u1.profile_picture 
               END as other_profile_picture
        FROM conversations c
        LEFT JOIN users u1 ON c.user1_id = u1.id
        LEFT JOIN users u2 ON c.user2_id = u2.id
        WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)
    ");
    $convDetailStmt->execute([$current_user_id, $current_user_id, $current_user_id, $conversation_id, $current_user_id, $current_user_id]);
    $current_conversation = $convDetailStmt->fetch();
    
    if ($current_conversation) {
        // Get messages for this conversation
        $messagesStmt = $pdo->prepare("
            SELECT m.*, u.username as sender_username, u.profile_picture as sender_profile_picture
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $messagesStmt->execute([$conversation_id]);
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $markReadStmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
            WHERE conversation_id = ? AND receiver_id = ? AND is_read = FALSE
        ");
        $markReadStmt->execute([$conversation_id, $current_user_id]);
    }
} elseif ($to_user_id > 0 && $to_user_id != $current_user_id) {
    // Check if user exists
    $userStmt = $pdo->prepare("SELECT id, username, profile_picture FROM users WHERE id = ?");
    $userStmt->execute([$to_user_id]);
    $other_user = $userStmt->fetch();
    
    // Check if conversation already exists
    if ($other_user) {
        $checkConvStmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
        ");
        $checkConvStmt->execute([$current_user_id, $to_user_id, $to_user_id, $current_user_id]);
        $existingConv = $checkConvStmt->fetch();
        
        if ($existingConv) {
            header("Location: messages.php?conversation_id=" . $existingConv['id']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
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
            --message-sent: #ffeded;
            --message-received: #ffffff;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fffaf8 0%, #fff0f0 50%, #fff8f0 100%);
            min-height: 100vh;
        }
        
        .messaging-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 40px);
        }
        
        .messaging-wrapper {
            display: flex;
            height: 100%;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
        }
        
        /* Conversations List */
        .conversations-sidebar {
            width: 350px;
            border-right: 1px solid var(--border-light);
            background: var(--light-bg);
            display: flex;
            flex-direction: column;
        }
        
        .conversations-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-light);
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            color: white;
        }
        
        .conversations-header h2 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
        }
        
        .conversation-item:hover {
            background: var(--red-light);
        }
        
        .conversation-item.active {
            background: var(--red-light);
            border-left: 4px solid var(--primary-red);
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.1);
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-last-message {
            font-size: 0.9rem;
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }
        
        .conversation-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .unread-badge {
            background: var(--primary-red);
            color: white;
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
        
        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-light);
            background: var(--card-bg);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.1);
        }
        
        .chat-user-info h3 {
            margin: 0;
            color: var(--text-dark);
        }
        
        .chat-user-info small {
            color: var(--text-light);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--light-bg);
        }
        
        .message-wrapper {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message-wrapper.sent {
            align-items: flex-end;
        }
        
        .message-wrapper.received {
            align-items: flex-start;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-wrapper.sent .message-bubble {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-wrapper.received .message-bubble {
            background: var(--message-received);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .message-status {
            font-size: 0.75rem;
            margin-top: 2px;
        }
        
        .message-status.read {
            color: var(--primary-red);
        }
        
        .message-status.sent {
            color: var(--text-light);
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid var(--border-light);
            background: var(--card-bg);
        }
        
        .message-form {
            display: flex;
            gap: 10px;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border-light);
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }
        
        .message-input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(255, 68, 68, 0.1);
        }
        
        .send-button {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 68, 68, 0.2);
        }
        
        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            color: var(--text-light);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--border-light);
            margin-bottom: 20px;
        }
        
        .new-conversation-btn {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .new-conversation-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 68, 68, 0.2);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .conversations-sidebar {
                width: 100%;
                display: none;
            }
            
            .conversations-sidebar.active {
                display: flex;
            }
            
            .chat-area {
                display: none;
            }
            
            .chat-area.active {
                display: flex;
            }
            
            .back-to-conversations {
                display: flex;
            }
        }
        
        .back-to-conversations {
            display: none;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
        }
        
        .mobile-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: inline-block;
            }
        }
    </style>
</head>
<body>
    <div class="messaging-container">
        <div class="messaging-wrapper">
            <!-- Conversations Sidebar -->
            <div class="conversations-sidebar <?php echo !$current_conversation && !$other_user ? 'active' : ''; ?>">
                <div class="conversations-header">
                    <h2>
                        <i class="bi bi-chat-dots"></i>
                        Messages
                    </h2>
                </div>
                
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="bi bi-chat-dots empty-state-icon"></i>
                            <h4>No messages yet</h4>
                            <p>Start a conversation with someone!</p>
                            <a href="users.php" class="new-conversation-btn">
                                <i class="bi bi-plus-circle"></i> Find Users
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="messages.php?conversation_id=<?php echo $conv['id']; ?>" 
                               class="conversation-item <?php echo ($current_conversation && $current_conversation['id'] == $conv['id']) ? 'active' : ''; ?>">
                                <img src="<?php 
                                    echo !empty($conv['other_profile_picture']) && file_exists($conv['other_profile_picture']) 
                                        ? htmlspecialchars($conv['other_profile_picture']) 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($conv['other_username']) . '&background=ff4444&color=fff&size=50';
                                ?>" alt="<?php echo htmlspecialchars($conv['other_username']); ?>" class="conversation-avatar">
                                
                                <div class="conversation-info">
                                    <div class="conversation-name">
                                        <?php echo htmlspecialchars($conv['other_username']); ?>
                                    </div>
                                    <div class="conversation-last-message">
                                        <?php 
                                            if ($conv['last_message']) {
                                                echo htmlspecialchars(mb_strlen($conv['last_message']) > 40 
                                                    ? mb_substr($conv['last_message'], 0, 40) . '...' 
                                                    : $conv['last_message']);
                                            } else {
                                                echo 'Start a conversation...';
                                            }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="conversation-meta">
                                    <div class="conversation-time">
                                        <?php 
                                            if ($conv['last_message_at']) {
                                                $time = strtotime($conv['last_message_at']);
                                                if (date('Y-m-d') == date('Y-m-d', $time)) {
                                                    echo date('H:i', $time);
                                                } elseif (date('Y') == date('Y', $time)) {
                                                    echo date('M d', $time);
                                                } else {
                                                    echo date('Y-m-d', $time);
                                                }
                                            }
                                        ?>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge">
                                            <?php echo $conv['unread_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area <?php echo ($current_conversation || $other_user) ? 'active' : ''; ?>">
                <?php if ($current_conversation || $other_user): ?>
                    <div class="chat-header">
                        <a href="messages.php" class="back-to-conversations mobile-toggle">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        
                        <?php if ($current_conversation): ?>
                            <img src="<?php 
                                echo !empty($current_conversation['other_profile_picture']) && file_exists($current_conversation['other_profile_picture']) 
                                    ? htmlspecialchars($current_conversation['other_profile_picture']) 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($current_conversation['other_username']) . '&background=ff4444&color=fff&size=50';
                            ?>" alt="<?php echo htmlspecialchars($current_conversation['other_username']); ?>" class="chat-user-avatar">
                            
                            <div class="chat-user-info">
                                <h3><?php echo htmlspecialchars($current_conversation['other_username']); ?></h3>
                                <small>
                                    <?php 
                                        // Show online status (you can implement actual online status later)
                                        echo 'Active recently';
                                    ?>
                                </small>
                            </div>
                            
                            <div class="ms-auto">
                                <a href="profile.php?id=<?php echo $current_conversation['other_user_id']; ?>" 
                                   class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </div>
                            
                        <?php elseif ($other_user): ?>
                            <img src="<?php 
                                echo !empty($other_user['profile_picture']) && file_exists($other_user['profile_picture']) 
                                    ? htmlspecialchars($other_user['profile_picture']) 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($other_user['username']) . '&background=ff4444&color=fff&size=50';
                            ?>" alt="<?php echo htmlspecialchars($other_user['username']); ?>" class="chat-user-avatar">
                            
                            <div class="chat-user-info">
                                <h3><?php echo htmlspecialchars($other_user['username']); ?></h3>
                                <small>Start a new conversation</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message-wrapper <?php echo $message['sender_id'] == $current_user_id ? 'sent' : 'received'; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                        <?php if ($message['sender_id'] == $current_user_id): ?>
                                            <span class="message-status <?php echo $message['is_read'] ? 'read' : 'sent'; ?>">
                                                <i class="bi bi-check2<?php echo $message['is_read'] ? '-all' : ''; ?>"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($other_user): ?>
                            <div class="empty-state">
                                <i class="bi bi-chat-dots empty-state-icon"></i>
                                <h4>Start a conversation with <?php echo htmlspecialchars($other_user['username']); ?></h4>
                                <p>Send your first message below!</p>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-chat-dots empty-state-icon"></i>
                                <h4>No messages yet</h4>
                                <p>Be the first to say hello!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input-area">
                        <form method="post" class="message-form" id="messageForm">
                            <input type="hidden" name="receiver_id" 
                                   value="<?php echo $current_conversation ? $current_conversation['other_user_id'] : ($other_user ? $other_user['id'] : ''); ?>">
                            
                            <input type="text" 
                                   name="message" 
                                   class="message-input" 
                                   placeholder="Type your message..." 
                                   required
                                   autocomplete="off">
                            
                            <button type="submit" class="send-button" id="sendButton">
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-left-dots empty-state-icon"></i>
                        <h4>Select a conversation</h4>
                        <p>Choose a conversation from the list or start a new one</p>
                        <a href="users.php" class="new-conversation-btn">
                            <i class="bi bi-plus-circle"></i> New Message
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // Scroll on load
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        
        // Handle message form submission
        document.getElementById('messageForm')?.addEventListener('submit', function(e) {
            const messageInput = this.querySelector('.message-input');
            const sendButton = this.querySelector('.send-button');
            
            if (messageInput.value.trim() === '') {
                e.preventDefault();
                return;
            }
            
            // Disable button and show loading
            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="bi bi-clock"></i>';
            
            // Message will send, page will reload
        });
        
        // Auto-focus message input
        document.querySelector('.message-input')?.focus();
        
        // Mobile toggle
        document.querySelectorAll('.back-to-conversations, .conversation-item').forEach(el => {
            el.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.conversations-sidebar').classList.toggle('active');
                    document.querySelector('.chat-area').classList.toggle('active');
                }
            });
        });
        
        // Real-time updates (simple polling for now)
        <?php if ($current_conversation): ?>
        function checkNewMessages() {
            fetch(`check_messages.php?conversation_id=<?php echo $conversation_id; ?>&last_message_id=<?php 
                echo !empty($messages) ? end($messages)['id'] : 0; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages.length > 0) {
                        // Reload page to show new messages
                        location.reload();
                    }
                });
        }
        
        // Check every 5 seconds
        setInterval(checkNewMessages, 5000);
        <?php endif; ?>
        
        // Enter to send (Shift+Enter for new line)
        document.querySelector('.message-input')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('messageForm')?.submit();
            }
        });
    </script>
</body>
</html>