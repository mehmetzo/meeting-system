<?php
header('Content-Type: text/html; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ldap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$token    = $_POST['token']           ?? '';
$username = trim($_POST['username']   ?? '');
$password = $_POST['password']        ?? '';
$unit     = trim($_POST['unit']       ?? '');
$title    = trim($_POST['title_field'] ?? '');

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM meetings WHERE qr_token = ? AND status = 'active'");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    header("Location: /attend/?token={$token}&error=invalid");
    exit;
}

$ldap = new LdapAuth();

if (!$ldap->isEnabled()) {
    $_SESSION['attend_error'] = 'LDAP aktif degil';
    header("Location: /attend/?token={$token}&error=ldap");
    exit;
}

$result = $ldap->authenticate($username, $password);

error_log('[STAFF] user=' . $username . ' success=' . ($result['success'] ? 'true' : 'false') . ' error=' . ($result['error'] ?? ''));

if (!$result['success']) {
    $_SESSION['attend_error'] = $result['error'] ?? 'Kimlik dogrulama basarisiz';
    header("Location: /attend/?token={$token}&error=ldap");
    exit;
}

$ldapUser = $result['user'];

$check = $db->prepare(
    "SELECT id FROM attendees WHERE meeting_id = ? AND ldap_username = ? LIMIT 1"
);
$check->execute([$meeting['id'], $username]);

if ($check->fetch()) {
    $_SESSION['attend_success_name'] = $ldapUser['full_name'] ?: $username;
    header("Location: /attend/success.php?type=staff&already=1");
    exit;
}

$stmt2 = $db->prepare(
    "INSERT INTO attendees
     (meeting_id, attendee_type, full_name, tc_no, phone, email,
      institution, title, unit, ldap_username, ip_address)
     VALUES (?, 'staff', ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt2->execute([
    $meeting['id'],
    $ldapUser['full_name']  ?: $username,
    $ldapUser['tc_no']      ?? '',
    $ldapUser['phone']      ?? '',
    $ldapUser['email']      ?? '',
    getSetting('hospital_name', ''),
    $title ?: ($ldapUser['title']      ?? ''),
    $unit  ?: ($ldapUser['department'] ?? ''),
    $username,
    $_SERVER['REMOTE_ADDR'] ?? '',
]);

error_log('[STAFF] Kayit eklendi: ' . $username);

$_SESSION['attend_success_name'] = $ldapUser['full_name'] ?: $username;
header("Location: /attend/success.php?type=staff");
exit;