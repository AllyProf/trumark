@extends('layouts.vali')

@section('title', 'WhatsApp Live Chat')
@section('page_icon', 'fa-whatsapp')

@section('subtitle')
Monitor customer conversations, reply manually, and pause/resume automated bot replies
@endsection

@section('styles')
<style>
    .chat-container {
        height: calc(100vh - 200px);
        min-height: 550px;
        display: flex;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .chat-sidebar {
        width: 340px;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
        background: #fdfdfd;
    }
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #efeae2; /* Classic WA background */
        position: relative;
    }
    .search-wrapper {
        padding: 12px;
        border-bottom: 1px solid #e0e0e0;
        background: #f6f6f6;
    }
    .search-input {
        border-radius: 20px;
        padding-left: 15px;
        font-size: 13px;
    }
    .thread-list {
        flex: 1;
        overflow-y: auto;
    }
    .thread-item {
        display: flex;
        padding: 14px 16px;
        border-bottom: 1px solid #f2f2f2;
        cursor: pointer;
        transition: background 0.2s;
        align-items: center;
    }
    .thread-item:hover, .thread-item.active {
        background: #ebebeb;
    }
    .thread-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #940000;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 12px;
        font-size: 16px;
        flex-shrink: 0;
    }
    .thread-info {
        flex: 1;
        min-width: 0;
    }
    .thread-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        align-items: center;
    }
    .thread-name {
        font-weight: bold;
        font-size: 13.5px;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .thread-time {
        font-size: 10.5px;
        color: #888;
    }
    .thread-snippet-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .thread-snippet {
        font-size: 12px;
        color: #666;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        margin-right: 8px;
    }
    .chat-header {
        padding: 14px 20px;
        background: #f0f2f5;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
    }
    .chat-header-info {
        display: flex;
        align-items: center;
    }
    .chat-header-name {
        font-weight: bold;
        font-size: 15px;
        color: #333;
    }
    .chat-header-phone {
        font-size: 12px;
        color: #666;
    }
    .chat-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .message-wrapper {
        display: flex;
        width: 100%;
    }
    .message-wrapper.incoming {
        justify-content: flex-start;
    }
    .message-wrapper.outgoing {
        justify-content: flex-end;
    }
    .message-bubble {
        max-width: 65%;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        position: relative;
        line-height: 1.4;
        box-shadow: 0 1px 1px rgba(0,0,0,0.08);
        word-break: break-word;
    }
    .message-wrapper.incoming .message-bubble {
        background: #ffffff;
        color: #333;
        border-top-left-radius: 0;
    }
    .message-wrapper.outgoing .message-bubble {
        background: #d9fdd3;
        color: #333;
        border-top-right-radius: 0;
    }
    .message-wrapper.bot .message-bubble {
        background: #e3f2fd;
        border: 1px solid #bbdefb;
    }
    .message-meta {
        font-size: 9.5px;
        color: #888;
        text-align: right;
        margin-top: 4px;
        display: block;
    }
    .chat-footer {
        padding: 12px 20px;
        background: #f0f2f5;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .chat-input {
        flex: 1;
        border: 1px solid #ccc;
        border-radius: 20px;
        padding: 10px 18px;
        outline: none;
        font-size: 13px;
        background: #fff;
    }
    .send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #940000;
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    .send-btn:hover {
        background: #7a0000;
    }
    .empty-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        color: #777;
    }
    .empty-chat i {
        font-size: 64px;
        color: #ccc;
        margin-bottom: 15px;
    }
    .bot-toggle-btn {
        font-size: 12px;
        font-weight: bold;
        border-radius: 20px;
        padding: 6px 16px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .bot-active-btn {
        background-color: #d4edda;
        color: #155724;
    }
    .bot-active-btn:hover {
        background-color: #c3e6cb;
    }
    .bot-paused-btn {
        background-color: #f8d7da;
        color: #721c24;
    }
    .bot-paused-btn:hover {
        background-color: #f5c6cb;
    }
    .status-badge {
        font-size: 9px;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: bold;
    }
    .status-badge-active {
        background: #d4edda;
        color: #155724;
    }
    .status-badge-paused {
        background: #f8d7da;
        color: #721c24;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="chat-container">
            <!-- LEFT SIDEBAR -->
            <div class="chat-sidebar">
                <div class="search-wrapper">
                    <input type="text" id="chatSearch" class="form-control search-input" placeholder="Search number or name...">
                </div>
                <div class="thread-list" id="threadList">
                    @forelse($threads as $thread)
                        <div class="thread-item" data-phone="{{ $thread->phone }}" data-name="{{ $thread->customer_name ?? $thread->phone }}">
                            @php
                                $initials = '';
                                $displayName = $thread->customer_name ?? $thread->phone;
                                $nameParts = explode(' ', $displayName);
                                foreach($nameParts as $p) {
                                    $initials .= strtoupper(substr($p, 0, 1));
                                }
                                $initials = substr($initials, 0, 2);
                            @endphp
                            <div class="thread-avatar">{{ $initials }}</div>
                            <div class="thread-info">
                                <div class="thread-header">
                                    <div class="thread-name">{{ $displayName }}</div>
                                    <div class="thread-time">{{ \Carbon\Carbon::parse($thread->created_at)->diffForHumans() }}</div>
                                </div>
                                <div class="thread-snippet-wrapper">
                                    <div class="thread-snippet">{{ $thread->message }}</div>
                                    <span class="status-badge status-badge-{{ $thread->is_bot_paused ? 'paused' : 'active' }}" id="badge-{{ preg_replace('/[^0-9]/', '', $thread->phone) }}">
                                        {{ $thread->is_bot_paused ? 'Bot Paused' : 'Bot Active' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fa fa-comments-o fa-3x mb-2 d-block"></i>
                            No chats found.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- RIGHT CHAT AREA -->
            <div class="chat-main" id="chatMain">
                <!-- Select Chat Placeholder -->
                <div class="empty-chat" id="emptyPlaceholder">
                    <i class="fa fa-whatsapp"></i>
                    <h4>Trumark WhatsApp Live Chat</h4>
                    <p>Select a chat from the left panel to read logs or reply to a customer.</p>
                </div>

                <!-- Chat Header -->
                <div class="chat-header d-none" id="chatHeader">
                    <div class="chat-header-info">
                        <div class="thread-avatar" id="headerAvatar">WA</div>
                        <div>
                            <div class="chat-header-name" id="headerName">Loading...</div>
                            <div class="chat-header-phone" id="headerPhone">Loading...</div>
                        </div>
                    </div>
                    <div>
                        <button class="bot-toggle-btn bot-active-btn" id="botToggleBtn" onclick="toggleBotStatus()">
                            <i class="fa fa-android mr-1"></i> Bot: Active
                        </button>
                    </div>
                </div>

                <!-- Chat Body (Message logs) -->
                <div class="chat-body d-none" id="chatBody">
                    <!-- Message bubbles injected here -->
                </div>

                <!-- Chat Input Footer -->
                <div class="chat-footer d-none" id="chatFooter">
                    <input type="text" id="chatInput" class="chat-input" placeholder="Type a message..." onkeydown="handleInputKeydown(event)">
                    <button class="send-btn" onclick="sendManualMessage()">
                        <i class="fa fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activePhone = null;
    let activeName = null;
    let pollInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Handle Search filtering
        document.getElementById('chatSearch').addEventListener('input', function(e) {
            let term = e.target.value.toLowerCase();
            document.querySelectorAll('.thread-item').forEach(item => {
                let name = item.getAttribute('data-name').toLowerCase();
                let phone = item.getAttribute('data-phone').toLowerCase();
                if (name.includes(term) || phone.includes(term)) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });

        // Add click events to thread items
        document.querySelectorAll('.thread-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.thread-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                let phone = this.getAttribute('data-phone');
                let name = this.getAttribute('data-name');
                loadThread(phone, name);
            });
        });
    });

    /**
     * Load message thread for phone number
     */
    function loadThread(phone, name) {
        activePhone = phone;
        activeName = name;

        // Show chat components, hide placeholder
        document.getElementById('emptyPlaceholder').classList.add('d-none');
        document.getElementById('chatHeader').classList.remove('d-none');
        document.getElementById('chatBody').classList.remove('d-none');
        document.getElementById('chatFooter').classList.remove('d-none');

        // Setup Header details
        document.getElementById('headerPhone').textContent = phone;
        document.getElementById('headerName').textContent = name;
        
        let initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        document.getElementById('headerAvatar').textContent = initials;

        // Fetch thread messages
        fetchThreadMessages(phone);

        // Start polling for new messages every 5 seconds
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(function() {
            if (activePhone === phone) {
                fetchThreadMessages(phone, true); // true = silent poll (no scroll interrupt unless new message)
            }
        }, 5000);
    }

    let lastMessageCount = 0;

    /**
     * Call backend API to fetch logs
     */
    function fetchThreadMessages(phone, isPoll = false) {
        fetch(`/whatsapp/chat/thread/${phone}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                    updateBotToggleButton(data.is_bot_paused);
                    updateThreadBadge(phone, data.is_bot_paused);

                    // Auto scroll to bottom only on initial load or if message count increases
                    if (!isPoll || data.messages.length > lastMessageCount) {
                        scrollToBottom();
                    }
                    lastMessageCount = data.messages.length;
                }
            })
            .catch(err => console.error("Error fetching chat:", err));
    }

    /**
     * Render message list into bubbles
     */
    function renderMessages(messages) {
        let body = document.getElementById('chatBody');
        body.innerHTML = '';

        messages.forEach(msg => {
            let isIncoming = msg.message.startsWith('INCOMING: ');
            let cleanMessageText = msg.message;
            
            let extraClass = '';
            if (isIncoming) {
                extraClass = 'incoming';
                cleanMessageText = msg.message.replace('INCOMING: ', '');
            } else {
                extraClass = 'outgoing';
                if (msg.message.startsWith('[BOT REPLY] ')) {
                    extraClass += ' bot';
                    cleanMessageText = msg.message.replace('[BOT REPLY] ', '');
                }
            }

            let date = new Date(msg.created_at);
            let timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            let msgHtml = `
                <div class="message-wrapper ${extraClass}">
                    <div class="message-bubble">
                        ${escapeHtml(cleanMessageText)}
                        <span class="message-meta">${timeStr}</span>
                    </div>
                </div>
            `;
            body.insertAdjacentHTML('beforeend', msgHtml);
        });
    }

    /**
     * Send message to customer via backend API
     */
    function sendManualMessage() {
        let input = document.getElementById('chatInput');
        let text = input.value.trim();
        if (!text || !activePhone) return;

        input.disabled = true;

        fetch('/whatsapp/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                phone: activePhone,
                message: text
            })
        })
        .then(res => res.json())
        .then(data => {
            input.disabled = false;
            if (data.success) {
                input.value = '';
                // Reload thread immediately
                fetchThreadMessages(activePhone);
                // Switch button status to Paused (since human replied)
                updateBotToggleButton(true);
                updateThreadBadge(activePhone, true);
            } else {
                alert("Error sending message: " + data.message);
            }
        })
        .catch(err => {
            input.disabled = false;
            console.error(err);
            alert("Exception occurred. Check logs.");
        });
    }

    function handleInputKeydown(e) {
        if (e.key === 'Enter') {
            sendManualMessage();
        }
    }

    /**
     * Toggle active/paused state of bot
     */
    function toggleBotStatus() {
        if (!activePhone) return;

        fetch(`/whatsapp/chat/toggle-bot/${activePhone}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let isPaused = data.status === 'paused';
                updateBotToggleButton(isPaused);
                updateThreadBadge(activePhone, isPaused);
            }
        })
        .catch(err => console.error("Error toggling bot:", err));
    }

    function updateBotToggleButton(isPaused) {
        let btn = document.getElementById('botToggleBtn');
        if (isPaused) {
            btn.className = 'bot-toggle-btn bot-paused-btn';
            btn.innerHTML = '<i class="fa fa-pause-circle mr-1"></i> Bot: Paused';
        } else {
            btn.className = 'bot-toggle-btn bot-active-btn';
            btn.innerHTML = '<i class="fa fa-android mr-1"></i> Bot: Active';
        }
    }

    function updateThreadBadge(phone, isPaused) {
        let cleanPhone = phone.replace(/[^0-9]/g, '');
        let badge = document.getElementById(`badge-${cleanPhone}`);
        if (badge) {
            if (isPaused) {
                badge.className = 'status-badge status-badge-paused';
                badge.textContent = 'Bot Paused';
            } else {
                badge.className = 'status-badge status-badge-active';
                badge.textContent = 'Bot Active';
            }
        }
    }

    function scrollToBottom() {
        let body = document.getElementById('chatBody');
        body.scrollTop = body.scrollHeight;
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;")
            .replace(/\n/g, "<br>");
    }
</script>
@endsection
