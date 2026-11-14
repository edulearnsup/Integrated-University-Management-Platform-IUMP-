<?php
/**
 * میزکار جامع دانشجو - سامانه یکپارچه صمد (PHP 8 + MySQL)
 * ماژول‌های فعال:
 * ۱. رزرو وعده‌های غذایی سلف‌سرویس و مدیریت کیف پول الکترونیک
 * ۲. وضعیت اسکان، خوابگاه، روزشمار میهمانی، و هم‌اتاقی‌ها
 * ۳. پرونده مهارتی، تیم‌های ورزشی و فراخوان‌های اختصاصی صادرشده توسط مدیر
 * ۴. صدور و چاپ کارت دیجیتال شناسایی دانشجویی A4
 * ۵. صدور و چاپ گواهی رسمی سکونت در خوابگاه (فرمت اداری A4 دانشگاه ملی مهارت)
 * ۶. کارنامه سرشماری شبانه دانشجو و گزارش نظافت اتاق
 * ۷. ویرایش مشخصات تماس و تغییر کلمه عبور
 */
session_start();
require_once 'db.php';
require_once 'jwt_auth.php';

$jwtAuth = samad_require_page_jwt($pdo, ['student'], 'dorm:room:view_self');
$student_id = (string)$jwtAuth['sub'];
$msg = '';
$err = '';

// واکشی مشخصات کامل دانشجو در ابتدای اسکریپت قبل از پردازش فرم‌ها
$stmt = $pdo->prepare("SELECT * FROM `students` WHERE `student_id` = ? LIMIT 1");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// تابع تبدیل تاریخ میلادی به هجری شمسی
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
$today_parts = explode('-', date('Y-m-d'));
list($jy, $jm, $jd) = gregorian_to_jalali(intval($today_parts[0]), intval($today_parts[1]), intval($today_parts[2]));
$current_persian_date = "{$jd} " . ($months_fa[$jm - 1] ?? '') . " {$jy}";

// ویرایش مشخصات تماس و رمز عبور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    if (!samad_has_permission($jwtAuth, 'student:profile:edit')) {
        samad_log_auth_event($pdo, 'access_denied_403', $student_id, 'student', $jwtAuth['jti'], 'dashboard.php', 403, 'مجوز student:profile:edit غیرفعال است');
        $err = 'مجوز ویرایش مشخصات فردی توسط مدیریت غیرفعال شده است (403).';
    } else {
        $new_mobile = toEnglishDigits(trim($_POST['mobile'] ?? ''));
        $new_pass   = trim($_POST['new_password'] ?? '');

        if (!empty($new_mobile)) {
            try {
                if (!empty($new_pass)) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmtUp = $pdo->prepare("UPDATE `students` SET `mobile` = ?, `password` = ? WHERE `student_id` = ?");
                    $stmtUp->execute([$new_mobile, $hashed, $student_id]);
                } else {
                    $stmtUp = $pdo->prepare("UPDATE `students` SET `mobile` = ? WHERE `student_id` = ?");
                    $stmtUp->execute([$new_mobile, $student_id]);
                }
                $msg = 'اطلاعات کاربری و شماره تماس شما با موفقیت در پایگاه‌داده ذخیره شد.';
            } catch (Exception $e) {
                $err = 'خطا در ذخیره‌سازی: ' . $e->getMessage();
            }
        }
    }
}

// شارژ آنلاین کیف پول الکترونیک دانشجو
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_charge_wallet'])) {
    if (!samad_has_permission($jwtAuth, 'welfare:book')) {
        samad_log_auth_event($pdo, 'access_denied_403', $student_id, 'student', $jwtAuth['jti'], 'dashboard.php', 403, 'مجوز welfare:book برای شارژ کیف پول غیرفعال است');
        $err = 'مجوز عملیات کیف پول و خدمات رفاهی برای نقش شما غیرفعال است (403).';
    } else {
        $amount = intval($_POST['charge_amount'] ?? 0);
        if ($amount >= 10000 && $amount <= 50000000) {
            try {
                $stmtUpW = $pdo->prepare("UPDATE `students` SET `wallet_balance` = `wallet_balance` + ? WHERE `student_id` = ?");
                $stmtUpW->execute([$amount, $student_id]);
                $msg = 'کیف پول الکترونیک شما با موفقیت به مبلغ ' . number_format($amount) . ' ریال (' . number_format($amount / 10) . ' تومان) از طریق درگاه پرداخت شاپرک شارژ شد.';
            } catch (Exception $e) {
                $err = 'خطا در افزایش موجودی: ' . $e->getMessage();
            }
        } else {
            $err = 'مبلغ وارد شده برای شارژ کیف پول نامعتبر است.';
        }
    }
}

// رزرو سانس سالن بدنسازی توسط دانشجو (با مدیریت Race Condition و سهمیه)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_book_gym_slot'])) {
    if (!samad_has_permission($jwtAuth, 'welfare:book')) {
        samad_log_auth_event($pdo, 'access_denied_403', $student_id, 'student', $jwtAuth['jti'], 'dashboard.php', 403, 'مجوز welfare:book برای رزرو سانس غیرفعال است');
        $err = 'مجوز رزرو سالن ورزشی و خدمات رفاهی برای نقش دانشجو غیرفعال شده است (403).';
    } else {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        if ($slot_id > 0) {
            try {
            $pdo->beginTransaction();

            // قفل رکورد سانس ورزشی در سطح سطر جهت جلوگیری از رقابت همزمان (Race Condition Protection)
            $stmtSlot = $pdo->prepare("SELECT * FROM `welfare_service_slots` WHERE `id` = ? FOR UPDATE");
            $stmtSlot->execute([$slot_id]);
            $slot = $stmtSlot->fetch();

            if (!$slot) {
                throw new Exception('سانس ورزشی مورد نظر یافت نشد.');
            }

            if ($slot['is_blocked']) {
                throw new Exception('این سانس ورزشی مسدود و غیرفعال است (تایم ناهار/نظافت).');
            }

            // بررسی رزرو تیم‌های ورزشی
            if (!empty($slot['is_team_reserved']) && !empty($slot['reserved_team_name'])) {
                // بررسی تیم‌های ورزشی دانشجو
                $stmtChkTeam = $pdo->prepare("SELECT COUNT(*) FROM `student_sports_teams` WHERE `student_id` = ? AND `team_name` = ?");
                $stmtChkTeam->execute([$student_id, $slot['reserved_team_name']]);
                if ((int)$stmtChkTeam->fetchColumn() === 0) {
                    throw new Exception('این سانس ویژه تمرین تیم «' . $slot['reserved_team_name'] . '» دانشگاه بوده و برای سایر دانشجویان مجاز نمی‌باشد.');
                }
            }

            // بررسی ظرفیت آزاد سانس
            if ($slot['booked_count'] >= $slot['total_capacity']) {
                throw new Exception('متاسفانه ظرفیت این سانس سالن بدنسازی تکمیل شده است.');
            }

            // بررسی عدم رزرو تکراری توسط همین دانشجو
            $stmtCheckDuplicate = $pdo->prepare("SELECT id FROM `welfare_bookings` WHERE `slot_id` = ? AND `student_id` = ? AND `status` = 'confirmed'");
            $stmtCheckDuplicate->execute([$slot_id, $student_id]);
            if ($stmtCheckDuplicate->fetch()) {
                throw new Exception('شما قبلاً این سانس ورزشی را رزرو کرده‌اید و بلیت شما ثبت شده است.');
            }

            // قانون ۱: محدودیت روزانه حداکثر ۲ سانس در هر روز (روزانه نهایتاً ۲ تایم)
            $stmtDaily = $pdo->prepare("
                SELECT COUNT(*) FROM `welfare_bookings` b
                JOIN `welfare_service_slots` s ON b.slot_id = s.id
                WHERE b.student_id = ? AND s.slot_date_gregorian = ? AND b.status = 'confirmed'
            ");
            $stmtDaily->execute([$student_id, $slot['slot_date_gregorian']]);
            $dailyCount = (int)$stmtDaily->fetchColumn();
            if ($dailyCount >= 2) {
                throw new Exception('طبق مقررات، هر دانشجو در هر روز حداکثر مجاز به رزرو ۲ سانس (۴ ساعت) می‌باشد.');
            }

            // قانون ۲: محدودیت هفتگی حداکثر ۸ ساعت در هفته (هفته جاری شنبه تا جمعه)
            $slotTimestamp = strtotime($slot['slot_date_gregorian']);
            $wDay = date('w', $slotTimestamp); // 0 (Sun) to 6 (Sat)
            $diffToSat = ($wDay + 1) % 7;
            $weekStart = date('Y-m-d', strtotime("-{$diffToSat} days", $slotTimestamp));
            $weekEnd = date('Y-m-d', strtotime("+6 days", strtotime($weekStart)));

            $stmtWeekly = $pdo->prepare("
                SELECT COALESCE(SUM(s.duration_hours), 0) FROM `welfare_bookings` b
                JOIN `welfare_service_slots` s ON b.slot_id = s.id
                WHERE b.student_id = ? AND s.slot_date_gregorian BETWEEN ? AND ? AND b.status = 'confirmed'
            ");
            $stmtWeekly->execute([$student_id, $weekStart, $weekEnd]);
            $weeklyHours = (int)$stmtWeekly->fetchColumn();
            $slotHours = (int)$slot['duration_hours'];

            if ($weeklyHours + $slotHours > 8) {
                throw new Exception("طبق قوانین، سقف مجاز استفاده از سالن بدنسازی در هفته حداکثر ۸ ساعت می‌باشد (مجموع ثبت‌شده شما: {$weeklyHours} ساعت).");
            }

            // واکشی تعرفه مصوب سالن
            $servStmt = $pdo->prepare("SELECT hourly_rate_rials FROM `welfare_services` WHERE `id` = ?");
            $servStmt->execute([$slot['service_id']]);
            $hourlyRate = (int)$servStmt->fetchColumn();
            if ($hourlyRate <= 0) $hourlyRate = 50000;
            $cost = $hourlyRate * $slotHours;

            // بررسی موجودی کیف پول الکترونیک با قفل ردیف دانشجو
            $stmtStdLock = $pdo->prepare("SELECT `wallet_balance` FROM `students` WHERE `student_id` = ? FOR UPDATE");
            $stmtStdLock->execute([$student_id]);
            $currentWallet = (int)$stmtStdLock->fetchColumn();

            if ($currentWallet < $cost) {
                throw new Exception('موجودی کیف پول شما کافی نیست! هزینه این سانس ' . number_format($cost) . ' ریال می‌باشد. لطفاً ابتدا از بخش کیف پول حساب خود را شارژ نمایید.');
            }

            // کسر مبلغ از کیف پول دانشجو
            $stmtDecW = $pdo->prepare("UPDATE `students` SET `wallet_balance` = `wallet_balance` - ? WHERE `student_id` = ?");
            $stmtDecW->execute([$cost, $student_id]);

            // افزایش ظرفیت رزرو شده سانس
            $stmtIncSlot = $pdo->prepare("UPDATE `welfare_service_slots` SET `booked_count` = `booked_count` + 1 WHERE `id` = ?");
            $stmtIncSlot->execute([$slot_id]);

            // تولید توکن رمزنگاری‌شده امنیتی و کد پیگیری
            $secureToken = bin2hex(random_bytes(16));
            $trackingCode = 'GYM-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            // نام دانشجو با فال‌بک مطمئن
            $studentFullName = !empty($student['full_name']) ? $student['full_name'] : (!empty($_SESSION['student_name']) ? $_SESSION['student_name'] : 'دانشجوی پردیس');

            // درج رزرو قطعی در جدول رفاهی با فیلدهای استاندارد
            $stmtInsB = $pdo->prepare("
                INSERT INTO `welfare_bookings` 
                    (`booking_token`, `tracking_code`, `service_id`, `slot_id`, `student_id`, `student_name`, `slot_date_jalali`, `start_time`, `end_time`, `duration_hours`, `amount_paid_rials`, `status`, `refund_status`, `created_at`)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'none', NOW())
            ");
            $stmtInsB->execute([
                $secureToken,
                $trackingCode,
                $slot['service_id'],
                $slot_id,
                $student_id,
                $studentFullName,
                $slot['slot_date_jalali'],
                $slot['start_time'],
                $slot['end_time'],
                $slotHours,
                $cost
            ]);

            $pdo->commit();
            $msg = "سانس سالن بدنسازی با موفقیت رزرو شد! کد رهگیری بلیت شما: {$trackingCode} (توکن دیجیتال صادر گردید).";

            // بازخوانی موجودی جدید کیف پول و مشخصات دانشجو
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $err = $e->getMessage();
            }
        }
    }
}

// اطمینان از به‌روز بودن اطلاعات دانشجو
if (empty($student)) {
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
}

// واکشی مهارت‌ها
$skills = [];
try {
    $stmtSkills = $pdo->prepare("SELECT `skill_name` FROM `student_skills` WHERE `student_id` = ?");
    $stmtSkills->execute([$student_id]);
    $skills = $stmtSkills->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) {
    $skills = [];
}

// واکشی جشنواره‌های انتخابی
$festivals = [];
$festivalKeys = [];
try {
    $stmtFest = $pdo->prepare("SELECT `festival_key`, `festival_title` FROM `student_festivals` WHERE `student_id` = ?");
    $stmtFest->execute([$student_id]);
    $festRows = $stmtFest->fetchAll();
    $festivals = array_column($festRows, 'festival_title');
    $festivalKeys = array_column($festRows, 'festival_key');
} catch (\Throwable $e) {
    $festivals = [];
    $festivalKeys = [];
}

// واکشی تیم‌های ورزشی انتخابی دانشجو (student_sports_teams)
$sportsTeams = [];
try {
    $stmtTeams = $pdo->prepare("SELECT `team_name` FROM `student_sports_teams` WHERE `student_id` = ?");
    $stmtTeams->execute([$student_id]);
    $sportsTeams = $stmtTeams->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) {
    $sportsTeams = [];
}

// واکشی فراخوان‌ها و اطلاعیه‌های هدفمند صادرشده توسط مدیر
$allCalls = [];
try {
    $stmtCalls = $pdo->query("SELECT * FROM `festival_calls` ORDER BY `id` DESC");
    if ($stmtCalls) {
        $allCalls = $stmtCalls->fetchAll();
    }
} catch (\Throwable $e) {
    $allCalls = [];
}

// فیلتر هوشمند اطلاعیه‌ها برای این دانشجو
$myTargetedCalls = array_filter($allCalls, function($call) use ($festivalKeys, $sportsTeams) {
    $cat = $call['target_category'];
    if ($cat === 'all') return true;
    if ($cat === 'sports') {
        if (!empty($call['target_sports_team'])) {
            return in_array($call['target_sports_team'], $sportsTeams);
        }
        return in_array('sports', $festivalKeys) || !empty($sportsTeams);
    }
    return in_array($cat, $festivalKeys);
});

// واکشی مشخصات خوابگاه و وضعیت فعال/غیرفعال اتاق دانشجو
$dorm = null;
try {
    $stmtDorm = $pdo->prepare("
        SELECT a.*, r.id AS dorm_room_id, r.room_number, r.floor, r.capacity, r.is_active AS room_is_active, r.description AS room_desc
        FROM `dorm_allocations` a
        JOIN `dorm_rooms` r ON a.room_id = r.id
        WHERE a.student_id = ? AND a.is_active = 1
        LIMIT 1
    ");
    $stmtDorm->execute([$student_id]);
    $dorm = $stmtDorm->fetch();
} catch (\Throwable $e) {
    $dorm = null;
}

// در صورت داشتن خوابگاه، واکشی هم‌اتاقی‌ها (Roommates)، نظافت و سرشماری
$roommates = [];
$roomFacility = null;
$myAttendanceRecords = [];
$myCleanlinessRecords = [];

if ($dorm) {
    try {
        $stmtMates = $pdo->prepare("
            SELECT s.student_id, s.full_name, s.field_of_study, s.study_shift, a.dorm_status, a.guest_days_remaining
            FROM `dorm_allocations` a
            JOIN `students` s ON a.student_id = s.student_id
            WHERE a.room_id = ? AND a.is_active = 1 AND a.student_id != ?
        ");
        $stmtMates->execute([$dorm['dorm_room_id'], $student_id]);
        $roommates = $stmtMates->fetchAll();
    } catch (\Throwable $e) {
        $roommates = [];
    }

    // امکانات اتاق
    try {
        $stmtFac = $pdo->prepare("SELECT * FROM `room_facilities` WHERE `room_id` = ? LIMIT 1");
        $stmtFac->execute([$dorm['dorm_room_id']]);
        $roomFacility = $stmtFac->fetch();
    } catch (\Throwable $e) {
        $roomFacility = null;
    }

    // سرشماری‌های شبانه این دانشجو
    try {
        $stmtAtt = $pdo->prepare("
            SELECT * FROM `dorm_night_attendance`
            WHERE `student_id` = ?
            ORDER BY `attendance_date` DESC
            LIMIT 10
        ");
        $stmtAtt->execute([$student_id]);
        $myAttendanceRecords = $stmtAtt->fetchAll();
    } catch (Exception $e) {}

    // سابقه ارزیابی نظافت اتاق این دانشجو
    try {
        $stmtClean = $pdo->prepare("
            SELECT * FROM `room_cleanliness_records`
            WHERE `room_id` = ?
            ORDER BY `inspection_date` DESC
            LIMIT 5
        ");
        $stmtClean->execute([$dorm['dorm_room_id']]);
        $myCleanlinessRecords = $stmtClean->fetchAll();
    } catch (Exception $e) {}
}

// =============================================================
// ۸. واکشی داده‌های خدمات رفاهی و سانس‌های سالن بدنسازی دانشجو
// =============================================================
$welfareGym = null;
try {
    $stmtGym = $pdo->query("SELECT * FROM `welfare_services` WHERE `category` = 'gym' OR `id` = 1 LIMIT 1");
    if ($stmtGym) {
        $welfareGym = $stmtGym->fetch();
    }
} catch (Exception $e) {
    $welfareGym = null;
}

// در صورت نبود سرویس سالن بدنسازی، ایجاد پیش‌فرض
if (!$welfareGym) {
    try {
        $pdo->exec("
            INSERT INTO `welfare_services` (`title`, `category`, `location`, `hourly_rate_rials`, `slot_duration_hours`, `capacity_per_slot`, `rules_guidelines`, `is_active`)
            VALUES ('سالن بدنسازی و پرورش اندام پردیس', 'gym', 'زیرزمین خوابگاه مرکزی صمد - سالن شماره ۱', 50000, 2, 20, 'استفاده صرفاً با لباس و کفش ورزشی تمیز الزامی است. حداکثر زمان مجاز رزرو ۸ ساعت در هفته می‌باشد.', 1)
        ");
        $stmtGym = $pdo->query("SELECT * FROM `welfare_services` WHERE `category` = 'gym' OR `id` = 1 LIMIT 1");
        $welfareGym = $stmtGym->fetch();
    } catch (Exception $e) {}
}

// در صورت نبود سانس برای روزهای جاری و آینده، ایجاد خودکار سانس‌های ۷ روز پیش‌رو
try {
    $checkSlotsCount = (int)$pdo->query("SELECT COUNT(*) FROM `welfare_service_slots` WHERE `slot_date_gregorian` >= CURDATE()")->fetchColumn();
    if ($checkSlotsCount === 0 && $welfareGym) {
        $dayNamesFa = [
            'Saturday'  => 'شنبه',
            'Sunday'    => 'یک‌شنبه',
            'Monday'    => 'دوشنبه',
            'Tuesday'   => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday'  => 'پنج‌شنبه',
            'Friday'    => 'جمعه'
        ];

        $timeBlocks = [
            ['start' => '08:00:00', 'end' => '10:00:00', 'blocked' => 0, 'reason' => null],
            ['start' => '10:00:00', 'end' => '12:00:00', 'blocked' => 0, 'reason' => null],
            ['start' => '12:00:00', 'end' => '14:00:00', 'blocked' => 1, 'reason' => 'زمان ناهار، استراحت و نظافت سالن'],
            ['start' => '14:00:00', 'end' => '16:00:00', 'blocked' => 0, 'reason' => null],
            ['start' => '16:00:00', 'end' => '18:00:00', 'blocked' => 0, 'reason' => null],
            ['start' => '18:00:00', 'end' => '20:00:00', 'blocked' => 0, 'reason' => null],
            ['start' => '20:00:00', 'end' => '22:00:00', 'blocked' => 0, 'reason' => null]
        ];

        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime("+{$i} days");
            $gDate = date('Y-m-d', $ts);
            $dayEn = date('l', $ts);
            $dayFa = $dayNamesFa[$dayEn] ?? $dayEn;

            list($jYear, $jMonth, $jDay) = gregorian_to_jalali(intval(date('Y', $ts)), intval(date('n', $ts)), intval(date('j', $ts)));
            $jDateStr = sprintf('%04d/%02d/%02d', $jYear, $jMonth, $jDay);

            foreach ($timeBlocks as $block) {
                $stmtInsSlot = $pdo->prepare("
                    INSERT INTO `welfare_service_slots`
                        (`service_id`, `slot_date_jalali`, `slot_date_gregorian`, `day_name_fa`, `start_time`, `end_time`, `duration_hours`, `total_capacity`, `booked_count`, `is_blocked`, `block_reason`)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, 2, ?, 0, ?, ?)
                ");
                $stmtInsSlot->execute([
                    $welfareGym['id'],
                    $jDateStr,
                    $gDate,
                    $dayFa,
                    $block['start'],
                    $block['end'],
                    $welfareGym['capacity_per_slot'] ?? 20,
                    $block['blocked'],
                    $block['reason']
                ]);
            }
        }
    }
} catch (Exception $e) {}

// واکشی سانس‌های ورزشی از امروز تا پایان روزهای آینده
$gymSlots = [];
$gymSlotsByDate = [];
try {
    $stmtSlots = $pdo->prepare("
        SELECT * FROM `welfare_service_slots`
        WHERE `service_id` = ? AND `slot_date_gregorian` >= CURDATE()
        ORDER BY `slot_date_gregorian` ASC, `start_time` ASC
    ");
    $stmtSlots->execute([$welfareGym['id'] ?? 1]);
    $gymSlots = $stmtSlots->fetchAll();

    foreach ($gymSlots as $s) {
        $key = $s['slot_date_jalali'];
        if (!isset($gymSlotsByDate[$key])) {
            $gymSlotsByDate[$key] = [
                'jalali' => $s['slot_date_jalali'],
                'gregorian' => $s['slot_date_gregorian'],
                'day_fa' => $s['day_name_fa'],
                'slots' => []
            ];
        }
        $gymSlotsByDate[$key]['slots'][] = $s;
    }
} catch (Exception $e) {
    $gymSlots = [];
    $gymSlotsByDate = [];
}

// واکشی سوابق رزروهای دانشجو با فیلدهای هماهنگ
$myGymBookings = [];
$myBookedSlotIds = [];
try {
    $stmtB = $pdo->prepare("
        SELECT b.*, s.slot_date_jalali, s.slot_date_gregorian, s.day_name_fa, s.start_time, s.end_time, s.duration_hours, s.is_team_reserved, s.reserved_team_name, w.title AS gym_title, w.location AS location_name, w.hourly_rate_rials
        FROM `welfare_bookings` b
        JOIN `welfare_service_slots` s ON b.slot_id = s.id
        JOIN `welfare_services` w ON b.service_id = w.id
        WHERE b.student_id = ?
        ORDER BY s.slot_date_gregorian DESC, s.start_time DESC
    ");
    $stmtB->execute([$student_id]);
    $myGymBookings = $stmtB->fetchAll();

    foreach ($myGymBookings as $bk) {
        if ($bk['status'] === 'confirmed') {
            $myBookedSlotIds[] = (int)$bk['slot_id'];
        }
    }
} catch (Exception $e) {
    $myGymBookings = [];
    $myBookedSlotIds = [];
}

// محاسبه ساعت استفاده شده دانشجو در هفته جاری (شنبه تا جمعه)
$todayTs = strtotime('today');
$wDayNow = date('w', $todayTs);
$diffSat = ($wDayNow + 1) % 7;
$currWeekStart = date('Y-m-d', strtotime("-{$diffSat} days", $todayTs));
$currWeekEnd = date('Y-m-d', strtotime("+6 days", strtotime($currWeekStart)));

$myWeeklyUsedHours = 0;
try {
    $stmtWeekH = $pdo->prepare("
        SELECT COALESCE(SUM(s.duration_hours), 0)
        FROM `welfare_bookings` b
        JOIN `welfare_service_slots` s ON b.slot_id = s.id
        WHERE b.student_id = ? AND s.slot_date_gregorian BETWEEN ? AND ? AND b.status = 'confirmed'
    ");
    $stmtWeekH->execute([$student_id, $currWeekStart, $currWeekEnd]);
    $myWeeklyUsedHours = (int)$stmtWeekH->fetchColumn();
} catch (Exception $e) {
    $myWeeklyUsedHours = 0;
}

// محاسبه تعداد رزروهای امروز دانشجو
$myTodayBookingsCount = 0;
try {
    $stmtTodayC = $pdo->prepare("
        SELECT COUNT(*)
        FROM `welfare_bookings` b
        JOIN `welfare_service_slots` s ON b.slot_id = s.id
        WHERE b.student_id = ? AND s.slot_date_gregorian = CURDATE() AND b.status = 'confirmed'
    ");
    $stmtTodayC->execute([$student_id]);
    $myTodayBookingsCount = (int)$stmtTodayC->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>میزکار دانشجویی - سامانه صمد | دانشگاه ملی مهارت</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="assets/tailwind/tailwind.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            .no-print, .no-print * { display: none !important; }
            body { 
                background: white !important; 
                color: black !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 0.8cm;
            }

            /* حالت ۱: چاپ اختصاصی بلیت سالن بدنسازی */
            body.print-gym-ticket-active > *:not(#gymTicketModal) {
                display: none !important;
            }
            body.print-gym-ticket-active #gymTicketModal {
                display: block !important;
                position: static !important;
                inset: auto !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-shadow: none !important;
            }
            body.print-gym-ticket-active #gymTicketModal > div {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
            }
            body.print-gym-ticket-active #printableGymTicketArea {
                border: 2px solid #0f172a !important;
                box-shadow: none !important;
                padding: 0.8cm !important;
                width: 100% !important;
                page-break-inside: avoid !important;
                background: white !important;
                border-radius: 1rem !important;
                position: relative !important;
            }

            /* حالت ۲: چاپ اختصاصی گواهی سکونت خوابگاه */
            body.print-dorm-certificate-active > *:not(#dormCertificateModal) {
                display: none !important;
            }
            body.print-dorm-certificate-active #dormCertificateModal {
                display: block !important;
                position: static !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-shadow: none !important;
            }
            body.print-dorm-certificate-active #dormCertificateModal > div {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
            }
            body.print-dorm-certificate-active #printableCertificateArea {
                border: 2px solid #0f172a !important;
                box-shadow: none !important;
                padding: 1.2cm !important;
                width: 100% !important;
                page-break-inside: avoid !important;
                background: white !important;
            }

            /* حالت ۳: چاپ اختصاصی کارت هوشمند دانشجویی */
            body.print-student-card-active > *:not(#studentCardModal) {
                display: none !important;
            }
            body.print-student-card-active #studentCardModal {
                display: block !important;
                position: static !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-shadow: none !important;
            }
            body.print-student-card-active #studentCardModal > div {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-6 px-4 text-slate-800 selection:bg-indigo-600 selection:text-white">

    <!-- نوار میانبر تعاملی برای سوئیچ سریع بین سیستم‌ها (جهت سهولت کاربری و تست) -->
    <div class="max-w-6xl mx-auto mb-4 bg-slate-900 text-white px-4 py-2.5 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs shadow-md no-print">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-bold">سامانه یکپارچه صمد - دانشگاه ملی مهارت</span>
            <span class="text-slate-400">|</span>
            <span class="text-indigo-300 font-mono"><?= htmlspecialchars($current_persian_date) ?></span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[11px] text-slate-400">جابجایی بین نقش‌ها:</span>
            <a href="manager_panel.php" class="px-2.5 py-1 bg-indigo-700 hover:bg-indigo-600 text-white rounded-lg font-bold transition-all flex items-center gap-1">
                <span>🏛️</span>
                <span>پنل مدیر کل</span>
            </a>
            <a href="supervisor_panel.php" class="px-2.5 py-1 bg-amber-700 hover:bg-amber-600 text-white rounded-lg font-bold transition-all flex items-center gap-1">
                <span>🌙</span>
                <span>پنل سرپرست شب</span>
            </a>
            <a href="index.php" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all flex items-center gap-1">
                <span>🌐</span>
                <span>صفحه اصلی پرتال</span>
            </a>
        </div>
    </div>

    <div class="max-w-6xl mx-auto space-y-6">

        <?php if (!empty($msg)): ?>
            <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold rounded-2xl flex items-center justify-between no-print shadow-xs">
                <span>✓ <?= htmlspecialchars($msg) ?></span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 font-black">✕</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
            <div class="p-4 bg-rose-50 border border-rose-300 text-rose-800 text-xs font-bold rounded-2xl flex items-center justify-between no-print shadow-xs">
                <span>⚠️ <?= htmlspecialchars($err) ?></span>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-950 font-black">✕</button>
            </div>
        <?php endif; ?>
        
        <!-- هدر کاربر و مشخصات فردی -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 flex flex-col sm:flex-row items-center justify-between gap-5 shadow-sm no-print">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-200 overflow-hidden flex items-center justify-center shrink-0 shadow-inner">
                    <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/' . $student['photo_path'])): ?>
                        <img src="<?= htmlspecialchars($student['photo_path']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-2xl font-black text-indigo-600">🎓</span>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900"><?= htmlspecialchars($student['full_name']) ?></h1>
                        <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">
                            <?= htmlspecialchars($student['campus']) ?>
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1 font-mono">
                        <span>شماره دانشجویی: <strong class="text-slate-800"><?= htmlspecialchars($student['student_id']) ?></strong></span>
                        <span>کد ملی: <strong class="text-slate-800"><?= htmlspecialchars($student['national_id']) ?></strong></span>
                        <span class="font-sans">رشته: <strong class="text-slate-800"><?= htmlspecialchars($student['field_of_study']) ?></strong></span>
                        <span>تلفن: <strong class="text-slate-800"><?= htmlspecialchars($student['mobile']) ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end flex-wrap">
                <span class="px-2.5 py-1.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-[11px] font-mono tabular-nums" title="توکن احراز هویت JWT فعال (HS256)">
                    JWT: <?= htmlspecialchars(substr($jwtAuth['jti'], 0, 8)) ?> · <?= htmlspecialchars(samad_format_remaining_ttl((int)$jwtAuth['exp'])) ?>
                </span>
                <button type="button" onclick="openStudentDormGraph()" class="px-3.5 py-2 bg-gradient-to-r from-teal-700 to-indigo-700 hover:from-teal-600 hover:to-indigo-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-1.5">
                    <span>🗺️</span>
                    <span>گراف محل اقامت من</span>
                </button>
                <button type="button" onclick="openStudentCardModal()" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-1.5">
                    <span>🪪</span>
                    <span>کارت دانشجویی</span>
                </button>
                <button type="button" onclick="openProfileModal()" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 text-xs font-bold rounded-xl transition-all cursor-pointer flex items-center gap-1.5">
                    <span>✏️</span>
                    <span>ویرایش تماس</span>
                </button>
                <a href="logout.php" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition-all">
                    خروج
                </a>
            </div>
        </div>

        <!-- تب‌های تعاملی داشبورد دانشجو -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto no-print">
            <button type="button" onclick="switchStudentTab('card')" id="tab-btn-card" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-teal-700 text-white shadow-sm cursor-pointer shrink-0">
                <span>🪪</span>
                <span>کارت دانشجویی هوشمند</span>
            </button>
            <button type="button" onclick="switchStudentTab('dorm')" id="tab-btn-dorm" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-white text-slate-600 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0">
                <span>🏠</span>
                <span>پرونده اسکان، هم‌اتاقی‌ها و گراف</span>
            </button>
            <button type="button" onclick="switchStudentTab('festivals')" id="tab-btn-festivals" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-white text-slate-600 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0">
                <span>🏆</span>
                <span>مهارت‌ها، تیم‌های ورزشی و فراخوان‌ها (<?= count($myTargetedCalls) ?>)</span>
            </button>
            <button type="button" onclick="switchStudentTab('welfare')" id="tab-btn-welfare" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-white text-slate-600 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0">
                <span>🏋️</span>
                <span>خدمات رفاهی و سالن بدنسازی</span>
                <?php if (count($myGymBookings) > 0): ?>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"><?= count($myGymBookings) ?> بلیت</span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ==================== TAB 1: SMART STUDENT ID CARD (کارت دانشجویی در صفحه اصلی) ==================== -->
        <div id="tab-content-card" class="space-y-6">
            <!-- کارت هوشمند دانشجویی پردیس پسران نیشابور -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4 no-print">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <span>🪪</span>
                            <span>کارت دانشجویی هوشمند پردیس پسران نیشابور</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            کارت رسمی هویت دانشجویی، تردد به خوابگاه صمد، کارگاه‌های صنعتی و آزمایشگاه‌های تخصصی پردیس
                        </p>
                    </div>
                    <button type="button" onclick="printStudentCard()" class="px-4 py-2 bg-gradient-to-r from-teal-700 to-indigo-700 hover:from-teal-600 hover:to-indigo-600 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                        <span>🖨</span>
                        <span>چاپ رسمی کارت دانشجویی (A4)</span>
                    </button>
                </div>

                <!-- بدنه فیزیکی کارت هوشمند -->
                <div class="max-w-xl mx-auto bg-gradient-to-br from-slate-900 via-[#071d36] to-teal-950 text-white rounded-3xl p-6 sm:p-7 shadow-2xl border-2 border-teal-500/40 relative overflow-hidden">
                    <div class="flex items-center justify-between border-b border-teal-500/30 pb-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-teal-950/80 border border-teal-400/50 p-0.5 overflow-hidden shrink-0 shadow-inner">
                                <img src="assets/images/nishapur_seal_emblem.jpg" alt="نشان پردیس پسران نیشابور" class="w-full h-full object-cover rounded-lg">
                            </div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-black text-white tracking-tight">
                                    دانشگاه ملی مهارت واحد پسران نیشابور
                                </h4>
                                <span class="text-[10px] text-teal-300 font-medium block">
                                    کارت هوشمند دانشجو و مجوز تردد خوابگاه صمد
                                </span>
                            </div>
                        </div>

                        <div class="text-left font-mono text-[10px] text-teal-400">
                            <span class="block font-sans text-slate-400 text-[9px]">سال تحصیلی:</span>
                            <strong>۱۴۰۳ - ۱۴۰۴</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="flex flex-col items-center gap-2">
                            <div class="w-24 h-32 rounded-2xl overflow-hidden border-2 border-teal-400/60 shadow-lg bg-slate-800">
                                <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/' . $student['photo_path'])): ?>
                                    <img src="<?= htmlspecialchars($student['photo_path']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <img src="assets/images/student_sample_avatar_1790759772433.jpg" class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                            <span class="px-2 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/30 text-[9px] font-bold">
                                <?= htmlspecialchars($student['study_shift'] ?? 'نوبت روزانه') ?>
                            </span>
                        </div>

                        <div class="col-span-2 space-y-2 text-xs">
                            <div>
                                <span className="text-[10px] text-slate-400 block">نام و نام‌خانوادگی:</span>
                                <strong class="text-sm font-black text-white"><?= htmlspecialchars($student['full_name']) ?></strong>
                            </div>

                            <div class="grid grid-cols-2 gap-2 font-mono">
                                <div>
                                    <span class="text-[10px] font-sans text-slate-400 block">شماره دانشجویی:</span>
                                    <strong class="text-amber-300 text-xs"><?= htmlspecialchars($student['student_id']) ?></strong>
                                </div>
                                <div>
                                    <span class="text-[10px] font-sans text-slate-400 block">کد ملی:</span>
                                    <strong class="text-slate-200 text-xs"><?= htmlspecialchars($student['national_id']) ?></strong>
                                </div>
                            </div>

                            <div>
                                <span class="text-[10px] text-slate-400 block">رشته تحصیلی:</span>
                                <strong class="text-teal-200 text-xs"><?= htmlspecialchars($student['field_of_study']) ?></strong>
                            </div>

                            <div class="pt-1 border-t border-teal-500/20 flex items-center justify-between text-[10px]">
                                <div>
                                    <span class="text-slate-400 block text-[9px]">اسکان خوابگاه:</span>
                                    <?php if ($dorm): ?>
                                        <strong class="text-emerald-400 font-bold">اتاق <?= htmlspecialchars($dorm['room_number']) ?> (<?= htmlspecialchars($dorm['floor']) ?>)</strong>
                                    <?php else: ?>
                                        <strong class="text-amber-400 font-bold">متقاضی اسکان</strong>
                                    <?php endif; ?>
                                </div>
                                <div class="text-left font-mono">
                                    <span class="text-slate-400 block text-[9px]">تلفن همراه:</span>
                                    <span class="text-slate-300 dir-ltr"><?= htmlspecialchars($student['mobile'] ?? '۰۹۱۵۰۰۰۰۰۰۰') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-teal-500/30 flex items-center justify-between text-[9px] text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📱</span>
                            <div class="font-mono">
                                <span class="block font-black text-white tracking-widest text-[10px]">||||||| | ||||| ||||||</span>
                                <span>SAMAD-ID-<?= htmlspecialchars($student['student_id']) ?></span>
                            </div>
                        </div>

                        <div class="text-left">
                            <span class="text-teal-400 font-bold block">مهر معاونت دانشجویی و فرهنگی</span>
                            <span class="text-[8px] text-slate-400">پردیس پسران نیشابور ✓</span>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-1 text-slate-600 no-print">
                    <span class="font-bold text-slate-800 block">دستورالعمل کارت دانشجویی:</span>
                    <p class="text-[11px] leading-relaxed">
                        این کارت معرف هویت دانشگاهی شما در پردیس پسران دانشگاه ملی مهارت نیشابور می‌باشد و برای ورود به خوابگاه صمد، کارگاه‌های فنی و تردد در پردیس الزامی است.
                    </p>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: DORM & ROOMMATES (خوابگاه و هم‌اتاقی‌ها) ==================== -->
        <div id="tab-content-dorm" class="hidden space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">وضعیت اسکان و مشخصات اتاق در خوابگاه دانشجویی صمد</h3>
                            <?php if ($dorm): ?>
                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-300 text-[11px] font-bold rounded-lg">
                                    اسکان دایر (<?= htmlspecialchars($dorm['floor']) ?> - اتاق <?= htmlspecialchars($dorm['room_number']) ?>) ✓
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">مشاهده هم‌اتاقی‌ها، امکانات اتاق، کارنامه سرشماری شبانه و دسترسی به نقشه کامل تعاملی خوابگاه</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if ($dorm): ?>
                            <!-- دکمه باز کردن گراف در پنجره جدید -->
                            <a href="dorm_graph/index.html?mode=php&role=student" target="_blank" class="px-4 py-2 bg-gradient-to-r from-teal-700 to-indigo-700 hover:from-teal-600 hover:to-indigo-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 cursor-pointer no-print">
                                <span>🗺️</span>
                                <span>باز کردن گراف در صفحه جدید ↗</span>
                            </a>
                            <button type="button" onclick="openDormCertificateModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-xs transition-all cursor-pointer flex items-center gap-1.5 no-print">
                                <span>📜</span>
                                <span>چاپ گواهی سکونت</span>
                            </button>
                        <?php elseif ($student['request_dormitory']): ?>
                            <span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-300 text-xs font-bold rounded-xl">
                                در صف انتظار تخصیص
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl">
                                فاقد تقاضای خوابگاه
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- نمای کارتی هم‌اتاقی‌ها و پرونده اسکان -->
                <div id="dorm-subview-cards" class="space-y-6">
                    <?php if ($dorm): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <span class="text-slate-500 block text-[11px]">اتاق و طبقه استقرار:</span>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-base">اتاق <?= htmlspecialchars($dorm['room_number']) ?></span>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded <?= $dorm['room_is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= $dorm['room_is_active'] ? 'دایر و فعال ✓' : 'تعمیرات ⚠️' ?>
                                </span>
                            </div>
                            <span class="text-indigo-600 font-semibold block"><?= htmlspecialchars($dorm['floor']) ?> - ظرفیت <?= htmlspecialchars($dorm['capacity']) ?> نفره</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <span class="text-slate-500 block text-[11px]">وضعیت شهریه و اقامت:</span>
                            <span class="font-bold text-emerald-800 text-sm block"><?= htmlspecialchars($dorm['dorm_status']) ?></span>
                            <?php if ($dorm['dorm_status'] === 'میهمان'): ?>
                                <div class="p-2 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-[11px] font-bold">
                                    ⏱ روزهای باقی‌مانده میهمانی: <?= htmlspecialchars($dorm['guest_days_remaining'] ?? 7) ?> روز (از <?= htmlspecialchars($dorm['guest_days_total'] ?? 7) ?> روز)
                                </div>
                            <?php else: ?>
                                <span class="text-slate-500 text-[11px] block">اسکان رسمی تا پایان ترم تحصیلی جاری</span>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <span class="text-slate-500 block text-[11px]">سرشماری شبانه خوابگاه:</span>
                            <span class="font-bold text-slate-800 text-sm block">دارای پرونده سرپرستی فعال</span>
                            <span class="text-slate-500 text-[11px] block">کنترل تردد هر شب ساعت ۲۲:۳۰ توسط سرپرست شبانه خوابگاه</span>
                        </div>
                    </div>

                    <!-- هم‌اتاقی‌های شما در این اتاق -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span>👥</span>
                                <span>هم‌اتاقی‌های شما در اتاق <?= htmlspecialchars($dorm['room_number']) ?> (<?= count($roommates) + 1 ?> نفر ساکن):</span>
                            </h4>
                            <span class="text-[11px] text-slate-500">ظرفیت مصوب: <?= htmlspecialchars($dorm['capacity']) ?> تخت</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <!-- خود دانشجو -->
                            <div class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-indigo-950"><?= htmlspecialchars($student['full_name']) ?> (شما)</span>
                                    <span class="px-2 py-0.5 bg-indigo-200/80 text-indigo-900 rounded text-[10px] font-bold">من</span>
                                </div>
                                <span class="text-[11px] text-slate-600 block"><?= htmlspecialchars($student['field_of_study']) ?></span>
                                <span class="text-[10px] text-slate-500 font-mono block">ش.د: <?= htmlspecialchars($student['student_id']) ?></span>
                            </div>

                            <!-- بقیه هم‌اتاقی‌ها -->
                            <?php foreach ($roommates as $mate): ?>
                                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900"><?= htmlspecialchars($mate['full_name']) ?></span>
                                        <span class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px]"><?= htmlspecialchars($mate['dorm_status']) ?></span>
                                    </div>
                                    <span class="text-[11px] text-slate-600 block"><?= htmlspecialchars($mate['field_of_study']) ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono block"><?= htmlspecialchars($mate['study_shift']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- شناسنامه امکانات اتاق -->
                    <?php if ($roomFacility): ?>
                        <?php
                            $secFacilities = !empty($roomFacility['secondary_facilities']) ? json_decode($roomFacility['secondary_facilities'], true) : [];
                            $secNames = is_array($secFacilities) ? implode('، ', array_map(fn($x) => $x['name'] ?? '', $secFacilities)) : '';
                        ?>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 text-xs">
                            <span class="font-bold text-slate-900 block">شناسنامه تجهیزات ثبتی این اتاق توسط مدیر:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-[11px] text-slate-700">
                                <div>🧊 یخچال: <strong><?= htmlspecialchars($roomFacility['refrigerator'] ?? $roomFacility['fridge_model'] ?? 'یخچال اختصاصی') ?></strong></div>
                                <div>🚪 کمد: <strong><?= htmlspecialchars($roomFacility['wardrobe'] ?? (!empty($roomFacility['has_wardrobe']) ? 'دارای کمد تفکیکی ✓' : 'کمد استاندارد')) ?></strong></div>
                                <div>🧶 فرش: <strong><?= htmlspecialchars($roomFacility['carpet'] ?? (!empty($roomFacility['has_carpet']) ? 'فرش استاندارد ۶ متری ✓' : 'فرش اتاق')) ?></strong></div>
                                <div>🛋 امکانات جانبی: <strong><?= htmlspecialchars($secNames ?: ($roomFacility['status'] ?? 'تکمیل‌شده')) ?></strong></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- کارنامه سرشماری شبانه اخیر دانشجو -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span>📋</span>
                                <span>سوابق سرشماری شبانه اخیر شما:</span>
                            </h4>
                            <span class="text-[11px] text-slate-500">کنترل‌شده توسط سرپرستان شبانه</span>
                        </div>

                        <?php if (empty($myAttendanceRecords)): ?>
                            <div class="p-4 bg-slate-50 border border-dashed rounded-2xl text-center text-xs text-slate-400">
                                هنوز سابقه‌ای برای سرشماری شبانه شما در این ماه ثبت نشده است.
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-right text-xs">
                                    <thead class="bg-slate-50 text-slate-600 border-b">
                                        <tr>
                                            <th class="p-2.5">تاریخ سرشماری</th>
                                            <th class="p-2.5">وضعیت ثبت‌شده</th>
                                            <th class="p-2.5">توضیحات سرپرست</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($myAttendanceRecords as $att): ?>
                                            <tr>
                                                <td class="p-2.5 font-mono text-slate-700"><?= htmlspecialchars($att['attendance_date']) ?></td>
                                                <td class="p-2.5">
                                                    <?php if ($att['status'] === 'حاضر'): ?>
                                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold">حاضر در اتاق ✓</span>
                                                    <?php elseif ($att['status'] === 'غایب'): ?>
                                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold">غیبت غیرموجه ⚠️</span>
                                                    <?php elseif ($att['status'] === 'مرخصی'): ?>
                                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold">مرخصی رسمی</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded font-bold"><?= htmlspecialchars($att['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-2.5 text-slate-500"><?= htmlspecialchars($att['notes'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- گزارش بهداشت و نظافت اخیر اتاق -->
                    <?php if (!empty($myCleanlinessRecords)): ?>
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between border-b pb-2">
                                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>✨</span>
                                    <span>نتایج ارزیابی بهداشت و نظافت این اتاق توسط سرپرستان:</span>
                                </h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <?php foreach ($myCleanlinessRecords as $cln): ?>
                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-800">تاریخ: <?= htmlspecialchars($cln['inspection_date']) ?></span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $cln['grade'] === 'عالی' ? 'bg-emerald-100 text-emerald-800' : ($cln['grade'] === 'خوب' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>">
                                                سطح کیفیت: <?= htmlspecialchars($cln['grade']) ?>
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($cln['notes'] ?: 'نظافت اتاق مطابق با موازین بهداشتی ارزیابی شد.') ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="p-8 text-center text-xs text-slate-500 bg-slate-50 rounded-2xl border border-dashed">
                        در حال حاضر اتاقی به شما تخصیص نیافته است. پس از بررسی وضعیت توسط مدیر امور رفاهی پردیس، مشخصات خوابگاه در این بخش نمایش داده خواهد شد.
                    </div>
                <?php endif; ?>
                </div>

                <!-- بخش گراف تعاملی خوابگاه (ماژول React تعبیه‌شده در داشبورد دانشجو) -->
                <div id="dorm-embedded-graph-section" class="pt-4 border-t border-slate-200 space-y-3 no-print">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                <span>🕸️</span>
                                <span>نقشه گراف تعاملی اسکان و هم‌اتاقی‌ها</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                نمایش سلسله‌مراتبی خوابگاه، ساختمان، طبقه، اتاق و تمام هم‌اتاقی‌های شما با قابلیت کشیدن گره‌ها، بزرگ‌نمایی و بررسی جزئیات
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" onclick="reloadStudentDormGraph()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-[11px] font-bold rounded-xl transition-all cursor-pointer flex items-center gap-1.5">
                                <span>🔄</span>
                                <span>همگام‌سازی گراف</span>
                            </button>
                            <a href="dorm_graph/index.html?mode=php&role=student" target="_blank" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold rounded-xl transition-all flex items-center gap-1.5">
                                <span>نمایش تمام‌صفحه</span>
                                <span>↗</span>
                            </a>
                        </div>
                    </div>
                    <div class="w-full h-[660px] rounded-2xl overflow-hidden border border-slate-800 bg-[#141414] shadow-lg">
                        <iframe
                            id="student-dorm-graph-iframe"
                            data-src="dorm_graph/index.html?mode=php&role=student&embed=1"
                            src="about:blank"
                            title="گراف تعاملی اسکان دانشجو"
                            class="w-full h-full border-0"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 3: FESTIVALS & SKILLS (مهارت‌ها و جشنواره‌ها) ==================== -->
        <div id="tab-content-festivals" class="hidden space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-6 shadow-sm">
                
                <!-- تیم‌های ورزشی منتخب -->
                <div>
                    <h3 class="text-xs font-bold text-emerald-800 flex items-center gap-1.5 mb-3">
                        <span>🏆</span>
                        <span>تیم‌های ورزشی منتخب دانشگاه ملی مهارت (ثبت‌نامی شما در سامانه صمد):</span>
                    </h3>
                    <?php if (!empty($sportsTeams)): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($sportsTeams as $team): ?>
                                <span class="px-3.5 py-2 bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-bold rounded-xl flex items-center gap-1.5 shadow-xs">
                                    <span>✓</span>
                                    <span><?= htmlspecialchars($team) ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-slate-500">شما در حال حاضر عضو تیم ورزشی خاصی نشده‌اید.</p>
                    <?php endif; ?>
                </div>

                <!-- مهارت‌های فردی -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-1.5 mb-3">
                        <span>💡</span>
                        <span>مهارت‌ها و تخصص‌های ثبت‌شده شما (بانک استعدادهای دانشگاه):</span>
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $sk): ?>
                                <span class="px-3 py-1.5 bg-slate-100 text-slate-800 text-xs font-semibold rounded-xl border border-slate-200"><?= htmlspecialchars($sk) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-xs text-slate-500">مهارتی ثبت نشده است.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- فراخوان‌های اختصاصی ارسال شده توسط مدیر -->
                <div class="pt-4 border-t border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                            <span>🔔</span>
                            <span>فراخوان‌ها و اطلاعیه‌های اختصاصی ارسالی برای شما (بر اساس علایق و تیم‌های ورزشی):</span>
                        </h3>
                        <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full font-mono border border-indigo-200">
                            <?= count($myTargetedCalls) ?> فراخوان فعال
                        </span>
                    </div>

                    <?php if (empty($myTargetedCalls)): ?>
                        <div class="p-6 bg-slate-50 border border-dashed rounded-2xl text-center text-xs text-slate-400">
                            در حال حاضر فراخوان جدیدی متناسب با حوزه‌های انتخابی شما صادر نشده است.
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($myTargetedCalls as $call): ?>
                                <?php $isSportsSpecific = !empty($call['target_sports_team']) && in_array($call['target_sports_team'], $sportsTeams); ?>
                                <div class="p-4 sm:p-5 rounded-2xl border text-xs space-y-2.5 <?= $isSportsSpecific ? 'bg-emerald-50/60 border-emerald-300' : 'bg-slate-50 border-slate-200' ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 pb-2">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <?php if ($isSportsSpecific): ?>
                                                <span class="px-2.5 py-0.5 bg-emerald-600 text-white rounded-lg text-[10px] font-bold">
                                                    🎯 ویژه <?= htmlspecialchars($call['target_sports_team']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <h4 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($call['title']) ?></h4>
                                        </div>
                                        <span class="text-[11px] text-slate-600 font-mono">
                                            مهلت ثبت‌نام: <strong class="text-rose-600"><?= htmlspecialchars($call['deadline_date']) ?></strong>
                                        </span>
                                    </div>
                                    <p class="text-slate-700 text-xs leading-relaxed">
                                        <?= nl2br(htmlspecialchars($call['content'])) ?>
                                    </p>
                                    <div class="pt-2 text-[10px] text-slate-500 flex justify-between border-t border-slate-100">
                                        <span>صادرکننده: <?= htmlspecialchars($call['issued_by']) ?></span>
                                        <span>پرتال رویدادهای دانشگاه ملی مهارت</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- بخش گراف تعاملی مهارت‌ها، علایق و دستاوردهای دانشجو -->
                <div id="skills-embedded-graph-section" class="pt-5 border-t border-slate-200 space-y-3 no-print">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                <span>🧠</span>
                                <span>نقشه گراف تعاملی مهارت‌ها، علایق و جشنواره‌های شما</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                مهارت‌ها، جشنواره‌ها و تیم‌های ورزشی ثبت‌نامی شما به‌صورت خودکار در باکس‌های اولیه بارگذاری شده‌اند؛ می‌توانید باکس جدید بسازید، با نقاط اطراف هر باکس اتصال برقرار کنید و چیدمان را ویرایش نمایید.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" onclick="reloadStudentSkillsGraph()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-[11px] font-bold rounded-xl transition-all cursor-pointer flex items-center gap-1.5">
                                <span>🔄</span>
                                <span>همگام‌سازی گراف مهارت‌ها</span>
                            </button>
                            <a href="skills_graph/index.html?mode=php&role=student" target="_blank" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold rounded-xl transition-all flex items-center gap-1.5">
                                <span>نمایش تمام‌صفحه</span>
                                <span>↗</span>
                            </a>
                        </div>
                    </div>
                    <div class="w-full h-[680px] rounded-2xl overflow-hidden border border-slate-800 bg-[#141414] shadow-lg">
                        <iframe
                            id="student-skills-graph-iframe"
                            data-src="skills_graph/index.html?mode=php&role=student&embed=1"
                            src="about:blank"
                            title="نقشه گراف تعاملی مهارت‌ها و علایق دانشجو"
                            class="w-full h-full border-0"
                        ></iframe>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== TAB 4: WELFARE & GYM RESERVATIONS (خدمات رفاهی و سالن بدنسازی) ==================== -->
        <div id="tab-content-welfare" class="hidden space-y-6">

            <!-- بنر سربرگ سالن بدنسازی و خدمات رفاهی -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-indigo-500/30 space-y-4 relative overflow-hidden">
                <div class="absolute -left-10 -bottom-10 opacity-10 text-9xl select-none pointer-events-none">🏋️</div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-indigo-500/20 pb-4 relative z-10">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold">
                                سامانه خدمات رفاهی دانشجویان صمد ✓
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-bold">
                                نرخ مصوب هر ساعت: <?= number_format($welfareGym['hourly_rate_rials'] ?? 50000) ?> ریال
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-black text-white flex items-center gap-2">
                            <span>🏋️</span>
                            <span><?= htmlspecialchars($welfareGym['title'] ?? 'سالن بدنسازی و پرورش اندام پردیس') ?></span>
                        </h2>
                        <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            📍 <?= htmlspecialchars($welfareGym['location_name'] ?? 'مجموعه ورزشی شهید همت پردیس') ?> | 
                            سانس‌های استاندارد ۲ ساعته از ۸ صبح تا ۱۰ شب (بجز ۱۲ الی ۱۴ نماز و ناهار) | مجهز به توکن امنیتی ضدجعل
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap shrink-0">
                        <button type="button" onclick="document.getElementById('wallet-charge-box').scrollIntoView({behavior: 'smooth'})" class="px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5">
                            <span>💳</span>
                            <span>افزایش موجودی کیف پول</span>
                        </button>
                    </div>
                </div>

                <!-- کارت‌های سه‌گانه وضعیت سهمیه، موجودی کیف پول و ضوابط -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 relative z-10">
                    <!-- کارت ۱: کیف پول الکترونیک دانشجو -->
                    <div id="wallet-charge-box" class="bg-slate-900/90 rounded-2xl p-4 border border-indigo-500/30 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-bold flex items-center gap-1.5">
                                <span>💰</span>
                                <span>موجودی کیف پول شما:</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">فعال و متصل به درگاه</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span id="walletBalanceDisplay" class="text-2xl font-black text-emerald-400 font-mono">
                                <?= number_format($student['wallet_balance'] ?? 0) ?>
                            </span>
                            <span class="text-xs text-slate-300">ریال</span>
                            <span class="text-xs text-slate-400 font-mono">(<?= number_format(($student['wallet_balance'] ?? 0) / 10) ?> تومان)</span>
                        </div>

                        <!-- دکمه‌های شارژ فوری حساب -->
                        <div class="pt-2 border-t border-slate-800 space-y-2">
                            <span class="text-[10px] text-slate-400 block font-bold">شارژ سریع آنلاین:</span>
                            <form method="POST" class="grid grid-cols-2 gap-1.5">
                                <input type="hidden" name="action_charge_wallet" value="1">
                                <button type="submit" name="charge_amount" value="50000" class="px-2 py-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                    + ۵۰,۰۰۰ ریال
                                </button>
                                <button type="submit" name="charge_amount" value="100000" class="px-2 py-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                    + ۱۰۰,۰۰۰ ریال
                                </button>
                                <button type="submit" name="charge_amount" value="200000" class="px-2 py-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                    + ۲۰۰,۰۰۰ ریال
                                </button>
                                <button type="submit" name="charge_amount" value="500000" class="px-2 py-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                    + ۵۰۰,۰۰۰ ریال
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- کارت ۲: سهمیه هفتگی و روزانه دانشجو -->
                    <div class="bg-slate-900/90 rounded-2xl p-4 border border-indigo-500/30 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-bold flex items-center gap-1.5">
                                <span>⏱️</span>
                                <span>سهمیه هفتگی و روزانه:</span>
                            </span>
                            <?php if ($myWeeklyUsedHours >= 8): ?>
                                <span class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-300 text-[10px] font-bold">تکمیل سقف هفته</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 text-[10px] font-bold"><?= 8 - $myWeeklyUsedHours ?> ساعت مجاز باقی‌مانده</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1 font-mono">
                                <span class="text-slate-300">استفاده هفته جاری:</span>
                                <strong class="text-amber-400"><?= $myWeeklyUsedHours ?> از ۸ ساعت</strong>
                            </div>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <?php $pct = min(100, round(($myWeeklyUsedHours / 8) * 100)); ?>
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-amber-500 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-800 text-[11px] space-y-1 text-slate-300">
                            <div class="flex items-center justify-between">
                                <span>رزرو امروز شما:</span>
                                <strong class="font-mono <?= $myTodayBookingsCount >= 2 ? 'text-rose-400' : 'text-emerald-400' ?>"><?= $myTodayBookingsCount ?> از ۲ سانس مجاز</strong>
                            </div>
                            <span class="text-[10px] text-slate-400 block leading-tight">
                                مجاز به رزرو بیش از ۲ ساعت (سانس‌های متوالی) در صورت داشتن سهمیه.
                            </span>
                        </div>
                    </div>

                    <!-- کارت ۳: قوانین انضباطی و امنیت رزرو -->
                    <div class="bg-slate-900/90 rounded-2xl p-4 border border-indigo-500/30 space-y-2 text-xs text-slate-300">
                        <span class="text-amber-400 font-bold flex items-center gap-1.5">
                            <span>🛡️</span>
                            <span>قوانین رزرو و استرداد وجه:</span>
                        </span>
                        <ul class="space-y-1 text-[11px] text-slate-400 leading-relaxed list-disc list-inside">
                            <li>رزرو نوبت قطعی است و <strong class="text-slate-200">امکان لغو یا عودت وجه توسط دانشجو وجود ندارد</strong>.</li>
                            <li>در صورت <strong class="text-emerald-300">لغو اضطراری سانس توسط مدیریت</strong>، هزینه بلافاصله به کیف پول عودت داده می‌شود.</li>
                            <li>ثبت نوبت با <strong class="text-indigo-300">توکن امنیتی و کد پیگیری یکتا</strong> در سرور صمد انجام می‌پذیرد.</li>
                            <li>ورود به سالن منوط به ارائه کارت دانشجویی و کفش ورزشی تمیز است.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- جدول هوشمند سانس‌های ورزشی بر اساس روزهای هفته -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <span>📅</span>
                            <span>جدول زمان‌بندی و انتخاب سانس سالن بدنسازی</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            سانس مورد نظر خود را از میان زمان‌های آزاد انتخاب و به صورت برخط رزرو نمایید.
                        </p>
                    </div>

                    <!-- تب‌های انتخاب تاریخ روز -->
                    <?php if (!empty($gymSlotsByDate)): ?>
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
                            <?php $isFirstDate = true; ?>
                            <?php foreach ($gymSlotsByDate as $jDate => $dayGroup): ?>
                                <button type="button" onclick="selectGymDateFilter('<?= $jDate ?>', this)" class="gym-date-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shrink-0 <?= $isFirstDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' ?>">
                                    <?= htmlspecialchars($dayGroup['day_fa']) ?> (<?= substr($jDate, 5) ?>)
                                </button>
                                <?php $isFirstDate = false; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($gymSlots)): ?>
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-xs text-slate-500">
                        در حال حاضر سانسی برای روزهای جاری تعریف نشده است. لطفاً ساعات آینده مراجعه فرمایید.
                    </div>
                <?php else: ?>
                    <!-- کانتینر سانس‌ها برای روزهای مختلف -->
                    <?php $dateIndex = 0; ?>
                    <?php foreach ($gymSlotsByDate as $jDate => $dayGroup): ?>
                        <div id="gym-date-group-<?= str_replace('/', '-', $jDate) ?>" class="gym-date-group space-y-4 <?= $dateIndex === 0 ? '' : 'hidden' ?>">
                            <div class="flex items-center justify-between bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">برنامه سانس‌های <?= htmlspecialchars($dayGroup['day_fa']) ?></span>
                                    <span class="text-indigo-600 font-mono font-bold"><?= htmlspecialchars($jDate) ?></span>
                                </div>
                                <span class="text-slate-500 text-[11px]"><?= count($dayGroup['slots']) ?> بازه زمانی</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                                <?php foreach ($dayGroup['slots'] as $slot): ?>
                                    <?php 
                                        $isBlocked = (bool)$slot['is_blocked'];
                                        $hasTeam = !empty($slot['is_team_reserved']) && !empty($slot['reserved_team_name']);
                                        $teamName = $slot['reserved_team_name'] ?? '';
                                        $isMyTeam = $hasTeam && in_array($teamName, $sportsTeams);
                                        $isBookedByMe = in_array((int)$slot['id'], $myBookedSlotIds);
                                        $isFull = $slot['booked_count'] >= $slot['total_capacity'];
                                        $freeCap = max(0, $slot['total_capacity'] - $slot['booked_count']);
                                        $slotCost = (int)($welfareGym['hourly_rate_rials'] ?? 50000) * (int)$slot['duration_hours'];
                                        $canAfford = ($student['wallet_balance'] ?? 0) >= $slotCost;
                                        $canBookToday = $myTodayBookingsCount < 2;
                                        $canBookWeek = ($myWeeklyUsedHours + (int)$slot['duration_hours']) <= 8;
                                    ?>
                                    <div class="rounded-2xl border p-4 flex flex-col justify-between transition-all <?= 
                                        $isBookedByMe ? 'bg-emerald-50/70 border-emerald-300 shadow-xs' :
                                        ($isBlocked ? 'bg-slate-100/80 border-slate-200 opacity-70' :
                                        ($hasTeam ? ($isMyTeam ? 'bg-amber-50/80 border-amber-300' : 'bg-slate-50 border-slate-300 opacity-75') :
                                        ($isFull ? 'bg-rose-50/50 border-rose-200' : 'bg-white border-slate-200 hover:border-indigo-400 hover:shadow-md')))
                                    ?>">
                                        <div class="space-y-2.5">
                                            <!-- ساعت سانس و برچسب وضعیت -->
                                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                                                <div class="flex items-center gap-1.5 font-mono text-sm font-black text-slate-900">
                                                    <span>⏰</span>
                                                    <span><?= substr($slot['start_time'], 0, 5) ?> الی <?= substr($slot['end_time'], 0, 5) ?></span>
                                                </div>
                                                <span class="text-[10px] text-slate-500 font-bold"><?= $slot['duration_hours'] ?> ساعت</span>
                                            </div>

                                            <!-- وضعیت سانس -->
                                            <div>
                                                <?php if ($isBookedByMe): ?>
                                                    <div class="p-2 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-bold flex items-center justify-between">
                                                        <span>✓ رزرو شده توسط شما</span>
                                                        <span class="text-[10px] font-mono">بلیت فعال</span>
                                                    </div>
                                                <?php elseif ($isBlocked): ?>
                                                    <div class="p-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5">
                                                        <span>🍽️</span>
                                                        <span>سلف، نماز و نظافت (تعطیل)</span>
                                                    </div>
                                                <?php elseif ($hasTeam): ?>
                                                    <div class="p-2 rounded-xl <?= $isMyTeam ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-700' ?> text-xs font-bold space-y-1">
                                                        <div class="flex items-center gap-1">
                                                            <span>🏆</span>
                                                            <span>اختصاصی تیم <?= htmlspecialchars($teamName) ?></span>
                                                        </div>
                                                        <?php if ($isMyTeam): ?>
                                                            <span class="text-[10px] text-emerald-700 block">شما عضو این تیم هستید (سانس اختصاصی) ✓</span>
                                                        <?php else: ?>
                                                            <span class="text-[10px] text-slate-500 block">ویژه اعضای تیم دانشگاه</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($isFull): ?>
                                                    <div class="p-2 rounded-xl bg-rose-100 text-rose-800 text-xs font-bold flex items-center justify-between">
                                                        <span>⛔ تکمیل ظرفیت</span>
                                                        <span class="text-[10px] font-mono"><?= $slot['total_capacity'] ?>/<?= $slot['total_capacity'] ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px]">
                                                            🟢 <?= $freeCap ?> جای خالی
                                                        </span>
                                                        <span class="font-mono text-slate-500 text-[11px]">
                                                            ظرفیت: <?= $slot['total_capacity'] ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- قیمت و جزئیات هزینه -->
                                            <div class="pt-1 text-xs flex items-center justify-between text-slate-600">
                                                <span>هزینه سانس:</span>
                                                <strong class="font-mono text-slate-900"><?= number_format($slotCost) ?> ریال</strong>
                                            </div>
                                        </div>

                                        <!-- دکمه اقدام رزرو یا وضعیت -->
                                        <div class="pt-3 border-t border-slate-100 mt-3">
                                            <?php if ($isBookedByMe): ?>
                                                <?php 
                                                    // پیدا کردن بلیت مربوطه
                                                    $curBooking = null;
                                                    foreach ($myGymBookings as $bkItem) {
                                                        if ((int)$bkItem['slot_id'] === (int)$slot['id']) {
                                                            $curBooking = $bkItem;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                <button type="button" onclick='openGymTicketModal(<?= json_encode($curBooking, JSON_UNESCAPED_UNICODE) ?>)' class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5">
                                                    <span>🖨️</span>
                                                    <span>مشاهده بلیت دیجیتال</span>
                                                </button>
                                            <?php elseif ($isBlocked): ?>
                                                <button type="button" disabled class="w-full py-2 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold cursor-not-allowed text-center">
                                                    غیرقابل رزرو
                                                </button>
                                            <?php elseif ($hasTeam && !$isMyTeam): ?>
                                                <button type="button" disabled class="w-full py-2 bg-slate-200 text-slate-500 rounded-xl text-xs font-bold cursor-not-allowed text-center">
                                                    مختص تیم ورزشی
                                                </button>
                                            <?php elseif ($isFull): ?>
                                                <button type="button" disabled class="w-full py-2 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold cursor-not-allowed text-center">
                                                    ظرفیت تکمیل است
                                                </button>
                                            <?php else: ?>
                                                <?php if (!$canBookToday): ?>
                                                    <button type="button" disabled class="w-full py-2 bg-amber-100 text-amber-800 rounded-xl text-[11px] font-bold cursor-not-allowed text-center" title="حداکثر مجاز ۲ سانس در هر روز">
                                                        سقف ۲ سانس امروز پر شد
                                                    </button>
                                                <?php elseif (!$canBookWeek): ?>
                                                    <button type="button" disabled class="w-full py-2 bg-amber-100 text-amber-800 rounded-xl text-[11px] font-bold cursor-not-allowed text-center" title="سقف ۸ ساعت در هفته">
                                                        سقف ۸ ساعت هفته پر شد
                                                    </button>
                                                <?php elseif (!$canAfford): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action_charge_wallet" value="1">
                                                        <input type="hidden" name="charge_amount" value="<?= $slotCost ?>">
                                                        <button type="submit" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[11px] font-bold transition-all cursor-pointer text-center">
                                                            شارژ فوری کیف پول و رزرو
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" onsubmit="return confirm('آیا از رزرو قطعی این سانس سالن بدنسازی اطمینان دارید؟ مبلغ <?= number_format($slotCost) ?> ریال از کیف پول کسر خواهد شد و امکان انصراف وجود ندارد.');">
                                                        <input type="hidden" name="action_book_gym_slot" value="1">
                                                        <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer flex items-center justify-center gap-1">
                                                            <span>رزرو فوری نوبت</span>
                                                            <span class="text-[10px] opacity-80">(<?= number_format($slotCost) ?> ریال)</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $dateIndex++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- جدول سوابق رزروهای من و کارت‌های دیجیتال ورود -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <span>🎫</span>
                            <span>بلیت‌های رزرو شده و سوابق نوبت‌های من</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            کارت ورود الکترونیک سالن با توکن امنیتی صادر شده و در درب ورودی بررسی می‌گردد.
                        </p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 font-mono">
                        مجموع: <?= count($myGymBookings) ?> نوبت
                    </span>
                </div>

                <?php if (empty($myGymBookings)): ?>
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-xs text-slate-400">
                        شما تاکنون سانسی برای سالن بدنسازی رزرو نکرده‌اید. از جدول فوق سانس دلخواه خود را رزرو فرمایید.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">کد رهگیری</th>
                                    <th class="p-3">تاریخ و روز</th>
                                    <th class="p-3">ساعت سانس</th>
                                    <th class="p-3">مبلغ پرداختی</th>
                                    <th class="p-3">توکن امنیتی</th>
                                    <th class="p-3">وضعیت نوبت</th>
                                    <th class="p-3 text-center">چاپ بلیت دیجیتال</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($myGymBookings as $bk): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="p-3 font-mono font-bold text-indigo-600">
                                            <?= htmlspecialchars($bk['tracking_code']) ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($bk['day_name_fa'] ?? $bk['day_of_week'] ?? '') ?></div>
                                            <span class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($bk['slot_date_jalali']) ?></span>
                                        </td>
                                        <td class="p-3 font-mono font-bold text-slate-800">
                                            <?= substr($bk['start_time'], 0, 5) ?> الی <?= substr($bk['end_time'], 0, 5) ?>
                                        </td>
                                        <td class="p-3 font-mono">
                                            <?= number_format($bk['amount_paid_rials'] ?? 0) ?> ریال
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px]" title="<?= htmlspecialchars($bk['booking_token']) ?>">
                                                <?= substr($bk['booking_token'], 0, 10) ?>...
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($bk['status'] === 'confirmed'): ?>
                                                <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                                                    ✓ تایید و قطعی
                                                </span>
                                            <?php elseif ($bk['status'] === 'cancelled_by_manager' || ($bk['refund_status'] ?? '') === 'refunded_to_wallet'): ?>
                                                <span class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 text-[11px] font-bold" title="<?= htmlspecialchars($bk['cancellation_reason'] ?? 'لغو توسط مدیر') ?>">
                                                    ↩️ لغو و عودت به کیف پول
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold">
                                                    <?= htmlspecialchars($bk['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <?php if ($bk['status'] === 'cancelled_by_manager' || ($bk['refund_status'] ?? '') === 'refunded_to_wallet'): ?>
                                                <button type="button" onclick='openGymTicketModal(<?= json_encode($bk, JSON_UNESCAPED_UNICODE) ?>)' class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1 mx-auto" title="این نوبت توسط مدیریت لغو شده و مبلغ به کیف پول عودت داده شده است">
                                                    <span>🚫</span>
                                                    <span>بلیت باطل‌شده (لغو)</span>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" onclick='openGymTicketModal(<?= json_encode($bk, JSON_UNESCAPED_UNICODE) ?>)' class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1 mx-auto">
                                                    <span>🖨️</span>
                                                    <span>چاپ بلیت A4</span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- مودال کارت دانشجویی هوشمند جهت چاپ A4 -->
    <div id="studentCardModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto no-print">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-sm font-bold text-slate-900">کارت شناسایی هوشمند دانشجویی صمد</h3>
                <button type="button" onclick="closeStudentCardModal()" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
            </div>

            <!-- بدنه کارت دیجیتال -->
            <div id="printableCardArea" class="bg-slate-900 text-white p-6 rounded-3xl space-y-4 shadow-xl border border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-black text-sm">صمد</div>
                        <div>
                            <h4 class="font-bold text-xs text-white">دانشگاه ملی مهارت</h4>
                            <span class="text-[10px] text-indigo-400"><?= htmlspecialchars($student['campus']) ?></span>
                        </div>
                    </div>
                    <span class="text-[10px] text-emerald-400 font-bold">دارای بارکد الکترونیک ✓</span>
                </div>

                <div class="flex gap-4 items-center">
                    <div class="w-20 h-20 rounded-2xl bg-slate-800 overflow-hidden shrink-0 border border-slate-700 flex items-center justify-center">
                        <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/' . $student['photo_path'])): ?>
                            <img src="<?= htmlspecialchars($student['photo_path']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-3xl">🎓</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs space-y-1">
                        <div class="text-sm font-black text-white"><?= htmlspecialchars($student['full_name']) ?></div>
                        <div class="text-slate-300">رشته: <?= htmlspecialchars($student['field_of_study']) ?></div>
                        <div class="font-mono text-slate-400">کد دانشجویی: <?= htmlspecialchars($student['student_id']) ?></div>
                        <div class="font-mono text-slate-400">کد ملی: <?= htmlspecialchars($student['national_id']) ?></div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                    <span>نوبت تحصیلی: <?= htmlspecialchars($student['study_shift']) ?></span>
                    <?php if ($dorm): ?>
                        <span class="text-emerald-400 font-bold">خوابگاه: اتاق <?= htmlspecialchars($dorm['room_number']) ?> (<?= htmlspecialchars($dorm['floor']) ?>)</span>
                    <?php else: ?>
                        <span>خوابگاه: فاقد اسکان</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="printStudentCard()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow cursor-pointer">
                    چاپ فیزیکی کارت 🖨
                </button>
                <button type="button" onclick="closeStudentCardModal()" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                    بستن
                </button>
            </div>
        </div>
    </div>

    <!-- مودال چاپ گواهی رسمی سکونت در خوابگاه (فرمت اداری A4 دانشگاه ملی مهارت) -->
    <div id="dormCertificateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <h3 class="text-sm font-bold text-slate-900">پیش‌نمایش گواهی رسمی سکونت در خوابگاه</h3>
                <button type="button" onclick="closeDormCertificateModal()" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
            </div>

            <!-- محتوای اداری A4 گواهی سکونت -->
            <div id="printableCertificateArea" class="border-2 border-slate-800 p-8 rounded-2xl bg-white text-slate-900 space-y-6 relative print-certificate-page">
                <!-- سربرگ رسمی -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
                    <div class="w-16 h-16 rounded-xl border border-slate-300 flex items-center justify-center font-bold text-2xl text-indigo-900 bg-slate-50">
                        🇮🇷
                    </div>
                    <div class="text-center space-y-1">
                        <h2 class="text-xs font-bold text-slate-700">جمهوری اسلامی ایران</h2>
                        <h1 class="text-sm font-extrabold text-slate-900">وزارت علوم، تحقیقات و فناوری</h1>
                        <h2 class="text-base font-black text-indigo-950">دانشگاه ملی مهارت - <?= htmlspecialchars($student['campus']) ?></h2>
                        <span class="text-[11px] text-slate-600 font-bold">معاونت دانشجویی و فرهنگی - اداره امور خوابگاه‌ها</span>
                    </div>
                    <div class="text-left font-mono text-[10px] space-y-1 text-slate-600">
                        <div>شماره: <strong>گ-خ-۴۰۵/<?= htmlspecialchars($student['student_id']) ?></strong></div>
                        <div>تاریخ: <strong><?= htmlspecialchars($current_persian_date) ?></strong></div>
                        <div>پیوست: <strong>ندارد</strong></div>
                    </div>
                </div>

                <!-- عنوان گواهی -->
                <div class="text-center py-2">
                    <span class="inline-block px-6 py-1.5 border-b-2 border-indigo-900 text-base font-black text-indigo-950 tracking-wider">
                        « گـواهـی سـکـونـت در خـوابـگـاه دانـشـجـویـی »
                    </span>
                </div>

                <!-- متن نامه رسمی -->
                <div class="text-xs leading-loose text-justify space-y-4 text-slate-800">
                    <p>
                        بدین‌وسیله گواهی می‌شود، دانشجو <strong><?= htmlspecialchars($student['full_name']) ?></strong> 
                        فرزند <?= htmlspecialchars($student['father_name'] ?? 'محترم') ?>، دارای شماره ملی <strong class="font-mono"><?= htmlspecialchars($student['national_id']) ?></strong> 
                        و شماره دانشجویی <strong class="font-mono"><?= htmlspecialchars($student['student_id']) ?></strong>، 
                        دانشجوی رشته <strong><?= htmlspecialchars($student['field_of_study']) ?></strong> (نوبت <?= htmlspecialchars($student['study_shift']) ?>) این دانشگاه، 
                        در نیم‌سال تحصیلی جاری در <strong>خوابگاه دانشجویی صمد</strong> 
                        استقرار داشته و در <strong><?= htmlspecialchars($dorm['floor'] ?? 'طبقه اول') ?>، اتاق <?= htmlspecialchars($dorm['room_number'] ?? '۱۰۱') ?></strong> 
                        سکونت رسمی دارد.
                    </p>
                    <p>
                        این گواهی بنا به تقاضای نامبرده جهت ارائه به مبادی ذی‌ربط صادر گردیده و فاقد هرگونه ارزش و اعتبار دیگری می‌باشد.
                    </p>
                </div>

                <!-- امضا و مهر رسمی -->
                <div class="pt-8 flex items-center justify-between border-t border-slate-200">
                    <div class="text-center space-y-1">
                        <span class="text-xs font-bold text-slate-800">دکتر علیرضا حسینی</span>
                        <p class="text-[11px] text-slate-500">مدیر کل امور دانشجویی و اسکان پردیس</p>
                        <div class="w-24 h-12 border border-dashed border-emerald-400 rounded-lg flex items-center justify-center text-[10px] text-emerald-700 font-bold bg-emerald-50/50 mt-1">
                            مهر رسمی دانشگاه ✓
                        </div>
                    </div>

                    <!-- بارکد اعتبارسنجی -->
                    <div class="text-center space-y-1">
                        <div class="w-16 h-16 border border-slate-300 rounded-lg flex items-center justify-center font-mono text-[9px] bg-slate-50 text-slate-600">
                            [QR Code]
                        </div>
                        <span class="text-[9px] text-slate-400 font-mono">SAMAD-VERIFY-405</span>
                    </div>

                    <div class="text-center space-y-1">
                        <span class="text-xs font-bold text-slate-800">مهندس کامران قنبری</span>
                        <p class="text-[11px] text-slate-500">مسئول ارشد سرپرستی خوابگاه صمد</p>
                        <div class="w-24 h-12 border border-dashed border-indigo-400 rounded-lg flex items-center justify-center text-[10px] text-indigo-700 font-bold bg-indigo-50/50 mt-1">
                            امضای سرپرستی ✓
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t no-print">
                <button type="button" onclick="printDormCertificate()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow cursor-pointer flex items-center gap-1.5">
                    <span>🖨</span>
                    <span>چاپ این گواهی در قطع A4</span>
                </button>
                <button type="button" onclick="closeDormCertificateModal()" class="px-4 py-2.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                    بستن
                </button>
            </div>
        </div>
    </div>

    <!-- مودال ویرایش اطلاعات تماس دانشجو -->
    <div id="profileModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto no-print">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-sm font-bold text-slate-900">ویرایش مشخصات تماس و رمز عبور</h3>
                <button type="button" onclick="closeProfileModal()" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
            </div>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action_update_profile" value="1">

                <div>
                    <label class="block text-slate-700 font-bold mb-1">شماره تلفن همراه:</label>
                    <input type="text" name="mobile" value="<?= htmlspecialchars($student['mobile']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-left focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">رمز عبور جدید (اختیاری):</label>
                    <input type="password" name="new_password" placeholder="تنها در صورت تمایل به تغییر وارد کنید" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow cursor-pointer">
                        ذخیره تغییرات ✓
                    </button>
                    <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl cursor-pointer">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MODAL: PRINTABLE DIGITAL GYM E-TICKET (A4) ===================== -->
    <div id="gymTicketModal" onclick="if(event.target === this) closeGymTicketModal()" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-6 shadow-2xl relative">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🎫</span>
                    <h3 class="text-sm font-bold text-slate-900">بلیت دیجیتال و کارت ورود رسمی به سالن بدنسازی</h3>
                </div>
                <button type="button" onclick="closeGymTicketModal()" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
            </div>

            <!-- بنر هشدار برای بلیت‌های لغوشده توسط مدیر -->
            <div id="gt-cancelled-banner" class="hidden p-3.5 bg-rose-50 border border-rose-300 text-rose-800 rounded-xl text-xs space-y-1 no-print">
                <div class="font-bold flex items-center gap-1.5 text-rose-900">
                    <span>⚠️</span>
                    <span>این نوبت توسط مدیریت دانشگاه لغو شده و مبلغ پرداختی به کیف پول شما عودت گردیده است.</span>
                </div>
                <p class="text-[11px] text-rose-700 leading-relaxed">
                    این بلیت فاقد هرگونه اعتبار قانونی بوده و امکان چاپ آن به عنوان مجوز ورود به سالن ورزشی مسدود شده است.
                </p>
            </div>

            <!-- بخش رسمی و پرینت‌پذیر بلیت در قطع A4 -->
            <div id="printableGymTicketArea" class="border-2 border-slate-800 p-8 rounded-2xl bg-white text-slate-900 space-y-6 relative overflow-hidden">
                <!-- واترمارک ابطال بلیت در صورت لغو -->
                <div id="gt-watermark" class="hidden pointer-events-none select-none absolute inset-0 flex items-center justify-center z-20 overflow-hidden">
                    <span class="text-rose-600/20 border-4 border-rose-600/30 rounded-3xl px-8 py-4 text-3xl sm:text-5xl font-black -rotate-12 uppercase tracking-widest">
                        بـاطـل شـد / لـغـو
                    </span>
                </div>

                <!-- سربرگ رسمی وزارت و دانشگاه -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
                    <div class="w-16 h-16 rounded-xl border border-slate-300 flex items-center justify-center font-bold text-2xl text-indigo-900 bg-slate-50">
                        🇮🇷
                    </div>
                    <div class="text-center space-y-1">
                        <h2 class="text-xs font-bold text-slate-700">جمهوری اسلامی ایران</h2>
                        <h1 class="text-sm font-extrabold text-slate-900">وزارت علوم، تحقیقات و فناوری</h1>
                        <h2 class="text-base font-black text-indigo-950">دانشگاه ملی مهارت - <?= htmlspecialchars($student['campus']) ?></h2>
                        <span class="text-[11px] text-slate-600 font-bold">معاونت دانشجویی و فرهنگی - اداره تربیت بدنی و خدمات رفاهی</span>
                    </div>
                    <div class="text-left font-mono text-[10px] space-y-1 text-slate-600">
                        <div>کد رهگیری: <strong id="gt-tracking" class="text-indigo-700 font-bold">GYM-1403-0000</strong></div>
                        <div>تاریخ صدور: <strong><?= htmlspecialchars($current_persian_date) ?></strong></div>
                        <div>اعتبار: <strong id="gt-status" class="text-emerald-700 font-bold">تایید شده ✓</strong></div>
                    </div>
                </div>

                <!-- عنوان بلیت -->
                <div class="text-center py-1">
                    <span class="inline-block px-6 py-1.5 border-b-2 border-indigo-900 text-base font-black text-indigo-950 tracking-wider">
                        « کـارت ورود الـکـتـرونـیـک و بـلـیـت سـالـن بـدنـسـازی »
                    </span>
                </div>

                <!-- مشخصات دارنده بلیت و جزئیات سانس -->
                <div class="grid grid-cols-3 gap-4 items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="flex flex-col items-center gap-1.5">
                        <div class="w-20 h-24 rounded-xl overflow-hidden border-2 border-slate-300 bg-slate-200">
                            <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/' . $student['photo_path'])): ?>
                                <img src="<?= htmlspecialchars($student['photo_path']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <img src="assets/images/student_sample_avatar_1790759772433.jpg" class="w-full h-full object-cover">
                            <?php endif; ?>
                        </div>
                        <span class="text-[9px] text-slate-500 font-mono"><?= htmlspecialchars($student['student_id']) ?></span>
                    </div>

                    <div class="col-span-2 space-y-2 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-[10px] text-slate-500 block">نام و نام‌خانوادگی:</span>
                                <strong class="text-slate-900 text-sm"><?= htmlspecialchars($student['full_name']) ?></strong>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block">کد ملی:</span>
                                <strong class="font-mono text-slate-800"><?= htmlspecialchars($student['national_id']) ?></strong>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-[10px] text-slate-500 block">رشته و مقطع:</span>
                                <span class="text-slate-800"><?= htmlspecialchars($student['field_of_study']) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block">محل سالن ورزشی:</span>
                                <span class="text-slate-800 font-bold"><?= htmlspecialchars($welfareGym['location_name'] ?? 'مجموعه ورزشی شهید همت') ?></span>
                            </div>
                        </div>

                        <div class="p-2.5 bg-indigo-50 border border-indigo-200 rounded-lg text-indigo-950 font-bold flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-indigo-700 block">زمان و تاریخ سانس:</span>
                                <span id="gt-datetime" class="font-mono">شنبه - ۱۴۰۳/۰۸/۰۱ ساعت ۱۶:۰۰ الی ۱۸:۰۰</span>
                            </div>
                            <div class="text-left font-mono">
                                <span class="text-[10px] text-indigo-700 block">مدت و هزینه:</span>
                                <span id="gt-cost">۲ ساعت (۱۰۰,۰۰۰ ریال)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- مشخصات امنیتی و توکن ضد جعل -->
                <div class="p-3 bg-slate-900 text-white rounded-xl space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">توکن امنیتی رمزنگاری‌شده در سرور دانشگاه (Security Token):</span>
                        <span id="gt-token-badge" class="text-[9px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">معتبر و یکتا ✓</span>
                    </div>
                    <div id="gt-token-display" class="break-all text-[11px] text-amber-300 font-bold tracking-widest bg-slate-950 p-2 rounded-lg border border-slate-800 select-all">
                        00000000000000000000000000000000
                    </div>
                </div>

                <!-- مقررات و شرایط سالن -->
                <div class="text-[11px] text-slate-600 space-y-1 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <strong class="text-slate-900 block">ضوابط و مقررات حضور در سالن بدنسازی:</strong>
                    <ul class="list-disc list-inside space-y-0.5 text-[10px]">
                        <li>حضور در سالن صرفاً با <strong class="text-slate-800">کفش ورزشی تمیز و حوله شخصی</strong> مجاز است.</li>
                        <li>این بلیت مختص دارنده کارت دانشجویی فوق بوده و <strong class="text-slate-800">غیرقابل واگذاری به غیر</strong> می‌باشد.</li>
                        <li>دانشجو موظف است ۱۰ دقیقه پیش از شروع سانس جهت احراز هویت در ورودی سالن حضور یابد.</li>
                    </ul>
                </div>

                <!-- امضا و مهر دانشگاه -->
                <div class="pt-6 flex items-center justify-between border-t border-slate-200">
                    <div class="text-center space-y-1">
                        <span class="text-xs font-bold text-slate-800">مسئول سالن ورزشی و بدنسازی</span>
                        <p class="text-[10px] text-slate-500">پردیس پسران نیشابور</p>
                        <div id="gt-stamp" class="w-28 h-10 border border-dashed border-emerald-400 rounded-lg flex items-center justify-center text-[10px] text-emerald-700 font-bold bg-emerald-50/50 mt-1">
                            تایید نوبت ورزشی ✓
                        </div>
                    </div>

                    <!-- بارکد اعتبارسنجی سامانه صمد -->
                    <div class="text-center space-y-1">
                        <div class="w-20 h-16 border border-slate-300 rounded-lg flex flex-col items-center justify-center font-mono text-[9px] bg-slate-50 text-slate-600 p-1">
                            <span class="text-xs">📱</span>
                            <span id="gt-qr-label" class="text-[8px] font-bold">QR / TOKEN</span>
                        </div>
                        <span id="gt-qr-sub" class="text-[8px] text-slate-400 font-mono">SAMAD-GYM-OK</span>
                    </div>

                    <div class="text-center space-y-1">
                        <span class="text-xs font-bold text-slate-800">دکتر علیرضا حسینی</span>
                        <p class="text-[10px] text-slate-500">مدیر کل امور دانشجویی و رفاهی</p>
                        <div class="w-24 h-10 border border-dashed border-indigo-400 rounded-lg flex items-center justify-center text-[10px] text-indigo-700 font-bold bg-indigo-50/50 mt-1">
                            مهر رسمی پردیس ✓
                        </div>
                    </div>
                </div>
            </div>

            <!-- دکمه‌های پرینت و بستن -->
            <div class="flex justify-end gap-2 pt-2 border-t no-print">
                <button type="button" id="gt-print-btn" onclick="printGymTicket()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow cursor-pointer flex items-center gap-1.5 transition-all">
                    <span>🖨️</span>
                    <span>چاپ رسمی این بلیت در قطع A4</span>
                </button>
                <button type="button" onclick="closeGymTicketModal()" class="px-4 py-2.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                    بستن
                </button>
            </div>
        </div>
    </div>

    <script>
        let walletBalance = <?= intval($student['wallet_balance'] ?? 0) ?>;

        function chargeWallet(amount) {
            walletBalance += amount;
            const el = document.getElementById('walletBalanceDisplay');
            if (el) el.textContent = walletBalance.toLocaleString('fa-IR') + ' ریال';
            alert('حساب کیف پول شما با موفقیت ' + (amount / 10).toLocaleString('fa-IR') + ' تومان شارژ شد.');
        }

        function toggleMeal(mealKey, btn) {
            const isReserved = btn.getAttribute('data-reserved') === '1';
            const price = 25000;

            if (isReserved) {
                walletBalance += price;
                btn.setAttribute('data-reserved', '0');
                btn.className = 'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer bg-white hover:bg-slate-100 text-slate-700 border border-slate-300';
                btn.textContent = btn.textContent.includes('شام') ? 'رزرو شام' : 'رزرو ناهار';
            } else {
                if (walletBalance < price) {
                    alert('موجودی کیف پول شما کافی نیست. لطفاً حساب خود را شارژ فرمایید.');
                    return;
                }
                walletBalance -= price;
                btn.setAttribute('data-reserved', '1');
                btn.className = 'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer bg-emerald-600 text-white shadow-xs';
                btn.textContent = 'رزرو شده ✓';
            }
            const el = document.getElementById('walletBalanceDisplay');
            if (el) el.textContent = walletBalance.toLocaleString('fa-IR') + ' ریال';
        }

        function ensureStudentDormGraphLoaded(forceReload = false) {
            const iframe = document.getElementById('student-dorm-graph-iframe');
            if (!iframe) return;
            const targetSrc = iframe.getAttribute('data-src') || 'dorm_graph/index.html?mode=php&role=student&embed=1';
            const currentSrc = iframe.getAttribute('src') || '';
            if (forceReload || !currentSrc || currentSrc === 'about:blank') {
                iframe.setAttribute('src', targetSrc);
            }
        }

        function reloadStudentDormGraph() {
            ensureStudentDormGraphLoaded(true);
        }

        function ensureStudentSkillsGraphLoaded(forceReload = false) {
            const iframe = document.getElementById('student-skills-graph-iframe');
            if (!iframe) return;
            const targetSrc = iframe.getAttribute('data-src') || 'skills_graph/index.html?mode=php&role=student&embed=1';
            const currentSrc = iframe.getAttribute('src') || '';
            if (forceReload || !currentSrc || currentSrc === 'about:blank') {
                iframe.setAttribute('src', targetSrc);
            }
        }

        function reloadStudentSkillsGraph() {
            ensureStudentSkillsGraphLoaded(true);
        }

        function openStudentDormGraph() {
            switchStudentTab('dorm');
            const section = document.getElementById('dorm-embedded-graph-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function switchStudentTab(tab) {
            ['card', 'dorm', 'festivals', 'welfare'].forEach(t => {
                const pane = document.getElementById('tab-content-' + t);
                const btn  = document.getElementById('tab-btn-' + t);
                if (pane && btn) {
                    if (t === tab) {
                        pane.classList.remove('hidden');
                        btn.className = 'flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-teal-700 text-white shadow-sm cursor-pointer shrink-0';
                    } else {
                        pane.classList.add('hidden');
                        btn.className = 'flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all bg-white text-slate-600 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0';
                    }
                }
            });

            if (tab === 'dorm') {
                ensureStudentDormGraphLoaded(false);
            } else if (tab === 'festivals') {
                ensureStudentSkillsGraphLoaded(false);
            }

            try {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            } catch (e) {}
        }

        // فیلتر روزهای جدول سانس‌های سالن بدنسازی
        function selectGymDateFilter(jDate, btn) {
            document.querySelectorAll('.gym-date-tab-btn').forEach(b => {
                b.className = 'gym-date-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700';
            });
            if (btn) {
                btn.className = 'gym-date-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shrink-0 bg-indigo-600 text-white shadow-xs';
            }

            const cleanKey = jDate.replace(/\//g, '-');
            document.querySelectorAll('.gym-date-group').forEach(grp => {
                grp.classList.add('hidden');
            });
            const targetGroup = document.getElementById('gym-date-group-' + cleanKey);
            if (targetGroup) {
                targetGroup.classList.remove('hidden');
            }
        }

        let currentViewingBooking = null;

        // مودال بلیت دیجیتال سالن بدنسازی با اعتبارسنجی وضعیت نوبت و کنترل چاپ
        function openGymTicketModal(bk) {
            if (!bk) return;
            currentViewingBooking = bk;
            const m = document.getElementById('gymTicketModal');
            if (!m) return;

            const trackingEl = document.getElementById('gt-tracking');
            if (trackingEl) trackingEl.textContent = bk.tracking_code || 'GYM-1403';

            const dtEl = document.getElementById('gt-datetime');
            if (dtEl) {
                dtEl.textContent = `${bk.day_name_fa || bk.day_of_week || ''} - ${bk.slot_date_jalali || ''} ساعت ${bk.start_time ? bk.start_time.substring(0, 5) : ''} الی ${bk.end_time ? bk.end_time.substring(0, 5) : ''}`;
            }

            const costEl = document.getElementById('gt-cost');
            if (costEl) {
                const cost = parseInt(bk.amount_paid_rials || bk.paid_amount || 0);
                costEl.textContent = `${bk.duration_hours || 2} ساعت (${cost.toLocaleString('fa-IR')} ریال)`;
            }

            const tokEl = document.getElementById('gt-token-display');
            if (tokEl) {
                tokEl.textContent = bk.booking_token || 'TOKEN-HASH';
            }

            // کنترل دقیق وضعیت اعتبار بلیت برای جلوگیری از سوءاستفاده از نوبت‌های لغو شده
            const statusEl = document.getElementById('gt-status');
            const stampEl = document.getElementById('gt-stamp');
            const warnBanner = document.getElementById('gt-cancelled-banner');
            const watermarkEl = document.getElementById('gt-watermark');
            const printBtn = document.getElementById('gt-print-btn');
            const tokenBadge = document.getElementById('gt-token-badge');
            const qrLabel = document.getElementById('gt-qr-label');
            const qrSub = document.getElementById('gt-qr-sub');

            const isCancelled = bk.status === 'cancelled_by_manager' || (bk.refund_status && bk.refund_status === 'refunded_to_wallet');

            if (isCancelled) {
                if (statusEl) {
                    statusEl.className = 'text-rose-600 font-bold';
                    statusEl.textContent = 'لغو شده توسط مدیریت (مبلغ به کیف پول عودت شد) ✕';
                }
                if (stampEl) {
                    stampEl.className = 'w-28 h-10 border border-dashed border-rose-500 rounded-lg flex items-center justify-center text-[10px] text-rose-700 font-bold bg-rose-50/70 mt-1';
                    stampEl.textContent = 'نوبت باطل است ✕';
                }
                if (warnBanner) warnBanner.classList.remove('hidden');
                if (watermarkEl) watermarkEl.classList.remove('hidden');
                if (tokenBadge) {
                    tokenBadge.className = 'text-[9px] px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold';
                    tokenBadge.textContent = 'باطل و لغو شده ✕';
                }
                if (qrLabel) qrLabel.textContent = 'CANCELLED';
                if (qrSub) qrSub.textContent = 'VOID-REFUND';

                // کنترل کلید چاپ در حالت لغو: مسدودسازی دکمه برای جلوگیری از سوءاستفاده
                if (printBtn) {
                    printBtn.disabled = true;
                    printBtn.className = 'px-5 py-2.5 bg-slate-200 text-slate-400 text-xs font-bold rounded-xl cursor-not-allowed flex items-center gap-1.5 transition-all';
                    printBtn.innerHTML = '<span>🚫</span><span>این بلیت باطل شده و غیرقابل چاپ است</span>';
                }
            } else {
                if (statusEl) {
                    if (bk.status === 'attended') {
                        statusEl.className = 'text-indigo-700 font-bold';
                        statusEl.textContent = 'استفاده شده (حاضر در سالن) ✓';
                    } else {
                        statusEl.className = 'text-emerald-700 font-bold';
                        statusEl.textContent = 'معتبر و تایید شده ✓';
                    }
                }
                if (stampEl) {
                    stampEl.className = 'w-28 h-10 border border-dashed border-emerald-500 rounded-lg flex items-center justify-center text-[10px] text-emerald-700 font-bold bg-emerald-50/70 mt-1';
                    stampEl.textContent = 'تایید نوبت ورزشی ✓';
                }
                if (warnBanner) warnBanner.classList.add('hidden');
                if (watermarkEl) watermarkEl.classList.add('hidden');
                if (tokenBadge) {
                    tokenBadge.className = 'text-[9px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold';
                    tokenBadge.textContent = 'معتبر و یکتا ✓';
                }
                if (qrLabel) qrLabel.textContent = 'QR / TOKEN';
                if (qrSub) qrSub.textContent = 'SAMAD-GYM-OK';

                // فعال‌سازی کلید چاپ رسمی برای بلیت معتبر
                if (printBtn) {
                    printBtn.disabled = false;
                    printBtn.className = 'px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow cursor-pointer flex items-center gap-1.5 transition-all';
                    printBtn.innerHTML = '<span>🖨️</span><span>چاپ رسمی این بلیت در قطع A4</span>';
                }
            }

            m.classList.remove('hidden');
            m.classList.add('flex');
            m.style.display = 'flex';
        }

        function closeGymTicketModal() {
            currentViewingBooking = null;
            const m = document.getElementById('gymTicketModal');
            if (m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
            }
        }

        // تابع کنترل‌شده و ایمن چاپ رسمی بلیت در قطع A4
        function printGymTicket() {
            if (currentViewingBooking && (currentViewingBooking.status === 'cancelled_by_manager' || currentViewingBooking.refund_status === 'refunded_to_wallet')) {
                alert('این بلیت توسط مدیریت لغو شده و مبلغ آن به کیف پول شما عودت داده شده است. امکان چاپ بلیت باطل شده وجود ندارد.');
                return;
            }
            document.body.classList.remove('print-dorm-certificate-active', 'print-student-card-active');
            document.body.classList.add('print-gym-ticket-active');
            window.print();
        }

        // تابع چاپ اختصاصی گواهی سکونت
        function printDormCertificate() {
            document.body.classList.remove('print-gym-ticket-active', 'print-student-card-active');
            document.body.classList.add('print-dorm-certificate-active');
            window.print();
        }

        // تابع چاپ اختصاصی کارت دانشجویی
        function printStudentCard() {
            document.body.classList.remove('print-gym-ticket-active', 'print-dorm-certificate-active');
            document.body.classList.add('print-student-card-active');
            window.print();
        }

        // هماهنگی هوشمند چاپ با کلیدهای میانبر مانند Ctrl + P
        window.addEventListener('beforeprint', () => {
            const gymModal = document.getElementById('gymTicketModal');
            const dormModal = document.getElementById('dormCertificateModal');
            const cardModal = document.getElementById('studentCardModal');

            if (gymModal && !gymModal.classList.contains('hidden') && gymModal.style.display !== 'none') {
                document.body.classList.add('print-gym-ticket-active');
            } else if (dormModal && !dormModal.classList.contains('hidden') && dormModal.style.display !== 'none') {
                document.body.classList.add('print-dorm-certificate-active');
            } else if (cardModal && !cardModal.classList.contains('hidden') && cardModal.style.display !== 'none') {
                document.body.classList.add('print-student-card-active');
            }
        });

        window.addEventListener('afterprint', () => {
            document.body.classList.remove('print-gym-ticket-active', 'print-dorm-certificate-active', 'print-student-card-active');
        });

        function openStudentCardModal() {
            document.getElementById('studentCardModal').classList.remove('hidden');
        }

        function closeStudentCardModal() {
            document.getElementById('studentCardModal').classList.add('hidden');
        }

        function openDormCertificateModal() {
            document.getElementById('dormCertificateModal').classList.remove('hidden');
        }

        function closeDormCertificateModal() {
            document.getElementById('dormCertificateModal').classList.add('hidden');
        }

        function openProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }

        // بررسی پارامتر تب در بارگذاری صفحه
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab');
            if (initialTab && ['card', 'dorm', 'festivals', 'welfare'].includes(initialTab)) {
                switchStudentTab(initialTab);
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeStudentCardModal();
                closeDormCertificateModal();
                closeProfileModal();
                closeGymTicketModal();
            }
        });
    </script>
</body>
</html>
