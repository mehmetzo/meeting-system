<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$institutionName = getSetting('institution_name', 'T.C. Saglik Bakanligi');
$hospitalName    = getSetting('hospital_name', 'Dijital Toplanti Sistemi');
$logoPath        = getSetting('logo_path', '');
$primaryColor    = getSetting('primary_color', '#1a5276');

function adjustColor(string $hex, int $amount): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = max(0, min(255, hexdec(substr($hex,0,2)) + $amount));
    $g = max(0, min(255, hexdec(substr($hex,2,2)) + $amount));
    $b = max(0, min(255, hexdec(substr($hex,4,2)) + $amount));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Yonetim Paneli', ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>:root { --primary: <?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?>; --primary-dark: <?= adjustColor($primaryColor, -20) ?>; }</style>
    <?= $extraHead ?? '' ?>
</head>
<body>
