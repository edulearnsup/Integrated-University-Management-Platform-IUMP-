<?php
/**
 * هسته مرکزی احراز هویت و کنترل دسترسی مبتنی بر JWT (HS256) و RBAC
 * سازگار کامل با PHP 8 و محیط WAMP/XAMPP بدون وابستگی خارجی
 */

if (!defined('SAMAD_JWT_COOKIE')) {
    define('SAMAD_JWT_COOKIE', 'samad_jwt');
}
if (!defined('SAMAD_JWT_ISSUER')) {
    define('SAMAD_JWT_ISSUER', 'samad-tvu-portal');
}
if (!defined('SAMAD_MANAGER_DELETE_SECRET')) {
    define('SAMAD_MANAGER_DELETE_SECRET', 'Q7#xM9!vK4');
}

function samad_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function samad_base64url_decode(string $data): string|false
{
    $remainder = strlen($data) % 4;
    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'), true);
}

function samad_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 64);
}

function samad_user_agent(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 250);
}

function samad_default_rbac_matrix(): array
{
    return [
        // دانشجو
        ['student', 'dorm:card:view_self', 'مشاهده و چاپ کارت هوشمند دانشجویی', 1],
        ['student', 'dorm:room:view_self', 'مشاهده پرونده اسکان، هم‌اتاقی‌ها و گواهی سکونت', 1],
        ['student', 'dorm:graph:view_self', 'مشاهده گراف تعاملی اتاق و هم‌اتاقی‌ها', 1],
        ['student', 'dorm:guest_request:submit', 'ثبت و پیگیری درخواست اقامت میهمان در خوابگاه', 1],
        ['student', 'student:festivals:view', 'مشاهده تب مهارت‌ها، تیم‌های ورزشی و فراخوان‌ها', 1],
        ['student', 'skills:graph:view_self', 'مشاهده نقشه تعاملی مهارت‌ها و علایق دانشجو', 1],
        ['student', 'skills:graph:manage_self', 'افزودن، ویرایش و چیدمان مهارت‌ها در گراف شخصی', 1],
        ['student', 'welfare:book', 'مشاهده خدمات رفاهی، شارژ کیف پول و رزرو سالن بدنسازی', 1],
        ['student', 'student:profile:edit', 'ویرایش شماره تماس و کلمه عبور دانشجویی', 1],
        // سرپرست شبانه
        ['supervisor', 'dorm:attendance:manage', 'ثبت و مدیریت سرشماری شبانه دانشجویان', 1],
        ['supervisor', 'dorm:cleanliness:manage', 'ارزیابی شب به شب کیفیت نظافت اتاق‌ها', 1],
        ['supervisor', 'dorm:maintenance:report', 'ثبت گزارش خرابی و درخواست تعمیر تجهیزات', 1],
        ['supervisor', 'dorm:facilities:view', 'مشاهده شناسنامه امکانات اتاق‌ها', 1],
        ['supervisor', 'dorm:guest_request:view', 'مشاهده درخواست‌های اقامت میهمان دانشجویان (فقط خواندنی)', 1],
        // مدیر دانشگاه
        ['manager', 'dorm:overview:view', 'پیشخوان، آمار کلی و وضعیت طبقات', 1],
        ['manager', 'dorm:admitted:manage', 'پذیرش دانشجوی جدید در جدول منبع', 1],
        ['manager', 'dorm:unhoused:manage', 'مدیریت متقاضیان خوابگاه در صف اسکان', 1],
        ['manager', 'dorm:student:purge', 'حذف کلی دانشجوی متقاضی خوابگاه از تمامی جداول و سامانه', 1],
        ['manager', 'dorm:rooms:manage', 'مدیریت اتاق‌ها، طبقات و ظرفیت خوابگاه', 1],
        ['manager', 'dorm:graph:view_all', 'مشاهده گراف جامع کل ساختمان، طبقات و ساکنان', 1],
        ['manager', 'dorm:facilities:manage', 'تکمیل شناسنامه امکانات و تجهیزات اتاق‌ها', 1],
        ['manager', 'dorm:maintenance:resolve', 'رسیدگی به خرابی‌ها و ثبت فاکتور تعمیرات', 1],
        ['manager', 'dorm:cleanliness:manage', 'نظارت بر رتبه‌بندی نظافت و خروجی اکسل', 1],
        ['manager', 'dorm:allocations:manage', 'دانشجویان ساکن، تغییر اتاق و میهمانی', 1],
        ['manager', 'dorm:guest_request:manage', 'بررسی، تایید یا رد درخواست‌های اقامت میهمان دانشجویان', 1],
        ['manager', 'dorm:student_panel:impersonate', 'ورود از کارت دانشجویی به پنل دانشجو (با حفظ نشست مدیر)', 1],
        ['manager', 'dorm:supervisor_panel:impersonate', 'ورود از کارت سرپرست به پنل سرپرست شبانه (با حفظ نشست مدیر)', 1],
        ['manager', 'dorm:attendance:manage', 'سرشماری شبانه، چاپ و خروجی اکسل', 1],
        ['manager', 'dorm:supervisors:manage', 'مدیریت سرپرستان، شیفت‌ها و کاربران مدیر', 1],
        ['manager', 'dorm:festivals:manage', 'جشنواره‌ها، داوطلبان و صدور فراخوان', 1],
        ['manager', 'dorm:skills:view', 'مشاهده بانک مهارت‌ها و استعدادیابی', 1],
        ['manager', 'welfare:manage', 'مدیریت خدمات رفاهی و سانس‌های ورزشی', 1],
        ['manager', 'auth:tokens:manage', 'مدیریت توکن‌های JWT، ابطال نشست و ماتریس دسترسی', 1],
    ];
}

function samad_manager_permission_catalog(): array
{
    $catalog = [];
    foreach (samad_default_rbac_matrix() as $row) {
        if ($row[0] === 'manager') {
            $catalog[$row[1]] = $row[2];
        }
    }
    return $catalog;
}

function samad_default_student_action_limits(): array
{
    return [
        // [action_key, action_label, permission_key, category, max_count, window_days, is_limit_enabled, sort_order]
        ['student:profile:change_mobile', 'تغییر شماره تلفن همراه دانشجو', 'student:profile:edit', 'پروفایل و هویت', 1, 30, 1, 10],
        ['student:profile:change_password', 'تغییر کلمه عبور حساب دانشجویی', 'student:profile:edit', 'پروفایل و هویت', 2, 30, 1, 20],
        ['dorm:guest_request:submit', 'ثبت درخواست اقامت میهمان در خوابگاه', 'dorm:guest_request:submit', 'خوابگاه و اسکان', 2, 30, 1, 30],
        ['dorm:card:print', 'چاپ رسمی کارت هوشمند دانشجویی (A4)', 'dorm:card:view_self', 'خوابگاه و اسکان', 5, 30, 1, 40],
        ['dorm:certificate:print', 'چاپ گواهی رسمی سکونت در خوابگاه (A4)', 'dorm:room:view_self', 'خوابگاه و اسکان', 3, 30, 1, 50],
        ['dorm:graph:view_self', 'مشاهده و بارگذاری گراف تعاملی اتاق و هم‌اتاقی‌ها', 'dorm:graph:view_self', 'خوابگاه و اسکان', 60, 1, 1, 60],
        ['welfare:wallet_charge', 'شارژ آنلاین کیف پول الکترونیک', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 5, 7, 1, 70],
        ['welfare:book_gym', 'رزرو سانس سالن بدنسازی', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 4, 7, 1, 80],
        ['welfare:gym_ticket:print', 'چاپ بلیت دیجیتال سالن بدنسازی (A4)', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 10, 7, 1, 90],
        ['skills:graph:view_self', 'مشاهده و بارگذاری نقشه گراف مهارت‌ها', 'skills:graph:view_self', 'گراف مهارت‌ها و علایق', 60, 1, 1, 100],
        ['skills:graph:attach_domain', 'افزودن حوزه رسمی دانشگاه به گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 10, 30, 1, 110],
        ['skills:graph:detach_domain', 'حذف / جدا کردن حوزه از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 10, 30, 1, 120],
        ['skills:graph:create_node', 'ایجاد باکس جدید (مهارت / علاقه / دستاورد / رویداد)', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 15, 7, 1, 130],
        ['skills:graph:update_node', 'ویرایش مشخصات باکس در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 140],
        ['skills:graph:delete_node', 'حذف باکس شخصی از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 15, 7, 1, 150],
        ['skills:graph:create_edge', 'ایجاد خط ارتباطی جدید در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 160],
        ['skills:graph:update_edge', 'ویرایش خط ارتباطی در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 170],
        ['skills:graph:delete_edge', 'حذف خط ارتباطی از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 20, 7, 1, 180],
        ['skills:graph:save_layout', 'ذخیره چیدمان و مختصات گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 100, 1, 1, 190],
        ['skills:graph:reset', 'بازنشانی کامل گراف مهارت‌ها به حالت پیش‌فرض', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 2, 30, 1, 200],
        ['auth:jwt:refresh', 'تمدید و چرخش توکن JWT نشست دانشجو', 'student:profile:edit', 'امنیت و نشست JWT', 10, 1, 1, 210],
    ];
}

function samad_exec_ddl_with_fallback(PDO $pdo, string $baseCreateSql): ?string
{
    $suffixes = [
        " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci",
        " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        " DEFAULT CHARSET=utf8mb4",
        "",
    ];
    $lastError = null;
    foreach ($suffixes as $suffix) {
        try {
            $pdo->exec($baseCreateSql . $suffix);
            return null;
        } catch (Throwable $e) {
            $lastError = $e->getMessage();
        }
    }
    return $lastError ?? 'خطای نامشخص در ایجاد جدول';
}

function samad_ensure_jwt_schema(PDO $pdo, bool $force = false): ?string
{
    static $ensured = false;
    static $lastSchemaError = null;

    if ($ensured && !$force) {
        return $lastSchemaError;
    }

    $errors = [];

    $err1 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_settings` (
            `setting_key` VARCHAR(80) PRIMARY KEY,
            `setting_value` TEXT NOT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    if ($err1 !== null) {
        $errors[] = 'auth_settings: ' . $err1;
    }

    $err2 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_role_permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `role` ENUM('student', 'supervisor', 'manager') NOT NULL,
            `permission_key` VARCHAR(80) NOT NULL,
            `permission_label` VARCHAR(150) NOT NULL,
            `is_allowed` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_role_perm` (`role`, `permission_key`)
        )
    ");
    if ($err2 !== null) {
        $errors[] = 'auth_role_permissions: ' . $err2;
    }

    $err3 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_jwt_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `jti` VARCHAR(64) NOT NULL UNIQUE,
            `subject_id` VARCHAR(64) NOT NULL,
            `role` ENUM('student', 'supervisor', 'manager') NOT NULL,
            `subject_name` VARCHAR(150) NOT NULL,
            `permissions_json` TEXT NOT NULL,
            `issued_at` INT NOT NULL,
            `expires_at` INT NOT NULL,
            `revoked_at` DATETIME DEFAULT NULL,
            `revoked_by` VARCHAR(100) DEFAULT NULL,
            `revoke_reason` VARCHAR(255) DEFAULT NULL,
            `ip_address` VARCHAR(64) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `last_used_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_jwt_subject` (`subject_id`, `role`),
            INDEX `idx_jwt_expires` (`expires_at`)
        )
    ");
    if ($err3 !== null) {
        $errors[] = 'auth_jwt_tokens: ' . $err3;
    }

    $err4 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_audit_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `event_type` VARCHAR(50) NOT NULL,
            `actor_id` VARCHAR(64) DEFAULT NULL,
            `actor_role` VARCHAR(30) DEFAULT NULL,
            `target_jti` VARCHAR(64) DEFAULT NULL,
            `endpoint` VARCHAR(150) DEFAULT NULL,
            `http_status` INT NOT NULL DEFAULT 200,
            `detail` VARCHAR(255) NOT NULL,
            `ip_address` VARCHAR(64) DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_audit_created` (`created_at`),
            INDEX `idx_audit_event` (`event_type`)
        )
    ");
    if ($err4 !== null) {
        $errors[] = 'auth_audit_logs: ' . $err4;
    }

    $err5 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_user_permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `subject_id` VARCHAR(64) NOT NULL,
            `role` ENUM('student', 'supervisor', 'manager') NOT NULL,
            `permission_key` VARCHAR(80) NOT NULL,
            `is_allowed` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_by` VARCHAR(64) DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_user_perm` (`subject_id`, `role`, `permission_key`),
            INDEX `idx_user_perm_sub` (`subject_id`, `role`)
        )
    ");
    if ($err5 !== null) {
        $errors[] = 'auth_user_permissions: ' . $err5;
    }

    try {
        $chkAdminCreator = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'created_by_national_id'")->fetchAll();
        if (empty($chkAdminCreator)) {
            $pdo->exec("ALTER TABLE `admins` ADD COLUMN `created_by_national_id` VARCHAR(30) DEFAULT NULL AFTER `role`");
        }
    } catch (Throwable $e) {}

    if ($err1 === null) {
        try {
            $chkSecret = $pdo->query("SELECT `setting_value` FROM `auth_settings` WHERE `setting_key` = 'jwt_secret' LIMIT 1")->fetchColumn();
            if (!is_string($chkSecret) || strlen($chkSecret) < 32) {
                $generatedSecret = bin2hex(random_bytes(32));
                $stmtSec = $pdo->prepare("INSERT IGNORE INTO `auth_settings` (`setting_key`, `setting_value`) VALUES ('jwt_secret', ?)");
                $stmtSec->execute([$generatedSecret]);
            }
        } catch (Throwable $e) {
            $errors[] = 'jwt_secret seed: ' . $e->getMessage();
        }
    }

    if ($err2 === null) {
        try {
            $defaultPerms = samad_default_rbac_matrix();
            $existingPairs = $pdo->query("SELECT CONCAT(`role`, ':', `permission_key`) FROM `auth_role_permissions`")->fetchAll(PDO::FETCH_COLUMN);
            $existingMap = is_array($existingPairs) ? array_flip($existingPairs) : [];
            $stmtPermSeed = $pdo->prepare("
                INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($defaultPerms as $permRow) {
                $pairKey = $permRow[0] . ':' . $permRow[1];
                if (!isset($existingMap[$pairKey])) {
                    $stmtPermSeed->execute($permRow);
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'auth_role_permissions seed: ' . $e->getMessage();
        }
    }

    $err6 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_student_action_limits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `action_key` VARCHAR(80) NOT NULL UNIQUE,
            `action_label` VARCHAR(180) NOT NULL,
            `permission_key` VARCHAR(80) NOT NULL,
            `category` VARCHAR(60) NOT NULL,
            `max_count` INT NOT NULL DEFAULT 1,
            `window_days` INT NOT NULL DEFAULT 30,
            `is_limit_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            `updated_by` VARCHAR(64) DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    if ($err6 !== null) {
        $errors[] = 'auth_student_action_limits: ' . $err6;
    }

    $err7 = samad_exec_ddl_with_fallback($pdo, "
        CREATE TABLE IF NOT EXISTS `auth_student_action_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `action_key` VARCHAR(80) NOT NULL,
            `detail` VARCHAR(255) DEFAULT NULL,
            `ip_address` VARCHAR(64) DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sal_student_action_time` (`student_id`, `action_key`, `created_at`),
            INDEX `idx_sal_action_time` (`action_key`, `created_at`)
        )
    ");
    if ($err7 !== null) {
        $errors[] = 'auth_student_action_logs: ' . $err7;
    }

    if ($err6 === null) {
        try {
            $defaultLimits = samad_default_student_action_limits();
            $existingLimKeys = $pdo->query("SELECT `action_key` FROM `auth_student_action_limits`")->fetchAll(PDO::FETCH_COLUMN);
            $existingLimMap = is_array($existingLimKeys) ? array_flip($existingLimKeys) : [];
            $stmtLimSeed = $pdo->prepare("
                INSERT IGNORE INTO `auth_student_action_limits`
                    (`action_key`, `action_label`, `permission_key`, `category`, `max_count`, `window_days`, `is_limit_enabled`, `sort_order`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($defaultLimits as $limRow) {
                if (!isset($existingLimMap[$limRow[0]])) {
                    $stmtLimSeed->execute($limRow);
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'auth_student_action_limits seed: ' . $e->getMessage();
        }
    }

    $lastSchemaError = empty($errors) ? null : implode(' | ', $errors);
    $ensured = ($lastSchemaError === null);
    return $lastSchemaError;
}

function samad_get_jwt_secret(PDO $pdo): string
{
    static $cachedSecret = null;
    if ($cachedSecret !== null) {
        return $cachedSecret;
    }

    samad_ensure_jwt_schema($pdo);

    try {
        $val = $pdo->query("SELECT `setting_value` FROM `auth_settings` WHERE `setting_key` = 'jwt_secret' LIMIT 1")->fetchColumn();
        if (is_string($val) && strlen($val) >= 32) {
            $cachedSecret = $val;
            return $cachedSecret;
        }
        $newSecret = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO `auth_settings` (`setting_key`, `setting_value`) VALUES ('jwt_secret', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
        $stmt->execute([$newSecret]);
        $cachedSecret = $newSecret;
        return $cachedSecret;
    } catch (Throwable $e) {
        // Fallback deterministic server key derived from DB connection if table temporarily unavailable
        $cachedSecret = hash('sha256', __DIR__ . '|samad_jwt_fallback_key|v1');
        return $cachedSecret;
    }
}

function samad_get_role_permissions(PDO $pdo, string $role): array
{
    samad_ensure_jwt_schema($pdo);

    try {
        $stmt = $pdo->prepare("SELECT `permission_key` FROM `auth_role_permissions` WHERE `role` = ? AND `is_allowed` = 1 ORDER BY `id` ASC");
        $stmt->execute([$role]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (is_array($rows) && count($rows) > 0) {
            return array_values(array_unique(array_map('strval', $rows)));
        }
        // Check whether the table actually has any rows for this role (it might intentionally be all unchecked)
        $chkRoleRows = $pdo->prepare("SELECT COUNT(*) FROM `auth_role_permissions` WHERE `role` = ?");
        $chkRoleRows->execute([$role]);
        if ((int)$chkRoleRows->fetchColumn() > 0) {
            return [];
        }
    } catch (Throwable $e) {}

    $fallback = [];
    foreach (samad_default_rbac_matrix() as $row) {
        if ($row[0] === $role && (int)$row[3] === 1) {
            $fallback[] = $row[1];
        }
    }
    return $fallback;
}

function samad_get_user_permission_overrides(PDO $pdo, string $subjectId, string $role): array
{
    samad_ensure_jwt_schema($pdo);
    $map = [];
    try {
        $stmt = $pdo->prepare("SELECT `permission_key`, `is_allowed` FROM `auth_user_permissions` WHERE `subject_id` = ? AND `role` = ?");
        $stmt->execute([$subjectId, $role]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(string)$row['permission_key']] = ((int)$row['is_allowed'] === 1);
        }
    } catch (Throwable $e) {}
    return $map;
}

function samad_get_effective_permissions(PDO $pdo, string $subjectId, string $role): array
{
    $rolePerms = samad_get_role_permissions($pdo, $role);
    if ($role !== 'manager' || trim($subjectId) === '') {
        return $rolePerms;
    }
    $overrides = samad_get_user_permission_overrides($pdo, $subjectId, $role);
    if (empty($overrides)) {
        return $rolePerms;
    }
    $effective = [];
    foreach ($rolePerms as $permKey) {
        if (!empty($overrides[$permKey])) {
            $effective[] = $permKey;
        }
    }
    return $effective;
}

function samad_set_user_permissions(PDO $pdo, string $subjectId, string $role, array $allowedKeys, string $updatedBy): void
{
    samad_ensure_jwt_schema($pdo);
    $allKeys = array_keys(samad_manager_permission_catalog());
    $allowedSet = array_flip(array_map('strval', $allowedKeys));

    $stmt = $pdo->prepare("
        INSERT INTO `auth_user_permissions` (`subject_id`, `role`, `permission_key`, `is_allowed`, `updated_by`)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `is_allowed` = VALUES(`is_allowed`),
            `updated_by` = VALUES(`updated_by`),
            `updated_at` = CURRENT_TIMESTAMP
    ");
    foreach ($allKeys as $key) {
        $isAllowed = isset($allowedSet[$key]) ? 1 : 0;
        $stmt->execute([$subjectId, $role, $key, $isAllowed, substr($updatedBy, 0, 64)]);
    }
}

function samad_log_auth_event(
    PDO $pdo,
    string $eventType,
    ?string $actorId,
    ?string $actorRole,
    ?string $targetJti,
    string $endpoint,
    int $httpStatus,
    string $detail
): void {
    samad_ensure_jwt_schema($pdo);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `auth_audit_logs`
                (`event_type`, `actor_id`, `actor_role`, `target_jti`, `endpoint`, `http_status`, `detail`, `ip_address`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            substr($eventType, 0, 50),
            $actorId !== null ? substr($actorId, 0, 64) : null,
            $actorRole !== null ? substr($actorRole, 0, 30) : null,
            $targetJti !== null ? substr($targetJti, 0, 64) : null,
            substr($endpoint, 0, 150),
            $httpStatus,
            substr($detail, 0, 255),
            samad_client_ip(),
        ]);
    } catch (Throwable $e) {}
}

function samad_set_jwt_cookie(string $token, int $expiresAt): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    @setcookie(SAMAD_JWT_COOKIE, $token, [
        'expires' => $expiresAt,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[SAMAD_JWT_COOKIE] = $token;
}

function samad_clear_jwt_cookie(): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    @setcookie(SAMAD_JWT_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[SAMAD_JWT_COOKIE]);
}

function samad_issue_jwt(
    PDO $pdo,
    string $subjectId,
    string $role,
    string $displayName,
    bool $remember = false,
    array $extraClaims = []
): array {
    samad_ensure_jwt_schema($pdo);
    $now = time();
    $ttl = $remember ? (7 * 86400) : (8 * 3600); // ۸ ساعت عادی یا ۷ روز در حالت مرا به خاطر بسپار
    $expiresAt = $now + $ttl;
    $jti = bin2hex(random_bytes(16));
    $permissions = samad_get_effective_permissions($pdo, $subjectId, $role);

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT',
    ];

    $payload = array_merge([
        'iss' => SAMAD_JWT_ISSUER,
        'sub' => $subjectId,
        'role' => $role,
        'name' => $displayName,
        'permissions' => $permissions,
        'jti' => $jti,
        'iat' => $now,
        'exp' => $expiresAt,
    ], $extraClaims);

    $encodedHeader = samad_base64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $encodedPayload = samad_base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $signingInput = $encodedHeader . '.' . $encodedPayload;
    $secret = samad_get_jwt_secret($pdo);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $encodedSig = samad_base64url_encode($signature);
    $token = $signingInput . '.' . $encodedSig;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `auth_jwt_tokens`
                (`jti`, `subject_id`, `role`, `subject_name`, `permissions_json`, `issued_at`, `expires_at`, `ip_address`, `user_agent`, `last_used_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $jti,
            $subjectId,
            $role,
            $displayName,
            json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $now,
            $expiresAt,
            samad_client_ip(),
            samad_user_agent(),
        ]);
    } catch (Throwable $e) {}

    samad_set_jwt_cookie($token, $expiresAt);
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['samad_jwt_token'] = $token;
        $_SESSION['samad_jwt_jti'] = $jti;
    }

    samad_log_auth_event(
        $pdo,
        'token_issued',
        $subjectId,
        $role,
        $jti,
        (string)($_SERVER['SCRIPT_NAME'] ?? 'login.php'),
        200,
        "صدور توکن JWT جدید برای {$displayName} (نقش: {$role})"
    );

    return [
        'token' => $token,
        'jti' => $jti,
        'payload' => $payload,
        'issued_at' => $now,
        'expires_at' => $expiresAt,
        'permissions' => $permissions,
    ];
}

function samad_extract_jwt_token(): ?string
{
    $authHeader = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = (string)$_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = (string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                if (strtolower((string)$k) === 'authorization') {
                    $authHeader = (string)$v;
                    break;
                }
            }
        }
    }

    if ($authHeader !== '' && preg_match('/^\s*Bearer\s+([A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+)\s*$/i', $authHeader, $m)) {
        return trim($m[1]);
    }

    if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $xToken = trim((string)$_SERVER['HTTP_X_AUTH_TOKEN']);
        if (substr_count($xToken, '.') === 2) {
            return $xToken;
        }
    }

    if (!empty($_COOKIE[SAMAD_JWT_COOKIE])) {
        $cookieToken = trim((string)$_COOKIE[SAMAD_JWT_COOKIE]);
        if (substr_count($cookieToken, '.') === 2) {
            return $cookieToken;
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['samad_jwt_token'])) {
        $sessionToken = trim((string)$_SESSION['samad_jwt_token']);
        if (substr_count($sessionToken, '.') === 2) {
            return $sessionToken;
        }
    }

    return null;
}

function samad_decode_jwt_unverified(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    $headerJson = samad_base64url_decode($parts[0]);
    $payloadJson = samad_base64url_decode($parts[1]);
    if ($headerJson === false || $payloadJson === false) {
        return null;
    }
    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($payload)) {
        return null;
    }
    return ['header' => $header, 'payload' => $payload];
}

function samad_verify_jwt(PDO $pdo, ?string $rawToken = null, bool $touchLastUsed = false): array
{
    $token = $rawToken ?? samad_extract_jwt_token();
    if ($token === null || trim($token) === '') {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_MISSING',
            'message' => 'توکن احراز هویت (JWT) یافت نشد. لطفاً مجدداً وارد سامانه شوید.',
        ];
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_MALFORMED',
            'message' => 'ساختار توکن JWT نامعتبر است.',
        ];
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $headerJson = samad_base64url_decode($encodedHeader);
    $payloadJson = samad_base64url_decode($encodedPayload);
    $sigBin = samad_base64url_decode($encodedSignature);

    if ($headerJson === false || $payloadJson === false || $sigBin === false) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_MALFORMED',
            'message' => 'رمزگشایی اجزای توکن JWT با خطا مواجه شد.',
        ];
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($payload)) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_MALFORMED',
            'message' => 'محتوای JSON توکن نامعتبر است.',
        ];
    }

    if (($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_ALG_UNSUPPORTED',
            'message' => 'الگوریتم امضای توکن مجاز نیست.',
        ];
    }

    $secret = samad_get_jwt_secret($pdo);
    $expectedSig = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
    if (!hash_equals($expectedSig, $sigBin)) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_INVALID_SIGNATURE',
            'message' => 'امضای دیجیتال توکن JWT معتبر نیست.',
        ];
    }

    $now = time();
    $exp = (int)($payload['exp'] ?? 0);
    if ($exp <= $now) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_EXPIRED',
            'message' => 'اعتبار زمانی توکن JWT به پایان رسیده است. لطفاً دوباره وارد شوید.',
            'payload' => $payload,
        ];
    }

    $jti = trim((string)($payload['jti'] ?? ''));
    $sub = trim((string)($payload['sub'] ?? ''));
    $role = trim((string)($payload['role'] ?? ''));

    if ($jti === '' || $sub === '' || !in_array($role, ['student', 'supervisor', 'manager'], true)) {
        return [
            'valid' => false,
            'errorCode' => 'TOKEN_CLAIMS_INVALID',
            'message' => 'اطلاعات هویتی درون توکن ناقص یا نامعتبر است.',
        ];
    }

    // بررسی ابطال‌نشدن توکن در پایگاه‌داده MySQL
    samad_ensure_jwt_schema($pdo);
    $tokenRow = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM `auth_jwt_tokens` WHERE `jti` = ? LIMIT 1");
        $stmt->execute([$jti]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            return [
                'valid' => false,
                'errorCode' => 'TOKEN_UNKNOWN',
                'message' => 'شناسه توکن در نشست‌های معتبر سرور ثبت نشده است.',
                'payload' => $payload,
            ];
        }

        if (!empty($tokenRow['revoked_at'])) {
            return [
                'valid' => false,
                'errorCode' => 'TOKEN_REVOKED',
                'message' => 'این توکن دسترسی توسط مدیریت یا هنگام خروج باطل شده است.',
                'payload' => $payload,
                'tokenRow' => $tokenRow,
            ];
        }

        if ($touchLastUsed) {
            $upd = $pdo->prepare("UPDATE `auth_jwt_tokens` SET `last_used_at` = NOW() WHERE `jti` = ?");
            $upd->execute([$jti]);
        }
    } catch (Throwable $e) {}

    // همگام‌سازی مجوزهای زنده نقش و سیاست اختصاصی کاربر از ماتریس RBAC
    $livePermissions = samad_get_effective_permissions($pdo, $sub, $role);

    return [
        'valid' => true,
        'token' => $token,
        'header' => $header,
        'payload' => $payload,
        'sub' => $sub,
        'role' => $role,
        'name' => (string)($payload['name'] ?? ''),
        'jti' => $jti,
        'iat' => (int)($payload['iat'] ?? 0),
        'exp' => $exp,
        'permissions' => $livePermissions,
        'tokenPermissions' => is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [],
        'tokenRow' => $tokenRow,
    ];
}

function samad_revoke_jwt(PDO $pdo, string $jti, string $revokedBy = 'system', string $reason = 'لغو دستی نشست'): bool
{
    if (trim($jti) === '') {
        return false;
    }
    try {
        $stmt = $pdo->prepare("
            UPDATE `auth_jwt_tokens`
            SET `revoked_at` = NOW(), `revoked_by` = ?, `revoke_reason` = ?
            WHERE `jti` = ? AND `revoked_at` IS NULL
        ");
        $stmt->execute([substr($revokedBy, 0, 100), substr($reason, 0, 255), $jti]);
        $affected = $stmt->rowCount() > 0;
        if ($affected) {
            samad_log_auth_event(
                $pdo,
                'token_revoked',
                $revokedBy,
                null,
                $jti,
                (string)($_SERVER['SCRIPT_NAME'] ?? 'logout.php'),
                200,
                "ابطال توکن JWT ({$reason})"
            );
        }
        return $affected;
    } catch (Throwable $e) {
        return false;
    }
}

function samad_has_permission(array $authContext, string $permission): bool
{
    $perms = $authContext['permissions'] ?? [];
    if (!is_array($perms)) {
        return false;
    }
    return in_array($permission, $perms, true);
}

function samad_clear_session_auth(): void
{
    samad_clear_jwt_cookie();
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset(
            $_SESSION['student_id'],
            $_SESSION['national_id'],
            $_SESSION['student_name'],
            $_SESSION['campus'],
            $_SESSION['field'],
            $_SESSION['admin_id'],
            $_SESSION['admin_national_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_role'],
            $_SESSION['samad_jwt_token'],
            $_SESSION['samad_jwt_jti'],
            $_SESSION['samad_impersonate_student'],
            $_SESSION['samad_impersonate_supervisor']
        );
    }
}

function samad_start_student_impersonation(PDO $pdo, array $managerAuth, array $studentRow): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $targetSid = trim((string)($studentRow['student_id'] ?? ''));
    $targetName = trim((string)($studentRow['full_name'] ?? 'دانشجو'));
    if ($targetSid === '') {
        return;
    }
    $_SESSION['samad_impersonate_student'] = [
        'student_id' => $targetSid,
        'student_name' => $targetName,
        'manager_sub' => (string)($managerAuth['sub'] ?? ''),
        'manager_name' => (string)($managerAuth['name'] ?? ''),
        'manager_jti' => (string)($managerAuth['jti'] ?? ''),
        'started_at' => time(),
    ];
    $_SESSION['student_id'] = $targetSid;
    $_SESSION['student_name'] = $targetName;
    if (isset($studentRow['national_id'])) {
        $_SESSION['national_id'] = (string)$studentRow['national_id'];
    }
    if (isset($studentRow['campus'])) {
        $_SESSION['campus'] = (string)$studentRow['campus'];
    }
    if (isset($studentRow['field_of_study'])) {
        $_SESSION['field'] = (string)$studentRow['field_of_study'];
    }

    samad_log_auth_event(
        $pdo,
        'manager_impersonate_start',
        (string)($managerAuth['sub'] ?? ''),
        'manager',
        (string)($managerAuth['jti'] ?? ''),
        (string)($_SERVER['SCRIPT_NAME'] ?? 'manager_panel.php'),
        200,
        "ورود مدیر ({$managerAuth['name']}) به پنل دانشجو {$targetName} ({$targetSid}) با حفظ نشست مدیر"
    );
}

function samad_stop_student_impersonation(PDO $pdo, bool $logEvent = true): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $imp = $_SESSION['samad_impersonate_student'] ?? null;
    if ($logEvent && is_array($imp) && !empty($imp['student_id'])) {
        samad_log_auth_event(
            $pdo,
            'manager_impersonate_end',
            (string)($imp['manager_sub'] ?? ''),
            'manager',
            (string)($imp['manager_jti'] ?? ''),
            (string)($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'),
            200,
            "بازگشت مدیر ({$imp['manager_name']}) از پنل دانشجو {$imp['student_name']} ({$imp['student_id']}) به پنل مدیریت"
        );
    }
    unset(
        $_SESSION['samad_impersonate_student'],
        $_SESSION['student_id'],
        $_SESSION['student_name'],
        $_SESSION['national_id'],
        $_SESSION['campus'],
        $_SESSION['field']
    );
}

function samad_start_supervisor_impersonation(PDO $pdo, array $managerAuth, array $supervisorRow): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $targetNid = trim((string)($supervisorRow['national_id'] ?? ''));
    $targetName = trim((string)($supervisorRow['full_name'] ?? 'سرپرست شبانه'));
    if ($targetNid === '') {
        return;
    }
    unset($_SESSION['samad_impersonate_student']);
    $_SESSION['samad_impersonate_supervisor'] = [
        'supervisor_nid' => $targetNid,
        'supervisor_name' => $targetName,
        'shift_days' => trim((string)($supervisorRow['shift_days'] ?? '')),
        'shift_hours' => trim((string)($supervisorRow['shift_hours'] ?? '')),
        'manager_sub' => (string)($managerAuth['sub'] ?? ''),
        'manager_name' => (string)($managerAuth['name'] ?? ''),
        'manager_jti' => (string)($managerAuth['jti'] ?? ''),
        'started_at' => time(),
    ];

    samad_log_auth_event(
        $pdo,
        'manager_impersonate_supervisor_start',
        (string)($managerAuth['sub'] ?? ''),
        'manager',
        (string)($managerAuth['jti'] ?? ''),
        (string)($_SERVER['SCRIPT_NAME'] ?? 'manager_panel.php'),
        200,
        "ورود مدیر ({$managerAuth['name']}) به پنل سرپرست {$targetName} ({$targetNid}) با حفظ نشست مدیر"
    );
}

function samad_stop_supervisor_impersonation(PDO $pdo, bool $logEvent = true): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $imp = $_SESSION['samad_impersonate_supervisor'] ?? null;
    if ($logEvent && is_array($imp) && !empty($imp['supervisor_nid'])) {
        samad_log_auth_event(
            $pdo,
            'manager_impersonate_supervisor_end',
            (string)($imp['manager_sub'] ?? ''),
            'manager',
            (string)($imp['manager_jti'] ?? ''),
            (string)($_SERVER['SCRIPT_NAME'] ?? 'supervisor_panel.php'),
            200,
            "بازگشت مدیر ({$imp['manager_name']}) از پنل سرپرست {$imp['supervisor_name']} ({$imp['supervisor_nid']}) به پنل مدیریت"
        );
    }
    unset($_SESSION['samad_impersonate_supervisor']);
}

function samad_resolve_impersonation_context(PDO $pdo, array $verified, array $allowedRoles): array
{
    if (empty($verified['valid']) || ($verified['role'] ?? '') !== 'manager') {
        return $verified;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return $verified;
    }

    // ۱. بررسی ورود موقت مدیر به پنل دانشجو
    if (!empty($_SESSION['samad_impersonate_student']) && is_array($_SESSION['samad_impersonate_student'])) {
        $imp = $_SESSION['samad_impersonate_student'];
        $targetSid = trim((string)($imp['student_id'] ?? ''));
        if ($targetSid === '' || ($imp['manager_jti'] ?? '') !== ($verified['jti'] ?? '')) {
            unset($_SESSION['samad_impersonate_student']);
        } elseif (!samad_has_permission($verified, 'dorm:student_panel:impersonate')) {
            samad_stop_student_impersonation($pdo, false);
        } else {
            $wantsStudent = in_array('student', $allowedRoles, true) && (
                !in_array('manager', $allowedRoles, true) ||
                (($_GET['role'] ?? '') === 'student') ||
                strpos((string)($_SERVER['HTTP_REFERER'] ?? ''), 'dashboard.php') !== false ||
                strpos((string)($_SERVER['HTTP_REFERER'] ?? ''), 'role=student') !== false
            );
            if ($wantsStudent) {
                $resolved = $verified;
                $resolved['is_impersonating'] = true;
                $resolved['impersonator'] = [
                    'sub' => (string)$verified['sub'],
                    'name' => (string)$verified['name'],
                    'role' => 'manager',
                    'jti' => (string)$verified['jti'],
                    'started_at' => (int)($imp['started_at'] ?? time()),
                ];
                $resolved['role'] = 'student';
                $resolved['sub'] = $targetSid;
                $resolved['name'] = (string)($imp['student_name'] ?? $targetSid);
                $resolved['permissions'] = samad_get_effective_permissions($pdo, $targetSid, 'student');
                return $resolved;
            }
        }
    }

    // ۲. بررسی ورود موقت مدیر به پنل سرپرست شبانه
    if (!empty($_SESSION['samad_impersonate_supervisor']) && is_array($_SESSION['samad_impersonate_supervisor'])) {
        $impSup = $_SESSION['samad_impersonate_supervisor'];
        $targetNid = trim((string)($impSup['supervisor_nid'] ?? ''));
        if ($targetNid === '' || ($impSup['manager_jti'] ?? '') !== ($verified['jti'] ?? '')) {
            unset($_SESSION['samad_impersonate_supervisor']);
        } elseif (!samad_has_permission($verified, 'dorm:supervisor_panel:impersonate')) {
            samad_stop_supervisor_impersonation($pdo, false);
        } else {
            $wantsSupervisor = in_array('supervisor', $allowedRoles, true) && (
                !in_array('manager', $allowedRoles, true) ||
                (($_GET['role'] ?? '') === 'supervisor') ||
                strpos((string)($_SERVER['HTTP_REFERER'] ?? ''), 'supervisor_panel.php') !== false ||
                strpos((string)($_SERVER['HTTP_REFERER'] ?? ''), 'role=supervisor') !== false
            );
            if ($wantsSupervisor) {
                $resolved = $verified;
                $resolved['is_impersonating'] = true;
                $resolved['is_supervisor_impersonating'] = true;
                $resolved['impersonator'] = [
                    'sub' => (string)$verified['sub'],
                    'name' => (string)$verified['name'],
                    'role' => 'manager',
                    'jti' => (string)$verified['jti'],
                    'started_at' => (int)($impSup['started_at'] ?? time()),
                ];
                $resolved['role'] = 'supervisor';
                $resolved['sub'] = $targetNid;
                $resolved['name'] = (string)($impSup['supervisor_name'] ?? $targetNid);
                $resolved['permissions'] = samad_get_effective_permissions($pdo, $targetNid, 'supervisor');
                return $resolved;
            }
        }
    }

    return $verified;
}

function samad_render_forbidden_page(string $title, string $detail, string $currentRole): void
{
    http_response_code(403);
    $homeUrl = 'login.php';
    $homeLabel = 'ورود با حساب مجاز';
    if ($currentRole === 'student') {
        $homeUrl = 'dashboard.php';
        $homeLabel = 'بازگشت به میزکار دانشجو';
    } elseif ($currentRole === 'supervisor') {
        $homeUrl = 'supervisor_panel.php';
        $homeLabel = 'بازگشت به پنل سرپرست شبانه';
    } elseif ($currentRole === 'manager') {
        $homeUrl = 'manager_panel.php';
        $homeLabel = 'بازگشت به پنل مدیریت';
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>عدم دسترسی (403) - سامانه صمد</title>
        <link rel="stylesheet" href="assets/fonts/fonts.css">
        <link rel="stylesheet" href="assets/tailwind/tailwind.css">
        <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-6">
        <div class="max-w-md w-full bg-slate-900 border border-rose-500/40 rounded-2xl p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <span class="text-xs font-bold text-rose-400">خطای مجوز دسترسی</span>
                <span class="px-2 py-0.5 rounded bg-rose-950 text-rose-300 border border-rose-500/30 font-mono text-xs tabular-nums">HTTP 403</span>
            </div>
            <h1 class="text-base font-extrabold text-white"><?= htmlspecialchars($title) ?></h1>
            <p class="text-xs text-slate-300 leading-relaxed"><?= htmlspecialchars($detail) ?></p>
            <div class="pt-3 border-t border-slate-800 flex items-center gap-2.5 flex-wrap">
                <a href="<?= htmlspecialchars($homeUrl) ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors">
                    <?= htmlspecialchars($homeLabel) ?>
                </a>
                <a href="logout.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-colors">
                    تغییر حساب کاربری / خروج
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function samad_require_page_jwt(
    PDO $pdo,
    array $allowedRoles,
    ?string $requiredPermission = null,
    string $loginRedirect = 'login.php'
): array {
    $verified = samad_verify_jwt($pdo, null, true);

    if (!$verified['valid']) {
        $errCode = (string)($verified['errorCode'] ?? 'UNAUTHORIZED');
        samad_log_auth_event(
            $pdo,
            'token_verified_failed',
            $verified['payload']['sub'] ?? null,
            $verified['payload']['role'] ?? null,
            $verified['payload']['jti'] ?? null,
            (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            401,
            (string)($verified['message'] ?? 'اعتبارسنجی توکن ناموفق بود')
        );
        samad_clear_session_auth();
        $reasonParam = strtolower(str_replace('TOKEN_', '', $errCode));
        header("Location: {$loginRedirect}?auth_error=" . urlencode($reasonParam));
        exit;
    }

    $verified = samad_resolve_impersonation_context($pdo, $verified, $allowedRoles);
    $role = $verified['role'];
    if (!in_array($role, $allowedRoles, true)) {
        samad_log_auth_event(
            $pdo,
            'access_denied_403',
            $verified['sub'],
            $role,
            $verified['jti'],
            (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            403,
            "تلاش نقش '{$role}' برای دسترسی به صفحه غیرمجاز"
        );
        samad_render_forbidden_page(
            'دسترسی نقش شما به این بخش مجاز نیست',
            "توکن JWT شما با نقش «{$role}» صادر شده است و مجوز ورود به این پنل را ندارد.",
            $role
        );
    }

    if ($requiredPermission !== null && !samad_has_permission($verified, $requiredPermission)) {
        samad_log_auth_event(
            $pdo,
            'access_denied_403',
            $verified['sub'],
            $role,
            $verified['jti'],
            (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            403,
            "نداشتن مجوز RBAC: {$requiredPermission}"
        );
        samad_render_forbidden_page(
            'مجوز دسترسی غیرفعال شده است',
            "مجوز «{$requiredPermission}» در ماتریس دسترسی (RBAC) برای نقش شما غیرفعال است.",
            $role
        );
    }

    // همگام‌سازی متغیرهای نشست با اطلاعات تاییدشده JWT
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['samad_jwt_token'] = $verified['token'];
        $_SESSION['samad_jwt_jti'] = $verified['jti'];
        if ($role === 'student') {
            $_SESSION['student_id'] = $verified['sub'];
            $_SESSION['student_name'] = $verified['name'];
            // در حالت ورود مدیر به پنل دانشجو، متغیرهای نشست مدیر دست‌نخورده حفظ می‌شوند
        } elseif (empty($verified['is_impersonating'])) {
            $_SESSION['admin_role'] = $role;
            $_SESSION['admin_name'] = $verified['name'];
            $_SESSION['admin_national_id'] = $verified['sub'];
        }
    }

    return $verified;
}

function samad_send_api_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function samad_require_api_jwt(PDO $pdo, array $allowedRoles, ?string $requiredPermission = null): array
{
    $verified = samad_verify_jwt($pdo, null, true);

    if (!$verified['valid']) {
        $errCode = (string)($verified['errorCode'] ?? 'UNAUTHORIZED');
        samad_log_auth_event(
            $pdo,
            'token_verified_failed',
            $verified['payload']['sub'] ?? null,
            $verified['payload']['role'] ?? null,
            $verified['payload']['jti'] ?? null,
            (string)($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? 'api'),
            401,
            (string)($verified['message'] ?? 'خطای اعتبار توکن API')
        );
        samad_send_api_json(401, [
            'errorCode' => $errCode,
            'message' => $verified['message'] ?? 'نشست JWT معتبر نیست. لطفاً مجدداً وارد سامانه شوید.',
        ]);
    }

    $verified = samad_resolve_impersonation_context($pdo, $verified, $allowedRoles);
    $role = $verified['role'];
    if (!in_array($role, $allowedRoles, true)) {
        samad_log_auth_event(
            $pdo,
            'access_denied_403',
            $verified['sub'],
            $role,
            $verified['jti'],
            (string)($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? 'api'),
            403,
            "نقش '{$role}' مجاز به فراخوانی این API نیست"
        );
        samad_send_api_json(403, [
            'errorCode' => 'FORBIDDEN_ROLE',
            'message' => 'نقش کاربری توکن شما مجوز دسترسی به این منبع را ندارد.',
        ]);
    }

    if ($requiredPermission !== null && !samad_has_permission($verified, $requiredPermission)) {
        samad_log_auth_event(
            $pdo,
            'access_denied_403',
            $verified['sub'],
            $role,
            $verified['jti'],
            (string)($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? 'api'),
            403,
            "فقدان مجوز RBAC در API: {$requiredPermission}"
        );
        samad_send_api_json(403, [
            'errorCode' => 'FORBIDDEN_PERMISSION',
            'message' => "مجوز «{$requiredPermission}» برای توکن شما فعال نیست.",
        ]);
    }

    return $verified;
}

function samad_format_remaining_ttl(int $expiresAt): string
{
    $diff = max(0, $expiresAt - time());
    if ($diff <= 0) {
        return 'منقضی';
    }
    $days = intdiv($diff, 86400);
    $hours = intdiv($diff % 86400, 3600);
    $mins = intdiv($diff % 3600, 60);
    if ($days > 0) {
        return "{$days} روز و {$hours} ساعت";
    }
    if ($hours > 0) {
        return "{$hours} ساعت و {$mins} دقیقه";
    }
    return "{$mins} دقیقه";
}

function samad_get_student_action_limits(PDO $pdo): array
{
    samad_ensure_jwt_schema($pdo);
    $rows = [];
    try {
        $stmt = $pdo->query("SELECT * FROM `auth_student_action_limits` ORDER BY `sort_order` ASC, `id` ASC");
        if ($stmt) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $rows = [];
    }

    if (empty($rows)) {
        foreach (samad_default_student_action_limits() as $item) {
            $rows[] = [
                'action_key' => $item[0],
                'action_label' => $item[1],
                'permission_key' => $item[2],
                'category' => $item[3],
                'max_count' => $item[4],
                'window_days' => $item[5],
                'is_limit_enabled' => $item[6],
                'sort_order' => $item[7],
            ];
        }
    }

    $indexed = [];
    foreach ($rows as $row) {
        $indexed[(string)$row['action_key']] = [
            'id' => (int)($row['id'] ?? 0),
            'action_key' => (string)$row['action_key'],
            'action_label' => (string)$row['action_label'],
            'permission_key' => (string)$row['permission_key'],
            'category' => (string)$row['category'],
            'max_count' => max(0, (int)$row['max_count']),
            'window_days' => max(1, (int)$row['window_days']),
            'is_limit_enabled' => ((int)$row['is_limit_enabled']) === 1,
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'updated_by' => $row['updated_by'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
    return $indexed;
}

function samad_get_student_action_limit(PDO $pdo, string $actionKey): ?array
{
    $all = samad_get_student_action_limits($pdo);
    return $all[$actionKey] ?? null;
}

function samad_check_student_rate_limit(PDO $pdo, string $studentId, string $actionKey): array
{
    samad_ensure_jwt_schema($pdo);
    $cfg = samad_get_student_action_limit($pdo, $actionKey);
    if (!$cfg) {
        return [
            'allowed' => true,
            'action_key' => $actionKey,
            'action_label' => $actionKey,
            'is_limit_enabled' => false,
            'used' => 0,
            'max_count' => 0,
            'window_days' => 1,
            'remaining' => 999999,
            'message' => '',
        ];
    }

    $windowDays = max(1, (int)$cfg['window_days']);
    $maxCount = max(0, (int)$cfg['max_count']);
    $isEnabled = (bool)$cfg['is_limit_enabled'];
    $used = 0;
    $oldestAt = null;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, MIN(`created_at`) AS oldest_at
            FROM `auth_student_action_logs`
            WHERE `student_id` = ?
              AND `action_key` = ?
              AND `created_at` >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$studentId, $actionKey, $windowDays]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $used = (int)($stats['cnt'] ?? 0);
        $oldestAt = $stats['oldest_at'] ?? null;
    } catch (Throwable $e) {
        $used = 0;
    }

    if (!$isEnabled) {
        return [
            'allowed' => true,
            'action_key' => $actionKey,
            'action_label' => $cfg['action_label'],
            'is_limit_enabled' => false,
            'used' => $used,
            'max_count' => $maxCount,
            'window_days' => $windowDays,
            'remaining' => 999999,
            'message' => '',
        ];
    }

    $allowed = ($used < $maxCount);
    $remaining = max(0, $maxCount - $used);
    $msg = '';

    if (!$allowed) {
        if ($maxCount === 0) {
            $msg = "امکان انجام عملیات «{$cfg['action_label']}» با سقف مجاز ۰ در پنل مدیریت موقتاً مسدود شده است.";
        } else {
            $retryInfo = '';
            if (!empty($oldestAt)) {
                $resetTs = strtotime((string)$oldestAt . " + {$windowDays} days");
                if ($resetTs !== false && $resetTs > time()) {
                    $diffHours = (int)ceil(($resetTs - time()) / 3600);
                    if ($diffHours >= 24) {
                        $diffDays = (int)ceil($diffHours / 24);
                        $retryInfo = " (بازنشانی سهمیه بعدی تا حدود {$diffDays} روز دیگر)";
                    } else {
                        $retryInfo = " (بازنشانی سهمیه بعدی تا حدود {$diffHours} ساعت دیگر)";
                    }
                }
            }
            $msg = "سقف مجاز برای «{$cfg['action_label']}» (حداکثر {$maxCount} بار در بازه {$windowDays} روز) تکمیل شده است. میزان مصرف شما: {$used} از {$maxCount}{$retryInfo}.";
        }
    }

    return [
        'allowed' => $allowed,
        'action_key' => $actionKey,
        'action_label' => $cfg['action_label'],
        'is_limit_enabled' => true,
        'used' => $used,
        'max_count' => $maxCount,
        'window_days' => $windowDays,
        'remaining' => $remaining,
        'message' => $msg,
    ];
}

function samad_record_student_action(PDO $pdo, string $studentId, string $actionKey, string $detail = ''): void
{
    if (trim($studentId) === '' || trim($actionKey) === '') {
        return;
    }
    samad_ensure_jwt_schema($pdo);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `auth_student_action_logs` (`student_id`, `action_key`, `detail`, `ip_address`, `created_at`)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            substr(trim($studentId), 0, 20),
            substr(trim($actionKey), 0, 80),
            mb_substr(trim($detail), 0, 255, 'UTF-8'),
            samad_client_ip(),
        ]);
    } catch (Throwable $e) {}
}

function samad_log_rate_limit_exceeded(PDO $pdo, string $studentId, ?string $jti, string $endpoint, array $limitResult): void
{
    $label = (string)($limitResult['action_label'] ?? $limitResult['action_key'] ?? '');
    $used = (int)($limitResult['used'] ?? 0);
    $max = (int)($limitResult['max_count'] ?? 0);
    $days = (int)($limitResult['window_days'] ?? 1);
    samad_log_auth_event(
        $pdo,
        'rate_limit_exceeded',
        $studentId,
        'student',
        $jti,
        $endpoint,
        429,
        "محدودیت تعداد اکشن دانشجو ({$label}): {$used} از {$max} بار در {$days} روز"
    );
}

function samad_get_all_student_action_quotas(PDO $pdo, string $studentId): array
{
    $limits = samad_get_student_action_limits($pdo);
    $quotas = [];
    foreach ($limits as $key => $cfg) {
        $quotas[$key] = samad_check_student_rate_limit($pdo, $studentId, $key);
    }
    return $quotas;
}

