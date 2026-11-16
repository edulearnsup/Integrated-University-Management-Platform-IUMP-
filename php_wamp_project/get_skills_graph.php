<?php
/**
 * API: دریافت و مدیریت گراف تعاملی مهارت‌ها، علایق و دستاوردهای دانشجو
 * سازگار با ماژول React (SkillsGraphWorkspace) و سامانه صمد (WAMP + MySQL + JWT RBAC)
 *
 * اکشن‌های پشتیبانی‌شده:
 *  - GET  ?action=me                          -> دریافت گراف کامل دانشجوی احراز هویت‌شده (همراه با درون‌ریزی خودکار داده‌های ثبت‌نام)
 *  - GET  ?action=domains                     -> فهرست حوزه‌های رسمی فعال دانشگاه
 *  - POST ?action=attach_domain               -> اتصال یک حوزه رسمی به نقشه دانشجو
 *  - POST|DELETE ?action=detach_domain        -> جدا کردن حوزه رسمی از نقشه دانشجو
 *  - POST ?action=create_node                 -> ساخت باکس جدید (مهارت، حوزه شخصی، دستاورد، رویداد)
 *  - POST|PUT ?action=update_node             -> ویرایش باکس شخصی یا مرجع
 *  - POST|DELETE ?action=delete_node          -> حذف باکس شخصی/مرجع و یال‌های متصل
 *  - POST ?action=create_edge                 -> ایجاد خط ارتباطی بین دو باکس (با پورت‌های ۴گانه)
 *  - POST|PUT ?action=update_edge             -> ویرایش برچسب و نوع رابطه خط ارتباطی
 *  - POST|DELETE ?action=delete_edge          -> حذف خط ارتباطی
 *  - POST|PUT ?action=save_layout             -> ذخیره مختصات باکس‌ها پس از جابه‌جایی
 *  - POST ?action=reset                       -> بازنشانی نقشه به داده‌های پیش‌فرض ثبت‌نام دانشجو
 */

@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt_auth.php';

const SKILL_WORLD_CENTER_X = 560;
const SKILL_WORLD_CENTER_Y = 380;
const SKILL_BOX_W = 220;
const SKILL_BOX_H = 82;

function skills_send_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function skills_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function skills_uuid_short(): string
{
    try {
        return bin2hex(random_bytes(5));
    } catch (Exception $e) {
        return substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
    }
}

function skills_derive_entry_year(string $studentId): int
{
    if (preg_match('/^40([0-9])/', $studentId, $m)) {
        return 1400 + (int)$m[1];
    }
    return 1403;
}

function skills_level_to_numeric(?string $level): int
{
    switch ($level) {
        case 'beginner':
            return 1;
        case 'elementary':
            return 2;
        case 'intermediate':
            return 3;
        case 'advanced':
            return 4;
        case 'expert':
            return 5;
        default:
            return 3;
    }
}

function skills_numeric_to_level(int $num): string
{
    switch ($num) {
        case 1:
            return 'beginner';
        case 2:
            return 'elementary';
        case 4:
            return 'advanced';
        case 5:
            return 'expert';
        default:
            return 'intermediate';
    }
}

function skills_normalize_proficiency($level, $numeric = null): string
{
    $allowed = ['beginner', 'elementary', 'intermediate', 'advanced', 'expert'];
    if (is_string($level) && in_array($level, $allowed, true)) {
        return $level;
    }
    if (is_numeric($numeric)) {
        $n = max(1, min(5, (int)$numeric));
        return skills_numeric_to_level($n);
    }
    return 'intermediate';
}

function skills_normalize_learning_status($status): string
{
    $allowed = ['interested', 'learning', 'practiced', 'proficient', 'archived'];
    if (is_string($status) && in_array($status, $allowed, true)) {
        return $status;
    }
    return 'learning';
}

function skills_normalize_relation_type($rel): string
{
    $allowed = [
        'has_domain',
        'has_interest',
        'has_skill',
        'belongs_to',
        'related_to',
        'has_achievement',
        'demonstrates',
        'participated_in',
    ];
    if (is_string($rel) && in_array($rel, $allowed, true)) {
        return $rel;
    }
    return 'related_to';
}

function skills_default_relation_label(string $rel): string
{
    $map = [
        'has_domain' => 'اتصال به حوزه',
        'has_interest' => 'دارای علاقه',
        'has_skill' => 'دارای مهارت',
        'belongs_to' => 'زیرمجموعه حوزه',
        'related_to' => 'مرتبط با',
        'has_achievement' => 'دارای دستاورد',
        'demonstrates' => 'اثبات‌کننده مهارت',
        'participated_in' => 'مشارکت در رویداد',
    ];
    return $map[$rel] ?? 'ارتباط مهارتی';
}

function skills_normalize_port($side): ?string
{
    $allowed = ['top', 'bottom', 'left', 'right'];
    return (is_string($side) && in_array($side, $allowed, true)) ? $side : null;
}

function skills_audit(PDO $pdo, string $actorId, string $action, string $entityType, string $entityId, $before = null, $after = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `ug_audit_logs` (`actor_id`, `action`, `entity_type`, `entity_id`, `before_data`, `after_data`)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $actorId,
            $action,
            $entityType,
            $entityId,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    } catch (Throwable $e) {
    }
}

function skills_str_lower(string $text): string
{
    $trimmed = trim($text);
    return function_exists('mb_strtolower') ? mb_strtolower($trimmed, 'UTF-8') : strtolower($trimmed);
}

function skills_str_contains(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return false;
    }
    if (function_exists('mb_strpos')) {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }
    return strpos($haystack, $needle) !== false;
}

function skills_ensure_schema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $required = [
        'ug_domains',
        'ug_student_domains',
        'ug_skills',
        'ug_skill_domains',
        'ug_achievements',
        'ug_graph_edges',
        'ug_graph_layouts',
        'ug_audit_logs',
    ];
    $missing = false;
    foreach ($required as $tbl) {
        $chk = $pdo->query("SHOW TABLES LIKE '{$tbl}'")->fetchColumn();
        if (!$chk) {
            $missing = true;
            break;
        }
    }

    if ($missing) {
        $ddls = [
            'ug_domains' => "
                CREATE TABLE IF NOT EXISTS `ug_domains` (
                    `id` VARCHAR(64) PRIMARY KEY,
                    `parent_id` VARCHAR(64) DEFAULT NULL,
                    `title` VARCHAR(150) NOT NULL,
                    `slug` VARCHAR(80) NOT NULL UNIQUE,
                    `description` TEXT DEFAULT NULL,
                    `icon` VARCHAR(50) NOT NULL DEFAULT 'book',
                    `color` VARCHAR(20) NOT NULL DEFAULT '#589bf6',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `sort_order` INT NOT NULL DEFAULT 0,
                    `created_at` DATETIME DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ",
            'ug_student_domains' => "
                CREATE TABLE IF NOT EXISTS `ug_student_domains` (
                    `id` VARCHAR(80) PRIMARY KEY,
                    `student_id` VARCHAR(20) NOT NULL,
                    `domain_id` VARCHAR(64) NOT NULL,
                    `relation_kind` VARCHAR(30) NOT NULL DEFAULT 'interest',
                    `custom_title` VARCHAR(150) DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `visibility` ENUM('private', 'university', 'selected_roles', 'public') NOT NULL DEFAULT 'private',
                    `created_at` DATETIME DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_ug_student_domain` (`student_id`, `domain_id`),
                    INDEX `idx_ug_sd_student` (`student_id`)
                )
            ",
            'ug_skills' => "
                CREATE TABLE IF NOT EXISTS `ug_skills` (
                    `id` VARCHAR(80) PRIMARY KEY,
                    `student_id` VARCHAR(20) NOT NULL,
                    `title` VARCHAR(180) NOT NULL,
                    `subtitle` VARCHAR(255) DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `category` VARCHAR(100) DEFAULT 'مهارت شخصی',
                    `level` ENUM('beginner', 'elementary', 'intermediate', 'advanced', 'expert') NOT NULL DEFAULT 'intermediate',
                    `learning_status` ENUM('interested', 'learning', 'practiced', 'proficient', 'archived') NOT NULL DEFAULT 'learning',
                    `source_type` ENUM('personal', 'system', 'reference') NOT NULL DEFAULT 'personal',
                    `verification_status` ENUM('unverified', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
                    `catalog_skill_id` VARCHAR(64) DEFAULT NULL,
                    `visibility` ENUM('private', 'university', 'selected_roles', 'public') NOT NULL DEFAULT 'private',
                    `started_at` DATETIME DEFAULT NULL,
                    `archived_at` DATETIME DEFAULT NULL,
                    `created_at` DATETIME DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_ug_skills_student` (`student_id`)
                )
            ",
            'ug_skill_domains' => "
                CREATE TABLE IF NOT EXISTS `ug_skill_domains` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `skill_id` VARCHAR(80) NOT NULL,
                    `student_domain_id` VARCHAR(80) NOT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_ug_skill_dom` (`skill_id`, `student_domain_id`),
                    INDEX `idx_ug_skdom_skill` (`skill_id`),
                    INDEX `idx_ug_skdom_dom` (`student_domain_id`)
                )
            ",
            'ug_achievements' => "
                CREATE TABLE IF NOT EXISTS `ug_achievements` (
                    `id` VARCHAR(80) PRIMARY KEY,
                    `student_id` VARCHAR(20) NOT NULL,
                    `title` VARCHAR(200) NOT NULL,
                    `subtitle` VARCHAR(255) DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `achievement_type` ENUM('achievement', 'event') NOT NULL DEFAULT 'achievement',
                    `domain_id` VARCHAR(64) DEFAULT NULL,
                    `source_type` ENUM('personal', 'system', 'reference') NOT NULL DEFAULT 'personal',
                    `source_system` VARCHAR(80) DEFAULT NULL,
                    `source_record_id` VARCHAR(80) DEFAULT NULL,
                    `verification_status` ENUM('unverified', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
                    `achieved_at` VARCHAR(40) DEFAULT NULL,
                    `visibility` ENUM('private', 'university', 'selected_roles', 'public') NOT NULL DEFAULT 'private',
                    `created_at` DATETIME DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_ug_ach_student` (`student_id`)
                )
            ",
            'ug_graph_edges' => "
                CREATE TABLE IF NOT EXISTS `ug_graph_edges` (
                    `id` VARCHAR(100) PRIMARY KEY,
                    `student_id` VARCHAR(20) NOT NULL,
                    `source_type` VARCHAR(30) NOT NULL,
                    `source_id` VARCHAR(100) NOT NULL,
                    `target_type` VARCHAR(30) NOT NULL,
                    `target_id` VARCHAR(100) NOT NULL,
                    `edge_type` VARCHAR(40) NOT NULL,
                    `label` VARCHAR(120) NOT NULL,
                    `source_handle` VARCHAR(16) DEFAULT NULL,
                    `target_handle` VARCHAR(16) DEFAULT NULL,
                    `source_type_badge` ENUM('personal', 'system', 'reference') NOT NULL DEFAULT 'personal',
                    `metadata` TEXT DEFAULT NULL,
                    `created_at` DATETIME DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_ug_edge_student` (`student_id`),
                    INDEX `idx_ug_edge_pair` (`student_id`, `source_id`(64), `target_id`(64))
                )
            ",
            'ug_graph_layouts' => "
                CREATE TABLE IF NOT EXISTS `ug_graph_layouts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `student_id` VARCHAR(20) NOT NULL,
                    `graph_type` VARCHAR(40) NOT NULL DEFAULT 'student_skills',
                    `layout_version` INT NOT NULL DEFAULT 1,
                    `layout_data` LONGTEXT NOT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_ug_layout_student` (`student_id`, `graph_type`)
                )
            ",
            'ug_audit_logs' => "
                CREATE TABLE IF NOT EXISTS `ug_audit_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `actor_id` VARCHAR(64) NOT NULL,
                    `action` VARCHAR(60) NOT NULL,
                    `entity_type` VARCHAR(50) NOT NULL,
                    `entity_id` VARCHAR(100) NOT NULL,
                    `before_data` TEXT DEFAULT NULL,
                    `after_data` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_ug_audit_actor` (`actor_id`)
                )
            ",
        ];

        foreach ($ddls as $tblName => $ddlSql) {
            $err = samad_exec_ddl_with_fallback($pdo, $ddlSql);
            if ($err !== null) {
                throw new RuntimeException("خطا در ایجاد جدول {$tblName}: {$err}");
            }
        }
    }

    $checked = true;
}

/**
 * تشخیص هوشمند بهترین حوزه فعالیت دانشگاهی برای یک مهارت یا رشته تحصیلی
 */
function skills_infer_domain_id(string $text, array $candidateDomainIds = []): string
{
    $lower = skills_str_lower($text);

    $rules = [
        'domain-tech' => ['برنامه', 'نرم‌افزار', 'نرم افزار', 'کامپیوتر', 'وب', 'سایت', 'پایتون', 'python', 'php', 'react', 'js', 'javascript', 'typescript', 'java', 'c++', 'c#', 'sql', 'mysql', 'شبکه', 'هوش مصنوعی', 'داده', 'لینوکس', 'امنیت', 'اپلیکیشن', 'فرانت', 'بک‌اند', 'کدنویس', 'گیت', 'it', 'فناوری'],
        'domain-workshop' => ['رباتیک', 'آردوینو', 'arduino', 'الکترونیک', 'برق', 'مکاترونیک', 'سخت‌افزار', 'کارگاه', 'تراش', 'جوش', 'تاسیسات', 'مکانیک', 'خودرو', 'ساخت', 'مدار', 'plc', 'فنی'],
        'domain-sport' => ['فوتسال', 'فوتبال', 'والیبال', 'بسکتبال', 'شنا', 'شطرنج', 'تنیس', 'پینگ', 'ورزش', 'بدنسازی', 'آمادگی', 'دومیدانی', 'کشتی', 'کاراته', 'تکواندو', 'کوهنوردی', 'تیم'],
        'domain-art' => ['طراحی', 'گرافیک', 'فتوشاپ', 'photoshop', 'illustrator', 'فیگما', 'figma', 'ui', 'ux', 'عکاسی', 'تدوین', 'پریمیر', 'افتر', 'موسیقی', 'معماری', 'نقاشی', 'خوشنویسی', 'هنر', 'انیمیشن'],
        'domain-culture' => ['فرهنگی', 'رسانه', 'نشریه', 'نویسندگی', 'محتوا', 'تئاتر', 'فیلم', 'کانون', 'خبرنگار', 'پادکست', 'اجرا', 'ادبی', 'شعر'],
        'domain-quran' => ['قرآن', 'عترت', 'قرائت', 'حفظ', 'مفاهیم', 'نهج', 'مداحی', 'دینی', 'معارفی', 'مذهبی', 'تواشیح'],
        'domain-research' => ['پژوهش', 'مقاله', 'اختراع', 'تحقیق', 'دانش‌بنیان', 'المپیاد', 'آزمایشگاه', 'نوآوری', 'استارتاپ', 'ایده'],
        'domain-edu' => ['زبان', 'انگلیسی', 'تدریس', 'ریاضی', 'فیزیک', 'حسابداری', 'مدیریت', 'علمی', 'آموزش', 'مشاوره', 'ترجمه', 'آفیس', 'اکسل', 'پاورپوینت', 'ورد'],
    ];

    foreach ($rules as $domId => $keywords) {
        if (!empty($candidateDomainIds) && !in_array($domId, $candidateDomainIds, true)) {
            continue;
        }
        foreach ($keywords as $kw) {
            if (skills_str_contains($lower, $kw)) {
                return $domId;
            }
        }
    }

    if (empty($candidateDomainIds)) {
        return skills_infer_domain_id($text, array_keys($rules));
    }

    return $candidateDomainIds[0] ?? 'domain-tech';
}

/**
 * حل تداخل مختصات باکس‌ها (Magnetic Repulsion Solver در PHP)
 */
function skills_resolve_layout_overlaps(array $positions, ?string $pinnedNodeId = null, int $iterations = 18): array
{
    $minDistX = SKILL_BOX_W + 54; // 274px
    $minDistY = SKILL_BOX_H + 52; // 134px

    $ids = array_keys($positions);
    $count = count($ids);
    if ($count < 2) {
        return $positions;
    }

    for ($iter = 0; $iter < $iterations; $iter++) {
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $idA = $ids[$i];
                $idB = $ids[$j];

                $ax = (float)$positions[$idA]['x'];
                $ay = (float)$positions[$idA]['y'] + (SKILL_BOX_H / 2);
                $bx = (float)$positions[$idB]['x'];
                $by = (float)$positions[$idB]['y'] + (SKILL_BOX_H / 2);

                $dx = $bx - $ax;
                $dy = $by - $ay;
                $absDx = abs($dx);
                $absDy = abs($dy);

                if ($absDx < $minDistX && $absDy < $minDistY) {
                    $overlapX = $minDistX - $absDx;
                    $overlapY = $minDistY - $absDy;

                    $pushX = 0.0;
                    $pushY = 0.0;

                    if ($overlapX < $overlapY * 1.4) {
                        $sign = $dx >= 0 ? 1.0 : -1.0;
                        $pushX = $sign * ($overlapX + 2.0);
                    } else {
                        $sign = $dy >= 0 ? 1.0 : -1.0;
                        $pushY = $sign * ($overlapY + 2.0);
                    }

                    $aPinned = ($idA === $pinnedNodeId);
                    $bPinned = ($idB === $pinnedNodeId);

                    if ($aPinned && !$bPinned) {
                        $positions[$idB]['x'] = (int)round($positions[$idB]['x'] + $pushX);
                        $positions[$idB]['y'] = (int)round($positions[$idB]['y'] + $pushY);
                    } elseif ($bPinned && !$aPinned) {
                        $positions[$idA]['x'] = (int)round($positions[$idA]['x'] - $pushX);
                        $positions[$idA]['y'] = (int)round($positions[$idA]['y'] - $pushY);
                    } else {
                        $positions[$idA]['x'] = (int)round($positions[$idA]['x'] - ($pushX * 0.5));
                        $positions[$idA]['y'] = (int)round($positions[$idA]['y'] - ($pushY * 0.5));
                        $positions[$idB]['x'] = (int)round($positions[$idB]['x'] + ($pushX * 0.5));
                        $positions[$idB]['y'] = (int)round($positions[$idB]['y'] + ($pushY * 0.5));
                    }
                }
            }
        }
    }

    return $positions;
}

function skills_load_layout(PDO $pdo, string $studentId): array
{
    $stmt = $pdo->prepare("
        SELECT `layout_version`, `layout_data`, `updated_at`
        FROM `ug_graph_layouts`
        WHERE `student_id` = ? AND `graph_type` = 'student_skills'
        LIMIT 1
    ");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['version' => 1, 'positions' => [], 'updated_at' => gmdate('c')];
    }
    $decoded = json_decode((string)$row['layout_data'], true);
    return [
        'version' => (int)$row['layout_version'],
        'positions' => is_array($decoded) ? $decoded : [],
        'updated_at' => !empty($row['updated_at']) ? date('c', strtotime((string)$row['updated_at'])) : gmdate('c'),
    ];
}

function skills_save_layout(PDO $pdo, string $studentId, array $positions, bool $incrementVersion = true): int
{
    $stmtCur = $pdo->prepare("
        SELECT `layout_version` FROM `ug_graph_layouts`
        WHERE `student_id` = ? AND `graph_type` = 'student_skills'
        LIMIT 1
    ");
    $stmtCur->execute([$studentId]);
    $cur = $stmtCur->fetch(PDO::FETCH_ASSOC);
    $nextVersion = $cur ? ((int)$cur['layout_version'] + ($incrementVersion ? 1 : 0)) : 1;

    $json = json_encode($positions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmtUp = $pdo->prepare("
        INSERT INTO `ug_graph_layouts` (`student_id`, `graph_type`, `layout_version`, `layout_data`)
        VALUES (?, 'student_skills', ?, ?)
        ON DUPLICATE KEY UPDATE
            `layout_version` = VALUES(`layout_version`),
            `layout_data` = VALUES(`layout_data`)
    ");
    $stmtUp->execute([$studentId, $nextVersion, $json]);
    return $nextVersion;
}

/**
 * درون‌ریزی خودکار باکس‌های اولیه دانشجو از داده‌های فرم ثبت‌نام صمد
 * (student_skills + student_festivals + student_sports_teams + students)
 */
function samad_seed_student_skill_graph_from_registration(PDO $pdo, string $studentId): void
{
    $stmtSt = $pdo->prepare("
        SELECT `student_id`, `full_name`, `campus`, `field_of_study`, `study_shift`
        FROM `students`
        WHERE `student_id` = ?
        LIMIT 1
    ");
    $stmtSt->execute([$studentId]);
    $student = $stmtSt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        return;
    }

    // خواندن حوزه‌های فعال رسمی
    $stmtAllDom = $pdo->query("SELECT * FROM `ug_domains` WHERE `is_active` = 1 ORDER BY `sort_order` ASC");
    $allDomains = [];
    foreach ($stmtAllDom->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $allDomains[$d['id']] = $d;
    }

    // خواندن مهارت‌های ثبت‌شده هنگام نام‌نویسی
    $regSkills = [];
    try {
        $stmtSk = $pdo->prepare("SELECT `id`, `skill_name` FROM `student_skills` WHERE `student_id` = ? ORDER BY `id` ASC");
        $stmtSk->execute([$studentId]);
        $regSkills = $stmtSk->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // خواندن جشنواره‌های انتخاب‌شده هنگام نام‌نویسی
    $regFestivals = [];
    try {
        $stmtFest = $pdo->prepare("SELECT `id`, `festival_key`, `festival_title` FROM `student_festivals` WHERE `student_id` = ? ORDER BY `id` ASC");
        $stmtFest->execute([$studentId]);
        $regFestivals = $stmtFest->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // خواندن تیم‌های ورزشی انتخاب‌شده هنگام نام‌نویسی
    $regTeams = [];
    try {
        $stmtTeams = $pdo->prepare("SELECT `id`, `team_name` FROM `student_sports_teams` WHERE `student_id` = ? ORDER BY `id` ASC");
        $stmtTeams->execute([$studentId]);
        $regTeams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // نگاشت کلیدهای جشنواره ثبت‌نام به حوزه‌های استاندارد
    $festivalToDomain = [
        'technical' => 'domain-tech',
        'skillBased' => 'domain-workshop',
        'religious' => 'domain-quran',
        'cultural' => 'domain-culture',
        'robotics' => 'domain-research',
        'sports' => 'domain-sport',
    ];

    $chosenDomainIds = [];

    foreach ($regFestivals as $f) {
        $domId = $festivalToDomain[$f['festival_key']] ?? null;
        if ($domId && isset($allDomains[$domId]) && !in_array($domId, $chosenDomainIds, true)) {
            $chosenDomainIds[] = $domId;
        }
    }

    if (!empty($regTeams) && isset($allDomains['domain-sport']) && !in_array('domain-sport', $chosenDomainIds, true)) {
        $chosenDomainIds[] = 'domain-sport';
    }

    foreach ($regSkills as $sk) {
        $domId = skills_infer_domain_id((string)$sk['skill_name']);
        if (isset($allDomains[$domId]) && !in_array($domId, $chosenDomainIds, true)) {
            $chosenDomainIds[] = $domId;
        }
    }

    // تطبیق با رشته تحصیلی دانشجو
    $majorDom = skills_infer_domain_id((string)($student['field_of_study'] ?? ''));
    if (isset($allDomains[$majorDom]) && !in_array($majorDom, $chosenDomainIds, true)) {
        array_unshift($chosenDomainIds, $majorDom);
    }

    // اطمینان از وجود حداقل ۲ حوزه فعال در گراف اولیه
    foreach (['domain-tech', 'domain-edu', 'domain-research'] as $fallbackDom) {
        if (count($chosenDomainIds) >= 2) {
            break;
        }
        if (isset($allDomains[$fallbackDom]) && !in_array($fallbackDom, $chosenDomainIds, true)) {
            $chosenDomainIds[] = $fallbackDom;
        }
    }

    $chosenDomainIds = array_slice($chosenDomainIds, 0, 5);

    $pdo->beginTransaction();
    try {
        $studentNodeId = 'node-student-' . $studentId;
        $positions = [
            $studentNodeId => ['x' => SKILL_WORLD_CENTER_X, 'y' => SKILL_WORLD_CENTER_Y],
        ];

        $studentDomainRowIds = [];
        $domainChildren = [];
        foreach ($chosenDomainIds as $domId) {
            $domainChildren[$domId] = [];
        }

        // ۱. ثبت حوزه‌های متصل به دانشجو
        $stmtInsDom = $pdo->prepare("
            INSERT IGNORE INTO `ug_student_domains` (`id`, `student_id`, `domain_id`, `relation_kind`)
            VALUES (?, ?, ?, 'interest')
        ");
        $stmtInsEdge = $pdo->prepare("
            INSERT IGNORE INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_type_badge`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($chosenDomainIds as $domId) {
            $sdId = 'sd-' . $studentId . '-' . $domId;
            $studentDomainRowIds[$domId] = $sdId;
            $stmtInsDom->execute([$sdId, $studentId, $domId]);

            $domNodeId = 'node-domain-' . $domId;
            $edgeId = 'edge-std-' . $domId . '-' . skills_uuid_short();
            $stmtInsEdge->execute([
                $edgeId,
                $studentId,
                'student',
                $studentNodeId,
                'domain',
                $domNodeId,
                'has_domain',
                'اتصال به حوزه',
                'system',
            ]);
        }

        // ۲. اگر در ثبت‌نام هیچ مهارتی وارد نشده بود، ۲ مهارت پایه بر اساس رشته تحصیلی بساز
        if (empty($regSkills)) {
            $fieldText = trim((string)($student['field_of_study'] ?? 'مهندسی'));
            if (skills_str_contains($fieldText, 'کامپیوتر') || skills_str_contains($fieldText, 'نرم‌افزار')) {
                $regSkills = [
                    ['id' => 'starter-1', 'skill_name' => 'توسعه و طراحی نرم‌افزار'],
                    ['id' => 'starter-2', 'skill_name' => 'کار با پایگاه داده SQL'],
                ];
            } else {
                $regSkills = [
                    ['id' => 'starter-1', 'skill_name' => 'مهارت‌های تخصصی ' . $fieldText],
                    ['id' => 'starter-2', 'skill_name' => 'پژوهش و ارائه علمی'],
                ];
            }
        }

        // ۳. تبدیل مهارت‌های ثبت‌نام به باکس‌های ug_skills + اتصال به حوزه متناظر
        $stmtInsSkill = $pdo->prepare("
            INSERT INTO `ug_skills` (`id`, `student_id`, `title`, `subtitle`, `description`, `category`, `level`, `learning_status`, `source_type`, `verification_status`)
            VALUES (?, ?, ?, ?, ?, 'مهارت ثبت‌نامی', 'intermediate', 'practiced', 'personal', 'unverified')
        ");
        $stmtInsSkillDom = $pdo->prepare("
            INSERT IGNORE INTO `ug_skill_domains` (`skill_id`, `student_domain_id`)
            VALUES (?, ?)
        ");

        foreach ($regSkills as $idx => $sk) {
            $title = trim((string)$sk['skill_name']);
            if ($title === '') {
                continue;
            }
            $skillNodeId = 'node-skill-reg-' . ($idx + 1) . '-' . skills_uuid_short();
            $targetDomId = skills_infer_domain_id($title, $chosenDomainIds);
            $domNodeId = 'node-domain-' . $targetDomId;

            $stmtInsSkill->execute([
                $skillNodeId,
                $studentId,
                $title,
                'مهارت ثبت‌شده در نام‌نویسی صمد',
                'این مهارت هنگام ثبت‌نام دانشجویی در سامانه صمد ثبت شده است.',
            ]);

            if (isset($studentDomainRowIds[$targetDomId])) {
                $stmtInsSkillDom->execute([$skillNodeId, $studentDomainRowIds[$targetDomId]]);
            }

            $edgeId = 'edge-sk-' . skills_uuid_short();
            $stmtInsEdge->execute([
                $edgeId,
                $studentId,
                'skill',
                $skillNodeId,
                'domain',
                $domNodeId,
                'belongs_to',
                'زیرمجموعه حوزه',
                'personal',
            ]);

            $domainChildren[$targetDomId][] = $skillNodeId;
        }

        // ۴. تبدیل جشنواره‌های ثبت‌نام به باکس‌های رویداد مرجع (ug_achievements با نوع event)
        $stmtInsAch = $pdo->prepare("
            INSERT INTO `ug_achievements` (`id`, `student_id`, `title`, `subtitle`, `description`, `achievement_type`, `domain_id`, `source_type`, `source_system`, `source_record_id`, `verification_status`, `achieved_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'reference', 'samad_registration', ?, 'verified', 'ثبت‌نام صمد')
        ");

        foreach ($regFestivals as $idx => $fest) {
            $fTitle = trim((string)$fest['festival_title']);
            if ($fTitle === '') {
                continue;
            }
            $evtNodeId = 'node-evt-reg-' . ($idx + 1) . '-' . skills_uuid_short();
            $mappedDom = $festivalToDomain[$fest['festival_key']] ?? ($chosenDomainIds[0] ?? 'domain-tech');
            if (!in_array($mappedDom, $chosenDomainIds, true)) {
                $mappedDom = $chosenDomainIds[0] ?? 'domain-tech';
            }

            $stmtInsAch->execute([
                $evtNodeId,
                $studentId,
                $fTitle,
                'جشنواره ثبت‌نام‌شده در سامانه صمد',
                'علاقه‌مندی و مشارکت انتخاب‌شده در فرم ثبت‌نام دانشگاهی صمد',
                'event',
                $mappedDom,
                'fest-' . ($fest['id'] ?? $idx),
            ]);

            $domNodeId = 'node-domain-' . $mappedDom;
            $edgeId = 'edge-evt-' . skills_uuid_short();
            $stmtInsEdge->execute([
                $edgeId,
                $studentId,
                'domain',
                $domNodeId,
                'event',
                $evtNodeId,
                'participated_in',
                'مشارکت در رویداد',
                'reference',
            ]);

            $domainChildren[$mappedDom][] = $evtNodeId;
        }

        // ۵. تبدیل تیم‌های ورزشی منتخب دانشجو به باکس‌های دستاورد/عضویت ورزشی
        foreach ($regTeams as $idx => $tm) {
            $tName = trim((string)$tm['team_name']);
            if ($tName === '') {
                continue;
            }
            $achNodeId = 'node-ach-sport-' . ($idx + 1) . '-' . skills_uuid_short();
            $mappedDom = in_array('domain-sport', $chosenDomainIds, true) ? 'domain-sport' : ($chosenDomainIds[0] ?? 'domain-tech');

            $stmtInsAch->execute([
                $achNodeId,
                $studentId,
                'عضو ' . $tName,
                'تیم ورزشی منتخب دانشگاه ملی مهارت',
                'عضویت رسمی ثبت‌شده در تیم ورزشی سامانه صمد',
                'achievement',
                $mappedDom,
                'sport-' . ($tm['id'] ?? $idx),
            ]);

            $domNodeId = 'node-domain-' . $mappedDom;
            $edgeId = 'edge-tm-' . skills_uuid_short();
            $stmtInsEdge->execute([
                $edgeId,
                $studentId,
                'student',
                $studentNodeId,
                'achievement',
                $achNodeId,
                'has_achievement',
                'عضویت رسمی',
                'reference',
            ]);

            $domainChildren[$mappedDom][] = $achNodeId;
        }

        // ۶. محاسبه چیدمان شعاعی بدون تداخل (Ring 1 حوزه‌ها، Ring 2 مهارت‌ها و رویدادها)
        $domCount = max(1, count($chosenDomainIds));
        $ring1Rx = 295;
        $ring1Ry = 220;
        $ring2Rx = 570;
        $ring2Ry = 415;

        foreach ($chosenDomainIds as $dIdx => $domId) {
            $baseAngle = (-M_PI / 2) + ((2 * M_PI * $dIdx) / $domCount);
            $domNodeId = 'node-domain-' . $domId;

            $positions[$domNodeId] = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + $ring1Rx * cos($baseAngle)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + $ring1Ry * sin($baseAngle)),
            ];

            $children = $domainChildren[$domId] ?? [];
            $childCount = count($children);
            if ($childCount > 0) {
                $spread = min(0.85, 0.28 * max(1, $childCount - 1));
                foreach ($children as $cIdx => $childNodeId) {
                    $offsetRatio = $childCount === 1 ? 0.0 : (($cIdx / ($childCount - 1)) - 0.5);
                    $childAngle = $baseAngle + ($offsetRatio * $spread);
                    $ringBump = ($cIdx % 2 === 1 && $childCount > 2) ? 1.15 : 1.0;

                    $positions[$childNodeId] = [
                        'x' => (int)round(SKILL_WORLD_CENTER_X + ($ring2Rx * $ringBump) * cos($childAngle)),
                        'y' => (int)round(SKILL_WORLD_CENTER_Y + ($ring2Ry * $ringBump) * sin($childAngle)),
                    ];
                }
            }
        }

        $resolvedPositions = skills_resolve_layout_overlaps($positions, $studentNodeId, 20);
        skills_save_layout($pdo, $studentId, $resolvedPositions, false);
        skills_audit($pdo, $studentId, 'seed_from_registration', 'graph', $studentId, null, [
            'domains' => count($chosenDomainIds),
            'skills' => count($regSkills),
            'festivals' => count($regFestivals),
            'teams' => count($regTeams),
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * ساخت خروجی استاندارد StudentSkillGraphResponseDto از جداول ug_*
 */
function samad_build_student_skill_graph(PDO $pdo, string $studentId): array
{
    $stmtSt = $pdo->prepare("
        SELECT `student_id`, `full_name`, `campus`, `field_of_study`, `study_shift`
        FROM `students`
        WHERE `student_id` = ?
        LIMIT 1
    ");
    $stmtSt->execute([$studentId]);
    $student = $stmtSt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        skills_send_json(404, [
            'errorCode' => 'STUDENT_NOT_FOUND',
            'message' => 'پرونده دانشجویی در سامانه یافت نشد.',
        ]);
    }

    // بررسی اینکه آیا دانشجو قبلاً گراف مهارتی داشته یا اولین بار است
    $stmtCount = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM `ug_student_domains` WHERE `student_id` = ?) +
            (SELECT COUNT(*) FROM `ug_skills` WHERE `student_id` = ?) +
            (SELECT COUNT(*) FROM `ug_achievements` WHERE `student_id` = ?) +
            (SELECT COUNT(*) FROM `ug_graph_layouts` WHERE `student_id` = ? AND `graph_type` = 'student_skills') AS total_rows
    ");
    $stmtCount->execute([$studentId, $studentId, $studentId, $studentId]);
    $totalRows = (int)($stmtCount->fetchColumn() ?: 0);

    if ($totalRows === 0) {
        samad_seed_student_skill_graph_from_registration($pdo, $studentId);
    }

    $layout = skills_load_layout($pdo, $studentId);
    $savedPositions = $layout['positions'];
    $layoutDirty = false;

    $nodes = [];
    $studentNodeId = 'node-student-' . $studentId;

    $studentPos = $savedPositions[$studentNodeId] ?? ['x' => SKILL_WORLD_CENTER_X, 'y' => SKILL_WORLD_CENTER_Y];
    if (!isset($savedPositions[$studentNodeId])) {
        $savedPositions[$studentNodeId] = $studentPos;
        $layoutDirty = true;
    }

    // ۱. گره مرکزی دانشجو
    $nodes[] = [
        'id' => $studentNodeId,
        'nodeType' => 'student',
        'title' => $student['full_name'],
        'subtitle' => ($student['field_of_study'] ?? '') . ' · ' . ($student['campus'] ?? ''),
        'category' => 'پروفایل دانشجو',
        'sourceType' => 'system',
        'notes' => 'دانشکده/پردیس: ' . ($student['campus'] ?? '-') . ' | نوبت: ' . ($student['study_shift'] ?? '-'),
        'isCustom' => false,
        'position' => $studentPos,
    ];

    // ۲. گره‌های حوزه‌های متصل دانشجو
    $stmtDom = $pdo->prepare("
        SELECT sd.*, d.title AS sys_title, d.description AS sys_desc
        FROM `ug_student_domains` sd
        LEFT JOIN `ug_domains` d ON sd.domain_id = d.id
        WHERE sd.student_id = ?
        ORDER BY sd.created_at ASC
    ");
    $stmtDom->execute([$studentId]);
    $domRows = $stmtDom->fetchAll(PDO::FETCH_ASSOC);

    foreach ($domRows as $idx => $dr) {
        $isPersonalDomain = !empty($dr['custom_title']) && empty($dr['sys_title']);
        $nodeId = 'node-domain-' . $dr['domain_id'];
        $title = $isPersonalDomain ? $dr['custom_title'] : ($dr['sys_title'] ?: $dr['domain_id']);
        $desc = $dr['description'] ?: ($dr['sys_desc'] ?: 'حوزه فعالیت دانشگاهی');

        if (!isset($savedPositions[$nodeId])) {
            $ang = (-M_PI / 2) + ((2 * M_PI * $idx) / max(1, count($domRows)));
            $savedPositions[$nodeId] = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + 295 * cos($ang)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + 220 * sin($ang)),
            ];
            $layoutDirty = true;
        }

        $nodes[] = [
            'id' => $nodeId,
            'nodeType' => 'domain',
            'title' => $title,
            'subtitle' => $isPersonalDomain ? 'حوزه شخصی' : 'حوزه رسمی دانشگاه',
            'domainId' => $dr['domain_id'],
            'domainTitle' => $title,
            'category' => $isPersonalDomain ? 'حوزه شخصی' : 'حوزه رسمی',
            'sourceType' => $isPersonalDomain ? 'personal' : 'system',
            'notes' => $desc,
            'isCustom' => $isPersonalDomain,
            'position' => $savedPositions[$nodeId],
            'createdAt' => $dr['created_at'] ?? null,
        ];
    }

    // ۳. گره‌های مهارت‌ها و علاقه‌مندی‌ها
    $stmtSk = $pdo->prepare("
        SELECT s.*,
               (SELECT sd.domain_id FROM `ug_skill_domains` skd
                JOIN `ug_student_domains` sd ON skd.student_domain_id = sd.id
                WHERE skd.skill_id = s.id LIMIT 1) AS linked_domain_id
        FROM `ug_skills` s
        WHERE s.student_id = ?
        ORDER BY s.created_at ASC
    ");
    $stmtSk->execute([$studentId]);
    $skRows = $stmtSk->fetchAll(PDO::FETCH_ASSOC);

    foreach ($skRows as $idx => $sr) {
        $nodeId = $sr['id'];
        if (!isset($savedPositions[$nodeId])) {
            $ang = 0.4 + ($idx * 0.65);
            $savedPositions[$nodeId] = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + 540 * cos($ang)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + 390 * sin($ang)),
            ];
            $layoutDirty = true;
        }
        $nType = ($sr['category'] === 'علاقه شخصی') ? 'interest' : 'skill';
        $nodes[] = [
            'id' => $nodeId,
            'nodeType' => $nType,
            'title' => $sr['title'],
            'subtitle' => $sr['subtitle'] ?: '',
            'domainId' => $sr['linked_domain_id'] ?: null,
            'category' => $sr['category'] ?: 'مهارت شخصی',
            'sourceType' => $sr['source_type'] ?: 'personal',
            'verificationStatus' => $sr['verification_status'] ?: 'unverified',
            'proficiencyLevel' => $sr['level'] ?: 'intermediate',
            'numericLevel' => skills_level_to_numeric($sr['level'] ?: 'intermediate'),
            'learningStatus' => $sr['learning_status'] ?: 'learning',
            'notes' => $sr['description'] ?: ($sr['subtitle'] ?: ''),
            'isCustom' => ($sr['source_type'] === 'personal'),
            'position' => $savedPositions[$nodeId],
            'createdAt' => $sr['created_at'] ?? null,
        ];
    }

    // ۴. گره‌های دستاوردها و رویدادها / جشنواره‌ها
    $stmtAch = $pdo->prepare("
        SELECT * FROM `ug_achievements`
        WHERE `student_id` = ?
        ORDER BY `created_at` ASC
    ");
    $stmtAch->execute([$studentId]);
    $achRows = $stmtAch->fetchAll(PDO::FETCH_ASSOC);

    foreach ($achRows as $idx => $ar) {
        $nodeId = $ar['id'];
        if (!isset($savedPositions[$nodeId])) {
            $ang = 2.2 + ($idx * 0.7);
            $savedPositions[$nodeId] = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + 560 * cos($ang)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + 410 * sin($ang)),
            ];
            $layoutDirty = true;
        }
        $nType = ($ar['achievement_type'] === 'event') ? 'event' : 'achievement';
        $nodes[] = [
            'id' => $nodeId,
            'nodeType' => $nType,
            'title' => $ar['title'],
            'subtitle' => $ar['subtitle'] ?: '',
            'domainId' => $ar['domain_id'] ?: null,
            'category' => $nType === 'event' ? 'رویداد و جشنواره' : 'دستاورد و افتخار',
            'sourceType' => $ar['source_type'] ?: 'personal',
            'verificationStatus' => $ar['verification_status'] ?: 'unverified',
            'notes' => $ar['description'] ?: ($ar['subtitle'] ?: ''),
            'isCustom' => ($ar['source_type'] === 'personal'),
            'position' => $savedPositions[$nodeId],
            'createdAt' => $ar['created_at'] ?? null,
        ];
    }

    if ($layoutDirty) {
        $savedPositions = skills_resolve_layout_overlaps($savedPositions, $studentNodeId, 10);
        skills_save_layout($pdo, $studentId, $savedPositions, false);
        foreach ($nodes as &$nRef) {
            if (isset($savedPositions[$nRef['id']])) {
                $nRef['position'] = $savedPositions[$nRef['id']];
            }
        }
        unset($nRef);
    }

    // ۵. یال‌های گراف
    $validNodeSet = [];
    foreach ($nodes as $nItem) {
        $validNodeSet[$nItem['id']] = true;
    }

    $stmtEdges = $pdo->prepare("
        SELECT * FROM `ug_graph_edges`
        WHERE `student_id` = ?
        ORDER BY `created_at` ASC
    ");
    $stmtEdges->execute([$studentId]);
    $edgeRows = $stmtEdges->fetchAll(PDO::FETCH_ASSOC);

    $edges = [];
    foreach ($edgeRows as $er) {
        if (!isset($validNodeSet[$er['source_id']]) || !isset($validNodeSet[$er['target_id']])) {
            continue;
        }
        $edgeDto = [
            'id' => $er['id'],
            'source' => $er['source_id'],
            'target' => $er['target_id'],
            'relationType' => $er['edge_type'],
            'label' => $er['label'],
            'sourceType' => $er['source_type_badge'] ?: 'personal',
        ];
        if (!empty($er['source_handle'])) {
            $edgeDto['sourceHandle'] = $er['source_handle'];
        }
        if (!empty($er['target_handle'])) {
            $edgeDto['targetHandle'] = $er['target_handle'];
        }
        $edges[] = $edgeDto;
    }

    return [
        'graphId' => 'skills-graph-' . $studentId,
        'apiVersion' => 'v2',
        'version' => 2,
        'module' => 'student_skills',
        'studentId' => $studentId,
        'studentDisplayName' => $student['full_name'],
        'major' => $student['field_of_study'] ?? 'رشته تحصیلی',
        'faculty' => $student['campus'] ?? 'پردیس دانشگاه',
        'entryYear' => skills_derive_entry_year($studentId),
        'nodes' => $nodes,
        'edges' => $edges,
        'meta' => [
            'generatedAt' => gmdate('c'),
            'layoutVersion' => $layout['version'],
        ],
        'updatedAt' => $layout['updated_at'],
    ];
}

// =========================================================================
// مسیریابی اکشن‌های API گراف مهارت‌ها
// =========================================================================
function skills_check_limit_or_429(PDO $pdo, array $jwtAuth, string $actionKey): void
{
    $studentId = (string)($jwtAuth['sub'] ?? '');
    $limRes = samad_check_student_rate_limit($pdo, $studentId, $actionKey);
    if (!$limRes['allowed']) {
        samad_log_rate_limit_exceeded($pdo, $studentId, $jwtAuth['jti'] ?? null, 'get_skills_graph.php?action=' . $actionKey, $limRes);
        skills_send_json(429, [
            'errorCode' => 'RATE_LIMIT_EXCEEDED',
            'message' => $limRes['message'],
            'rateLimit' => $limRes,
        ]);
    }
}

$action = trim((string)($_GET['action'] ?? 'me'));
if ($action === '') {
    $action = 'me';
}

try {
    skills_ensure_schema($pdo);

    // ۱. دریافت گراف دانشجو
    if ($action === 'me') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:view_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:view_self');
        $graphPayload = samad_build_student_skill_graph($pdo, $studentId);
        samad_record_student_action($pdo, $studentId, 'skills:graph:view_self', 'مشاهده گراف مهارت‌ها');
        skills_send_json(200, $graphPayload);
    }

    // ۲. فهرست حوزه‌های رسمی فعال دانشگاه
    if ($action === 'domains') {
        samad_require_api_jwt($pdo, ['student'], 'skills:graph:view_self');
        $stmt = $pdo->query("
            SELECT `id`, `title`, `slug`, `description`, `icon`, `color`, `is_active`
            FROM `ug_domains`
            WHERE `is_active` = 1
            ORDER BY `sort_order` ASC
        ");
        $list = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $list[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'description' => $row['description'] ?? '',
                'icon' => $row['icon'] ?: 'book',
                'color' => $row['color'] ?: '#589bf6',
                'isActive' => ((int)$row['is_active']) === 1,
            ];
        }
        skills_send_json(200, $list);
    }

    // ۳. اتصال یک حوزه رسمی به نقشه دانشجو
    if ($action === 'attach_domain') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:attach_domain');
        $body = skills_read_json_body();
        $domainId = trim((string)($body['domainId'] ?? $_POST['domainId'] ?? ''));
        $relationKind = trim((string)($body['relationKind'] ?? 'interest'));

        if ($domainId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_DOMAIN', 'message' => 'شناسه حوزه الزامی است.']);
        }

        $stmtDom = $pdo->prepare("SELECT * FROM `ug_domains` WHERE `id` = ? AND `is_active` = 1 LIMIT 1");
        $stmtDom->execute([$domainId]);
        $dom = $stmtDom->fetch(PDO::FETCH_ASSOC);
        if (!$dom) {
            skills_send_json(404, ['errorCode' => 'DOMAIN_NOT_FOUND', 'message' => 'حوزه انتخاب‌شده در فهرست رسمی یافت نشد.']);
        }

        $sdId = 'sd-' . $studentId . '-' . $domainId;
        $stmtIns = $pdo->prepare("
            INSERT IGNORE INTO `ug_student_domains` (`id`, `student_id`, `domain_id`, `relation_kind`)
            VALUES (?, ?, ?, ?)
        ");
        $stmtIns->execute([$sdId, $studentId, $domainId, $relationKind ?: 'interest']);

        $studentNodeId = 'node-student-' . $studentId;
        $domNodeId = 'node-domain-' . $domainId;

        $stmtEdgeChk = $pdo->prepare("
            SELECT `id` FROM `ug_graph_edges`
            WHERE `student_id` = ? AND `source_id` = ? AND `target_id` = ?
            LIMIT 1
        ");
        $stmtEdgeChk->execute([$studentId, $studentNodeId, $domNodeId]);
        if (!$stmtEdgeChk->fetchColumn()) {
            $stmtEdge = $pdo->prepare("
                INSERT INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_type_badge`)
                VALUES (?, ?, 'student', ?, 'domain', ?, 'has_domain', 'اتصال به حوزه', 'system')
            ");
            $stmtEdge->execute(['edge-std-' . $domainId . '-' . skills_uuid_short(), $studentId, $studentNodeId, $domNodeId]);
        }

        $layout = skills_load_layout($pdo, $studentId);
        $positions = $layout['positions'];
        if (!isset($positions[$domNodeId])) {
            $angle = (count($positions) % 8) * (M_PI / 4) - (M_PI / 2);
            $positions[$domNodeId] = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + 295 * cos($angle)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + 220 * sin($angle)),
            ];
            $positions = skills_resolve_layout_overlaps($positions, $studentNodeId, 12);
            skills_save_layout($pdo, $studentId, $positions, true);
        }

        skills_audit($pdo, $studentId, 'attach_domain', 'domain', $domainId, null, ['domainId' => $domainId]);
        samad_record_student_action($pdo, $studentId, 'skills:graph:attach_domain', 'حوزه: ' . $domainId);

        skills_send_json(201, [
            'id' => $domNodeId,
            'nodeType' => 'domain',
            'title' => $dom['title'],
            'subtitle' => 'حوزه رسمی دانشگاه',
            'domainId' => $dom['id'],
            'domainTitle' => $dom['title'],
            'category' => 'حوزه رسمی',
            'sourceType' => 'system',
            'notes' => $dom['description'] ?? '',
            'isCustom' => false,
            'position' => $positions[$domNodeId],
        ]);
    }

    // ۴. جدا کردن یک حوزه از نقشه دانشجو
    if ($action === 'detach_domain') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:detach_domain');
        $body = skills_read_json_body();
        $domainId = trim((string)($_GET['domain_id'] ?? $body['domainId'] ?? ''));
        if (strpos($domainId, 'node-domain-') === 0) {
            $domainId = substr($domainId, strlen('node-domain-'));
        }

        if ($domainId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_DOMAIN', 'message' => 'شناسه حوزه مشخص نشده است.']);
        }

        $sdId = 'sd-' . $studentId . '-' . $domainId;
        $domNodeId = 'node-domain-' . $domainId;

        $pdo->prepare("DELETE FROM `ug_skill_domains` WHERE `student_domain_id` = ?")->execute([$sdId]);
        $pdo->prepare("DELETE FROM `ug_student_domains` WHERE `student_id` = ? AND `domain_id` = ?")->execute([$studentId, $domainId]);
        $pdo->prepare("DELETE FROM `ug_graph_edges` WHERE `student_id` = ? AND (`source_id` = ? OR `target_id` = ?)")->execute([$studentId, $domNodeId, $domNodeId]);

        $layout = skills_load_layout($pdo, $studentId);
        $positions = $layout['positions'];
        if (isset($positions[$domNodeId])) {
            unset($positions[$domNodeId]);
            skills_save_layout($pdo, $studentId, $positions, true);
        }

        skills_audit($pdo, $studentId, 'detach_domain', 'domain', $domainId);
        samad_record_student_action($pdo, $studentId, 'skills:graph:detach_domain', 'حوزه: ' . $domainId);
        skills_send_json(200, ['success' => true]);
    }

    // ۵. ساخت باکس جدید
    if ($action === 'create_node') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:create_node');
        $body = skills_read_json_body();

        $title = trim((string)($body['title'] ?? ''));
        if ($title === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_TITLE', 'message' => 'عنوان باکس نمی‌تواند خالی باشد.']);
        }

        $allowedNodeTypes = ['skill', 'interest', 'domain', 'achievement', 'event'];
        $nodeType = in_array(($body['nodeType'] ?? 'skill'), $allowedNodeTypes, true) ? (string)$body['nodeType'] : 'skill';
        $notes = trim((string)($body['notes'] ?? ''));
        $subtitle = trim((string)($body['subtitle'] ?? $notes));
        $domainId = isset($body['domainId']) && trim((string)$body['domainId']) !== '' && $body['domainId'] !== 'none'
            ? trim((string)$body['domainId'])
            : null;
        $autoConnect = !empty($body['autoConnect']);
        $proficiency = skills_normalize_proficiency($body['proficiencyLevel'] ?? 'intermediate', $body['numericLevel'] ?? null);
        $learningStatus = skills_normalize_learning_status($body['learningStatus'] ?? 'learning');

        $layout = skills_load_layout($pdo, $studentId);
        $positions = $layout['positions'];

        if (isset($body['position']['x'], $body['position']['y']) && is_numeric($body['position']['x']) && is_numeric($body['position']['y'])) {
            $pos = ['x' => (int)round((float)$body['position']['x']), 'y' => (int)round((float)$body['position']['y'])];
        } else {
            $ang = (count($positions) * 0.72);
            $pos = [
                'x' => (int)round(SKILL_WORLD_CENTER_X + 430 * cos($ang)),
                'y' => (int)round(SKILL_WORLD_CENTER_Y + 320 * sin($ang)),
            ];
        }

        $studentNodeId = 'node-student-' . $studentId;
        $createdDto = null;

        if ($nodeType === 'domain') {
            $customDomKey = 'custom-' . skills_uuid_short();
            $sdId = 'sd-' . $studentId . '-' . $customDomKey;
            $nodeId = 'node-domain-' . $customDomKey;

            $stmt = $pdo->prepare("
                INSERT INTO `ug_student_domains` (`id`, `student_id`, `domain_id`, `relation_kind`, `custom_title`, `description`)
                VALUES (?, ?, ?, 'interest', ?, ?)
            ");
            $stmt->execute([$sdId, $studentId, $customDomKey, $title, $notes]);

            if ($autoConnect) {
                $pdo->prepare("
                    INSERT INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_type_badge`)
                    VALUES (?, ?, 'student', ?, 'domain', ?, 'has_domain', 'حوزه شخصی', 'personal')
                ")->execute(['edge-cd-' . skills_uuid_short(), $studentId, $studentNodeId, $nodeId]);
            }

            $createdDto = [
                'id' => $nodeId,
                'nodeType' => 'domain',
                'title' => $title,
                'subtitle' => $subtitle ?: 'حوزه شخصی',
                'domainId' => $customDomKey,
                'domainTitle' => $title,
                'category' => 'حوزه شخصی',
                'sourceType' => 'personal',
                'notes' => $notes,
                'isCustom' => true,
                'position' => $pos,
            ];
        } elseif ($nodeType === 'achievement' || $nodeType === 'event') {
            $prefix = $nodeType === 'event' ? 'node-evt-' : 'node-ach-';
            $nodeId = $prefix . skills_uuid_short();

            $stmt = $pdo->prepare("
                INSERT INTO `ug_achievements` (`id`, `student_id`, `title`, `subtitle`, `description`, `achievement_type`, `domain_id`, `source_type`, `verification_status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'personal', 'unverified')
            ");
            $stmt->execute([$nodeId, $studentId, $title, $subtitle, $notes, $nodeType, $domainId]);

            if ($autoConnect && $domainId) {
                $domNodeId = 'node-domain-' . $domainId;
                $edgeType = $nodeType === 'event' ? 'participated_in' : 'has_achievement';
                $edgeLabel = $nodeType === 'event' ? 'مشارکت در رویداد' : 'دستاورد مرتبط';
                $pdo->prepare("
                    INSERT INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_type_badge`)
                    VALUES (?, ?, 'domain', ?, ?, ?, ?, ?, 'personal')
                ")->execute(['edge-ac-' . skills_uuid_short(), $studentId, $domNodeId, $nodeType, $nodeId, $edgeType, $edgeLabel]);
            }

            $createdDto = [
                'id' => $nodeId,
                'nodeType' => $nodeType,
                'title' => $title,
                'subtitle' => $subtitle,
                'domainId' => $domainId,
                'category' => $nodeType === 'event' ? 'رویداد و جشنواره' : 'دستاورد و افتخار',
                'sourceType' => 'personal',
                'verificationStatus' => 'unverified',
                'notes' => $notes,
                'isCustom' => true,
                'position' => $pos,
            ];
        } else {
            $nodeId = 'node-skill-' . skills_uuid_short();
            $catLabel = $nodeType === 'interest' ? 'علاقه شخصی' : ($body['category'] ?? 'مهارت شخصی');

            $stmt = $pdo->prepare("
                INSERT INTO `ug_skills` (`id`, `student_id`, `title`, `subtitle`, `description`, `category`, `level`, `learning_status`, `source_type`, `verification_status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'personal', 'unverified')
            ");
            $stmt->execute([$nodeId, $studentId, $title, $subtitle, $notes, $catLabel, $proficiency, $learningStatus]);

            if ($domainId) {
                $sdId = 'sd-' . $studentId . '-' . $domainId;
                $stmtChkSd = $pdo->prepare("SELECT `id` FROM `ug_student_domains` WHERE `id` = ? LIMIT 1");
                $stmtChkSd->execute([$sdId]);
                if ($stmtChkSd->fetchColumn()) {
                    $pdo->prepare("INSERT IGNORE INTO `ug_skill_domains` (`skill_id`, `student_domain_id`) VALUES (?, ?)")->execute([$nodeId, $sdId]);
                }
                if ($autoConnect) {
                    $domNodeId = 'node-domain-' . $domainId;
                    $pdo->prepare("
                        INSERT INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_type_badge`)
                        VALUES (?, ?, 'skill', ?, 'domain', ?, 'belongs_to', 'زیرمجموعه حوزه', 'personal')
                    ")->execute(['edge-sk-' . skills_uuid_short(), $studentId, $nodeId, $domNodeId]);
                }
            }

            $createdDto = [
                'id' => $nodeId,
                'nodeType' => $nodeType,
                'title' => $title,
                'subtitle' => $subtitle,
                'domainId' => $domainId,
                'category' => $catLabel,
                'sourceType' => 'personal',
                'verificationStatus' => 'unverified',
                'proficiencyLevel' => $proficiency,
                'numericLevel' => skills_level_to_numeric($proficiency),
                'learningStatus' => $learningStatus,
                'notes' => $notes,
                'isCustom' => true,
                'position' => $pos,
            ];
        }

        $positions[$createdDto['id']] = $pos;
        $positions = skills_resolve_layout_overlaps($positions, $createdDto['id'], 10);
        skills_save_layout($pdo, $studentId, $positions, true);
        $createdDto['position'] = $positions[$createdDto['id']];

        skills_audit($pdo, $studentId, 'create_node', $nodeType, $createdDto['id'], null, $createdDto);
        samad_record_student_action($pdo, $studentId, 'skills:graph:create_node', 'گره: ' . $createdDto['id']);
        skills_send_json(201, $createdDto);
    }

    // ۶. ویرایش باکس
    if ($action === 'update_node') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:update_node');
        $body = skills_read_json_body();
        $nodeId = trim((string)($_GET['node_id'] ?? $body['nodeId'] ?? $body['id'] ?? ''));

        if ($nodeId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_NODE', 'message' => 'شناسه باکس الزامی است.']);
        }
        if ($nodeId === 'node-student-' . $studentId) {
            skills_send_json(403, ['errorCode' => 'READ_ONLY_NODE', 'message' => 'گره رسمی دانشجو قابل ویرایش نیست.']);
        }

        $layout = skills_load_layout($pdo, $studentId);
        $pos = $layout['positions'][$nodeId] ?? ['x' => SKILL_WORLD_CENTER_X, 'y' => SKILL_WORLD_CENTER_Y];

        // بررسی در جدول مهارت‌ها
        $stmtSk = $pdo->prepare("SELECT * FROM `ug_skills` WHERE `id` = ? AND `student_id` = ? LIMIT 1");
        $stmtSk->execute([$nodeId, $studentId]);
        $skillRow = $stmtSk->fetch(PDO::FETCH_ASSOC);

        if ($skillRow) {
            if ($skillRow['source_type'] === 'system') {
                skills_send_json(403, ['errorCode' => 'READ_ONLY_NODE', 'message' => 'این گره سیستمی قابل ویرایش نیست.']);
            }
            $newTitle = isset($body['title']) && trim((string)$body['title']) !== '' ? trim((string)$body['title']) : $skillRow['title'];
            $newNotes = array_key_exists('notes', $body) ? trim((string)$body['notes']) : ($skillRow['description'] ?? '');
            $newSub = array_key_exists('subtitle', $body) ? trim((string)$body['subtitle']) : $newNotes;
            $newLevel = isset($body['proficiencyLevel'])
                ? skills_normalize_proficiency($body['proficiencyLevel'], $body['numericLevel'] ?? null)
                : ($skillRow['level'] ?: 'intermediate');
            $newLearn = isset($body['learningStatus'])
                ? skills_normalize_learning_status($body['learningStatus'])
                : ($skillRow['learning_status'] ?: 'learning');
            $reqType = $body['nodeType'] ?? null;
            $newCat = $reqType === 'interest' ? 'علاقه شخصی' : ($reqType === 'skill' ? 'مهارت شخصی' : ($skillRow['category'] ?: 'مهارت شخصی'));

            // اگر کاربر نوع باکس را از مهارت به دستاورد/رویداد تغییر داده باشد
            if ($reqType === 'achievement' || $reqType === 'event') {
                $pdo->prepare("
                    INSERT INTO `ug_achievements` (`id`, `student_id`, `title`, `subtitle`, `description`, `achievement_type`, `source_type`, `verification_status`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$nodeId, $studentId, $newTitle, $newSub, $newNotes, $reqType, $skillRow['source_type'], $skillRow['verification_status']]);
                $pdo->prepare("DELETE FROM `ug_skill_domains` WHERE `skill_id` = ?")->execute([$nodeId]);
                $pdo->prepare("DELETE FROM `ug_skills` WHERE `id` = ? AND `student_id` = ?")->execute([$nodeId, $studentId]);

                samad_record_student_action($pdo, $studentId, 'skills:graph:update_node', 'ویرایش باکس: ' . $nodeId);
                skills_send_json(200, [
                    'id' => $nodeId,
                    'nodeType' => $reqType,
                    'title' => $newTitle,
                    'subtitle' => $newSub,
                    'category' => $reqType === 'event' ? 'رویداد و جشنواره' : 'دستاورد و افتخار',
                    'sourceType' => $skillRow['source_type'],
                    'verificationStatus' => $skillRow['verification_status'],
                    'notes' => $newNotes,
                    'isCustom' => true,
                    'position' => $pos,
                ]);
            }

            $pdo->prepare("
                UPDATE `ug_skills`
                SET `title` = ?, `subtitle` = ?, `description` = ?, `category` = ?, `level` = ?, `learning_status` = ?
                WHERE `id` = ? AND `student_id` = ?
            ")->execute([$newTitle, $newSub, $newNotes, $newCat, $newLevel, $newLearn, $nodeId, $studentId]);

            $resType = ($newCat === 'علاقه شخصی') ? 'interest' : 'skill';
            samad_record_student_action($pdo, $studentId, 'skills:graph:update_node', 'ویرایش باکس: ' . $nodeId);
            skills_send_json(200, [
                'id' => $nodeId,
                'nodeType' => $resType,
                'title' => $newTitle,
                'subtitle' => $newSub,
                'category' => $newCat,
                'sourceType' => $skillRow['source_type'],
                'verificationStatus' => $skillRow['verification_status'],
                'proficiencyLevel' => $newLevel,
                'numericLevel' => skills_level_to_numeric($newLevel),
                'learningStatus' => $newLearn,
                'notes' => $newNotes,
                'isCustom' => $skillRow['source_type'] === 'personal',
                'position' => $pos,
            ]);
        }

        // بررسی در جدول دستاوردها و رویدادها
        $stmtAch = $pdo->prepare("SELECT * FROM `ug_achievements` WHERE `id` = ? AND `student_id` = ? LIMIT 1");
        $stmtAch->execute([$nodeId, $studentId]);
        $achRow = $stmtAch->fetch(PDO::FETCH_ASSOC);

        if ($achRow) {
            if ($achRow['source_type'] === 'system') {
                skills_send_json(403, ['errorCode' => 'READ_ONLY_NODE', 'message' => 'این گره رسمی قابل ویرایش نیست.']);
            }
            $newTitle = isset($body['title']) && trim((string)$body['title']) !== '' ? trim((string)$body['title']) : $achRow['title'];
            $newNotes = array_key_exists('notes', $body) ? trim((string)$body['notes']) : ($achRow['description'] ?? '');
            $newSub = array_key_exists('subtitle', $body) ? trim((string)$body['subtitle']) : $newNotes;
            $reqType = $body['nodeType'] ?? $achRow['achievement_type'];

            if ($reqType === 'skill' || $reqType === 'interest') {
                $newLevel = skills_normalize_proficiency($body['proficiencyLevel'] ?? 'intermediate', $body['numericLevel'] ?? null);
                $newLearn = skills_normalize_learning_status($body['learningStatus'] ?? 'learning');
                $newCat = $reqType === 'interest' ? 'علاقه شخصی' : 'مهارت شخصی';
                $pdo->prepare("
                    INSERT INTO `ug_skills` (`id`, `student_id`, `title`, `subtitle`, `description`, `category`, `level`, `learning_status`, `source_type`, `verification_status`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$nodeId, $studentId, $newTitle, $newSub, $newNotes, $newCat, $newLevel, $newLearn, $achRow['source_type'], $achRow['verification_status']]);
                $pdo->prepare("DELETE FROM `ug_achievements` WHERE `id` = ? AND `student_id` = ?")->execute([$nodeId, $studentId]);

                samad_record_student_action($pdo, $studentId, 'skills:graph:update_node', 'ویرایش باکس: ' . $nodeId);
                skills_send_json(200, [
                    'id' => $nodeId,
                    'nodeType' => $reqType,
                    'title' => $newTitle,
                    'subtitle' => $newSub,
                    'category' => $newCat,
                    'sourceType' => $achRow['source_type'],
                    'verificationStatus' => $achRow['verification_status'],
                    'proficiencyLevel' => $newLevel,
                    'numericLevel' => skills_level_to_numeric($newLevel),
                    'learningStatus' => $newLearn,
                    'notes' => $newNotes,
                    'isCustom' => true,
                    'position' => $pos,
                ]);
            }

            $achType = ($reqType === 'event') ? 'event' : 'achievement';
            $pdo->prepare("
                UPDATE `ug_achievements`
                SET `title` = ?, `subtitle` = ?, `description` = ?, `achievement_type` = ?
                WHERE `id` = ? AND `student_id` = ?
            ")->execute([$newTitle, $newSub, $newNotes, $achType, $nodeId, $studentId]);

            samad_record_student_action($pdo, $studentId, 'skills:graph:update_node', 'ویرایش باکس: ' . $nodeId);
            skills_send_json(200, [
                'id' => $nodeId,
                'nodeType' => $achType,
                'title' => $newTitle,
                'subtitle' => $newSub,
                'domainId' => $achRow['domain_id'],
                'category' => $achType === 'event' ? 'رویداد و جشنواره' : 'دستاورد و افتخار',
                'sourceType' => $achRow['source_type'],
                'verificationStatus' => $achRow['verification_status'],
                'notes' => $newNotes,
                'isCustom' => $achRow['source_type'] === 'personal',
                'position' => $pos,
            ]);
        }

        // بررسی در جدول حوزه‌های شخصی دانشجو
        if (strpos($nodeId, 'node-domain-custom-') === 0) {
            $domId = substr($nodeId, strlen('node-domain-'));
            $stmtDom = $pdo->prepare("SELECT * FROM `ug_student_domains` WHERE `student_id` = ? AND `domain_id` = ? LIMIT 1");
            $stmtDom->execute([$studentId, $domId]);
            $domRow = $stmtDom->fetch(PDO::FETCH_ASSOC);
            if ($domRow) {
                $newTitle = isset($body['title']) && trim((string)$body['title']) !== '' ? trim((string)$body['title']) : ($domRow['custom_title'] ?: $domId);
                $newNotes = array_key_exists('notes', $body) ? trim((string)$body['notes']) : ($domRow['description'] ?? '');
                $pdo->prepare("
                    UPDATE `ug_student_domains`
                    SET `custom_title` = ?, `description` = ?
                    WHERE `student_id` = ? AND `domain_id` = ?
                ")->execute([$newTitle, $newNotes, $studentId, $domId]);

                samad_record_student_action($pdo, $studentId, 'skills:graph:update_node', 'ویرایش باکس: ' . $nodeId);
                skills_send_json(200, [
                    'id' => $nodeId,
                    'nodeType' => 'domain',
                    'title' => $newTitle,
                    'subtitle' => 'حوزه شخصی',
                    'domainId' => $domId,
                    'domainTitle' => $newTitle,
                    'category' => 'حوزه شخصی',
                    'sourceType' => 'personal',
                    'notes' => $newNotes,
                    'isCustom' => true,
                    'position' => $pos,
                ]);
            }
        }

        skills_send_json(404, ['errorCode' => 'NODE_NOT_FOUND', 'message' => 'باکس مورد نظر یافت نشد یا غیرقابل ویرایش است.']);
    }

    // ۷. حذف باکس
    if ($action === 'delete_node') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:delete_node');
        $body = skills_read_json_body();
        $nodeId = trim((string)($_GET['node_id'] ?? $body['nodeId'] ?? $body['id'] ?? ''));

        if ($nodeId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_NODE', 'message' => 'شناسه باکس الزامی است.']);
        }
        if ($nodeId === 'node-student-' . $studentId) {
            skills_send_json(403, ['errorCode' => 'READ_ONLY_NODE', 'message' => 'امکان حذف گره مرکزی دانشجو وجود ندارد.']);
        }

        $deleted = false;

        $stmtDelSk = $pdo->prepare("DELETE FROM `ug_skills` WHERE `id` = ? AND `student_id` = ? AND `source_type` != 'system'");
        $stmtDelSk->execute([$nodeId, $studentId]);
        if ($stmtDelSk->rowCount() > 0) {
            $pdo->prepare("DELETE FROM `ug_skill_domains` WHERE `skill_id` = ?")->execute([$nodeId]);
            $deleted = true;
        }

        $stmtDelAch = $pdo->prepare("DELETE FROM `ug_achievements` WHERE `id` = ? AND `student_id` = ? AND `source_type` != 'system'");
        $stmtDelAch->execute([$nodeId, $studentId]);
        if ($stmtDelAch->rowCount() > 0) {
            $deleted = true;
        }

        if (strpos($nodeId, 'node-domain-custom-') === 0) {
            $domId = substr($nodeId, strlen('node-domain-'));
            $stmtDelCd = $pdo->prepare("DELETE FROM `ug_student_domains` WHERE `student_id` = ? AND `domain_id` = ? AND `custom_title` IS NOT NULL");
            $stmtDelCd->execute([$studentId, $domId]);
            if ($stmtDelCd->rowCount() > 0) {
                $deleted = true;
            }
        }

        if (!$deleted) {
            skills_send_json(404, ['errorCode' => 'NODE_NOT_FOUND', 'message' => 'باکس یافت نشد یا جزو گره‌های رسمی غیرقابل حذف است.']);
        }

        $pdo->prepare("DELETE FROM `ug_graph_edges` WHERE `student_id` = ? AND (`source_id` = ? OR `target_id` = ?)")->execute([$studentId, $nodeId, $nodeId]);

        $layout = skills_load_layout($pdo, $studentId);
        $positions = $layout['positions'];
        if (isset($positions[$nodeId])) {
            unset($positions[$nodeId]);
            skills_save_layout($pdo, $studentId, $positions, true);
        }

        skills_audit($pdo, $studentId, 'delete_node', 'node', $nodeId);
        samad_record_student_action($pdo, $studentId, 'skills:graph:delete_node', 'حذف باکس: ' . $nodeId);
        skills_send_json(200, ['success' => true]);
    }

    // ۸. ایجاد خط ارتباطی بین دو باکس
    if ($action === 'create_edge') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:create_edge');
        $body = skills_read_json_body();

        $source = trim((string)($body['source'] ?? ''));
        $target = trim((string)($body['target'] ?? ''));
        if ($source === '' || $target === '' || $source === $target) {
            skills_send_json(400, ['errorCode' => 'INVALID_EDGE', 'message' => 'مبدا و مقصد اتصال معتبر نیست.']);
        }

        $relationType = skills_normalize_relation_type($body['relationType'] ?? 'related_to');
        $label = trim((string)($body['label'] ?? ''));
        if ($label === '') {
            $label = skills_default_relation_label($relationType);
        }
        $sourceHandle = skills_normalize_port($body['sourceHandle'] ?? null);
        $targetHandle = skills_normalize_port($body['targetHandle'] ?? null);

        // بررسی تکراری نبودن یال هم‌جهت و به‌روزرسانی پورت در صورت وجود
        $stmtExist = $pdo->prepare("
            SELECT * FROM `ug_graph_edges`
            WHERE `student_id` = ? AND `source_id` = ? AND `target_id` = ?
            LIMIT 1
        ");
        $stmtExist->execute([$studentId, $source, $target]);
        $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pdo->prepare("
                UPDATE `ug_graph_edges`
                SET `edge_type` = ?, `label` = ?, `source_handle` = ?, `target_handle` = ?
                WHERE `id` = ? AND `student_id` = ?
            ")->execute([$relationType, $label, $sourceHandle, $targetHandle, $existing['id'], $studentId]);

            $dto = [
                'id' => $existing['id'],
                'source' => $source,
                'target' => $target,
                'relationType' => $relationType,
                'label' => $label,
                'sourceType' => $existing['source_type_badge'] ?: 'personal',
            ];
            if ($sourceHandle) $dto['sourceHandle'] = $sourceHandle;
            if ($targetHandle) $dto['targetHandle'] = $targetHandle;
            samad_record_student_action($pdo, $studentId, 'skills:graph:create_edge', 'بروزرسانی اتصال: ' . $existing['id']);
            skills_send_json(200, $dto);
        }

        $edgeId = 'edge-' . skills_uuid_short();
        $stmtIns = $pdo->prepare("
            INSERT INTO `ug_graph_edges` (`id`, `student_id`, `source_type`, `source_id`, `target_type`, `target_id`, `edge_type`, `label`, `source_handle`, `target_handle`, `source_type_badge`)
            VALUES (?, ?, 'node', ?, 'node', ?, ?, ?, ?, ?, 'personal')
        ");
        $stmtIns->execute([$edgeId, $studentId, $source, $target, $relationType, $label, $sourceHandle, $targetHandle]);

        $dto = [
            'id' => $edgeId,
            'source' => $source,
            'target' => $target,
            'relationType' => $relationType,
            'label' => $label,
            'sourceType' => 'personal',
        ];
        if ($sourceHandle) $dto['sourceHandle'] = $sourceHandle;
        if ($targetHandle) $dto['targetHandle'] = $targetHandle;

        skills_audit($pdo, $studentId, 'create_edge', 'edge', $edgeId, null, $dto);
        samad_record_student_action($pdo, $studentId, 'skills:graph:create_edge', 'اتصال جدید: ' . $edgeId);
        skills_send_json(201, $dto);
    }

    // ۹. ویرایش خط ارتباطی
    if ($action === 'update_edge') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:update_edge');
        $body = skills_read_json_body();
        $edgeId = trim((string)($_GET['edge_id'] ?? $body['edgeId'] ?? $body['id'] ?? ''));

        if ($edgeId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_EDGE', 'message' => 'شناسه خط ارتباطی مشخص نشده است.']);
        }

        $stmt = $pdo->prepare("SELECT * FROM `ug_graph_edges` WHERE `id` = ? AND `student_id` = ? LIMIT 1");
        $stmt->execute([$edgeId, $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            skills_send_json(404, ['errorCode' => 'EDGE_NOT_FOUND', 'message' => 'خط ارتباطی یافت نشد.']);
        }

        $relationType = isset($body['relationType'])
            ? skills_normalize_relation_type($body['relationType'])
            : $row['edge_type'];
        $label = isset($body['label']) && trim((string)$body['label']) !== ''
            ? trim((string)$body['label'])
            : $row['label'];
        $sourceHandle = array_key_exists('sourceHandle', $body)
            ? skills_normalize_port($body['sourceHandle'])
            : ($row['source_handle'] ?: null);
        $targetHandle = array_key_exists('targetHandle', $body)
            ? skills_normalize_port($body['targetHandle'])
            : ($row['target_handle'] ?: null);

        $pdo->prepare("
            UPDATE `ug_graph_edges`
            SET `edge_type` = ?, `label` = ?, `source_handle` = ?, `target_handle` = ?
            WHERE `id` = ? AND `student_id` = ?
        ")->execute([$relationType, $label, $sourceHandle, $targetHandle, $edgeId, $studentId]);

        $dto = [
            'id' => $edgeId,
            'source' => $row['source_id'],
            'target' => $row['target_id'],
            'relationType' => $relationType,
            'label' => $label,
            'sourceType' => $row['source_type_badge'] ?: 'personal',
        ];
        if ($sourceHandle) $dto['sourceHandle'] = $sourceHandle;
        if ($targetHandle) $dto['targetHandle'] = $targetHandle;

        samad_record_student_action($pdo, $studentId, 'skills:graph:update_edge', 'ویرایش اتصال: ' . $edgeId);
        skills_send_json(200, $dto);
    }

    // ۱۰. حذف خط ارتباطی
    if ($action === 'delete_edge') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:delete_edge');
        $body = skills_read_json_body();
        $edgeId = trim((string)($_GET['edge_id'] ?? $body['edgeId'] ?? $body['id'] ?? ''));

        if ($edgeId === '') {
            skills_send_json(400, ['errorCode' => 'INVALID_EDGE', 'message' => 'شناسه خط ارتباطی الزامی است.']);
        }

        $stmt = $pdo->prepare("DELETE FROM `ug_graph_edges` WHERE `id` = ? AND `student_id` = ?");
        $stmt->execute([$edgeId, $studentId]);
        if ($stmt->rowCount() === 0) {
            skills_send_json(404, ['errorCode' => 'EDGE_NOT_FOUND', 'message' => 'خط ارتباطی یافت نشد.']);
        }

        skills_audit($pdo, $studentId, 'delete_edge', 'edge', $edgeId);
        samad_record_student_action($pdo, $studentId, 'skills:graph:delete_edge', 'حذف اتصال: ' . $edgeId);
        skills_send_json(200, ['success' => true]);
    }

    // ۱۱. ذخیره چیدمان باکس‌ها
    if ($action === 'save_layout') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:save_layout');
        $body = skills_read_json_body();
        $positionsList = $body['positions'] ?? [];
        if (!is_array($positionsList)) {
            skills_send_json(400, ['errorCode' => 'INVALID_LAYOUT', 'message' => 'فرمت مختصات نامعتبر است.']);
        }

        $layout = skills_load_layout($pdo, $studentId);
        $positions = $layout['positions'];
        foreach ($positionsList as $item) {
            if (!is_array($item) || empty($item['nodeId'])) {
                continue;
            }
            if (isset($item['x'], $item['y']) && is_numeric($item['x']) && is_numeric($item['y'])) {
                $positions[(string)$item['nodeId']] = [
                    'x' => (int)round((float)$item['x']),
                    'y' => (int)round((float)$item['y']),
                ];
            }
        }

        $nextVersion = skills_save_layout($pdo, $studentId, $positions, true);
        samad_record_student_action($pdo, $studentId, 'skills:graph:save_layout', 'ذخیره چیدمان نسخه ' . $nextVersion);
        skills_send_json(200, ['success' => true, 'layoutVersion' => $nextVersion]);
    }

    // ۱۲. بازنشانی کامل نقشه به داده‌های پیش‌فرض ثبت‌نام دانشجو
    if ($action === 'reset') {
        $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'skills:graph:manage_self');
        $studentId = (string)$jwtAuth['sub'];
        skills_check_limit_or_429($pdo, $jwtAuth, 'skills:graph:reset');

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM `ug_graph_edges` WHERE `student_id` = ?")->execute([$studentId]);
            $pdo->prepare("
                DELETE skd FROM `ug_skill_domains` skd
                JOIN `ug_skills` s ON skd.skill_id = s.id
                WHERE s.student_id = ?
            ")->execute([$studentId]);
            $pdo->prepare("DELETE FROM `ug_skills` WHERE `student_id` = ?")->execute([$studentId]);
            $pdo->prepare("DELETE FROM `ug_achievements` WHERE `student_id` = ?")->execute([$studentId]);
            $pdo->prepare("DELETE FROM `ug_student_domains` WHERE `student_id` = ?")->execute([$studentId]);
            $pdo->prepare("DELETE FROM `ug_graph_layouts` WHERE `student_id` = ? AND `graph_type` = 'student_skills'")->execute([$studentId]);
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        samad_seed_student_skill_graph_from_registration($pdo, $studentId);
        samad_record_student_action($pdo, $studentId, 'skills:graph:reset', 'بازنشانی کامل گراف مهارت‌ها');
        skills_send_json(200, samad_build_student_skill_graph($pdo, $studentId));
    }

    skills_send_json(400, [
        'errorCode' => 'UNKNOWN_ACTION',
        'message' => 'عملیات درخواستی معتبر نیست.',
    ]);
} catch (Throwable $e) {
    skills_send_json(500, [
        'errorCode' => 'INTERNAL_ERROR',
        'message' => 'خطای داخلی سرور در پردازش نقشه مهارت‌ها رخ داد: ' . $e->getMessage(),
        'detail' => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
}
