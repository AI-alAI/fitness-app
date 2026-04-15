<?php
// utilisateur/calendrier.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer les séances pour les afficher dans le calendrier
$query = "SELECT id_seance, date_seance, duree, calories FROM seances WHERE id_utilisateur = :id ORDER BY date_seance";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($seances as $seance) {
    $events[] = [
        'title' => "Séance: {$seance['duree']} min - {$seance['calories']} cal",
        'start' => $seance['date_seance'],
        'url' => 'seances.php'
    ];
}

$page_title = 'Calendrier';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Calendrier des séances</h1>
    <p class="text-gray-600 mt-2">Visualisez votre historique d'entraînement</p>
</div>

<div class="bg-white rounded-xl shadow-md p-6">
    <div id="calendar" class="h-[600px]"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'fr',
            events: <?php echo json_encode($events); ?>,
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });
        calendar.render();
    });
</script>

<?php include '../include/footer.php'; ?>