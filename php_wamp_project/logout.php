<?php
session_start();
require_once 'db.php';
require_once 'jwt_auth.php';

$token = samad_extract_jwt_token();
if ($token !== null) {
    $decoded = samad_decode_jwt_unverified($token);
    $jti = (string)($decoded['payload']['jti'] ?? '');
    $actor = (string)($decoded['payload']['sub'] ?? 'user');
    if ($jti !== '') {
        samad_revoke_jwt($pdo, $jti, $actor, 'خروج امن کاربر از سامانه');
    }
} elseif (!empty($_SESSION['samad_jwt_jti'])) {
    $actor = (string)($_SESSION['student_id'] ?? $_SESSION['admin_national_id'] ?? 'user');
    samad_revoke_jwt($pdo, (string)$_SESSION['samad_jwt_jti'], $actor, 'خروج امن کاربر از سامانه');
}

samad_clear_session_auth();
session_unset();
session_destroy();
header('Location: login.php?logged_out=1');
exit;
