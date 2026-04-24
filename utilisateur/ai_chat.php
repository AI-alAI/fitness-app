<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$page_title = 'Coach IA - Smart Fitness';
include '../include/header.php';
?>

<div class="max-w-3xl mx-auto mt-5 px-4">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden flex flex-col" style="height:600px">

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-xl">
                🤖
            </div>
            <div>
                <p class="font-bold text-lg leading-tight">Coach IA – Smart Fitness</p>
                <p class="text-xs text-purple-200" id="status-text">En ligne</p>
            </div>
        </div>

        <!-- Chat messages -->
        <div id="chat-box" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"></div>

        <!-- Input -->
        <div class="p-4 border-t bg-white flex gap-2 items-center">
            <input id="message"
                   class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                   placeholder="Écris ton message..."
                   autocomplete="off"
                   maxlength="500">
            <button id="send-btn"
                    onclick="sendMessage()"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-full transition shadow text-sm font-medium flex items-center gap-2">
                <i class="fas fa-paper-plane"></i>
                <span>Envoyer</span>
            </button>
        </div>
    </div>
    <p class="text-xs text-gray-400 text-center mt-2">
        🔐 Réponses personnalisées par Google Gemini
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let isLoading = false;

function escapeHtml(text) {
    return $('<div>').text(text).html();
}

// ✅ NEW: Markdown formatter
function formatMarkdown(text) {
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')  // **bold**
        .replace(/\*(.*?)\*/g, '<em>$1</em>')              // *italic*
        .replace(/- (.*?)(\n|$)/g, '• $1<br>')             // - list → bullet
        .replace(/\n/g, '<br>');                           // line breaks
}

function formatTime(dateStr) {
    try {
        return new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    } catch(e) {
        return new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    }
}

// ✅ UPDATED buildBubble
function buildBubble(role, text, time) {
    const isUser    = (role === 'user');
    const align     = isUser ? 'justify-end' : 'justify-start';
    const bubble    = isUser
        ? 'bg-purple-600 text-white rounded-tr-none'
        : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none';
    const timeColor = isUser ? 'text-purple-200' : 'text-gray-400';
    const timeAlign = isUser ? 'text-right' : 'text-left';
    const avatar    = isUser ? '' : `
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs flex-shrink-0 mt-1">
            🤖
        </div>`;

    // 🔥 KEY FIX HERE
    const formatted = isUser 
        ? escapeHtml(text) 
        : formatMarkdown(escapeHtml(text));

    return `
        <div class="flex ${align} items-start gap-2">
            ${!isUser ? avatar : ''}
            <div class="max-w-xs sm:max-w-sm lg:max-w-md ${bubble} rounded-2xl px-4 py-2 shadow-sm">
                <p class="text-sm leading-relaxed">${formatted}</p>
                <p class="text-xs ${timeColor} mt-1 ${timeAlign}">${time}</p>
            </div>
        </div>`;
}

function showTypingIndicator() {
    if ($('#typing-indicator').length) return;
    $('#chat-box').append(`
        <div id="typing-indicator" class="flex justify-start items-start gap-2">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs flex-shrink-0 mt-1">
                🤖
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm flex gap-1 items-center">
                <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce"></span>
            </div>
        </div>
    `);
    scrollBottom();
}

function removeTypingIndicator() {
    $('#typing-indicator').remove();
}

function scrollBottom() {
    const box = document.getElementById('chat-box');
    box.scrollTop = box.scrollHeight;
}

function setStatus(text, color = 'text-purple-200') {
    $('#status-text').removeClass('text-purple-200 text-yellow-300 text-red-300')
                     .addClass(color)
                     .text(text);
}

function loadHistory() {
    $.getJSON('../ajax_get_chat_memory.php', function(data) {
        const box = $('#chat-box');
        box.empty();

        if (!data || !data.length) {
            box.append(`
                <div class="flex justify-start items-start gap-2">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs flex-shrink-0 mt-1">
                        🤖
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm max-w-sm">
                        <p class="text-sm text-gray-800">Bonjour ! 💪 Je suis ton coach IA fitness. Par quoi commences-tu aujourd'hui ?</p>
                        <p class="text-xs text-gray-400 mt-1">maintenant</p>
                    </div>
                </div>
            `);
        } else {
            data.forEach(function(msg) {
                box.append(buildBubble(msg.role, msg.message, formatTime(msg.created_at)));
            });
        }
        scrollBottom();
    });
}

function sendMessage() {
    if (isLoading) return;
    const msg = $('#message').val().trim();
    if (!msg) return;

    isLoading = true;
    const btn = $('#send-btn');
    btn.prop('disabled', true).text('Envoi...');

    $('#chat-box').append(
        buildBubble('user', msg, new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}))
    );

    $('#message').val('');
    showTypingIndicator();

    $.ajax({
        url: '../ajax_send_chat.php',
        method: 'POST',
        data: { message: msg },
        dataType: 'json',
        success: function(res) {
            removeTypingIndicator();

            if (res.reply) {
                $('#chat-box').append(
                    buildBubble('assistant', res.reply,
                        new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}))
                );
            }
        },
        complete: function() {
            isLoading = false;
            btn.prop('disabled', false).text('Envoyer');
        }
    });
}

$(document).ready(function() {
    loadHistory();

    $('#message').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $('#message').focus();
});
</script>

<?php include '../include/footer.php'; ?>