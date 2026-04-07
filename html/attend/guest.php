<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$token       = $_POST['token'] ?? '';
$fullName    = trim($_POST['full_name'] ?? '');
$institution = trim($_POST['institution'] ?? '');
$title       = trim($_POST['title_field'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');

if (!$fullName) {
    header("Location: /attend/?token={$token}&error=name");
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT * FROM meetings WHERE qr_token = ? AND status != 'cancelled'"
);
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die('Geçersiz toplantý.');
}

$stmt2 = $db->prepare(
    "INSERT INTO attendees
     (meeting_id, attendee_type, full_name, email, phone,
      institution, title, ip_address)
     VALUES (?, 'guest', ?, ?, ?, ?, ?, ?)"
);
$stmt2->execute([
    $meeting['id'],
    $fullName,
    $email,
    $phone,
    $institution,
    $title,
    $_SERVER['REMOTE_ADDR'] ?? ''
]);

logAccess(
    'guest_attend',
    "Misafir katýldý: {$fullName} › Toplantý: {$meeting['title']}",
    'info'
);

$_SESSION['attend_success_name'] = $fullName;
header("Location: /attend/success.php?type=guest");
exit;