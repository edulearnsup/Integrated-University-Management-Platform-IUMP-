<?php
/**
 * پنل اختصاصی مدیر دانشگاه و امور خوابگاه‌ها (PHP + MySQL)
 * طراحی شده با قالب مدرن تیره (مشابه پیشخوان‌های حرفه‌ای ابزاروردپرس)
 * 
 * بخش‌ها (تفکیک شده در تب‌های داشبورد سمت راست بدون ریدایرکت به صفحات دیگر):
 * ۱. پیشخوان و آمار کلی (Overview)
 * ۲. پذیرش دانشجوی جدید در جدول منبع (admitted_students)
 * ۳. متقاضیان خوابگاه در صف اسکان (با قابلیت تعیین مدت میهمانی با واحد روز)
 * ۴. مدیریت اتاق‌ها و طبقات (فعال/غیرفعال، حذف و ایجاد اتاق)
 * ۵. دانشجویان اسکان‌یافته و تغییر اتاق (مدیریت روزهای میهمانی)
 * ۶. بررسی سرشماری و حضور و غیاب شبانه (همراه با پرینت کلی و پرینت فردی)
 * ۷. مدیریت سرپرستان خوابگاه و صدور کارت شناسایی
 */
session_start();
require_once 'db.php';
require_once 'jwt_auth.php';

$jwtAuth = samad_require_page_jwt($pdo, ['manager'], 'dorm:rooms:manage');

$msg = '';
$err = '';

// تابع خودکارسازی ایجاد ستون‌های میهمانی در صورت نبود در دیتابیس لوکال WAMP
function checkOrAddGuestColumns($pdo) {
    try {
        $c = $pdo->query("SHOW COLUMNS FROM `dorm_allocations` LIKE 'guest_days_total'")->fetchAll();
        if (empty($c)) {
            $pdo->exec("
                ALTER TABLE `dorm_allocations` 
                ADD COLUMN `guest_days_total` INT DEFAULT NULL COMMENT 'تعداد کل روزهای اقامت میهمانی' AFTER `is_active`,
                ADD COLUMN `guest_days_remaining` INT DEFAULT NULL COMMENT 'روزهای باقی‌مانده میهمانی' AFTER `guest_days_total`
            ");
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
checkOrAddGuestColumns($pdo);

// تابع خودکارسازی ستون‌های شیفت کاری سرپرستان
function checkOrAddSupervisorColumns($pdo) {
    try {
        $c = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'shift_hours'")->fetchAll();
        if (empty($c)) {
            $pdo->exec("
                ALTER TABLE `admins`
                ADD COLUMN `shift_hours` VARCHAR(150) DEFAULT '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)' AFTER `role`,
                ADD COLUMN `shift_days` VARCHAR(150) DEFAULT 'شنبه تا چهارشنبه' AFTER `shift_hours`
            ");
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
checkOrAddSupervisorColumns($pdo);

// توابع محاسبات تقویم جلالی (شمسی) جهت تعیین زمان میهمانی و انقضا
if (!function_exists('jalali_to_gregorian')) {
    function jalali_to_gregorian($jy, $jm, $jd) {
        $gy = ($jy <= 979) ? 621 : 1600;
        $jy -= ($jy <= 979) ? 0 : 979;
        $days = (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4)
            + 78 + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy += 400 * (int)($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $days--;
            $gy += 100 * (int)($days / 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        while ($gm < 13 && $days >= $sal_a[$gm]) {
            $days -= $sal_a[$gm];
            $gm++;
        }
        $gd = $days + 1;
        return [$gy, $gm, $gd];
    }
}

if (!function_exists('gregorian_to_jalali')) {
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
}

if (!function_exists('add_days_to_jalali_php')) {
    function add_days_to_jalali_php($jalali_str, $days_to_add) {
        $clean = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','-'], ['0','1','2','3','4','5','6','7','8','9','/'], $jalali_str);
        $parts = explode('/', $clean);
        if (count($parts) === 3) {
            $jy = intval($parts[0]);
            $jm = intval($parts[1]);
            $jd = intval($parts[2]);
            if ($jy > 1300) {
                list($gy, $gm, $gd) = jalali_to_gregorian($jy, $jm, $jd);
                $ts = strtotime("{$gy}-{$gm}-{$gd} + {$days_to_add} days");
                list($njy, $njm, $njd) = gregorian_to_jalali(intval(date('Y', $ts)), intval(date('n', $ts)), intval(date('j', $ts)));
                return sprintf('%04d/%02d/%02d', $njy, $njm, $njd);
            }
        }
        return date('Y-m-d', strtotime("+ {$days_to_add} days"));
    }
}

// دریافت تب فعال از پارامتر یا پیش‌فرض پیشخوان
$active_tab = $_GET['tab'] ?? 'overview';

// -------------------------------------------------------------
// ۱. عملیات: افزودن دانشجوی قبولی به جدول منبع (admitted_students)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_admitted'])) {
    $nid   = trim($_POST['national_id'] ?? '');
    $sid   = trim($_POST['student_id'] ?? '');
    $name  = trim($_POST['full_name'] ?? '');
    $campus = trim($_POST['campus'] ?? 'پردیس فناوری');
    $field = trim($_POST['field_of_study'] ?? '');

    if (!empty($nid) && !empty($sid) && !empty($name) && !empty($field)) {
        try {
            $chk = $pdo->prepare("SELECT id FROM `admitted_students` WHERE `national_id` = ? OR `student_id` = ?");
            $chk->execute([$nid, $sid]);
            if ($chk->fetch()) {
                $err = "داوطلبی با این کد ملی ({$nid}) یا شماره دانشجویی ({$sid}) قبلاً در جدول منبع ثبت شده است.";
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO `admitted_students` (`national_id`, `student_id`, `full_name`, `campus`, `field_of_study`, `is_registered`)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $ins->execute([$nid, $sid, $name, $campus, $field]);
                $msg = "داوطلب جدید «{$name}» با موفقیت در جدول منبع (admitted_students) ثبت شد.";
            }
        } catch (Exception $e) {
            $err = "خطا در ثبت داوطلب: " . $e->getMessage();
        }
    } else {
        $err = "لطفاً تمامی فیلدهای الزامی داوطلب را تکمیل نمایید.";
    }
}

// حذف داوطلب از جدول منبع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_admitted'])) {
    $adm_id = intval($_POST['admitted_id'] ?? 0);
    try {
        $del = $pdo->prepare("DELETE FROM `admitted_students` WHERE `id` = ?");
        $del->execute([$adm_id]);
        $msg = "داوطلب با موفقیت از جدول منبع حذف گردید.";
    } catch (Exception $e) {
        $err = "خطا در حذف داوطلب: " . $e->getMessage();
    }
}

// -------------------------------------------------------------
// ۲. عملیات: اسکان متقاضی با تعیین مدت میهمانی با واحد روز
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_assign_unhoused'])) {
    $student_id       = trim($_POST['student_id'] ?? '');
    $room_id          = intval($_POST['room_id'] ?? 0);
    $dorm_status      = trim($_POST['dorm_status'] ?? 'بدون شهریه');
    $guest_days       = max(1, intval($_POST['guest_days'] ?? 7));
    if (!empty($_POST['guest_start_year']) && !empty($_POST['guest_start_month']) && !empty($_POST['guest_start_day'])) {
        $gy = intval($_POST['guest_start_year']);
        $gm = intval($_POST['guest_start_month']);
        $gd = intval($_POST['guest_start_day']);
        $guest_start_date = sprintf('%04d/%02d/%02d', $gy, $gm, $gd);
    } elseif (!empty($_POST['guest_start_date'])) {
        $guest_start_date = trim($_POST['guest_start_date']);
    } else {
        $guest_start_date = '1405/07/25';
    }
    $guest_end_date   = add_days_to_jalali_php($guest_start_date, $guest_days);

    if (!empty($student_id) && $room_id > 0) {
        try {
            $pdo->beginTransaction();
            // غیرفعال‌سازی تخصیص‌های قبلی
            $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0 WHERE `student_id` = ?")->execute([$student_id]);

            $hasCols = checkOrAddGuestColumns($pdo);

            if ($hasCols) {
                if ($dorm_status === 'میهمان') {
                    $ins = $pdo->prepare("
                        INSERT INTO `dorm_allocations` 
                            (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`, `guest_days_total`, `guest_days_remaining`, `guest_start_date`, `guest_end_date`)
                        VALUES (?, ?, 'میهمان', ?, 1, ?, ?, ?, ?)
                    ");
                    $ins->execute([$student_id, $room_id, $guest_start_date, $guest_days, $guest_days, $guest_start_date, $guest_end_date]);
                    $msg = "دانشجو ({$student_id}) به صورت میهمان از تاریخ {$guest_start_date} تا {$guest_end_date} ({$guest_days} روزه) در اتاق اسکان داده شد.";
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO `dorm_allocations` 
                            (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`, `guest_days_total`, `guest_days_remaining`, `guest_start_date`, `guest_end_date`)
                        VALUES (?, ?, ?, CURDATE(), 1, NULL, NULL, NULL, NULL)
                    ");
                    $ins->execute([$student_id, $room_id, $dorm_status]);
                    $msg = "دانشجو ({$student_id}) با موفقیت در اتاق اسکان داده شد.";
                }
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO `dorm_allocations` 
                        (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`)
                    VALUES (?, ?, ?, CURDATE(), 1)
                ");
                $ins->execute([$student_id, $room_id, $dorm_status]);
                $msg = "دانشجو ({$student_id}) با موفقیت در اتاق اسکان داده شد.";
            }

            // اطمینان از وضعیت درخواست خوابگاه
            $pdo->prepare("UPDATE `students` SET `request_dormitory` = 1 WHERE `student_id` = ?")->execute([$student_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در اسکان دانشجو: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// ۳. عملیات: تغییر اتاق و وضعیت شهریه / روزهای میهمانی دانشجو
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_student_dorm'])) {
    $student_id       = trim($_POST['student_id'] ?? '');
    $new_room_id      = intval($_POST['new_room_id'] ?? 0);
    $new_dorm_status  = trim($_POST['dorm_status'] ?? 'بدون شهریه');
    $guest_days       = max(1, intval($_POST['guest_days'] ?? 7));
    if (!empty($_POST['guest_start_year']) && !empty($_POST['guest_start_month']) && !empty($_POST['guest_start_day'])) {
        $gy = intval($_POST['guest_start_year']);
        $gm = intval($_POST['guest_start_month']);
        $gd = intval($_POST['guest_start_day']);
        $guest_start_date = sprintf('%04d/%02d/%02d', $gy, $gm, $gd);
    } elseif (!empty($_POST['guest_start_date'])) {
        $guest_start_date = trim($_POST['guest_start_date']);
    } else {
        $guest_start_date = '1405/07/25';
    }
    $guest_end_date   = add_days_to_jalali_php($guest_start_date, $guest_days);

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0 WHERE `student_id` = ?")->execute([$student_id]);

        $hasCols = checkOrAddGuestColumns($pdo);

        if ($hasCols) {
            if ($new_dorm_status === 'میهمان') {
                $ins = $pdo->prepare("
                    INSERT INTO `dorm_allocations` 
                        (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`, `guest_days_total`, `guest_days_remaining`, `guest_start_date`, `guest_end_date`)
                    VALUES (?, ?, 'میهمان', ?, 1, ?, ?, ?, ?)
                ");
                $ins->execute([$student_id, $new_room_id, $guest_start_date, $guest_days, $guest_days, $guest_start_date, $guest_end_date]);
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO `dorm_allocations` 
                        (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`, `guest_days_total`, `guest_days_remaining`, `guest_start_date`, `guest_end_date`)
                    VALUES (?, ?, ?, CURDATE(), 1, NULL, NULL, NULL, NULL)
                ");
                $ins->execute([$student_id, $new_room_id, $new_dorm_status]);
            }
        } else {
            $ins = $pdo->prepare("
                INSERT INTO `dorm_allocations` 
                    (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`)
                VALUES (?, ?, ?, CURDATE(), 1)
            ");
            $ins->execute([$student_id, $new_room_id, $new_dorm_status]);
        }

        $pdo->commit();
        $msg = "اتاق و وضعیت اسکان دانشجو ({$student_id}) با موفقیت در پایگاه داده به‌روزرسانی شد.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "خطا در به‌روزرسانی اسکان: " . $e->getMessage();
    }
}

// کسر ۱ روز از مهلت میهمانی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reduce_guest_day'])) {
    $alloc_id = intval($_POST['alloc_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `dorm_allocations` WHERE `id` = ? AND `is_active` = 1");
        $stmt->execute([$alloc_id]);
        $row = $stmt->fetch();
        if ($row && $row['dorm_status'] === 'میهمان') {
            $rem = intval($row['guest_days_remaining'] ?? 7) - 1;
            if ($rem <= 0) {
                // انقضا و بازگشت قطعی به فاقدین اسکان
                $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0, `guest_days_remaining` = 0 WHERE `id` = ?")->execute([$alloc_id]);
                $pdo->prepare("UPDATE `students` SET `request_dormitory` = 1 WHERE `student_id` = ?")->execute([$row['student_id']]);
                $msg = "مهلت اقامت میهمان دانشجو ({$row['student_id']}) به پایان رسید و به صورت خودکار به لیست متقاضیان خوابگاه بازگشت.";
            } else {
                $pdo->prepare("UPDATE `dorm_allocations` SET `guest_days_remaining` = ? WHERE `id` = ?")->execute([$rem, $alloc_id]);
                $msg = "یک روز از مهلت اقامت میهمان کسر گردید ({$rem} روز باقی‌مانده).";
            }
        }
    } catch (Exception $e) {
        $err = "خطا در کسر روز میهمانی: " . $e->getMessage();
    }
}

// اتمام دستی اقامت میهمانی و بازگشت به لیست متقاضیان خوابگاه
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_end_guest_stay'])) {
    $alloc_id = intval($_POST['alloc_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `dorm_allocations` WHERE `id` = ? AND `is_active` = 1");
        $stmt->execute([$alloc_id]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0, `guest_days_remaining` = 0 WHERE `id` = ?")->execute([$alloc_id]);
            $pdo->prepare("UPDATE `students` SET `request_dormitory` = 1 WHERE `student_id` = ?")->execute([$row['student_id']]);
            $msg = "اقامت میهمان دانشجو ({$row['student_id']}) به پایان رسید و به لیست متقاضیان خوابگاه بازگردانده شد.";
        }
    } catch (Exception $e) {
        $err = "خطا در اتمام دوره میهمانی: " . $e->getMessage();
    }
}

// لغو اسکان دانشجو و انتقال به فاقدین اسکان
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_evict_student'])) {
    $alloc_id = intval($_POST['alloc_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `dorm_allocations` WHERE `id` = ?");
        $stmt->execute([$alloc_id]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0 WHERE `id` = ?")->execute([$alloc_id]);
            $pdo->prepare("UPDATE `students` SET `request_dormitory` = 1 WHERE `student_id` = ?")->execute([$row['student_id']]);
            $msg = "اسکان دانشجو لغو و به لیست متقاضیان در انتظار اتاق منتقل شد.";
        }
    } catch (Exception $e) {
        $err = "خطا در لغو اسکان: " . $e->getMessage();
    }
}

// -------------------------------------------------------------
// ۴. عملیات: مدیریت اتاق‌ها (فعال/غیرفعال، حذف، ایجاد اتاق)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle_room'])) {
    $room_id = intval($_POST['room_id'] ?? 0);
    $current_status = intval($_POST['current_status'] ?? 0);
    $new_status = $current_status === 1 ? 0 : 1;
    try {
        $stmt = $pdo->prepare("UPDATE `dorm_rooms` SET `is_active` = ? WHERE `id` = ?");
        $stmt->execute([$new_status, $room_id]);
        $msg = "وضعیت فعالیت اتاق با موفقیت به " . ($new_status === 1 ? "«فعال»" : "«غیرفعال»") . " تغییر یافت.";
    } catch (Exception $e) {
        $err = "خطا در تغییر وضعیت اتاق: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_room'])) {
    $room_id = intval($_POST['room_id'] ?? 0);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE `dorm_allocations` SET `is_active` = 0 WHERE `room_id` = ?")->execute([$room_id]);
        $del = $pdo->prepare("DELETE FROM `dorm_rooms` WHERE `id` = ?");
        $del->execute([$room_id]);
        $pdo->commit();
        $msg = "اتاق با موفقیت حذف گردید و دانشجویان ساکن آن به لیست فاقدین اسکان منتقل شدند.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "خطا در حذف اتاق: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_room'])) {
    $room_number = trim($_POST['room_number'] ?? '');
    $floor       = trim($_POST['floor'] ?? 'همکف');
    $capacity    = intval($_POST['capacity'] ?? 4);
    $description = trim($_POST['description'] ?? '');

    if (!empty($room_number)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `dorm_rooms` (`room_number`, `floor`, `capacity`, `is_active`, `description`) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$room_number, $floor, $capacity, $description]);
            $msg = "اتاق جدید ({$room_number}) با موفقیت به {$floor} اضافه شد.";
        } catch (Exception $e) {
            $err = "خطا در ایجاد اتاق: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// ۵. عملیات: افزودن و حذف سرپرست خوابگاه و کاربر مدیر کل
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action_add_supervisor']) || isset($_POST['action_add_manager']))) {
    $active_tab = 'supervisors';
    $requestedRole = isset($_POST['action_add_manager']) ? 'manager' : trim($_POST['admin_role'] ?? 'supervisor');
    $targetRole = ($requestedRole === 'manager') ? 'manager' : 'supervisor';

    $sup_nid   = toEnglishDigits(trim($_POST['sup_national_id'] ?? ($_POST['mgr_national_id'] ?? '')));
    $sup_name  = trim($_POST['sup_full_name'] ?? ($_POST['mgr_full_name'] ?? ''));
    $defaultPass = ($targetRole === 'manager') ? 'Admin@12345' : 'Super@12345';
    $sup_pass  = trim($_POST['sup_password'] ?? ($_POST['mgr_password'] ?? ''));
    if ($sup_pass === '') {
        $sup_pass = $defaultPass;
    }
    $sup_shift_days  = trim($_POST['sup_shift_days'] ?? ($targetRole === 'manager' ? 'شنبه تا پنج‌شنبه' : 'شنبه تا چهارشنبه'));
    $sup_shift_hours = trim($_POST['sup_shift_hours'] ?? ($targetRole === 'manager' ? '۰۸:۰۰ الی ۱۶:۰۰ (اداری)' : '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)'));

    if (!preg_match('/^\d{10}$/', $sup_nid)) {
        $err = "کد ملی باید دقیقاً ۱۰ رقم عددی معتبر باشد.";
    } elseif (empty($sup_name)) {
        $err = "لطفاً نام و نام خانوادگی کاربر را وارد نمایید.";
    } elseif (strlen($sup_pass) < 4) {
        $err = "کلمه عبور باید حداقل ۴ کاراکتر باشد.";
    } else {
        try {
            $chk = $pdo->prepare("SELECT id, role FROM `admins` WHERE `national_id` = ?");
            $chk->execute([$sup_nid]);
            if ($chk->fetch()) {
                $err = "کد ملی {$sup_nid} قبلاً برای یک کاربر در سامانه ثبت شده است.";
            } else {
                $hash = password_hash($sup_pass, PASSWORD_DEFAULT);
                $hasCols = checkOrAddSupervisorColumns($pdo);
                if ($hasCols) {
                    $ins = $pdo->prepare("INSERT INTO `admins` (`national_id`, `password_hash`, `role`, `full_name`, `shift_hours`, `shift_days`) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->execute([$sup_nid, $hash, $targetRole, $sup_name, $sup_shift_hours, $sup_shift_days]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO `admins` (`national_id`, `password_hash`, `role`, `full_name`) VALUES (?, ?, ?, ?)");
                    $ins->execute([$sup_nid, $hash, $targetRole, $sup_name]);
                }
                if ($targetRole === 'manager') {
                    $msg = "کاربر مدیر کل جدید «{$sup_name}» (کد ملی: {$sup_nid}) با دسترسی کامل مدیریتی ایجاد گردید.";
                } else {
                    $msg = "سرپرست جدید «{$sup_name}» با موفقیت ایجاد گردید.";
                }
            }
        } catch (Exception $e) {
            $err = "خطا در ثبت کاربر: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_supervisor_shift'])) {
    $active_tab = 'supervisors';
    $sup_id = intval($_POST['supervisor_id'] ?? 0);
    $sup_shift_days  = trim($_POST['sup_shift_days'] ?? 'شنبه تا چهارشنبه');
    $sup_shift_hours = trim($_POST['sup_shift_hours'] ?? '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)');
    try {
        checkOrAddSupervisorColumns($pdo);
        $up = $pdo->prepare("UPDATE `admins` SET `shift_hours` = ?, `shift_days` = ? WHERE `id` = ? AND `role` = 'supervisor'");
        $up->execute([$sup_shift_hours, $sup_shift_days, $sup_id]);
        $msg = "روزها و ساعات کاری شیفت سرپرست با موفقیت به‌روزرسانی شد.";
    } catch (Exception $e) {
        $err = "خطا در تغییر شیفت سرپرست: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_supervisor'])) {
    $active_tab = 'supervisors';
    $sup_id = intval($_POST['supervisor_id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM `admins` WHERE `id` = ? AND `role` = 'supervisor'")->execute([$sup_id]);
        $msg = "سرپرست از سامانه حذف گردید.";
    } catch (Exception $e) {
        $err = "خطا در حذف سرپرست: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_manager'])) {
    $active_tab = 'supervisors';
    $mgr_id = intval($_POST['manager_id'] ?? 0);
    try {
        $stmtTarget = $pdo->prepare("SELECT * FROM `admins` WHERE `id` = ? AND `role` = 'manager' LIMIT 1");
        $stmtTarget->execute([$mgr_id]);
        $targetMgr = $stmtTarget->fetch();
        $currentNid = $jwtAuth['sub'] ?? ($_SESSION['admin_national_id'] ?? '');
        $mgrCount = intval($pdo->query("SELECT COUNT(*) FROM `admins` WHERE `role` = 'manager'")->fetchColumn());

        if (!$targetMgr) {
            $err = "کاربر مدیر مورد نظر یافت نشد.";
        } elseif ($targetMgr['national_id'] === $currentNid) {
            $err = "امکان حذف حساب کاربری مدیریتی که اکنون با آن وارد شده‌اید وجود ندارد.";
        } elseif ($mgrCount <= 1) {
            $err = "حداقل یک کاربر مدیر کل باید در سامانه باقی بماند.";
        } else {
            $pdo->prepare("DELETE FROM `admins` WHERE `id` = ? AND `role` = 'manager'")->execute([$mgr_id]);
            $msg = "کاربر مدیر «{$targetMgr['full_name']}» از سامانه حذف گردید.";
        }
    } catch (Exception $e) {
        $err = "خطا در حذف کاربر مدیر: " . $e->getMessage();
    }
}

// ۸. عملیات: ثبت و تکمیل اطلاعات و امکانات اتاق (Room Facilities)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_facilities'])) {
    $r_num       = trim($_POST['room_number'] ?? '');
    $fridge      = trim($_POST['fridge_model'] ?? 'یخچال ۵ فوت امرسان');
    $bed_type    = trim($_POST['bed_type'] ?? 'تخت فلزی دو طبقه استاندارد');
    $broom       = isset($_POST['has_broom']) ? 1 : 0;
    $trash       = isset($_POST['has_trash_bin']) ? 1 : 0;
    $bucket      = isset($_POST['has_bucket']) ? 1 : 0;
    $tv          = trim($_POST['tv_model'] ?? '');
    $ac_heater   = trim($_POST['ac_heater'] ?? 'شوفاژ مرکزی + کولر آبی');
    $carpet      = isset($_POST['has_carpet']) ? 1 : 0;
    $wardrobe    = isset($_POST['has_wardrobe']) ? 1 : 0;
    $desk        = isset($_POST['has_desk']) ? 1 : 0;
    $shoe_rack   = isset($_POST['has_shoe_rack']) ? 1 : 0;
    $mirror      = isset($_POST['has_mirror']) ? 1 : 0;
    $fac_notes   = trim($_POST['notes'] ?? '');

    try {
        $rRow = $pdo->prepare("SELECT id FROM `dorm_rooms` WHERE `room_number` = ?");
        $rRow->execute([$r_num]);
        $rmFound = $rRow->fetch();
        $roomId = $rmFound ? $rmFound['id'] : null;

        $checkFac = $pdo->prepare("SELECT id FROM `room_facilities` WHERE `room_number` = ?");
        $checkFac->execute([$r_num]);
        if ($checkFac->fetch()) {
            $upFac = $pdo->prepare("
                UPDATE `room_facilities` SET
                    `fridge_model` = ?, `bed_type` = ?, `has_broom` = ?, `has_trash_bin` = ?, `has_bucket` = ?,
                    `tv_model` = ?, `ac_heater` = ?, `has_carpet` = ?, `has_wardrobe` = ?, `has_desk` = ?,
                    `has_shoe_rack` = ?, `has_mirror` = ?, `notes` = ?, `room_id` = ?, `updated_at` = NOW()
                WHERE `room_number` = ?
            ");
            $upFac->execute([$fridge, $bed_type, $broom, $trash, $bucket, $tv, $ac_heater, $carpet, $wardrobe, $desk, $shoe_rack, $mirror, $fac_notes, $roomId, $r_num]);
        } else {
            $insFac = $pdo->prepare("
                INSERT INTO `room_facilities` 
                    (`room_id`, `room_number`, `fridge_model`, `bed_type`, `has_broom`, `has_trash_bin`, `has_bucket`, `tv_model`, `ac_heater`, `has_carpet`, `has_wardrobe`, `has_desk`, `has_shoe_rack`, `has_mirror`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insFac->execute([$roomId, $r_num, $fridge, $bed_type, $broom, $trash, $bucket, $tv, $ac_heater, $carpet, $wardrobe, $desk, $shoe_rack, $mirror, $fac_notes]);
        }

        // بروزرسانی وضعیت تکمیل اتاق
        $pdo->prepare("UPDATE `dorm_rooms` SET `is_completed` = 1 WHERE `room_number` = ?")->execute([$r_num]);
        $msg = "مشخصات و امکانات تفکیکی اتاق «{$r_num}» با موفقیت ثبت شد و به عنوان «تکمیل شده» علامت‌گذاری گردید.";
    } catch (Exception $e) {
        $err = "خطا در ثبت امکانات اتاق: " . $e->getMessage();
    }
}

// ۹. عملیات: ثبت اقدام مدیر و فاکتور مالی خرابی و تعمیرات (Maintenance Resolution)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_resolve_maintenance'])) {
    $rep_id       = intval($_POST['report_id'] ?? 0);
    $act_taken    = trim($_POST['action_taken'] ?? 'repair');
    $cost_rials   = intval($_POST['cost_rials'] ?? 0);
    $inv_num      = trim($_POST['invoice_number'] ?? '');
    $contractor   = trim($_POST['contractor_or_store_name'] ?? '');
    $res_notes    = trim($_POST['resolution_notes'] ?? '');
    $mgr_name     = trim($_POST['resolved_by'] ?? ($_SESSION['admin_name'] ?? 'دکتر علیرضا حسینی (مدیر امور رفاهی)'));

    if ($rep_id > 0) {
        try {
            $statusVal = ($act_taken === 'replacement' || str_contains($act_taken, 'خرید') || str_contains($act_taken, 'تعویض')) ? 'رفع‌شده / خریداری و تعویض شد' : 'رفع‌شده / تعمیر شد';
            $nowPersian = date('Y/m/d - H:i');
            
            // استخراج ستون‌های موجود در جدول برای سازگاری کامل
            $cols = $pdo->query("SHOW COLUMNS FROM `maintenance_reports`")->fetchAll(PDO::FETCH_COLUMN);
            $hasActionTaken   = in_array('action_taken', $cols);
            $hasResAction     = in_array('resolution_action', $cols);
            $hasCostRials     = in_array('cost_rials', $cols);
            $hasResCost       = in_array('resolution_cost', $cols);
            $hasResolvedBy    = in_array('resolved_by', $cols);
            $hasResolvedByMgr = in_array('resolved_by_manager_name', $cols);
            $hasInvoice       = in_array('invoice_number', $cols);
            $hasContractor    = in_array('contractor_or_store_name', $cols);
            $hasResNotes      = in_array('resolution_notes', $cols);
            $hasResolvedAt    = in_array('resolved_at', $cols);

            $sets = ["`status` = ?"];
            $params = [$statusVal];

            if ($hasActionTaken)   { $sets[] = "`action_taken` = ?"; $params[] = $act_taken; }
            if ($hasResAction)     { $sets[] = "`resolution_action` = ?"; $params[] = ($act_taken === 'replacement' ? 'خرید و جایگزینی نو' : 'تعمیر قطعه'); }
            if ($hasCostRials)     { $sets[] = "`cost_rials` = ?"; $params[] = $cost_rials; }
            if ($hasResCost)       { $sets[] = "`resolution_cost` = ?"; $params[] = $cost_rials; }
            if ($hasInvoice)       { $sets[] = "`invoice_number` = ?"; $params[] = $inv_num; }
            if ($hasContractor)    { $sets[] = "`contractor_or_store_name` = ?"; $params[] = $contractor; }
            if ($hasResNotes)      { $sets[] = "`resolution_notes` = ?"; $params[] = $res_notes; }
            if ($hasResolvedBy)    { $sets[] = "`resolved_by` = ?"; $params[] = $mgr_name; }
            if ($hasResolvedByMgr) { $sets[] = "`resolved_by_manager_name` = ?"; $params[] = $mgr_name; }
            if ($hasResolvedAt)    { $sets[] = "`resolved_at` = ?"; $params[] = $nowPersian; }

            $params[] = $rep_id;
            $sql = "UPDATE `maintenance_reports` SET " . implode(', ', $sets) . " WHERE `id` = ?";
            $upMaint = $pdo->prepare($sql);
            $upMaint->execute($params);

            $msg = "گزارش خرابی (کد پیگیری #{$rep_id}) با ثبت رسمی فاکتور (شماره {$inv_num}) و هزینه " . number_format($cost_rials) . " ریال به عنوان «رفع‌شده» ثبت گردید.";
        } catch (Exception $e) {
            $err = "خطا در ثبت اقدام گزارش خرابی: " . $e->getMessage();
        }
    }
}

// ۱۰. عملیات: ایجاد فراخوان هدفمند جدید توسط مدیریت با بازه زمانی (دیت‌پیکر شروع و پایان)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_festival_call'])) {
    $call_title   = trim($_POST['call_title'] ?? '');
    $call_cat     = trim($_POST['call_category'] ?? 'general');
    $call_desc    = trim($_POST['call_description'] ?? '');
    if (!empty($_POST['call_start_year']) && !empty($_POST['call_start_month']) && !empty($_POST['call_start_day'])) {
        $call_start = sprintf('%04d/%02d/%02d', intval($_POST['call_start_year']), intval($_POST['call_start_month']), intval($_POST['call_start_day']));
    } else {
        $call_start = trim($_POST['start_date'] ?? '1405/07/25');
    }

    if (!empty($_POST['call_deadline_year']) && !empty($_POST['call_deadline_month']) && !empty($_POST['call_deadline_day'])) {
        $call_dead = sprintf('%04d/%02d/%02d', intval($_POST['call_deadline_year']), intval($_POST['call_deadline_month']), intval($_POST['call_deadline_day']));
    } else {
        $call_dead = trim($_POST['deadline_date'] ?? '1405/08/10');
    }
    $target_team  = trim($_POST['target_sports_team'] ?? '');
    $target_skill = trim($_POST['target_skill'] ?? '');

    if (!empty($call_title)) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `festival_calls`")->fetchAll(PDO::FETCH_COLUMN);
            $hasCat       = in_array('category', $cols);
            $hasTargetCat = in_array('target_category', $cols);
            $hasDesc      = in_array('description', $cols);
            $hasContent   = in_array('content', $cols);
            $hasCreatedBy = in_array('created_by', $cols);
            $hasIssuedBy  = in_array('issued_by', $cols);
            $hasStartDate = in_array('start_date', $cols);

            $fields = ['title', 'deadline_date', 'target_sports_team'];
            $placeholders = ['?', '?', '?'];
            $params = [$call_title, $call_dead, !empty($target_team) ? $target_team : null];

            if ($hasStartDate) {
                $fields[] = 'start_date';
                $placeholders[] = '?';
                $params[] = $call_start;
            }
            if ($hasCat) {
                $fields[] = 'category';
                $placeholders[] = '?';
                $params[] = $call_cat;
            }
            if ($hasTargetCat) {
                $fields[] = 'target_category';
                $placeholders[] = '?';
                $params[] = ($call_cat === 'general' ? 'all' : $call_cat);
            }
            if ($hasDesc) {
                $fields[] = 'description';
                $placeholders[] = '?';
                $params[] = $call_desc;
            }
            if ($hasContent) {
                $fields[] = 'content';
                $placeholders[] = '?';
                $params[] = $call_desc;
            }
            if ($hasCreatedBy) {
                $fields[] = 'created_by';
                $placeholders[] = '?';
                $params[] = $_SESSION['admin_name'] ?? 'مدیر امور دانشجویی';
            }
            if ($hasIssuedBy) {
                $fields[] = 'issued_by';
                $placeholders[] = '?';
                $params[] = $_SESSION['admin_name'] ?? 'مدیر امور دانشجویی';
            }
            if (in_array('target_skill', $cols)) {
                $fields[] = 'target_skill';
                $placeholders[] = '?';
                $params[] = !empty($target_skill) ? $target_skill : null;
            }
            if (in_array('is_active', $cols)) {
                $fields[] = 'is_active';
                $placeholders[] = '1';
            }

            $sql = "INSERT INTO `festival_calls` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "فراخوان هدفمند «{$call_title}» (از تاریخ {$call_start} تا {$call_dead}) با موفقیت صادر و منتشر گردید.";
        } catch (Exception $e) {
            $err = "خطا در ایجاد فراخوان: " . $e->getMessage();
        }
    } else {
        $err = "عنوان فراخوان الزامی است.";
    }
}

// ۱۱. عملیات: حذف فراخوان
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_festival_call'])) {
    $cid = intval($_POST['call_id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM `festival_calls` WHERE `id` = ?")->execute([$cid]);
        $msg = "فراخوان با موفقیت حذف گردید.";
    } catch (Exception $e) {
        $err = "خطا در حذف فراخوان: " . $e->getMessage();
    }
}

// -------------------------------------------------------------
// ۱۲. عملیات خدمات رفاهی: تنظیمات سالن بدنسازی، سانس‌ها، رزرو تیم و لغو با عودت وجه
// -------------------------------------------------------------

// ۱۲.۱ ویرایش مشخصات و نرخ ساعتی سالن بدنسازی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_welfare_gym'])) {
    $active_tab  = 'welfare';
    $serv_id     = intval($_POST['service_id'] ?? 1);
    $title       = trim($_POST['service_title'] ?? 'سالن بدنسازی و پرورش اندام پردیس');
    $location    = trim($_POST['service_location'] ?? 'زیرزمین خوابگاه مرکزی صمد');
    $rate_rials  = max(0, intval($_POST['hourly_rate_rials'] ?? 50000));
    $capacity    = max(5, intval($_POST['capacity_per_slot'] ?? 20));
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $rules       = trim($_POST['rules_guidelines'] ?? '');

    try {
        $stmtGym = $pdo->prepare("
            UPDATE `welfare_services` SET 
                `title` = ?, `location` = ?, `hourly_rate_rials` = ?, 
                `capacity_per_slot` = ?, `is_active` = ?, `rules_guidelines` = ?
            WHERE `id` = ?
        ");
        $stmtGym->execute([$title, $location, $rate_rials, $capacity, $is_active, $rules, $serv_id]);
        $msg = "تنظیمات سالن بدنسازی و نرخ ساعتی با موفقیت ذخیره گردید.";
    } catch (Exception $e) {
        $err = "خطا در ذخیره مشخصات سالن: " . $e->getMessage();
    }
}

// ۱۲.۲ تولید خودکار سانس‌های استاندارد (۸ تا ۲۲ به جز ۱۲ تا ۱۴ برای ناهار)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_generate_gym_slots'])) {
    $active_tab = 'welfare';
    $serv_id = intval($_POST['service_id'] ?? 1);
    try {
        // واکشی ظرفیت سالن
        $servRow = $pdo->query("SELECT * FROM `welfare_services` WHERE `id` = {$serv_id}")->fetch();
        $cap = $servRow ? intval($servRow['capacity_per_slot']) : 20;

        // بلوک‌های استاندارد سانس روزانه (۲ ساعته)
        // ۸ تا ۱۰، ۱۰ تا ۱۲، (۱۲ تا ۱۴ ناهار مسدود)، ۱۴ تا ۱۶، ۱۶ تا ۱۸، ۱۸ تا ۲۰، ۲۰ تا ۲۲
        $standardSlots = [
            ['start' => '08:00:00', 'end' => '10:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '10:00:00', 'end' => '12:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '12:00:00', 'end' => '14:00:00', 'is_blocked' => 1, 'reason' => 'زمان ناهار، استراحت و نظافت سالن'],
            ['start' => '14:00:00', 'end' => '16:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '16:00:00', 'end' => '18:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '18:00:00', 'end' => '20:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '20:00:00', 'end' => '22:00:00', 'is_blocked' => 0, 'reason' => null],
        ];

        $dayNamesFa = [
            'Saturday'  => 'شنبه',
            'Sunday'    => 'یک‌شنبه',
            'Monday'    => 'دوشنبه',
            'Tuesday'   => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday'  => 'پنج‌شنبه',
            'Friday'    => 'جمعه'
        ];

        $insCount = 0;
        // تولید سانس برای ۷ روز آینده
        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime("+{$i} days");
            $gDate = date('Y-m-d', $ts);
            $dayOfWeek = date('l', $ts);
            $dayFa = $dayNamesFa[$dayOfWeek] ?? 'نامشخص';

            list($jy, $jm, $jd) = gregorian_to_jalali(intval(date('Y', $ts)), intval(date('m', $ts)), intval(date('d', $ts)));
            $jDate = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

            foreach ($standardSlots as $slot) {
                // بررسی عدم وجود قبلی
                $chkSlot = $pdo->prepare("SELECT id FROM `welfare_service_slots` WHERE `service_id` = ? AND `slot_date_gregorian` = ? AND `start_time` = ?");
                $chkSlot->execute([$serv_id, $gDate, $slot['start']]);
                if (!$chkSlot->fetch()) {
                    $insSlot = $pdo->prepare("
                        INSERT INTO `welfare_service_slots`
                            (`service_id`, `slot_date_jalali`, `slot_date_gregorian`, `day_name_fa`, `start_time`, `end_time`, `duration_hours`, `total_capacity`, `booked_count`, `is_blocked`, `block_reason`)
                        VALUES
                            (?, ?, ?, ?, ?, ?, 2, ?, 0, ?, ?)
                    ");
                    $insSlot->execute([$serv_id, $jDate, $gDate, $dayFa, $slot['start'], $slot['end'], $cap, $slot['is_blocked'], $slot['reason']]);
                    $insCount++;
                }
            }
        }
        $msg = "سانس‌های استاندارد سالن بدنسازی برای ۷ روز آینده (شامل تقویم خورشیدی و تایم ناهار ۱۲ تا ۱۴) با موفقیت تولید و همگام گردید ({$insCount} سانس جدید افزوده شد).";
    } catch (Exception $e) {
        $err = "خطا در تولید سانس‌های سالن: " . $e->getMessage();
    }
}

// ۱۲.۳ رزرو اختصاصی سانس برای تیم‌های ورزشی دانشگاه و اتصال هوشمند به فراخوان‌های هدفمند
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_team_reserve_slot'])) {
    $active_tab = 'welfare';
    $slot_id   = intval($_POST['slot_id'] ?? 0);
    $team_name = trim($_POST['target_sports_team'] ?? '');
    $notes     = trim($_POST['team_notes'] ?? 'تمرین اختصاصی و آمادگی جسمانی تیم ورزشی در سالن بدنسازی');

    if ($slot_id > 0 && !empty($team_name)) {
        try {
            $pdo->beginTransaction();

            $slotStmt = $pdo->prepare("SELECT * FROM `welfare_service_slots` WHERE `id` = ? FOR UPDATE");
            $slotStmt->execute([$slot_id]);
            $slot = $slotStmt->fetch();

            if ($slot) {
                // به‌روزرسانی وضعیت سانس به اختصاصی تیم
                $upSlot = $pdo->prepare("
                    UPDATE `welfare_service_slots` SET 
                        `is_team_reserved` = 1,
                        `reserved_team_name` = ?,
                        `team_notes` = ?
                    WHERE `id` = ?
                ");
                $upSlot->execute([$team_name, $notes, $slot_id]);

                // ثبت همزمان در جدول فراخوان‌های هدفمند (festival_calls) تا در پنل دانشجویان عضو تیم ورزشی ظاهر شود
                $callTitle = "🎯 نوبت تمرین اختصاصی {$team_name} در سالن بدنسازی پردیس";
                $callContent = "به اطلاع اعضای محترم {$team_name} می‌رساند، سانس روز {$slot['day_name_fa']} مورخ {$slot['slot_date_jalali']} از ساعت " . substr($slot['start_time'], 0, 5) . " الی " . substr($slot['end_time'], 0, 5) . " به صورت اختصاصی برای تمرین بدنسازی و آمادگی جسمانی تیم شما رزرو گردیده است.\nتوضیحات: {$notes}\nورود برای کلیه اعضای تیم رایگان بوده و همراه داشتن کفش سالنی و حوله الزامی است.";
                
                $insCall = $pdo->prepare("
                    INSERT INTO `festival_calls` 
                        (`title`, `target_category`, `target_sports_team`, `content`, `deadline_date`, `issued_by`, `start_date`, `is_active`)
                    VALUES 
                        (?, 'sports', ?, ?, ?, ?, ?, 1)
                ");
                $mgrName = $_SESSION['admin_name'] ?? 'مدیر امور ورزشی و رفاهی پردیس';
                $insCall->execute([$callTitle, $team_name, $callContent, $slot['slot_date_jalali'], $mgrName, $slot['slot_date_jalali']]);

                $pdo->commit();
                $msg = "سانس {$slot['day_name_fa']} ({$slot['slot_date_jalali']}) با موفقیت برای «{$team_name}» قفل شد و اعلان تمرینی در کارتابل دانشجویان این تیم منتشر گردید.";
            } else {
                $pdo->rollBack();
                $err = "سانس مورد نظر یافت نشد.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در رزرو سانس برای تیم ورزشی: " . $e->getMessage();
        }
    } else {
        $err = "لطفاً سانس و تیم ورزشی مورد نظر را مشخص فرمایید.";
    }
}

// ۱۲.۴ لغو اضطراری نوبت‌های سانس توسط مدیر با عودت خودکار وجه به کیف پول دانشجویان (تفکیک شده از مسدودسازی)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_manager_cancel_slot'])) {
    $active_tab = 'welfare';
    $slot_id = intval($_POST['slot_id'] ?? 0);
    $reason  = trim($_POST['cancellation_reason'] ?? 'لغو اضطراری نوبت‌ها توسط مدیریت');
    $block_too = isset($_POST['block_slot_too']) ? 1 : 0;

    if ($slot_id > 0) {
        try {
            $pdo->beginTransaction();

            $slotStmt = $pdo->prepare("SELECT * FROM `welfare_service_slots` WHERE `id` = ? FOR UPDATE");
            $slotStmt->execute([$slot_id]);
            $slot = $slotStmt->fetch();

            if ($slot) {
                // واکشی تمامی نوبت‌های فعال این سانس
                $bookStmt = $pdo->prepare("SELECT * FROM `welfare_bookings` WHERE `slot_id` = ? AND `status` = 'confirmed' FOR UPDATE");
                $bookStmt->execute([$slot_id]);
                $bookings = $bookStmt->fetchAll();

                $refundCount = 0;
                $totalRefundRials = 0;

                foreach ($bookings as $b) {
                    $refundAmt = intval($b['amount_paid_rials']);
                    if ($refundAmt > 0) {
                        // عودت وجه به کیف پول دانشجو
                        $pdo->prepare("UPDATE `students` SET `wallet_balance` = `wallet_balance` + ? WHERE `student_id` = ?")
                            ->execute([$refundAmt, $b['student_id']]);
                        $totalRefundRials += $refundAmt;
                    }

                    // تغییر وضعیت نوبت به لغو شده همراه با ثبت عودت
                    $pdo->prepare("
                        UPDATE `welfare_bookings` SET 
                            `status` = 'cancelled_by_manager',
                            `refund_status` = 'refunded_to_wallet',
                            `refunded_at` = NOW(),
                            `cancellation_reason` = ?
                        WHERE `id` = ?
                    ")->execute([$reason, $b['id']]);

                    $refundCount++;
                }

                // به‌روزرسانی سانس: نوبت‌ها صفر می‌شود. اگر مدیر مسدودسازی را هم انتخاب کرده باشد مسدود می‌شود
                if ($block_too) {
                    $pdo->prepare("
                        UPDATE `welfare_service_slots` SET 
                            `booked_count` = 0,
                            `is_team_reserved` = 0,
                            `reserved_team_name` = NULL,
                            `is_blocked` = 1,
                            `block_reason` = ?
                        WHERE `id` = ?
                    ")->execute([$reason, $slot_id]);
                    $extraNote = " و این سانس مسدود شد";
                } else {
                    $pdo->prepare("
                        UPDATE `welfare_service_slots` SET 
                            `booked_count` = 0,
                            `is_team_reserved` = 0,
                            `reserved_team_name` = NULL
                        WHERE `id` = ?
                    ")->execute([$slot_id]);
                    $extraNote = " (سانس باز مانده و برای رزروهای جدید فعال است)";
                }

                $pdo->commit();
                $msg = "نوبت‌های سانس مورخ {$slot['slot_date_jalali']} با موفقیت لغو شد. تعداد {$refundCount} نوبت لغو و مجموعاً " . number_format($totalRefundRials) . " ریال به کیف پول دانشجویان عودت داده شد{$extraNote}.";
            } else {
                $pdo->rollBack();
                $err = "سانس مورد نظر یافت نشد.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در لغو نوبت‌های سانس و عودت وجه: " . $e->getMessage();
        }
    }
}

// ۱۲.۵ عملیات مستقل: مسدود کردن یا بازگشایی سانس سالن (بدون لغو نوبت)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle_block_slot'])) {
    $active_tab = 'welfare';
    $slot_id = intval($_POST['slot_id'] ?? 0);
    $set_blocked = intval($_POST['set_blocked'] ?? 0);
    $reason = trim($_POST['block_reason'] ?? 'تعمیرات دوره‌ای و نظافت سالن');

    if ($slot_id > 0) {
        try {
            if ($set_blocked === 1) {
                $pdo->prepare("UPDATE `welfare_service_slots` SET `is_blocked` = 1, `block_reason` = ? WHERE `id` = ?")
                    ->execute([$reason, $slot_id]);
                $msg = "سانس با موفقیت مسدود گردید (دلیل: {$reason}). دانشجویان امکان رزرو جدید نخواهند داشت.";
            } else {
                $pdo->prepare("UPDATE `welfare_service_slots` SET `is_blocked` = 0, `block_reason` = NULL WHERE `id` = ?")
                    ->execute([$slot_id]);
                $msg = "سانس با موفقیت بازگشایی شد و برای نوبت‌گیری عمومی دانشجویان فعال گردید.";
            }
        } catch (Exception $e) {
            $err = "خطا در تغییر وضعیت مسدودی سانس: " . $e->getMessage();
        }
    }
}

// ۱۲.۶ عملیات مستقل: لغو تکی یک نوبت خاص دانشجو همراه با عودت آنی وجه به کیف پول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cancel_single_booking'])) {
    $active_tab = 'welfare';
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $reason = trim($_POST['cancellation_reason'] ?? 'لغو نوبت توسط مدیریت دانشگاه');

    if ($booking_id > 0) {
        try {
            $pdo->beginTransaction();
            $bkStmt = $pdo->prepare("SELECT * FROM `welfare_bookings` WHERE `id` = ? AND `status` = 'confirmed' FOR UPDATE");
            $bkStmt->execute([$booking_id]);
            $bk = $bkStmt->fetch();

            if ($bk) {
                $refundAmt = intval($bk['amount_paid_rials']);
                if ($refundAmt > 0) {
                    $pdo->prepare("UPDATE `students` SET `wallet_balance` = `wallet_balance` + ? WHERE `student_id` = ?")
                        ->execute([$refundAmt, $bk['student_id']]);
                }

                $pdo->prepare("
                    UPDATE `welfare_bookings` SET 
                        `status` = 'cancelled_by_manager',
                        `refund_status` = 'refunded_to_wallet',
                        `refunded_at` = NOW(),
                        `cancellation_reason` = ?
                    WHERE `id` = ?
                ")->execute([$reason, $booking_id]);

                // کاهش شمارنده رزرو سانس مربوطه
                $pdo->prepare("UPDATE `welfare_service_slots` SET `booked_count` = GREATEST(0, `booked_count` - 1) WHERE `id` = ?")
                    ->execute([$bk['slot_id']]);

                $pdo->commit();
                $msg = "نوبت دانشجو «{$bk['student_name']}» (کد پیگیری: {$bk['tracking_code']}) لغو شد و مبلغ " . number_format($refundAmt) . " ریال به کیف پول ایشان عودت داده شد.";
            } else {
                $pdo->rollBack();
                $err = "نوبت فعال مورد نظر یافت نشد یا پیش‌تر لغو گردیده است.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "خطا در لغو نوبت دانشجو: " . $e->getMessage();
        }
    }
}


// =============================================================
// واکشی داده‌های بخش‌های مختلف
// =============================================================

// ۱. واکشی جدول منبع پذیرفته‌شدگان (admitted_students)
$admittedStudents = $pdo->query("SELECT * FROM `admitted_students` ORDER BY `id` DESC")->fetchAll();

// ۲. واکشی دانشجویان متقاضی خوابگاه در انتظار اسکان (فاقد اتاق)
$unhousedStudents = $pdo->query("
    SELECT s.* 
    FROM `students` s
    LEFT JOIN `dorm_allocations` a ON s.student_id = a.student_id AND a.is_active = 1
    WHERE s.request_dormitory = 1 AND a.id IS NULL
    ORDER BY s.id DESC
")->fetchAll();

// ۳. واکشی اتاق‌های خوابگاه و تعداد ساکنین
$allRooms = $pdo->query("
    SELECT r.*,
           COUNT(a.id) AS occupied_beds,
           (r.capacity - COUNT(a.id)) AS free_beds
    FROM `dorm_rooms` r
    LEFT JOIN `dorm_allocations` a ON r.id = a.room_id AND a.is_active = 1
    GROUP BY r.id
    ORDER BY FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC
")->fetchAll();

// ۴. واکشی دانشجویان ساکن و جزئیات اسکان (به همراه استان و شهر برای پرونده کامل)
$housedAllocations = $pdo->query("
    SELECT a.*, s.full_name, s.national_id, s.mobile, s.field_of_study, s.study_shift, s.province, s.city, s.campus,
           r.room_number, r.floor, r.capacity, r.is_active AS room_is_active
    FROM `dorm_allocations` a
    JOIN `students` s ON a.student_id = s.student_id
    JOIN `dorm_rooms` r ON a.room_id = r.id
    WHERE a.is_active = 1
    ORDER BY FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC, s.full_name ASC
")->fetchAll();

// ۵. واکشی سرپرستان خوابگاه و مدیران کل (به همراه روزها و ساعات شیفت)
$supervisors = $pdo->query("SELECT * FROM `admins` WHERE `role` = 'supervisor' ORDER BY `id` DESC")->fetchAll();
$managers = $pdo->query("SELECT * FROM `admins` WHERE `role` = 'manager' ORDER BY `id` DESC")->fetchAll();

// ۷. واکشی داوطلبان جشنواره‌ها
$studentFestivals = [];
try {
    $studentFestivals = $pdo->query("
        SELECT s.student_id, s.full_name, s.national_id, s.mobile, s.campus, s.field_of_study,
               f.festival_key, f.festival_title, f.created_at
        FROM `student_festivals` f
        JOIN `students` s ON f.student_id = s.student_id
        ORDER BY f.id DESC
    ")->fetchAll();
} catch (Exception $e) {}

if (empty($studentFestivals)) {
    $studentFestivals = [
        [
            'student_id' => '4012130045',
            'full_name' => 'علی رضایی مقدم',
            'national_id' => '0021487591',
            'mobile' => '09121234567',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی کامپیوتر - هوش مصنوعی',
            'festival_key' => 'technical',
            'festival_title' => 'جشنواره‌ها و المپیادهای فنی و اختراعات',
            'created_at' => '1404-07-10'
        ],
        [
            'student_id' => '4012130045',
            'full_name' => 'علی رضایی مقدم',
            'national_id' => '0021487591',
            'mobile' => '09121234567',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی کامپیوتر - هوش مصنوعی',
            'festival_key' => 'robotics',
            'festival_title' => 'رویدادهای رباتیک و هوش مصنوعی',
            'created_at' => '1404-07-12'
        ],
        [
            'student_id' => '4012130046',
            'full_name' => 'سارا احمدی',
            'national_id' => '0019845231',
            'mobile' => '09129876543',
            'campus' => 'پردیس مهارت',
            'field_of_study' => 'گرافیک و ارتباط تصویری',
            'festival_key' => 'cultural',
            'festival_title' => 'جشنواره فرهنگی، ادبی و هنری',
            'created_at' => '1404-07-15'
        ],
        [
            'student_id' => '4012130047',
            'full_name' => 'امیرحسین کاظمی',
            'national_id' => '0452318974',
            'mobile' => '09351239988',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی برق - الکترونیک و مخابرات',
            'festival_key' => 'religious',
            'festival_title' => 'مسابقات و جشنواره‌های مذهبی و قرآنی',
            'created_at' => '1404-07-18'
        ],
        [
            'student_id' => '4012130048',
            'full_name' => 'زهرا محمدی',
            'national_id' => '1278965412',
            'mobile' => '09135554433',
            'campus' => 'پردیس مهارت',
            'field_of_study' => 'معماری و دکوراسیون داخلی',
            'festival_key' => 'skillBased',
            'festival_title' => 'جشنواره ملی مهارت و کارآفرینی',
            'created_at' => '1404-07-20'
        ],
        [
            'student_id' => '4012130050',
            'full_name' => 'رضا باقری',
            'national_id' => '0041258963',
            'mobile' => '09127778899',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی مکانیک و مکاترونیک',
            'festival_key' => 'technical',
            'festival_title' => 'جشنواره‌ها و المپیادهای فنی و اختراعات',
            'created_at' => '1404-07-22'
        ]
    ];
}

// ۸. واکشی مهارت‌های دانشجویان
$studentSkillsList = [];
try {
    $studentSkillsList = $pdo->query("
        SELECT s.student_id, s.full_name, s.national_id, s.mobile, s.campus, s.field_of_study,
               GROUP_CONCAT(sk.skill_name SEPARATOR '، ') AS skills_concatenated
        FROM `students` s
        JOIN `student_skills` sk ON s.student_id = sk.student_id
        GROUP BY s.id
        ORDER BY s.id DESC
    ")->fetchAll();
} catch (Exception $e) {}

if (empty($studentSkillsList)) {
    $studentSkillsList = [
        [
            'student_id' => '4012130045',
            'full_name' => 'علی رضایی مقدم',
            'national_id' => '0021487591',
            'mobile' => '09121234567',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی کامپیوتر - هوش مصنوعی و داده',
            'skills_concatenated' => 'پایتون (Python)، یادگیری ماشین، هوش مصنوعی، لینوکس و گیت، طراحی وب'
        ],
        [
            'student_id' => '4012130046',
            'full_name' => 'سارا احمدی',
            'national_id' => '0019845231',
            'mobile' => '09129876543',
            'campus' => 'پردیس مهارت',
            'field_of_study' => 'گرافیک و ارتباط تصویری',
            'skills_concatenated' => 'فتوشاپ، ایلاستریتور، طراحی رابط کاربری (UI/UX)، انیمیشن دو بعدی، تدوین ویدیو'
        ],
        [
            'student_id' => '4012130047',
            'full_name' => 'امیرحسین کاظمی',
            'national_id' => '0452318974',
            'mobile' => '09351239988',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی برق - الکترونیک و مخابرات',
            'skills_concatenated' => 'طراحی مدار الکترونیک، برنامه‌نویسی میکروکنترلر AVR، برق صنعتی، PLC، اتوکد الکتریکال'
        ],
        [
            'student_id' => '4012130048',
            'full_name' => 'زهرا محمدی',
            'national_id' => '1278965412',
            'mobile' => '09135554433',
            'campus' => 'پردیس مهارت',
            'field_of_study' => 'معماری و دکوراسیون داخلی',
            'skills_concatenated' => 'اتوکد (AutoCAD)، تری‌دی مکس (3Ds Max)، طراحی پلان و دکوراسیون، رندرسازی با V-Ray، ماکت‌سازی تخصصی'
        ],
        [
            'student_id' => '4012130049',
            'full_name' => 'مهدی صادقی',
            'national_id' => '0032541896',
            'mobile' => '09194443322',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی صنایع و مدیریت سیستم',
            'skills_concatenated' => 'مدیریت پروژه و MSP، اکسل پیشرفته و داشبورد مدیریتی، کنترل کیفیت آماری، برنامه‌ریزی تولید، پژوهش عملیاتی'
        ],
        [
            'student_id' => '4012130050',
            'full_name' => 'رضا باقری',
            'national_id' => '0041258963',
            'mobile' => '09127778899',
            'campus' => 'پردیس فناوری',
            'field_of_study' => 'مهندسی مکانیک و مکاترونیک',
            'skills_concatenated' => 'سالیدورکس (SolidWorks)، کتیا (CATIA)، مکاترونیک و هیدرولیک، تراشکاری و فرزکاری، تحلیل تنش ANSYS، جوشکاری و تاسیسات'
        ]
    ];
}

// ۶. واکشی داده‌های سرشماری شبانه برای تاریخ جاری
$attendance_date = $_GET['att_date'] ?? date('Y-m-d');
$stmtAttendance = $pdo->prepare("
    SELECT a.student_id, s.full_name, s.national_id, s.field_of_study,
           r.room_number, r.floor,
           COALESCE(att.status, 'حاضر') AS attendance_status,
           att.notes AS attendance_notes,
           att.created_at AS recorded_at
    FROM `dorm_allocations` a
    JOIN `students` s ON a.student_id = s.student_id
    JOIN `dorm_rooms` r ON a.room_id = r.id
    LEFT JOIN `dorm_night_attendance` att ON a.student_id = att.student_id AND att.attendance_date = ?
    WHERE a.is_active = 1
    ORDER BY FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC, s.full_name ASC
");
$stmtAttendance->execute([$attendance_date]);
$attendanceList = $stmtAttendance->fetchAll();

// آمار سرشماری
$totalResidents = count($attendanceList);
$pCount = 0;
$aCount = 0;
$lCount = 0;
foreach ($attendanceList as $att) {
    if ($att['attendance_status'] === 'حاضر') $pCount++;
    elseif ($att['attendance_status'] === 'غایب') $aCount++;
    elseif ($att['attendance_status'] === 'مرخصی') $lCount++;
}
$pPercent = $totalResidents > 0 ? round(($pCount / $totalResidents) * 100) : 100;

// آمار طبقات
$floorStats = [
    'همکف' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'occupied' => 0, 'capacity' => 0],
    'طبقه اول' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'occupied' => 0, 'capacity' => 0],
    'طبقه دوم' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'occupied' => 0, 'capacity' => 0]
];
foreach ($allRooms as $rm) {
    $fl = $rm['floor'];
    if (isset($floorStats[$fl])) {
        $floorStats[$fl]['total']++;
        if ($rm['is_active']) $floorStats[$fl]['active']++;
        else $floorStats[$fl]['inactive']++;
        $floorStats[$fl]['occupied'] += $rm['occupied_beds'];
        $floorStats[$fl]['capacity'] += $rm['capacity'];
    }
}

// ۹. واکشی امکانات ثبت‌شده اتاق‌ها (Room Facilities)
$roomFacilitiesRaw = [];
try {
    $roomFacilitiesRaw = $pdo->query("SELECT * FROM `room_facilities`")->fetchAll();
} catch (Exception $e) {}
$roomFacilities = [];
foreach ($roomFacilitiesRaw as $rf) {
    $roomFacilities[$rf['room_number']] = $rf;
}

// ۱۰. واکشی گزارش‌های خرابی و فاکتورهای مالی (Maintenance Reports)
$maintenanceReports = [];
try {
    $maintenanceReports = $pdo->query("
        SELECT m.*, r.floor 
        FROM `maintenance_reports` m
        LEFT JOIN `dorm_rooms` r ON m.room_number = r.room_number
        ORDER BY m.id DESC
    ")->fetchAll();
} catch (Exception $e) {}

$totalMaintenanceCost = 0;
$pendingMaintCount = 0;
$resolvedMaintCount = 0;
$urgentMaintCount = 0;
foreach ($maintenanceReports as $m) {
    $cost = intval($m['cost_rials'] ?? $m['resolution_cost'] ?? 0);
    $totalMaintenanceCost += $cost;
    $st = $m['status'] ?? 'pending';
    $urg = $m['urgency'] ?? '';
    if ($st === 'pending' || $st === 'در انتظار اقدام' || $st === 'در حال تعمیر') {
        $pendingMaintCount++;
        if ($urg === 'urgent' || $urg === 'فوری') $urgentMaintCount++;
    } else {
        $resolvedMaintCount++;
    }
}

// ۱۱. واکشی داده‌های ارزیابی کیفی نظافت شبانه و محاسبه رتبه‌بندی (Cleanliness Records & Rankings)
$cleanlinessRecords = [];
try {
    $cleanlinessRecords = $pdo->query("
        SELECT c.*, r.floor 
        FROM `room_cleanliness_records` c
        LEFT JOIN `dorm_rooms` r ON c.room_number = r.room_number
        ORDER BY c.inspection_date DESC, c.id DESC
    ")->fetchAll();
} catch (Exception $e) {}

// محاسبه رتبه‌بندی اتاق‌ها بر اساس میانگین نمرات نظافت
$cleanlinessScoresMap = [
    'عالی' => 4, 'خوب' => 3, 'متوسط' => 2, 'ضعیف' => 1,
    'excellent' => 4, 'good' => 3, 'average' => 2, 'poor' => 1
];
$roomCleanStats = [];
foreach ($allRooms as $r) {
    $rn = $r['room_number'];
    $roomCleanStats[$rn] = [
        'room_number' => $rn,
        'floor' => $r['floor'],
        'capacity' => $r['capacity'],
        'total_score' => 0,
        'inspections_count' => 0,
        'excellent_count' => 0,
        'good_count' => 0,
        'average_count' => 0,
        'poor_count' => 0,
        'avg_score' => 0,
        'last_status' => 'عالی',
        'last_date' => '-'
    ];
}

foreach ($cleanlinessRecords as $rec) {
    $rn = $rec['room_number'];
    if (isset($roomCleanStats[$rn])) {
        $q = $rec['grade'] ?? $rec['cleanliness_grade'] ?? 'خوب';
        $score = $cleanlinessScoresMap[$q] ?? 3;
        $roomCleanStats[$rn]['total_score'] += $score;
        $roomCleanStats[$rn]['inspections_count']++;
        if ($q === 'عالی' || $q === 'excellent') $roomCleanStats[$rn]['excellent_count']++;
        elseif ($q === 'خوب' || $q === 'good') $roomCleanStats[$rn]['good_count']++;
        elseif ($q === 'متوسط' || $q === 'average') $roomCleanStats[$rn]['average_count']++;
        elseif ($q === 'ضعیف' || $q === 'poor') $roomCleanStats[$rn]['poor_count']++;
        if ($roomCleanStats[$rn]['inspections_count'] === 1) {
            $roomCleanStats[$rn]['last_status'] = $q;
            $roomCleanStats[$rn]['last_date'] = $rec['inspection_date'];
        }
    }
}

foreach ($roomCleanStats as &$rcs) {
    if ($rcs['inspections_count'] > 0) {
        $rcs['avg_score'] = round($rcs['total_score'] / $rcs['inspections_count'], 2);
    } else {
        $rcs['avg_score'] = 3.5;
    }
}
unset($rcs);

// مرتب‌سازی اتاق‌ها برای رتبه‌بندی از بالاترین امتیاز به پایین‌ترین
$roomRankings = array_values($roomCleanStats);
usort($roomRankings, function($a, $b) {
    if ($b['avg_score'] == $a['avg_score']) {
        return $b['inspections_count'] <=> $a['inspections_count'];
    }
    return ($b['avg_score'] > $a['avg_score']) ? 1 : -1;
});

// میانگین کلی بهداشت خوابگاه
$overallCleanlinessScore = 0;
if (count($roomRankings) > 0) {
    $sumAvg = array_sum(array_column($roomRankings, 'avg_score'));
    $overallCleanlinessScore = round($sumAvg / count($roomRankings), 2);
}

// ۱۲. واکشی فراخوان‌های صادر شده توسط مدیریت (Festival Calls)
$festivalCalls = [];
try {
    $festivalCalls = $pdo->query("SELECT * FROM `festival_calls` ORDER BY `id` DESC")->fetchAll();
} catch (Exception $e) {}

// ۱۳. واکشی اطلاعات سالن بدنسازی، سانس‌ها، رزروها و تیم‌های ورزشی
$welfareGym = null;
$gymSlots = [];
$gymBookings = [];
$allSportsTeams = [];
try {
    $welfareGym = $pdo->query("SELECT * FROM `welfare_services` WHERE `category` = 'gym' LIMIT 1")->fetch();
    if (!$welfareGym) {
        $pdo->exec("
            INSERT INTO `welfare_services` 
                (`title`, `category`, `location`, `hourly_rate_rials`, `slot_duration_hours`, `capacity_per_slot`, `rules_guidelines`, `is_active`)
            VALUES 
                ('سالن بدنسازی و پرورش اندام پردیس', 'gym', 'زیرزمین خوابگاه مرکزی صمد - سالن شماره ۱', 50000, 2, 20, 'همراه داشتن کفش ورزشی تمیز مخصوص سالن، حوله شخصی و بطری آب الزامی است.', 1)
        ");
        $welfareGym = $pdo->query("SELECT * FROM `welfare_services` WHERE `category` = 'gym' LIMIT 1")->fetch();
    }

    // اگر سانسی برای سالن ثبت نشده باشد، به طور خودکار تولید اولیه شود
    $slotCount = $pdo->query("SELECT COUNT(*) FROM `welfare_service_slots` WHERE `service_id` = {$welfareGym['id']}")->fetchColumn();
    if ($slotCount == 0) {
        // تولید سانس برای ۷ روز آینده
        $standardSlots = [
            ['start' => '08:00:00', 'end' => '10:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '10:00:00', 'end' => '12:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '12:00:00', 'end' => '14:00:00', 'is_blocked' => 1, 'reason' => 'زمان ناهار، استراحت و نظافت سالن'],
            ['start' => '14:00:00', 'end' => '16:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '16:00:00', 'end' => '18:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '18:00:00', 'end' => '20:00:00', 'is_blocked' => 0, 'reason' => null],
            ['start' => '20:00:00', 'end' => '22:00:00', 'is_blocked' => 0, 'reason' => null],
        ];
        $dayNamesFa = [
            'Saturday' => 'شنبه', 'Sunday' => 'یک‌شنبه', 'Monday' => 'دوشنبه',
            'Tuesday' => 'سه‌شنبه', 'Wednesday' => 'چهارشنبه', 'Thursday' => 'پنج‌شنبه', 'Friday' => 'جمعه'
        ];
        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime("+{$i} days");
            $gDate = date('Y-m-d', $ts);
            $dayFa = $dayNamesFa[date('l', $ts)] ?? 'نامشخص';
            list($jy, $jm, $jd) = gregorian_to_jalali(intval(date('Y', $ts)), intval(date('m', $ts)), intval(date('d', $ts)));
            $jDate = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            foreach ($standardSlots as $slot) {
                $pdo->prepare("
                    INSERT INTO `welfare_service_slots`
                        (`service_id`, `slot_date_jalali`, `slot_date_gregorian`, `day_name_fa`, `start_time`, `end_time`, `duration_hours`, `total_capacity`, `booked_count`, `is_blocked`, `block_reason`)
                    VALUES (?, ?, ?, ?, ?, ?, 2, ?, 0, ?, ?)
                ")->execute([$welfareGym['id'], $jDate, $gDate, $dayFa, $slot['start'], $slot['end'], $welfareGym['capacity_per_slot'], $slot['is_blocked'], $slot['reason']]);
            }
        }
    }

    $gymSlots = $pdo->query("
        SELECT * FROM `welfare_service_slots` 
        WHERE `service_id` = {$welfareGym['id']} 
        ORDER BY `slot_date_gregorian` ASC, `start_time` ASC
    ")->fetchAll();

    $gymBookings = $pdo->query("
        SELECT b.*, s.mobile, s.field_of_study 
        FROM `welfare_bookings` b
        LEFT JOIN `students` s ON b.student_id = s.student_id
        ORDER BY b.id DESC
    ")->fetchAll();

    // لیست تیم‌های ورزشی موجود در دانشگاه
    $teamsQuery = $pdo->query("SELECT DISTINCT `team_name` FROM `student_sports_teams` WHERE `team_name` IS NOT NULL AND `team_name` != ''")->fetchAll(PDO::FETCH_COLUMN);
    $defaultTeams = ['تیم فوتبال دانشگاه', 'تیم فوتسال پردیس', 'تیم والیبال', 'تیم بسکتبال', 'کشتی آزاد و فرنگی', 'دو و میدانی', 'تکواندو و رزمی', 'تنیس روی میز'];
    $allSportsTeams = array_unique(array_merge($defaultTeams, $teamsQuery));
} catch (Exception $e) {}

// =========================================================================
// مدیریت توکن‌های JWT، ابطال آنی نشست‌ها و ماتریس دسترسی RBAC
// =========================================================================
$jwtSchemaErr = samad_ensure_jwt_schema($pdo, true);
if ($jwtSchemaErr !== null && empty($err)) {
    $err = 'خطا در آماده‌سازی جداول JWT/RBAC: ' . $jwtSchemaErr;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['action_revoke_jwt']) ||
    isset($_POST['action_revoke_role_jwts']) ||
    isset($_POST['action_refresh_my_jwt']) ||
    isset($_POST['action_update_role_permissions'])
)) {
    $active_tab = 'authjwt';
    if (!samad_has_permission($jwtAuth, 'auth:tokens:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], 'manager', $jwtAuth['jti'], 'manager_panel.php', 403, 'مجوز auth:tokens:manage غیرفعال است');
        $err = 'مجوز مدیریت توکن‌های JWT برای حساب شما فعال نیست (403).';
    } else {
        // ۱. ابطال یک توکن مشخص با jti
        if (isset($_POST['action_revoke_jwt'])) {
            $targetJti = trim((string)($_POST['target_jti'] ?? ''));
            $reason = trim((string)($_POST['revoke_reason'] ?? 'ابطال دستی توسط مدیر دانشگاه'));
            if ($reason === '') {
                $reason = 'ابطال دستی توسط مدیر دانشگاه';
            }
            if ($targetJti === $jwtAuth['jti']) {
                $err = 'برای خروج از نشست فعلی خود از دکمه خروج در منو استفاده نمایید.';
            } elseif (samad_revoke_jwt($pdo, $targetJti, (string)$jwtAuth['sub'], $reason)) {
                $msg = 'توکن JWT با شناسه ' . substr($targetJti, 0, 10) . '... با موفقیت ابطال و دسترسی کاربر قطع شد.';
            } else {
                $err = 'توکن مورد نظر یافت نشد یا قبلاً ابطال شده است.';
            }
        }

        // ۲. ابطال دسته‌جمعی توکن‌های فعال یک نقش
        if (isset($_POST['action_revoke_role_jwts'])) {
            $targetRole = trim((string)($_POST['target_role'] ?? ''));
            if (in_array($targetRole, ['student', 'supervisor', 'manager'], true)) {
                try {
                    $stmtMass = $pdo->prepare("
                        UPDATE `auth_jwt_tokens`
                        SET `revoked_at` = NOW(), `revoked_by` = ?, `revoke_reason` = ?
                        WHERE `role` = ? AND `revoked_at` IS NULL AND `expires_at` > ? AND `jti` != ?
                    ");
                    $stmtMass->execute([
                        (string)$jwtAuth['sub'],
                        "ابطال گروهی نشست‌های نقش {$targetRole} توسط مدیر",
                        $targetRole,
                        time(),
                        $jwtAuth['jti'],
                    ]);
                    $cntRevoked = $stmtMass->rowCount();
                    samad_log_auth_event($pdo, 'token_revoked', $jwtAuth['sub'], 'manager', null, 'manager_panel.php', 200, "ابطال {$cntRevoked} توکن فعال نقش {$targetRole}");
                    $msg = "{$cntRevoked} توکن فعال مربوط به نقش «{$targetRole}» با موفقیت لغو گردید.";
                } catch (Exception $e) {
                    $err = 'خطا در ابطال گروهی توکن‌ها: ' . $e->getMessage();
                }
            }
        }

        // ۳. چرخش و تمدید توکن فعلی مدیر
        if (isset($_POST['action_refresh_my_jwt'])) {
            samad_revoke_jwt($pdo, $jwtAuth['jti'], (string)$jwtAuth['sub'], 'چرخش امنیتی کلید نشست توسط مدیر');
            $reissued = samad_issue_jwt($pdo, $jwtAuth['sub'], 'manager', $jwtAuth['name'], false);
            $jwtAuth = samad_verify_jwt($pdo, $reissued['token'], false);
            $msg = 'توکن JWT فعلی شما با موفقیت چرخش داده شد (شناسه جدید: ' . substr($reissued['jti'], 0, 10) . '...).';
        }

        // ۴. بروزرسانی ماتریس مجوزهای نقش (RBAC)
        if (isset($_POST['action_update_role_permissions'])) {
            $editRole = trim((string)($_POST['edit_role'] ?? ''));
            $allowedKeys = $_POST['permissions'] ?? [];
            if (!is_array($allowedKeys)) {
                $allowedKeys = [];
            }
            if (in_array($editRole, ['student', 'supervisor', 'manager'], true)) {
                try {
                    $stmtRolePerms = $pdo->prepare("SELECT `id`, `permission_key` FROM `auth_role_permissions` WHERE `role` = ?");
                    $stmtRolePerms->execute([$editRole]);
                    $rolePermRows = $stmtRolePerms->fetchAll(PDO::FETCH_ASSOC);

                    $updPerm = $pdo->prepare("UPDATE `auth_role_permissions` SET `is_allowed` = ? WHERE `id` = ?");
                    foreach ($rolePermRows as $rp) {
                        $permKey = $rp['permission_key'];
                        // محافظت از حذف تصادفی مجوز حیاتی مدیر
                        if ($editRole === 'manager' && in_array($permKey, ['auth:tokens:manage', 'dorm:rooms:manage'], true)) {
                            $isAllowed = 1;
                        } else {
                            $isAllowed = in_array($permKey, $allowedKeys, true) ? 1 : 0;
                        }
                        $updPerm->execute([$isAllowed, $rp['id']]);
                    }
                    $jwtAuth['permissions'] = samad_get_role_permissions($pdo, 'manager');
                    samad_log_auth_event($pdo, 'permission_updated', $jwtAuth['sub'], 'manager', null, 'manager_panel.php', 200, "بروزرسانی ماتریس مجوزهای RBAC برای نقش {$editRole}");
                    $msg = "ماتریس مجوزهای دسترسی (RBAC) برای نقش «{$editRole}» ذخیره و فوراً اعمال شد.";
                } catch (Exception $e) {
                    $err = 'خطا در ذخیره مجوزها: ' . $e->getMessage();
                }
            }
        }
    }
}

// واکشی داده‌های کنسول مدیریت JWT و RBAC
$jwtTokensList = [];
$jwtActiveCount = 0;
$jwtRevokedCount = 0;
$rbacRowsByRole = ['student' => [], 'supervisor' => [], 'manager' => []];
$rbacActiveCount = 0;
$authAuditLogs = [];

try {
    $jwtTokensList = $pdo->query("
        SELECT * FROM `auth_jwt_tokens`
        ORDER BY `id` DESC
        LIMIT 80
    ")->fetchAll(PDO::FETCH_ASSOC);

    $nowTs = time();
    foreach ($jwtTokensList as $tRow) {
        if (empty($tRow['revoked_at']) && (int)$tRow['expires_at'] > $nowTs) {
            $jwtActiveCount++;
        } elseif (!empty($tRow['revoked_at'])) {
            $jwtRevokedCount++;
        }
    }

    $allRbacRows = $pdo->query("SELECT * FROM `auth_role_permissions` ORDER BY FIELD(`role`, 'student', 'supervisor', 'manager'), `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allRbacRows as $rRow) {
        $rbacRowsByRole[$rRow['role']][] = $rRow;
        if ((int)$rRow['is_allowed'] === 1) {
            $rbacActiveCount++;
        }
    }

    $authAuditLogs = $pdo->query("SELECT * FROM `auth_audit_logs` ORDER BY `id` DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if (empty($err)) {
        $err = 'خطا در واکشی اطلاعات جدول مجوزها و توکن‌های JWT: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیشخوان مدیریت - دانشگاه ملی مهارت واحد پسران نیشابور</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="assets/tailwind/tailwind.css">
    <link rel="stylesheet" href="assets/portal-theme.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            body { background: white !important; color: black !important; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .print-break { page-break-after: always; }
        }
    </style>
</head>
<body class="<?= ($currentTheme === 'light') ? 'theme-light' : 'theme-dark' ?> bg-[#0b0f19] text-slate-100 min-h-screen flex flex-col md:flex-row antialiased">

    <!-- ===================== SIDEBAR (RIGHT SIDE - RTL) ===================== -->
    <aside id="mainSidebar" class="hidden md:flex w-full md:w-64 lg:w-72 bg-[#0f172a] border-l border-slate-800 flex-col shrink-0 no-print shadow-2xl z-30">
        <!-- Logo & Branding -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-700 flex items-center justify-center font-black text-white text-base shadow-lg shadow-indigo-500/20 border border-indigo-400/30">
                    صمد
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-white tracking-wide">سامانه هوشمند صمد</h1>
                    <span class="text-[10px] text-indigo-400 font-medium block">پنل مدیریت دانشگاه (WAMP)</span>
                </div>
            </div>
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse" title="آنلاین"></span>
        </div>

        <!-- Manager Quick Profile Snapshot -->
        <div class="p-4 mx-3 my-3 bg-gradient-to-br from-slate-900 to-indigo-950/40 rounded-2xl border border-slate-800 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold">
                    👨‍💼
                </div>
                <div class="flex-1 min-w-0">
                    <span class="text-xs font-bold text-white block truncate"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'دکتر علیرضا حسینی') ?></span>
                    <span class="text-[10px] text-slate-400 block truncate">مدیر امور دانشجویی دانشگاه</span>
                </div>
            </div>
        </div>

        <!-- Navigation Menu Tabs -->
        <nav class="flex-1 px-3 py-2 space-y-1.5 overflow-y-auto">
            <button type="button" onclick="switchTab('overview')" id="nav-overview" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>📊</span>
                    <span>پیشخوان و آمار کلی</span>
                </div>
            </button>

            <button type="button" onclick="switchTab('admitted')" id="nav-admitted" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🎓</span>
                    <span>پذیرش جدید (جدول منبع)</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-mono">
                    <?= count($admittedStudents) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('unhoused')" id="nav-unhoused" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>⏳</span>
                    <span>متقاضیان خوابگاه (صف اسکان)</span>
                </div>
                <?php if (count($unhousedStudents) > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono">
                        <?= count($unhousedStudents) ?>
                    </span>
                <?php endif; ?>
            </button>

            <button type="button" onclick="switchTab('rooms')" id="nav-rooms" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🏢</span>
                    <span>مدیریت اتاق‌ها و طبقات</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 font-mono">
                    <?= count($allRooms) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('dormgraph')" id="nav-dormgraph" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🗺️</span>
                    <span>نقشه تعاملی خوابگاه‌ها (Graph)</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/20 text-teal-300 font-mono">
                    SRS v1.0
                </span>
            </button>

            <button type="button" onclick="switchTab('facilities')" id="nav-facilities" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🛋</span>
                    <span>امکانات و تجهیزات اتاق‌ها</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 font-mono">
                    <?= count(array_filter($allRooms, fn($r) => !empty($r['is_completed']))) ?>/<?= count($allRooms) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('maintenance')" id="nav-maintenance" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🔧</span>
                    <span>کارتابل خرابی و فاکتورها</span>
                </div>
                <?php if ($pendingMaintCount > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 font-mono animate-pulse">
                        <?= $pendingMaintCount ?> اقدام
                    </span>
                <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 font-mono">
                        <?= count($maintenanceReports) ?>
                    </span>
                <?php endif; ?>
            </button>

            <button type="button" onclick="switchTab('cleanliness')" id="nav-cleanliness" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>✨</span>
                    <span>ارزیابی نظافت و رتبه‌بندی</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 font-mono">
                    <?= $overallCleanlinessScore ?>/۴
                </span>
            </button>

            <button type="button" onclick="switchTab('students')" id="nav-students" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🛏</span>
                    <span>دانشجویان ساکن و تغییر اتاق</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 font-mono">
                    <?= count($housedAllocations) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('attendance')" id="nav-attendance" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>📋</span>
                    <span>سرشماری و حضور و غیاب</span>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                    پرینت 🖨
                </span>
            </button>

            <button type="button" onclick="switchTab('supervisors')" id="nav-supervisors" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🛡</span>
                    <span>مدیریت سرپرستان و شیفت‌ها</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 font-mono">
                    <?= count($supervisors) + count($managers) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('festivals')" id="nav-festivals" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🏆</span>
                    <span>جشنواره‌ها و داوطلبان</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-300 font-mono">
                    <?= count($studentFestivals) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('skills')" id="nav-skills" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>✨</span>
                    <span>بانک مهارت‌ها (جستجوی زنده)</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 font-mono">
                    <?= count($studentSkillsList) ?>
                </span>
            </button>

            <button type="button" onclick="switchTab('welfare')" id="nav-welfare" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🏋️</span>
                    <span>خدمات رفاهی و سالن بدنسازی</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 font-mono">
                    <?= count($gymSlots) ?> سانس
                </span>
            </button>

            <button type="button" onclick="switchTab('authjwt')" id="nav-authjwt" class="tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span>🔐</span>
                    <span>مدیریت دسترسی و توکن (JWT)</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 font-mono tabular-nums">
                    <?= $jwtActiveCount ?> فعال
                </span>
            </button>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800 space-y-2">
            <a href="logout.php" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600/15 hover:bg-rose-600 text-rose-300 hover:text-white rounded-xl text-xs font-bold transition-all border border-rose-500/30">
                <span>✕ خروج از حساب مدیریت</span>
            </a>
        </div>
    </aside>

    <!-- ===================== MAIN CONTENT WRAPPER ===================== -->
    <main class="flex-1 flex flex-col min-w-0 bg-[#0b0f19] overflow-y-auto">
        <!-- Top Navbar -->
        <header class="h-16 px-4 sm:px-6 bg-[#0f172a]/90 backdrop-blur-md border-b border-slate-800 flex items-center justify-between sticky top-0 z-20 no-print flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebarMobile()" class="md:hidden p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white cursor-pointer text-sm">
                    ☰
                </button>
                <h2 id="top-title" class="text-xs sm:text-sm font-bold text-white">پیشخوان و داشبورد مدیریتی صمد</h2>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                <button type="button" onclick="switchTab('authjwt')" class="px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-slate-700 text-[11px] font-mono tabular-nums rounded-xl transition-all hidden lg:flex items-center gap-1.5 cursor-pointer" title="وضعیت توکن احراز هویت JWT">
                    <span>JWT:</span>
                    <span><?= htmlspecialchars(substr($jwtAuth['jti'], 0, 8)) ?></span>
                    <span class="text-slate-500">·</span>
                    <span class="text-emerald-400"><?= htmlspecialchars(samad_format_remaining_ttl((int)$jwtAuth['exp'])) ?></span>
                </button>
                <!-- میانبر تعاملی به نقش‌های دیگر سامانه -->
                <a href="supervisor_panel.php" class="px-2.5 py-1.5 bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/30 text-xs font-bold rounded-xl transition-all flex items-center gap-1">
                    <span>🌙</span>
                    <span class="hidden sm:inline">پنل سرپرست شب</span>
                </a>
                <a href="dashboard.php" class="px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-bold rounded-xl transition-all flex items-center gap-1">
                    <span>🎓</span>
                    <span class="hidden sm:inline">میزکار دانشجو</span>
                </a>
                <a href="index.php" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-xl transition-all hidden md:flex items-center gap-1">
                    <span>🌐</span>
                    <span>پورتال اصلی</span>
                </a>
                <!-- کلید تغییر تم سراسری دارک و لایت -->
                <button type="button" onclick="togglePortalTheme()" id="globalThemeToggleBtn" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm" title="سوییچ بین حالت تاریک و روشن">
                    <span id="themeToggleIcon"><?= ($currentTheme === 'light') ? '🌙' : '☀️' ?></span>
                    <span id="themeToggleText"><?= ($currentTheme === 'light') ? 'تم دارک' : 'تم لایت' ?></span>
                </button>
                <button type="button" onclick="switchTab('attendance'); setTimeout(openBatchPrintModal, 200);" class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                    <span>🖨</span>
                    <span class="hidden sm:inline">چاپ سرشماری A4</span>
                </button>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-6 space-y-6">
            <?php if (!empty($msg)): ?>
                <div class="p-4 bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-lg no-print">
                    <span>✓</span>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($err)): ?>
                <div class="p-4 bg-rose-950/80 border border-rose-500/50 text-rose-200 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-lg no-print">
                    <span>✕</span>
                    <span><?= htmlspecialchars($err) ?></span>
                </div>
            <?php endif; ?>

            <!-- ===================== TAB 1: OVERVIEW ===================== -->
            <div id="tab-overview" class="tab-pane space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg">
                        <span class="text-xs text-slate-400 block mb-1">دانشجویان اسکان‌یافته</span>
                        <span class="text-2xl font-black text-white font-mono"><?= count($housedAllocations) ?> نفر</span>
                        <span class="text-[11px] text-indigo-400 block mt-2">از کل ظرفیت خوابگاه صمد</span>
                    </div>

                    <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg">
                        <span class="text-xs text-slate-400 block mb-1">متقاضیان در انتظار اسکان</span>
                        <span class="text-2xl font-black text-amber-400 font-mono"><?= count($unhousedStudents) ?> نفر</span>
                        <span class="text-[11px] text-amber-300/80 block mt-2">نیازمند تخصیص فوری اتاق</span>
                    </div>

                    <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg">
                        <span class="text-xs text-slate-400 block mb-1">کل اتاق‌های فعال خوابگاه</span>
                        <span class="text-2xl font-black text-emerald-400 font-mono">
                            <?= count(array_filter($allRooms, fn($r) => $r['is_active'])) ?> اتاق
                        </span>
                        <span class="text-[11px] text-emerald-300/80 block mt-2">در طبقات همکف، ۱ و ۲</span>
                    </div>

                    <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg">
                        <span class="text-xs text-slate-400 block mb-1">درصد حضور در آخرین سرشماری</span>
                        <span class="text-2xl font-black text-rose-400 font-mono">٪<?= $pPercent ?></span>
                        <span class="text-[11px] text-rose-300/80 block mt-2"><?= $pCount ?> حاضر از <?= $totalResidents ?> نفر</span>
                    </div>
                </div>

                <!-- وضعیت طبقات -->
                <div class="bg-[#1e293b]/50 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-white">وضعیت طبقات ساختمان خوابگاه مرکزی صمد (همکف، ۱ و ۲)</h3>
                        <button type="button" onclick="switchTab('rooms')" class="text-xs text-indigo-400 font-bold hover:underline">مشاهده جزئیات اتاق‌ها ←</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($floorStats as $flName => $st): ?>
                            <div class="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-white"><?= $flName ?></span>
                                    <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 text-xs font-bold rounded font-mono"><?= $st['total'] ?> اتاق</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="p-2 bg-slate-800/60 rounded">اتاق فعال: <strong class="text-emerald-400 font-mono"><?= $st['active'] ?></strong></div>
                                    <div class="p-2 bg-slate-800/60 rounded">اتاق غیرفعال: <strong class="text-rose-400 font-mono"><?= $st['inactive'] ?></strong></div>
                                    <div class="p-2 bg-slate-800/60 rounded">ساکنین: <strong class="text-indigo-300 font-mono"><?= $st['occupied'] ?></strong></div>
                                    <div class="p-2 bg-slate-800/60 rounded">ظرفیت کل: <strong class="text-amber-400 font-mono"><?= $st['capacity'] ?></strong></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- کارت‌های وضعیت تعمیرات، بهداشت و تجهیزات اتاق‌ها -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-[#1e293b]/70 border border-slate-800 hover:border-rose-500/40 rounded-2xl p-5 shadow-lg space-y-3 transition-all">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <span>🔧</span>
                                <span>کارتابل خرابی و تعمیرات</span>
                            </span>
                            <?php if ($pendingMaintCount > 0): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 font-mono animate-pulse">
                                    <?= $pendingMaintCount ?> نیازمند اقدام
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 font-mono">
                                    وضعیت پایدار ✓
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1">
                            <span class="text-2xl font-black text-white font-mono"><?= number_format($totalMaintenanceCost) ?> <span class="text-xs font-normal text-slate-400">ریال</span></span>
                            <span class="text-[11px] text-slate-400 block">کل هزینه‌های فاکتور شده تعمیر و تجهیز</span>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400"><?= $resolvedMaintCount ?> مورد رفع‌شده</span>
                            <button type="button" onclick="switchTab('maintenance')" class="text-xs text-rose-400 hover:text-rose-300 font-bold cursor-pointer">
                                ورود به کارتابل ←
                            </button>
                        </div>
                    </div>

                    <div class="bg-[#1e293b]/70 border border-slate-800 hover:border-emerald-500/40 rounded-2xl p-5 shadow-lg space-y-3 transition-all">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <span>✨</span>
                                <span>کیفیت نظافت شبانه</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono">
                                میانگین <?= $overallCleanlinessScore ?> از ۴.۰
                            </span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-2xl font-black text-emerald-400 font-mono"><?= round(($overallCleanlinessScore / 4) * 100) ?>٪</span>
                            <span class="text-[11px] text-slate-400 block">شاخص بهداشت و پاکیزگی خوابگاه صمد</span>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400"><?= count($cleanlinessRecords) ?> نوبت ارزیابی ثبت شده</span>
                            <button type="button" onclick="switchTab('cleanliness')" class="text-xs text-emerald-400 hover:text-emerald-300 font-bold cursor-pointer">
                                رتبه‌بندی و چاپ کارنامه ←
                            </button>
                        </div>
                    </div>

                    <div class="bg-[#1e293b]/70 border border-slate-800 hover:border-amber-500/40 rounded-2xl p-5 shadow-lg space-y-3 transition-all">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <span>🛋</span>
                                <span>امکانات و تجهیزات اتاق‌ها</span>
                            </span>
                            <?php $completedCount = count(array_filter($allRooms, fn($r) => !empty($r['is_completed']))); ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono">
                                <?= $completedCount ?> از <?= count($allRooms) ?> تکمیل
                            </span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-2xl font-black text-amber-400 font-mono"><?= count($allRooms) > 0 ? round(($completedCount / count($allRooms)) * 100) : 0 ?>٪</span>
                            <span class="text-[11px] text-slate-400 block">درصد اتاق‌های دارای شناسنامه تجهیزات</span>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400"><?= count($allRooms) - $completedCount ?> اتاق نیازمند تکمیل</span>
                            <button type="button" onclick="switchTab('facilities')" class="text-xs text-amber-400 hover:text-amber-300 font-bold cursor-pointer">
                                تکمیل اطلاعات اتاق‌ها ←
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 2: ADMITTED STUDENTS ===================== -->
            <div id="tab-admitted" class="tab-pane hidden space-y-6">
                <!-- فرم افزودن پذیرفته شده جدید -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-white">افزودن داوطلب قبولی جدید به جدول منبع (admitted_students)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">دانشجو در مرحله ۱ ثبت‌نام، با استعلام کدملی و شماره دانشجویی از این جدول احراز هویت می‌شود</p>
                    </div>

                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 text-xs">
                        <input type="hidden" name="action_add_admitted" value="1">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">کد ملی ده رقمی *</label>
                            <input type="text" name="national_id" maxlength="10" placeholder="مثال: 0098765432" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono outline-none focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">شماره دانشجویی *</label>
                            <input type="text" name="student_id" placeholder="مثال: 4012130099" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono outline-none focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">نام و نام خانوادگی *</label>
                            <input type="text" name="full_name" placeholder="مثال: مسعود شجاعی" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">پردیس قبولی *</label>
                            <select name="campus" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none focus:border-cyan-500">
                                <option value="پردیس فناوری">پردیس فناوری</option>
                                <option value="پردیس مهارت">پردیس مهارت</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">رشته قبولی *</label>
                            <input type="text" name="field_of_study" placeholder="مثال: مهندسی برق" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none focus:border-cyan-500">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-5 flex justify-end pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                                + ثبت داوطلب در جدول منبع
                            </button>
                        </div>
                    </form>
                </div>

                <!-- جدول پذیرفته شدگان -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white">فهرست پذیرفته‌شدگان آزمون در پایگاه داده (admitted_students)</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">امکان جستجوی سریع بر اساس کدملی، شماره دانشجویی، نام یا رشته</p>
                        </div>
                        <span class="px-2.5 py-1 bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 rounded-full text-xs font-bold font-mono">
                            <?= count($admittedStudents) ?> داوطلب
                        </span>
                    </div>

                    <!-- جعبه جستجو -->
                    <div class="relative">
                        <input type="text" id="admittedSearchInput" oninput="filterAdmittedTable()" placeholder="🔍 جستجو بر اساس کد ملی، شماره دانشجویی، نام یا رشته قبولی..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-cyan-500 transition-all shadow-inner">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="admittedTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">ردیف</th>
                                    <th class="p-3">نام و نام خانوادگی</th>
                                    <th class="p-3">کد ملی</th>
                                    <th class="p-3">شماره دانشجویی</th>
                                    <th class="p-3">پردیس و رشته قبولی</th>
                                    <th class="p-3">وضعیت ثبت‌نام در سامانه</th>
                                    <th class="p-3 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php foreach ($admittedStudents as $idx => $adm): ?>
                                    <tr class="hover:bg-slate-800/40 admitted-row" data-search="<?= htmlspecialchars(strtolower($adm['full_name'] . ' ' . $adm['national_id'] . ' ' . $adm['student_id'] . ' ' . $adm['field_of_study'])) ?>">
                                        <td class="p-3 font-mono text-slate-400"><?= $idx + 1 ?></td>
                                        <td class="p-3 font-bold text-white"><?= htmlspecialchars($adm['full_name']) ?></td>
                                        <td class="p-3 font-mono text-cyan-300"><?= htmlspecialchars($adm['national_id']) ?></td>
                                        <td class="p-3 font-mono text-amber-300 font-bold"><?= htmlspecialchars($adm['student_id']) ?></td>
                                        <td class="p-3">
                                            <span class="block text-slate-200"><?= htmlspecialchars($adm['field_of_study']) ?></span>
                                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($adm['campus']) ?></span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($adm['is_registered']): ?>
                                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">ثبت‌نام شده ✓</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded text-[10px] font-bold">در انتظار ثبت‌نام ⏳</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <form method="POST" onsubmit="return confirm('آیا از حذف داوطلب از جدول منبع اطمینان دارید؟');" class="inline">
                                                <input type="hidden" name="action_delete_admitted" value="1">
                                                <input type="hidden" name="admitted_id" value="<?= $adm['id'] ?>">
                                                <button type="submit" class="text-slate-400 hover:text-rose-400 text-xs">حذف ✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 3: UNHOUSED APPLICANTS ===================== -->
            <div id="tab-unhoused" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white">دانشجویان متقاضی خوابگاه در انتظار اسکان (فاقد اتاق)</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">امکان اسکان فوری دانشجو به همراه تعیین زمان میهمانی با واحد روز و جستجو بر اساس کدملی</p>
                        </div>
                        <span class="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full text-xs font-bold font-mono">
                            <?= count($unhousedStudents) ?> متقاضی
                        </span>
                    </div>

                    <!-- جعبه جستجو -->
                    <div class="relative">
                        <input type="text" id="unhousedSearchInput" oninput="filterUnhousedTable()" placeholder="🔍 جستجو بر اساس کد ملی، شماره دانشجویی، نام دانشجو، استان یا شهر..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500 transition-all shadow-inner">
                    </div>

                    <?php if (empty($unhousedStudents)): ?>
                        <div class="p-8 text-center bg-slate-900/60 rounded-xl border border-dashed border-slate-700">
                            <span class="text-3xl block mb-2">✓</span>
                            <p class="text-sm font-bold text-white">تمامی متقاضیان خوابگاه در حال حاضر اسکان داده شده‌اند.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs" id="unhousedTable">
                                <thead>
                                    <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                        <th class="p-3">نام دانشجو</th>
                                        <th class="p-3">شماره دانشجویی / کدملی</th>
                                        <th class="p-3">رشته و نوبت تحصیلی</th>
                                        <th class="p-3">محل سکونت</th>
                                        <th class="p-3">توضیحات دانشجو</th>
                                        <th class="p-3 text-center">عملیات اسکان</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($unhousedStudents as $std): ?>
                                        <tr class="hover:bg-slate-800/40 unhoused-row" data-search="<?= htmlspecialchars(strtolower($std['full_name'] . ' ' . $std['national_id'] . ' ' . $std['student_id'] . ' ' . $std['province'] . ' ' . $std['city'] . ' ' . $std['field_of_study'])) ?>">
                                            <td class="p-3 font-bold text-white"><?= htmlspecialchars($std['full_name']) ?></td>
                                            <td class="p-3 font-mono">
                                                <span class="text-indigo-400 font-bold block"><?= htmlspecialchars($std['student_id']) ?></span>
                                                <span class="text-cyan-300 text-[10px]"><?= htmlspecialchars($std['national_id']) ?></span>
                                            </td>
                                            <td class="p-3">
                                                <span class="text-slate-200 block"><?= htmlspecialchars($std['field_of_study']) ?></span>
                                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($std['study_shift']) ?></span>
                                            </td>
                                            <td class="p-3 text-slate-300"><?= htmlspecialchars($std['province']) ?> (<?= htmlspecialchars($std['city']) ?>)</td>
                                            <td class="p-3 text-[11px] text-slate-400"><?= htmlspecialchars($std['dormitory_note'] ?? 'متقاضی خوابگاه') ?></td>
                                            <td class="p-3 text-center">
                                                <button type="button" onclick="openAssignModal('<?= htmlspecialchars($std['student_id']) ?>', '<?= htmlspecialchars($std['full_name']) ?>', '<?= htmlspecialchars($std['study_shift']) ?>')" class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                                                    اسکان در اتاق ←
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===================== TAB: DORMITORY GRAPH (مدیر دانشگاه) ===================== -->
            <div id="tab-dormgraph" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-white">مانیتورینگ جامع و نقشه گراف تعاملی کل خوابگاه‌ها (React Graph)</h3>
                                <span class="px-2 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/30 text-[10px] font-bold">
                                    نمایش کل ساختار، طبقات و همه ساکنان
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">
                                مشاهده یکپارچه خوابگاه، ساختمان، تفکیک طبقه به طبقه (همکف، طبقه اول، طبقه دوم)، تمامی اتاق‌ها و کلیه دانشجویان ساکن هر اتاق بدون تداخل گره‌ها
                            </p>
                        </div>

                        <div class="flex items-center gap-2 text-xs flex-wrap">
                            <button type="button" onclick="const f = document.getElementById('manager-dorm-graph-iframe'); if (f) f.src = f.src;" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                🔄 همگام‌سازی مجدد با پایگاه‌داده
                            </button>
                            <a href="dorm_graph/index.html?mode=php&role=manager" target="_blank" class="px-3 py-1.5 bg-gradient-to-r from-teal-600 to-indigo-600 hover:from-teal-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                                <span>🗺️</span>
                                <span>باز کردن گراف در پنجره تمام‌صفحه ↗</span>
                            </a>
                        </div>
                    </div>

                    <!-- بوم تعاملی گراف تعبیه‌شده React متصل به MySQL -->
                    <div class="w-full h-[740px] rounded-2xl overflow-hidden border border-slate-800 bg-[#141414] shadow-2xl">
                        <iframe
                            id="manager-dorm-graph-iframe"
                            src="dorm_graph/index.html?mode=php&role=manager&embed=1"
                            title="گراف جامع مدیریت خوابگاه صمد"
                            class="w-full h-full border-0"
                        ></iframe>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 4: ROOMS ===================== -->
            <div id="tab-rooms" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white">مدیریت و نظارت بر اتاق‌های خوابگاه و طبقات</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">کلیک بر روی نام اتاق جهت مشاهده کامل پرونده و عکس دانشجویان</p>
                        </div>
                        <span class="px-2.5 py-1 bg-slate-800 text-slate-300 rounded-full text-xs font-mono"><?= count($allRooms) ?> اتاق</span>
                    </div>

                    <!-- Floor & Status Filters and Occupant Search -->
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between pt-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">فیلتر طبقه:</span>
                            <button type="button" onclick="setRoomFloorFilter('all', this)" class="room-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setRoomFloorFilter('همکف', this)" class="room-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">همکف</button>
                            <button type="button" onclick="setRoomFloorFilter('طبقه اول', this)" class="room-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه اول</button>
                            <button type="button" onclick="setRoomFloorFilter('طبقه دوم', this)" class="room-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه دوم</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">وضعیت:</span>
                            <button type="button" onclick="setRoomStatusFilter('all', this)" class="room-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setRoomStatusFilter('active', this)" class="room-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">فعال</button>
                            <button type="button" onclick="setRoomStatusFilter('inactive', this)" class="room-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">غیرفعال</button>
                        </div>
                    </div>

                    <!-- فیلد جستجوی اتاق و ساکنین با کدملی -->
                    <div class="relative">
                        <input type="text" id="roomsSearchInput" oninput="filterRoomsTable()" placeholder="🔍 جستجو در اتاق‌ها و ساکنین (کد ملی، شماره دانشجویی، نام دانشجو یا شماره اتاق)..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-emerald-500 transition-all shadow-inner">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="roomsTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">شماره اتاق</th>
                                    <th class="p-3">طبقه</th>
                                    <th class="p-3">وضعیت فعالیت</th>
                                    <th class="p-3">شناسنامه امکانات</th>
                                    <th class="p-3">ظرفیت / تخت خالی</th>
                                    <th class="p-3">توضیحات</th>
                                    <th class="p-3 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php foreach ($allRooms as $rm): ?>
                                    <?php
                                        $occStr = '';
                                        foreach ($housedAllocations as $ha) {
                                            if ($ha['room_id'] == $rm['id']) {
                                                $occStr .= ' ' . $ha['full_name'] . ' ' . $ha['national_id'] . ' ' . $ha['student_id'];
                                            }
                                        }
                                        $occStr = strtolower(trim($occStr));
                                    ?>
                                    <tr class="hover:bg-slate-800/40 room-row" data-floor="<?= htmlspecialchars($rm['floor']) ?>" data-active="<?= $rm['is_active'] ? '1' : '0' ?>" data-room="<?= htmlspecialchars($rm['room_number']) ?>" data-occupants="<?= htmlspecialchars($occStr) ?>">
                                        <td class="p-3 font-bold font-mono text-emerald-400 text-sm">
                                            <button type="button" onclick="openRoomDetailsModal('<?= htmlspecialchars($rm['room_number']) ?>', '<?= htmlspecialchars($rm['floor']) ?>', <?= $rm['capacity'] ?>)" class="text-emerald-400 hover:text-emerald-300 hover:underline flex items-center gap-1 cursor-pointer" title="کلیک جهت مشاهده کامل پرونده و عکس دانشجویان این اتاق">
                                                <span>اتاق <?= htmlspecialchars($rm['room_number']) ?></span>
                                                <span class="text-[10px] text-emerald-400">👁</span>
                                            </button>
                                        </td>
                                        <td class="p-3 text-slate-300"><?= htmlspecialchars($rm['floor']) ?></td>
                                        <td class="p-3">
                                            <?php if ($rm['is_active']): ?>
                                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">فعال و دایر ✓</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold">غیرفعال (تعمیرات) ⚠️</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <?php if (!empty($rm['is_completed'])): ?>
                                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">
                                                    تکمیل شده ✓
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded text-[10px] font-bold">
                                                    تکمیل‌نشده ⚠️
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 font-mono">
                                            <span class="text-white font-bold"><?= $rm['occupied_beds'] ?></span> از <?= $rm['capacity'] ?> نفر
                                            <?php if ($rm['occupied_beds'] > $rm['capacity']): ?>
                                                <span class="px-1.5 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold font-mono">
                                                    مازاد (+<?= $rm['occupied_beds'] - $rm['capacity'] ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-amber-400 font-bold">(<?= $rm['free_beds'] ?> تخت خالی)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-[11px] text-slate-400"><?= htmlspecialchars($rm['description'] ?? '-') ?></td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <button type="button" onclick="openFacilitiesModal('<?= htmlspecialchars($rm['room_number']) ?>', '<?= htmlspecialchars($rm['floor']) ?>')" class="px-2 py-1 rounded text-[10px] font-bold bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 transition-all cursor-pointer" title="تکمیل و ویرایش شناسنامه امکانات اتاق">
                                                    امکانات 🛋
                                                </button>

                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action_toggle_room" value="1">
                                                    <input type="hidden" name="room_id" value="<?= $rm['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $rm['is_active'] ?>">
                                                    <button type="submit" class="px-2 py-1 rounded text-[10px] font-bold <?= $rm['is_active'] ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300' ?> cursor-pointer">
                                                        <?= $rm['is_active'] ? 'غیرفعال' : 'فعال' ?>
                                                    </button>
                                                </form>

                                                <form method="POST" onsubmit="return confirm('آیا از حذف اتاق اطمینان دارید؟ ساکنین به لیست بدون اسکان منتقل می‌شوند.');" class="inline">
                                                    <input type="hidden" name="action_delete_room" value="1">
                                                    <input type="hidden" name="room_id" value="<?= $rm['id'] ?>">
                                                    <button type="submit" class="text-slate-400 hover:text-rose-400 text-xs cursor-pointer p-1">✕</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- فرم افزودن اتاق در پایین جدول -->
                    <div class="pt-4 border-t border-slate-800">
                        <form method="POST" class="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-3">
                            <input type="hidden" name="action_add_room" value="1">
                            <span class="text-xs font-bold text-white block">افزودن اتاق جدید به خوابگاه:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <label class="block text-slate-400 mb-1">شماره اتاق *</label>
                                    <input type="text" name="room_number" placeholder="مثال: 105" required class="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg font-mono">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">طبقه استقرار *</label>
                                    <select name="floor" class="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg">
                                        <option value="همکف">همکف</option>
                                        <option value="طبقه اول">طبقه اول</option>
                                        <option value="طبقه دوم">طبقه دوم</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ظرفیت تخت (دل‌بخواهی) *</label>
                                    <input type="number" name="capacity" id="new_room_capacity_input" value="4" min="1" required class="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg font-mono">
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <?php foreach ([2, 4, 6, 8, 10, 12, 16] as $cb): ?>
                                            <button type="button" onclick="document.getElementById('new_room_capacity_input').value='<?= $cb ?>'" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-800 text-slate-400 hover:text-white cursor-pointer"><?= $cb ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">توضیحات اتاق</label>
                                    <input type="text" name="description" placeholder="ویژگی‌ها (نورگیر، یخچال...)" class="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg">
                                </div>
                            </div>
                            <div class="flex justify-end pt-1">
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl cursor-pointer">
                                    + ایجاد اتاق جدید
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB: FACILITIES (امکانات و تجهیزات اتاق‌ها) ===================== -->
            <div id="tab-facilities" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🛋</span>
                                <span>شناسنامه امکانات و تجهیزات اختصاصی اتاق‌های خوابگاه</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">مدیریت تجهیزات تفکیکی (یخچال، سازه تخت، ابزار نظافت، تلویزیون، اسپلیت و مبلمان) جهت نظارت سرپرستان</p>
                        </div>
                        <?php $compCnt = count(array_filter($allRooms, fn($r) => !empty($r['is_completed']))); ?>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-xs font-mono font-bold">
                                <?= $compCnt ?> تکمیل شده
                            </span>
                            <span class="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full text-xs font-mono font-bold">
                                <?= count($allRooms) - $compCnt ?> نیازمند تکمیل
                            </span>
                        </div>
                    </div>

                    <!-- Facilities Metric Highlights -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-1">
                            <span class="text-xs text-slate-400">کل موجودیت اتاق‌های خوابگاه</span>
                            <span class="text-xl font-black text-white font-mono"><?= count($allRooms) ?> اتاق</span>
                            <span class="text-[10px] text-slate-500 block">پوشش کامل طبقات همکف، ۱ و ۲</span>
                        </div>
                        <div class="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-1">
                            <span class="text-xs text-slate-400">اتاق‌های تکمیل‌شده با شناسنامه</span>
                            <span class="text-xl font-black text-emerald-400 font-mono"><?= $compCnt ?> اتاق (<?= count($allRooms) > 0 ? round(($compCnt / count($allRooms)) * 100) : 0 ?>٪)</span>
                            <span class="text-[10px] text-emerald-400/80 block">دارای فرم تجهیزات ثبت‌شده</span>
                        </div>
                        <div class="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-1">
                            <span class="text-xs text-slate-400">نیازمند بازبینی و تکمیل اطلاعات</span>
                            <span class="text-xl font-black text-amber-400 font-mono"><?= count($allRooms) - $compCnt ?> اتاق</span>
                            <span class="text-[10px] text-amber-400/80 block">فاقد مشخصات تفکیکی تجهیزات</span>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between pt-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">فیلتر طبقه:</span>
                            <button type="button" onclick="setFacFloorFilter('all', this)" class="fac-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setFacFloorFilter('همکف', this)" class="fac-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">همکف</button>
                            <button type="button" onclick="setFacFloorFilter('طبقه اول', this)" class="fac-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه اول</button>
                            <button type="button" onclick="setFacFloorFilter('طبقه دوم', this)" class="fac-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه دوم</button>
                        </div>
                        <div class="relative flex-1 max-w-sm">
                            <input type="text" id="facilitiesSearchInput" oninput="filterFacilitiesTable()" placeholder="🔍 جستجو در اتاق‌ها یا مدل یخچال و تجهیزات..." class="w-full pr-4 pl-10 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500 transition-all shadow-inner">
                        </div>
                    </div>

                    <!-- Facilities Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="facilitiesTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">وضعیت شناسنامه</th>
                                    <th class="p-3">یخچال و سرمایش/گرمایش</th>
                                    <th class="p-3">سازه تخت</th>
                                    <th class="p-3">وسایل نظافت و بهداشت</th>
                                    <th class="p-3">مبلمان و رفاهی</th>
                                    <th class="p-3 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php foreach ($allRooms as $rm): ?>
                                    <?php 
                                        $f = $roomFacilities[$rm['room_number']] ?? null;
                                        $isComp = !empty($rm['is_completed']) || ($f !== null);
                                    ?>
                                    <tr class="hover:bg-slate-800/40 fac-row" data-floor="<?= htmlspecialchars($rm['floor']) ?>" data-completed="<?= $isComp ? '1' : '0' ?>" data-search="<?= htmlspecialchars(strtolower($rm['room_number'] . ' ' . $rm['floor'] . ' ' . ($f['fridge_model'] ?? '') . ' ' . ($f['bed_type'] ?? '') . ' ' . ($f['tv_model'] ?? ''))) ?>">
                                        <td class="p-3">
                                            <div class="font-bold text-white font-mono text-sm">اتاق <?= htmlspecialchars($rm['room_number']) ?></div>
                                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($rm['floor']) ?> | <?= $rm['capacity'] ?> تخت</span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($isComp): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    تکمیل شده ✓
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                    تکمیل‌نشده ⚠️
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-slate-300">
                                            <div>❄️ <?= htmlspecialchars($f['fridge_model'] ?? 'یخچال ۵ فوت استاندارد') ?></div>
                                            <div class="text-[10px] text-slate-400">🌡️ <?= htmlspecialchars($f['ac_heater'] ?? 'شوفاژ + کولر آبی') ?></div>
                                        </td>
                                        <td class="p-3 text-slate-300">
                                            <span>🛏️ <?= htmlspecialchars($f['bed_type'] ?? 'تخت فلزی دو طبقه استاندارد') ?></span>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] <?= !empty($f['has_broom']) ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">جارو</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] <?= !empty($f['has_trash_bin']) ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">سطل زباله</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] <?= !empty($f['has_bucket']) ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">لگن شستشو</span>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-1 flex-wrap text-[10px] text-slate-400">
                                                <?php if (!empty($f['tv_model'])): ?>
                                                    <span class="text-cyan-400">📺 <?= htmlspecialchars($f['tv_model']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($f['has_carpet'])): ?><span class="text-slate-300">فرش ✓</span><?php endif; ?>
                                                <?php if (!empty($f['has_wardrobe'])): ?><span class="text-slate-300">کمد ✓</span><?php endif; ?>
                                                <?php if (!empty($f['has_desk'])): ?><span class="text-slate-300">میز ✓</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <button type="button" onclick="openFacilitiesModal('<?= htmlspecialchars($rm['room_number']) ?>', '<?= htmlspecialchars($rm['floor']) ?>')" class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                                                ویرایش شناسنامه 🛋
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB: MAINTENANCE (کارتابل خرابی و فاکتورها) ===================== -->
            <div id="tab-maintenance" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🔧</span>
                                <span>کارتابل اطلاع‌رسانی خرابی، درخواست تعمیر/تعویض و ثبت مالی مدیر</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">ثبت رسمی فاکتورها، هزینه قطعات، پیمانکاران و صدور صورت‌حساب مالی A4 با سربرگ دانشگاه</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openMaintenancePrintModal()" class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                                <span>چاپ صورت‌حساب رسمی A4 🖨</span>
                            </button>
                        </div>
                    </div>

                    <!-- Financial & Operational Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">کل هزینه‌های فاکتور شده</span>
                            <span class="text-2xl font-black text-white font-mono"><?= number_format($totalMaintenanceCost) ?></span>
                            <span class="text-[11px] text-slate-400 block mt-1">ریال (ثبت شده در فاکتورها)</span>
                        </div>

                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">کل گزارش‌های ارسالی</span>
                            <span class="text-2xl font-black text-indigo-400 font-mono"><?= count($maintenanceReports) ?> مورد</span>
                            <span class="text-[11px] text-indigo-300/80 block mt-1">از طرف سرپرستان شیفت شب</span>
                        </div>

                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">در انتظار اقدام مدیر</span>
                            <span class="text-2xl font-black text-rose-400 font-mono <?= $pendingMaintCount > 0 ? 'animate-pulse' : '' ?>">
                                <?= $pendingMaintCount ?> مورد
                            </span>
                            <span class="text-[11px] text-rose-300/80 block mt-1"><?= $urgentMaintCount ?> مورد با فوریت بالا</span>
                        </div>

                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">رفع‌شده و مختومه</span>
                            <span class="text-2xl font-black text-emerald-400 font-mono"><?= $resolvedMaintCount ?> مورد</span>
                            <span class="text-[11px] text-emerald-300/80 block mt-1">همراه با فاکتور معتبر</span>
                        </div>
                    </div>

                    <!-- Filters & Search -->
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between pt-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">وضعیت:</span>
                            <button type="button" onclick="setMaintStatusFilter('all', this)" class="maint-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setMaintStatusFilter('pending', this)" class="maint-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">در انتظار اقدام</button>
                            <button type="button" onclick="setMaintStatusFilter('resolved', this)" class="maint-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">رفع‌شده</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">فوریت:</span>
                            <button type="button" onclick="setMaintUrgencyFilter('all', this)" class="maint-urgency-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setMaintUrgencyFilter('urgent', this)" class="maint-urgency-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">فوری 🚨</button>
                            <button type="button" onclick="setMaintUrgencyFilter('normal', this)" class="maint-urgency-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">عادی</button>
                            <button type="button" onclick="setMaintUrgencyFilter('low', this)" class="maint-urgency-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">کم‌اهمیت</button>
                        </div>
                    </div>

                    <!-- Maintenance Reports Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="maintenanceTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">کد / اتاق</th>
                                    <th class="p-3">تجهیز و نوع درخواست</th>
                                    <th class="p-3">فوریت</th>
                                    <th class="p-3">گزارش‌دهنده و شرح</th>
                                    <th class="p-3">وضعیت اقدام</th>
                                    <th class="p-3">فاکتور و مالی</th>
                                    <th class="p-3 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php if (empty($maintenanceReports)): ?>
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-slate-400">
                                            هنوز هیچ گزارش خرابی در سامانه ثبت نشده است.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($maintenanceReports as $rep): ?>
                                        <?php
                                            $eqName = $rep['equipment_name'] ?? $rep['item_name'] ?? 'تجهیز خوابگاه';
                                            $repBy = $rep['reported_by_supervisor_name'] ?? $rep['reported_by'] ?? 'سرپرست شیفت شب';
                                            $resBy = $rep['resolved_by_manager_name'] ?? $rep['resolved_by'] ?? 'مدیریت امور دانشجویی';
                                            $costRials = intval($rep['resolution_cost'] ?? $rep['cost_rials'] ?? 0);
                                            $rawStatus = $rep['status'] ?? 'pending';
                                            $isResolved = ($rawStatus === 'resolved') || (str_contains($rawStatus, 'رفع‌شده'));
                                            $rawUrgency = $rep['urgency'] ?? 'normal';
                                            $isUrgent = ($rawUrgency === 'urgent') || ($rawUrgency === 'فوری');
                                            $isNormal = ($rawUrgency === 'normal') || ($rawUrgency === 'عادی');
                                        ?>
                                        <tr class="hover:bg-slate-800/40 maint-row" data-status="<?= $isResolved ? 'resolved' : 'pending' ?>" data-urgency="<?= $isUrgent ? 'urgent' : ($isNormal ? 'normal' : 'low') ?>">
                                            <td class="p-3">
                                                <span class="font-mono text-indigo-400 font-bold block">#<?= $rep['id'] ?></span>
                                                <span class="font-bold text-white">اتاق <?= htmlspecialchars($rep['room_number']) ?></span>
                                                <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($rep['floor'] ?? '') ?></span>
                                            </td>
                                            <td class="p-3">
                                                <strong class="text-white block"><?= htmlspecialchars($eqName) ?></strong>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($rep['maintenance_type'] ?? '') === 'replacement' || ($rep['maintenance_type'] ?? '') === 'تعویض / خرید جدید' ? 'bg-purple-950 text-purple-300 border border-purple-500/30' : 'bg-blue-950 text-blue-300 border border-blue-500/30' ?>">
                                                    <?= ($rep['maintenance_type'] ?? '') === 'replacement' || ($rep['maintenance_type'] ?? '') === 'تعویض / خرید جدید' ? 'درخواست تعویض قطعه' : 'درخواست تعمیرات' ?>
                                                </span>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($isUrgent): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">فوری 🚨</span>
                                                <?php elseif ($isNormal): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">عادی</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">کم‌اهمیت</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 max-w-xs">
                                                <div class="text-[10px] text-slate-400">سرپرست: <?= htmlspecialchars($repBy) ?> | <?= htmlspecialchars(substr($rep['reported_at'] ?? $rep['created_at'] ?? '', 0, 16)) ?></div>
                                                <p class="text-slate-300 line-clamp-2 mt-0.5"><?= htmlspecialchars($rep['description'] ?? '-') ?></p>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($isResolved): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 block w-fit">رفع‌شده ✓</span>
                                                    <span class="text-[10px] text-slate-400 block mt-0.5">توسط <?= htmlspecialchars($resBy) ?></span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 block w-fit">در انتظار اقدام</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($costRials > 0): ?>
                                                    <span class="font-mono text-emerald-400 font-bold block"><?= number_format($costRials) ?> ریال</span>
                                                    <span class="text-[10px] text-slate-400 block">فاکتور: <?= htmlspecialchars($rep['invoice_number'] ?? '-') ?></span>
                                                    <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($rep['contractor_or_store_name'] ?? '') ?></span>
                                                <?php else: ?>
                                                    <span class="text-slate-500 text-[11px]">- فاقد هزینه -</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                    <?php if (!$isResolved): ?>
                                                        <button type="button" onclick="openMaintenanceResolutionModalById(<?= $rep['id'] ?>)" class="px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                                                            ثبت اقدام و فاکتور 📝
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" onclick="openSingleInvoiceModalById(<?= $rep['id'] ?>)" class="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 hover:text-white rounded-xl text-xs font-bold border border-indigo-500/30 transition-all cursor-pointer">
                                                            مشاهده فاکتور 📄
                                                        </button>
                                                        <button type="button" onclick="openMaintenanceResolutionModalById(<?= $rep['id'] ?>)" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-[11px] font-bold transition-all cursor-pointer" title="ویرایش فاکتور یا اقدام">
                                                            ویرایش ✏️
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB: CLEANLINESS (ارزیابی نظافت و رتبه‌بندی) ===================== -->
            <div id="tab-cleanliness" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>✨</span>
                                <span>سامانه نظارت بر بهداشت و رتبه‌بندی عملکرد نظافت خوابگاه صمد</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">پایش ارزیابی شب به شب سرپرستان در ۴ سطح کیفی (عالی، خوب، متوسط، ضعیف) و صدور کارنامه رسمی چاپی با سربرگ دانشگاه</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openCleanlinessPrintModal()" class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                                <span>چاپ کارنامه رسمی بهداشت A4 🖨</span>
                            </button>
                        </div>
                    </div>

                    <!-- Cleanliness Overview Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">شاخص کلی بهداشت خوابگاه</span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-black text-emerald-400 font-mono"><?= $overallCleanlinessScore ?></span>
                                <span class="text-xs text-slate-400 font-mono">از ۴.۰</span>
                            </div>
                            <span class="text-[11px] text-emerald-300/80 block mt-1">معادل <?= round(($overallCleanlinessScore / 4) * 100) ?>٪ کیفیت مطلوب</span>
                        </div>

                        <?php
                            $excRms = count(array_filter($roomRankings, fn($r) => $r['avg_score'] >= 3.5));
                            $goodRms = count(array_filter($roomRankings, fn($r) => $r['avg_score'] >= 2.75 && $r['avg_score'] < 3.5));
                            $medRms = count(array_filter($roomRankings, fn($r) => $r['avg_score'] < 2.75));
                        ?>
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">اتاق‌های رتبه عالی (ممتاز)</span>
                            <span class="text-2xl font-black text-white font-mono"><?= $excRms ?> اتاق</span>
                            <span class="text-[11px] text-emerald-400 block mt-1">⭐⭐⭐⭐ امتیاز بالای ۳.۵</span>
                        </div>

                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">اتاق‌های رتبه خوب (مطلوب)</span>
                            <span class="text-2xl font-black text-cyan-400 font-mono"><?= $goodRms ?> اتاق</span>
                            <span class="text-[11px] text-cyan-300/80 block mt-1">⭐⭐⭐ امتیاز بین ۲.۷۵ تا ۳.۵</span>
                        </div>

                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
                            <span class="text-xs text-slate-400 block mb-1">نیازمند تذکر و بهبود</span>
                            <span class="text-2xl font-black text-amber-400 font-mono"><?= $medRms ?> اتاق</span>
                            <span class="text-[11px] text-amber-300/80 block mt-1">سطح متوسط یا ضعیف</span>
                        </div>
                    </div>

                    <!-- Cleanliness View Mode Toggle -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="switchCleanView('ranking')" id="btn-clean-ranking" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white cursor-pointer transition-all">
                                🏆 جدول رتبه‌بندی عملکرد اتاق‌ها
                            </button>
                            <button type="button" onclick="switchCleanView('history')" id="btn-clean-history" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">
                                📅 تاریخچه بازرسی‌های ثبت‌شده
                            </button>
                        </div>
                        <span class="text-xs text-slate-400 font-mono"><?= count($cleanlinessRecords) ?> رکورد بازرسی</span>
                    </div>

                    <!-- 1. Ranking Table View -->
                    <div id="cleanViewRanking" class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3 text-center">رتبه</th>
                                    <th class="p-3">شماره اتاق و طبقه</th>
                                    <th class="p-3">امتیاز میانگین</th>
                                    <th class="p-3">شاخص کیفی (ستاره‌ها)</th>
                                    <th class="p-3">تعداد بازرسی‌ها</th>
                                    <th class="p-3">تفکیک ارزیابی‌ها</th>
                                    <th class="p-3 text-center">وضعیت بهداشتی</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php $rankIdx = 1; ?>
                                <?php foreach ($roomRankings as $rk): ?>
                                    <tr class="hover:bg-slate-800/40">
                                        <td class="p-3 text-center">
                                            <?php if ($rankIdx === 1): ?>
                                                <span class="w-6 h-6 rounded-full bg-yellow-500 text-slate-950 font-black inline-flex items-center justify-center font-mono">۱</span>
                                            <?php elseif ($rankIdx === 2): ?>
                                                <span class="w-6 h-6 rounded-full bg-slate-300 text-slate-950 font-black inline-flex items-center justify-center font-mono">۲</span>
                                            <?php elseif ($rankIdx === 3): ?>
                                                <span class="w-6 h-6 rounded-full bg-amber-700 text-white font-black inline-flex items-center justify-center font-mono">۳</span>
                                            <?php else: ?>
                                                <span class="font-mono text-slate-400 font-bold"><?= $rankIdx ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 font-bold text-white font-mono text-sm">
                                            اتاق <?= htmlspecialchars($rk['room_number']) ?>
                                            <span class="text-[10px] text-slate-400 block font-normal font-sans"><?= htmlspecialchars($rk['floor']) ?></span>
                                        </td>
                                        <td class="p-3 font-mono font-bold text-sm text-emerald-400">
                                            <?= $rk['avg_score'] ?> <span class="text-[10px] text-slate-400 font-normal">از ۴</span>
                                        </td>
                                        <td class="p-3">
                                            <?php 
                                                $starCount = round($rk['avg_score']);
                                                $stars = str_repeat('⭐', max(1, min(4, $starCount)));
                                            ?>
                                            <span class="text-sm"><?= $stars ?></span>
                                        </td>
                                        <td class="p-3 font-mono text-slate-300">
                                            <?= $rk['inspections_count'] ?> نوبت
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-1 text-[10px] font-mono">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-500/20" title="تعداد عالی"><?= $rk['excellent_count'] ?> عالی</span>
                                                <span class="px-1.5 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-500/20" title="تعداد خوب"><?= $rk['good_count'] ?> خوب</span>
                                                <span class="px-1.5 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-500/20" title="تعداد متوسط"><?= $rk['average_count'] ?> متوسط</span>
                                                <span class="px-1.5 py-0.5 rounded bg-rose-950 text-rose-300 border border-rose-500/20" title="تعداد ضعیف"><?= $rk['poor_count'] ?> ضعیف</span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <?php if ($rk['avg_score'] >= 3.5): ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    نمونه و ممتاز 🏆
                                                </span>
                                            <?php elseif ($rk['avg_score'] >= 2.75): ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                                    مطلوب و منظم ✓
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                    نیازمند بهبود ⚠️
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $rankIdx++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 2. History Table View -->
                    <div id="cleanViewHistory" class="hidden overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">تاریخ بازرسی</th>
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">درجه کیفی نظافت</th>
                                    <th class="p-3">سرپرست ارزیاب</th>
                                    <th class="p-3">یادداشت و مشاهدات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php if (empty($cleanlinessRecords)): ?>
                                    <tr>
                                        <td colspan="5" class="p-6 text-center text-slate-400">سابقه‌ای ثبت نشده است.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cleanlinessRecords as $cr): ?>
                                        <tr class="hover:bg-slate-800/40">
                                            <td class="p-3 font-mono text-slate-300"><?= htmlspecialchars($cr['inspection_date']) ?></td>
                                            <td class="p-3 font-bold text-white font-mono">اتاق <?= htmlspecialchars($cr['room_number']) ?> (<?= htmlspecialchars($cr['floor'] ?? '') ?>)</td>
                                            <td class="p-3">
                                                <?php
                                                    $cg = $cr['grade'] ?? $cr['cleanliness_grade'] ?? '';
                                                    if ($cg === 'عالی' || $cg === 'excellent'):
                                                ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">عالی (۴/۴) ⭐⭐⭐⭐</span>
                                                <?php elseif ($cg === 'خوب' || $cg === 'good'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">خوب (۳/۴) ⭐⭐⭐</span>
                                                <?php elseif ($cg === 'متوسط' || $cg === 'average'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">متوسط (۲/۴) ⭐⭐</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">ضعیف (۱/۴) ⭐</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-slate-300"><?= htmlspecialchars($cr['supervisor_name'] ?? 'سرپرست شیفت شب') ?></td>
                                            <td class="p-3 text-slate-400"><?= htmlspecialchars($cr['notes'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 5: HOUSED STUDENTS ===================== -->
            <div id="tab-students" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white">فهرست دانشجویان اسکان‌یافته و مدیریت میهمانان</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">کلیک بر روی نام دانشجو جهت مشاهده و چاپ کارت دانشجویی هوشمند</p>
                        </div>
                        <span class="px-2.5 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-xs font-mono font-bold">
                            <?= count($housedAllocations) ?> دانشجو
                        </span>
                    </div>

                    <!-- جستجوی دانشجویان ساکن بر اساس کدملی، شماره دانشجویی، نام، اتاق -->
                    <div class="relative">
                        <input type="text" id="housedSearchInput" oninput="filterHousedTable()" placeholder="🔍 جستجو بر اساس کد ملی، شماره دانشجویی، نام دانشجو یا شماره اتاق..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-indigo-500 transition-all shadow-inner">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="housedTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">دانشجو (مشاهده کارت)</th>
                                    <th class="p-3">شماره دانشجویی / کدملی</th>
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">حالت اسکان</th>
                                    <th class="p-3">مدت اقامت میهمانی</th>
                                    <th class="p-3 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php foreach ($housedAllocations as $alloc): ?>
                                    <tr class="hover:bg-slate-800/40 housed-row" data-search="<?= htmlspecialchars(strtolower($alloc['full_name'] . ' ' . $alloc['national_id'] . ' ' . $alloc['student_id'] . ' ' . $alloc['room_number'] . ' ' . $alloc['floor'])) ?>">
                                        <td class="p-3">
                                            <button type="button" onclick='openStudentCardFromAlloc(<?= json_encode($alloc, JSON_UNESCAPED_UNICODE) ?>)' class="font-bold text-white hover:text-indigo-400 hover:underline flex items-center gap-1.5 cursor-pointer" title="مشاهده و چاپ کارت هوشمند دانشجویی">
                                                <span><?= htmlspecialchars($alloc['full_name']) ?></span>
                                                <span class="text-[10px] text-indigo-400 font-mono px-1 py-0.5 bg-indigo-500/20 rounded">🪪 کارت</span>
                                            </button>
                                        </td>
                                        <td class="p-3 font-mono">
                                            <span class="text-indigo-400 font-bold block"><?= htmlspecialchars($alloc['student_id']) ?></span>
                                            <span class="text-cyan-300 text-[10px]"><?= htmlspecialchars($alloc['national_id']) ?></span>
                                        </td>
                                        <td class="p-3">
                                            <span class="font-bold text-emerald-400">اتاق <?= htmlspecialchars($alloc['room_number']) ?></span>
                                            <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($alloc['floor']) ?></span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $alloc['dorm_status'] === 'میهمان' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' ?>">
                                                <?= htmlspecialchars($alloc['dorm_status']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($alloc['dorm_status'] === 'میهمان'): ?>
                                                <div class="space-y-1">
                                                    <span class="text-amber-300 font-mono font-bold block">
                                                        ⏱ <?= htmlspecialchars($alloc['guest_days_remaining'] ?? 7) ?> روز باقی‌مانده (از <?= htmlspecialchars($alloc['guest_days_total'] ?? 7) ?>)
                                                    </span>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action_reduce_guest_day" value="1">
                                                        <input type="hidden" name="alloc_id" value="<?= $alloc['id'] ?>">
                                                        <button type="submit" class="px-2 py-0.5 bg-slate-800 text-slate-300 rounded text-[10px] cursor-pointer hover:bg-slate-700">
                                                            -۱ روز (شبیه‌سازی)
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="inline" onsubmit="return confirm('آیا از اتمام دوره میهمانی و بازگرداندن دانشجو به صف متقاضیان خوابگاه اطمینان دارید؟');">
                                                        <input type="hidden" name="action_end_guest_stay" value="1">
                                                        <input type="hidden" name="alloc_id" value="<?= $alloc['id'] ?>">
                                                        <button type="submit" class="px-2 py-0.5 bg-amber-500/20 text-amber-300 hover:bg-amber-500 hover:text-slate-950 border border-amber-500/30 rounded text-[10px] font-bold cursor-pointer transition-all" title="پایان اسکان میهمان و انتقال به لیست متقاضیان">
                                                            پایان میهمانی و بازگشت به متقاضیان ↩️
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-500 text-[11px]">اسکان ترمی دائمی</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button" onclick="openEditDormModal('<?= htmlspecialchars($alloc['student_id']) ?>', '<?= htmlspecialchars($alloc['room_id']) ?>', '<?= htmlspecialchars($alloc['dorm_status']) ?>', '<?= htmlspecialchars($alloc['guest_days_remaining'] ?? 7) ?>')" class="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer">
                                                    تغییر اتاق / وضعیت
                                                </button>
                                                <form method="POST" onsubmit="return confirm('آیا از لغو اسکان دانشجو اطمینان دارید؟');" class="inline">
                                                    <input type="hidden" name="action_evict_student" value="1">
                                                    <input type="hidden" name="alloc_id" value="<?= $alloc['id'] ?>">
                                                    <button type="submit" class="px-2 py-1 bg-rose-500/20 text-rose-300 hover:bg-rose-500 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer">
                                                        لغو اسکان
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 6: NIGHT ATTENDANCE ===================== -->
            <div id="tab-attendance" class="tab-pane hidden space-y-6">
                <!-- Attendance Metrics Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5 no-print">
                    <div class="p-4 bg-[#1e293b]/70 border border-slate-800 rounded-2xl">
                        <span class="text-[11px] text-slate-400 block mb-1">کل دانشجویان</span>
                        <span class="text-xl font-black text-white font-mono"><?= $totalResidents ?></span>
                    </div>
                    <div class="p-4 bg-emerald-950/40 border border-emerald-500/30 rounded-2xl">
                        <span class="text-[11px] text-emerald-400 block mb-1">حاضر در اتاق</span>
                        <span class="text-xl font-black text-emerald-400 font-mono"><?= $pCount ?></span>
                    </div>
                    <div class="p-4 bg-rose-950/40 border border-rose-500/30 rounded-2xl">
                        <span class="text-[11px] text-rose-400 block mb-1">غایب</span>
                        <span class="text-xl font-black text-rose-400 font-mono"><?= $aCount ?></span>
                    </div>
                    <div class="p-4 bg-amber-950/40 border border-amber-500/30 rounded-2xl">
                        <span class="text-[11px] text-amber-400 block mb-1">مرخصی</span>
                        <span class="text-xl font-black text-amber-400 font-mono"><?= $lCount ?></span>
                    </div>
                    <div class="p-4 bg-indigo-950/40 border border-indigo-500/30 rounded-2xl">
                        <span class="text-[11px] text-indigo-400 block mb-1">درصد حضور</span>
                        <span class="text-xl font-black text-indigo-300 font-mono">٪<?= $pPercent ?></span>
                    </div>
                </div>

                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4 no-print">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">بررسی سرشماری شبانه خوابگاه و کارنامه تردد</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">ثبت‌شده توسط سرپرست خوابگاه با قابلیت پرینت فردی و کلی</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="GET" class="flex items-center gap-2">
                                <input type="hidden" name="tab" value="attendance">
                                <input type="date" name="att_date" value="<?= htmlspecialchars($attendance_date) ?>" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs font-mono">
                            </form>
                            <button type="button" onclick="openBatchPrintModal()" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md cursor-pointer">
                                چاپ گزارش کلی سرشماری 🖨
                            </button>
                        </div>
                    </div>

                    <!-- Attendance Filters & Search with National ID -->
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between pt-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">فیلتر طبقه:</span>
                            <button type="button" onclick="setAttFloorFilter('all', this)" class="att-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setAttFloorFilter('همکف', this)" class="att-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">همکف</button>
                            <button type="button" onclick="setAttFloorFilter('طبقه اول', this)" class="att-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه اول</button>
                            <button type="button" onclick="setAttFloorFilter('طبقه دوم', this)" class="att-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">طبقه دوم</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">وضعیت:</span>
                            <button type="button" onclick="setAttStatusFilter('all', this)" class="att-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                            <button type="button" onclick="setAttStatusFilter('حاضر', this)" class="att-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">حاضر</button>
                            <button type="button" onclick="setAttStatusFilter('غایب', this)" class="att-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">غایب</button>
                            <button type="button" onclick="setAttStatusFilter('مرخصی', this)" class="att-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">مرخصی</button>
                        </div>
                    </div>

                    <!-- جعبه جستجوی سرشماری -->
                    <div class="relative">
                        <input type="text" id="attendanceSearchInput" oninput="filterAttendanceTable()" placeholder="🔍 جستجو بر اساس کد ملی، شماره دانشجویی، نام دانشجو یا شماره اتاق..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-rose-500 transition-all shadow-inner">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs" id="attendanceTable">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">دانشجو</th>
                                    <th class="p-3">شماره دانشجویی / کدملی</th>
                                    <th class="p-3">اتاق و طبقه</th>
                                    <th class="p-3">وضعیت سرشماری</th>
                                    <th class="p-3">توضیحات سرپرست</th>
                                    <th class="p-3 text-center">چاپ کارنامه تردد / پرینت فردی</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php foreach ($attendanceList as $att): ?>
                                    <tr class="hover:bg-slate-800/40 att-row" data-floor="<?= htmlspecialchars($att['floor']) ?>" data-status="<?= htmlspecialchars($att['attendance_status']) ?>" data-search="<?= htmlspecialchars(strtolower($att['full_name'] . ' ' . $att['national_id'] . ' ' . $att['student_id'] . ' ' . $att['room_number'] . ' ' . $att['floor'])) ?>">
                                        <td class="p-3 font-bold text-white"><?= htmlspecialchars($att['full_name']) ?></td>
                                        <td class="p-3 font-mono">
                                            <span class="text-indigo-400 font-bold block"><?= htmlspecialchars($att['student_id']) ?></span>
                                            <span class="text-cyan-300 text-[10px]"><?= htmlspecialchars($att['national_id']) ?></span>
                                        </td>
                                        <td class="p-3">اتاق <?= htmlspecialchars($att['room_number']) ?> (<?= htmlspecialchars($att['floor']) ?>)</td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $att['attendance_status'] === 'حاضر' ? 'bg-emerald-500/20 text-emerald-300' : ($att['attendance_status'] === 'غایب' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300') ?>">
                                                <?= htmlspecialchars($att['attendance_status']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-[11px] text-slate-300"><?= htmlspecialchars($att['attendance_notes'] ?? 'ثبت حضوری انجام شد') ?></td>
                                        <td class="p-3 text-center">
                                            <button type="button" onclick="openIndividualPrintModal('<?= htmlspecialchars($att['full_name']) ?>', '<?= htmlspecialchars($att['student_id']) ?>', '<?= htmlspecialchars($att['national_id']) ?>', '<?= htmlspecialchars($att['room_number']) ?>', '<?= htmlspecialchars($att['floor']) ?>', '<?= htmlspecialchars($att['attendance_status']) ?>', '<?= htmlspecialchars($att['attendance_notes'] ?? 'تایید حضور حضوری') ?>')" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white rounded-lg text-xs font-bold border border-slate-700 transition-colors cursor-pointer">
                                                پرینت فردی 🖨
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 7: SUPERVISORS & MANAGERS ===================== -->
            <div id="tab-supervisors" class="tab-pane hidden space-y-6">
                <!-- فرم ثبت کاربر جدید (سرپرست خوابگاه یا کاربر مدیر کل) -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="border-b border-slate-800 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span id="add-admin-icon">🛡</span>
                                <span id="add-admin-title">افزودن سرپرست جدید و تعیین شیفت کاری</span>
                            </h3>
                            <p id="add-admin-subtitle" class="text-[11px] text-slate-400 mt-0.5">سرپرستان با کد ملی و رمز عبور وارد پنل سرشماری می‌شوند</p>
                        </div>
                        <div class="inline-flex items-center p-1 bg-slate-900 border border-slate-700 rounded-xl text-xs gap-1 self-start sm:self-auto">
                            <button type="button" id="role-btn-supervisor" onclick="switchAdminCreateRole('supervisor')" class="px-3 py-1.5 rounded-lg font-bold bg-amber-500 text-slate-950 transition-all cursor-pointer">
                                🛡 سرپرست خوابگاه
                            </button>
                            <button type="button" id="role-btn-manager" onclick="switchAdminCreateRole('manager')" class="px-3 py-1.5 rounded-lg font-bold text-slate-300 hover:text-white transition-all cursor-pointer">
                                👔 کاربر مدیر کل
                            </button>
                        </div>
                    </div>

                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 text-xs">
                        <input type="hidden" name="action_add_supervisor" value="1">
                        <input type="hidden" name="admin_role" id="add_admin_role_input" value="supervisor">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">کد ملی ده رقمی (نام کاربری) *</label>
                            <input type="text" name="sup_national_id" id="add_sup_nid" maxlength="10" placeholder="مثال: 0099887766" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-amber-500 outline-none" dir="ltr">
                        </div>
                        <div>
                            <label id="add_sup_name_label" class="block text-slate-300 font-semibold mb-1">نام و نام خانوادگی سرپرست *</label>
                            <input type="text" name="sup_full_name" id="add_sup_full_name" placeholder="مثال: مهندس حسام داوودی" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                        </div>
                        <div>
                            <label id="add_sup_days_label" class="block text-slate-300 font-semibold mb-1">روزهای شیفت کاری *</label>
                            <input type="text" name="sup_shift_days" id="add_sup_shift_days" value="شنبه تا چهارشنبه" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                            <div class="flex flex-wrap gap-1 mt-1">
                                <?php foreach (['شنبه تا چهارشنبه', 'شنبه تا پنج‌شنبه', 'پنج‌شنبه و جمعه', 'روزهای زوج', 'روزهای فرد'] as $d): ?>
                                    <button type="button" onclick="document.getElementById('add_sup_shift_days').value='<?= $d ?>'" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-800 text-slate-400 hover:text-white cursor-pointer"><?= $d ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <label id="add_sup_hours_label" class="block text-slate-300 font-semibold mb-1">ساعات کاری شیفت *</label>
                            <input type="text" name="sup_shift_hours" id="add_sup_shift_hours" value="۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                            <div class="flex flex-wrap gap-1 mt-1">
                                <?php foreach (['۲۲:۰۰ الی ۰۶:۰۰ (شب)', '۰۸:۰۰ الی ۱۶:۰۰ (اداری)', '۱۶:۰۰ الی ۲۴:۰۰ (عصر)'] as $h): ?>
                                    <button type="button" onclick="document.getElementById('add_sup_shift_hours').value='<?= $h ?>'" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-800 text-slate-400 hover:text-white cursor-pointer"><?= $h ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">کلمه عبور اختصاصی *</label>
                            <input type="text" name="sup_password" id="add_sup_password" value="Super@12345" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-amber-500 outline-none" dir="ltr">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-5 flex items-center justify-between pt-1 gap-3 flex-wrap">
                            <span id="add-admin-role-hint" class="text-[11px] text-amber-300/90">نقش انتخابی: سرپرست شیفت (دسترسی به پنل سرپرست خوابگاه)</span>
                            <button type="submit" id="add-admin-submit-btn" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                                + ثبت سرپرست جدید و صدور دسترسی
                            </button>
                        </div>
                    </form>
                </div>

                <!-- بخش مدیران کل سامانه -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-indigo-300 flex items-center gap-2">
                            <span>👔</span>
                            <span>کاربران مدیر کل سامانه (<?= count($managers) ?> کاربر)</span>
                        </h4>
                        <span class="text-[11px] text-slate-400">دارای دسترسی کامل به داشبورد مدیریت کل</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php
                            $currentAdminNid = $jwtAuth['sub'] ?? ($_SESSION['admin_national_id'] ?? '');
                            foreach ($managers as $mgr):
                                $mgrDays = !empty($mgr['shift_days']) ? $mgr['shift_days'] : 'شنبه تا پنج‌شنبه';
                                $mgrHours = !empty($mgr['shift_hours']) ? $mgr['shift_hours'] : '۰۸:۰۰ الی ۱۶:۰۰ (اداری)';
                                $isCurrentMgr = ($mgr['national_id'] === $currentAdminNid);
                        ?>
                            <div class="bg-[#1e293b]/70 border <?= $isCurrentMgr ? 'border-indigo-500/50' : 'border-slate-800' ?> rounded-2xl p-5 shadow-lg space-y-3 relative">
                                <div class="flex justify-between items-center gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-bold text-sm shrink-0">
                                            👔
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-white text-sm truncate"><?= htmlspecialchars($mgr['full_name']) ?></h4>
                                            <span class="text-[10px] text-slate-400 font-mono">کدملی: <?= htmlspecialchars($mgr['national_id']) ?></span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded text-[10px] font-bold shrink-0">مدیر کل ✓</span>
                                </div>

                                <div class="p-3 bg-slate-900/80 rounded-xl space-y-2 text-xs">
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">📅 روزهای حضور:</span>
                                        <span class="font-bold text-cyan-300"><?= htmlspecialchars($mgrDays) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">⏰ ساعات کاری:</span>
                                        <span class="font-bold text-indigo-300"><?= htmlspecialchars($mgrHours) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">سطح دسترسی:</span>
                                        <span class="text-emerald-300 font-bold">دسترسی کل مدیریت</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[11px]">
                                    <?php if ($isCurrentMgr): ?>
                                        <span class="text-emerald-400 font-bold">● حساب فعال جاری شما</span>
                                    <?php else: ?>
                                        <span class="text-slate-400">ورود با کد ملی و رمز اختصاصی</span>
                                        <?php if (count($managers) > 1): ?>
                                            <form method="POST" onsubmit="return confirm('آیا از حذف دسترسی این کاربر مدیر کل اطمینان دارید؟');" class="inline">
                                                <input type="hidden" name="action_delete_manager" value="1">
                                                <input type="hidden" name="manager_id" value="<?= $mgr['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 bg-rose-950/60 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 rounded-lg text-[11px] font-bold transition-colors cursor-pointer" title="حذف کاربر مدیر">حذف مدیر ✕</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- کارت‌های سرپرستان با روزها و ساعات کاری شیفت -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-amber-300 flex items-center gap-2">
                            <span>🛡</span>
                            <span>سرپرستان خوابگاه و شیفت‌های کاری (<?= count($supervisors) ?> کاربر)</span>
                        </h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($supervisors as $sup): ?>
                            <?php
                                $supDays = !empty($sup['shift_days']) ? $sup['shift_days'] : 'شنبه تا چهارشنبه';
                                $supHours = !empty($sup['shift_hours']) ? $sup['shift_hours'] : '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)';
                            ?>
                            <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-3 relative group">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300 font-bold text-sm">
                                            👨‍💼
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-white text-sm"><?= htmlspecialchars($sup['full_name']) ?></h4>
                                            <span class="text-[10px] text-slate-400 font-mono">کدملی: <?= htmlspecialchars($sup['national_id']) ?></span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">سرپرست رسمی ✓</span>
                                </div>

                                <div class="p-3 bg-slate-900/80 rounded-xl space-y-2 text-xs">
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">📅 روزهای شیفت:</span>
                                        <span class="font-bold text-cyan-300"><?= htmlspecialchars($supDays) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">⏰ ساعات کاری:</span>
                                        <span class="font-bold text-amber-300"><?= htmlspecialchars($supHours) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-300">
                                        <span class="text-slate-400">مسئولیت:</span>
                                        <span class="text-slate-200">سرپرست شبانه خوابگاه صمد</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-800 gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" onclick="openSupervisorCardModal('<?= htmlspecialchars($sup['full_name']) ?>', '<?= htmlspecialchars($sup['national_id']) ?>')" class="px-2.5 py-1.5 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-xl text-xs font-bold transition-all border border-indigo-500/30 cursor-pointer">
                                            کارت سرپرست 🪪
                                        </button>
                                        <button type="button" onclick="openEditSupervisorShiftModal(<?= $sup['id'] ?>, '<?= htmlspecialchars($sup['full_name']) ?>', '<?= htmlspecialchars($supDays) ?>', '<?= htmlspecialchars($supHours) ?>')" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 rounded-xl text-xs font-bold transition-all border border-slate-700 cursor-pointer" title="تنظیم روزهای شیفت و ساعات کاری">
                                            تنظیم شیفت ⚙️
                                        </button>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('آیا از حذف دسترسی این سرپرست اطمینان دارید؟');" class="inline">
                                        <input type="hidden" name="action_delete_supervisor" value="1">
                                        <input type="hidden" name="supervisor_id" value="<?= $sup['id'] ?>">
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 text-xs cursor-pointer" title="حذف سرپرست">✕</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 8: FESTIVALS & VOLUNTEERS ===================== -->
            <div id="tab-festivals" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🏆</span>
                                <span>سامانه جشنواره‌ها، المپیادها و داوطلبان دانشجویی</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">مشاهده دانشجویانی که در حوزه‌های مختلف علمی، ورزشی، فرهنگی و مذهبی داوطلب شده‌اند</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openCreateCallModal()" class="flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-400 hover:to-amber-400 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                                <span>+ صدور فراخوان هدفمند جدید 📢</span>
                            </button>
                            <span class="px-2.5 py-1 bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 rounded-full text-xs font-mono font-bold">
                                <?= count($studentFestivals) ?> داوطلب
                            </span>
                        </div>
                    </div>

                    <!-- بخش فراخوان‌های هدفمند دانشگاه صمد -->
                    <div class="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-800 pb-2">
                            <div>
                                <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>📢</span>
                                    <span>فراخوان‌های دانشگاه (سبز: فعال | زرد: آینده | خاکستری: پایان‌یافته):</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">فراخوان‌های آینده به صورت خودکار با رسیدن تاریخ شروع فعال می‌شوند و فراخوان‌های پایان‌یافته بایگانی می‌شوند.</span>
                            </div>
                            <!-- Status Filter Buttons -->
                            <div class="flex items-center gap-1 text-[11px]">
                                <button type="button" onclick="filterCallsByStatus('all', this)" class="call-filter-btn px-2.5 py-1 rounded-lg font-bold bg-indigo-600 text-white cursor-pointer transition-all">همه</button>
                                <button type="button" onclick="filterCallsByStatus('active', this)" class="call-filter-btn px-2.5 py-1 rounded-lg bg-emerald-950/60 text-emerald-300 border border-emerald-500/40 hover:bg-emerald-900/60 cursor-pointer transition-all">🟢 فعال</button>
                                <button type="button" onclick="filterCallsByStatus('upcoming', this)" class="call-filter-btn px-2.5 py-1 rounded-lg bg-amber-950/60 text-amber-300 border border-amber-500/40 hover:bg-amber-900/60 cursor-pointer transition-all">🟡 آینده (به‌زودی)</button>
                                <button type="button" onclick="filterCallsByStatus('expired', this)" class="call-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-750 cursor-pointer transition-all">⚪ پایان‌یافته</button>
                            </div>
                        </div>

                        <?php if (empty($festivalCalls)): ?>
                            <p class="text-[11px] text-slate-400">هنوز فراخوانی صادر نشده است. با کلیک بر دکمه «صدور فراخوان هدفمند جدید»، می‌توانید تیم‌های ورزشی یا جشنواره‌های تخصصی را فراخوان دهید.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 pt-1" id="festivalCallsGrid">
                                <?php 
                                $pDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                                $eDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                                $todayNorm = '1405/07/25'; // Jalali today

                                foreach ($festivalCalls as $fc): 
                                    $sNorm = str_replace($pDigits, $eDigits, $fc['start_date'] ?? '');
                                    $dNorm = str_replace($pDigits, $eDigits, $fc['deadline_date'] ?? '');
                                    preg_match('/\d{4}\/\d{2}\/\d{2}/', $sNorm, $sm);
                                    preg_match('/\d{4}\/\d{2}\/\d{2}/', $dNorm, $dm);
                                    $sDate = $sm[0] ?? substr($sNorm, 0, 10);
                                    $dDate = $dm[0] ?? substr($dNorm, 0, 10);

                                    $callStatus = 'active';
                                    if ($sDate && $todayNorm < $sDate) {
                                        $callStatus = 'upcoming';
                                    } elseif ($dDate && $todayNorm > $dDate) {
                                        $callStatus = 'expired';
                                    }

                                    // Dynamic border class based on user requirements:
                                    // Active: green border | Upcoming: yellow border | Expired: gray, not deleted
                                    if ($callStatus === 'active') {
                                        $cardBorder = 'border-2 border-emerald-500 bg-slate-950/80 shadow-md shadow-emerald-500/10';
                                    } elseif ($callStatus === 'upcoming') {
                                        $cardBorder = 'border-2 border-amber-400 bg-slate-950/80 shadow-md shadow-amber-500/10';
                                    } else {
                                        $cardBorder = 'border-2 border-slate-600 bg-slate-950/40 text-slate-400 opacity-75';
                                    }
                                ?>
                                    <div class="p-3.5 rounded-xl space-y-2.5 relative transition-all festival-call-item <?= $cardBorder ?>" data-status="<?= $callStatus ?>">
                                        <div class="flex items-start justify-between gap-2 border-b border-slate-800 pb-2">
                                            <div>
                                                <!-- Status Badge -->
                                                <?php if ($callStatus === 'active'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 mb-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                        🟢 فعال و در حال ثبت‌نام
                                                    </span>
                                                <?php elseif ($callStatus === 'upcoming'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 mb-1">
                                                        <span>⏰</span>
                                                        🟡 شروع در آینده (به‌زودی)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700 mb-1">
                                                        <span>⚪</span>
                                                        مهلت پایان‌یافته (غیرقابل دسترس)
                                                    </span>
                                                <?php endif; ?>

                                                <h5 class="text-xs font-bold text-white leading-snug"><?= htmlspecialchars($fc['title']) ?></h5>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('آیا از حذف این فراخوان از لیست اطمینان دارید؟');" class="inline">
                                                <input type="hidden" name="action_delete_festival_call" value="1">
                                                <input type="hidden" name="call_id" value="<?= $fc['id'] ?>">
                                                <button type="submit" class="text-slate-500 hover:text-rose-400 text-xs cursor-pointer p-0.5" title="حذف فراخوان">✕</button>
                                            </form>
                                        </div>

                                        <p class="text-[11px] leading-relaxed <?= $callStatus === 'expired' ? 'text-slate-400' : 'text-slate-300' ?> line-clamp-2">
                                            <?= htmlspecialchars($fc['description'] ?? $fc['content'] ?? '') ?>
                                        </p>

                                        <div class="flex items-center justify-between pt-1 border-t border-slate-800 text-[10px] flex-wrap gap-1">
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <?php if (!empty($fc['target_sports_team'])): ?>
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-500/30 font-bold">⚽ تیم <?= htmlspecialchars($fc['target_sports_team']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($fc['target_skill'])): ?>
                                                    <span class="px-1.5 py-0.5 rounded bg-purple-950 text-purple-300 border border-purple-500/30 font-bold">🎯 <?= htmlspecialchars($fc['target_skill']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="font-mono text-[10px] text-right">
                                                <?php if (!empty($fc['start_date'])): ?>
                                                    <span class="text-slate-400">شروع: <?= htmlspecialchars($fc['start_date']) ?> | </span>
                                                <?php endif; ?>
                                                <span class="<?= $callStatus === 'expired' ? 'text-slate-500 line-through' : 'text-amber-400' ?>">
                                                    مهلت: <?= htmlspecialchars($fc['deadline_date']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Filter & Search Box -->
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between pt-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">حوزه رویداد:</span>
                            <button type="button" onclick="setFestivalCatFilter('all', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-yellow-600 text-white cursor-pointer transition-all">همه حوزه‌ها</button>
                            <button type="button" onclick="setFestivalCatFilter('technical', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">فنی و اختراعات</button>
                            <button type="button" onclick="setFestivalCatFilter('robotics', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">رباتیک و هوش مصنوعی</button>
                            <button type="button" onclick="setFestivalCatFilter('cultural', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">فرهنگی و هنری</button>
                            <button type="button" onclick="setFestivalCatFilter('skillBased', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">ملی مهارت</button>
                            <button type="button" onclick="setFestivalCatFilter('religious', this)" class="fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all">قرآنی و معارف</button>
                        </div>
                        <div class="relative flex-1 max-w-md">
                            <input type="text" id="festivalsSearchInput" oninput="filterFestivalsList()" placeholder="🔍 جستجو در داوطلبان (کد ملی، شماره دانشجویی، نام یا رویداد)..." class="w-full pr-4 pl-10 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-yellow-500 transition-all shadow-inner">
                        </div>
                    </div>

                    <!-- Festivals Cards Grid -->
                    <div id="festivalsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        <?php foreach ($studentFestivals as $fest): ?>
                            <div class="bg-slate-900/90 border border-slate-800 hover:border-yellow-500/40 rounded-2xl p-5 shadow-lg space-y-3.5 transition-all fest-card flex flex-col justify-between" data-category="<?= htmlspecialchars($fest['festival_key']) ?>" data-search="<?= htmlspecialchars(strtolower($fest['full_name'] . ' ' . $fest['national_id'] . ' ' . $fest['student_id'] . ' ' . $fest['festival_title'] . ' ' . $fest['field_of_study'])) ?>">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/30 flex items-center justify-center text-yellow-300 font-bold text-sm">
                                                🎖
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-bold text-white"><?= htmlspecialchars($fest['full_name']) ?></h4>
                                                <span class="text-[10px] text-slate-400 font-mono">کدملی: <?= htmlspecialchars($fest['national_id']) ?> | ش.د: <?= htmlspecialchars($fest['student_id']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-xs text-slate-300">
                                        <span class="text-slate-400">رشته: </span>
                                        <span class="font-semibold text-slate-200"><?= htmlspecialchars($fest['field_of_study']) ?></span>
                                        <span class="text-[10px] text-yellow-400 block mt-0.5"><?= htmlspecialchars($fest['campus']) ?></span>
                                    </div>

                                    <div class="p-3 bg-slate-950/70 rounded-xl border border-yellow-500/20 space-y-1">
                                        <span class="text-[10px] text-yellow-400 font-bold block">رویداد داوطلب شده:</span>
                                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($fest['festival_title']) ?></p>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                    <span class="text-slate-400 font-mono text-[11px]">
                                        📞 <?= htmlspecialchars($fest['mobile']) ?>
                                    </span>
                                    <button type="button" onclick='openStudentCardModal(<?= json_encode($fest, JSON_UNESCAPED_UNICODE) ?>)' class="px-2.5 py-1 bg-yellow-600/30 hover:bg-yellow-600 text-yellow-200 hover:text-white rounded-lg text-[10px] font-bold border border-yellow-500/30 transition-all cursor-pointer">
                                        مشاهده پرونده / کارت
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 9: SKILLS BANK & LIVE SEARCH ===================== -->
            <div id="tab-skills" class="tab-pane hidden space-y-6">
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="border-b border-slate-800 pb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>✨</span>
                                <span>بانک مهارت‌های دانشجویی و استعدادیابی (جستجوی زنده و لحظه‌ای)</span>
                                <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-full text-xs font-bold font-mono">
                                    <?= count($studentSkillsList) ?> دانشجو
                                </span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">با تایپ عنوان هر مهارت، دانشجویانی که مهارت‌های نزدیک به عبارت جستجو دارند به صورت لحظه‌ای فیلتر می‌شوند</p>
                        </div>
                    </div>

                    <!-- Big Live Search Box -->
                    <div class="space-y-3">
                        <div class="relative">
                            <input type="text" id="skillLiveQueryInput" oninput="filterSkillsLive()" placeholder="عنوان مهارت یا نام و کدملی را تایپ کنید (مثلاً: پایتون، فتوشاپ، برق، لینوکس، طراحی وب، تاسیسات...)" class="w-full pr-4 pl-20 py-3 bg-slate-900 border-2 border-purple-500/40 focus:border-purple-500 text-white rounded-2xl text-sm outline-none transition-all shadow-inner">
                            <button type="button" onclick="clearSkillLiveQuery()" id="clearSkillBtn" class="hidden absolute left-3 top-3 text-slate-400 hover:text-white text-xs px-2 py-1 bg-slate-800 rounded-lg cursor-pointer">
                                ✕ پاک کردن
                            </button>
                        </div>

                        <!-- Quick Skill Suggestion Chips -->
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="text-slate-400 text-[11px] ml-1">پیشنهادهای پرتکرار:</span>
                            <?php foreach (['پایتون', 'هوش مصنوعی', 'فتوشاپ', 'برق صنعتی', 'الکترونیک', 'اتوکد', 'سالیدورکس', 'مدیریت پروژه', 'تاسیسات', 'طراحی وب'] as $tag): ?>
                                <button type="button" onclick="setSkillTag('<?= $tag ?>')" class="skill-tag-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700 transition-all cursor-pointer">
                                    #<?= $tag ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Students Skills Cards Grid -->
                    <div id="skillsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        <?php foreach ($studentSkillsList as $stdSkill): ?>
                            <?php
                                $skillsArr = array_filter(array_map('trim', explode('،', $stdSkill['skills_concatenated'] ?? '')));
                            ?>
                            <div class="bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-5 shadow-lg space-y-3.5 transition-all skill-card flex flex-col justify-between" data-search="<?= htmlspecialchars(strtolower($stdSkill['full_name'] . ' ' . $stdSkill['national_id'] . ' ' . $stdSkill['student_id'] . ' ' . $stdSkill['skills_concatenated'] . ' ' . $stdSkill['field_of_study'])) ?>">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-300 font-bold text-sm">
                                                🎓
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-bold text-white"><?= htmlspecialchars($stdSkill['full_name']) ?></h4>
                                                <span class="text-[10px] text-slate-400 font-mono">کدملی: <?= htmlspecialchars($stdSkill['national_id']) ?> | ش.د: <?= htmlspecialchars($stdSkill['student_id']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-xs text-slate-300">
                                        <span class="text-slate-400">رشته: </span>
                                        <span class="font-semibold text-slate-200"><?= htmlspecialchars($stdSkill['field_of_study']) ?></span>
                                        <span class="text-[10px] text-purple-400 block mt-0.5"><?= htmlspecialchars($stdSkill['campus']) ?></span>
                                    </div>

                                    <div>
                                        <span class="text-[11px] text-slate-400 font-bold block mb-1.5">مهارت‌ها و تخصص‌ها:</span>
                                        <div class="flex flex-wrap gap-1.5 skills-badges-wrap">
                                            <?php foreach ($skillsArr as $sk): ?>
                                                <span class="skill-badge px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-950/40 text-purple-300 border border-purple-500/30 transition-all" data-name="<?= htmlspecialchars(strtolower($sk)) ?>">
                                                    <?= htmlspecialchars($sk) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                    <span class="text-slate-400 font-mono text-[11px]">
                                        📞 <?= htmlspecialchars($stdSkill['mobile'] ?? '09120000000') ?>
                                    </span>
                                    <button type="button" onclick='openStudentCardModal(<?= json_encode($stdSkill, JSON_UNESCAPED_UNICODE) ?>)' class="px-2.5 py-1 bg-purple-600/30 hover:bg-purple-600 text-purple-200 hover:text-white rounded-lg text-[10px] font-bold border border-purple-500/30 transition-all cursor-pointer">
                                        مشاهده پرونده / کارت
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ===================== TAB 10: WELFARE & GYM RESERVATIONS ===================== -->
            <div id="tab-welfare" class="tab-pane hidden space-y-6">
                <!-- سربرگ و آمار کلی سالن بدنسازی و خدمات رفاهی -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                    <div class="border-b border-slate-800 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🏋️</span>
                                <span>مدیریت خدمات رفاهی، سالن بدنسازی و سانس‌های ورزشی پردیس</span>
                                <?php if (!empty($welfareGym['is_active'])): ?>
                                    <span class="px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-xs font-bold">
                                        سالن دایر و فعال ✓
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-full text-xs font-bold">
                                        سالن غیرفعال / تعمیرات ⚠️
                                    </span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-1">
                                تنظیم نرخ ساعتی، سانس‌بندی ۸ تا ۲۲ (بجز ۱۲ تا ۱۴ ناهار)، رزرو اختصاصی تیم‌های ورزشی و لغو سانس با عودت خودکار وجه به کیف پول دانشجو
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action_generate_gym_slots" value="1">
                                <input type="hidden" name="service_id" value="<?= $welfareGym['id'] ?? 1 ?>">
                                <button type="submit" class="px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                                    <span>🔄</span>
                                    <span>تولید و همگام‌سازی سانس‌های ۷ روز آینده</span>
                                </button>
                            </form>
                            <button type="button" onclick="openGymPrintModal()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                                <span>🖨</span>
                                <span>چاپ گزارش تقویم خورشیدی</span>
                            </button>
                        </div>
                    </div>

                    <!-- کارت‌های شاخص سریع سالن -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div class="p-4 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-1.5 shadow-inner">
                            <span class="text-slate-400 block text-[11px]">موقعیت مکانی سالن:</span>
                            <strong class="text-white text-sm block"><?= htmlspecialchars($welfareGym['location'] ?? 'زیرزمین خوابگاه صمد') ?></strong>
                            <span class="text-emerald-400 font-semibold block text-[10px]">مختص دانشجویان پسر پردیس</span>
                        </div>

                        <div class="p-4 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-1.5 shadow-inner">
                            <span class="text-slate-400 block text-[11px]">نرخ مصوب هر ۱ ساعت:</span>
                            <div class="flex items-baseline gap-1.5">
                                <strong class="text-amber-300 text-base font-mono"><?= number_format($welfareGym['hourly_rate_rials'] ?? 50000) ?></strong>
                                <span class="text-slate-400 text-[10px]">ریال / ساعت</span>
                            </div>
                            <span class="text-slate-400 text-[10px] block">سانس ۲ ساعته: <?= number_format(($welfareGym['hourly_rate_rials'] ?? 50000) * 2) ?> ریال</span>
                        </div>

                        <div class="p-4 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-1.5 shadow-inner">
                            <span class="text-slate-400 block text-[11px]">ظرفیت استاندارد هر سانس:</span>
                            <strong class="text-cyan-300 text-base font-mono"><?= htmlspecialchars($welfareGym['capacity_per_slot'] ?? 20) ?> نفر</strong>
                            <span class="text-slate-400 text-[10px] block">حداکثر سقف مجاز همزمانی</span>
                        </div>

                        <div class="p-4 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-1.5 shadow-inner">
                            <span class="text-slate-400 block text-[11px]">کل نوبت‌های ثبت‌شده:</span>
                            <strong class="text-indigo-300 text-base font-mono"><?= count($gymBookings) ?> رزرو</strong>
                            <span class="text-slate-400 text-[10px] block">تراکنش‌های امن با توکن یکتا</span>
                        </div>
                    </div>
                </div>

                <!-- فرم ویرایش تنظیمات و قوانین سالن بدنسازی -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h4 class="text-xs font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-2.5">
                        <span>⚙️</span>
                        <span>تنظیمات عمومی، ظرفیت و نرخ ساعتی سالن بدنسازی</span>
                    </h4>

                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <input type="hidden" name="action_update_welfare_gym" value="1">
                        <input type="hidden" name="service_id" value="<?= $welfareGym['id'] ?? 1 ?>">

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">عنوان خدمت / سالن *</label>
                            <input type="text" name="service_title" value="<?= htmlspecialchars($welfareGym['title'] ?? 'سالن بدنسازی و پرورش اندام پردیس') ?>" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-bold focus:border-emerald-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">محل استقرار فیزیکی *</label>
                            <input type="text" name="service_location" value="<?= htmlspecialchars($welfareGym['location'] ?? 'زیرزمین خوابگاه مرکزی صمد') ?>" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">هزینه هر ۱ ساعت رزرو (ریال) *</label>
                            <input type="number" name="hourly_rate_rials" value="<?= intval($welfareGym['hourly_rate_rials'] ?? 50000) ?>" min="0" step="5000" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-amber-300 font-mono font-bold rounded-xl focus:border-emerald-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">ظرفیت هر سانس (نفر) *</label>
                            <input type="number" name="capacity_per_slot" value="<?= intval($welfareGym['capacity_per_slot'] ?? 20) ?>" min="5" max="60" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-cyan-300 font-mono font-bold rounded-xl focus:border-emerald-500 outline-none">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-slate-300 font-semibold mb-1">قوانین و راهنمای ورود به سالن (قابل مشاهده برای دانشجو):</label>
                            <input type="text" name="rules_guidelines" value="<?= htmlspecialchars($welfareGym['rules_guidelines'] ?? '') ?>" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-slate-200 rounded-xl focus:border-emerald-500 outline-none">
                        </div>

                        <div class="flex items-center gap-3 pt-6">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-200 font-bold">
                                <input type="checkbox" name="is_active" value="1" <?= !empty($welfareGym['is_active']) ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600 rounded bg-slate-900 border-slate-700">
                                <span>سالن دایر و فعال باشد</span>
                            </label>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold cursor-pointer transition-all shadow-md mr-auto">
                                ذخیره تنظیمات سالن ✓
                            </button>
                        </div>
                    </form>
                </div>

                <!-- تقویم خورشیدی و مدیریت هوشمند سانس‌ها (خلوت، منظم و مدرن) -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                    <!-- سربرگ بخش تقویم همراه با کنترل‌ها و راهنما -->
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-slate-800 pb-4">
                        <div>
                            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                                <span class="p-1.5 bg-emerald-500/10 text-emerald-400 rounded-lg border border-emerald-500/20">📅</span>
                                <span>تقویم خورشیدی و زمان‌بندی سانس‌های سالن بدنسازی (۰۸:۰۰ الی ۲۲:۰۰)</span>
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-1">
                                سانس‌های ۲ ساعته استاندارد، تفکیک زمان ناهار و نماز (۱۲ الی ۱۴)، وضعیت پر و خالی بودن، رزرو تیم و لغو نوبت با عودت وجه
                            </p>
                        </div>

                        <!-- ابزارهای تعویض نما و فیلتر سریع -->
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- سوئیچ بین نمای کارتی مدرن و نمای جدول فشرده -->
                            <div class="inline-flex p-1 bg-slate-900 border border-slate-800 rounded-xl text-xs">
                                <button type="button" id="gymViewBtnGrid" onclick="setGymViewMode('grid')" class="px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer bg-emerald-600 text-white shadow-xs flex items-center gap-1.5">
                                    <span>🗂️</span>
                                    <span>نمای کارتی روزانه</span>
                                </button>
                                <button type="button" id="gymViewBtnTable" onclick="setGymViewMode('table')" class="px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer text-slate-400 hover:text-slate-200 flex items-center gap-1.5">
                                    <span>📋</span>
                                    <span>نمای جدول یکپارچه</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php
                    // استخراج تاریخ‌های یکتا جهت ساخت تب‌های روزانه تقویم خورشیدی
                    $uniqueDates = [];
                    $slotsByDate = [];
                    foreach ($gymSlots as $s) {
                        $dKey = $s['slot_date_jalali'];
                        if (!in_array($dKey, $uniqueDates)) {
                            $uniqueDates[] = $dKey;
                        }
                        if (!isset($slotsByDate[$dKey])) {
                            $slotsByDate[$dKey] = [
                                'day_name_fa' => $s['day_name_fa'],
                                'date_jalali' => $dKey,
                                'slots' => [],
                                'total_slots' => 0,
                                'open_slots' => 0,
                                'team_slots' => 0,
                                'blocked_slots' => 0,
                                'total_bookings' => 0
                            ];
                        }
                        $slotsByDate[$dKey]['slots'][] = $s;
                        $slotsByDate[$dKey]['total_slots']++;
                        if (!empty($s['is_blocked'])) {
                            $slotsByDate[$dKey]['blocked_slots']++;
                        } elseif (!empty($s['is_team_reserved'])) {
                            $slotsByDate[$dKey]['team_slots']++;
                        } else {
                            $slotsByDate[$dKey]['open_slots']++;
                        }
                        $slotsByDate[$dKey]['total_bookings'] += intval($s['booked_count'] ?? 0);
                    }
                    $firstDate = !empty($uniqueDates) ? $uniqueDates[0] : '';
                    ?>

                    <?php if (empty($gymSlots)): ?>
                        <div class="p-8 text-center bg-slate-900/60 rounded-xl border border-slate-800 text-slate-400 space-y-3">
                            <span class="text-3xl block">🏋️‍♂️</span>
                            <p class="font-bold text-slate-300">هنوز سانسی برای سالن بدنسازی ثبت نشده است.</p>
                            <p class="text-xs text-slate-500">جهت زمان‌بندی خودکار سانس‌های استاندارد ۸ الی ۲۲، بر روی دکمه «تولید و همگام‌سازی سانس‌های ۷ روز آینده» در بالای صفحه کلیک فرمایید.</p>
                        </div>
                    <?php else: ?>
                        <!-- نوار تب‌های انتخاب روز خورشیدی (شنبه تا جمعه) -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                                    <span>🗓️</span>
                                    <span>انتخاب روز تقویم خورشیدی:</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono" id="gymCurrentSelectedDateLabel">
                                    نمایش سانس‌های: <strong class="text-emerald-400"><?= htmlspecialchars($firstDate) ?></strong>
                                </span>
                            </div>

                            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                                <!-- دکمه نمایش تمام روزها -->
                                <button type="button" onclick="selectGymDateTab('all', this)" class="gym-date-tab px-3.5 py-2 rounded-xl border border-slate-800 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-all cursor-pointer shrink-0 flex items-center gap-1.5 bg-slate-900">
                                    <span>🌐</span>
                                    <span>تمام ۷ روز</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-slate-800 text-slate-400"><?= count($gymSlots) ?></span>
                                </button>

                                <?php foreach ($slotsByDate as $dKey => $dayData): ?>
                                    <?php $isActive = ($dKey === $firstDate); ?>
                                    <button type="button" 
                                            onclick="selectGymDateTab('<?= htmlspecialchars($dKey) ?>', this)" 
                                            class="gym-date-tab px-3.5 py-2 rounded-xl border text-xs font-bold transition-all cursor-pointer shrink-0 flex flex-col items-start gap-1 <?= $isActive ? 'bg-emerald-600/20 border-emerald-500/50 text-emerald-300 shadow-sm active-tab' : 'bg-slate-900/80 border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800' ?>"
                                            data-date="<?= htmlspecialchars($dKey) ?>">
                                        <div class="flex items-center gap-1.5">
                                            <span><?= htmlspecialchars($dayData['day_name_fa']) ?></span>
                                            <span class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($dKey) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1 text-[10px]">
                                            <span class="text-emerald-400 font-mono"><?= $dayData['open_slots'] ?> سانس آزاد</span>
                                            <?php if ($dayData['team_slots'] > 0): ?>
                                                <span class="text-amber-400 font-mono">• <?= $dayData['team_slots'] ?> تیم</span>
                                            <?php endif; ?>
                                            <?php if ($dayData['total_bookings'] > 0): ?>
                                                <span class="text-indigo-300 font-mono">• <?= $dayData['total_bookings'] ?> رزرو</span>
                                            <?php endif; ?>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ========== ۱. نمای کارتی منظم و خلوت (پیش‌فرض) ========== -->
                        <div id="gymGridView" class="space-y-6">
                            <?php foreach ($slotsByDate as $dKey => $dayData): ?>
                                <div class="gym-day-section space-y-3" data-date="<?= htmlspecialchars($dKey) ?>">
                                    <div class="flex items-center justify-between border-r-4 border-emerald-500 pr-3 py-1">
                                        <div class="flex items-center gap-2">
                                            <h5 class="text-xs font-bold text-white"><?= htmlspecialchars($dayData['day_name_fa']) ?></h5>
                                            <span class="font-mono text-cyan-300 text-xs">(<?= htmlspecialchars($dKey) ?>)</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            <span>مجموع نوبت‌های دانشجو:</span>
                                            <strong class="text-emerald-400 font-mono font-bold mr-1"><?= $dayData['total_bookings'] ?> رزرو</strong>
                                        </div>
                                    </div>

                                    <!-- گرید کارت‌های ۲ ساعته (۰۸:۰۰ الی ۲۲:۰۰) -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                                        <?php foreach ($dayData['slots'] as $slot): ?>
                                            <?php
                                                $isLunchTime = (substr($slot['start_time'], 0, 5) === '12:00');
                                                $isBlocked = !empty($slot['is_blocked']);
                                                $isTeam = !empty($slot['is_team_reserved']);
                                                $isFull = $slot['booked_count'] >= $slot['total_capacity'];
                                                $freeCap = max(0, $slot['total_capacity'] - $slot['booked_count']);
                                                $percent = min(100, round(($slot['booked_count'] / max(1, $slot['total_capacity'])) * 100));

                                                // رنگ و کلس پس‌زمینه کارت
                                                $cardBorder = "border-slate-800 hover:border-slate-700 bg-slate-900/90";
                                                if ($isLunchTime) {
                                                    $cardBorder = "border-amber-900/30 bg-slate-950/60 opacity-80";
                                                } elseif ($isBlocked) {
                                                    $cardBorder = "border-rose-900/40 bg-rose-950/10";
                                                } elseif ($isTeam) {
                                                    $cardBorder = "border-amber-700/40 bg-amber-950/15";
                                                } elseif ($isFull) {
                                                    $cardBorder = "border-rose-700/40 bg-rose-950/15";
                                                } elseif ($slot['booked_count'] > 0) {
                                                    $cardBorder = "border-indigo-800/50 bg-indigo-950/15";
                                                }
                                            ?>
                                            <div class="rounded-2xl border <?= $cardBorder ?> p-4 flex flex-col justify-between gap-3 shadow-md transition-all">
                                                <!-- سربرگ کارت: ساعت و وضعیت -->
                                                <div>
                                                    <div class="flex items-center justify-between gap-2 border-b border-slate-800/80 pb-2.5">
                                                        <div class="flex items-center gap-1.5 font-mono font-bold text-sm text-white">
                                                            <span class="text-slate-400">🕒</span>
                                                            <span><?= substr($slot['start_time'], 0, 5) ?></span>
                                                            <span class="text-slate-500 font-normal">تا</span>
                                                            <span><?= substr($slot['end_time'], 0, 5) ?></span>
                                                        </div>

                                                        <!-- برچسب وضعیت -->
                                                        <?php if ($isLunchTime): ?>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                                                🍽️ ناهار و استراحت
                                                            </span>
                                                        <?php elseif ($isBlocked): ?>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                                ⛔ مسدود موقت
                                                            </span>
                                                        <?php elseif ($isTeam): ?>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                                🏆 تیم دانشگاه
                                                            </span>
                                                        <?php elseif ($isFull): ?>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                                🔴 تکمیل ظرفیت
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                                🟢 آماده رزرو
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- محتوای کارت و ظرفیت -->
                                                    <div class="mt-2.5 space-y-2">
                                                        <?php if ($isLunchTime): ?>
                                                            <p class="text-[11px] text-amber-300/80 leading-relaxed">
                                                                بازه نماز، ناهار و تهویه هوای سالن (پردیس تعطیل است).
                                                            </p>
                                                        <?php elseif ($isBlocked): ?>
                                                            <div class="text-[11px] text-rose-300/90 leading-relaxed bg-rose-950/30 p-2 rounded-xl border border-rose-900/30">
                                                                <strong>علت مسدودی:</strong> <?= htmlspecialchars($slot['block_reason'] ?? 'توسط مدیریت') ?>
                                                            </div>
                                                        <?php elseif ($isTeam): ?>
                                                            <div class="bg-amber-950/30 p-2.5 rounded-xl border border-amber-900/30 space-y-1">
                                                                <div class="text-amber-200 font-bold text-xs flex items-center gap-1.5">
                                                                    <span>🥇</span>
                                                                    <span><?= htmlspecialchars($slot['reserved_team_name']) ?></span>
                                                                </div>
                                                                <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($slot['team_notes'] ?? 'تمرین اختصاصی اعضای تیم') ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <!-- نوار بار گرافیکی ظرفیت -->
                                                            <div class="space-y-1">
                                                                <div class="flex justify-between items-center text-[11px]">
                                                                    <span class="text-slate-400">ظرفیت صندلی/کمد:</span>
                                                                    <div class="font-mono">
                                                                        <strong class="<?= $freeCap > 0 ? 'text-white' : 'text-rose-400' ?>"><?= $slot['booked_count'] ?></strong>
                                                                        <span class="text-slate-500">/ <?= $slot['total_capacity'] ?> نفر</span>
                                                                    </div>
                                                                </div>
                                                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                                    <div class="h-full rounded-full transition-all <?= $percent >= 100 ? 'bg-rose-500' : ($percent >= 70 ? 'bg-amber-500' : 'bg-emerald-500') ?>" style="width: <?= $percent ?>%"></div>
                                                                </div>
                                                                <div class="flex justify-between items-center text-[10px] text-slate-400 pt-0.5">
                                                                    <span class="text-emerald-400 font-semibold font-mono"><?= $freeCap ?> جای خالی</span>
                                                                    <span class="text-slate-400 font-mono"><?= number_format(($welfareGym['hourly_rate_rials'] ?? 50000) * 2) ?> ریال</span>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- دکمه‌های کنترلی کارت (مرتب و فشرده) -->
                                                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-1.5 flex-wrap">
                                                    <?php if ($isBlocked): ?>
                                                        <!-- بازگشایی سانس مسدود شده -->
                                                        <form method="POST" action="?tab=welfare" class="w-full">
                                                            <input type="hidden" name="action_toggle_block_slot" value="1">
                                                            <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                                            <input type="hidden" name="set_blocked" value="0">
                                                            <button type="submit" class="w-full py-1.5 px-2 bg-emerald-500/20 hover:bg-emerald-600 text-emerald-300 hover:text-white rounded-xl text-[11px] font-bold transition-all cursor-pointer flex items-center justify-center gap-1 shadow-xs">
                                                                <span>🔓</span>
                                                                <span>بازگشایی و فعال‌سازی سانس</span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <div class="flex items-center gap-1.5 w-full">
                                                            <!-- مسدود کردن سانس -->
                                                            <button type="button" 
                                                                    onclick="openBlockSlotModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', '<?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?>')" 
                                                                    class="flex-1 py-1 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-[10px] transition-all cursor-pointer text-center" 
                                                                    title="مسدودسازی موقت سانس">
                                                                🔒 مسدود
                                                            </button>

                                                            <?php if (!$isTeam): ?>
                                                                <!-- رزرو برای تیم ورزشی -->
                                                                <button type="button" 
                                                                        onclick="openTeamLockModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', '<?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?>')" 
                                                                        class="flex-1 py-1 px-2 rounded-xl bg-amber-500/15 hover:bg-amber-500 text-amber-300 hover:text-slate-950 font-bold text-[10px] transition-all cursor-pointer text-center" 
                                                                        title="رزرو برای تیم ورزشی دانشگاه">
                                                                    🏆 رزرو تیم
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($slot['booked_count'] > 0): ?>
                                                        <!-- دکمه لغو نوبت‌ها و عودت وجه با شماره شمارنده -->
                                                        <button type="button" 
                                                                onclick="openCancelSlotModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', <?= intval($slot['booked_count'] ?? 0) ?>)" 
                                                                class="w-full mt-1 py-1.5 px-2 rounded-xl bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white font-bold text-[10px] transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-xs" 
                                                                title="لغو تمام نوبت‌های دانشجویان در این سانس و عودت مستقیم وجه به کیف پول">
                                                            <span>↩️</span>
                                                            <span>لغو نوبت‌ها و عودت وجه (<?= $slot['booked_count'] ?> نفر)</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ========== ۲. نمای جدول فشرده (برای گزارش‌گیری کلی) ========== -->
                        <div id="gymTableView" class="hidden overflow-x-auto">
                            <table class="w-full text-right text-xs" id="gymSlotsTable">
                                <thead>
                                    <tr class="bg-slate-900/90 text-slate-300 border-b border-slate-800">
                                        <th class="p-3">روز و تاریخ خورشیدی</th>
                                        <th class="p-3">ساعت سانس</th>
                                        <th class="p-3">وضعیت</th>
                                        <th class="p-3">ظرفیت و رزرو</th>
                                        <th class="p-3">تیم ورزشی / یادداشت</th>
                                        <th class="p-3 text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($gymSlots as $slot): ?>
                                        <?php
                                            $isLunchTime = (substr($slot['start_time'], 0, 5) === '12:00');
                                            $isBlocked = !empty($slot['is_blocked']);
                                            $isTeam = !empty($slot['is_team_reserved']);
                                            $isFull = $slot['booked_count'] >= $slot['total_capacity'];
                                            $freeCap = max(0, $slot['total_capacity'] - $slot['booked_count']);
                                        ?>
                                        <tr class="hover:bg-slate-800/40 gym-slot-row" data-date="<?= htmlspecialchars($slot['slot_date_jalali']) ?>">
                                            <td class="p-3 font-bold text-white">
                                                <span><?= htmlspecialchars($slot['day_name_fa']) ?></span>
                                                <span class="font-mono text-cyan-300 block text-[11px]"><?= htmlspecialchars($slot['slot_date_jalali']) ?></span>
                                            </td>

                                            <td class="p-3 font-mono font-bold text-sm text-slate-200">
                                                <?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?>
                                            </td>

                                            <td class="p-3">
                                                <?php if ($isLunchTime): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300">ناهار و استراحت</span>
                                                <?php elseif ($isBlocked): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300">مسدود ⛔</span>
                                                <?php elseif ($isTeam): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300">تیم ورزشی 🏆</span>
                                                <?php elseif ($isFull): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300">پر 🔴</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300">آزاد 🟢</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="p-3 font-mono">
                                                <span class="text-white font-bold"><?= $slot['booked_count'] ?></span>
                                                <span class="text-slate-400">/ <?= $slot['total_capacity'] ?> نفر</span>
                                                <span class="text-emerald-400 text-[10px] block font-bold">(<?= $freeCap ?> جای خالی)</span>
                                            </td>

                                            <td class="p-3">
                                                <?php if ($isTeam): ?>
                                                    <strong class="text-amber-300 text-xs block"><?= htmlspecialchars($slot['reserved_team_name']) ?></strong>
                                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($slot['team_notes'] ?? 'تمرین تیمی') ?></span>
                                                <?php elseif ($isBlocked): ?>
                                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($slot['block_reason'] ?? 'مسدود') ?></span>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400">نوبت‌گیری عمومی آزاد</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="p-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                    <?php if ($isBlocked): ?>
                                                        <form method="POST" action="?tab=welfare" class="inline">
                                                            <input type="hidden" name="action_toggle_block_slot" value="1">
                                                            <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                                            <input type="hidden" name="set_blocked" value="0">
                                                            <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500 text-emerald-300 hover:text-slate-950 font-bold text-[10px] cursor-pointer">
                                                                بازگشایی 🔓
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button" onclick="openBlockSlotModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', '<?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?>')" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[10px] cursor-pointer">
                                                            مسدود 🔒
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if (!$isBlocked && !$isTeam): ?>
                                                        <button type="button" onclick="openTeamLockModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', '<?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?>')" class="px-2 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 font-bold text-[10px] cursor-pointer">
                                                            تیم 🏆
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($slot['booked_count'] > 0): ?>
                                                        <button type="button" onclick="openCancelSlotModal(<?= intval($slot['id']) ?>, '<?= htmlspecialchars($slot['day_name_fa'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($slot['slot_date_jalali'] ?? '', ENT_QUOTES) ?>', <?= intval($slot['booked_count'] ?? 0) ?>)" class="px-2 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white font-bold text-[10px] cursor-pointer">
                                                            لغو و عودت (<?= $slot['booked_count'] ?>)
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- جدول تاریخچه نوبت‌ها و رزروهای دانشجویان -->
                <div class="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>📋</span>
                            <span>لیست نوبت‌های رزرو شده دانشجویان در سالن بدنسازی</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300">
                                <?= count($gymBookings) ?> نوبت
                            </span>
                        </h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                                    <th class="p-3">کد رهگیری / توکن</th>
                                    <th class="p-3">نام دانشجو / شماره دانشجویی</th>
                                    <th class="p-3">تاریخ و ساعت نوبت</th>
                                    <th class="p-3">مبلغ پرداختی</th>
                                    <th class="p-3">وضعیت نوبت</th>
                                    <th class="p-3">وضعیت عودت مالی</th>
                                    <th class="p-3 text-center">عملیات لغو نوبت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <?php if (empty($gymBookings)): ?>
                                    <tr>
                                        <td colspan="7" class="p-6 text-center text-slate-400">
                                            هنوز نوبتی توسط دانشجویان ثبت نگردیده است.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($gymBookings as $bk): ?>
                                        <tr class="hover:bg-slate-800/40">
                                            <td class="p-3">
                                                <strong class="font-mono text-cyan-300 block"><?= htmlspecialchars($bk['tracking_code']) ?></strong>
                                                <span class="text-[9px] font-mono text-slate-500 truncate max-w-[120px] block" title="<?= htmlspecialchars($bk['booking_token']) ?>">
                                                    <?= substr($bk['booking_token'], 0, 16) ?>...
                                                </span>
                                            </td>

                                            <td class="p-3">
                                                <span class="font-bold text-white block"><?= htmlspecialchars($bk['student_name']) ?></span>
                                                <span class="font-mono text-slate-400 text-[10px]">ش.د: <?= htmlspecialchars($bk['student_id']) ?> | <?= htmlspecialchars($bk['field_of_study'] ?? '') ?></span>
                                            </td>

                                            <td class="p-3 font-mono">
                                                <span class="text-amber-300 font-bold"><?= htmlspecialchars($bk['slot_date_jalali']) ?></span>
                                                <span class="text-slate-300 block text-[11px]"><?= htmlspecialchars($bk['start_time']) ?> الی <?= htmlspecialchars($bk['end_time']) ?></span>
                                            </td>

                                            <td class="p-3 font-mono">
                                                <strong class="text-emerald-400"><?= number_format($bk['amount_paid_rials']) ?></strong>
                                                <span class="text-slate-400 text-[10px]">ریال</span>
                                            </td>

                                            <td class="p-3">
                                                <?php if ($bk['status'] === 'confirmed'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        تایید شده ✓
                                                    </span>
                                                <?php elseif ($bk['status'] === 'cancelled_by_manager'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        لغو توسط مدیر ✕
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300">
                                                        حاضر شده
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="p-3">
                                                <?php if ($bk['refund_status'] === 'refunded_to_wallet'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                                        عودت داده شد به کیف پول
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-slate-500 text-[11px]">- تسویه کامل -</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="p-3 text-center">
                                                <?php if ($bk['status'] === 'confirmed'): ?>
                                                    <!-- دکمه لغو نوبت تکی دانشجو با عودت به کیف پول -->
                                                    <form method="POST" action="?tab=welfare" onsubmit="return confirm('آیا از لغو نوبت دانشجو «<?= htmlspecialchars($bk['student_name']) ?>» (کد پیگیری: <?= htmlspecialchars($bk['tracking_code']) ?>) و بازگرداندن مبلغ <?= number_format($bk['amount_paid_rials']) ?> ریال به کیف پول او اطمینان دارید؟');" class="inline">
                                                        <input type="hidden" name="action_cancel_single_booking" value="1">
                                                        <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer shadow-xs" title="لغو این نوبت و عودت مستقیم وجه به کیف پول دانشجو">
                                                            لغو نوبت و عودت وجه ↩️
                                                        </button>
                                                    </form>
                                                <?php elseif ($bk['status'] === 'cancelled_by_manager'): ?>
                                                    <span class="text-rose-400 font-mono text-[10px] font-bold">لغو و تسویه شده ✓</span>
                                                <?php else: ?>
                                                    <span class="text-slate-500 text-[10px]">-</span>
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

            <!-- ===================== TAB 11: JWT AUTHORIZATION & RBAC ===================== -->
            <div id="tab-authjwt" class="tab-pane hidden space-y-6">
                <!-- ردیف شاخص‌های کلیدی دسترسی (Tabular Numerals) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-4">
                        <div class="text-[11px] text-slate-400 font-semibold">توکن‌های JWT فعال</div>
                        <div class="mt-2 text-2xl font-black text-emerald-400 font-mono tabular-nums"><?= number_format($jwtActiveCount) ?></div>
                        <div class="mt-1 text-[11px] text-slate-500">دارای اعتبار زمانی و ابطال‌نشده</div>
                    </div>
                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-4">
                        <div class="text-[11px] text-slate-400 font-semibold">توکن‌های لغو / ابطال‌شده</div>
                        <div class="mt-2 text-2xl font-black text-rose-400 font-mono tabular-nums"><?= number_format($jwtRevokedCount) ?></div>
                        <div class="mt-1 text-[11px] text-slate-500">قطع دسترسی لحظه‌ای در MySQL</div>
                    </div>
                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-4">
                        <div class="text-[11px] text-slate-400 font-semibold">مجوزهای فعال ماتریس RBAC</div>
                        <div class="mt-2 text-2xl font-black text-indigo-400 font-mono tabular-nums"><?= number_format($rbacActiveCount) ?></div>
                        <div class="mt-1 text-[11px] text-slate-500">تنظیم‌شده روی ۳ نقش اصلی سامانه</div>
                    </div>
                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-4">
                        <div class="text-[11px] text-slate-400 font-semibold">رویدادهای ممیزی امنیتی</div>
                        <div class="mt-2 text-2xl font-black text-amber-400 font-mono tabular-nums"><?= number_format(count($authAuditLogs)) ?></div>
                        <div class="mt-1 text-[11px] text-slate-500">صدور، ابطال و کنترل خطاهای 401/403</div>
                    </div>
                </div>

                <!-- مشخصات توکن جاری مدیر + عملیات نشست -->
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-bold text-white">نشست JWT فعال شما (الگوریتم HS256)</h3>
                            <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-mono text-[11px]">HS256 · HttpOnly + Bearer</span>
                        </div>
                        <div class="text-xs text-slate-400 flex flex-wrap items-center gap-x-4 gap-y-1 font-mono tabular-nums">
                            <span>شناسه JTI: <strong class="text-slate-200"><?= htmlspecialchars($jwtAuth['jti']) ?></strong></span>
                            <span>صاحب توکن (sub): <strong class="text-slate-200"><?= htmlspecialchars($jwtAuth['sub']) ?></strong></span>
                            <span>انقضا: <strong class="text-emerald-300"><?= htmlspecialchars(date('Y-m-d H:i', (int)$jwtAuth['exp'])) ?></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap shrink-0">
                        <button
                            type="button"
                            onclick='openJwtPayloadModal(<?= json_encode([
                                "jti" => $jwtAuth["jti"],
                                "role" => $jwtAuth["role"],
                                "name" => $jwtAuth["name"],
                                "header" => $jwtAuth["header"] ?? ["alg" => "HS256", "typ" => "JWT"],
                                "payload" => $jwtAuth["payload"],
                                "permissions" => $jwtAuth["permissions"],
                            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold cursor-pointer transition-colors"
                        >
                            بازبینی Payload توکن
                        </button>
                        <form method="POST" action="?tab=authjwt" class="inline">
                            <input type="hidden" name="action_refresh_my_jwt" value="1">
                            <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold cursor-pointer transition-colors">
                                چرخش و تمدید توکن فعلی
                            </button>
                        </form>
                    </div>
                </div>

                <!-- جدول توکن‌های صادرشده و ابطال آنی -->
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">فهرست توکن‌های JWT صادرشده و کنترل نشست‌ها</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">با ابطال هر توکن، دسترسی کاربر در صفحات PHP و گراف تعاملی خوابگاه بلافاصله قطع می‌شود.</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="text" id="jwtSearchInput" oninput="filterJwtTable()" placeholder="جستجوی نام، شناسه یا JTI..." class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-indigo-500">
                            <select id="jwtRoleFilter" onchange="filterJwtTable()" class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                                <option value="all">همه نقش‌ها</option>
                                <option value="student">دانشجو</option>
                                <option value="supervisor">سرپرست</option>
                                <option value="manager">مدیر</option>
                            </select>
                            <form method="POST" action="?tab=authjwt" onsubmit="return confirm('تمامی توکن‌های فعال دانشجویان لغو شود؟');" class="inline">
                                <input type="hidden" name="action_revoke_role_jwts" value="1">
                                <input type="hidden" name="target_role" value="student">
                                <button type="submit" class="px-3 py-1.5 bg-rose-950/70 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 rounded-xl text-[11px] font-bold cursor-pointer transition-colors">
                                    ابطال همه توکن‌های دانشجو
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs border-collapse" id="jwtTokensTable">
                            <thead>
                                <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800">
                                    <th class="p-3 font-bold">کاربر / شناسه (sub)</th>
                                    <th class="p-3 font-bold">نقش</th>
                                    <th class="p-3 font-bold">شناسه توکن (JTI)</th>
                                    <th class="p-3 font-bold">صدور و انقضا</th>
                                    <th class="p-3 font-bold">وضعیت</th>
                                    <th class="p-3 font-bold text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($jwtTokensList)): ?>
                                    <tr>
                                        <td colspan="6" class="p-6 text-center text-slate-500">هنوز توکن JWT ثبت‌شده‌ای وجود ندارد.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jwtTokensList as $tok):
                                        $isRevoked = !empty($tok['revoked_at']);
                                        $isExpired = ((int)$tok['expires_at'] <= time());
                                        $isCurrentTok = ($tok['jti'] === $jwtAuth['jti']);
                                        $tokPerms = json_decode((string)($tok['permissions_json'] ?? '[]'), true);
                                        if (!is_array($tokPerms)) $tokPerms = [];
                                        $roleFa = $tok['role'] === 'manager' ? 'مدیر' : ($tok['role'] === 'supervisor' ? 'سرپرست' : 'دانشجو');
                                    ?>
                                        <tr class="jwt-row hover:bg-slate-900/60 transition-colors"
                                            data-role="<?= htmlspecialchars($tok['role']) ?>"
                                            data-search="<?= htmlspecialchars(mb_strtolower($tok['subject_name'] . ' ' . $tok['subject_id'] . ' ' . $tok['jti'])) ?>">
                                            <td class="p-3">
                                                <div class="font-bold text-slate-100 flex items-center gap-1.5">
                                                    <span><?= htmlspecialchars($tok['subject_name']) ?></span>
                                                    <?php if ($isCurrentTok): ?>
                                                        <span class="px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px]">نشست جاری</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-400 font-mono tabular-nums mt-0.5"><?= htmlspecialchars($tok['subject_id']) ?> · IP: <?= htmlspecialchars($tok['ip_address'] ?? '-') ?></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded border text-[11px] font-bold <?= $tok['role'] === 'manager' ? 'bg-indigo-950/70 text-indigo-300 border-indigo-500/30' : ($tok['role'] === 'supervisor' ? 'bg-amber-950/70 text-amber-300 border-amber-500/30' : 'bg-emerald-950/70 text-emerald-300 border-emerald-500/30') ?>">
                                                    <?= $roleFa ?>
                                                </span>
                                            </td>
                                            <td class="p-3 font-mono tabular-nums text-[11px] text-slate-300">
                                                <?= htmlspecialchars(substr($tok['jti'], 0, 12)) ?>...
                                            </td>
                                            <td class="p-3 font-mono tabular-nums text-[11px] text-slate-400">
                                                <div>صدور: <?= htmlspecialchars(date('m/d H:i', (int)$tok['issued_at'])) ?></div>
                                                <div>انقضا: <?= htmlspecialchars(date('m/d H:i', (int)$tok['expires_at'])) ?></div>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($isRevoked): ?>
                                                    <span class="px-2 py-0.5 rounded bg-rose-500/15 text-rose-300 border border-rose-500/30 text-[11px] font-bold">لغوشده</span>
                                                    <?php if (!empty($tok['revoke_reason'])): ?>
                                                        <div class="text-[10px] text-slate-500 mt-0.5"><?= htmlspecialchars($tok['revoke_reason']) ?></div>
                                                    <?php endif; ?>
                                                <?php elseif ($isExpired): ?>
                                                    <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30 text-[11px] font-bold">منقضی</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold">فعال</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        onclick='openJwtPayloadModal(<?= json_encode([
                                                            "jti" => $tok["jti"],
                                                            "role" => $tok["role"],
                                                            "name" => $tok["subject_name"],
                                                            "header" => ["alg" => "HS256", "typ" => "JWT"],
                                                            "payload" => [
                                                                "iss" => "samad-tvu-portal",
                                                                "sub" => $tok["subject_id"],
                                                                "role" => $tok["role"],
                                                                "name" => $tok["subject_name"],
                                                                "jti" => $tok["jti"],
                                                                "iat" => (int)$tok["issued_at"],
                                                                "exp" => (int)$tok["expires_at"],
                                                            ],
                                                            "permissions" => $tokPerms,
                                                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                        class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-[11px] font-bold cursor-pointer"
                                                    >
                                                        Payload
                                                    </button>
                                                    <?php if (!$isRevoked && !$isExpired && !$isCurrentTok): ?>
                                                        <form method="POST" action="?tab=authjwt" onsubmit="return confirm('آیا از لغو آنی توکن JWT «<?= htmlspecialchars($tok['subject_name']) ?>» اطمینان دارید؟');" class="inline">
                                                            <input type="hidden" name="action_revoke_jwt" value="1">
                                                            <input type="hidden" name="target_jti" value="<?= htmlspecialchars($tok['jti']) ?>">
                                                            <input type="hidden" name="revoke_reason" value="ابطال آنی از کنسول مدیر">
                                                            <button type="submit" class="px-2.5 py-1 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 rounded-lg text-[11px] font-bold cursor-pointer transition-colors">
                                                                لغو آنی
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ماتریس مجوزهای نقش (RBAC) -->
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-white">ماتریس مجوزهای دسترسی نقش‌محور (RBAC Permissions)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">تغییرات اعمال‌شده روی هر نقش، بلافاصله در اعتبارسنجی صفحات و درخواست‌های API کنترل می‌شود.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <?php
                        $roleTitlesMap = [
                            'student' => ['title' => 'نقش دانشجو (student)', 'border' => 'border-emerald-800/60', 'btn' => 'bg-emerald-600 hover:bg-emerald-500'],
                            'supervisor' => ['title' => 'نقش سرپرست شبانه (supervisor)', 'border' => 'border-amber-800/60', 'btn' => 'bg-amber-600 hover:bg-amber-500'],
                            'manager' => ['title' => 'نقش مدیر دانشگاه (manager)', 'border' => 'border-indigo-800/60', 'btn' => 'bg-indigo-600 hover:bg-indigo-500'],
                        ];
                        foreach ($roleTitlesMap as $rKey => $rMeta):
                            $rPerms = $rbacRowsByRole[$rKey] ?? [];
                        ?>
                            <form method="POST" action="?tab=authjwt" class="bg-slate-950/80 border <?= $rMeta['border'] ?> rounded-xl p-4 flex flex-col justify-between space-y-4">
                                <div class="space-y-3">
                                    <input type="hidden" name="action_update_role_permissions" value="1">
                                    <input type="hidden" name="edit_role" value="<?= htmlspecialchars($rKey) ?>">
                                    <h4 class="text-xs font-extrabold text-white border-b border-slate-800 pb-2"><?= htmlspecialchars($rMeta['title']) ?></h4>
                                    <div class="space-y-2">
                                        <?php if (empty($rPerms)): ?>
                                            <div class="p-3 rounded-lg bg-amber-950/40 border border-amber-500/30 text-[11px] text-amber-300 leading-relaxed">
                                                ردیف مجوزهای این نقش در پایگاه‌داده یافت نشد. لطفاً صفحه را یک بار بارگذاری مجدد (Refresh) کنید تا جدول به‌صورت خودکار ترمیم شود.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($rPerms as $rp):
                                                $isLocked = ($rKey === 'manager' && in_array($rp['permission_key'], ['auth:tokens:manage', 'dorm:rooms:manage'], true));
                                            ?>
                                                <label class="flex items-start gap-2.5 text-xs cursor-pointer p-1.5 rounded-lg hover:bg-slate-900">
                                                    <input
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value="<?= htmlspecialchars($rp['permission_key']) ?>"
                                                        <?= ((int)$rp['is_allowed'] === 1) ? 'checked' : '' ?>
                                                        <?= $isLocked ? 'disabled' : '' ?>
                                                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
                                                    >
                                                    <?php if ($isLocked): ?>
                                                        <input type="hidden" name="permissions[]" value="<?= htmlspecialchars($rp['permission_key']) ?>">
                                                    <?php endif; ?>
                                                    <div class="min-w-0">
                                                        <div class="text-slate-200 font-semibold leading-snug"><?= htmlspecialchars($rp['permission_label']) ?></div>
                                                        <div class="text-[10px] font-mono text-slate-500 mt-0.5"><?= htmlspecialchars($rp['permission_key']) ?><?= $isLocked ? ' (الزامی)' : '' ?></div>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button type="submit" <?= empty($rPerms) ? 'disabled' : '' ?> class="w-full py-2 <?= $rMeta['btn'] ?> disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold rounded-xl cursor-pointer transition-colors">
                                    ذخیره مجوزهای <?= htmlspecialchars($rKey) ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- گزارش ممیزی رویدادهای امنیتی -->
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-white">گزارش ممیزی امنیت و دسترسی‌ها (Security Audit Log)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">ثبت وقایع صدور توکن، ابطال نشست و خطاهای عدم مجوز (401 / 403)</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800">
                                    <th class="p-2.5 font-bold">رویداد</th>
                                    <th class="p-2.5 font-bold">کاربر / نقش</th>
                                    <th class="p-2.5 font-bold">مسیر / شرح</th>
                                    <th class="p-2.5 font-bold">کد HTTP</th>
                                    <th class="p-2.5 font-bold">زمان ثبت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($authAuditLogs)): ?>
                                    <tr>
                                        <td colspan="5" class="p-5 text-center text-slate-500">رویداد امنیتی ثبت نشده است.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($authAuditLogs as $logRow):
                                        $stCode = (int)$logRow['http_status'];
                                    ?>
                                        <tr class="hover:bg-slate-900/50">
                                            <td class="p-2.5 font-mono text-[11px] text-indigo-300"><?= htmlspecialchars($logRow['event_type']) ?></td>
                                            <td class="p-2.5 font-mono tabular-nums text-[11px] text-slate-300">
                                                <?= htmlspecialchars($logRow['actor_id'] ?? '-') ?> (<?= htmlspecialchars($logRow['actor_role'] ?? '-') ?>)
                                            </td>
                                            <td class="p-2.5 text-slate-200">
                                                <div><?= htmlspecialchars($logRow['detail']) ?></div>
                                                <div class="text-[10px] font-mono text-slate-500"><?= htmlspecialchars($logRow['endpoint'] ?? '') ?></div>
                                            </td>
                                            <td class="p-2.5 font-mono tabular-nums">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold <?= $stCode >= 400 ? 'bg-rose-500/15 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' ?>">
                                                    <?= $stCode ?>
                                                </span>
                                            </td>
                                            <td class="p-2.5 font-mono tabular-nums text-[11px] text-slate-400" dir="ltr"><?= htmlspecialchars($logRow['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===================== MODAL: LOCK SLOT FOR SPORTS TEAM ===================== -->
    <div id="teamLockModal" onclick="if(event.target === this) closeTeamLockModal()" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-md w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8 text-xs relative">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-lg">🏆</span>
                    <h3 class="text-sm font-bold text-white">رزرو اختصاصی سانس برای تیم ورزشی دانشگاه</h3>
                </div>
                <button type="button" onclick="closeTeamLockModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-1 font-mono text-[11px]">
                <div class="text-slate-300">روز و تاریخ سانس: <strong id="tlm-date" class="text-cyan-300"></strong></div>
                <div class="text-slate-300">ساعت سانس: <strong id="tlm-time" class="text-amber-300"></strong></div>
                <div id="team-lock-slot-info" class="text-slate-400 text-[10px] hidden"></div>
            </div>

            <form method="POST" action="?tab=welfare" class="space-y-4">
                <input type="hidden" name="action_team_reserve_slot" value="1">
                <input type="hidden" name="slot_id" id="tlm-slot-id" value="">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">انتخاب تیم ورزشی هدف *</label>
                    <select name="target_sports_team" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none font-bold">
                        <?php foreach ($allSportsTeams as $tm): ?>
                            <option value="<?= htmlspecialchars($tm) ?>"><?= htmlspecialchars($tm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">توضیحات و دستورالعمل تمرین تیم:</label>
                    <textarea name="team_notes" rows="2" placeholder="مثال: تمرین آمادگی جسمانی و وزنه‌زنی پیش از اعزام به مسابقات المپیاد" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none resize-none">تمرین اختصاصی و آمادگی جسمانی تیم در سالن بدنسازی</textarea>
                </div>

                <div class="p-3 bg-indigo-950/40 border border-indigo-500/30 rounded-xl text-[11px] text-indigo-200 leading-relaxed">
                    ℹ️ این سانس برای دانشجویان عادی مسدود شده و یک <strong>فراخوان تمرینی هدفمند</strong> در میزکار کلیه دانشجویان عضو این تیم منتشر خواهد شد.
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeTeamLockModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer">انصراف</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-slate-950 rounded-xl font-bold cursor-pointer shadow-md">
                        ثبت و قفل برای تیم ورزشی 🏆
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: BLOCK / CLOSE SLOT (SEPARATE FROM CANCELLATION) ===================== -->
    <div id="blockSlotModal" onclick="if(event.target === this) closeBlockSlotModal()" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-md w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8 text-xs relative">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-lg">🔒</span>
                    <h3 class="text-sm font-bold text-white">مسدودسازی موقت سانس سالن بدنسازی</h3>
                </div>
                <button type="button" onclick="closeBlockSlotModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <p class="text-slate-300 leading-relaxed">
                مسدودسازی نوبت‌گیری برای سانس <strong id="bsm-info" class="text-cyan-300"></strong>
                <br>
                <span class="text-[11px] text-slate-400 mt-1 block">با مسدود کردن، این تایم برای دانشجویان قفل شده و امکان نوبت‌گیری نخواهد داشت. هر زمان مایل باشید می‌توانید آن را مجدداً بازگشایی کنید.</span>
            </p>

            <form method="POST" action="?tab=welfare" class="space-y-4">
                <input type="hidden" name="action_toggle_block_slot" value="1">
                <input type="hidden" name="slot_id" id="bsm-slot-id" value="">
                <input type="hidden" name="set_blocked" value="1">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">دلیل مسدودسازی سانس *</label>
                    <input type="text" name="block_reason" id="bsm-reason" value="تعمیرات دوره‌ای، نظافت و ضدعفونی سالن" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none font-medium">
                </div>

                <div class="p-3 bg-amber-950/40 border border-amber-500/30 rounded-xl text-amber-200 text-[11px] leading-relaxed">
                    ℹ️ این عملیات سانس را مسدود می‌کند. اگر دانشجویی از قبل نوبت داشته باشد، نوبتش محفوظ می‌ماند؛ در صورتی که تمایل دارید نوبت‌های موجود لغو و هزینه عودت داده شود، از دکمه «لغو نوبت‌ها و عودت وجه» استفاده فرمایید.
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeBlockSlotModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer">انصراف</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-white rounded-xl font-bold cursor-pointer shadow-md">
                        تایید مسدودسازی سانس 🔒
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: CANCEL SLOT WITH WALLET REFUND ===================== -->
    <div id="cancelSlotModal" onclick="if(event.target === this) closeCancelSlotModal()" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-md w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8 text-xs relative">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-rose-400 text-lg">⚠️</span>
                    <h3 class="text-sm font-bold text-white">لغو نوبت‌های رزرو شده با عودت خودکار وجه به کیف پول</h3>
                </div>
                <button type="button" onclick="closeCancelSlotModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <p class="text-slate-300 leading-relaxed">
                آیا از لغو نوبت‌های دانشجویان در سانس <strong id="csm-date" class="text-white"></strong> اطمینان دارید؟
                <br>
                تعداد <strong id="csm-count" class="text-amber-300 font-mono"></strong> دانشجو در این سانس ثبت‌نام کرده‌اند.
            </p>

            <form method="POST" action="?tab=welfare" class="space-y-4">
                <input type="hidden" name="action_manager_cancel_slot" value="1">
                <input type="hidden" name="slot_id" id="csm-slot-id" value="">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">دلیل لغو نوبت‌ها:</label>
                    <input type="text" name="cancellation_reason" value="تعمیرات فنی اضطراری سالن / شرایط نامساعد" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-rose-500 outline-none">
                </div>

                <div class="flex items-center gap-2 p-3 bg-slate-900 rounded-xl border border-slate-800">
                    <input type="checkbox" name="block_slot_too" id="csm-block-too" value="1" class="w-4 h-4 rounded text-rose-600 focus:ring-0 cursor-pointer">
                    <label for="csm-block-too" class="text-slate-300 text-[11px] cursor-pointer">
                        این سانس نیز پس از لغو نوبت‌ها <strong>مسدود شود</strong> (در غیر این صورت، نوبت‌ها لغو و تسویه شده ولی سانس برای رزرو مجدد باز می‌ماند).
                    </label>
                </div>

                <div class="p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-rose-200 text-[11px] leading-relaxed">
                    ✓ مبلغ پرداخت‌شده کلیه دانشجویان ثبت‌نامی در این سانس بلافاصله و به صورت سیستمی به <strong>کیف پول الکترونیک</strong> آنها بازگردانده می‌شود.
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeCancelSlotModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer">انصراف</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl font-bold cursor-pointer shadow-md">
                        تایید لغو نوبت‌ها و عودت وجوه ↩️
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: PRINT OFFICIAL GYM SCHEDULE A4 ===================== -->
    <div id="gymPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-3xl max-w-4xl w-full p-8 shadow-2xl space-y-6 my-8 font-sans">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <h3 class="text-sm font-bold text-slate-900">پیش‌نمایش چاپ رسمی برنامه تقویم خورشیدی سالن بدنسازی</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold cursor-pointer">چاپ برگه A4 🖨</button>
                    <button type="button" onclick="closeGymPrintModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">بستن</button>
                </div>
            </div>

            <div class="border-2 border-slate-900 p-6 rounded-2xl space-y-4">
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl border border-slate-300 p-1 flex items-center justify-center">
                            <img src="assets/images/samad_campus_emblem_1790759744696.jpg" alt="نشان" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-sm font-black">دانشگاه ملی مهارت واحد پسران نیشابور</h2>
                            <h3 class="text-xs text-slate-700 font-bold">اداره تربیت بدنی و امور رفاهی | جدول زمان‌بندی سالن بدنسازی پردیس</h3>
                        </div>
                    </div>
                    <div class="text-left font-mono text-xs">
                        <div>تاریخ گزارش: <?= date('Y/m/d') ?></div>
                        <div>نوبت کاری: ۰۸:۰۰ الی ۲۲:۰۰</div>
                    </div>
                </div>

                <table class="w-full text-right text-xs border border-slate-300">
                    <thead class="bg-slate-200">
                        <tr>
                            <th class="p-2 border border-slate-300">روز و تاریخ خورشیدی</th>
                            <th class="p-2 border border-slate-300">ساعت سانس</th>
                            <th class="p-2 border border-slate-300">وضعیت سانس</th>
                            <th class="p-2 border border-slate-300">ظرفیت / ثبت‌نام</th>
                            <th class="p-2 border border-slate-300">تیم ورزشی اختصاصی</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gymSlots as $s): ?>
                            <tr>
                                <td class="p-2 border border-slate-300 font-bold"><?= $s['day_name_fa'] ?> (<?= $s['slot_date_jalali'] ?>)</td>
                                <td class="p-2 border border-slate-300 font-mono"><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?></td>
                                <td class="p-2 border border-slate-300">
                                    <?= !empty($s['is_blocked']) ? 'تعطیل / ناهار' : (!empty($s['is_team_reserved']) ? 'اختصاصی تیم ورزشی' : 'آزاد عمومی') ?>
                                </td>
                                <td class="p-2 border border-slate-300 font-mono"><?= $s['booked_count'] ?> از <?= $s['total_capacity'] ?> نفر</td>
                                <td class="p-2 border border-slate-300"><?= htmlspecialchars($s['reserved_team_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: ASSIGN UNHOUSED WITH GUEST DAYS ===================== -->
    <div id="assignModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4">
        <div class="bg-[#1e293b] rounded-2xl p-6 max-w-lg w-full border border-slate-700 shadow-2xl space-y-4 text-xs">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-sm font-bold text-white">تخصیص اتاق به دانشجو (<span id="modal-std-name"></span>)</h3>
                <button type="button" onclick="closeAssignModal()" class="text-slate-400 hover:text-white font-bold text-base cursor-pointer">✕</button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action_assign_unhoused" value="1">
                <input type="hidden" name="student_id" id="modal-std-id" value="">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">انتخاب اتاق (فقط اتاق‌های فعال و دارای ظرفیت خالی):</label>
                    <select name="room_id" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-bold">
                        <?php foreach ($allRooms as $rm): ?>
                            <?php if ($rm['is_active'] && $rm['free_beds'] > 0): ?>
                                <option value="<?= $rm['id'] ?>">
                                    اتاق <?= htmlspecialchars($rm['room_number']) ?> - <?= htmlspecialchars($rm['floor']) ?> (<?= $rm['free_beds'] ?> تخت خالی از <?= $rm['capacity'] ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">تعیین حالت خوابگاه دانشجو:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="p-2.5 bg-slate-900 border border-slate-700 rounded-xl text-center cursor-pointer hover:bg-slate-800">
                            <input type="radio" name="dorm_status" value="بدون شهریه" checked onchange="toggleGuestDaysInput(this.value)" class="mr-1">
                            <span>بدون شهریه</span>
                        </label>
                        <label class="p-2.5 bg-slate-900 border border-slate-700 rounded-xl text-center cursor-pointer hover:bg-slate-800">
                            <input type="radio" name="dorm_status" value="دارای شهریه" onchange="toggleGuestDaysInput(this.value)" class="mr-1">
                            <span>دارای شهریه</span>
                        </label>
                        <label class="p-2.5 bg-slate-900 border border-slate-700 rounded-xl text-center cursor-pointer hover:bg-slate-800">
                            <input type="radio" name="dorm_status" value="میهمان" onchange="toggleGuestDaysInput(this.value)" class="mr-1">
                            <span class="text-amber-300 font-bold">میهمان</span>
                        </label>
                    </div>
                </div>

                <!-- بخش تعیین زمان میهمانی با ۳ انتخابگر (روز / ماه / سال) و تعیین روزها -->
                <div id="guest-days-box" class="hidden p-4 bg-amber-950/40 border border-amber-500/40 rounded-2xl space-y-4">
                    <!-- ۱. انتخاب تعداد روزهای اقامت میهمانی -->
                    <div>
                        <label class="block text-amber-200 font-bold mb-1.5 flex items-center gap-1.5 text-xs">
                            <span>⏳</span>
                            <span>مدت زمان اقامت میهمانی (تعداد روزها) *:</span>
                        </label>
                        <div class="flex gap-1.5 flex-wrap mb-2">
                            <button type="button" onclick="setGuestDaysValue(3)" class="px-2.5 py-1 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 border border-slate-700 text-white rounded-lg text-[11px] font-bold cursor-pointer transition-all">۳ روز</button>
                            <button type="button" onclick="setGuestDaysValue(7)" class="px-2.5 py-1 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 border border-slate-700 text-white rounded-lg text-[11px] font-bold cursor-pointer transition-all">۷ روز (۱ هفته)</button>
                            <button type="button" onclick="setGuestDaysValue(14)" class="px-2.5 py-1 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 border border-slate-700 text-white rounded-lg text-[11px] font-bold cursor-pointer transition-all">۱۴ روز (۲ هفته)</button>
                            <button type="button" onclick="setGuestDaysValue(30)" class="px-2.5 py-1 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 border border-slate-700 text-white rounded-lg text-[11px] font-bold cursor-pointer transition-all">۳۰ روز (۱ ماه)</button>
                            <button type="button" onclick="setGuestDaysValue(60)" class="px-2.5 py-1 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 border border-slate-700 text-white rounded-lg text-[11px] font-bold cursor-pointer transition-all">۶۰ روز (۲ ماه)</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" id="guest_days_inp" name="guest_days" value="7" min="1" max="180" oninput="updateGuestEndDatePreview()" class="w-24 px-3 py-1.5 bg-slate-900 border border-amber-500/60 text-white font-mono font-bold text-center rounded-xl focus:border-amber-400 outline-none">
                            <span class="text-slate-300 text-xs">روز اقامت میهمانی</span>
                        </div>
                    </div>

                    <!-- ۲. تعیین تاریخ شروع میهمانی با ۳ سلکت آپشن (روز، ماه، سال) -->
                    <div>
                        <label class="block text-amber-200 font-bold mb-1.5 flex items-center gap-1.5 text-xs">
                            <span>📅</span>
                            <span>تاریخ شروع اقامت میهمانی *:</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- سلکت روز -->
                            <div>
                                <label class="block text-[10px] text-amber-300/80 mb-1">روز:</label>
                                <select id="guest_start_day" name="guest_start_day" onchange="updateGuestEndDatePreview()" class="w-full px-2.5 py-2 bg-slate-900 border border-amber-500/50 text-white rounded-xl text-xs font-mono font-bold focus:border-amber-400 outline-none cursor-pointer">
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= sprintf('%02d', $d) ?>" <?= $d === 25 ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- سلکت ماه -->
                            <div>
                                <label class="block text-[10px] text-amber-300/80 mb-1">ماه:</label>
                                <select id="guest_start_month" name="guest_start_month" onchange="updateGuestEndDatePreview()" class="w-full px-2 py-2 bg-slate-900 border border-amber-500/50 text-white rounded-xl text-xs font-bold focus:border-amber-400 outline-none cursor-pointer">
                                    <option value="01">فروردین (۰۱)</option>
                                    <option value="02">اردیبهشت (۰۲)</option>
                                    <option value="03">خرداد (۰۳)</option>
                                    <option value="04">تیر (۰۴)</option>
                                    <option value="05">مرداد (۰۵)</option>
                                    <option value="06">شهریور (۰۶)</option>
                                    <option value="07" selected>مهر (۰۷)</option>
                                    <option value="08">آبان (۰۸)</option>
                                    <option value="09">آذر (۰۹)</option>
                                    <option value="10">دی (۱۰)</option>
                                    <option value="11">بهمن (۱۱)</option>
                                    <option value="12">اسفند (۱۲)</option>
                                </select>
                            </div>

                            <!-- سلکت سال -->
                            <div>
                                <label class="block text-[10px] text-amber-300/80 mb-1">سال:</label>
                                <select id="guest_start_year" name="guest_start_year" onchange="updateGuestEndDatePreview()" class="w-full px-2.5 py-2 bg-slate-900 border border-amber-500/50 text-white rounded-xl text-xs font-mono font-bold focus:border-amber-400 outline-none cursor-pointer">
                                    <option value="1402">۱۴۰۲</option>
                                    <option value="1403">۱۴۰۳</option>
                                    <option value="1404">۱۴۰۴</option>
                                    <option value="1405" selected>۱۴۰۵</option>
                                    <option value="1406">۱۴۰۶</option>
                                    <option value="1407">۱۴۰۷</option>
                                    <option value="1408">۱۴۰۸</option>
                                </select>
                            </div>
                        </div>

                        <!-- فیلد مخفی همگام‌ساز تاریخ شروع -->
                        <input type="hidden" name="guest_start_date" id="guest_start_date_inp" value="1405/07/25">
                        <span class="text-[10px] text-amber-300/80 block mt-1">مدیر تعداد روزها را تعیین کرده و با ۳ سلکت فوق تاریخ شروع را مشخص می‌نماید.</span>
                    </div>

                    <!-- ۳. پیش‌نمایش زنده و محاسبه دقیق تاریخ پایان اقامت -->
                    <div id="guest-end-date-preview" class="p-3 bg-amber-900/30 rounded-xl border border-amber-500/40 text-xs text-amber-200 space-y-1.5 shadow-inner">
                        <div class="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                            <span class="text-slate-300">📅 شروع میهمانی:</span>
                            <span id="preview-guest-start-date" class="font-mono font-bold text-amber-300">۱۴۰۵/۰۷/۲۵</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                            <span class="text-slate-300">⏱ مدت زمان اقامت:</span>
                            <span id="preview-guest-days-count" class="font-bold text-white">۷ روز</span>
                        </div>
                        <div class="flex items-center justify-between pt-0.5">
                            <span class="font-bold text-amber-200">🏁 تاریخ پایان و سررسید:</span>
                            <strong id="preview-guest-end-date" class="font-mono font-bold text-emerald-300 text-sm">۱۴۰۵/۰۸/۰۲</strong>
                        </div>
                        <p class="text-[10px] text-amber-300/70 pt-1 leading-relaxed">
                            ℹ️ پس از سررسید این تاریخ، وضعیت اسکان منقضی شده و دانشجو به صورت خودکار به لیست متقاضیان خوابگاه بازمی‌گردد.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl">انصراف</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-bold rounded-xl">تایید و صدور اسکان</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: EDIT DORM STATUS ===================== -->
    <div id="editDormModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4">
        <div class="bg-[#1e293b] rounded-2xl p-6 max-w-md w-full border border-slate-700 shadow-2xl space-y-4 text-xs">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-sm font-bold text-white">ویرایش اتاق و وضعیت اسکان (<span id="edit-std-id-lbl"></span>)</h3>
                <button type="button" onclick="closeEditDormModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action_update_student_dorm" value="1">
                <input type="hidden" name="student_id" id="edit-std-id-val" value="">

                <div>
                    <label class="block text-slate-300 mb-1">انتقال به اتاق جدید:</label>
                    <select name="new_room_id" id="edit-room-select" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl">
                        <?php foreach ($allRooms as $rm): ?>
                            <?php if ($rm['is_active']): ?>
                                <option value="<?= $rm['id'] ?>">اتاق <?= htmlspecialchars($rm['room_number']) ?> - <?= htmlspecialchars($rm['floor']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-300 mb-1">وضعیت خوابگاه:</label>
                    <select name="dorm_status" id="edit-dorm-status" onchange="toggleEditGuestDays(this.value)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl">
                        <option value="بدون شهریه">بدون شهریه</option>
                        <option value="دارای شهریه">دارای شهریه</option>
                        <option value="میهمان">میهمان</option>
                    </select>
                </div>

                <div id="edit-guest-box" class="hidden p-3 bg-amber-950/40 border border-amber-500/40 rounded-xl space-y-2">
                    <label class="block text-amber-200 font-bold">روزهای باقی‌مانده میهمانی:</label>
                    <input type="number" name="guest_days" id="edit-guest-days-val" value="7" min="1" max="90" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded font-mono text-center">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeEditDormModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl">انصراف</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: BATCH OFFICIAL A4 ATTENDANCE PRINT ===================== -->
    <div id="batchPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-2xl max-w-4xl w-full p-8 shadow-2xl space-y-6 my-8">
            <div class="flex justify-between items-center border-b pb-4 no-print">
                <h3 class="text-base font-bold text-slate-900">پیش‌نمایش چاپ رسمی گزارش سرشماری شبانه خوابگاه صمد</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer">
                        چاپ برگه A4 🖨
                    </button>
                    <button type="button" onclick="closeBatchPrintModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>

            <div class="p-6 border-2 border-slate-900 rounded-xl space-y-6">
                <!-- سربرگ رسمی -->
                <div class="flex justify-between items-center border-b-2 border-slate-900 pb-4">
                    <div class="w-14 h-14 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg">صمد</div>
                    <div class="text-center">
                        <h1 class="text-sm font-extrabold text-slate-900">پردیس فناوری و مهارت - معاونت امور دانشجویی</h1>
                        <h2 class="text-xs font-bold text-indigo-900">گزارش رسمی سرشماری شبانه خوابگاه صمد</h2>
                        <span class="text-[11px] text-slate-600">صورتجلسه رسمی حضور و غیاب شبانه</span>
                    </div>
                    <div class="text-left text-xs font-mono text-slate-700">
                        <div>تاریخ: <strong><?= htmlspecialchars($attendance_date) ?></strong></div>
                        <div>ساعت سرشماری: <strong>۲۳:۰۰</strong></div>
                    </div>
                </div>

                <!-- آمار سرشماری -->
                <div class="grid grid-cols-4 gap-2 text-center text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    <div>کل ساکنین: <strong><?= $totalResidents ?> نفر</strong></div>
                    <div>حاضرین: <strong class="text-emerald-700 font-bold"><?= $pCount ?> نفر</strong></div>
                    <div>غایبین: <strong class="text-rose-700 font-bold"><?= $aCount ?> نفر</strong></div>
                    <div>مرخصی: <strong class="text-amber-700 font-bold"><?= $lCount ?> نفر</strong></div>
                </div>

                <!-- جدول سرشماری -->
                <table class="w-full text-right text-xs border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300">
                            <th class="p-2 border-l border-slate-300 w-10 text-center">ردیف</th>
                            <th class="p-2 border-l border-slate-300">نام دانشجو</th>
                            <th class="p-2 border-l border-slate-300 text-center">شماره دانشجویی</th>
                            <th class="p-2 border-l border-slate-300 text-center">اتاق و طبقه</th>
                            <th class="p-2 border-l border-slate-300 text-center">وضعیت حضور</th>
                            <th class="p-2">توضیحات سرپرست شب</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($attendanceList as $idx => $att): ?>
                            <tr>
                                <td class="p-2 border-l border-slate-200 text-center font-mono"><?= $idx + 1 ?></td>
                                <td class="p-2 border-l border-slate-200 font-bold"><?= htmlspecialchars($att['full_name']) ?></td>
                                <td class="p-2 border-l border-slate-200 text-center font-mono"><?= htmlspecialchars($att['student_id']) ?></td>
                                <td class="p-2 border-l border-slate-200 text-center">اتاق <?= htmlspecialchars($att['room_number']) ?> (<?= htmlspecialchars($att['floor']) ?>)</td>
                                <td class="p-2 border-l border-slate-200 text-center font-bold">
                                    <?= htmlspecialchars($att['attendance_status']) ?>
                                </td>
                                <td class="p-2 text-[11px] text-slate-600"><?= htmlspecialchars($att['attendance_notes'] ?? 'تایید شده') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pt-6 grid grid-cols-2 gap-8 text-center text-xs">
                    <div class="space-y-12">
                        <span class="font-bold block">مهر و امضای سرپرست شیفت شب:</span>
                        <span class="text-slate-500 block">مهندس کامران قنبری</span>
                    </div>
                    <div class="space-y-12">
                        <span class="font-bold block">تایید و امضای مدیر امور خوابگاه‌ها:</span>
                        <span class="text-slate-500 block">دکتر علیرضا حسینی</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: INDIVIDUAL ATTENDANCE PRINT CERTIFICATE ===================== -->
    <div id="individualPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5 my-8">
            <div class="flex justify-between items-center border-b pb-3 no-print">
                <h3 class="text-sm font-bold text-slate-900">کارنامه تردد فردی دانشجو (<span id="indiv-name-lbl"></span>)</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold cursor-pointer">
                        چاپ گواهی 🖨
                    </button>
                    <button type="button" onclick="closeIndividualPrintModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>

            <div class="p-6 border-2 border-indigo-900 rounded-2xl space-y-5">
                <div class="flex justify-between items-center border-b border-indigo-900 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-900 text-white flex items-center justify-center font-bold text-base">صمد</div>
                        <div>
                            <h2 class="text-xs font-bold text-slate-700">پردیس فناوری و مهارت - امور دانشجویی</h2>
                            <h1 class="text-sm font-extrabold text-slate-900">گواهی حضور و کارنامه تردد سرشماری شبانه خوابگاه</h1>
                        </div>
                    </div>
                    <span class="font-mono text-xs text-indigo-900 font-bold"><?= htmlspecialchars($attendance_date) ?></span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <div>نام و نام خانوادگی: <strong id="indiv-name-val" class="text-slate-900"></strong></div>
                    <div>شماره دانشجویی: <strong id="indiv-id-val" class="font-mono text-indigo-700"></strong></div>
                    <div>کد ملی دانشجو: <strong id="indiv-nid-val" class="font-mono"></strong></div>
                    <div>اتاق و طبقه: <strong id="indiv-room-val"></strong></div>
                </div>

                <div class="p-4 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-indigo-950">وضعیت ثبت‌شده در سرشماری شبانه:</span>
                        <span id="indiv-status-val" class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-600 text-white"></span>
                    </div>
                    <div class="text-slate-700">
                        <span>ملاحظات سرپرست خوابگاه: </span>
                        <span id="indiv-notes-val" class="font-medium text-slate-900"></span>
                    </div>
                </div>

                <div class="pt-6 flex justify-between text-xs text-center">
                    <div class="space-y-8">
                        <span class="block font-bold">مهر و امضای سرپرست خوابگاه:</span>
                        <span class="block text-slate-500 font-mono">تاییدیه سرپرستی شب</span>
                    </div>
                    <div class="space-y-8">
                        <span class="block font-bold">مدیر امور دانشجویی دانشگاه:</span>
                        <span class="block text-slate-500">دکتر علیرضا حسینی</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: SUPERVISOR ID CARD ===================== -->
    <div id="supervisorCardModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4">
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-indigo-500/40 max-w-xl w-full relative">
            <div class="flex items-center justify-between pb-4 border-b border-indigo-700/60">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-md">صمد</div>
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
                    <span class="text-4xl mb-1">👨‍💼</span>
                    <span class="text-[10px] text-indigo-200">سرپرست رسمی</span>
                </div>

                <div class="flex-1 w-full space-y-3 text-xs">
                    <div>
                        <span class="text-[11px] text-indigo-300 block">نام و نام خانوادگی سرپرست:</span>
                        <span id="sup-card-name" class="text-base font-bold text-white mt-0.5 block"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-indigo-300 text-[11px] block">کد ملی (شناسه ورود):</span>
                            <span id="sup-card-nid" class="font-mono text-sm font-bold text-amber-300 tracking-wider"></span>
                        </div>
                        <div>
                            <span class="text-indigo-300 text-[11px] block">شیفت کاری:</span>
                            <span class="font-mono text-xs font-semibold text-slate-200">۲۲:۰۰ الی ۰۶:۰۰ (شبانه)</span>
                        </div>
                    </div>

                    <div class="p-3 bg-indigo-900/60 border border-indigo-600/50 rounded-xl space-y-1">
                        <div class="text-amber-300 font-bold text-xs">مسئولیت: سرپرست شیفت شب - طبقات همکف، ۱ و ۲</div>
                        <div class="text-slate-300 text-[10px]">🏢 ساختمان مرکزی خوابگاه صمد (پردیس فناوری و مهارت)</div>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-indigo-800/60 flex items-center justify-between text-xs">
                <span class="text-slate-400 text-[11px]">شناسه سرپرستی: <strong class="text-amber-300 font-mono">SUP-405-OFFICIAL</strong></span>
                <div class="flex gap-2">
                    <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer">
                        چاپ کارت 🖨
                    </button>
                    <button type="button" onclick="closeSupervisorCardModal()" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: ROOM FULL DETAILS & OCCUPANTS ===================== -->
    <div id="roomDetailsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-2xl w-full border border-slate-700 shadow-2xl p-6 space-y-5 my-8">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-sm">
                        🛏
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">پرونده کامل اتاق <span id="rdm-room-no" class="text-emerald-400 font-mono"></span> (<span id="rdm-floor"></span>)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">مشاهده عکس پرسنلی، مشخصات هویتی، استان محل سکونت و صدور کارت دانشجویی</p>
                    </div>
                </div>
                <button type="button" onclick="closeRoomDetailsModal()" class="text-slate-400 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <!-- Header Stats -->
            <div class="grid grid-cols-3 gap-3 p-3 bg-slate-900 rounded-xl border border-slate-800 text-xs text-center font-mono">
                <div>
                    <span class="text-slate-400 block text-[10px]">کل ظرفیت تخت</span>
                    <strong id="rdm-cap" class="text-white text-sm"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px]">ساکنین مستقر</span>
                    <strong id="rdm-occ-cnt" class="text-emerald-400 text-sm"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px]">تخت‌های خالی</span>
                    <strong id="rdm-free-cnt" class="text-amber-400 text-sm"></strong>
                </div>
            </div>

            <!-- Occupants List Container -->
            <div class="space-y-3 max-h-96 overflow-y-auto" id="rdm-occupants-list">
                <!-- Dynamically populated -->
            </div>

            <div class="pt-3 border-t border-slate-700 flex justify-end">
                <button type="button" onclick="closeRoomDetailsModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer">
                    بستن پنجره
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: SMART STUDENT ID CARD ===================== -->
    <div id="studentCardModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#0f172a] text-white rounded-3xl max-w-md w-full border border-indigo-500/40 shadow-2xl p-6 space-y-4 my-8 relative overflow-hidden">
            <div class="flex items-center justify-between border-b border-indigo-900/60 pb-3 no-print">
                <div class="flex items-center gap-2">
                    <span class="text-indigo-400 text-lg">🪪</span>
                    <h3 class="text-sm font-bold text-white">کارت شناسایی هوشمند دانشجو</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold cursor-pointer flex items-center gap-1">
                        <span>چاپ کارت 🖨</span>
                    </button>
                    <button type="button" onclick="closeStudentCardModal()" class="text-slate-400 hover:text-white font-bold p-1 cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Official University Card Template -->
            <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 border border-indigo-500/30 space-y-4 relative shadow-xl">
                <!-- Card Header -->
                <div class="flex items-center justify-between border-b border-indigo-800/60 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-white p-1 overflow-hidden shrink-0 border border-indigo-300/30">
                            <img src="assets/images/samad_campus_emblem_1790759744696.jpg" alt="لوگو صمد" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-xs font-black tracking-tight text-white">کارت شناسایی و اسکان صمد</h2>
                            <span id="sc-campus" class="text-[10px] text-indigo-300 block">پردیس فناوری صمد</span>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        معتبر ۱۴۰۵-۱۴۰۴ ✓
                    </span>
                </div>

                <!-- Photo & Details -->
                <div class="flex gap-4 items-center">
                    <div class="w-24 h-32 rounded-xl overflow-hidden border-2 border-indigo-400/40 bg-slate-800 shrink-0 shadow-md">
                        <img src="assets/images/student_sample_avatar_1790759772433.jpg" id="sc-photo" alt="تصویر دانشجو" class="w-full h-full object-cover">
                    </div>

                    <div class="space-y-1.5 text-xs flex-1">
                        <div>
                            <span class="text-[10px] text-slate-400 block">نام و نام خانوادگی:</span>
                            <strong id="sc-name" class="text-white text-sm"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block">شماره دانشجویی:</span>
                            <strong id="sc-sid" class="font-mono text-indigo-300"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block">کد ملی:</span>
                            <strong id="sc-nid" class="font-mono text-cyan-300"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block">رشته تحصیلی:</span>
                            <span id="sc-field" class="text-slate-200 text-[11px]"></span>
                        </div>
                    </div>
                </div>

                <!-- Residence & Room Badge -->
                <div class="p-3 bg-slate-950/70 rounded-xl border border-indigo-900/60 text-xs space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">محل سکونت در خوابگاه:</span>
                        <span id="sc-room" class="font-bold text-emerald-400"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">استان / محل زندگی:</span>
                        <span id="sc-province" class="text-slate-300"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">وضعیت اسکان:</span>
                        <span id="sc-dormstatus" class="text-indigo-300 font-bold"></span>
                    </div>
                </div>

                <!-- Card Footer Barcode & Stamp -->
                <div class="pt-2 border-t border-indigo-900/60 flex items-center justify-between text-[10px] text-slate-400">
                    <div class="space-y-0.5">
                        <div class="font-mono text-[9px] tracking-widest text-slate-400">||| | || |||| | ||| |||| |</div>
                        <span>بارکد اختصاصی احراز هویت دانشگاه</span>
                    </div>
                    <div class="text-center">
                        <span class="text-emerald-400 font-bold block">مهر دیجیتال صمد</span>
                        <span id="sc-stamp" class="text-[8px] text-slate-500 font-mono"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: EDIT SUPERVISOR SHIFT ===================== -->
    <div id="editSupervisorShiftModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-md w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-lg">⚙️</span>
                    <h3 class="text-sm font-bold text-white">تنظیم روزها و ساعات شیفت سرپرست</h3>
                </div>
                <button type="button" onclick="closeEditSupervisorShiftModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <p class="text-xs text-slate-300">
                سرپرست: <strong id="ess-sup-name" class="text-white"></strong>
            </p>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action_update_supervisor_shift" value="1">
                <input type="hidden" name="supervisor_id" id="ess-sup-id" value="">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">روزهای کاری شیفت:</label>
                    <input type="text" name="sup_shift_days" id="ess-shift-days" placeholder="مثال: شنبه تا چهارشنبه" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        <button type="button" onclick="document.getElementById('ess-shift-days').value='شنبه تا چهارشنبه'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">شنبه تا چهارشنبه</button>
                        <button type="button" onclick="document.getElementById('ess-shift-days').value='پنج‌شنبه و جمعه'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">پنج‌شنبه و جمعه</button>
                        <button type="button" onclick="document.getElementById('ess-shift-days').value='روزهای زوج'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">روزهای زوج</button>
                        <button type="button" onclick="document.getElementById('ess-shift-days').value='روزهای فرد'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">روزهای فرد</button>
                        <button type="button" onclick="document.getElementById('ess-shift-days').value='همه روزه'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">همه روزه</button>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">ساعات کاری شیفت:</label>
                    <input type="text" name="sup_shift_hours" id="ess-shift-hours" placeholder="مثال: ۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        <button type="button" onclick="document.getElementById('ess-shift-hours').value='۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)</button>
                        <button type="button" onclick="document.getElementById('ess-shift-hours').value='۰۸:۰۰ الی ۱۶:۰۰ (شیفت صبح)'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">۰۸:۰۰ الی ۱۶:۰۰ (شیفت صبح)</button>
                        <button type="button" onclick="document.getElementById('ess-shift-hours').value='۱۶:۰۰ الی ۲۴:۰۰ (شیفت عصر)'" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer">۱۶:۰۰ الی ۲۴:۰۰ (شیفت عصر)</button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeEditSupervisorShiftModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer">
                        انصراف
                    </button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl text-xs font-bold cursor-pointer shadow-md">
                        ذخیره تغییرات شیفت
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: ROOM FACILITIES ENTITY ===================== -->
    <div id="facilitiesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-xl w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-lg">🛋</span>
                    <h3 class="text-sm font-bold text-white">تکمیل و ویرایش شناسنامه امکانات اتاق <span id="fac-modal-room-title" class="text-amber-400 font-mono"></span></h3>
                </div>
                <button type="button" onclick="closeFacilitiesModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action_save_facilities" value="1">
                <input type="hidden" name="room_number" id="fac-form-room-number" value="">

                <!-- الگوهای سریع پیش‌فرض -->
                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 space-y-1.5">
                    <span class="text-[10px] text-amber-400 font-bold block">✨ الگوهای آماده تجهیزات (تکمیل خودکار با ۱ کلیک):</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="applyFacPreset('standard')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-300 text-[10px] font-bold transition-all cursor-pointer">اتاق ۴ تخته استاندارد</button>
                        <button type="button" onclick="applyFacPreset('elite')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-300 text-[10px] font-bold transition-all cursor-pointer">اتاق ۶ تخته نخبگان</button>
                        <button type="button" onclick="applyFacPreset('suite')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-300 text-[10px] font-bold transition-all cursor-pointer">سوئیت ۱۰ تخته نمونه</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">مدل و ابعاد یخچال:</label>
                        <input type="text" name="fridge_model" id="fac-fridge-model" placeholder="مثال: امرسان ۵ فوت استاندارد" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">نوع سازه تخت‌ها:</label>
                        <input type="text" name="bed_type" id="fac-bed-type" placeholder="مثال: تخت فلزی دو طبقه استاندارد" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">سیستم سرمایشی و گرمایشی:</label>
                        <input type="text" name="ac_heater" id="fac-ac-heater" placeholder="مثال: شوفاژ مرکزی + کولر آبی" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">تلویزیون دیواری (اختیاری):</label>
                        <input type="text" name="tv_model" id="fac-tv-model" placeholder="مثال: سامسونگ ۳۲ اینچ LED یا خالی" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                    </div>
                </div>

                <div class="space-y-2 pt-1 border-t border-slate-800">
                    <label class="block text-slate-300 font-semibold">ابزار نظافت و بهداشت اتاق:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_broom" id="fac-has-broom" value="1">
                            <span class="text-slate-300">جارو و خاک‌انداز</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_trash_bin" id="fac-has-trash" value="1">
                            <span class="text-slate-300">سطل زباله پدالی</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_bucket" id="fac-has-bucket" value="1">
                            <span class="text-slate-300">لگن و سطل شستشو</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-2 pt-1 border-t border-slate-800">
                    <label class="block text-slate-300 font-semibold">مبلمان و اقلام رفاهی:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_carpet" id="fac-has-carpet" value="1">
                            <span class="text-slate-300">فرش یا موکت</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_wardrobe" id="fac-has-wardrobe" value="1">
                            <span class="text-slate-300">کمد دیواری قفل‌دار</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_desk" id="fac-has-desk" value="1">
                            <span class="text-slate-300">میز و صندلی مطالعه</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_shoe_rack" id="fac-has-shoerack" value="1">
                            <span class="text-slate-300">جاکفشی طبقاتی</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-xl cursor-pointer">
                            <input type="checkbox" name="has_mirror" id="fac-has-mirror" value="1">
                            <span class="text-slate-300">آینه دیواری</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">توضیحات و مشخصات خاص اتاق:</label>
                    <input type="text" name="notes" id="fac-notes" placeholder="نکات فنی، وضعیت درب و پنجره، پرده و..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-700">
                    <button type="button" onclick="closeFacilitiesModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer">
                        انصراف
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold cursor-pointer shadow-md">
                        ذخیره و تکمیل شناسنامه اتاق ✓
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: RESOLVE MAINTENANCE & INVOICE ===================== -->
    <div id="maintenanceResolutionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-lg w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400 text-lg">📝</span>
                    <h3 class="text-sm font-bold text-white">ثبت اقدام مدیر و صدور فاکتور مالی خرابی</h3>
                </div>
                <button type="button" onclick="closeMaintenanceResolutionModal()" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
            </div>

            <!-- Reported Issue Summary Card -->
            <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 space-y-1.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">کد گزارش: <strong id="mrm-id" class="text-indigo-400 font-mono"></strong></span>
                    <span id="mrm-urgency" class="px-2 py-0.5 rounded text-[10px] font-bold"></span>
                </div>
                <div>محل خرابی: <strong id="mrm-room" class="text-white"></strong></div>
                <div>تجهیز نیازمند رسیدگی: <strong id="mrm-item" class="text-amber-300"></strong></div>
                <div class="text-[11px] text-slate-400">توضیح سرپرست: <span id="mrm-desc" class="text-slate-300"></span></div>
            </div>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action_resolve_maintenance" value="1">
                <input type="hidden" name="report_id" id="mrm-report-id" value="">

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">نوع اقدام انجام‌شده توسط مدیریت *</label>
                    <select name="action_taken" id="mrm-action-taken" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl">
                        <option value="repair">تعمیر و سرویس قطعه معیوب (تعمیراتی)</option>
                        <option value="replacement">خرید و جایگزینی تجهیز/قطعه نو (جایگزینی)</option>
                    </select>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <button type="button" onclick="setMaintPreset('سرویسکار مجاز و رفع عیب قطعه', 'تعمیرگاه مجاز لوازم خانگی پردیس', 3500000, 'INV-1403-1005', 'repair')" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-emerald-600 hover:text-white text-slate-300 text-[10px] transition-colors cursor-pointer">سرویسکار مجاز (۳۵۰ هزار تومان)</button>
                        <button type="button" onclick="setMaintPreset('خرید و نصب تجهیز نو با فاکتور', 'فروشگاه تاسیسات نوین مرکزی', 6800000, 'INV-1403-1006', 'replacement')" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-emerald-600 hover:text-white text-slate-300 text-[10px] transition-colors cursor-pointer">خرید نو با فاکتور (۶۸۰ هزار تومان)</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">مبلغ فاکتور / هزینه پرداختی (ریال) *</label>
                        <input type="number" name="cost_rials" id="mrm-cost" placeholder="مثال: 4500000" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">شماره فاکتور / رسید خرید:</label>
                        <input type="text" name="invoice_number" id="mrm-inv-num" placeholder="مثال: فک-۱۴۰۴-۰۸۹" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">نام فروشگاه / پیمانکار تعمیراتی:</label>
                    <input type="text" name="contractor_or_store_name" id="mrm-contractor" placeholder="مثال: تاسیسات مرکزی مهارت / فروشگاه لوازم خانگی ایران" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none">
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">توضیحات و گزارش تکمیلی اقدام مدیریت:</label>
                    <input type="text" name="resolution_notes" id="mrm-notes" placeholder="شرح قطعات تعویض شده، ضمانت و تحویل به سرپرست..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-700">
                    <button type="button" onclick="closeMaintenanceResolutionModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer">
                        انصراف
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold cursor-pointer shadow-md">
                        ثبت نهایی فاکتور و رفع مشکل ✓
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: VIEW SINGLE MAINTENANCE INVOICE ===================== -->
    <div id="singleInvoiceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl my-8 border border-slate-200">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📄</span>
                    <h3 class="text-sm font-bold text-slate-900">صورتحساب و سند مالی رسمی رفع خرابی</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="printSingleInvoice()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold cursor-pointer flex items-center gap-1 shadow-sm">
                        <span>چاپ فاکتور 🖨</span>
                    </button>
                    <button type="button" onclick="closeSingleInvoiceModal()" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">✕</button>
                </div>
            </div>

            <!-- Single Invoice Printable Card -->
            <div id="singleInvoicePrintable" class="p-6 bg-slate-50 rounded-2xl border border-slate-300 space-y-4 text-xs font-sans">
                <!-- Letterhead -->
                <div class="flex items-center justify-between border-b-2 border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl border border-slate-300 p-1 flex items-center justify-center bg-white">
                            <img src="assets/images/samad_campus_emblem_1790759744696.jpg" alt="نشان" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="text-xs font-black text-slate-900">دانشگاه ملی مهارت - خوابگاه صمد</div>
                            <div class="text-[10px] text-slate-600">فاکتور تسویه و هزینه خدمات رفاهی دانشجویی</div>
                        </div>
                    </div>
                    <div class="text-left font-mono text-[11px] text-slate-700 space-y-0.5">
                        <div>شماره فاکتور: <strong id="sim-inv-num" class="text-indigo-900 font-black"></strong></div>
                        <div>کد رهگیری: <span id="sim-code"></span></div>
                        <div>تاریخ اقدام: <span id="sim-date"></span></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 bg-white p-3.5 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-500 block">محل استقرار:</span>
                        <strong id="sim-room" class="text-slate-800"></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block">تجهیز / قطعه موضوع اقدام:</span>
                        <strong id="sim-item" class="text-indigo-800"></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block">نوع اقدام مدیریت:</span>
                        <span id="sim-action" class="font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block">فروشگاه یا پیمانکار مجری:</span>
                        <span id="sim-contractor" class="font-semibold text-slate-800"></span>
                    </div>
                </div>

                <div class="bg-indigo-50 border border-indigo-200 p-3 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-indigo-900 font-bold block">مبلغ نهایی فاکتور پرداختی:</span>
                        <span class="text-[10px] text-indigo-700">تایید شده توسط مدیریت امور رفاهی و دانشجویی</span>
                    </div>
                    <div class="text-left">
                        <span id="sim-cost" class="text-base font-black text-indigo-950 font-mono"></span>
                        <span class="text-[10px] text-indigo-800 block">ریال</span>
                    </div>
                </div>

                <div>
                    <span class="text-[10px] text-slate-500 block mb-0.5">شرح اقدام و قطعات تعویض شده:</span>
                    <p id="sim-notes" class="text-slate-700 bg-white p-2.5 rounded-lg border border-slate-200 text-[11px] leading-relaxed"></p>
                </div>

                <div class="pt-4 border-t border-slate-300 flex items-center justify-between text-[11px] text-slate-600">
                    <div>تاییدکننده نهایی: <strong id="sim-manager" class="text-slate-900"></strong></div>
                    <div class="text-emerald-700 font-bold">تسویه شده و رفع عیب کامل ✓</div>
                </div>
            </div>

            <div class="flex justify-between items-center gap-2 pt-2 border-t no-print">
                <button type="button" id="sim-edit-btn" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer transition-all">
                    ویرایش مجدد اطلاعات فاکتور ✏️
                </button>
                <div class="flex gap-2">
                    <button type="button" onclick="printSingleInvoice()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold cursor-pointer shadow-md">
                        چاپ فاکتور رسمی 🖨
                    </button>
                    <button type="button" onclick="closeSingleInvoiceModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: PRINT A4 MAINTENANCE EXPENSE REPORT ===================== -->
    <div id="maintenanceInvoicePrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-2xl max-w-4xl w-full p-8 space-y-6 shadow-2xl my-8 print:p-0 print:shadow-none print:m-0">
            <div class="flex items-center justify-between border-b pb-4 no-print">
                <span class="text-xs font-bold text-slate-600">پیش‌نمایش چاپ کارنامه مالی و فاکتورهای تعمیرات (A4)</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold cursor-pointer flex items-center gap-1.5 shadow-md">
                        <span>چاپ رسمی صورت‌حساب 🖨</span>
                    </button>
                    <button type="button" onclick="closeMaintenancePrintModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>

            <!-- Letterhead -->
            <div class="border-b-2 border-slate-900 pb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-xl border border-slate-300 p-1 flex items-center justify-center">
                        <img src="assets/images/samad_campus_emblem_1790759744696.jpg" alt="نشان دانشگاه" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h2 class="text-base font-black">دانشگاه ملی مهارت - وزارت علوم، تحقیقات و فناوری</h2>
                        <h3 class="text-xs font-bold text-slate-700">معاونت دانشجویی و فرهنگی | مدیریت امور خوابگاه‌ها</h3>
                    </div>
                </div>
                <div class="text-left text-xs font-mono space-y-1">
                    <div>تاریخ گزارش: <strong><?= date('Y/m/d') ?></strong></div>
                    <div>شماره سند: <strong>FAK-<?= date('Ymd') ?>-01</strong></div>
                    <div>پیوست: <strong>فاکتورهای رسمی</strong></div>
                </div>
            </div>

            <div class="text-center py-2 bg-slate-100 rounded-xl">
                <h1 class="text-sm font-black text-slate-900">صورت‌حساب رسمی و کارنامه تجمیعی هزینه‌های تعمیرات، سرویس و تجهیز خوابگاه صمد</h1>
            </div>

            <!-- Table -->
            <table class="w-full text-right text-xs border border-slate-300">
                <thead>
                    <tr class="bg-slate-200 text-slate-800 font-bold border-b border-slate-300">
                        <th class="p-2 border-l border-slate-300 text-center">ردیف</th>
                        <th class="p-2 border-l border-slate-300">شماره اتاق</th>
                        <th class="p-2 border-l border-slate-300">شرح عیب و قطعه</th>
                        <th class="p-2 border-l border-slate-300">نوع اقدام</th>
                        <th class="p-2 border-l border-slate-300">فروشگاه / پیمانکار</th>
                        <th class="p-2 border-l border-slate-300 font-mono">شماره فاکتور</th>
                        <th class="p-2 font-mono text-left">مبلغ (ریال)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php $mIdx = 1; ?>
                    <?php foreach ($maintenanceReports as $mr): ?>
                        <tr>
                            <td class="p-2 border-l border-slate-300 text-center font-mono"><?= $mIdx++ ?></td>
                            <td class="p-2 border-l border-slate-300 font-bold font-mono">اتاق <?= htmlspecialchars($mr['room_number']) ?> (<?= htmlspecialchars($mr['floor'] ?? '') ?>)</td>
                            <td class="p-2 border-l border-slate-300"><?= htmlspecialchars($mr['equipment_name'] ?? $mr['item_name'] ?? 'تجهیز خوابگاه') ?></td>
                            <td class="p-2 border-l border-slate-300"><?= ($mr['resolution_action'] ?? $mr['action_taken'] ?? '') === 'replacement' || str_contains($mr['resolution_action'] ?? '', 'خرید') ? 'خرید و جایگزینی نو' : 'تعمیر و سرویس قطعه' ?></td>
                            <td class="p-2 border-l border-slate-300"><?= htmlspecialchars($mr['contractor_or_store_name'] ?? '-') ?></td>
                            <td class="p-2 border-l border-slate-300 font-mono"><?= htmlspecialchars($mr['invoice_number'] ?? '-') ?></td>
                            <td class="p-2 font-mono font-bold text-left"><?= number_format($mr['cost_rials'] ?? $mr['resolution_cost'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-slate-100 font-black">
                        <td colspan="6" class="p-3 border-l border-slate-300 text-left">مجموع کل هزینه‌های فاکتور شده مصوب:</td>
                        <td class="p-3 font-mono text-left text-sm text-indigo-900"><?= number_format($totalMaintenanceCost) ?> ریال</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatures Box -->
            <div class="grid grid-cols-3 gap-6 pt-8 text-center text-xs text-slate-700">
                <div class="space-y-8">
                    <span>سرپرست شیفت شب خوابگاه</span>
                    <div class="border-b border-dashed border-slate-400 w-32 mx-auto"></div>
                    <span class="text-[10px] text-slate-500">امضا و تاریخ</span>
                </div>
                <div class="space-y-8">
                    <span>کارشناس امور مالی و تدارکات</span>
                    <div class="border-b border-dashed border-slate-400 w-32 mx-auto"></div>
                    <span class="text-[10px] text-slate-500">امضا و تایید مالی</span>
                </div>
                <div class="space-y-8">
                    <span>مدیر کل امور دانشجویی پردیس</span>
                    <div class="border-b border-dashed border-slate-400 w-32 mx-auto"></div>
                    <span class="text-[10px] text-slate-500">مهر و امضای نهایی</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: PRINT A4 CLEANLINESS REPORT ===================== -->
    <div id="cleanlinessPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white text-slate-900 rounded-2xl max-w-4xl w-full p-8 space-y-6 shadow-2xl my-8 print:p-0 print:shadow-none print:m-0">
            <div class="flex items-center justify-between border-b pb-4 no-print">
                <span class="text-xs font-bold text-slate-600">پیش‌نمایش چاپ کارنامه رسمی ارزیابی بهداشت و نظافت خوابگاه (A4)</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold cursor-pointer flex items-center gap-1.5 shadow-md">
                        <span>چاپ رسمی کارنامه 🖨</span>
                    </button>
                    <button type="button" onclick="closeCleanlinessPrintModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                        بستن
                    </button>
                </div>
            </div>

            <!-- Letterhead -->
            <div class="border-b-2 border-slate-900 pb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-xl border border-slate-300 p-1 flex items-center justify-center">
                        <img src="assets/images/samad_campus_emblem_1790759744696.jpg" alt="نشان دانشگاه" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h2 class="text-base font-black">دانشگاه ملی مهارت - وزارت علوم، تحقیقات و فناوری</h2>
                        <h3 class="text-xs font-bold text-slate-700">معاونت دانشجویی | اداره کل امور بهداشت و نظارت خوابگاهی</h3>
                    </div>
                </div>
                <div class="text-left text-xs font-mono space-y-1">
                    <div>تاریخ صدور: <strong><?= date('Y/m/d') ?></strong></div>
                    <div>کد ارزیابی: <strong>SAN-<?= date('Ymd') ?></strong></div>
                    <div>دوره: <strong>ارزیابی مستمر شبانه</strong></div>
                </div>
            </div>

            <div class="text-center py-2 bg-emerald-50 rounded-xl border border-emerald-200">
                <h1 class="text-sm font-black text-emerald-950">کارنامه رسمی ارزیابی، رتبه‌بندی عملکرد و کنترل بهداشت خوابگاه صمد</h1>
                <p class="text-xs text-emerald-800 mt-0.5">شاخص کلی پاکیزگی مجموعه: <strong><?= $overallCleanlinessScore ?> از ۴.۰</strong> (معادل <?= round(($overallCleanlinessScore / 4) * 100) ?>٪ رضایت بهداشتی)</p>
            </div>

            <!-- Table -->
            <table class="w-full text-right text-xs border border-slate-300">
                <thead>
                    <tr class="bg-slate-200 text-slate-800 font-bold border-b border-slate-300">
                        <th class="p-2 border-l border-slate-300 text-center">رتبه</th>
                        <th class="p-2 border-l border-slate-300">شماره اتاق</th>
                        <th class="p-2 border-l border-slate-300">طبقه</th>
                        <th class="p-2 border-l border-slate-300 font-mono text-center">میانگین امتیاز</th>
                        <th class="p-2 border-l border-slate-300 text-center">شاخص ستاره‌ای</th>
                        <th class="p-2 border-l border-slate-300 font-mono text-center">تعداد بازرسی</th>
                        <th class="p-2 text-center">وضعیت کیفی بهداشت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php $cIdx = 1; ?>
                    <?php foreach ($roomRankings as $rk): ?>
                        <tr>
                            <td class="p-2 border-l border-slate-300 text-center font-mono font-bold"><?= $cIdx++ ?></td>
                            <td class="p-2 border-l border-slate-300 font-bold font-mono">اتاق <?= htmlspecialchars($rk['room_number']) ?></td>
                            <td class="p-2 border-l border-slate-300"><?= htmlspecialchars($rk['floor']) ?></td>
                            <td class="p-2 border-l border-slate-300 font-mono font-bold text-center"><?= $rk['avg_score'] ?></td>
                            <td class="p-2 border-l border-slate-300 text-center">
                                <?= str_repeat('⭐', max(1, min(4, round($rk['avg_score'])))) ?>
                            </td>
                            <td class="p-2 border-l border-slate-300 font-mono text-center"><?= $rk['inspections_count'] ?> نوبت</td>
                            <td class="p-2 text-center font-bold">
                                <?php if ($rk['avg_score'] >= 3.5): ?>
                                    <span class="text-emerald-700">اتاق نمونه و ممتاز 🏆</span>
                                <?php elseif ($rk['avg_score'] >= 2.75): ?>
                                    <span class="text-blue-700">مطلوب و منظم ✓</span>
                                <?php else: ?>
                                    <span class="text-amber-700">نیازمند بهسازی و تذکر ⚠️</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Signatures Box -->
            <div class="grid grid-cols-2 gap-12 pt-8 text-center text-xs text-slate-700">
                <div class="space-y-8">
                    <span>سرپرست شیفت شب و مسئول نظارت خوابگاه</span>
                    <div class="border-b border-dashed border-slate-400 w-36 mx-auto"></div>
                    <span class="text-[10px] text-slate-500">امضا و تایید چک‌لیست بهداشتی</span>
                </div>
                <div class="space-y-8">
                    <span>مدیر امور دانشجویی و سرپرستی پردیس</span>
                    <div class="border-b border-dashed border-slate-400 w-36 mx-auto"></div>
                    <span class="text-[10px] text-slate-500">مهر رسمی دانشگاه و تایید کارنامه</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL: CREATE TARGETED FESTIVAL CALL ===================== -->
    <div id="createFestivalCallModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
        <div class="bg-[#1e293b] rounded-2xl max-w-xl w-full border border-slate-700 shadow-2xl flex flex-col max-h-[92vh] my-auto text-xs overflow-hidden">
            <!-- Modal Header (Fixed) -->
            <div class="flex items-center justify-between border-b border-slate-700/80 px-5 py-3.5 bg-slate-900/70 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-yellow-400 text-lg">📢</span>
                    <h3 class="text-sm font-bold text-white">صدور فراخوان هدفمند جدید برای دانشجویان</h3>
                </div>
                <button type="button" onclick="closeCreateCallModal()" class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold cursor-pointer transition-colors" title="بستن">✕</button>
            </div>

            <!-- Modal Form with Scrollable Body -->
            <form method="POST" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" name="action_create_festival_call" value="1">

                <div class="p-5 space-y-3.5 overflow-y-auto flex-1">
                    <!-- عنوان فراخوان و الگوهای آماده -->
                    <div>
                        <div class="flex items-center justify-between mb-1 flex-wrap gap-1">
                            <label class="block text-slate-300 font-semibold">عنوان فراخوان یا رویداد *</label>
                            <div class="flex items-center gap-1 flex-wrap">
                                <span class="text-[10px] text-slate-400">الگوهای آماده:</span>
                                <button type="button" onclick="setCallPreset('المپیاد ورزشی فوتبال پردیس', 'sports', 'فوتبال', 'از کلیه دانشجویان عضو تیم فوتبال جهت تمرین انتخابی دعوت به عمل می‌آید.')" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-yellow-500 hover:text-slate-950 text-slate-300 text-[10px] transition-colors cursor-pointer">⚽ فوتبال</button>
                                <button type="button" onclick="setCallPreset('مسابقات کشوری رباتیک و هوش مصنوعی', 'robotics', '', 'ثبت‌نام تیم‌های ربات‌های امدادگر و پرنده هوشمند دانشگاه.')" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-yellow-500 hover:text-slate-950 text-slate-300 text-[10px] transition-colors cursor-pointer">🤖 رباتیک</button>
                                <button type="button" onclick="setCallPreset('سی‌امین جشنواره سراسری قرآن و عترت', 'religious', '', 'مسابقات حفظ، قرائت، تواشیح و مقاله‌نویسی قرآنی دانشگاهیان.')" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-yellow-500 hover:text-slate-950 text-slate-300 text-[10px] transition-colors cursor-pointer">📖 قرآن</button>
                            </div>
                        </div>
                        <input type="text" name="call_title" id="call-title-inp" required placeholder="مثال: فراخوان عضویت در تیم فوتسال دانشگاه" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none font-bold">
                    </div>

                    <!-- دسته‌بندی و تیم هدف (در یک سطر دو ستونه) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">دسته‌بندی رویداد *</label>
                            <select name="call_category" id="call-category-select" onchange="toggleSportsTarget(this.value)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl">
                                <option value="sports">جشنواره‌های ورزشی و مسابقات پردیس ⚽</option>
                                <option value="technical">جشنواره‌ها و المپیادهای فنی و اختراعات ⚙️</option>
                                <option value="robotics">رویدادهای رباتیک و هوش مصنوعی 🤖</option>
                                <option value="cultural">جشنواره فرهنگی، ادبی و هنری 🎨</option>
                                <option value="religious">مسابقات و جشنواره‌های مذهبی و قرآنی 📖</option>
                                <option value="skillBased">جشنواره ملی مهارت و کارآفرینی 🛠️</option>
                                <option value="general">فراخوان عمومی پردیس 📢</option>
                            </select>
                        </div>

                        <div id="call-sports-target-box">
                            <label class="block text-slate-300 font-semibold mb-1">تیم ورزشی هدف دانشگاه:</label>
                            <select name="target_sports_team" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl">
                                <option value="">همه رشته‌های ورزشی</option>
                                <option value="فوتبال">فوتبال چمنی</option>
                                <option value="فوتسال">فوتسال سالنی</option>
                                <option value="والیبال">والیبال</option>
                                <option value="بسکتبال">بسکتبال</option>
                                <option value="تنیس روی میز">تنیس روی میز</option>
                                <option value="شنا">شنا و ورزش‌های آبی</option>
                                <option value="بدمینتون">بدمینتون</option>
                                <option value="دو و میدانی">دو و میدانی</option>
                                <option value="کشتی">کشتی آزاد و فرنگی</option>
                                <option value="تکواندو">تکواندو و رزمی</option>
                            </select>
                        </div>
                    </div>

                    <!-- بخش بازه زمانی فراخوان با دیت‌پیکرهای ۳ انتخابی (قرارگیری زیر هم) -->
                    <div class="p-3.5 bg-slate-900/90 rounded-2xl border border-indigo-500/40 space-y-2.5 shadow-inner">
                        <!-- دیت‌پیکرهای شروع و پایان به صورت کاملاً زیر هم -->
                        <div class="space-y-2">
                            <!-- ۱. انتخاب تاریخ شروع فراخوان -->
                            <div class="p-2.5 bg-slate-950/70 rounded-xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="text-indigo-300 font-bold text-xs flex items-center gap-1.5 shrink-0 sm:w-36">
                                    <span>📅</span>
                                    <span>تاریخ شروع فراخوان *:</span>
                                </label>
                                <div class="grid grid-cols-3 gap-1.5 flex-1">
                                    <!-- روز شروع -->
                                    <div>
                                        <select id="call_start_day" name="call_start_day" onchange="updateCallDatesPreview()" class="w-full px-2 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-mono font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                                <option value="<?= sprintf('%02d', $d) ?>" <?= $d === 25 ? 'selected' : '' ?>>روز <?= $d ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <!-- ماه شروع -->
                                    <div>
                                        <select id="call_start_month" name="call_start_month" onchange="updateCallDatesPreview()" class="w-full px-1.5 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <option value="01">فروردین (۰۱)</option>
                                            <option value="02">اردیبهشت (۰۲)</option>
                                            <option value="03">خرداد (۰۳)</option>
                                            <option value="04">تیر (۰۴)</option>
                                            <option value="05">مرداد (۰۵)</option>
                                            <option value="06">شهریور (۰۶)</option>
                                            <option value="07" selected>مهر (۰۷)</option>
                                            <option value="08">آبان (۰۸)</option>
                                            <option value="09">آذر (۰۹)</option>
                                            <option value="10">دی (۱۰)</option>
                                            <option value="11">بهمن (۱۱)</option>
                                            <option value="12">اسفند (۱۲)</option>
                                        </select>
                                    </div>
                                    <!-- سال شروع -->
                                    <div>
                                        <select id="call_start_year" name="call_start_year" onchange="updateCallDatesPreview()" class="w-full px-2 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-mono font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <option value="1402">۱۴۰۲</option>
                                            <option value="1403">۱۴۰۳</option>
                                            <option value="1404">۱۴۰۴</option>
                                            <option value="1405" selected>۱۴۰۵</option>
                                            <option value="1406">۱۴۰۶</option>
                                            <option value="1407">۱۴۰۷</option>
                                            <option value="1408">۱۴۰۸</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="start_date" id="call-start-date" value="1405/07/25">
                            </div>

                            <!-- ۲. انتخاب تاریخ و مهلت پایان فراخوان (زیر هم) -->
                            <div class="p-2.5 bg-slate-950/70 rounded-xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="text-indigo-300 font-bold text-xs flex items-center gap-1.5 shrink-0 sm:w-36">
                                    <span>⏰</span>
                                    <span>مهلت پایان فراخوان *:</span>
                                </label>
                                <div class="grid grid-cols-3 gap-1.5 flex-1">
                                    <!-- روز پایان -->
                                    <div>
                                        <select id="call_deadline_day" name="call_deadline_day" onchange="updateCallDatesPreview()" class="w-full px-2 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-mono font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                                <option value="<?= sprintf('%02d', $d) ?>" <?= $d === 10 ? 'selected' : '' ?>>روز <?= $d ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <!-- ماه پایان -->
                                    <div>
                                        <select id="call_deadline_month" name="call_deadline_month" onchange="updateCallDatesPreview()" class="w-full px-1.5 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <option value="01">فروردین (۰۱)</option>
                                            <option value="02">اردیبهشت (۰۲)</option>
                                            <option value="03">خرداد (۰۳)</option>
                                            <option value="04">تیر (۰۴)</option>
                                            <option value="05">مرداد (۰۵)</option>
                                            <option value="06">شهریور (۰۶)</option>
                                            <option value="07">مهر (۰۷)</option>
                                            <option value="08" selected>آبان (۰۸)</option>
                                            <option value="09">آذر (۰۹)</option>
                                            <option value="10">دی (۱۰)</option>
                                            <option value="11">بهمن (۱۱)</option>
                                            <option value="12">اسفند (۱۲)</option>
                                        </select>
                                    </div>
                                    <!-- سال پایان -->
                                    <div>
                                        <select id="call_deadline_year" name="call_deadline_year" onchange="updateCallDatesPreview()" class="w-full px-2 py-1 bg-slate-900 border border-indigo-500/50 text-white rounded-lg text-xs font-mono font-bold focus:border-indigo-400 outline-none cursor-pointer">
                                            <option value="1402">۱۴۰۲</option>
                                            <option value="1403">۱۴۰۳</option>
                                            <option value="1404">۱۴۰۴</option>
                                            <option value="1405" selected>۱۴۰۵</option>
                                            <option value="1406">۱۴۰۶</option>
                                            <option value="1407">۱۴۰۷</option>
                                            <option value="1408">۱۴۰۸</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="deadline_date" id="call-deadline-date" value="1405/08/10">
                            </div>
                        </div>

                        <!-- دکمه‌های فشرده تنظیم سریع بازه زمانی -->
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between gap-1 flex-wrap">
                            <span class="text-[10px] text-amber-300 font-bold shrink-0">⚡ تنظیم سریع:</span>
                            <div class="flex items-center gap-1 flex-wrap">
                                <button type="button" onclick="setCallSchedule(0, 7)" class="px-2 py-0.5 rounded bg-emerald-950/70 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold cursor-pointer transition-all">🟢 فوری (۷ روزه)</button>
                                <button type="button" onclick="setCallSchedule(0, 14)" class="px-2 py-0.5 rounded bg-emerald-950/70 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold cursor-pointer transition-all">🟢 ۲ هفته‌ای</button>
                                <button type="button" onclick="setCallSchedule(5, 15)" class="px-2 py-0.5 rounded bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-500/40 text-[10px] font-bold cursor-pointer transition-all">🟡 ۵ روز آینده</button>
                                <button type="button" onclick="setCallSchedule(10, 30)" class="px-2 py-0.5 rounded bg-indigo-950/70 hover:bg-indigo-900 text-indigo-300 border border-indigo-500/40 text-[10px] font-bold cursor-pointer transition-all">🟡 ۱ ماهه</button>
                            </div>
                        </div>

                        <!-- نوار فشرده پیش‌نمایش و مدت زمان -->
                        <div id="call-dates-preview" class="p-2 bg-indigo-950/40 rounded-xl border border-indigo-500/30 text-[11px] text-indigo-200 flex items-center justify-between flex-wrap gap-2 shadow-inner">
                            <div class="flex items-center gap-2">
                                <span>📅 شروع: <strong id="preview-call-start" class="font-mono text-amber-300 font-bold">۱۴۰۵/۰۷/۲۵</strong></span>
                                <span class="text-slate-600">|</span>
                                <span>⏰ پایان: <strong id="preview-call-deadline" class="font-mono text-emerald-300 font-bold">۱۴۰۵/۰۸/۱۰</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span id="preview-call-status-badge" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">🟢 فعال</span>
                                <strong id="preview-call-duration" class="font-mono text-white text-[11px]">۱۶ روز مهلت</strong>
                            </div>
                        </div>
                    </div>

                    <!-- مهارت هدف و شرح فراخوان (در دو ستون یا فشرده) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">مهارت یا تخصص هدف (اختیاری):</label>
                            <input type="text" name="target_skill" placeholder="مثال: پایتون، فتوشاپ، کمک‌های اولیه..." class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">شرح مختصر و شرایط فراخوان:</label>
                            <textarea name="call_description" rows="1" placeholder="توضیحات تمرین، شرایط ثبت‌نام..." class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Fixed Sticky Bottom) -->
                <div class="px-5 py-3 bg-slate-900/90 border-t border-slate-700/80 flex items-center justify-end gap-2 shrink-0">
                    <button type="button" onclick="closeCreateCallModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer transition-colors">
                        انصراف
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-400 hover:to-amber-400 text-slate-950 rounded-xl font-bold cursor-pointer shadow-md transition-all">
                        انتشار فراخوان 📢
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: JWT PAYLOAD INSPECTOR ===================== -->
    <div id="jwtPayloadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
        <div class="bg-[#0f172a] border border-slate-700 rounded-2xl max-w-xl w-full p-5 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 id="jwtPayloadTitle" class="text-sm font-bold text-white">بازبینی ساختار توکن JWT</h3>
                <button type="button" onclick="closeJwtPayloadModal()" class="text-slate-400 hover:text-white text-lg cursor-pointer">✕</button>
            </div>
            <pre id="jwtPayloadJsonBody" dir="ltr" class="bg-slate-950 border border-slate-800 rounded-xl p-4 text-[11px] font-mono text-emerald-300 overflow-x-auto max-h-[60vh] leading-relaxed"></pre>
            <div class="flex justify-end pt-2 border-t border-slate-800">
                <button type="button" onclick="closeJwtPayloadModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl cursor-pointer">بستن</button>
            </div>
        </div>
    </div>

    <!-- ===================== JAVASCRIPT TAB SWITCHING & MODALS ===================== -->
    <script>
        const tabs = ['overview', 'admitted', 'unhoused', 'rooms', 'dormgraph', 'facilities', 'maintenance', 'cleanliness', 'students', 'attendance', 'supervisors', 'festivals', 'skills', 'welfare', 'authjwt'];
        const tabTitles = {
            'overview': 'پیشخوان و داشبورد مدیریتی صمد',
            'admitted': 'پذیرش دانشجوی جدید در جدول منبع (admitted_students)',
            'unhoused': 'دانشجویان متقاضی خوابگاه در انتظار اسکان',
            'rooms': 'مدیریت و نظارت بر اتاق‌ها و طبقات خوابگاه',
            'dormgraph': 'نقشه تعاملی و مانیتورینگ بصری ساختار خوابگاه (Dormitory Graph)',
            'facilities': 'شناسنامه و امکانات تفکیکی اتاق‌های خوابگاه',
            'maintenance': 'کارتابل خرابی، درخواست تعمیر/تعویض و فاکتورهای مالی',
            'cleanliness': 'ارزیابی کیفیت بهداشت و رتبه‌بندی عملکرد نظافت خوابگاه',
            'students': 'فهرست دانشجویان اسکان‌یافته و مدیریت میهمانان',
            'attendance': 'بررسی سرشماری شبانه خوابگاه و صدور کارنامه تردد',
            'supervisors': 'مدیریت مدیران کل، سرپرستان خوابگاه و شیفت‌های کاری',
            'festivals': 'سامانه جشنواره‌ها، المپیادها و داوطلبان دانشجویی',
            'skills': 'بانک مهارت‌های دانشجویی و استعدادیابی (جستجوی زنده)',
            'welfare': 'مدیریت خدمات رفاهی، نوبت‌دهی سالن بدنسازی و سانس‌های ورزشی پردیس',
            'authjwt': 'مدیریت دسترسی مبتنی بر توکن JWT و ماتریس مجوزهای RBAC'
        };

        const allRoomsList = <?= json_encode($allRooms, JSON_UNESCAPED_UNICODE) ?>;
        const allHousedData = <?= json_encode($housedAllocations, JSON_UNESCAPED_UNICODE) ?>;
        const allFacilitiesData = <?= json_encode($roomFacilities, JSON_UNESCAPED_UNICODE) ?>;
        const allMaintenanceReports = <?= json_encode($maintenanceReports, JSON_UNESCAPED_UNICODE) ?>;
        const allFestivalCalls = <?= json_encode($festivalCalls, JSON_UNESCAPED_UNICODE) ?>;

        // نقشه گراف خوابگاه مدیر از طریق ماژول React تعبیه‌شده در تب #tab-dormgraph بارگذاری می‌شود.

        function filterJwtTable() {
            const q = (document.getElementById('jwtSearchInput')?.value || '').trim().toLowerCase();
            const role = document.getElementById('jwtRoleFilter')?.value || 'all';
            document.querySelectorAll('#jwtTokensTable tbody tr.jwt-row').forEach(tr => {
                const rowRole = tr.getAttribute('data-role') || '';
                const rowSearch = tr.getAttribute('data-search') || '';
                const matchRole = (role === 'all' || rowRole === role);
                const matchText = (!q || rowSearch.includes(q));
                tr.style.display = (matchRole && matchText) ? '' : 'none';
            });
        }

        function openJwtPayloadModal(data) {
            const modal = document.getElementById('jwtPayloadModal');
            const body = document.getElementById('jwtPayloadJsonBody');
            const title = document.getElementById('jwtPayloadTitle');
            if (!modal || !body) return;
            if (title) {
                title.textContent = 'بازبینی ساختار JWT: ' + (data.name || '') + ' (' + (data.role || '') + ')';
            }
            body.textContent = JSON.stringify({
                header: data.header,
                payload: data.payload,
                effectivePermissions: data.permissions
            }, null, 2);
            modal.classList.remove('hidden');
        }

        function closeJwtPayloadModal() {
            const modal = document.getElementById('jwtPayloadModal');
            if (modal) modal.classList.add('hidden');
        }

        function switchTab(tabId) {
            tabs.forEach(t => {
                const pane = document.getElementById('tab-' + t);
                const btn = document.getElementById('nav-' + t);
                if (pane) {
                    if (t === tabId) {
                        pane.classList.remove('hidden');
                    } else {
                        pane.classList.add('hidden');
                    }
                }
                if (btn) {
                    if (t === tabId) {
                        btn.className = 'tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25';
                    } else {
                        btn.className = 'tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60';
                    }
                }
            });

            const titleEl = document.getElementById('top-title');
            if (titleEl && tabTitles[tabId]) {
                titleEl.textContent = tabTitles[tabId];
            }

            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }

        // مقداردهی اولیه تب بر اساس پارامتر URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab') || '<?= htmlspecialchars($active_tab) ?>';
            if (tabs.includes(initialTab)) {
                switchTab(initialTab);
            } else {
                switchTab('overview');
            }
            if (typeof updateGuestEndDatePreview === 'function') {
                updateGuestEndDatePreview();
            }
            if (typeof updateCallDatesPreview === 'function') {
                updateCallDatesPreview();
            }
        });

        // مودال اسکان با پشتیبانی روزهای میهمانی و ۳ سلکت شروع
        function openAssignModal(studentId, fullName, studyShift) {
            document.getElementById('modal-std-id').value = studentId;
            document.getElementById('modal-std-name').textContent = fullName + ' (' + studentId + ')';
            document.getElementById('assignModal').classList.remove('hidden');
            if (typeof updateGuestEndDatePreview === 'function') {
                updateGuestEndDatePreview();
            }
        }
        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }

        function toggleGuestDaysInput(val) {
            const box = document.getElementById('guest-days-box');
            if (val === 'میهمان') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        // مودال ویرایش اسکان
        function openEditDormModal(stdId, roomId, dormStatus, guestDays) {
            document.getElementById('edit-std-id-val').value = stdId;
            document.getElementById('edit-std-id-lbl').textContent = stdId;
            document.getElementById('edit-room-select').value = roomId;
            document.getElementById('edit-dorm-status').value = dormStatus;
            document.getElementById('edit-guest-days-val').value = guestDays || 7;
            toggleEditGuestDays(dormStatus);
            document.getElementById('editDormModal').classList.remove('hidden');
        }
        function closeEditDormModal() {
            document.getElementById('editDormModal').classList.add('hidden');
        }
        function toggleEditGuestDays(val) {
            const box = document.getElementById('edit-guest-box');
            if (val === 'میهمان') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        // مودال پرینت کلی سرشماری
        function openBatchPrintModal() {
            document.getElementById('batchPrintModal').classList.remove('hidden');
        }
        function closeBatchPrintModal() {
            document.getElementById('batchPrintModal').classList.add('hidden');
        }

        // مودال پرینت فردی دانشجو
        function openIndividualPrintModal(name, id, nid, room, floor, status, notes) {
            document.getElementById('indiv-name-lbl').textContent = name;
            document.getElementById('indiv-name-val').textContent = name;
            document.getElementById('indiv-id-val').textContent = id;
            document.getElementById('indiv-nid-val').textContent = nid;
            document.getElementById('indiv-room-val').textContent = 'اتاق ' + room + ' (' + floor + ')';
            document.getElementById('indiv-status-val').textContent = status;
            document.getElementById('indiv-notes-val').textContent = notes;
            document.getElementById('individualPrintModal').classList.remove('hidden');
        }
        function closeIndividualPrintModal() {
            document.getElementById('individualPrintModal').classList.add('hidden');
        }

        // مودال کارت سرپرست
        function openSupervisorCardModal(name, nid) {
            document.getElementById('sup-card-name').textContent = name;
            document.getElementById('sup-card-nid').textContent = nid;
            document.getElementById('supervisorCardModal').classList.remove('hidden');
        }
        function closeSupervisorCardModal() {
            document.getElementById('supervisorCardModal').classList.add('hidden');
        }

        // مودال تنظیم شیفت سرپرست
        function openEditSupervisorShiftModal(id, name, days, hours) {
            document.getElementById('ess-sup-id').value = id;
            document.getElementById('ess-sup-name').textContent = name;
            document.getElementById('ess-shift-days').value = days;
            document.getElementById('ess-shift-hours').value = hours;
            document.getElementById('editSupervisorShiftModal').classList.remove('hidden');
        }
        function closeEditSupervisorShiftModal() {
            document.getElementById('editSupervisorShiftModal').classList.add('hidden');
        }

        // تغییر نوع کاربر قابل ایجاد در تب مدیریت سرپرستان و مدیران
        function switchAdminCreateRole(role) {
            const roleInput = document.getElementById('add_admin_role_input');
            const supBtn = document.getElementById('role-btn-supervisor');
            const mgrBtn = document.getElementById('role-btn-manager');
            const iconEl = document.getElementById('add-admin-icon');
            const titleEl = document.getElementById('add-admin-title');
            const subtitleEl = document.getElementById('add-admin-subtitle');
            const nameLabel = document.getElementById('add_sup_name_label');
            const nameInput = document.getElementById('add_sup_full_name');
            const daysLabel = document.getElementById('add_sup_days_label');
            const daysInput = document.getElementById('add_sup_shift_days');
            const hoursLabel = document.getElementById('add_sup_hours_label');
            const hoursInput = document.getElementById('add_sup_shift_hours');
            const passInput = document.getElementById('add_sup_password');
            const hintEl = document.getElementById('add-admin-role-hint');
            const submitBtn = document.getElementById('add-admin-submit-btn');

            if (!roleInput) return;
            roleInput.value = role;

            if (role === 'manager') {
                supBtn.className = 'px-3 py-1.5 rounded-lg font-bold text-slate-300 hover:text-white transition-all cursor-pointer';
                mgrBtn.className = 'px-3 py-1.5 rounded-lg font-bold bg-indigo-600 text-white transition-all cursor-pointer';
                iconEl.textContent = '👔';
                titleEl.textContent = 'افزودن کاربر مدیر کل (دسترسی کامل داشبورد)';
                subtitleEl.textContent = 'کاربر مدیر کل با کد ملی و کلمه عبور وارد داشبورد مدیریت کل می‌شود';
                nameLabel.textContent = 'نام، نام خانوادگی و سمت مدیر *';
                nameInput.placeholder = 'مثال: مهندس مهدی کریمی (معاون دانشجویی)';
                daysLabel.textContent = 'روزهای حضور اداری *';
                if (daysInput.value === 'شنبه تا چهارشنبه') daysInput.value = 'شنبه تا پنج‌شنبه';
                hoursLabel.textContent = 'ساعات کاری اداری *';
                if (hoursInput.value === '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)') hoursInput.value = '۰۸:۰۰ الی ۱۶:۰۰ (اداری)';
                if (passInput.value === 'Super@12345') passInput.value = 'Admin@12345';
                hintEl.className = 'text-[11px] text-indigo-300 font-semibold';
                hintEl.textContent = 'نقش انتخابی: مدیر کل (دسترسی کامل به تمامی تب‌ها، آمار و توکن‌ها)';
                submitBtn.className = 'px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer';
                submitBtn.textContent = '+ ثبت کاربر مدیر کل و صدور دسترسی';
            } else {
                mgrBtn.className = 'px-3 py-1.5 rounded-lg font-bold text-slate-300 hover:text-white transition-all cursor-pointer';
                supBtn.className = 'px-3 py-1.5 rounded-lg font-bold bg-amber-500 text-slate-950 transition-all cursor-pointer';
                iconEl.textContent = '🛡';
                titleEl.textContent = 'افزودن سرپرست جدید و تعیین شیفت کاری';
                subtitleEl.textContent = 'سرپرستان با کد ملی و رمز عبور وارد پنل سرشماری می‌شوند';
                nameLabel.textContent = 'نام و نام خانوادگی سرپرست *';
                nameInput.placeholder = 'مثال: مهندس حسام داوودی';
                daysLabel.textContent = 'روزهای شیفت کاری *';
                if (daysInput.value === 'شنبه تا پنج‌شنبه') daysInput.value = 'شنبه تا چهارشنبه';
                hoursLabel.textContent = 'ساعات کاری شیفت *';
                if (hoursInput.value === '۰۸:۰۰ الی ۱۶:۰۰ (اداری)') hoursInput.value = '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)';
                if (passInput.value === 'Admin@12345') passInput.value = 'Super@12345';
                hintEl.className = 'text-[11px] text-amber-300/90';
                hintEl.textContent = 'نقش انتخابی: سرپرست شیفت (دسترسی به پنل سرپرست خوابگاه)';
                submitBtn.className = 'px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer';
                submitBtn.textContent = '+ ثبت سرپرست جدید و صدور دسترسی';
            }
        }

        // مودال پرونده کامل اتاق و ساکنین
        function openRoomDetailsModal(roomNumber, floor, capacity) {
            document.getElementById('rdm-room-no').textContent = roomNumber;
            document.getElementById('rdm-floor').textContent = floor;
            document.getElementById('rdm-cap').textContent = capacity + ' نفر';

            const occupants = allHousedData.filter(h => String(h.room_number) === String(roomNumber));
            const occupiedCount = occupants.length;
            const freeCount = Math.max(0, capacity - occupiedCount);

            document.getElementById('rdm-occ-cnt').textContent = occupiedCount + ' نفر';
            document.getElementById('rdm-free-cnt').textContent = freeCount + ' تخت';

            const listEl = document.getElementById('rdm-occupants-list');
            listEl.innerHTML = '';

            if (occupants.length === 0) {
                listEl.innerHTML = `
                    <div class="p-8 text-center bg-slate-900/50 rounded-xl border border-slate-800 text-slate-400 text-xs">
                        ⚠️ در حال حاضر هیچ دانشجویی در این اتاق اسکان نیافته است. تمامی ${capacity} تخت این اتاق خالی می‌باشد.
                    </div>
                `;
            } else {
                occupants.forEach((occ, idx) => {
                    const cardDataJson = JSON.stringify(occ).replace(/"/g, '&quot;');
                    const prov = occ.province || 'تهران';
                    const city = occ.city || 'تهران';
                    const guestInfo = occ.dorm_status === 'میهمان' ? ` (${occ.guest_days_remaining || occ.guest_days_total || '۷'} روز باقی‌مانده)` : '';

                    const row = document.createElement('div');
                    row.className = 'p-4 bg-slate-900/90 rounded-xl border border-slate-700/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4';
                    row.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-16 rounded-xl overflow-hidden border border-emerald-500/30 bg-slate-800 shrink-0">
                                <img src="assets/images/student_sample_avatar_1790759772433.jpg" alt="${occ.full_name}" class="w-full h-full object-cover">
                            </div>
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-white text-sm">${occ.full_name}</h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold ${occ.dorm_status === 'میهمان' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'}">
                                        ${occ.dorm_status}${guestInfo}
                                    </span>
                                </div>
                                <div class="text-slate-400 flex items-center gap-2 font-mono">
                                    <span>ش.د: <strong class="text-indigo-400">${occ.student_id}</strong></span>
                                    <span>|</span>
                                    <span>کدملی: <strong class="text-cyan-300">${occ.national_id}</strong></span>
                                </div>
                                <div class="text-slate-300 text-[11px]">
                                    <span>رشته: </span>
                                    <span class="font-semibold text-white">${occ.field_of_study || 'فنی و مهندسی'}</span>
                                    <span class="text-slate-400 mx-1">(${occ.campus || 'پردیس فناوری'})</span>
                                </div>
                                <div class="text-slate-300 text-[11px] flex items-center gap-1.5 pt-0.5">
                                    <span class="text-amber-400">📍</span>
                                    <span>استان و محل سکونت: </span>
                                    <strong class="text-amber-200">${prov} (${city})</strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex sm:flex-col items-center sm:items-end gap-2 shrink-0 w-full sm:w-auto justify-between border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-800">
                            <span class="text-[11px] text-slate-400 font-mono">
                                📞 ${occ.mobile || '۰۹۱۲۰۰۰۰۰۰۰'}
                            </span>
                            <button type="button" onclick='openStudentCardModal(${cardDataJson})' class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md cursor-pointer flex items-center gap-1">
                                <span>کارت دانشجویی 🪪</span>
                            </button>
                        </div>
                    `;
                    listEl.appendChild(row);
                });
            }

            // نمایش تخت‌های خالی باقیمانده به عنوان اسلات‌های آماده
            if (freeCount > 0) {
                for (let i = 1; i <= freeCount; i++) {
                    const emptyBed = document.createElement('div');
                    emptyBed.className = 'p-3 bg-slate-900/40 border border-dashed border-slate-700/60 rounded-xl flex items-center justify-between text-xs text-slate-400';
                    emptyBed.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">🛏</span>
                            <span>تخت خالی شماره ${occupiedCount + i}</span>
                        </div>
                        <span class="px-2 py-0.5 bg-slate-800 text-slate-400 rounded text-[10px]">آماده تخصیص به متقاضی جدید</span>
                    `;
                    listEl.appendChild(emptyBed);
                }
            }

            document.getElementById('roomDetailsModal').classList.remove('hidden');
        }

        function closeRoomDetailsModal() {
            document.getElementById('roomDetailsModal').classList.add('hidden');
        }

        // مودال کارت دانشجویی هوشمند
        function openStudentCardModal(student) {
            if (!student) return;
            document.getElementById('sc-name').textContent = student.full_name || student.fullName || '';
            document.getElementById('sc-sid').textContent = student.student_id || student.studentId || '';
            document.getElementById('sc-nid').textContent = student.national_id || student.nationalId || '';
            document.getElementById('sc-field').textContent = student.field_of_study || student.fieldOfStudy || 'فنی و مهندسی';
            document.getElementById('sc-campus').textContent = student.campus || 'پردیس فناوری صمد';

            const roomNo = student.room_number || student.roomNumber;
            const floor = student.floor || '';
            document.getElementById('sc-room').textContent = roomNo ? `اتاق ${roomNo} (${floor})` : 'در صف اسکان';

            const prov = student.province || 'تهران';
            const city = student.city || 'تهران';
            document.getElementById('sc-province').textContent = `${prov} (${city})`;

            document.getElementById('sc-dormstatus').textContent = student.dorm_status || student.dormStatus || 'ثبت‌شده در سامانه';

            const sid = String(student.student_id || student.studentId || '1000');
            document.getElementById('sc-stamp').textContent = 'SAMAD-ID-' + sid.slice(-4);

            document.getElementById('studentCardModal').classList.remove('hidden');
        }

        function closeStudentCardModal() {
            document.getElementById('studentCardModal').classList.add('hidden');
        }

        function openStudentCardFromAlloc(student) {
            openStudentCardModal(student);
        }

        function filterHousedTable() {
            const query = (document.getElementById('housedSearchInput')?.value || document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.housed-row, .housed-student-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر جدول پذیرفته‌شدگان (Tab 2) با کد ملی، شماره دانشجویی، نام و رشته
        function filterAdmittedTable() {
            const query = (document.getElementById('admittedSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.admitted-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر دانشجویان در صف اسکان (Tab 3)
        function filterUnhousedTable() {
            const query = (document.getElementById('unhousedSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.unhoused-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر اتاق‌ها و طبقات (Tab 4)
        let roomCurrentFloor = 'all';
        let roomCurrentStatus = 'all';

        function setRoomFloorFilter(floor, btn) {
            roomCurrentFloor = floor;
            document.querySelectorAll('.room-floor-btn').forEach(b => {
                b.className = 'room-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'room-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterRoomsTable();
        }

        function setRoomStatusFilter(status, btn) {
            roomCurrentStatus = status;
            document.querySelectorAll('.room-status-btn').forEach(b => {
                b.className = 'room-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'room-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterRoomsTable();
        }

        function filterRoomsTable() {
            const query = (document.getElementById('roomOccupantSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.room-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const active = r.getAttribute('data-active') || '1';
                const occupants = (r.getAttribute('data-occupants') || '').toLowerCase();
                const roomNo = (r.getAttribute('data-room') || '').toLowerCase();

                const matchFloor = (roomCurrentFloor === 'all') || (floor === roomCurrentFloor);
                const matchStatus = (roomCurrentStatus === 'all') || (roomCurrentStatus === 'active' && active === '1') || (roomCurrentStatus === 'inactive' && active === '0');
                const matchQuery = !query || occupants.includes(query) || roomNo.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر دانشجویان اسکان یافته (Tab 5)
        let studentCurrentFloor = 'all';
        let studentCurrentStatus = 'all';

        function setStudentFloorFilter(floor, btn) {
            studentCurrentFloor = floor;
            document.querySelectorAll('.student-floor-btn').forEach(b => {
                b.className = 'student-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'student-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterStudentsTable();
        }

        function setStudentStatusFilter(status, btn) {
            studentCurrentStatus = status;
            document.querySelectorAll('.student-status-btn').forEach(b => {
                b.className = 'student-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'student-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterStudentsTable();
        }

        function filterStudentsTable() {
            const query = (document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.housed-student-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const dormStatus = r.getAttribute('data-dorm-status') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();

                const matchFloor = (studentCurrentFloor === 'all') || (floor === studentCurrentFloor);
                const matchStatus = (studentCurrentStatus === 'all') || (dormStatus === studentCurrentStatus);
                const matchQuery = !query || search.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر سرشماری شبانه (Tab 6)
        let attCurrentFloor = 'all';
        let attCurrentStatus = 'all';

        function setAttFloorFilter(floor, btn) {
            attCurrentFloor = floor;
            document.querySelectorAll('.att-floor-btn').forEach(b => {
                b.className = 'att-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'att-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterAttendanceTable();
        }

        function setAttStatusFilter(status, btn) {
            attCurrentStatus = status;
            document.querySelectorAll('.att-status-btn').forEach(b => {
                b.className = 'att-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'att-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterAttendanceTable();
        }

        function filterAttendanceTable() {
            const query = (document.getElementById('attendanceSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.att-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const status = r.getAttribute('data-status') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();

                const matchFloor = (attCurrentFloor === 'all') || (floor === attCurrentFloor);
                const matchStatus = (attCurrentStatus === 'all') || (status === attCurrentStatus);
                const matchQuery = !query || search.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر داوطلبان جشنواره‌ها (Tab 8)
        let festCurrentCat = 'all';

        function setFestivalCatFilter(cat, btn) {
            festCurrentCat = cat;
            document.querySelectorAll('.fest-cat-btn').forEach(b => {
                b.className = 'fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'fest-cat-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-yellow-600 text-white cursor-pointer transition-all';
            filterFestivalsList();
        }

        function filterFestivalsList() {
            const query = (document.getElementById('festivalsSearchInput')?.value || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.fest-card');

            cards.forEach(c => {
                const cat = c.getAttribute('data-category') || '';
                const search = (c.getAttribute('data-search') || '').toLowerCase();

                const matchCat = (festCurrentCat === 'all') || (cat === festCurrentCat);
                const matchQuery = !query || search.includes(query);

                if (matchCat && matchQuery) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        // فیلتر و جستجوی لحظه‌ای بانک مهارت‌ها (Tab 9)
        function filterSkillsLive() {
            const input = document.getElementById('skillLiveQueryInput');
            const clearBtn = document.getElementById('clearSkillBtn');
            const query = (input?.value || '').toLowerCase().trim();

            if (clearBtn) {
                if (query.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }

            const cards = document.querySelectorAll('.skill-card');

            cards.forEach(c => {
                const search = (c.getAttribute('data-search') || '').toLowerCase();
                const badges = c.querySelectorAll('.skill-badge');

                let hasMatchingSkill = false;
                badges.forEach(b => {
                    const badgeText = (b.getAttribute('data-name') || b.textContent || '').toLowerCase();
                    if (query && badgeText.includes(query)) {
                        b.className = 'skill-badge px-2 py-0.5 rounded-lg text-[10px] font-bold bg-yellow-500 text-slate-950 border border-yellow-400 shadow-sm animate-pulse transition-all';
                        hasMatchingSkill = true;
                    } else {
                        b.className = 'skill-badge px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-950/40 text-purple-300 border border-purple-500/30 transition-all';
                    }
                });

                if (!query || search.includes(query) || hasMatchingSkill) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        function setSkillTag(tag) {
            const input = document.getElementById('skillLiveQueryInput');
            if (input) {
                input.value = tag;
                filterSkillsLive();
            }
        }

        function clearSkillLiveQuery() {
            const input = document.getElementById('skillLiveQueryInput');
            if (input) {
                input.value = '';
                filterSkillsLive();
            }
        }

        // ===================== FACILITIES JS =====================
        function openFacilitiesModal(roomNumber, floor) {
            const modal = document.getElementById('facilitiesModal');
            if (!modal) return;
            const inputNum = document.getElementById('fac-form-room-number');
            if (inputNum) inputNum.value = roomNumber;
            const titleEl = document.getElementById('fac-modal-room-title');
            if (titleEl) titleEl.textContent = roomNumber + (floor ? ' (' + floor + ')' : '');

            const fac = (typeof allFacilitiesData !== 'undefined' && allFacilitiesData[roomNumber]) ? allFacilitiesData[roomNumber] : null;

            const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            const setChk = (id, val) => { const el = document.getElementById(id); if (el) el.checked = !!parseInt(val || 0); };

            if (fac) {
                setVal('fac-fridge-model', fac.fridge_model);
                setVal('fac-bed-type', fac.bed_type);
                setVal('fac-ac-heater', fac.ac_heater);
                setVal('fac-tv-model', fac.tv_model);
                setChk('fac-has-broom', fac.has_broom);
                setChk('fac-has-trash', fac.has_trash_bin);
                setChk('fac-has-bucket', fac.has_bucket);
                setChk('fac-has-carpet', fac.has_carpet);
                setChk('fac-has-wardrobe', fac.has_wardrobe);
                setChk('fac-has-desk', fac.has_desk);
                setChk('fac-has-shoerack', fac.has_shoe_rack);
                setChk('fac-has-mirror', fac.has_mirror);
                setVal('fac-notes', fac.notes);
            } else {
                setVal('fac-fridge-model', 'یخچال ۶ فوت امرسان استاندارد');
                setVal('fac-bed-type', 'تخت فلزی دو طبقه استاندارد');
                setVal('fac-ac-heater', 'شوفاژ مرکزی + کولر آبی پردیس');
                setVal('fac-tv-model', '');
                setChk('fac-has-broom', 1);
                setChk('fac-has-trash', 1);
                setChk('fac-has-bucket', 1);
                setChk('fac-has-carpet', 1);
                setChk('fac-has-wardrobe', 1);
                setChk('fac-has-desk', 0);
                setChk('fac-has-shoerack', 1);
                setChk('fac-has-mirror', 1);
                setVal('fac-notes', '');
            }

            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeFacilitiesModal() {
            const modal = document.getElementById('facilitiesModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        let facCurrentFloor = 'all';
        function setFacFloorFilter(floor, btn) {
            facCurrentFloor = floor;
            document.querySelectorAll('.fac-floor-btn').forEach(b => {
                b.className = 'fac-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'fac-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterFacilitiesTable();
        }

        function filterFacilitiesTable() {
            const query = (document.getElementById('facilitiesSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.fac-row');
            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                const matchFloor = (facCurrentFloor === 'all') || (floor === facCurrentFloor);
                const matchQuery = !query || search.includes(query);
                r.style.display = (matchFloor && matchQuery) ? '' : 'none';
            });
        }

        // ===================== MAINTENANCE JS =====================
        function openMaintenanceResolutionModalById(id) {
            const rep = (typeof allMaintenanceReports !== 'undefined') ? allMaintenanceReports.find(r => Number(r.id) === Number(id)) : null;
            if (rep) {
                openMaintenanceResolutionModal(rep);
            }
        }

        function openSingleInvoiceModalById(id) {
            const rep = (typeof allMaintenanceReports !== 'undefined') ? allMaintenanceReports.find(r => Number(r.id) === Number(id)) : null;
            if (rep) {
                openSingleInvoiceModal(rep);
            }
        }

        function openMaintenanceResolutionModal(rep) {
            if (!rep) return;
            const modal = document.getElementById('maintenanceResolutionModal');
            if (!modal) return;

            const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || ''; };

            setVal('mrm-report-id', rep.id);
            setText('mrm-id', '#' + rep.id);
            setText('mrm-room', 'اتاق ' + rep.room_number + (rep.floor ? ' (' + rep.floor + ')' : ''));
            setText('mrm-item', rep.equipment_name || rep.item_name || 'تجهیز خوابگاه');
            setText('mrm-desc', rep.description || '-');

            const urgEl = document.getElementById('mrm-urgency');
            if (urgEl) {
                const urg = rep.urgency || '';
                if (urg === 'urgent' || urg === 'فوری') {
                    urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30';
                    urgEl.textContent = 'فوری 🚨';
                } else if (urg === 'normal' || urg === 'عادی') {
                    urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30';
                    urgEl.textContent = 'عادی';
                } else {
                    urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400';
                    urgEl.textContent = 'کم‌اهمیت';
                }
            }

            const actVal = rep.resolution_action || rep.action_taken || (rep.maintenance_type === 'replacement' || rep.maintenance_type === 'تعویض / خرید جدید' ? 'replacement' : 'repair');
            setVal('mrm-action-taken', actVal);
            setVal('mrm-cost', rep.cost_rials || rep.resolution_cost || '');
            setVal('mrm-inv-num', rep.invoice_number || ('INV-1403-' + rep.id));
            setVal('mrm-contractor', rep.contractor_or_store_name || 'خدمات فنی پردیس پسران نیشابور');
            setVal('mrm-notes', rep.resolution_notes || '');

            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeMaintenanceResolutionModal() {
            const modal = document.getElementById('maintenanceResolutionModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        // ===================== SINGLE INVOICE JS =====================
        function openSingleInvoiceModal(rep) {
            if (!rep) return;
            const modal = document.getElementById('singleInvoiceModal');
            if (!modal) return;

            const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || ''; };

            setText('sim-inv-num', rep.invoice_number || ('INV-1403-' + rep.id));
            setText('sim-code', '#' + rep.id);
            setText('sim-date', rep.resolved_at || rep.reported_at || 'ثبت شده در سامانه');
            setText('sim-room', 'اتاق ' + rep.room_number + (rep.floor ? ' (' + rep.floor + ')' : ''));
            setText('sim-item', rep.equipment_name || rep.item_name || 'تجهیز خوابگاه');
            
            const isRep = (rep.resolution_action || rep.action_taken || '') === 'replacement' || String(rep.resolution_action || '').includes('خرید');
            setText('sim-action', isRep ? 'خرید و جایگزینی تجهیز نو' : 'تعمیر و سرویس قطعه معیوب');
            setText('sim-contractor', rep.contractor_or_store_name || 'خدمات فنی پردیس پسران نیشابور');
            const cost = parseInt(rep.cost_rials || rep.resolution_cost || 0);
            setText('sim-cost', cost.toLocaleString());
            setText('sim-notes', rep.resolution_notes || rep.description || 'اقدام فنی لازم توسط پیمانکار مجاز صورت گرفت و تحویل شد.');
            setText('sim-manager', rep.resolved_by_manager_name || rep.resolved_by || 'دکتر علیرضا حسینی (مدیر امور رفاهی و دانشجویی)');

            const editBtn = document.getElementById('sim-edit-btn');
            if (editBtn) {
                editBtn.onclick = function() {
                    closeSingleInvoiceModal();
                    openMaintenanceResolutionModal(rep);
                };
            }

            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeSingleInvoiceModal() {
            const modal = document.getElementById('singleInvoiceModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        function printSingleInvoice() {
            const el = document.getElementById('singleInvoicePrintable');
            if (!el) return;

            // چاپ مطمئن با iframe بدون بلاک شدن پاپ‌آپ
            let iframe = document.getElementById('print_invoice_frame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'print_invoice_frame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }
            
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!DOCTYPE html><html lang="fa" dir="rtl"><head><title>صورتحساب رسمی و فاکتور مالی - دانشگاه ملی مهارت واحد پسران نیشابور</title>');
            doc.write('<link rel="stylesheet" href="' + new URL('assets/fonts/fonts.css', window.location.href).href + '">');
            doc.write('<style>body{font-family:Vazirmatn,tahoma,sans-serif;margin:0;padding:25px;direction:rtl;text-align:right;color:#0f172a;background:#fff;}*{box-sizing:border-box;}table{width:100%;border-collapse:collapse;margin:15px 0;}th,td{border:1px solid #cbd5e1;padding:8px;font-size:11px;}th{background:#f1f5f9;font-weight:bold;}.no-print{display:none !important;}</style>');
            doc.write('</head><body><div style="max-width:700px;margin:0 auto;border:2px solid #0f172a;border-radius:12px;padding:20px;">');
            doc.write(el.innerHTML);
            doc.write('</div></body></html>');
            doc.close();

            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 300);
        }

        function openMaintenancePrintModal() {
            const m = document.getElementById('maintenanceInvoicePrintModal');
            if (m) {
                m.classList.remove('hidden');
                m.style.display = 'flex';
            }
        }

        function closeMaintenancePrintModal() {
            const m = document.getElementById('maintenanceInvoicePrintModal');
            if (m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        let maintCurrentStatus = 'all';
        let maintCurrentUrgency = 'all';

        function setMaintStatusFilter(status, btn) {
            maintCurrentStatus = status;
            document.querySelectorAll('.maint-status-btn').forEach(b => {
                b.className = 'maint-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'maint-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterMaintenanceTable();
        }

        function setMaintUrgencyFilter(urgency, btn) {
            maintCurrentUrgency = urgency;
            document.querySelectorAll('.maint-urgency-btn').forEach(b => {
                b.className = 'maint-urgency-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'maint-urgency-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterMaintenanceTable();
        }

        function filterMaintenanceTable() {
            const rows = document.querySelectorAll('.maint-row');
            rows.forEach(r => {
                const st = r.getAttribute('data-status') || '';
                const urg = r.getAttribute('data-urgency') || '';
                const matchSt = (maintCurrentStatus === 'all') || (st === maintCurrentStatus);
                const matchUrg = (maintCurrentUrgency === 'all') || (urg === maintCurrentUrgency);
                r.style.display = (matchSt && matchUrg) ? '' : 'none';
            });
        }

        // ===================== CLEANLINESS JS =====================
        function switchCleanView(mode) {
            const rkPane = document.getElementById('cleanViewRanking');
            const hisPane = document.getElementById('cleanViewHistory');
            const rkBtn = document.getElementById('btn-clean-ranking');
            const hisBtn = document.getElementById('btn-clean-history');

            if (mode === 'ranking') {
                rkPane.classList.remove('hidden');
                hisPane.classList.add('hidden');
                rkBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white cursor-pointer transition-all';
                hisBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            } else {
                rkPane.classList.add('hidden');
                hisPane.classList.remove('hidden');
                hisBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white cursor-pointer transition-all';
                rkBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            }
        }

        function openCleanlinessPrintModal() {
            document.getElementById('cleanlinessPrintModal').classList.remove('hidden');
        }

        function closeCleanlinessPrintModal() {
            document.getElementById('cleanlinessPrintModal').classList.add('hidden');
        }

        // ===================== FESTIVALS CALLS JS =====================
        function openCreateCallModal() {
            const m = document.getElementById('createFestivalCallModal');
            if (m) {
                m.classList.remove('hidden');
                m.style.display = 'flex';
                if (typeof updateCallDatesPreview === 'function') {
                    updateCallDatesPreview();
                }
            }
        }

        function closeCreateCallModal() {
            const m = document.getElementById('createFestivalCallModal');
            if (m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        function toggleSportsTarget(cat) {
            const box = document.getElementById('call-sports-target-box');
            if (box) {
                if (cat === 'sports') {
                    box.classList.remove('hidden');
                } else {
                    box.classList.add('hidden');
                }
            }
        }

        // الگوهای آماده شناسنامه امکانات اتاق
        function applyFacPreset(type) {
            if (type === 'standard') {
                document.getElementById('fac-fridge-model').value = 'یخچال ۶ فوت امرسان استاندارد';
                document.getElementById('fac-bed-type').value = 'تخت فلزی دو طبقه استاندارد';
                document.getElementById('fac-ac-heater').value = 'شوفاژ مرکزی + کولر آبی پردیس';
                document.getElementById('fac-tv-model').value = '';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = false;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            } else if (type === 'elite') {
                document.getElementById('fac-fridge-model').value = 'یخچال ۹ فوت دونار بزرگ';
                document.getElementById('fac-bed-type').value = 'تخت دو طبقه فلزی تقویت‌شده نخبگان';
                document.getElementById('fac-ac-heater').value = 'اسپلیت سرمایشی/گرمایشی ۱۸ هزار';
                document.getElementById('fac-tv-model').value = 'تلویزیون ۳۲ اینچ LED دیواری';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = true;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            } else if (type === 'suite') {
                document.getElementById('fac-fridge-model').value = 'یخچال دوقلو ۱۲ فوت بزرگ ویژه ۱۰ نفره';
                document.getElementById('fac-bed-type').value = 'تخت‌های سفارشی با کمد تفکیکی ۱۰ درب';
                document.getElementById('fac-ac-heater').value = 'اسپلیت ۲۴ هزار ال‌جی + شوفاژ پره‌دار';
                document.getElementById('fac-tv-model').value = 'تلویزیون ۴۳ اینچ هوشمند پردیس';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = true;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            }
        }

        // الگوی آماده اقدام تعمیراتی مدیر
        function setMaintPreset(actionNotes, contractor, cost, invNum, actionType) {
            document.getElementById('mrm-action-taken').value = actionType || 'repair';
            document.getElementById('mrm-cost').value = cost || '';
            document.getElementById('mrm-inv-num').value = invNum || '';
            document.getElementById('mrm-contractor').value = contractor || '';
            document.getElementById('mrm-notes').value = actionNotes || '';
        }

        // توابع محاسباتی تقویم جلالی (شمسی) برای انتخابگرهای ۳ گانه (روز، ماه، سال) و محاسبه سررسید
        const PERSIAN_MONTH_NAMES = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];

        function jalaliToGregorian(jy, jm, jd) {
            let gy = (jy <= 979) ? 621 : 1600;
            jy -= (jy <= 979) ? 0 : 979;
            let days = (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4)
                + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
            gy += 400 * Math.floor(days / 146097);
            days %= 146097;
            if (days > 36524) {
                gy += 100 * Math.floor(--days / 36524);
                days %= 36524;
                if (days >= 365) days++;
            }
            gy += 4 * Math.floor(days / 1461);
            days %= 1461;
            if (days > 365) {
                gy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }
            const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            let gm = 0;
            while (gm < 13 && days >= sal_a[gm]) {
                days -= sal_a[gm];
                gm++;
            }
            const gd = days + 1;
            return { gy, gm, gd };
        }

        function gregorianToJalali(gy, gm, gd) {
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            let jy = (gy <= 1600) ? 0 : 979;
            gy -= (gy <= 1600) ? 621 : 1600;
            const gy2 = (gm > 2) ? (gy + 1) : gy;
            let days = (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
                + Math.floor((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
            jy += 33 * Math.floor(days / 12053);
            days %= 12053;
            jy += 4 * Math.floor(days / 1461);
            days %= 1461;
            if (days > 365) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }
            const jm = (days < 186) ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
            const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
            return { jy, jm, jd };
        }

        // تنظیم بازه زمانی فراخوان
        function setCallDuration(days) {
            const startInp = document.getElementById('call-start-date');
            const deadInp = document.getElementById('call-deadline-date');
            if (!deadInp) return;
            const now = new Date();
            now.setDate(now.getDate() + parseInt(days));
            const j = gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
            const mm = j.jm < 10 ? '0' + j.jm : j.jm;
            const dd = j.jd < 10 ? '0' + j.jd : j.jd;
            deadInp.value = `${j.jy}/${mm}/${dd}`;
        }

        // فیلتر وضعیت فراخوان‌ها (همه، فعال، آینده، پایان‌یافته)
        function filterCallsByStatus(status, btn) {
            document.querySelectorAll('.call-filter-btn').forEach(b => {
                b.className = 'call-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer transition-all';
            });
            if (btn) {
                if (status === 'active') {
                    btn.className = 'call-filter-btn px-2.5 py-1 rounded-lg font-bold bg-emerald-600 text-white shadow-xs cursor-pointer transition-all';
                } else if (status === 'upcoming') {
                    btn.className = 'call-filter-btn px-2.5 py-1 rounded-lg font-bold bg-amber-600 text-white shadow-xs cursor-pointer transition-all';
                } else if (status === 'expired') {
                    btn.className = 'call-filter-btn px-2.5 py-1 rounded-lg font-bold bg-slate-600 text-white shadow-xs cursor-pointer transition-all';
                } else {
                    btn.className = 'call-filter-btn px-2.5 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow-xs cursor-pointer transition-all';
                }
            }

            document.querySelectorAll('.festival-call-item').forEach(card => {
                const s = card.getAttribute('data-status');
                if (status === 'all' || s === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // تنظیم بازه زمانی فراخوان با دکمه‌های آماده (به‌روزرسانی سلکت‌های سه‌گانه روز، ماه و سال)
        function setCallSchedule(startDays, endDays) {
            const now1 = new Date();
            now1.setDate(now1.getDate() + parseInt(startDays));
            const j1 = gregorianToJalali(now1.getFullYear(), now1.getMonth() + 1, now1.getDate());

            const sDay = document.getElementById('call_start_day');
            const sMonth = document.getElementById('call_start_month');
            const sYear = document.getElementById('call_start_year');
            if (sDay) sDay.value = (j1.jd < 10 ? '0' : '') + j1.jd;
            if (sMonth) sMonth.value = (j1.jm < 10 ? '0' : '') + j1.jm;
            if (sYear) sYear.value = '' + j1.jy;

            const now2 = new Date();
            now2.setDate(now2.getDate() + parseInt(endDays));
            const j2 = gregorianToJalali(now2.getFullYear(), now2.getMonth() + 1, now2.getDate());

            const dDay = document.getElementById('call_deadline_day');
            const dMonth = document.getElementById('call_deadline_month');
            const dYear = document.getElementById('call_deadline_year');
            if (dDay) dDay.value = (j2.jd < 10 ? '0' : '') + j2.jd;
            if (dMonth) dMonth.value = (j2.jm < 10 ? '0' : '') + j2.jm;
            if (dYear) dYear.value = '' + j2.jy;

            updateCallDatesPreview();
        }

        // محاسبه هوشمند و زنده تاریخ‌ها، مدت زمان و وضعیت فراخوان بر اساس ۳ سلکت شروع و ۳ سلکت پایان
        function updateCallDatesPreview() {
            const sDay = document.getElementById('call_start_day');
            const sMonth = document.getElementById('call_start_month');
            const sYear = document.getElementById('call_start_year');
            const dDay = document.getElementById('call_deadline_day');
            const dMonth = document.getElementById('call_deadline_month');
            const dYear = document.getElementById('call_deadline_year');

            const startInp = document.getElementById('call-start-date');
            const deadInp = document.getElementById('call-deadline-date');

            const sy = parseInt(sYear ? sYear.value : 1405) || 1405;
            const sm = parseInt(sMonth ? sMonth.value : 7) || 7;
            const sd = parseInt(sDay ? sDay.value : 25) || 25;

            const dy = parseInt(dYear ? dYear.value : 1405) || 1405;
            const dm = parseInt(dMonth ? dMonth.value : 8) || 8;
            const dd = parseInt(dDay ? dDay.value : 10) || 10;

            const smStr = sm < 10 ? '0' + sm : '' + sm;
            const sdStr = sd < 10 ? '0' + sd : '' + sd;
            const startJalaliStr = `${sy}/${smStr}/${sdStr}`;
            if (startInp) startInp.value = startJalaliStr;

            const dmStr = dm < 10 ? '0' + dm : '' + dm;
            const ddStr = dd < 10 ? '0' + dd : '' + dd;
            const deadJalaliStr = `${dy}/${dmStr}/${ddStr}`;
            if (deadInp) deadInp.value = deadJalaliStr;

            // تبدیل به تاریخ میلادی جهت مقایسه زمانی دقیق
            const gStart = jalaliToGregorian(sy, sm, sd);
            const dStart = new Date(gStart.gy, gStart.gm - 1, gStart.gd);

            const gDead = jalaliToGregorian(dy, dm, dd);
            const dDead = new Date(gDead.gy, gDead.gm - 1, gDead.gd);

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // محاسبه تعداد روزهای کل دوره
            const diffTime = dDead.getTime() - dStart.getTime();
            const diffDays = Math.round(diffTime / (1000 * 3600 * 24));

            const sMonthName = PERSIAN_MONTH_NAMES[sm - 1] || '';
            const dMonthName = PERSIAN_MONTH_NAMES[dm - 1] || '';

            const prevStart = document.getElementById('preview-call-start');
            if (prevStart) prevStart.textContent = `${sd} ${sMonthName} ${sy} (${startJalaliStr})`;

            const prevDead = document.getElementById('preview-call-deadline');
            if (prevDead) prevDead.textContent = `${dd} ${dMonthName} ${dy} (${deadJalaliStr})`;

            const prevBadge = document.getElementById('preview-call-status-badge');
            const prevDuration = document.getElementById('preview-call-duration');

            if (prevBadge && prevDuration) {
                if (dStart.getTime() > today.getTime()) {
                    // فراخوان آینده
                    const daysToStart = Math.round((dStart.getTime() - today.getTime()) / (1000 * 3600 * 24));
                    prevBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30';
                    prevBadge.textContent = '🟡 فراخوان آینده (پیش‌رو)';
                    prevDuration.textContent = `شروع از ${daysToStart} روز دیگر (مدت ${diffDays >= 0 ? diffDays : 0} روز)`;
                } else if (dDead.getTime() < today.getTime()) {
                    // منقضی
                    prevBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-slate-500/20 text-slate-400 border border-slate-600/30';
                    prevBadge.textContent = '⚪ منقضی‌شده';
                    prevDuration.textContent = 'مهلت به پایان رسیده است';
                } else {
                    // فعال
                    const daysLeft = Math.round((dDead.getTime() - today.getTime()) / (1000 * 3600 * 24));
                    prevBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                    prevBadge.textContent = '🟢 هم‌اکنون فعال';
                    prevDuration.textContent = `${diffDays >= 0 ? diffDays : 0} روز کل دوره (${daysLeft} روز باقی‌مانده)`;
                }
            }
        }

        // تنظیم سریع روزهای میهمانی با دکمه‌های ۳ روز، ۷ روز، ۱۴ روز، ۳۰ روز و ۶۰ روز
        function setGuestDaysValue(days) {
            const inp = document.getElementById('guest_days_inp');
            if (inp) {
                inp.value = days;
                updateGuestEndDatePreview();
            }
        }

        // محاسبه هوشمند تاریخ پایان اقامت میهمانی بر اساس ۳ سلکت (روز / ماه / سال) و تعداد روزها
        function updateGuestEndDatePreview() {
            const daySel = document.getElementById('guest_start_day');
            const monthSel = document.getElementById('guest_start_month');
            const yearSel = document.getElementById('guest_start_year');
            const daysInp = document.getElementById('guest_days_inp');
            const startInpHid = document.getElementById('guest_start_date_inp');

            const day = parseInt(daySel ? daySel.value : 25) || 25;
            const month = parseInt(monthSel ? monthSel.value : 7) || 7;
            const year = parseInt(yearSel ? yearSel.value : 1405) || 1405;
            const days = parseInt(daysInp ? daysInp.value : 7) || 7;

            const mmStr = month < 10 ? '0' + month : '' + month;
            const ddStr = day < 10 ? '0' + day : '' + day;
            const jalaliStartStr = `${year}/${mmStr}/${ddStr}`;
            if (startInpHid) startInpHid.value = jalaliStartStr;

            // تبدیل تاریخ شمسی شروع به میلادی و افزودن تعداد روزهای اقامت
            const g = jalaliToGregorian(year, month, day);
            const d = new Date(g.gy, g.gm - 1, g.gd);
            d.setDate(d.getDate() + days);

            // تبدیل نتیجه به تقویم جلالی
            const endJ = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
            const endMmStr = endJ.jm < 10 ? '0' + endJ.jm : '' + endJ.jm;
            const endDdStr = endJ.jd < 10 ? '0' + endJ.jd : '' + endJ.jd;
            const jalaliEndStr = `${endJ.jy}/${endMmStr}/${endDdStr}`;

            const monthName = PERSIAN_MONTH_NAMES[month - 1] || '';
            const endMonthName = PERSIAN_MONTH_NAMES[endJ.jm - 1] || '';

            // به‌روزرسانی برچسب‌های پیش‌نمایش
            const startLbl = document.getElementById('preview-guest-start-date');
            if (startLbl) startLbl.textContent = `${day} ${monthName} ${year} (${jalaliStartStr})`;

            const daysCountLbl = document.getElementById('preview-guest-days-count');
            if (daysCountLbl) daysCountLbl.textContent = `${days} روز اقامت`;

            const endLbl = document.getElementById('preview-guest-end-date');
            if (endLbl) endLbl.textContent = `${endDdStr} ${endMonthName} ${endJ.jy} (${jalaliEndStr})`;
        }

        // سوییچ زنده تم تاریک و روشن (با ذخیره در سشن و کوکی)
        function togglePortalTheme() {
            const isLight = document.body.classList.toggle('theme-light');
            document.body.classList.toggle('theme-dark', !isLight);
            const newTheme = isLight ? 'light' : 'dark';
            const icon = document.getElementById('themeToggleIcon');
            const text = document.getElementById('themeToggleText');
            if (icon) icon.textContent = isLight ? '🌙' : '☀️';
            if (text) text.textContent = isLight ? 'تم دارک' : 'تم لایت';
            try { localStorage.setItem('portal_theme', newTheme); } catch(e) {}
            fetch('?action_toggle_theme=1', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        }

        // الگوی آماده فراخوان جشنواره
        function setCallPreset(title, cat, team, desc) {
            document.getElementById('call-title-inp').value = title;
            const sel = document.getElementById('call-category-select');
            if (sel) {
                sel.value = cat;
                toggleSportsTarget(cat);
            }
            const teamSel = document.querySelector('select[name="target_sports_team"]');
            if (teamSel && team) teamSel.value = team;
            const descArea = document.querySelector('textarea[name="call_description"]');
            if (descArea) descArea.value = desc;
            if (typeof updateCallDatesPreview === 'function') {
                updateCallDatesPreview();
            }
        }

        function toggleSidebarMobile() {
            const sb = document.getElementById('mainSidebar');
            if (sb) {
                sb.classList.toggle('hidden');
            }
        }

        // ===================== WELFARE & GYM JS =====================
        function openTeamLockModal(slotId, dayFa, jDate, timeRange) {
            const m = document.getElementById('teamLockModal');
            if (!m) return;
            
            const slotInput = document.getElementById('tlm-slot-id') || document.getElementById('team-lock-slot-id');
            if (slotInput) slotInput.value = slotId;

            const dateEl = document.getElementById('tlm-date');
            if (dateEl) dateEl.textContent = `${dayFa} (${jDate})`;

            const timeEl = document.getElementById('tlm-time');
            if (timeEl) timeEl.textContent = timeRange;

            const infoEl = document.getElementById('team-lock-slot-info');
            if (infoEl) infoEl.textContent = `${dayFa} (${jDate}) - ساعت ${timeRange}`;

            m.classList.remove('hidden');
            m.classList.add('flex');
            m.style.display = 'flex';
        }

        function closeTeamLockModal() {
            const m = document.getElementById('teamLockModal');
            if (m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
            }
        }

        function openCancelSlotModal(slotId, dayFa, jDate, count) {
            const m = document.getElementById('cancelSlotModal');
            if (!m) return;

            const slotInput = document.getElementById('csm-slot-id') || document.getElementById('cancel-slot-id');
            if (slotInput) slotInput.value = slotId;

            const dateEl = document.getElementById('csm-date') || document.getElementById('cancel-slot-info');
            if (dateEl) dateEl.textContent = `${dayFa} (${jDate})`;

            const countEl = document.getElementById('csm-count') || document.getElementById('cancel-slot-count');
            if (countEl) countEl.textContent = count;

            m.classList.remove('hidden');
            m.classList.add('flex');
            m.style.display = 'flex';
        }

        function closeCancelSlotModal() {
            const m = document.getElementById('cancelSlotModal');
            if (m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
            }
        }

        // مودال اختصاصی مسدودسازی موقت سانس
        function openBlockSlotModal(slotId, dayFa, jDate, timeRange) {
            const m = document.getElementById('blockSlotModal');
            if (!m) return;
            const inp = document.getElementById('bsm-slot-id');
            if (inp) inp.value = slotId;
            const info = document.getElementById('bsm-info');
            if (info) info.textContent = `${dayFa} (${jDate}) - ساعت ${timeRange}`;
            m.classList.remove('hidden');
            m.classList.add('flex');
            m.style.display = 'flex';
        }

        function closeBlockSlotModal() {
            const m = document.getElementById('blockSlotModal');
            if (m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
            }
        }

        function openGymPrintModal() {
            const m = document.getElementById('gymPrintModal');
            if (m) {
                m.classList.remove('hidden');
                m.style.display = 'flex';
            }
        }

        function closeGymPrintModal() {
            const m = document.getElementById('gymPrintModal');
            if (m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        // مدیریت تب‌های روزانه تقویم خورشیدی و فیلتر خلوت سانس‌ها
        let currentGymSelectedDate = 'all';
        function selectGymDateTab(date, tabBtn) {
            currentGymSelectedDate = date;

            // به‌روزرسانی استایل دکمه‌های تب
            document.querySelectorAll('.gym-date-tab').forEach(b => {
                b.classList.remove('bg-emerald-600/20', 'border-emerald-500/50', 'text-emerald-300', 'shadow-sm', 'active-tab');
                b.classList.add('bg-slate-900/80', 'border-slate-800', 'text-slate-300');
            });
            if (tabBtn) {
                tabBtn.classList.remove('bg-slate-900/80', 'border-slate-800', 'text-slate-300');
                tabBtn.classList.add('bg-emerald-600/20', 'border-emerald-500/50', 'text-emerald-300', 'shadow-sm', 'active-tab');
            }

            // برچسب تاریخ فعلی
            const lbl = document.getElementById('gymCurrentSelectedDateLabel');
            if (lbl) {
                lbl.innerHTML = date === 'all' 
                    ? 'نمایش سانس‌های: <strong class="text-cyan-300">تمام ۷ روز آینده</strong>' 
                    : `نمایش سانس‌های: <strong class="text-emerald-400">${date}</strong>`;
            }

            // فیلتر بخش‌های نمای کارتی (Grid)
            const daySections = document.querySelectorAll('.gym-day-section');
            daySections.forEach(sec => {
                if (date === 'all' || sec.getAttribute('data-date') === date) {
                    sec.style.display = '';
                } else {
                    sec.style.display = 'none';
                }
            });

            // فیلتر ردیف‌های نمای جدول (Table)
            const rows = document.querySelectorAll('.gym-slot-row');
            rows.forEach(r => {
                if (date === 'all' || r.getAttribute('data-date') === date) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // تغییر حالت نمایش تقویم بین نمای کارتی و نمای جدول
        function setGymViewMode(mode) {
            const gridView = document.getElementById('gymGridView');
            const tableView = document.getElementById('gymTableView');
            const btnGrid = document.getElementById('gymViewBtnGrid');
            const btnTable = document.getElementById('gymViewBtnTable');

            if (mode === 'grid') {
                if (gridView) gridView.classList.remove('hidden');
                if (tableView) tableView.classList.add('hidden');
                if (btnGrid) {
                    btnGrid.className = 'px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer bg-emerald-600 text-white shadow-xs flex items-center gap-1.5';
                }
                if (btnTable) {
                    btnTable.className = 'px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer text-slate-400 hover:text-slate-200 flex items-center gap-1.5';
                }
            } else {
                if (gridView) gridView.classList.add('hidden');
                if (tableView) tableView.classList.remove('hidden');
                if (btnTable) {
                    btnTable.className = 'px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer bg-emerald-600 text-white shadow-xs flex items-center gap-1.5';
                }
                if (btnGrid) {
                    btnGrid.className = 'px-3 py-1 rounded-lg font-bold text-xs transition-all cursor-pointer text-slate-400 hover:text-slate-200 flex items-center gap-1.5';
                }
            }
        }

        function filterGymSlotsByDate(date) {
            selectGymDateTab(date, document.querySelector(`.gym-date-tab[data-date="${date}"]`));
        }

        // در صورت لود اولیه، روز اول را به عنوان پیش‌فرض انتخاب کنیم تا صفحه خلوت باشد
        document.addEventListener('DOMContentLoaded', () => {
            const firstActiveTab = document.querySelector('.gym-date-tab.active-tab');
            if (firstActiveTab) {
                const date = firstActiveTab.getAttribute('data-date');
                if (date) {
                    selectGymDateTab(date, firstActiveTab);
                }
            }
        });

        // بستن هر یک از مودال‌ها با فشردن کلید Escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modalIds = [
                    'assignModal', 'editDormModal', 'batchPrintModal', 'individualPrintModal',
                    'supervisorCardModal', 'roomDetailsModal', 'studentCardModal',
                    'editSupervisorShiftModal', 'facilitiesModal', 'maintenanceResolutionModal',
                    'singleInvoiceModal', 'maintenanceInvoicePrintModal', 'cleanlinessPrintModal', 'createFestivalCallModal',
                    'teamLockModal', 'blockSlotModal', 'cancelSlotModal', 'gymPrintModal'
                ];
                modalIds.forEach(id => {
                    const m = document.getElementById(id);
                    if (m) {
                        m.classList.add('hidden');
                        m.classList.remove('flex');
                        m.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
