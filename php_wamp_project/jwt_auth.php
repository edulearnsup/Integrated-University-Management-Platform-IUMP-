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
        ['student', 'dorm:room:view_self', 'مشاهده پرونده اسکان و کارت شناسایی خود', 1],
        ['student', 'dorm:graph:view_self', 'مشاهده گراف تعاملی اتاق و هم‌اتاقی‌ها', 1],
        ['student', 'skills:graph:view_self', 'مشاهده نقشه تعاملی مهارت‌ها و علایق دانشجو', 1],
        ['student', 'skills:graph:manage_self', 'افزودن، ویرایش و چیدمان مهارت‌ها در گراف شخصی', 1],
        ['student', 'welfare:book', 'رزرو سانس سالن بدنسازی و مدیریت کیف پول', 1],
        ['student', 'student:profile:edit', 'ویرایش شماره تماس و کلمه عبور دانشجویی', 1],
        // سرپرست شبانه
        ['supervisor', 'dorm:attendance:manage', 'ثبت و مدیریت سرشماری شبانه دانشجویان', 1],
        ['supervisor', 'dorm:cleanliness:manage', 'ارزیابی شب به شب کیفیت نظافت اتاق‌ها', 1],
        ['supervisor', 'dorm:maintenance:report', 'ثبت گزارش خرابی و درخواست تعمیر تجهیزات', 1],
        ['supervisor', 'dorm:facilities:view', 'مشاهده شناسنامه امکانات اتاق‌ها', 1],
        // مدیر دانشگاه
        ['manager', 'dorm:graph:view_all', 'مشاهده گراف جامع کل ساختمان، طبقات و ساکنان', 1],
        ['manager', 'dorm:rooms:manage', 'مدیریت اتاق‌ها، طبقات و ظرفیت خوابگاه', 1],
        ['manager', 'dorm:allocations:manage', 'اسکان دانشجویان، تغییر اتاق و میهمانی', 1],
        ['manager', 'dorm:attendance:manage', 'نظارت و چاپ گزارش‌های سرشماری شبانه', 1],
        ['manager', 'dorm:cleanliness:manage', 'نظارت بر رتبه‌بندی نظافت خوابگاه', 1],
        ['manager', 'dorm:maintenance:resolve', 'رسیدگی به خرابی‌ها و ثبت فاکتور تعمیرات', 1],
        ['manager', 'welfare:manage', 'مدیریت خدمات رفاهی و سانس‌های ورزشی', 1],
        ['manager', 'auth:tokens:manage', 'مدیریت توکن‌های JWT، ابطال نشست و ماتریس دسترسی', 1],
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
            $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM `auth_role_permissions`")->fetchColumn();
            if ($existingCount < count($defaultPerms)) {
                $stmtPermSeed = $pdo->prepare("
                    INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($defaultPerms as $permRow) {
                    $stmtPermSeed->execute($permRow);
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'auth_role_permissions seed: ' . $e->getMessage();
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
    $permissions = samad_get_role_permissions($pdo, $role);

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

    // همگام‌سازی مجوزهای زنده نقش از ماتریس RBAC
    $livePermissions = samad_get_role_permissions($pdo, $role);

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
            $_SESSION['samad_jwt_jti']
        );
    }
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
        } else {
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
