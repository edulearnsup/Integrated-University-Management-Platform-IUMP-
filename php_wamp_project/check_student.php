<?php
/**
 * استعلام و تطابق کد ملی با کد دانشجویی از جدول منبع پذیرفته‌شدگان (admitted_students)
 * فراخوانی از طریق Ajax در مرحله اول ثبت‌نام
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$national_id = isset($_POST['national_id']) ? toEnglishDigits(trim($_POST['national_id'])) : '';
$student_id = isset($_POST['student_id']) ? toEnglishDigits(trim($_POST['student_id'])) : '';

if (empty($national_id) || empty($student_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'لطفاً هم کد ملی و هم شماره دانشجویی را وارد فرمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // بررسی در جدول منبع پذیرفته‌شدگان
    $stmt = $pdo->prepare("SELECT * FROM `admitted_students` WHERE `national_id` = ? AND `student_id` = ? LIMIT 1");
    $stmt->execute([$national_id, $student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        // بررسی اینکه آیا کلاً چنین کد ملی یا شماره دانشجویی وجود دارد یا خیر
        $checkNational = $pdo->prepare("SELECT id FROM `admitted_students` WHERE `national_id` = ? LIMIT 1");
        $checkNational->execute([$national_id]);
        $hasNational = $checkNational->fetch();

        $checkStudent = $pdo->prepare("SELECT id FROM `admitted_students` WHERE `student_id` = ? LIMIT 1");
        $checkStudent->execute([$student_id]);
        $hasStudent = $checkStudent->fetch();

        $detailMsg = 'کد ملی با شماره دانشجویی در لیست پذیرفته‌شدگان مطابقت ندارد.';
        if (!$hasNational && !$hasStudent) {
            $detailMsg = 'هیچ سابقه‌ای با این کد ملی و شماره دانشجویی در جدول پذیرفته‌شدگان یافت نشد.';
        } elseif (!$hasNational) {
            $detailMsg = 'کد ملی وارد شده در سامانه پذیرفته‌شدگان ثبت نشده است.';
        } elseif (!$hasStudent) {
            $detailMsg = 'شماره دانشجویی وارد شده در سامانه پذیرفته‌شدگان ثبت نشده است.';
        }

        echo json_encode([
            'status' => 'mismatch',
            'message' => $detailMsg
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // بررسی آیا قبلاً ثبت نام قطعی کرده است؟
    $stmtReg = $pdo->prepare("SELECT id FROM `students` WHERE `national_id` = ? OR `student_id` = ? LIMIT 1");
    $stmtReg->execute([$national_id, $student_id]);
    if ($stmtReg->fetch()) {
        echo json_encode([
            'status' => 'already_registered',
            'message' => 'این دانشجو قبلاً مراحل ثبت‌نام را به پایان رسانده است. لطفاً از صفحه ورود وارد سامانه شوید.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // تطابق کامل تایید شد
    echo json_encode([
        'status' => 'success',
        'message' => 'اطلاعات هویتی با کارنامه قبولی در سامانه سنجش پردیس صمد تطابق دارد.',
        'data' => [
            'full_name' => $student['full_name'],
            'campus' => $student['campus'],
            'field_of_study' => $student['field_of_study']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطای سرور: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
