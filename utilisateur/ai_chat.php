<?php
// utilisateur/ai_chat.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$page_title = 'Coach IA - Smart Fitness';
include '../include/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap');

:root {
    --sidebar-bg: #0f0f13;
    --sidebar-width: 260px;
    --chat-bg: #16161d;
    --bubble-ai: #1e1e2e;
    --bubble-user: #6c47ff;
    --accent: #6c47ff;
    --accent2: #a78bfa;
    --text-main: #f0eeff;
    --text-muted: #7c7a92;
    --border: rgba(255,255,255,0.07);
    --input-bg: #1e1e2e;
    --hover: rgba(108,71,255,0.13);
    --radius: 16px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--chat-bg);
    color: var(--text-main);
    height: 100vh;
    overflow: hidden;
}

/* ── Layout ── */
#app {
    display: flex;
    height: 100vh;
    overflow: hidden;
}

/* ── Sidebar ── */
#sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border);
    transition: transform 0.3s ease;
    flex-shrink: 0;
    z-index: 10;
}

#sidebar-header {
    padding: 20px 16px 12px;
    border-bottom: 1px solid var(--border);
}

#sidebar-header .brand {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}

#new-chat-btn {
    width: 100%;
    padding: 9px 14px;
    background: var(--hover);
    border: 1px solid rgba(108,71,255,0.3);
    border-radius: 10px;
    color: var(--accent2);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

#new-chat-btn:hover {
    background: rgba(108,71,255,0.25);
    border-color: var(--accent);
    color: #fff;
}

#sessions-label {
    padding: 14px 16px 6px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-muted);
}

#sessions-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 8px 8px;
    scrollbar-width: thin;
    scrollbar-color: #2a2a3a transparent;
}

#sessions-list::-webkit-scrollbar { width: 4px; }
#sessions-list::-webkit-scrollbar-thumb { background: #2a2a3a; border-radius: 4px; }

.session-item {
    padding: 9px 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 2px;
    border: 1px solid transparent;
}

.session-item:hover { background: var(--hover); }
.session-item.active {
    background: var(--hover);
    border-color: rgba(108,71,255,0.35);
}

.session-icon {
    font-size: 0.95rem;
    flex-shrink: 0;
    opacity: 0.75;
}

.session-text {
    flex: 1;
    min-width: 0;
}

.session-title {
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.session-date {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 1px;
}

.session-item .del-btn {
    opacity: 0;
    background: none;
    border: none;
    color: #ff6b6b;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 2px 4px;
    border-radius: 4px;
    transition: opacity 0.15s;
    flex-shrink: 0;
}

.session-item:hover .del-btn { opacity: 1; }

#sidebar-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    font-size: 0.72rem;
    color: var(--text-muted);
    text-align: center;
}

/* ── Main chat area ── */
#chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: var(--chat-bg);
}

/* Top bar */
#chat-topbar {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(15,15,19,0.6);
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

#mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px;
}

.topbar-avatar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--accent), #a78bfa);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.topbar-info { flex: 1; }
.topbar-name {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text-main);
    line-height: 1.2;
}
.topbar-status {
    font-size: 0.72rem;
    color: #4ade80;
    display: flex;
    align-items: center;
    gap: 4px;
}
.topbar-status::before {
    content: '';
    width: 6px; height: 6px;
    background: #4ade80;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Messages */
#chat-box {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    scrollbar-width: thin;
    scrollbar-color: #2a2a3a transparent;
}

#chat-box::-webkit-scrollbar { width: 5px; }
#chat-box::-webkit-scrollbar-thumb { background: #2a2a3a; border-radius: 4px; }

.msg-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    animation: fadeUp 0.25s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.msg-row.user { flex-direction: row-reverse; }

.msg-avatar {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--accent), #a78bfa);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    margin-bottom: 2px;
}

.msg-bubble {
    max-width: min(480px, 72%);
    padding: 11px 15px;
    border-radius: 16px;
    font-size: 0.88rem;
    line-height: 1.6;
    position: relative;
    word-break: break-word;
}

.msg-row.ai .msg-bubble {
    background: var(--bubble-ai);
    border: 1px solid var(--border);
    border-bottom-left-radius: 4px;
    color: var(--text-main);
}

.msg-row.user .msg-bubble {
    background: var(--bubble-user);
    border-bottom-right-radius: 4px;
    color: #fff;
}

.msg-time {
    font-size: 0.68rem;
    margin-top: 5px;
    opacity: 0.55;
    text-align: right;
}

.msg-row.ai .msg-time { text-align: left; }

/* Welcome screen */
#welcome-screen {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    text-align: center;
    padding: 32px;
    color: var(--text-muted);
}

#welcome-screen .big-icon {
    font-size: 3.5rem;
    filter: drop-shadow(0 0 20px rgba(108,71,255,0.4));
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

#welcome-screen h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem;
    color: var(--text-main);
    font-weight: 700;
}

#welcome-screen p { font-size: 0.88rem; max-width: 320px; }

.suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-top: 8px;
}

.suggestion-chip {
    padding: 7px 14px;
    background: var(--bubble-ai);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 0.8rem;
    color: var(--accent2);
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}

.suggestion-chip:hover {
    background: var(--hover);
    border-color: var(--accent);
    color: #fff;
    transform: translateY(-1px);
}

/* Typing indicator */
#typing-indicator .msg-bubble {
    padding: 12px 16px;
}

.dot-flashing {
    display: flex;
    gap: 5px;
    align-items: center;
}

.dot-flashing span {
    width: 7px; height: 7px;
    background: var(--accent2);
    border-radius: 50%;
    animation: dotBounce 1.2s infinite ease-in-out;
}

.dot-flashing span:nth-child(2) { animation-delay: 0.2s; }
.dot-flashing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes dotBounce {
    0%, 80%, 100% { transform: scale(0.7); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

/* Input bar */
#input-bar {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    background: rgba(15,15,19,0.8);
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

#input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 8px 8px 8px 16px;
    transition: border-color 0.2s;
}

#input-wrap:focus-within {
    border-color: rgba(108,71,255,0.5);
    box-shadow: 0 0 0 3px rgba(108,71,255,0.08);
}

#message {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: var(--text-main);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    resize: none;
    max-height: 120px;
    min-height: 24px;
    line-height: 1.5;
}

#message::placeholder { color: var(--text-muted); }

#send-btn {
    width: 38px; height: 38px;
    background: var(--accent);
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;
}

#send-btn:hover { background: #7c5cff; transform: scale(1.05); }
#send-btn:disabled { background: #333; cursor: not-allowed; transform: none; }

#input-hint {
    text-align: center;
    font-size: 0.68rem;
    color: var(--text-muted);
    margin-top: 8px;
}

/* Mobile overlay */
#sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 9;
}

/* Responsive */
@media (max-width: 768px) {
    #sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        transform: translateX(-100%);
    }
    #sidebar.open { transform: translateX(0); }
    #sidebar-overlay.open { display: block; }
    #mobile-menu-btn { display: block; }
    :root { --sidebar-width: 280px; }
}
</style>

<div id="app">

    <!-- ── Sidebar ── -->
    <div id="sidebar">
        <div id="sidebar-header">
            <div class="brand">
                <span>⚡</span> Smart Fitness
            </div>
            <button id="new-chat-btn" onclick="newChat()">
                <span>✏️</span> Nouvelle conversation
            </button>
        </div>

        <div id="sessions-label">Historique</div>
        <div id="sessions-list">
            <!-- filled by JS -->
        </div>

        <div id="sidebar-footer">
            🔐 Propulsé par IA · Données personnelles
        </div>
    </div>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- ── Chat area ── -->
    <div id="chat-area">

        <!-- Top bar -->
        <div id="chat-topbar">
            <button id="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
            <div class="topbar-avatar">🤖</div>
            <div class="topbar-info">
                <div class="topbar-name">Coach IA – Smart Fitness</div>
                <div class="topbar-status">En ligne</div>
            </div>
        </div>

        <!-- Messages -->
        <div id="chat-box"></div>

        <!-- Input -->
        <div id="input-bar">
            <div id="input-wrap">
                <textarea id="message" rows="1" placeholder="Pose ta question au coach..."></textarea>
                <button id="send-btn" onclick="sendMessage()" title="Envoyer">➤</button>
            </div>
            <div id="input-hint">Entrée pour envoyer · Shift+Entrée pour nouvelle ligne</div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let isLoading  = false;
let sessions   = [];   // [{id, title, date, messages:[]}]
let currentId  = null;

// ── Utilities ────────────────────────────────────────────────
function escHtml(t) { return $('<div>').text(t).html(); }
function fmtTime(d) {
    try { return new Date(d).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); }
    catch(e) { return now(); }
}
function now() { return new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); }
function fmtDate(d) {
    try {
        const dt = new Date(d);
        const today = new Date();
        if (dt.toDateString() === today.toDateString()) return "Aujourd'hui";
        const yest = new Date(today); yest.setDate(today.getDate()-1);
        if (dt.toDateString() === yest.toDateString()) return 'Hier';
        return dt.toLocaleDateString('fr-FR', {day:'numeric',month:'short'});
    } catch(e) { return ''; }
}

function scrollBottom() {
    const b = document.getElementById('chat-box');
    b.scrollTop = b.scrollHeight;
}

// ── Session storage (localStorage) ───────────────────────────
function saveSessions() {
    localStorage.setItem('sf_sessions', JSON.stringify(sessions));
}

function loadSessions() {
    try {
        sessions = JSON.parse(localStorage.getItem('sf_sessions') || '[]');
    } catch(e) { sessions = []; }
}

// ── Sidebar rendering ─────────────────────────────────────────
function renderSidebar() {
    const list = $('#sessions-list').empty();

    if (!sessions.length) {
        list.append(`<div style="padding:16px;font-size:0.78rem;color:var(--text-muted);text-align:center;">
            Aucune conversation<br>pour l'instant
        </div>`);
        return;
    }

    // newest first
    const sorted = [...sessions].sort((a,b) => new Date(b.date) - new Date(a.date));

    sorted.forEach(s => {
        const active = s.id === currentId ? 'active' : '';
        list.append(`
            <div class="session-item ${active}" data-id="${s.id}" onclick="loadSession('${s.id}')">
                <span class="session-icon">💬</span>
                <div class="session-text">
                    <div class="session-title">${escHtml(s.title)}</div>
                    <div class="session-date">${fmtDate(s.date)}</div>
                </div>
                <button class="del-btn" onclick="deleteSession(event,'${s.id}')" title="Supprimer">✕</button>
            </div>
        `);
    });
}

// ── Welcome screen ────────────────────────────────────────────
function showWelcome() {
    $('#chat-box').html(`
        <div id="welcome-screen">
            <div class="big-icon">🏋️</div>
            <h2>Prêt à t'entraîner ?</h2>
            <p>Ton coach IA personnel est là pour t'aider à atteindre tes objectifs fitness.</p>
            <div class="suggestions">
                <span class="suggestion-chip" onclick="quickSend(this)">💪 Programme débutant</span>
                <span class="suggestion-chip" onclick="quickSend(this)">🔥 Perdre du poids</span>
                <span class="suggestion-chip" onclick="quickSend(this)">🍎 Conseils nutrition</span>
                <span class="suggestion-chip" onclick="quickSend(this)">🦵 Exercices abdos</span>
                <span class="suggestion-chip" onclick="quickSend(this)">🏃 Améliorer mon cardio</span>
                <span class="suggestion-chip" onclick="quickSend(this)">💤 Récupération musculaire</span>
            </div>
        </div>
    `);
}

function quickSend(el) {
    $('#message').val(el.textContent.replace(/^[^\w]+/, '').trim());
    sendMessage();
}

// ── Build a message bubble ────────────────────────────────────
function buildBubble(role, text, time) {
    const isUser = role === 'user';
    const rowClass = isUser ? 'user' : 'ai';
    const avatar = isUser ? '' : `<div class="msg-avatar">🤖</div>`;
    const avatarRight = isUser ? `<div class="msg-avatar" style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">👤</div>` : '';

    return `
        <div class="msg-row ${rowClass}">
            ${avatar}
            <div>
                <div class="msg-bubble">${escHtml(text)}</div>
                <div class="msg-time">${time}</div>
            </div>
            ${avatarRight}
        </div>`;
}

// ── Typing indicator ──────────────────────────────────────────
function showTyping() {
    if ($('#typing-indicator').length) return;
    $('#chat-box').append(`
        <div id="typing-indicator" class="msg-row ai">
            <div class="msg-avatar">🤖</div>
            <div class="msg-bubble">
                <div class="dot-flashing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>`);
    scrollBottom();
}
function hideTyping() { $('#typing-indicator').remove(); }

// ── New chat ──────────────────────────────────────────────────
function newChat() {
    currentId = null;
    showWelcome();
    renderSidebar();
    $('#message').focus();
    closeSidebar();
}

// ── Load a session from sidebar ───────────────────────────────
function loadSession(id) {
    const s = sessions.find(x => x.id === id);
    if (!s) return;

    currentId = id;
    const box = $('#chat-box').empty();

    s.messages.forEach(m => {
        box.append(buildBubble(m.role, m.text, m.time));
    });

    scrollBottom();
    renderSidebar();
    closeSidebar();
    $('#message').focus();
}

// ── Delete a session ──────────────────────────────────────────
function deleteSession(e, id) {
    e.stopPropagation();
    sessions = sessions.filter(x => x.id !== id);
    saveSessions();
    if (currentId === id) newChat();
    else renderSidebar();
}

// ── Send message ──────────────────────────────────────────────
function sendMessage() {
    if (isLoading) return;
    const msg = $('#message').val().trim();
    if (!msg) return;

    // Remove welcome screen if present
    $('#welcome-screen').remove();

    isLoading = true;
    const btn = $('#send-btn').prop('disabled', true).text('…');

    const t = now();
    $('#chat-box').append(buildBubble('user', msg, t));
    $('#message').val('').css('height', 'auto');
    scrollBottom();
    showTyping();

    $.ajax({
        url: '../ajax_send_chat.php',
        method: 'POST',
        data: { message: msg },
        dataType: 'json',
        success(res) {
            hideTyping();
            const replyTime = now();
            const reply = res.reply || ('⚠️ ' + (res.error || 'Erreur inconnue'));
            const role  = res.reply ? 'assistant' : 'assistant';

            $('#chat-box').append(buildBubble('assistant', reply, replyTime));
            scrollBottom();

            // Save to session
            saveToSession(msg, t, reply, replyTime);
        },
        error() {
            hideTyping();
            $('#chat-box').append(buildBubble('assistant', '❌ Erreur de connexion. Réessaie plus tard.', now()));
            scrollBottom();
        },
        complete() {
            isLoading = false;
            btn.prop('disabled', false).html('➤');
        }
    });
}

// ── Save exchange to local session ───────────────────────────
function saveToSession(userMsg, userTime, aiMsg, aiTime) {
    if (!currentId) {
        // Create new session
        currentId = 'ses_' + Date.now();
        const title = userMsg.length > 35 ? userMsg.slice(0, 35) + '…' : userMsg;
        sessions.unshift({
            id: currentId,
            title: title,
            date: new Date().toISOString(),
            messages: []
        });
    }

    const s = sessions.find(x => x.id === currentId);
    if (s) {
        s.messages.push({ role: 'user',      text: userMsg, time: userTime });
        s.messages.push({ role: 'assistant', text: aiMsg,   time: aiTime  });
        s.date = new Date().toISOString();
    }

    saveSessions();
    renderSidebar();
}

// ── Mobile sidebar ────────────────────────────────────────────
function toggleSidebar() {
    $('#sidebar').toggleClass('open');
    $('#sidebar-overlay').toggleClass('open');
}
function closeSidebar() {
    $('#sidebar').removeClass('open');
    $('#sidebar-overlay').removeClass('open');
}

// ── Auto-resize textarea ──────────────────────────────────────
$('#message').on('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ── Keyboard shortcut ─────────────────────────────────────────
$('#message').on('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// ── Init ──────────────────────────────────────────────────────
$(document).ready(function() {
    loadSessions();
    renderSidebar();

    // Load most recent session or show welcome
    if (sessions.length > 0) {
        // Show welcome but keep history in sidebar
        showWelcome();
    } else {
        showWelcome();
    }

    $('#message').focus();
});
</script>

<?php include '../include/footer.php'; ?>