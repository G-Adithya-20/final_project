<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

// Ensure only team members can access this page
if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

// Get the discussion_id from the URL
if (!isset($_GET['discussion_id'])) {
    echo "<script>window.location.href = 'discussion.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --light: #f9fafb;
            --dark: #111827;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --text-light: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Elegant Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1.25rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.2);
        }

        .header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(15deg);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-title i {
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.15);
            height: 3rem;
            width: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .back-link i {
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
        }

        .chat-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 10rem);
            border: 1px solid var(--gray-200);
        }

        /* Messages Section */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.03) 0, transparent 50%),
                radial-gradient(at 0% 0%, rgba(139, 92, 246, 0.03) 0, transparent 50%);
        }

        /* Custom Scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(107, 114, 128, 0.2);
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.3);
        }

        /* Message Styling */
        .message {
            display: flex;
            max-width: 85%;
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .message.sent {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .message.received {
            margin-right: auto;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.new {
            animation: newMessage 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes newMessage {
            0% { opacity: 0; transform: translateY(20px) scale(0.9); }
            70% { transform: translateY(-5px) scale(1.02); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .avatar {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 0 0.75rem;
        }

        .message-content {
            border-radius: 18px;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .message.sent .message-content {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received .message-content {
            background: white;
            color: var(--text-primary);
            border: 1px solid var(--gray-200);
            border-bottom-left-radius: 4px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.825rem;
        }

        .message.sent .message-header {
            color: rgba(255, 255, 255, 0.9);
        }

        .message.received .message-header {
            color: var(--text-secondary);
        }

        .message-text {
            line-height: 1.5;
            font-size: 0.95rem;
        }

        /* Input Section */
        .chat-input {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid var(--gray-200);
        }

        .chat-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .chat-form textarea {
            flex: 1;
            padding: 1rem 1.25rem;
            border: 1px solid var(--gray-300);
            border-radius: 16px;
            background: var(--gray-100);
            resize: none;
            height: 60px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02) inset;
        }

        .chat-form textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .chat-form textarea::placeholder {
            color: var(--text-light);
        }

        .send-button {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 14px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }

        .send-button i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .send-button:hover i {
            transform: translateX(3px);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header-container, .main-container {
                padding: 0 1rem;
            }

            .chat-container {
                height: calc(100vh - 8rem);
                border-radius: 16px;
            }

            .header-title h1 {
                font-size: 1.25rem;
            }

            .back-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .message {
                max-width: 90%;
            }

            .chat-form textarea {
                padding: 0.875rem;
            }

            .send-button {
                padding: 0.75rem 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .header-title i {
                height: 2.5rem;
                width: 2.5rem;
                font-size: 1.25rem;
            }

            .header-title h1 {
                font-size: 1.125rem;
            }

            .message {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-title">
                <i class="fas fa-comments"></i>
                <h1>Team Discussion</h1>
            </div>
            <a href="discussion.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Discussions</span>
            </a>
        </div>
    </header>

    <div class="main-container">
        <div class="chat-container">
            <div class="chat-messages" id="chat-messages">
                <?php
                $discussion_id = $_GET['discussion_id'];
                $query = "SELECT chat_id, username, chat_msg, time FROM chat WHERE discussion_id = ? ORDER BY time ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $discussion_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $chats = [];  // Initialize as empty array
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $chats[] = $row;
                    }
                }
                $stmt->close();
                ?>
                <?php if (!empty($chats)): ?>
                    <?php foreach ($chats as $chat): 
                        $initial = strtoupper(substr($chat['username'], 0, 1));
                    ?>
                        <div class="message <?= ($chat['username'] == $_SESSION['username']) ? 'sent' : 'received' ?>" 
                             data-message-id="<?= $chat['chat_id'] ?>">
                            <div class="avatar"><?= $initial ?></div>
                            <div class="message-content">
                                <div class="message-header">
                                    <strong><?= htmlspecialchars($chat['username']) ?></strong>
                                    <span><?= date('g:i A', strtotime($chat['time'])) ?></span>
                                </div>
                                <div class="message-text">
                                    <?= nl2br(htmlspecialchars($chat['chat_msg'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-comments mb-3 d-block" style="font-size: 2rem;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chat-input">
                <form class="chat-form" id="chat-form" method="POST">
                    <textarea name="chat_msg" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="send-button">
                        Send
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const currentUsername = '<?= $_SESSION['username'] ?>';
            const discussionId = '<?= $discussion_id ?>';
            let lastMessageId = getLastMessageId();

            // Scroll to bottom on load
            scrollToBottom();

            // Handle form submission
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                
                fetch(`chat_page.php?discussion_id=${discussionId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addMessageToChat(data.message, true);
                        chatForm.reset();
                        scrollToBottom();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to traditional form submission
                    chatForm.submit();
                });
            });

            // Fetch new messages periodically
            setInterval(fetchNewMessages, 1000);

            function fetchNewMessages() {
                fetch(`chat_page.php?discussion_id=${discussionId}&action=fetch_new_messages&last_id=${lastMessageId}`)
                    .then(response => response.json())
                    .then(messages => {
                        messages.forEach(message => {
                            if (message.username !== currentUsername) {
                                addMessageToChat(message);
                            }
                        });
                        if (messages.length > 0) {
                            scrollToBottom();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function addMessageToChat(message, isNew = false) {
                const initial = message.username.charAt(0).toUpperCase();
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.username === currentUsername ? 'sent' : 'received'}`;
                if (isNew) messageDiv.classList.add('new');
                messageDiv.dataset.messageId = message.chat_id;

                messageDiv.innerHTML = `
                    <div class="avatar">${initial}</div>
                    <div class="message-content">
                        <div class="message-header">
                            <strong>${escapeHtml(message.username)}</strong>
                            <span>${message.time}</span>
                        </div>
                        <div class="message-text">
                            ${escapeHtml(message.chat_msg).replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;

                chatMessages.appendChild(messageDiv);
                lastMessageId = Math.max(lastMessageId, message.chat_id);
            }

            function getLastMessageId() {
                const messages = document.querySelectorAll('.message');
                if (messages.length === 0) return 0;
                const lastMessage = messages[messages.length - 1];
                return parseInt(lastMessage.dataset.messageId) || 0;
            }

            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>