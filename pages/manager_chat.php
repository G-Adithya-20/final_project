<?php
session_start();
require '../includes/db_connect.php';

// Ensure only managers can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Manager') {
    echo "<script>window.location.href = 'login.php';</script>";
}

// Initialize variables
$discussion_id = null;
$chats = [];
$error_message = '';

// Get the discussion_id from the URL
if (isset($_GET['discussion_id'])) {
    $discussion_id = filter_var($_GET['discussion_id'], FILTER_VALIDATE_INT);
    
    if ($discussion_id === false || $discussion_id === null) {
        echo "<script>window.location.href = 'error.php';</script>";
    }

    // Fetch all chat messages for the discussion_id along with the username
    try {
        $query = "SELECT chat_id, username, chat_msg, time 
                 FROM chat 
                 WHERE discussion_id = ? 
                 ORDER BY time ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $discussion_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Error loading chat messages.";
        error_log("Database error: " . $e->getMessage());
    }
}

// Handle new chat message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_msg']) && $discussion_id) {
    $chat_msg = trim($_POST['chat_msg']);
    $username = $_SESSION['username'];

    if (!empty($chat_msg)) {
        try {
            $query = "INSERT INTO chat (discussion_id, chat_msg, username, time) 
                     VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $discussion_id, $chat_msg, $username);
            
            if ($stmt->execute()) {
                echo "<script>window.location.href = 'manager_chat.php?discussion_id=" . $discussion_id . "';</script>";
                exit;
            } else {
                $error_message = "Failed to send message.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error sending message.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Discussion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #4338ca;
            --secondary-color: #6366f1;
            --background-color: #f5f7fa;
            --chat-bg-sent: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            --chat-bg-received: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            overflow: hidden;
        }

        .chat-container {
            max-width: 1100px;
            margin: 2rem auto;
            background-color: #e8f4ff; /* Changed background color to light blue */
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
            height: calc(100vh - 4rem);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease-out;
            position: absolute; /* Added for centering */
            left: 50%; /* Added for centering */
            transform: translateX(-50%); /* Added for centering */
            width: 90%; /* Added for responsiveness */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background-color: var(--primary-color);
            color: white;
            border-bottom: 1px solid var(--border-color);
        }

        .chat-header h1 {
            font-size: 1.35rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            scroll-behavior: smooth;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 15px;
            max-height: 500px;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background-color: rgba(100, 116, 139, 0.3);
            border-radius: 6px;
        }

        .message {
            max-width: 70%;
            padding: 1rem 1.2rem;
            border-radius: 18px;
            position: relative;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            animation: messageAppear 0.3s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        @keyframes messageAppear {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .message.sent {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); /* Manager messages */
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            align-self: flex-end;
        }

        .message.received {
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%); /* Other users' messages */
            color: white;
            margin-right: auto;
            border-bottom-left-radius: 5px;
            align-self: flex-start;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .message.sent .message-header {
            color: rgba(255, 255, 255, 0.9);
        }

        .message.received .message-header {
            color: var(--text-secondary);
        }

        .message-content {
            line-height: 1.5;
            word-wrap: break-word;
        }

        .chat-input {
            padding: 1.5rem 2rem;
            background-color: white;
            border-top: 1px solid var(--border-color);
            position: relative;
        }

        .chat-form {
            display: flex;
            gap: 1rem;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-form textarea {
            flex: 1;
            padding: 1rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            resize: none;
            height: 100px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .chat-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .send-button {
            padding: 0 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Poppins', sans-serif;
        }

        .send-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .send-button:active {
            transform: translateY(0);
        }

        .send-button i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .send-button:hover i {
            transform: translateX(3px);
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem 1.5rem;
            margin: 1rem 2rem;
            border-radius: 12px;
            text-align: center;
            font-size: 0.95rem;
            animation: shake 0.5s ease-in-out;
            border-left: 4px solid #ef4444;
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-7px); }
            80% { transform: translateX(7px); }
            100% { transform: translateX(0); }
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            padding-left: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .typing-indicator.active {
            opacity: 1;
        }

        .typing-dots {
            display: flex;
            gap: 3px;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--primary-color);
            animation: pulse 1.5s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            text-align: center;
            padding: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-light);
        }

        @media (max-width: 768px) {
            .chat-container {
                width: 100%;
                margin: 0;
                height: 100vh;
                border-radius: 0;
                position: static; /* Reset position on mobile */
                transform: none; /* Reset transform on mobile */
            }

            .message {
                max-width: 85%;
            }
            
            .chat-header {
                padding: 1.2rem 1.5rem;
            }
            
            .chat-messages {
                padding: 1.2rem 1.5rem;
            }
            
            .chat-input {
                padding: 1.2rem 1.5rem;
            }
            
            .chat-form textarea {
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>Manager Discussion</h1>
            <a href="manager_discussion.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Discussions</span>
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="chat-messages" id="chat-messages">
            <?php if (empty($chats)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No messages yet</h3>
                    <p>Start the conversation by sending a message below.</p>
                </div>
            <?php else: ?>
                <?php foreach ($chats as $chat): ?>
                    <div class="message <?= ($chat['username'] == $_SESSION['username']) ? 'sent' : 'received' ?>">
                        <div class="message-header">
                            <strong><?= htmlspecialchars($chat['username']) ?></strong>
                            <span><?= date('g:i A', strtotime($chat['time'])) ?></span>
                        </div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($chat['chat_msg'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input">
            <form class="chat-form" method="POST" action="manager_chat.php?discussion_id=<?= urlencode($discussion_id) ?>" id="chat-form">
                <textarea name="chat_msg" placeholder="Type your message..." required id="message-input"></textarea>
                <button type="submit" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send</span>
                </button>
            </form>
            <div class="typing-indicator" id="typing-indicator">
                <span>Someone is typing</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to bottom of chat on load
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Message input and typing indicator
            const messageInput = document.getElementById('message-input');
            const typingIndicator = document.getElementById('typing-indicator');
            let typingTimer;
            
            // Simulate random typing indicators (for demo purposes)
            function randomTypingIndicator() {
                if (Math.random() > 0.7 && !typingIndicator.classList.contains('active')) {
                    typingIndicator.classList.add('active');
                    setTimeout(() => {
                        typingIndicator.classList.remove('active');
                    }, Math.random() * 3000 + 1000);
                }
                
                setTimeout(randomTypingIndicator, Math.random() * 10000 + 5000);
            }
            
            // Only run this in a real environment where multiple users are active
            // Uncomment for demo: randomTypingIndicator();
            
            // Message input animations
            messageInput.addEventListener('focus', function() {
                this.style.borderColor = 'var(--primary-color)';
            });
            
            messageInput.addEventListener('blur', function() {
                if (this.value.length === 0) {
                    this.style.borderColor = 'var(--border-color)';
                }
            });
            
            // Message animations on scroll
            const messages = document.querySelectorAll('.message');
            
            // Function to check if an element is in viewport
            function isInViewport(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            // Simple animation for messages when they come into view
            function animateMessagesOnScroll() {
                messages.forEach(message => {
                    if (isInViewport(message) && !message.classList.contains('animated')) {
                        message.classList.add('animated');
                        message.style.opacity = '0';
                        setTimeout(() => {
                            message.style.opacity = '1';
                            message.style.transform = 'translateY(0)';
                        }, 100);
                    }
                });
            }
            
            // Initial check for messages in viewport
            setTimeout(animateMessagesOnScroll, 300);
            
            // Check for messages in viewport on scroll
            chatMessages.addEventListener('scroll', animateMessagesOnScroll);
            
            // Form submission animation
            const chatForm = document.getElementById('chat-form');
            
            chatForm.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('.send-button');
                submitButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>Sending...</span>';
                
                // We're letting the form submit normally as per the original code
                // This is just a visual enhancement
            });
            
            // Add subtle hover effect to messages
            messages.forEach(message => {
                message.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                });
                
                message.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>