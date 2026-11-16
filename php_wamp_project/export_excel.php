<?php
/**
 * خروجی رسمی اکسل (.xls با پشتیبانی کامل UTF-8 RTL و فارسی) بدون نیاز به اینترنت یا کتابخانه خارجی
 * پشتیبانی از ۳ نوع گزارش مدیریتی:
 *  - jwt_tokens  (۱ ماهه، ۳ ماهه، ۶ ماهه)
 *  - attendance  (کل خوابگاه یا اتاق خاص، در بازه‌های ۱، ۳، ۷، ۱۵ روزه و ۱، ۳، ۶ ماهه)
 *  - cleanliness (کل خوابگاه یا اتاق خاص، در بازه‌های ۱، ۳، ۷، ۱۵ روزه و ۱، ۳، ۶ ماهه)
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt_auth.php';

$jwtAuth = samad_require_page_jwt($pdo, ['manager'], null);

$type = trim((string)($_GET['type'] ?? ''));
$range = trim((string)($_GET['range'] ?? '1m'));
$roomParam = trim((string)($_GET['room'] ?? 'all'));
if ($roomParam === '') {
    $roomParam = 'all';
}

function samad_export_jalali_to_gregorian(int $jy, int $jm, int $jd): array
{
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

function samad_export_parse_jalali_to_ymd(?string $jalaliStr, ?string $fallbackCreatedAt = null): ?string
{
    $clean = toEnglishDigits(trim((string)$jalaliStr));
    if (preg_match('/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $clean, $m)) {
        $y = (int)$m[1];
        $mo = (int)$m[2];
        $d = (int)$m[3];
        if ($y >= 1300 && $y <= 1500 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
            [$gy, $gm, $gd] = samad_export_jalali_to_gregorian($y, $mo, $d);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }
        if ($y >= 1990 && $y <= 2100 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }
    if (!empty($fallbackCreatedAt)) {
        $ts = strtotime($fallbackCreatedAt);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
    }
    return null;
}

function samad_export_output_excel(string $filenameBase, string $reportTitle, string $subtitle, array $headers, array $rows): void
{
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filenameBase) . '_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Tahoma, Arial, sans-serif; direction: rtl; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; vertical-align: middle; font-size: 12px; mso-number-format: "\@"; }
    th { background-color: #1e293b; color: #ffffff; font-weight: bold; }
    .title-cell { background-color: #0f172a; color: #ffffff; font-size: 14px; font-weight: bold; text-align: center; padding: 10px; }
    .sub-cell { background-color: #e2e8f0; color: #0f172a; font-size: 11px; text-align: center; padding: 6px; }
    .even { background-color: #f8fafc; }
</style>
</head>
<body>
    <table>
        <tr><td colspan="<?= count($headers) ?>" class="title-cell"><?= htmlspecialchars($reportTitle) ?></td></tr>
        <tr><td colspan="<?= count($headers) ?>" class="sub-cell"><?= htmlspecialchars($subtitle) ?> — تاریخ تولید: <?= date('Y-m-d H:i') ?> | تعداد رکورد: <?= count($rows) ?></td></tr>
        <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th><?= htmlspecialchars((string)$h) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= count($headers) ?>" style="text-align:center;">رکوردی در بازه انتخاب‌شده یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $idx => $r): ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'even' : '' ?>">
                        <?php foreach ($r as $cell): ?>
                            <td><?= htmlspecialchars((string)$cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
    <?php
    exit;
}

if ($type === 'jwt_tokens') {
    if (!samad_has_permission($jwtAuth, 'auth:tokens:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], 'manager', $jwtAuth['jti'], 'export_excel.php?type=jwt_tokens', 403, 'فقدان مجوز auth:tokens:manage برای خروجی اکسل توکن‌ها');
        samad_render_forbidden_page('عدم دسترسی به خروجی اکسل توکن‌ها', 'مجوز مدیریت توکن‌ها و دسترسی‌ها برای حساب شما غیرفعال است.', 'manager');
    }

    $rangeMap = [
        '1m' => ['days' => 30, 'label' => '۱ ماه گذشته (۳۰ روز)'],
        '3m' => ['days' => 90, 'label' => '۳ ماه گذشته (۹۰ روز)'],
        '6m' => ['days' => 180, 'label' => '۶ ماه گذشته (۱۸۰ روز)'],
    ];
    $cfg = $rangeMap[$range] ?? $rangeMap['1m'];
    $cutoffTs = time() - ($cfg['days'] * 86400);

    $stmt = $pdo->prepare("
        SELECT `jti`, `subject_id`, `role`, `subject_name`, `issued_at`, `expires_at`,
               `revoked_at`, `revoked_by`, `revoke_reason`, `ip_address`, `last_used_at`, `created_at`
        FROM `auth_jwt_tokens`
        WHERE `issued_at` >= ?
        ORDER BY `id` DESC
    ");
    $stmt->execute([$cutoffTs]);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $excelRows = [];
    $nowTs = time();
    foreach ($rawRows as $i => $row) {
        $roleFa = $row['role'] === 'manager' ? 'مدیر' : ($row['role'] === 'supervisor' ? 'سرپرست' : 'دانشجو');
        if (!empty($row['revoked_at'])) {
            $statusFa = 'لغوشده';
        } elseif ((int)$row['expires_at'] <= $nowTs) {
            $statusFa = 'منقضی';
        } else {
            $statusFa = 'فعال';
        }
        $excelRows[] = [
            $i + 1,
            $row['subject_name'],
            $row['subject_id'],
            $roleFa,
            $row['jti'],
            $statusFa,
            date('Y-m-d H:i:s', (int)$row['issued_at']),
            date('Y-m-d H:i:s', (int)$row['expires_at']),
            $row['revoked_at'] ?: '-',
            $row['revoked_by'] ?: '-',
            $row['revoke_reason'] ?: '-',
            $row['ip_address'] ?: '-',
            $row['last_used_at'] ?: '-',
        ];
    }

    samad_export_output_excel(
        'jwt_tokens_' . $range,
        'گزارش اکسل لاگ توکن‌های JWT سامانه صمد',
        'بازه زمانی: ' . $cfg['label'],
        ['ردیف', 'نام کاربر', 'شناسه (sub)', 'نقش', 'شناسه توکن (JTI)', 'وضعیت', 'زمان صدور', 'زمان انقضا', 'زمان ابطال', 'ابطال‌کننده', 'دلیل ابطال', 'آدرس IP', 'آخرین استفاده'],
        $excelRows
    );
}

if ($type === 'attendance') {
    if (!samad_has_permission($jwtAuth, 'dorm:attendance:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], 'manager', $jwtAuth['jti'], 'export_excel.php?type=attendance', 403, 'فقدان مجوز dorm:attendance:manage برای خروجی اکسل سرشماری');
        samad_render_forbidden_page('عدم دسترسی به خروجی اکسل سرشماری', 'مجوز مدیریت سرشماری شبانه برای حساب شما غیرفعال است.', 'manager');
    }

    $rangeMap = [
        '1d'  => ['days' => 1,   'label' => '۱ روز گذشته'],
        '3d'  => ['days' => 3,   'label' => '۳ روز گذشته'],
        '7d'  => ['days' => 7,   'label' => '۷ روز گذشته'],
        '15d' => ['days' => 15,  'label' => '۱۵ روز گذشته'],
        '1m'  => ['days' => 30,  'label' => '۱ ماه گذشته'],
        '3m'  => ['days' => 90,  'label' => '۳ ماه گذشته'],
        '6m'  => ['days' => 180, 'label' => '۶ ماه گذشته'],
    ];
    $cfg = $rangeMap[$range] ?? $rangeMap['7d'];
    $cutoffDate = date('Y-m-d', strtotime('-' . $cfg['days'] . ' days'));

    $sql = "
        SELECT a.attendance_date, a.student_id, a.status, a.notes, a.supervisor_national_id, a.created_at,
               s.full_name, s.national_id, s.field_of_study,
               r.room_number, r.floor,
               adm.full_name AS supervisor_name
        FROM `dorm_night_attendance` a
        LEFT JOIN `students` s ON a.student_id = s.student_id
        LEFT JOIN `dorm_rooms` r ON a.room_id = r.id
        LEFT JOIN `admins` adm ON a.supervisor_national_id = adm.national_id
        WHERE a.attendance_date >= ?
    ";
    $params = [$cutoffDate];
    $roomLabel = 'کل خوابگاه';
    if ($roomParam !== 'all') {
        $sql .= " AND r.room_number = ?";
        $params[] = $roomParam;
        $roomLabel = 'اتاق ' . $roomParam;
    }
    $sql .= " ORDER BY a.attendance_date DESC, FIELD(r.floor, 'همکف', 'طبقه اول', 'طبقه دوم'), r.room_number ASC, s.full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $excelRows = [];
    foreach ($rawRows as $i => $row) {
        $excelRows[] = [
            $i + 1,
            $row['attendance_date'],
            $row['room_number'] ?: '-',
            $row['floor'] ?: '-',
            $row['full_name'] ?: $row['student_id'],
            $row['student_id'],
            $row['national_id'] ?: '-',
            $row['field_of_study'] ?: '-',
            $row['status'],
            $row['notes'] ?: '-',
            $row['supervisor_name'] ?: ($row['supervisor_national_id'] ?: '-'),
            $row['created_at'] ?: '-',
        ];
    }

    samad_export_output_excel(
        'attendance_' . ($roomParam === 'all' ? 'all_dorm' : 'room_' . $roomParam) . '_' . $range,
        'گزارش اکسل سرشماری شبانه خوابگاه صمد',
        'محدوده: ' . $roomLabel . ' | بازه زمانی: ' . $cfg['label'] . " (از {$cutoffDate})",
        ['ردیف', 'تاریخ سرشماری', 'شماره اتاق', 'طبقه', 'نام دانشجو', 'شماره دانشجویی', 'کد ملی', 'رشته تحصیلی', 'وضعیت حضور', 'توضیحات سرپرست', 'سرپرست ثبت‌کننده', 'زمان ثبت سیستمی'],
        $excelRows
    );
}

if ($type === 'cleanliness') {
    if (!samad_has_permission($jwtAuth, 'dorm:cleanliness:manage')) {
        samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], 'manager', $jwtAuth['jti'], 'export_excel.php?type=cleanliness', 403, 'فقدان مجوز dorm:cleanliness:manage برای خروجی اکسل نظافت');
        samad_render_forbidden_page('عدم دسترسی به خروجی اکسل نظافت', 'مجوز نظارت بر نظافت اتاق‌ها برای حساب شما غیرفعال است.', 'manager');
    }

    $rangeMap = [
        '1d'  => ['days' => 1,   'label' => '۱ روز گذشته'],
        '3d'  => ['days' => 3,   'label' => '۳ روز گذشته'],
        '7d'  => ['days' => 7,   'label' => '۷ روز گذشته'],
        '15d' => ['days' => 15,  'label' => '۱۵ روز گذشته'],
        '1m'  => ['days' => 30,  'label' => '۱ ماه گذشته'],
        '3m'  => ['days' => 90,  'label' => '۳ ماه گذشته'],
        '6m'  => ['days' => 180, 'label' => '۶ ماه گذشته'],
    ];
    $cfg = $rangeMap[$range] ?? $rangeMap['7d'];
    $cutoffDate = date('Y-m-d', strtotime('-' . $cfg['days'] . ' days'));

    $sql = "
        SELECT c.*, r.floor AS room_floor
        FROM `room_cleanliness_records` c
        LEFT JOIN `dorm_rooms` r ON c.room_number = r.room_number
    ";
    $params = [];
    $roomLabel = 'کل خوابگاه';
    if ($roomParam !== 'all') {
        $sql .= " WHERE c.room_number = ?";
        $params[] = $roomParam;
        $roomLabel = 'اتاق ' . $roomParam;
    }
    $sql .= " ORDER BY c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allCleanRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $excelRows = [];
    $rowNum = 1;
    foreach ($allCleanRows as $row) {
        $parsedDate = samad_export_parse_jalali_to_ymd($row['inspection_date'] ?? '', $row['created_at'] ?? null);
        if ($parsedDate !== null && $parsedDate < $cutoffDate) {
            continue;
        }
        $grade = $row['grade'] ?? ($row['cleanliness_grade'] ?? '-');
        $excelRows[] = [
            $rowNum++,
            $row['inspection_date'] ?: '-',
            $parsedDate ?: '-',
            $row['room_number'] ?: '-',
            $row['room_floor'] ?: ($row['floor'] ?: '-'),
            $grade,
            $row['supervisor_name'] ?: '-',
            $row['supervisor_national_id'] ?: '-',
            $row['notes'] ?: '-',
            $row['recorded_at'] ?: ($row['created_at'] ?: '-'),
        ];
    }

    samad_export_output_excel(
        'cleanliness_' . ($roomParam === 'all' ? 'all_dorm' : 'room_' . $roomParam) . '_' . $range,
        'گزارش اکسل ارزیابی نظافت اتاق‌های خوابگاه صمد',
        'محدوده: ' . $roomLabel . ' | بازه زمانی: ' . $cfg['label'] . " (از {$cutoffDate})",
        ['ردیف', 'تاریخ بازرسی (شمسی)', 'معادل میلادی', 'شماره اتاق', 'طبقه', 'سطح کیفی نظافت', 'سرپرست ارزیاب', 'کد ملی سرپرست', 'توضیحات و تذکرات', 'زمان ثبت'],
        $excelRows
    );
}

http_response_code(400);
header('Content-Type: text/plain; charset=utf-8');
echo 'نوع گزارش درخواستی نامعتبر است.';
