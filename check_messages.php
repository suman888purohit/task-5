<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if ($conversation_id > 0) {
    // Check for new messages
    $stmt = $pdo->prepare("
        SELECT m.id, m.message, m.created_at, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? AND m.id > ? AND m.receiver_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id, $last_message_id, $current_user_id]);
    $new_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'new_messages' => $new_messages,
        'count' => count($new_messages)
    ]);
} else {
    echo json_encode(['error' => 'No conversation']);
}
?>