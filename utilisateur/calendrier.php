<?php
// utilisateur/calendrier.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle AJAX requests for adding/editing sessions from calendar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['ajax_action'])) {
        if ($_POST['ajax_action'] === 'add') {
            $date_seance = $_POST['date_seance'];
            $duree = intval($_POST['duree']);
            $calories = intval($_POST['calories']);
            $notes = trim($_POST['notes'] ?? '');
            
            $query = "INSERT INTO seances (id_utilisateur, date_seance, duree, calories, notes) 
                      VALUES (:id, :date, :duree, :calories, :notes)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':date', $date_seance);
            $stmt->bindParam(':duree', $duree);
            $stmt->bindParam(':calories', $calories);
            $stmt->bindParam(':notes', $notes);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Séance ajoutée';
                $response['id'] = $db->lastInsertId();
            } else {
                $response['message'] = 'Erreur lors de l\'ajout';
            }
        } elseif ($_POST['ajax_action'] === 'edit') {
            $id_seance = intval($_POST['id_seance']);
            $duree = intval($_POST['duree']);
            $calories = intval($_POST['calories']);
            $notes = trim($_POST['notes'] ?? '');
            
            $query = "UPDATE seances SET duree=:duree, calories=:calories, notes=:notes 
                      WHERE id_seance=:id AND id_utilisateur=:user";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':duree', $duree);
            $stmt->bindParam(':calories', $calories);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':id', $id_seance);
            $stmt->bindParam(':user', $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Séance modifiée';
            } else {
                $response['message'] = 'Erreur lors de la modification';
            }
        } elseif ($_POST['ajax_action'] === 'delete') {
            $id_seance = intval($_POST['id_seance']);
            $query = "DELETE FROM seances WHERE id_seance=:id AND id_utilisateur=:user";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_seance);
            $stmt->bindParam(':user', $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Séance supprimée';
            } else {
                $response['message'] = 'Erreur lors de la suppression';
            }
        }
    }
    echo json_encode($response);
    exit();
}

// Get sessions for calendar (with additional info)
$query = "SELECT s.*, p.titre as programme_titre 
          FROM seances s 
          LEFT JOIN programmes p ON s.id_programme = p.id_programme 
          WHERE s.id_utilisateur = :id 
          ORDER BY s.date_seance";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($seances as $seance) {
    // Determine color based on calories
    $color = '#10b981'; // green (default)
    if ($seance['calories'] > 300) $color = '#ef4444'; // red
    elseif ($seance['calories'] > 150) $color = '#f59e0b'; // orange
    elseif ($seance['calories'] > 50) $color = '#3b82f6'; // blue
    
    $title = "{$seance['duree']} min - {$seance['calories']} cal";
    if ($seance['programme_titre']) $title .= " ({$seance['programme_titre']})";
    
    $events[] = [
        'id' => $seance['id_seance'],
        'title' => $title,
        'start' => $seance['date_seance'],
        'color' => $color,
        'extendedProps' => [
            'duree' => $seance['duree'],
            'calories' => $seance['calories'],
            'notes' => $seance['notes'],
            'programme' => $seance['programme_titre']
        ]
    ];
}

// Get user's purchased programmes for linking to new sessions
$prog_sql = "SELECT DISTINCT p.id_programme, p.titre 
             FROM programmes p
             JOIN assignations a ON p.id_programme = a.id_programme
             WHERE a.id_utilisateur = :user
             ORDER BY p.titre";
$stmt_prog = $db->prepare($prog_sql);
$stmt_prog->bindParam(':user', $user_id);
$stmt_prog->execute();
$programmes = $stmt_prog->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Calendrier';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Calendrier des séances</h1>
    <p class="text-gray-600 mt-2">Visualisez votre historique d'entraînement. Cliquez sur une séance pour voir/modifier les détails.</p>
</div>

<div class="bg-white rounded-xl shadow-md p-6">
    <div id="calendar" class="h-[600px]"></div>
</div>

<!-- Modal for session details (view/edit/delete) -->
<div id="sessionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modal-title" class="text-lg font-bold">Détails de la séance</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div id="modal-content">
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<!-- Modal for adding a session -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Ajouter une séance</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="addSessionForm">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date *</label>
                <input type="date" name="date_seance" id="add_date" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Durée (minutes) *</label>
                <input type="number" name="duree" id="add_duree" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Calories *</label>
                <input type="number" name="calories" id="add_calories" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea name="notes" id="add_notes" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentEvent = null;

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
                currentEvent = info.event;
                showEventDetails(info.event);
            },
            dateClick: function(info) {
                // Allow adding session on date click
                document.getElementById('add_date').value = info.dateStr;
                openAddModal();
            }
        });
        calendar.render();
    });

    function showEventDetails(event) {
        const props = event.extendedProps;
        const date = new Date(event.start).toLocaleDateString('fr-FR');
        const html = `
            <div class="space-y-3">
                <p><strong>Date :</strong> ${date}</p>
                <p><strong>Durée :</strong> ${props.duree} minutes</p>
                <p><strong>Calories :</strong> ${props.calories} cal</p>
                <p><strong>Programme :</strong> ${props.programme || '—'}</p>
                <p><strong>Notes :</strong> ${props.notes ? props.notes : '—'}</p>
                <div class="flex space-x-2 pt-2">
                    <button onclick="openEditModal()" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i> Modifier
                    </button>
                    <button onclick="deleteSession()" class="flex-1 bg-red-600 text-white py-2 rounded hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i> Supprimer
                    </button>
                </div>
            </div>
        `;
        document.getElementById('modal-content').innerHTML = html;
        document.getElementById('sessionModal').classList.remove('hidden');
    }

    function openEditModal() {
        if (!currentEvent) return;
        const props = currentEvent.extendedProps;
        const date = new Date(currentEvent.start).toISOString().split('T')[0];
        const html = `
            <form id="editSessionForm">
                <input type="hidden" name="id_seance" value="${currentEvent.id}">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Date</label>
                    <input type="text" value="${date}" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Durée (minutes) *</label>
                    <input type="number" name="duree" id="edit_duree" value="${props.duree}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Calories *</label>
                    <input type="number" name="calories" id="edit_calories" value="${props.calories}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Notes</label>
                    <textarea name="notes" id="edit_notes" rows="2" class="w-full px-3 py-2 border rounded-lg">${props.notes || ''}</textarea>
                </div>
                <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
            </form>
        `;
        document.getElementById('modal-content').innerHTML = html;
        document.getElementById('modal-title').innerText = 'Modifier la séance';
        
        // Attach edit form handler
        $('#editSessionForm').submit(function(e) {
            e.preventDefault();
            const id_seance = currentEvent.id;
            const duree = $('#edit_duree').val();
            const calories = $('#edit_calories').val();
            const notes = $('#edit_notes').val();
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax_action: 'edit',
                    id_seance: id_seance,
                    duree: duree,
                    calories: calories,
                    notes: notes
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Erreur de connexion');
                }
            });
        });
    }

    function deleteSession() {
        if (!currentEvent) return;
        if (confirm('Supprimer définitivement cette séance ?')) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax_action: 'delete',
                    id_seance: currentEvent.id
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        }
    }

    function closeModal() {
        document.getElementById('sessionModal').classList.add('hidden');
        document.getElementById('modal-title').innerText = 'Détails de la séance';
    }

    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    $('#addSessionForm').submit(function(e) {
        e.preventDefault();
        const date = $('#add_date').val();
        const duree = $('#add_duree').val();
        const calories = $('#add_calories').val();
        const notes = $('#add_notes').val();
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                ajax_action: 'add',
                date_seance: date,
                duree: duree,
                calories: calories,
                notes: notes
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Erreur de connexion');
            }
        });
    });
</script>

<?php include '../include/footer.php'; ?>