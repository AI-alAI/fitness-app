<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get coach ID
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// Get users assigned to this coach's programmes
$query = "SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email
          FROM utilisateurs u
          JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
          JOIN programmes p ON a.id_programme = p.id_programme
          WHERE p.id_coach = :coach
          ORDER BY u.nom";
$stmt = $db->prepare($query);
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (count($users) > 0 ? $users[0]['id_utilisateur'] : 0);

$page_title = 'Messagerie Coach';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Messagerie</h1>
    <p class="text-gray-600">Discutez avec vos clients en temps réel.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1 bg-white rounded-xl shadow-md p-4">
        <h2 class="font-bold text-lg mb-3">Vos clients</h2>
        <?php if (count($users) == 0): ?>
            <p class="text-gray-500">Aucun client assigné pour le moment.</p>
        <?php else: ?>
            <div class="space-y-2" id="user-list">
                <?php foreach ($users as $user): ?>
                    <a href="?user_id=<?php echo $user['id_utilisateur']; ?>" 
                       class="block p-3 rounded-lg hover:bg-purple-50 transition user-item <?php echo ($selected_user_id == $user['id_utilisateur']) ? 'bg-purple-100 border-l-4 border-purple-600' : ''; ?>"
                       data-user-id="<?php echo $user['id_utilisateur']; ?>">
                        <p class="font-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-3 bg-white rounded-xl shadow-md flex flex-col h-[600px]">
        <?php if ($selected_user_id && count($users) > 0): 
            $user = array_filter($users, function($u) use ($selected_user_id) { return $u['id_utilisateur'] == $selected_user_id; });
            $user = reset($user);
        ?>
            <div class="p-4 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
            </div>
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                <div class="text-center text-gray-500">Chargement des messages...</div>
            </div>
            <div class="p-4 border-t bg-white">
                <form id="chat-form" class="flex space-x-2">
                    <input type="hidden" name="destinataire_id" id="destinataire_id" value="<?php echo $selected_user_id; ?>">
                    <input type="text" name="contenu" id="message-input" placeholder="Écrivez votre message..." class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-purple-500">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center text-gray-500">
                <i class="fas fa-comments text-4xl mb-2 block"></i>
                <p>Sélectionnez un client pour commencer à discuter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var lastMessageId = 0;
var currentOtherId = <?php echo $selected_user_id; ?>;
var pollingInterval = null;
var isLoading = false;

function loadMessages() {
    if (!currentOtherId || isLoading) return;
    isLoading = true;
    $.ajax({
        url: '../ajax_get_messages.php',
        method: 'GET',
        data: { other_id: currentOtherId, last_id: lastMessageId },
        dataType: 'json',
        success: function(messages) {
            if (messages.length > 0) {
                var container = $('#chat-messages');
                if (container.find('.text-center').length && container.children().length === 1) {
                    container.empty();
                }
                $.each(messages, function(i, msg) {
                    var isMine = (msg.id_expediteur == <?php echo $user_id; ?>);
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
        },
        complete: function() { isLoading = false; }
    });
}

function escapeHtml(text) {
    return $('<div>').text(text).html();
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
                var container = $('#chat-messages');
                container.scrollTop(container[0].scrollHeight);
            }
        }
    });
}

$(document).ready(function() {
    if (currentOtherId) {
        loadMessages();
        pollingInterval = setInterval(loadMessages, 2000);
    }

    $('#chat-form').submit(function(e) {
        e.preventDefault();
        var input = $('#message-input');
        var message = input.val().trim();
        if (message === '') return;
        sendMessage(message);
        input.val('');
    });

    $('.user-item').click(function(e) {
        var newUserId = $(this).data('user-id');
        if (newUserId == currentOtherId) return;
        if (pollingInterval) clearInterval(pollingInterval);
        currentOtherId = newUserId;
        lastMessageId = 0;
        $('#chat-messages').empty().html('<div class="text-center text-gray-500">Chargement des messages...</div>');
        $('#destinataire_id').val(newUserId);
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