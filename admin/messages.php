<?php
// admin/messages.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();
$admin_id = $_SESSION['user_id'];

// Récupérer les utilisateurs (role = 'utilisateur')
$users = $db->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE role = 'utilisateur' AND id_utilisateur != $admin_id ORDER BY nom")->fetchAll();

// Récupérer les coachs (role = 'coach')
$coachs = $db->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE role = 'coach' AND id_utilisateur != $admin_id ORDER BY nom")->fetchAll();

$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$active_tab = isset($_GET['tab']) && $_GET['tab'] == 'coachs' ? 'coachs' : 'users';

$page_title = 'Messagerie Admin';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Messagerie</h1>
    <p class="text-gray-600">Communication avec les utilisateurs et les coachs.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Liste des contacts avec onglets -->
    <div class="lg:col-span-1 bg-white rounded-xl shadow-md p-4">
        <div class="flex border-b mb-3">
            <button id="tab-users" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'users' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500'; ?>">Utilisateurs</button>
            <button id="tab-coachs" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'coachs' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500'; ?>">Coachs</button>
        </div>
        <div class="mb-3">
            <input type="text" id="searchContact" placeholder="Rechercher..." class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <!-- Liste des utilisateurs -->
        <div id="users-list" class="space-y-2 max-h-[500px] overflow-y-auto <?php echo $active_tab == 'users' ? '' : 'hidden'; ?>">
            <?php foreach ($users as $u): ?>
                <a href="?tab=users&user_id=<?php echo $u['id_utilisateur']; ?>" 
                   class="contact-item block p-3 rounded-lg hover:bg-purple-50 transition <?php echo ($selected_user_id == $u['id_utilisateur'] && $active_tab == 'users') ? 'bg-purple-100 border-l-4 border-purple-600' : ''; ?>"
                   data-name="<?php echo strtolower($u['prenom'] . ' ' . $u['nom']); ?>"
                   data-role="user">
                    <p class="font-semibold"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($u['email']); ?></p>
                    <p class="text-xs text-gray-400">Utilisateur</p>
                </a>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <p class="text-gray-500 text-center py-4">Aucun utilisateur</p>
            <?php endif; ?>
        </div>
        <!-- Liste des coachs -->
        <div id="coachs-list" class="space-y-2 max-h-[500px] overflow-y-auto <?php echo $active_tab == 'coachs' ? '' : 'hidden'; ?>">
            <?php foreach ($coachs as $c): ?>
                <a href="?tab=coachs&user_id=<?php echo $c['id_utilisateur']; ?>" 
                   class="contact-item block p-3 rounded-lg hover:bg-purple-50 transition <?php echo ($selected_user_id == $c['id_utilisateur'] && $active_tab == 'coachs') ? 'bg-purple-100 border-l-4 border-purple-600' : ''; ?>"
                   data-name="<?php echo strtolower($c['prenom'] . ' ' . $c['nom']); ?>"
                   data-role="coach">
                    <p class="font-semibold"><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($c['email']); ?></p>
                    <p class="text-xs text-gray-400">Coach</p>
                </a>
            <?php endforeach; ?>
            <?php if (empty($coachs)): ?>
                <p class="text-gray-500 text-center py-4">Aucun coach</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Zone de chat -->
    <div class="lg:col-span-3 bg-white rounded-xl shadow-md flex flex-col h-[600px]">
        <?php if ($selected_user_id): 
            // Déterminer le nom du contact
            $contact = null;
            foreach (array_merge($users, $coachs) as $c) {
                if ($c['id_utilisateur'] == $selected_user_id) {
                    $contact = $c;
                    break;
                }
            }
        ?>
            <div class="p-4 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']); ?></h3>
                <span class="text-sm text-gray-500"><?php echo $contact['role'] ?? (in_array($selected_user_id, array_column($users, 'id_utilisateur')) ? 'Utilisateur' : 'Coach'); ?></span>
            </div>
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                <div class="text-center text-gray-500">Chargement...</div>
            </div>
            <div class="p-4 border-t bg-white">
                <form id="chat-form" class="flex space-x-2">
                    <input type="hidden" name="destinataire_id" id="destinataire_id" value="<?php echo $selected_user_id; ?>">
                    <input type="text" name="contenu" id="message-input" placeholder="Écrivez votre message..." class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-purple-500">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center text-gray-500">
                <i class="fas fa-comments text-4xl mb-2 block"></i>
                <p>Sélectionnez un utilisateur ou un coach pour commencer à discuter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var lastMessageId = 0;
var currentOtherId = <?php echo $selected_user_id; ?>;
var pollingInterval = null;

function loadMessages() {
    if (!currentOtherId) return;
    $.ajax({
        url: '../ajax_get_messages.php',
        method: 'GET',
        data: { other_id: currentOtherId, last_id: lastMessageId },
        dataType: 'json',
        success: function(messages) {
            if (messages.length > 0) {
                var container = $('#chat-messages');
                if (container.find('.text-center').length && container.children().length === 1) container.empty();
                $.each(messages, function(i, msg) {
                    var isMine = (msg.id_expediteur == <?php echo $admin_id; ?>);
                    var time = new Date(msg.date_envoi).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                    var html = '<div class="flex ' + (isMine ? 'justify-end' : 'justify-start') + ' message-item">' +
                                '<div class="max-w-[70%] ' + (isMine ? 'bg-purple-600 text-white' : 'bg-white border border-gray-200 text-gray-800') + ' rounded-lg px-4 py-2 shadow-sm">' +
                                  '<p class="break-words">' + escapeHtml(msg.contenu) + '</p>' +
                                  '<p class="text-xs ' + (isMine ? 'text-purple-200' : 'text-gray-400') + ' mt-1 text-right">' + time + '</p>' +
                                '</div>' +
                              '</div>';
                    container.append(html);
                    if (msg.id_message > lastMessageId) lastMessageId = msg.id_message;
                });
                container.scrollTop(container[0].scrollHeight);
            }
        }
    });
}

function sendMessage(message) {
    $.ajax({
        url: '../ajax_send_message.php',
        method: 'POST',
        data: { destinataire_id: currentOtherId, contenu: message },
        dataType: 'json',
        success: function(response) {
            if (response && response.id_message) {
                var time = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                var html = '<div class="flex justify-end message-item">' +
                            '<div class="max-w-[70%] bg-purple-600 text-white rounded-lg px-4 py-2 shadow-sm">' +
                              '<p class="break-words">' + escapeHtml(response.contenu) + '</p>' +
                              '<p class="text-xs text-purple-200 mt-1 text-right">' + time + '</p>' +
                            '</div>' +
                          '</div>';
                $('#chat-messages').append(html);
                if (response.id_message > lastMessageId) lastMessageId = response.id_message;
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
            }
        }
    });
}

function escapeHtml(text) { return $('<div>').text(text).html(); }

$(document).ready(function() {
    if (currentOtherId) { loadMessages(); pollingInterval = setInterval(loadMessages, 2000); }

    $('#chat-form').submit(function(e) {
        e.preventDefault();
        var input = $('#message-input');
        var message = input.val().trim();
        if (!message) return;
        sendMessage(message);
        input.val('');
    });

    // Filtre de recherche
    $('#searchContact').on('keyup', function() {
        var search = this.value.toLowerCase();
        $('.contact-item').each(function() {
            var name = $(this).data('name');
            $(this).toggle(name.includes(search));
        });
    });

    // Changement d'onglet
    $('#tab-users').click(function() {
        $('#tab-users').addClass('text-purple-600 border-b-2 border-purple-600').removeClass('text-gray-500');
        $('#tab-coachs').removeClass('text-purple-600 border-b-2 border-purple-600').addClass('text-gray-500');
        $('#users-list').removeClass('hidden');
        $('#coachs-list').addClass('hidden');
        // Reset selected user to none
        if (currentOtherId) {
            if (pollingInterval) clearInterval(pollingInterval);
            currentOtherId = 0;
            $('#chat-messages').empty().html('<div class="text-center text-gray-500">Sélectionnez un contact</div>');
        }
        // Update URL without user_id
        var url = new URL(window.location.href);
        url.searchParams.delete('user_id');
        url.searchParams.set('tab', 'users');
        window.history.pushState({}, '', url);
    });
    $('#tab-coachs').click(function() {
        $('#tab-coachs').addClass('text-purple-600 border-b-2 border-purple-600').removeClass('text-gray-500');
        $('#tab-users').removeClass('text-purple-600 border-b-2 border-purple-600').addClass('text-gray-500');
        $('#coachs-list').removeClass('hidden');
        $('#users-list').addClass('hidden');
        if (currentOtherId) {
            if (pollingInterval) clearInterval(pollingInterval);
            currentOtherId = 0;
            $('#chat-messages').empty().html('<div class="text-center text-gray-500">Sélectionnez un contact</div>');
        }
        var url = new URL(window.location.href);
        url.searchParams.delete('user_id');
        url.searchParams.set('tab', 'coachs');
        window.history.pushState({}, '', url);
    });

    // Gestion du clic sur un contact (mise à jour de la variable currentOtherId)
    $('.contact-item').click(function(e) {
        var href = $(this).attr('href');
        var newId = href.split('=')[1];
        if (newId == currentOtherId) return;
        if (pollingInterval) clearInterval(pollingInterval);
        currentOtherId = newId;
        lastMessageId = 0;
        $('#chat-messages').empty().html('<div class="text-center text-gray-500">Chargement...</div>');
        $('#destinataire_id').val(newId);
        loadMessages();
        pollingInterval = setInterval(loadMessages, 2000);
    });
});
</script>
<style>
    #chat-messages::-webkit-scrollbar { width: 6px; }
    #chat-messages::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    #chat-messages::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
    .message-item { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php include '../include/footer.php'; ?>