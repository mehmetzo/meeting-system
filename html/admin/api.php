<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db     = getDB();

switch ($action) {
    case 'live_attendees':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare(
            "SELECT attendee_type as type, full_name as name,
                    DATE_FORMAT(attended_at,'%H:%i') as time
             FROM attendees WHERE meeting_id = ?
             ORDER BY attended_at DESC LIMIT 20"
        );
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'dashboard_stats':
        $stats = [
            'total'    => $db->query("SELECT COUNT(*) FROM meetings")->fetchColumn(),
            'active'   => $db->query("SELECT COUNT(*) FROM meetings WHERE status='active'")->fetchColumn(),
            'today'    => $db->query("SELECT COUNT(*) FROM attendees WHERE DATE(attended_at)=CURDATE()")->fetchColumn(),
        ];
        echo json_encode($stats);
        break;

    case 'toggle_meeting_status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            if (in_array($status, ['active', 'completed', 'cancelled'])) {
                $db->prepare("UPDATE meetings SET status=? WHERE id=?")
                   ->execute([$status, $id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Geçersiz durum']);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Geçersiz istek']);
}