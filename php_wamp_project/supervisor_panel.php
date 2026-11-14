<?php
/**
 * پنل سرپرست شبانه خوابگاه صمد (PHP + MySQL)
 * وظایف سرپرست:
 * ۱. سرشماری شبانه دانشجویان و ثبت حضور یا غیبت
 * ۲. ارزیابی شب به شب کیفیت نظافت خوابگاه‌ها در ۴ سطح (عالی، خوب، متوسط، ضعیف)
 * ۳. مشاهده امکانات و تجهیزات ثبتی اتاق‌ها توسط مدیر و ارسال گزارش خرابی/تعمیر/تعویض
 */
session_start();
require_once 'db.php';
require_once 'jwt_auth.php';

$jwtAuth = samad_require_page_jwt($pdo, ['supervisor'], 'dorm:attendance:manage');

// تابع تبدیل تاریخ میلادی به هجری شمسی در PHP
function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100)
        + (int)(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * (int)($days / 12053);
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}

$months_fa = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

$msg = '';
$err = '';
$active_tab = $_GET['tab'] ?? 'attendance';
$current_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$selected_floor = isset($_GET['floor']) ? trim($_GET['floor']) : 'all';

// محاسبه تاریخ شمسی تاریخ جاری
$dateParts = explode('-', $current_date);
$gy = intval($dateParts[0] ?? 2026);
$gm = intval($dateParts[1] ?? 9);
$gd = intval($dateParts[2] ?? 30);
list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
$persian_date_text = "{$jd} " . ($months_fa[$jm - 1] ?? '') . " {$jy}";
$persian_date_short = sprintf("%04d/%02d/%02d", $jy, $jm, $jd);

// محاسبه روز قبل و روز بعد
$prev_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
$today_date = date('Y-m-d');

$supervisor_name = $jwtAuth['name'] ?: ($_SESSION['admin_name'] ?? 'مهندس کامران قنبری (سرپرست شبانه)');
$supervisor_nid  = $jwtAuth['sub'] ?: ($_SESSION['admin_nid'] ?? '0099887766');

// -----------------------------------------------------------------
// ۱. پردازش ثبت سرشماری شبانه
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_attendance'])) {
    if (!samad_has_permission($jwtAuth, 'dorm:attendance:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $supervisor_nid, 'supervisor', $jwtAuth['jti'], 'supervisor_panel.php', 403, 'مجوز dorm:attendance:manage غیرفعال است');
        $err = 'مجوز ثبت سرشماری شبانه برای توکن شما غیرفعال شده است (403).';
    } else {
        $attendance_date = $_POST['attendance_date'] ?? $current_date;
        $statuses = $_POST['status'] ?? [];
        $notes = $_POST['note'] ?? [];
        $room_ids = $_POST['room_id'] ?? [];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO `dorm_night_attendance` 
                    (`student_id`, `room_id`, `attendance_date`, `status`, `supervisor_national_id`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `status` = VALUES(`status`),
                    `notes` = VALUES(`notes`),
                    `supervisor_national_id` = VALUES(`supervisor_national_id`),
                    `created_at` = CURRENT_TIMESTAMP
            ");

            foreach ($statuses as $std_id => $st) {
                $r_id = intval($room_ids[$std_id] ?? 0);
                $nt   = trim($notes[$std_id] ?? '');
                $stmt->execute([$std_id, $r_id, $attendance_date, $st, $supervisor_nid, $nt]);
            }

            $pdo->commit();
            $msg = "سرشماری شبانه تاریخ {$persian_date_text} ({$attendance_date}) با موفقیت در پایگاه‌داده MySQL ثبت گردید.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در ثبت سرشماری: " . $e->getMessage();
        }
    }
}

// -----------------------------------------------------------------
// ۲. پردازش ثبت ارزیابی شبانه نظافت اتاق‌ها (۴ سطحی)
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_cleanliness'])) {
    if (!samad_has_permission($jwtAuth, 'dorm:cleanliness:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $supervisor_nid, 'supervisor', $jwtAuth['jti'], 'supervisor_panel.php', 403, 'مجوز dorm:cleanliness:manage غیرفعال است');
        $err = 'مجوز ثبت ارزیابی نظافت برای توکن شما غیرفعال شده است (403).';
    } else {
        $cleanliness_date = trim($_POST['cleanliness_date'] ?? $persian_date_short);
        $grades = $_POST['cleanliness_grade'] ?? [];
        $notes  = $_POST['cleanliness_notes'] ?? [];
        $room_numbers = $_POST['cleanliness_room_number'] ?? [];
        $floors = $_POST['cleanliness_floor'] ?? [];
        $recorded_at = $cleanliness_date . ' - ' . date('H:i');

        try {
            $pdo->beginTransaction();

            $stmtClean = $pdo->prepare("
                INSERT INTO `room_cleanliness_records`
                    (`room_id`, `room_number`, `floor`, `inspection_date`, `grade`, `supervisor_national_id`, `supervisor_name`, `notes`, `recorded_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $cnt = 0;
            foreach ($grades as $r_id => $grd) {
                $r_id = intval($r_id);
                $r_num = $room_numbers[$r_id] ?? '';
                $flr = $floors[$r_id] ?? 'طبقه اول';
                $nt = trim($notes[$r_id] ?? '');

                // حذف ارزیابی تکراری همان اتاق در همان تاریخ
                $delOld = $pdo->prepare("DELETE FROM `room_cleanliness_records` WHERE `room_id` = ? AND `inspection_date` = ?");
                $delOld->execute([$r_id, $cleanliness_date]);

                $stmtClean->execute([$r_id, $r_num, $flr, $cleanliness_date, $grd, $supervisor_nid, $supervisor_name, $nt, $recorded_at]);
                $cnt++;
            }

            $pdo->commit();
            $msg = "ارزیابی کیفی نظافت {$cnt} اتاق خوابگاه برای تاریخ {$cleanliness_date} با موفقیت در سیستم ثبت گردید.";
            $active_tab = 'cleanliness';
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در ثبت ارزیابی نظافت: " . $e->getMessage();
        }
    }
}

// -----------------------------------------------------------------
// ۳. پردازش ثبت گزارش خرابی و درخواست تعمیر/تعویض توسط سرپرست
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_report_maintenance'])) {
    if (!samad_has_permission($jwtAuth, 'dorm:maintenance:report')) {
        samad_log_auth_event($pdo, 'access_denied_403', $supervisor_nid, 'supervisor', $jwtAuth['jti'], 'supervisor_panel.php', 403, 'مجوز dorm:maintenance:report غیرفعال است');
        $err = 'مجوز ثبت گزارش خرابی و تعمیر برای توکن شما غیرفعال شده است (403).';
    } else {
        $r_id    = intval($_POST['room_id'] ?? 0);
        $eq_name = trim($_POST['equipment_name'] ?? '');
        $m_type  = trim($_POST['maintenance_type'] ?? 'تعمیر');
        $urgency = trim($_POST['urgency'] ?? 'عادی');
        $desc    = trim($_POST['description'] ?? '');

        if ($r_id > 0 && !empty($eq_name) && !empty($desc)) {
            try {
                $rStmt = $pdo->prepare("SELECT `room_number`, `floor` FROM `dorm_rooms` WHERE `id` = ?");
                $rStmt->execute([$r_id]);
                $rInfo = $rStmt->fetch();

                if ($rInfo) {
                    $reported_at = $persian_date_short . ' - ' . date('H:i');
                    $sup_code = 'SUP-405-01';

                    $insMaint = $pdo->prepare("
                        INSERT INTO `maintenance_reports`
                            (`room_id`, `room_number`, `floor`, `equipment_name`, `maintenance_type`, `urgency`, `status`, `description`, `reported_by_supervisor_id`, `reported_by_supervisor_name`, `reported_at`)
                        VALUES (?, ?, ?, ?, ?, ?, 'در انتظار اقدام', ?, ?, ?, ?)
                    ");
                    $insMaint->execute([
                        $r_id, $rInfo['room_number'], $rInfo['floor'], $eq_name, $m_type, $urgency,
                        $desc, $sup_code, $supervisor_name, $reported_at
                    ]);

                    $msg = "گزارش خرابی «{$eq_name}» در اتاق {$rInfo['room_number']} با موفقیت به کارتابل اطلاع‌رسانی مدیر دانشگاه ارسال گردید.";
                    $active_tab = 'facilities';
                } else {
                    $err = "اتاق مورد نظر یافت نشد.";
                }
            } catch (Exception $e) {
                $err = "خطا در ثبت گزارش خرابی: " . $e->getMessage();
            }
        } else {
            $err = "لطفاً تمامی موارد گزارش خرابی شامل نام تجهیز و شرح عیب را تکمیل نمایید.";
        }
    }
}

// -----------------------------------------------------------------
// واکشی داده‌های مورد نیاز برای تب‌ها
// -----------------------------------------------------------------

// ۱. لیست دانشجویان جهت سرشماری
$sqlStudents = "
    SELECT s.student_id, s.full_name, s.mobile, s.campus, s.field_of_study,
           r.id AS room_id, r.room_number, r.floor,
           att.status AS attendance_status, att.notes AS attendance_notes
    FROM `dorm_allocations` a
    JOIN `students` s ON a.student_id = s.student_id
    JOIN `dorm_rooms` r ON a.room_id = r.id
    LEFT JOIN `dorm_night_attendance` att ON s.student_id = att.student_id AND att.attendance_date = ?
    WHERE a.is_active = 1
";

$params = [$current_date];
if ($selected_floor !== 'all') {
    $sqlStudents .= " AND r.floor = ?";
    $params[] = $selected_floor;
}

$sqlStudents .= " ORDER BY FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC, s.full_name ASC";
$stmtStudents = $pdo->prepare($sqlStudents);
$stmtStudents->execute($params);
$dormStudents = $stmtStudents->fetchAll();

// آمار سرشماری تاریخ جاری
$totalStudents = count($dormStudents);
$presentCount = 0;
$absentCount = 0;
$leaveCount = 0;
foreach ($dormStudents as $ds) {
    $st = $ds['attendance_status'] ?? 'حاضر';
    if ($st === 'حاضر') $presentCount++;
    elseif ($st === 'غایب') $absentCount++;
    elseif ($st === 'مرخصی') $leaveCount++;
}

// ۲. واکشی اتاق‌ها و ارزیابی‌های نظافت
$allRoomsStmt = $pdo->query("SELECT * FROM `dorm_rooms` WHERE `is_active` = 1 ORDER BY FIELD(`floor`, 'همکف', 'طبقه اول', 'طبقه دوم'), `room_number` ASC");
$allActiveRooms = $allRoomsStmt->fetchAll();

// آخرین ارزیابی نظافت هر اتاق
$cleanlinessMap = [];
$cStmt = $pdo->prepare("SELECT * FROM `room_cleanliness_records` WHERE `inspection_date` = ?");
$cStmt->execute([$persian_date_short]);
foreach ($cStmt->fetchAll() as $cr) {
    $cleanlinessMap[$cr['room_id']] = $cr;
}

// کل سوابق نظافت اخیر
$recentCleanlinessRecords = $pdo->query("SELECT * FROM `room_cleanliness_records` ORDER BY `id` DESC LIMIT 20")->fetchAll();

// ۳. واکشی اتاق‌ها و امکانات ثبت‌شده توسط مدیر
$roomsWithFacilities = $pdo->query("
    SELECT r.*, f.refrigerator, f.wardrobe, f.carpet, f.secondary_facilities, f.status AS facility_status, f.updated_at AS facility_updated
    FROM `dorm_rooms` r
    LEFT JOIN `room_facilities` f ON r.id = f.room_id
    ORDER BY FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC
")->fetchAll();

// گزارش‌های خرابی و تعمیرات ثبت‌شده
$myMaintenanceReports = $pdo->query("SELECT * FROM `maintenance_reports` ORDER BY `id` DESC LIMIT 30")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل سرپرست شبانه خوابگاه صمد - سرشماری، نظافت و امکانات</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="assets/tailwind/tailwind.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .print-sheet-page { border: none !important; box-shadow: none !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 pb-16">
    <!-- نوار میانبر تعاملی برای سوئیچ سریع بین سیستم‌ها (جهت سهولت کاربری و تست) -->
    <div class="max-w-7xl mx-auto my-3 px-4 sm:px-6 no-print">
        <div class="bg-slate-900 text-white px-4 py-2.5 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs shadow-md">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="font-bold">پنل سرپرستی شبانه صمد - دانشگاه ملی مهارت</span>
                <span class="text-slate-400">|</span>
                <span class="text-amber-300 font-mono"><?= htmlspecialchars($persian_date_text) ?></span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] text-slate-400">جابجایی بین نقش‌ها:</span>
                <a href="manager_panel.php" class="px-2.5 py-1 bg-indigo-700 hover:bg-indigo-600 text-white rounded-lg font-bold transition-all flex items-center gap-1">
                    <span>🏛️</span>
                    <span>پنل مدیر کل</span>
                </a>
                <a href="dashboard.php" class="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-600 text-white rounded-lg font-bold transition-all flex items-center gap-1">
                    <span>🎓</span>
                    <span>میزکار دانشجو</span>
                </a>
                <a href="index.php" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all flex items-center gap-1">
                    <span>🌐</span>
                    <span>صفحه اصلی پرتال</span>
                </a>
            </div>
        </div>
    </div>
    <!-- هدر بالای پنل سرپرست -->
    <header class="bg-indigo-950 text-white shadow-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg shadow-sm">صمد</div>
                <div>
                    <h1 class="text-sm font-bold text-white">پنل سرپرست شبانه خوابگاه صمد</h1>
                    <p class="text-[11px] text-indigo-300">سرشماری شبانه، ارزیابی نظافت و گزارش خرابی و تعمیرات خوابگاه</p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <span class="px-2.5 py-1 bg-indigo-900/80 text-indigo-200 border border-indigo-700/70 rounded-lg text-[11px] font-mono tabular-nums hidden md:inline" title="توکن JWT فعال">
                    JWT: <?= htmlspecialchars(substr($jwtAuth['jti'], 0, 8)) ?> · <?= htmlspecialchars(samad_format_remaining_ttl((int)$jwtAuth['exp'])) ?>
                </span>
                <button type="button" onclick="document.getElementById('supervisorCardModal').classList.remove('hidden')" class="px-3 py-1.5 bg-amber-400 hover:bg-amber-500 text-slate-950 text-xs font-bold rounded-lg shadow-sm transition-colors cursor-pointer">
                    کارت سرپرستی و شیفت 🪪
                </button>
                <span class="text-xs text-indigo-200 hidden sm:inline"><?= htmlspecialchars($supervisor_name) ?></span>
                <a href="logout.php" class="px-3 py-1.5 bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-semibold rounded-lg transition-colors">خروج</a>
            </div>
        </div>

        <!-- تب‌های پیمایش اصلی سرپرست -->
        <div class="bg-indigo-900/90 border-t border-indigo-800/80 px-4 sm:px-6">
            <div class="max-w-7xl mx-auto flex items-center gap-1 sm:gap-2 overflow-x-auto py-2">
                <a href="?tab=attendance&date=<?= urlencode($current_date) ?>&floor=<?= urlencode($selected_floor) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $active_tab === 'attendance' ? 'bg-white text-indigo-950 shadow-md' : 'text-indigo-200 hover:text-white hover:bg-indigo-800/60' ?>">
                    <span>📋</span>
                    <span>سرشماری شبانه دانشجویان</span>
                </a>
                <a href="?tab=cleanliness&date=<?= urlencode($current_date) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $active_tab === 'cleanliness' ? 'bg-white text-indigo-950 shadow-md' : 'text-indigo-200 hover:text-white hover:bg-indigo-800/60' ?>">
                    <span>✨</span>
                    <span>نظافت شبانه خوابگاه‌ها (ارزیابی ۴ سطحی)</span>
                </a>
                <a href="?tab=facilities" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $active_tab === 'facilities' ? 'bg-white text-indigo-950 shadow-md' : 'text-indigo-200 hover:text-white hover:bg-indigo-800/60' ?>">
                    <span>🛠</span>
                    <span>امکانات اتاق‌ها و گزارش خرابی/تعمیر</span>
                    <?php 
                        $pendingMaint = count(array_filter($myMaintenanceReports, fn($m) => $m['status'] === 'در انتظار اقدام' || $m['status'] === 'در حال تعمیر'));
                        if ($pendingMaint > 0):
                    ?>
                        <span class="px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-mono"><?= $pendingMaint ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-6 space-y-6">
        <?php if (!empty($msg)): ?>
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold flex items-center gap-2 shadow-xs">
                <span>✓</span>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($err)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-bold flex items-center gap-2 shadow-xs">
                <span>✕</span>
                <span><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- تب ۱: سرشماری شبانه دانشجویان -->
        <!-- ============================================================== -->
        <?php if ($active_tab === 'attendance'): ?>
            <!-- نوار تقویم شمسی، فیلتر طبقه و آمار بلادرنگ -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <form method="GET" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="tab" value="attendance">
                    <div class="bg-indigo-50/80 p-2.5 rounded-xl border border-indigo-100 flex items-center gap-2.5">
                        <div>
                            <span class="text-[10px] font-bold text-indigo-900 block">تقویم سرشماری شبانه (شمسی):</span>
                            <span class="text-xs font-bold text-indigo-950 font-mono"><?= $persian_date_text ?> (<?= $persian_date_short ?>)</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                        <a href="?tab=attendance&date=<?= $prev_date ?>&floor=<?= urlencode($selected_floor) ?>" class="px-2.5 py-1 text-slate-700 hover:bg-white rounded-lg transition-colors">
                            ← روز قبل
                        </a>
                        <a href="?tab=attendance&date=<?= $today_date ?>&floor=<?= urlencode($selected_floor) ?>" class="px-2.5 py-1 <?= $current_date === $today_date ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600' ?> rounded-lg">
                            امروز
                        </a>
                        <a href="?tab=attendance&date=<?= $next_date ?>&floor=<?= urlencode($selected_floor) ?>" class="px-2.5 py-1 text-slate-700 hover:bg-white rounded-lg transition-colors">
                            روز بعد →
                        </a>
                    </div>

                    <div>
                        <input type="date" name="date" value="<?= htmlspecialchars($current_date) ?>" class="px-2.5 py-1.5 text-xs border rounded-xl bg-slate-50 font-mono" onchange="this.form.submit()">
                    </div>

                    <div>
                        <select name="floor" class="px-3 py-1.5 text-xs border rounded-xl bg-slate-50 font-bold" onchange="this.form.submit()">
                            <option value="all" <?= $selected_floor === 'all' ? 'selected' : '' ?>>همه طبقات خوابگاه</option>
                            <option value="همکف" <?= $selected_floor === 'همکف' ? 'selected' : '' ?>>طبقه همکف</option>
                            <option value="طبقه اول" <?= $selected_floor === 'طبقه اول' ? 'selected' : '' ?>>طبقه اول</option>
                            <option value="طبقه دوم" <?= $selected_floor === 'طبقه دوم' ? 'selected' : '' ?>>طبقه دوم</option>
                        </select>
                    </div>
                </form>

                <!-- آمار سریع سرشماری امشب -->
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-3 py-1.5 bg-indigo-50 text-indigo-800 rounded-xl font-bold border border-indigo-200">
                        کل: <span id="cnt-total"><?= $totalStudents ?></span> دانشجو
                    </span>
                    <span class="px-3 py-1.5 bg-emerald-50 text-emerald-800 rounded-xl font-bold border border-emerald-200">
                        حاضر: <span id="cnt-present"><?= $presentCount ?></span>
                    </span>
                    <span class="px-3 py-1.5 bg-rose-50 text-rose-800 rounded-xl font-bold border border-rose-200">
                        غایب: <span id="cnt-absent"><?= $absentCount ?></span>
                    </span>
                    <span class="px-3 py-1.5 bg-amber-50 text-amber-800 rounded-xl font-bold border border-amber-200">
                        مرخصی: <span id="cnt-leave"><?= $leaveCount ?></span>
                    </span>
                </div>
            </div>

            <!-- جدول سرشماری و فرم ارسال -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="border-b pb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">لیست سرشماری شبانه دانشجویان در خوابگاه</h2>
                        <p class="text-xs text-slate-500 mt-0.5">تاریخ: <?= $persian_date_text ?> | ثبت اختصاصی توسط سرپرست خوابگاه</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" onclick="markAllVisible('حاضر')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center gap-1">
                            <span>✓</span>
                            <span>حاضر زدن همه</span>
                        </button>
                        <button type="button" onclick="markAllVisible('غایب')" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1">
                            <span>✕</span>
                            <span>غایب زدن همه</span>
                        </button>
                        <button type="button" onclick="openAttendancePrintModal()" class="px-3 py-1.5 bg-indigo-700 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center gap-1.5">
                            <span>🖨</span>
                            <span>چاپ صورت‌جلسه سرشماری (A4)</span>
                        </button>
                    </div>
                </div>

                <!-- نوار جستجوی زنده سرشماری -->
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <input type="text" id="rollCallSearchInput" onkeyup="filterRollCallLive(this.value)" placeholder="جستجوی زنده نام، اتاق یا شماره دانشجویی..." 
                            class="w-full px-3 py-2 pr-9 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute right-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>
                    <span class="text-[11px] text-slate-500 hidden sm:inline">با تایپ شماره اتاق یا نام، ردیف‌ها بی‌درنگ فیلتر می‌شوند.</span>
                </div>

                <form method="POST" id="attendanceForm">
                    <input type="hidden" name="action_save_attendance" value="1">
                    <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($current_date) ?>">

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 border-b">
                                    <th class="p-3">ردیف</th>
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">نام و شماره دانشجویی</th>
                                    <th class="p-3">رشته و پردیس</th>
                                    <th class="p-3">وضعیت حضور امشب</th>
                                    <th class="p-3">توضیحات و گزارش سرپرست</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php if (empty($dormStudents)): ?>
                                    <tr>
                                        <td colspan="6" class="p-6 text-center text-slate-500 text-xs">
                                            هیچ دانشجویی با فیلتر انتخابی یافت نشد.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dormStudents as $idx => $std): ?>
                                        <?php 
                                            $curStatus = $std['attendance_status'] ?? 'حاضر';
                                        ?>
                                        <tr class="roll-call-row hover:bg-slate-50 transition-colors" data-search="<?= htmlspecialchars(strtolower($std['full_name'] . ' ' . $std['student_id'] . ' ' . $std['room_number'] . ' ' . $std['floor'])) ?>">
                                            <td class="p-3 font-mono text-slate-500"><?= $idx + 1 ?></td>
                                            <td class="p-3">
                                                <strong class="font-mono text-indigo-700 text-sm">اتاق <?= htmlspecialchars($std['room_number']) ?></strong>
                                                <span class="block text-[11px] text-slate-500"><?= htmlspecialchars($std['floor']) ?></span>
                                                <input type="hidden" name="room_id[<?= $std['student_id'] ?>]" value="<?= $std['room_id'] ?>">
                                            </td>
                                            <td class="p-3">
                                                <strong class="text-slate-900 block"><?= htmlspecialchars($std['full_name']) ?></strong>
                                                <span class="font-mono text-slate-500 text-[11px]">ش.د: <?= htmlspecialchars($std['student_id']) ?></span>
                                            </td>
                                            <td class="p-3 text-slate-600">
                                                <div><?= htmlspecialchars($std['field_of_study']) ?></div>
                                                <span class="text-[10px] text-indigo-600"><?= htmlspecialchars($std['campus']) ?></span>
                                            </td>
                                            <td class="p-3">
                                                <div class="flex items-center gap-1.5 attendance-group" data-student="<?= $std['student_id'] ?>">
                                                    <label class="attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all <?= $curStatus === 'حاضر' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-200' ?>" data-val="حاضر">
                                                        <input type="radio" name="status[<?= $std['student_id'] ?>]" value="حاضر" class="hidden" <?= $curStatus === 'حاضر' ? 'checked' : '' ?>>
                                                        <span>حاضر ✓</span>
                                                    </label>
                                                    <label class="attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all <?= $curStatus === 'غایب' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-200' ?>" data-val="غایب">
                                                        <input type="radio" name="status[<?= $std['student_id'] ?>]" value="غایب" class="hidden" <?= $curStatus === 'غایب' ? 'checked' : '' ?>>
                                                        <span>غایب ✕</span>
                                                    </label>
                                                    <label class="attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all <?= $curStatus === 'مرخصی' ? 'bg-amber-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-200' ?>" data-val="مرخصی">
                                                        <input type="radio" name="status[<?= $std['student_id'] ?>]" value="مرخصی" class="hidden" <?= $curStatus === 'مرخصی' ? 'checked' : '' ?>>
                                                        <span>مرخصی ⏱</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="p-3">
                                                <input type="text" name="note[<?= $std['student_id'] ?>]" value="<?= htmlspecialchars($std['attendance_notes'] ?? '') ?>" placeholder="توضیح در صورت تاخیر یا غیبت..." class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs bg-slate-50 focus:bg-white focus:border-indigo-500 outline-none">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-2">
                            <span>ذخیره نهایی سرشماری شبانه در دیتابیس MySQL ✓</span>
                        </button>
                    </div>
                </form>
            </div>

        <!-- ============================================================== -->
        <!-- تب ۲: نظافت خوابگاه‌ها و ارزیابی ۴ سطحی کیفیت -->
        <!-- ============================================================== -->
        <?php elseif ($active_tab === 'cleanliness'): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>✨</span>
                            <span>سرشماری شبانه کیفیت نظافت خوابگاه‌ها (۴ سطح استاندارد)</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            ارزیابی شب به شب نظافت تک‌تک اتاق‌ها توسط سرپرست شیفت شب در سطوح: <strong>عالی</strong>، <strong>خوب</strong>، <strong>متوسط</strong>، و <strong>ضعیف</strong> جهت ثبت در کارنامه عملکرد
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs bg-indigo-50 text-indigo-900 px-3 py-1.5 rounded-xl border border-indigo-200 font-mono font-bold">
                            تاریخ: <?= $persian_date_short ?>
                        </span>
                        <button type="button" onclick="batchSetCleanliness('عالی')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all cursor-pointer">
                            همه عالی (۴⭐️)
                        </button>
                        <button type="button" onclick="batchSetCleanliness('خوب')" class="px-2.5 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all cursor-pointer">
                            همه خوب (۳⭐️)
                        </button>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action_save_cleanliness" value="1">
                    <input type="hidden" name="cleanliness_date" value="<?= htmlspecialchars($persian_date_short) ?>">

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 border-b">
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">ظرفیت تخت</th>
                                    <th class="p-3">کیفیت نظافت شبانه (انتخاب سطح)</th>
                                    <th class="p-3">توضیحات و تذکرات سرپرست</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($allActiveRooms as $rm): ?>
                                    <?php 
                                        $existing = $cleanlinessMap[$rm['id']] ?? null;
                                        $curGrade = $existing['grade'] ?? 'خوب';
                                        $curNotes = $existing['notes'] ?? '';
                                    ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3">
                                            <strong class="font-mono text-indigo-700 text-sm">اتاق <?= htmlspecialchars($rm['room_number']) ?></strong>
                                            <span class="block text-[11px] text-slate-500"><?= htmlspecialchars($rm['floor']) ?></span>
                                            <input type="hidden" name="cleanliness_room_number[<?= $rm['id'] ?>]" value="<?= htmlspecialchars($rm['room_number']) ?>">
                                            <input type="hidden" name="cleanliness_floor[<?= $rm['id'] ?>]" value="<?= htmlspecialchars($rm['floor']) ?>">
                                        </td>
                                        <td class="p-3 text-slate-600 font-mono">
                                            <?= htmlspecialchars($rm['capacity']) ?> نفره
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <label class="flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors <?= $curGrade === 'عالی' ? 'bg-emerald-600 text-white font-bold border-emerald-600' : 'bg-white text-slate-700 hover:bg-slate-100' ?>">
                                                    <input type="radio" name="cleanliness_grade[<?= $rm['id'] ?>]" value="عالی" <?= $curGrade === 'عالی' ? 'checked' : '' ?> class="accent-emerald-600">
                                                    <span>عالی (⭐️⭐️⭐️⭐️)</span>
                                                </label>
                                                <label class="flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors <?= $curGrade === 'خوب' ? 'bg-cyan-600 text-white font-bold border-cyan-600' : 'bg-white text-slate-700 hover:bg-slate-100' ?>">
                                                    <input type="radio" name="cleanliness_grade[<?= $rm['id'] ?>]" value="خوب" <?= $curGrade === 'خوب' ? 'checked' : '' ?> class="accent-cyan-600">
                                                    <span>خوب (⭐️⭐️⭐️)</span>
                                                </label>
                                                <label class="flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors <?= $curGrade === 'متوسط' ? 'bg-amber-600 text-white font-bold border-amber-600' : 'bg-white text-slate-700 hover:bg-slate-100' ?>">
                                                    <input type="radio" name="cleanliness_grade[<?= $rm['id'] ?>]" value="متوسط" <?= $curGrade === 'متوسط' ? 'checked' : '' ?> class="accent-amber-600">
                                                    <span>متوسط (⭐️⭐️)</span>
                                                </label>
                                                <label class="flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors <?= $curGrade === 'ضعیف' ? 'bg-rose-600 text-white font-bold border-rose-600' : 'bg-white text-slate-700 hover:bg-slate-100' ?>">
                                                    <input type="radio" name="cleanliness_grade[<?= $rm['id'] ?>]" value="ضعیف" <?= $curGrade === 'ضعیف' ? 'checked' : '' ?> class="accent-rose-600">
                                                    <span>ضعیف (⚠️ اخطار)</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <input type="text" name="cleanliness_notes[<?= $rm['id'] ?>]" value="<?= htmlspecialchars($curNotes) ?>" placeholder="یادداشت نظافت یا تذکر سرپرست..." class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs bg-slate-50 focus:bg-white focus:border-indigo-500 outline-none">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t flex justify-between items-center">
                        <span class="text-xs text-slate-500">
                            ثبت‌کننده: <strong><?= htmlspecialchars($supervisor_name) ?></strong> (شناسه: <?= htmlspecialchars($supervisor_nid) ?>)
                        </span>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                            ثبت نمرات نظافت شبانه در دیتابیس ✓
                        </button>
                    </div>
                </form>
            </div>

            <!-- تاریخچه آخرین ارزیابی‌های ثبت‌شده -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-slate-800">آخرین ارزیابی‌های ثبت‌شده در دیتابیس نظافت:</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 border-b">
                                <th class="p-2.5">تاریخ</th>
                                <th class="p-2.5">اتاق و طبقه</th>
                                <th class="p-2.5">سطح نظافت</th>
                                <th class="p-2.5">توضیحات سرپرست</th>
                                <th class="p-2.5">سرپرست ارزیاب</th>
                                <th class="p-2.5">زمان ثبت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($recentCleanlinessRecords as $rec): ?>
                                <tr>
                                    <td class="p-2.5 font-mono"><?= htmlspecialchars($rec['inspection_date']) ?></td>
                                    <td class="p-2.5 font-bold font-mono">اتاق <?= htmlspecialchars($rec['room_number']) ?> (<?= htmlspecialchars($rec['floor']) ?>)</td>
                                    <td class="p-2.5">
                                        <?php if ($rec['grade'] === 'عالی'): ?>
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold">عالی ⭐️⭐️⭐️⭐️</span>
                                        <?php elseif ($rec['grade'] === 'خوب'): ?>
                                            <span class="px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded font-bold">خوب ⭐️⭐️⭐️</span>
                                        <?php elseif ($rec['grade'] === 'متوسط'): ?>
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold">متوسط ⭐️⭐️</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold">ضعیف ⚠️ اخطار</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2.5 text-slate-600"><?= htmlspecialchars($rec['notes'] ?? '-') ?></td>
                                    <td class="p-2.5 text-slate-500"><?= htmlspecialchars($rec['supervisor_name']) ?></td>
                                    <td class="p-2.5 text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($rec['recorded_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ============================================================== -->
        <!-- تب ۳: امکانات اتاق‌ها و ارسال گزارش خرابی/تعمیر به مدیر -->
        <!-- ============================================================== -->
        <?php elseif ($active_tab === 'facilities'): ?>
            <div class="space-y-6">
                <!-- هدر و دکمه گزارش جدید -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>🛠</span>
                            <span>داشبورد امکانات خوابگاه‌ها و کارتابل گزارش خرابی / تعویض به مدیر</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            سرپرست می‌تواند امکاناتی که مدیر ثبت کرده ببیند و در صورت نیاز به تعمیر یا تعویض هر قطعه (یا تخت)، فوراً گزارش کند تا مدیر اقدام و هزینه/فاکتور را ثبت نماید.
                        </p>
                    </div>

                    <button type="button" onclick="openReportModal()" class="px-4 py-2.5 bg-gradient-to-r from-rose-600 to-amber-600 hover:from-rose-500 hover:to-amber-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer shrink-0">
                        <span>🚨</span>
                        <span>ارسال گزارش خرابی یا نقص تجهیز +</span>
                    </button>
                </div>

                <!-- کارت‌های امکانات ثبت‌شده اتاق‌ها توسط مدیر -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($roomsWithFacilities as $rf): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3.5 hover:border-indigo-300 transition-colors">
                            <div class="flex items-center justify-between border-b pb-3">
                                <div>
                                    <span class="font-bold text-sm font-mono text-indigo-700">اتاق <?= htmlspecialchars($rf['room_number']) ?></span>
                                    <span class="text-xs text-slate-500 block"><?= htmlspecialchars($rf['floor']) ?></span>
                                </div>
                                <div class="text-left">
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold font-mono">
                                        <?= htmlspecialchars($rf['capacity']) ?> تخت خواب
                                    </span>
                                    <?php if ($rf['facility_status'] === 'تکمیل‌شده'): ?>
                                        <span class="block text-[10px] text-emerald-600 font-bold mt-0.5">تکمیل اطلاعات شده ✓</span>
                                    <?php else: ?>
                                        <span class="block text-[10px] text-amber-600 font-bold mt-0.5">در انتظار تکمیل ⚠️</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- امکانات اصلی -->
                            <div class="space-y-1.5 text-xs">
                                <span class="text-[10px] text-slate-400 font-bold block">امکانات اصلی و الزامات خوابگاه:</span>
                                <div class="p-2.5 bg-slate-50 rounded-xl space-y-1 border border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span>❄️ یخچال:</span>
                                        <strong class="text-slate-800"><?= htmlspecialchars($rf['refrigerator'] ?? 'یخچال استاندارد') ?></strong>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>🚪 کمد:</span>
                                        <strong class="text-slate-800"><?= htmlspecialchars($rf['wardrobe'] ?? 'کمد اختصاصی') ?></strong>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>🧶 فرش:</span>
                                        <strong class="text-slate-800"><?= htmlspecialchars($rf['carpet'] ?? 'فرش ۶ متری') ?></strong>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>🛏 تخت‌ها:</span>
                                        <strong class="text-slate-800 font-mono"><?= htmlspecialchars($rf['capacity']) ?> تخت اختصاصی</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- امکانات فرعی -->
                            <?php 
                                $secs = !empty($rf['secondary_facilities']) ? json_decode($rf['secondary_facilities'], true) : [];
                            ?>
                            <?php if (!empty($secs) && is_array($secs)): ?>
                                <div class="space-y-1 text-xs">
                                    <span class="text-[10px] text-slate-400 font-bold block">امکانات فرعی و تکمیلی:</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($secs as $sc): ?>
                                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-lg text-[11px]">
                                                <?= htmlspecialchars($sc['name'] ?? '') ?> (<?= htmlspecialchars($sc['count'] ?? 1) ?> عدد)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- دکمه گزارش خرابی اختصاصی این اتاق -->
                            <div class="pt-2 border-t">
                                <button type="button" onclick="openReportModal(<?= $rf['id'] ?>, '<?= htmlspecialchars($rf['room_number']) ?>', '<?= htmlspecialchars($rf['floor']) ?>')" class="w-full py-2 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>⚠️</span>
                                    <span>گزارش خرابی یا نیاز به تعمیر در این اتاق</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- کارتابل گزارش‌های ثبت‌شده سرپرست و وضعیت اقدام مدیر -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>📋</span>
                            <span>لیست گزارش‌های خرابی ثبت‌شده و وضعیت رفع اشکال توسط مدیر دانشگاه</span>
                        </h3>
                        <span class="text-xs text-slate-500 font-mono"><?= count($myMaintenanceReports) ?> گزارش</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 border-b">
                                    <th class="p-3">اتاق و تجهیز</th>
                                    <th class="p-3">نوع نیاز / فوریت</th>
                                    <th class="p-3">شرح خرابی</th>
                                    <th class="p-3">زمان گزارش سرپرست</th>
                                    <th class="p-3">وضعیت اقدام مدیر</th>
                                    <th class="p-3">مشخصات رفع عیب و فاکتور</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php if (empty($myMaintenanceReports)): ?>
                                    <tr>
                                        <td colspan="6" class="p-6 text-center text-slate-500">هیچ گزارشی ثبت نشده است.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($myMaintenanceReports as $rep): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3">
                                                <strong class="font-mono text-indigo-700">اتاق <?= htmlspecialchars($rep['room_number']) ?></strong>
                                                <span class="text-slate-500 block text-[11px]"><?= htmlspecialchars($rep['floor']) ?></span>
                                                <strong class="text-slate-900 text-xs mt-0.5 block"><?= htmlspecialchars($rep['equipment_name']) ?></strong>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px] block font-bold"><?= htmlspecialchars($rep['maintenance_type']) ?></span>
                                                <span class="text-[10px] mt-0.5 block <?= $rep['urgency'] === 'فوری' ? 'text-rose-600 font-bold' : 'text-slate-500' ?>">
                                                    فوریت: <?= htmlspecialchars($rep['urgency']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-slate-700 max-w-xs">
                                                <?= htmlspecialchars($rep['description']) ?>
                                            </td>
                                            <td class="p-3 font-mono text-slate-500 text-[11px]">
                                                <?= htmlspecialchars($rep['reported_at']) ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (str_contains($rep['status'], 'رفع‌شده')): ?>
                                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold inline-block">
                                                        ✓ <?= htmlspecialchars($rep['status']) ?>
                                                    </span>
                                                <?php elseif ($rep['status'] === 'در حال تعمیر'): ?>
                                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold inline-block">
                                                        ⏳ در حال تعمیر
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 bg-rose-100 text-rose-800 border border-rose-300 rounded-lg text-xs font-bold inline-block">
                                                        ⚠️ در انتظار اقدام مدیر
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (str_contains($rep['status'], 'رفع‌شده')): ?>
                                                    <div class="space-y-0.5 text-[11px] text-slate-600">
                                                        <div>زمان رفع: <strong class="font-mono text-slate-800"><?= htmlspecialchars($rep['resolved_at'] ?? '-') ?></strong></div>
                                                        <div>مدیر: <strong><?= htmlspecialchars($rep['resolved_by_manager_name'] ?? 'مدیر امور رفاهی') ?></strong></div>
                                                        <?php if (!empty($rep['invoice_number'])): ?>
                                                            <div>فاکتور: <strong class="font-mono text-indigo-700"><?= htmlspecialchars($rep['invoice_number']) ?></strong></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($rep['resolution_cost'])): ?>
                                                            <div>هزینه: <strong class="font-mono text-emerald-700"><?= toPersianDigits(number_format($rep['resolution_cost'])) ?> ریال</strong></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-400 text-[11px]">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================== -->
    <!-- مودال ثبت گزارش خرابی و نقص امکانات/تخت توسط سرپرست -->
    <!-- ============================================================== -->
    <div id="reportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-200 max-w-lg w-full relative">
            <div class="flex items-center justify-between pb-3 border-b">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span>🚨</span>
                    <span>ثبت و ارسال گزارش نقص / خرابی تجهیزات به مدیر</span>
                </h3>
                <button type="button" onclick="closeReportModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" class="mt-4 space-y-3.5 text-xs">
                <input type="hidden" name="action_report_maintenance" value="1">

                <div>
                    <label class="block text-slate-700 font-bold mb-1">اتاق مربوطه <span class="text-rose-500">*</span></label>
                    <select name="room_id" id="modal_room_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-slate-50 focus:bg-white text-xs font-bold">
                        <option value="">-- انتخاب اتاق --</option>
                        <?php foreach ($allActiveRooms as $rm): ?>
                            <option value="<?= $rm['id'] ?>">اتاق <?= htmlspecialchars($rm['room_number']) ?> (<?= htmlspecialchars($rm['floor']) ?> - <?= $rm['capacity'] ?> نفره)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">نام تجهیز یا قطعه نیازمند اقدام <span class="text-rose-500">*</span></label>
                    <input type="text" name="equipment_name" id="modal_equipment_name" required placeholder="مثال: یخچال، کمد، تخت، ترموستات شوفاژ، تلویزیون، اسپلیت..." class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-slate-50 focus:bg-white text-xs">
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <button type="button" onclick="setEquipName('ترموستات و رله یخچال')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">🧊 یخچال</button>
                        <button type="button" onclick="setEquipName('شیر رادیاتور شوفاژ مرکزی')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">♨️ شوفاژ</button>
                        <button type="button" onclick="setEquipName('پایه و جوش نردبان تخت فلزی')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">🛏️ تخت فلزی</button>
                        <button type="button" onclick="setEquipName('قفل و لولای درب کمد دیواری')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">🚪 کمد</button>
                        <button type="button" onclick="setEquipName('اسپلیت سرمایشی و فن')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">❄️ اسپلیت</button>
                        <button type="button" onclick="setEquipName('شیرآلات و لوله‌کشی روشویی')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 text-[10px] font-medium border border-slate-200 transition-colors cursor-pointer">🚰 تاسیسات</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">نوع اقدام مورد نیاز</label>
                        <select name="maintenance_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-slate-50 text-xs">
                            <option value="تعمیر">تعمیر قطعه</option>
                            <option value="تعویض / خرید جدید">تعویض / خرید و جایگزینی تجهیز نو</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">درجه فوریت</label>
                        <select name="urgency" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-slate-50 text-xs font-bold">
                            <option value="عادی">عادی</option>
                            <option value="فوری" class="text-rose-600 font-bold">فوری و اضطراری ⚠️</option>
                            <option value="کم‌اهمیت">کم‌اهمیت</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">شرح دقیق خرابی و مشاهدات سرپرست <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="3" required placeholder="علت خرابی، نشانه‌ها یا خطرات احتمالی را شرح دهید..." class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-slate-50 focus:bg-white text-xs"></textarea>
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900">
                    ℹ️ این گزارش بلافاصله در کارتابل اطلاع‌رسانی پنل مدیر دانشگاه قرار می‌گیرد و پس از رفع آن، شماره فاکتور و صورت مخارج توسط مدیر ثبت خواهد شد.
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="closeReportModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold cursor-pointer">انصراف</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md cursor-pointer">ارسال گزارش به کارتابل مدیر ✓</button>
                </div>
            </form>
        </div>
    </div>

    <!-- مودال کارت رسمی شناسایی سرپرست خوابگاه -->
    <div id="supervisorCardModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-indigo-500/40 max-w-xl w-full relative">
            <div class="flex items-center justify-between pb-4 border-b border-indigo-700/60">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center font-bold text-white shadow-md border border-indigo-400/40">
                        صمد
                    </div>
                    <div>
                        <div class="text-[11px] text-indigo-300 font-medium">پردیس فناوری و مهارت - امور دانشجویی</div>
                        <h2 class="text-sm sm:text-base font-extrabold text-white">کارت رسمی شناسایی سرپرست خوابگاه</h2>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded-full text-[10px] font-bold">
                    سرپرست فعال ✓
                </span>
            </div>

            <div class="mt-5 flex flex-col sm:flex-row gap-5 items-center sm:items-start">
                <div class="w-28 h-36 rounded-2xl bg-slate-800 border-2 border-indigo-400/40 overflow-hidden relative shadow-lg shrink-0 flex flex-col items-center justify-center">
                    <div class="text-3xl mb-1">👨‍💼</div>
                    <span class="text-[10px] text-indigo-200">سرپرست شبانه</span>
                    <div class="absolute bottom-0 inset-x-0 bg-slate-950/80 py-1 text-center text-[10px] text-amber-300 font-bold">
                        سرپرست رسمی
                    </div>
                </div>

                <div class="flex-1 w-full space-y-3 text-xs">
                    <div>
                        <span class="text-[11px] text-indigo-300 block">نام و نام خانوادگی سرپرست:</span>
                        <span class="text-base font-bold text-white mt-0.5 block">
                            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'مهندس کامران قنبری') ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-indigo-300 text-[11px] block">کد ملی (شناسه ورود):</span>
                            <span class="font-mono text-sm font-bold text-amber-300 tracking-wider">
                                <?= htmlspecialchars($_SESSION['admin_nid'] ?? '0099887766') ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-indigo-300 text-[11px] block">شماره تماس اضطراری:</span>
                            <span class="font-mono text-xs font-semibold text-slate-200" dir="ltr">09129876543</span>
                        </div>
                    </div>

                    <div class="p-3 bg-indigo-900/60 border border-indigo-600/50 rounded-xl space-y-1.5 shadow-xs">
                        <div class="flex items-center gap-1.5 text-amber-300 font-bold text-xs">
                            <span>⏱</span>
                            <span>شیفت کاری: ۲۲:۰۰ الی ۰۶:۰۰ (شیفت سرشماری شبانه)</span>
                        </div>
                        <div class="text-indigo-200 text-[11px]">
                            <span>مسئولیت: <strong>سرپرست شبانه خوابگاه صمد - طبقات همکف، ۱ و ۲</strong></span>
                        </div>
                        <div class="text-slate-300 text-[10px] pt-0.5 border-t border-indigo-700/50">
                            🏢 ساختمان مرکزی خوابگاه صمد (پردیس فناوری و مهارت)
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-indigo-800/60 flex items-center justify-between text-xs">
                <span class="text-slate-400 text-[11px]">شناسه سرپرستی: <strong class="text-amber-300 font-mono">SUP-405-01</strong></span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer">
                        چاپ کارت
                    </button>
                    <button type="button" onclick="document.getElementById('supervisorCardModal').classList.add('hidden')" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال چاپ صورت‌جلسه رسمی سرشماری شبانه (A4) -->
    <div id="attendancePrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-4xl w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <h3 class="text-sm font-bold text-slate-900">پیش‌نمایش چاپ صورت‌جلسه رسمی سرشماری شبانه خوابگاه</h3>
                <button type="button" onclick="closeAttendancePrintModal()" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
            </div>

            <!-- برگه رسمی A4 سرشماری -->
            <div id="printableAttendanceSheet" class="border-2 border-slate-900 p-6 rounded-2xl bg-white text-slate-900 space-y-4 print-sheet-page">
                <!-- سربرگ رسمی -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-3">
                    <div class="w-14 h-14 border border-slate-300 rounded-lg flex items-center justify-center text-xl bg-slate-50">
                        🇮🇷
                    </div>
                    <div class="text-center space-y-0.5">
                        <h2 class="text-[11px] font-bold text-slate-600">جمهوری اسلامی ایران - وزارت علوم، تحقیقات و فناوری</h2>
                        <h1 class="text-sm font-black text-indigo-950">دانشگاه ملی مهارت - اداره امور خوابگاه‌ها</h1>
                        <span class="text-xs font-bold text-slate-800">صورت‌جلسه رسمی سرشماری و حضور و غیاب شبانه خوابگاه دانشجویی صمد</span>
                    </div>
                    <div class="text-left font-mono text-[10px] space-y-1 text-slate-600">
                        <div>تاریخ: <strong><?= $persian_date_text ?></strong></div>
                        <div>ساعت سرشماری: <strong>۲۲:۳۰ شب</strong></div>
                        <div>طبقه: <strong><?= $selected_floor === 'all' ? 'تمامی طبقات' : htmlspecialchars($selected_floor) ?></strong></div>
                    </div>
                </div>

                <!-- آمار سرشماری -->
                <div class="flex items-center justify-around p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs font-bold">
                    <span>کل اسکان: <strong><?= $totalStudents ?> نفر</strong></span>
                    <span class="text-emerald-700">حاضر: <strong><?= $presentCount ?> نفر</strong></span>
                    <span class="text-rose-700">غایب: <strong><?= $absentCount ?> نفر</strong></span>
                    <span class="text-amber-700">مرخصی: <strong><?= $leaveCount ?> نفر</strong></span>
                </div>

                <!-- جدول اسامی -->
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-[11px] border-collapse border border-slate-300">
                        <thead class="bg-slate-100 font-bold">
                            <tr>
                                <th class="border border-slate-300 p-1.5 text-center w-10">ردیف</th>
                                <th class="border border-slate-300 p-1.5">اتاق و طبقه</th>
                                <th class="border border-slate-300 p-1.5">نام دانشجو</th>
                                <th class="border border-slate-300 p-1.5">شماره دانشجویی</th>
                                <th class="border border-slate-300 p-1.5">رشته تحصیلی</th>
                                <th class="border border-slate-300 p-1.5 text-center">وضعیت امشب</th>
                                <th class="border border-slate-300 p-1.5">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dormStudents as $idx => $std): ?>
                                <tr>
                                    <td class="border border-slate-300 p-1.5 text-center font-mono"><?= $idx + 1 ?></td>
                                    <td class="border border-slate-300 p-1.5 font-bold font-mono">اتاق <?= htmlspecialchars($std['room_number']) ?> (<?= htmlspecialchars($std['floor']) ?>)</td>
                                    <td class="border border-slate-300 p-1.5 font-bold"><?= htmlspecialchars($std['full_name']) ?></td>
                                    <td class="border border-slate-300 p-1.5 font-mono"><?= htmlspecialchars($std['student_id']) ?></td>
                                    <td class="border border-slate-300 p-1.5"><?= htmlspecialchars($std['field_of_study']) ?></td>
                                    <td class="border border-slate-300 p-1.5 text-center font-bold">
                                        <?= htmlspecialchars($std['attendance_status'] ?? 'حاضر') ?>
                                    </td>
                                    <td class="border border-slate-300 p-1.5 text-slate-600"><?= htmlspecialchars($std['attendance_notes'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- امضاها -->
                <div class="pt-6 flex items-center justify-between border-t border-slate-300 text-xs">
                    <div class="text-center space-y-1">
                        <span>سرپرست شیفت شب:</span>
                        <strong class="block text-slate-900"><?= htmlspecialchars($supervisor_name) ?></strong>
                        <span class="text-[10px] text-slate-500 font-mono">کد ملی: <?= htmlspecialchars($supervisor_nid) ?></span>
                        <div class="h-10 border-b border-dashed border-slate-400 w-36 mx-auto mt-1 flex items-center justify-center text-[10px] text-slate-400">محل امضا</div>
                    </div>
                    <div class="text-center space-y-1">
                        <span>مسئول امور دانشجویی پردیس:</span>
                        <strong class="block text-slate-900">دکتر علیرضا حسینی</strong>
                        <div class="h-10 border-b border-dashed border-slate-400 w-36 mx-auto mt-1 flex items-center justify-center text-[10px] text-slate-400">محل مهر و تایید</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t no-print">
                <button type="button" onclick="window.print()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow cursor-pointer flex items-center gap-1.5">
                    <span>🖨</span>
                    <span>چاپ این صورت‌جلسه (A4)</span>
                </button>
                <button type="button" onclick="closeAttendancePrintModal()" class="px-4 py-2.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                    بستن
                </button>
            </div>
        </div>
    </div>

    <script>
    function openReportModal(roomId = null, roomNumber = '', floor = '') {
        const modal = document.getElementById('reportModal');
        modal.classList.remove('hidden');
        if (roomId) {
            document.getElementById('modal_room_id').value = roomId;
        }
    }
    function closeReportModal() {
        document.getElementById('reportModal').classList.add('hidden');
    }

    // به‌روزرسانی زنده شمارنده‌های سرشماری در تب attendance
    document.addEventListener('DOMContentLoaded', function() {
        const groups = document.querySelectorAll('.attendance-group');
        if (!groups.length) return;

        function updateCounterDisplay() {
            let p = 0, a = 0, l = 0;
            groups.forEach(g => {
                const checked = g.querySelector('input[type="radio"]:checked');
                if (checked) {
                    if (checked.value === 'حاضر') p++;
                    else if (checked.value === 'غایب') a++;
                    else if (checked.value === 'مرخصی') l++;
                }
            });
            const pEl = document.getElementById('cnt-present');
            const aEl = document.getElementById('cnt-absent');
            const lEl = document.getElementById('cnt-leave');
            if (pEl) pEl.textContent = p;
            if (aEl) aEl.textContent = a;
            if (lEl) lEl.textContent = l;
        }

        groups.forEach(group => {
            const labels = group.querySelectorAll('.attendance-lbl');
            labels.forEach(label => {
                label.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    labels.forEach(l => {
                        l.className = 'attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all text-slate-600 hover:bg-slate-200';
                    });

                    const val = this.getAttribute('data-val');
                    if (val === 'حاضر') {
                        this.className = 'attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all bg-emerald-600 text-white font-bold shadow-xs';
                    } else if (val === 'غایب') {
                        this.className = 'attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all bg-rose-600 text-white font-bold shadow-xs';
                    } else if (val === 'مرخصی') {
                        this.className = 'attendance-lbl flex items-center gap-1 px-3 py-1.5 rounded-lg cursor-pointer transition-all bg-amber-600 text-white font-bold shadow-xs';
                    }

                    updateCounterDisplay();
                });
            });
        });
    });

    function setEquipName(name) {
        document.getElementById('modal_equipment_name').value = name;
    }

    function filterRollCallLive(q) {
        q = (q || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.roll-call-row');
        rows.forEach(r => {
            const str = r.getAttribute('data-search') || '';
            r.style.display = (!q || str.includes(q)) ? '' : 'none';
        });
    }

    function markAllVisible(status) {
        const rows = document.querySelectorAll('.roll-call-row');
        rows.forEach(r => {
            if (r.style.display !== 'none') {
                const group = r.querySelector('.attendance-group');
                if (group) {
                    const targetLbl = group.querySelector(`.attendance-lbl[data-val="${status}"]`);
                    if (targetLbl) targetLbl.click();
                }
            }
        });
    }

    function openAttendancePrintModal() {
        document.getElementById('attendancePrintModal').classList.remove('hidden');
    }

    function closeAttendancePrintModal() {
        document.getElementById('attendancePrintModal').classList.add('hidden');
    }

    function batchSetCleanliness(grade) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${grade}"]`);
        radios.forEach(r => {
            r.checked = true;
            const lbl = r.closest('label');
            if (lbl) {
                const parent = lbl.parentElement;
                parent.querySelectorAll('label').forEach(l => {
                    l.className = 'flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors bg-white text-slate-700 hover:bg-slate-100';
                });
                if (grade === 'عالی') {
                    lbl.className = 'flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors bg-emerald-600 text-white font-bold border-emerald-600';
                } else if (grade === 'خوب') {
                    lbl.className = 'flex items-center gap-1 px-2.5 py-1.5 border rounded-lg cursor-pointer text-xs transition-colors bg-cyan-600 text-white font-bold border-cyan-600';
                }
            }
        });
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeReportModal();
            closeAttendancePrintModal();
            const supModal = document.getElementById('supervisorCardModal');
            if (supModal) supModal.classList.add('hidden');
        }
    });
    </script>
</body>
</html>
