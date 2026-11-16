<?php
/**
 * پردازش نهایی ثبت‌نام دانشجو و درج در دیتابیس MySQL
 * شامل اعتبارسنجی مجدد سمت سرور، آپلود عکس، هش کلمه عبور و تراکنش دیتابیس
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
require_once 'jwt_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// دریافت و پاکسازی داده‌های فرم
$national_id       = toEnglishDigits(trim($_POST['national_id'] ?? ''));
$student_id        = toEnglishDigits(trim($_POST['student_id'] ?? ''));
$full_name         = trim($_POST['full_name'] ?? '');
$mobile            = toEnglishDigits(trim($_POST['mobile'] ?? ''));
$campus            = trim($_POST['campus'] ?? '');
$field_of_study    = trim($_POST['field_of_study'] ?? '');
$study_shift       = trim($_POST['study_shift'] ?? 'روزانه');
$province          = trim($_POST['province'] ?? '');
$city              = trim($_POST['city'] ?? '');
$is_foreign        = isset($_POST['is_foreign_student']) && $_POST['is_foreign_student'] == '1' ? 1 : 0;
$foreign_code      = trim($_POST['foreign_student_code'] ?? '');
$address           = trim($_POST['residential_address'] ?? '');
$request_dorm      = isset($_POST['request_dormitory']) && $_POST['request_dormitory'] == '1' ? 1 : 0;
$dorm_note         = trim($_POST['dormitory_note'] ?? '');
$password          = $_POST['password'] ?? '';
$confirm_password  = $_POST['confirm_password'] ?? '';
$skills_raw        = $_POST['skills'] ?? '[]'; // JSON array or comma separated
$festivals_raw     = $_POST['festivals'] ?? '[]';

// ۱. بررسی تطابق کد ملی و کد دانشجویی در جدول منبع (admitted_students)
$stmt = $pdo->prepare("SELECT * FROM `admitted_students` WHERE `national_id` = ? AND `student_id` = ? LIMIT 1");
$stmt->execute([$national_id, $student_id]);
$admitted = $stmt->fetch();

if (!$admitted) {
    echo json_encode([
        'status' => 'error',
        'message' => 'عدم تطابق هویتی: کد ملی و کد دانشجویی در جدول منبع پذیرفته‌شدگان مطابقت ندارند.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ۲. اعتبارسنجی کلمه عبور طبق قوانین درخواست‌شده:
// حداقل ۸ حرف، حروف کوچک و بزرگ لاتین، حداقل یک کاراکتر خاص
$hasMinLength   = strlen($password) >= 8;
$hasLowercase   = preg_match('/[a-z]/', $password);
$hasUppercase   = preg_match('/[A-Z]/', $password);
$hasSpecialChar = preg_match('/[!@#$%^&*()_+\-=[\]{};\':"\\|,.<>\/?]/', $password);

if (!$hasMinLength || !$hasLowercase || !$hasUppercase || !$hasSpecialChar) {
    echo json_encode([
        'status' => 'error',
        'message' => 'رمز عبور ضعیف است. باید حداقل ۸ حرف و شامل حروف کوچک و بزرگ لاتین و علامت خاص باشد.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'کلمه عبور و تکرار آن یکسان نیستند.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ۳. پردازش آپلود عکس در صورت ارسال
$photo_path = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $filename = 'student_' . $student_id . '_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_path = 'uploads/' . $filename;
        }
    }
}

// ۴. شروع تراکنش دیتابیس (Transaction) جهت اطمینان از ثبت کامل همه جداول
try {
    $pdo->beginTransaction();

    // رمزنگاری امن کلمه عبور با الگوریتم استاندارد BCRYPT
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // الف) درج در جدول اصلی دانشجویان
    $sqlStudent = "INSERT INTO `students` (
        `national_id`, `student_id`, `full_name`, `mobile`, `campus`, `field_of_study`, 
        `study_shift`, `province`, `city`, `is_foreign_student`, `foreign_student_code`, 
        `residential_address`, `request_dormitory`, `dormitory_note`, `photo_path`, `password_hash`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmtStudent = $pdo->prepare($sqlStudent);
    $stmtStudent->execute([
        $national_id, $student_id, $full_name, $mobile, $campus, $field_of_study,
        $study_shift, $province, $city, $is_foreign, $foreign_code,
        $address, $request_dorm, $dorm_note, $photo_path, $password_hash
    ]);

    // ب) درج مهارت‌ها در جدول student_skills
    $skills = is_array($skills_raw) ? $skills_raw : json_decode($skills_raw, true);
    if (!empty($skills) && is_array($skills)) {
        $stmtSkill = $pdo->prepare("INSERT INTO `student_skills` (`student_id`, `skill_name`) VALUES (?, ?)");
        foreach ($skills as $skill) {
            $skill = trim($skill);
            if (!empty($skill)) {
                $stmtSkill->execute([$student_id, $skill]);
            }
        }
    }

    // ج) درج جشنواره‌ها در جدول student_festivals
    $festivals = is_array($festivals_raw) ? $festivals_raw : json_decode($festivals_raw, true);
    if (!empty($festivals) && is_array($festivals)) {
        $festivalTitles = [
            'technical' => 'جشنواره‌ها و المپیادهای فنی و اختراعات',
            'skillBased' => 'جشنواره‌های مهارتی و مسابقات ملی مهارت',
            'religious' => 'جشنواره‌های مذهبی، قرآنی و معارف اسلامی',
            'cultural' => 'جشنواره‌های فرهنگی، ادبی و هنری',
            'robotics' => 'مسابقات رباتیک و هوش مصنوعی',
            'sports' => 'جشنواره‌های ورزشی و مسابقات پردیس'
        ];

        $stmtFest = $pdo->prepare("INSERT INTO `student_festivals` (`student_id`, `festival_key`, `festival_title`) VALUES (?, ?, ?)");
        foreach ($festivals as $festKey => $isChecked) {
            if ($isChecked) {
                $title = $festivalTitles[$festKey] ?? $festKey;
                $stmtFest->execute([$student_id, $festKey, $title]);
            }
        }
    }

    // د) درج تیم‌های ورزشی انتخابی دانشجو در جدول student_sports_teams
    $sports_teams = $_POST['sports_teams'] ?? [];
    if (!empty($sports_teams) && is_array($sports_teams)) {
        try {
            $stmtTeam = $pdo->prepare("INSERT INTO `student_sports_teams` (`student_id`, `team_name`) VALUES (?, ?)");
            foreach ($sports_teams as $team) {
                $team = trim($team);
                if (!empty($team)) {
                    $stmtTeam->execute([$student_id, $team]);
                }
            }
        } catch (Exception $teamEx) {
            // نادیده گرفتن در صورت مشکل مقطعی در جدول تیم‌ها
        }
    }

    // ه) بروزرسانی وضعیت در جدول منبع
    $stmtUpdate = $pdo->prepare("UPDATE `admitted_students` SET `is_registered` = 1 WHERE `student_id` = ?");
    $stmtUpdate->execute([$student_id]);

    // تایید نهایی تراکنش
    $pdo->commit();

    // ایجاد نشست کاربر و صدور توکن JWT
    $_SESSION['student_id'] = $student_id;
    $_SESSION['national_id'] = $national_id;
    $_SESSION['student_name'] = $full_name;
    $_SESSION['campus'] = $campus;

    $issuedJwt = samad_issue_jwt($pdo, (string)$student_id, 'student', (string)$full_name, false);

    echo json_encode([
        'status' => 'success',
        'message' => 'ثبت‌نام شما با موفقیت انجام شد و در پایگاه‌داده MySQL ذخیره گردید.',
        'redirect' => 'dashboard.php',
        'jwt_jti' => $issuedJwt['jti'],
        'expires_at' => $issuedJwt['expires_at']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ثبت اطلاعات در دیتابیس: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
