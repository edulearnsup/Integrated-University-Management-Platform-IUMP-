<?php
/**
 * پورتال رسمی دانشگاه و سامانه یکپارچه صمد (PHP 8 + MySQL)
 * صفحه اصلی و لندینگ پیج دوزبانه (فارسی RTL / انگلیسی LTR) با حفظ کامل احراز هویت JWT و دسترسی سریع به پنل‌ها
 */
session_start();
require_once 'db.php';
require_once 'jwt_auth.php';

if (isset($_GET['lang'])) {
    $requestedLang = strtolower(trim((string)$_GET['lang']));
    if ($requestedLang === 'en' || $requestedLang === 'fa') {
        $_SESSION['landing_lang'] = $requestedLang;
    }
}
$lang = ($_SESSION['landing_lang'] ?? 'fa') === 'en' ? 'en' : 'fa';

// آمار زنده دیتابیس
$stats = [
    'students_count' => 0,
    'rooms_count' => 0,
    'admitted_count' => 0,
    'festivals_count' => 0
];
try {
    $stats['students_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `students`")->fetchColumn();
    $stats['rooms_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `dorm_rooms`")->fetchColumn();
    $stats['admitted_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `admitted_students`")->fetchColumn();
    $stats['festivals_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `festival_calls`")->fetchColumn();
} catch (Exception $e) {}

// بررسی لاگین بودن کاربر بر اساس اعتبار توکن JWT
$loggedInUser = null;
$loggedRole = null;
$jwtCheck = samad_verify_jwt($pdo, null, false);
if ($jwtCheck['valid']) {
    $loggedRole = $jwtCheck['role'];
    $loggedInUser = $jwtCheck['name'] ?: ($lang === 'en' ? 'Authenticated User' : 'کاربر احراز هویت‌شده');
}

$portalUrl = 'login.php';
$portalLabelFa = 'پرتال دانشجویی';
$portalLabelEn = 'Student Portal';
if ($loggedInUser) {
    if ($loggedRole === 'manager') {
        $portalUrl = 'manager_panel.php';
        $portalLabelFa = 'پنل مدیر کل';
        $portalLabelEn = 'Manager Panel';
    } elseif ($loggedRole === 'supervisor') {
        $portalUrl = 'supervisor_panel.php';
        $portalLabelFa = 'پنل سرپرست';
        $portalLabelEn = 'Supervisor Panel';
    } else {
        $portalUrl = 'dashboard.php';
        $portalLabelFa = 'میزکار دانشجویی';
        $portalLabelEn = 'My Dashboard';
    }
}

if ($lang === 'en') {
    require __DIR__ . '/landing_en.php';
} else {
    require __DIR__ . '/landing_fa.php';
}
