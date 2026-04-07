<?php
require_once __DIR__ . '/../config/session.php';
session_unset();
session_destroy();
header('Location: /admin/login.php');
exit;
