<?php
// utilisateur/ai_chat.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$page_title = 'Coach IA - Smart Fitness';
include '../include/header.php';
?>

<div class="max-w-3xl mx-auto mt-5">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden h-[600px] flex flex-col">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 font-bold text-xl">
            🤖 Coach IA – Smart Fitness
        </div>
        <div id="chat-box" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"></div>
        <div class="p-4 border-t bg-white flex gap-2">
            <input id="message" class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Écrivez votre message..." autocomplete="off">
            <button onclick="sendMessage()" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-full transition shadow">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </div>
    </div>
    <p class="text-xs text-gray-400 text-center mt-2">🔐 Coach IA – réponses personnalisées (Google Gemini)</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let isLoading = false;

function escapeHtml(text) {
    return $('<div>').text(text).html();
}

function loadChat() {
    $.ajax({
        url: '../ajax_get_chat.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let box = $('#chat-box');
            box.empty();
            if (!data.length) {
                box.html('<div class="text-center text-gray-500 mt-4">👋 Bonjour ! Posez votre première question à votre coach IA.</div>');
            } else {
                data.forEach(msg => {
                    let isUser = (msg.role === 'user');
                    let align = isUser ? 'justify-end' : 'justify-start';
                    let bubbleClass = isUser ? 'bg-purple-600 text-white' : 'bg-white border border-gray-200 text-gray-800';
                    let time = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                    box.append(`
                        <div class="flex ${align}">
                            <div class="max-w-[75%] ${bubbleClass} rounded-2xl px-4 py-2 shadow-sm">
                                <p>${escapeHtml(msg.message)}</p>
                                <p class="text-xs ${isUser ? 'text-purple-200' : 'text-gray-400'} mt-1 text-right">${time}</p>
                            </div>
                        </div>
                    `);
                });
            }
            box.scrollTop(box[0].scrollHeight);
        },
        error: function(xhr, status, error) {
            console.error("Ajax error:", error);
            $('#chat-box').html('<div class="text-center text-red-500 mt-4">❌ Erreur de chargement. Vérifiez que les fichiers sont bien placés à la racine du projet.</div>');
        }
    });
}

function sendMessage() {
    if (isLoading) return;
    let msg = $('#message').val().trim();
    if (!msg) return;

    isLoading = true;
    const btn = $('button');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Envoi...');

    // Display user message instantly
    let box = $('#chat-box');
    box.append(`
        <div class="flex justify-end">
            <div class="max-w-[75%] bg-purple-600 text-white rounded-2xl px-4 py-2 shadow-sm">
                <p>${escapeHtml(msg)}</p>
                <p class="text-xs text-purple-200 mt-1 text-right">À l'instant</p>
            </div>
        </div>
    `);
    box.scrollTop(box[0].scrollHeight);
    $('#message').val('');

    $.ajax({
        url: '../ajax_send_chat.php',
        method: 'POST',
        data: { message: msg },
        dataType: 'json',
        success: function(res) {
            if (res.reply) {
                box.append(`
                    <div class="flex justify-start">
                        <div class="max-w-[75%] bg-white border border-gray-200 text-gray-800 rounded-2xl px-4 py-2 shadow-sm">
                            <p>${escapeHtml(res.reply)}</p>
                            <p class="text-xs text-gray-400 mt-1 text-left">À l'instant</p>
                        </div>
                    </div>
                `);
                box.scrollTop(box[0].scrollHeight);
            } else if (res.error) {
                alert('Erreur: ' + res.error);
            }
        },
        error: function(xhr, status, error) {
            alert('Erreur de communication avec le serveur.\nVérifiez que les fichiers sont à la racine.');
            console.error(xhr.responseText);
        },
        complete: function() {
            isLoading = false;
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Envoyer');
        }
    });
}

$(document).ready(function() {
    loadChat();
    setInterval(loadChat, 5000);
    $('#message').keypress(function(e) {
        if (e.which === 13) sendMessage();
    });
});
</script>

<?php include '../include/footer.php'; ?>