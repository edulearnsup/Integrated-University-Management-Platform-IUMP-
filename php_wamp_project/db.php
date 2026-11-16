<?php
/**
 * اتصال به پایگاه‌داده MySQL با PDO (مخصوص WampServer و XAMPP)
 * تنظیمات پیش‌فرض WampServer:
 * نام کاربری: root
 * کلمه عبور: خالی (بدون پسورد)
 * نام پایگاه‌داده: samad_db
 */

$db_host = 'localhost';
$db_name = 'samad_db';
$db_user = 'root';
$db_pass = ''; // در ومپ سرور معمولاً پسورد خالی است

// ۱. اتصال اولیه به پایگاه‌داده (در صورت نبود دیتابیس، ساخت خودکار آن)
try {
    $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci");
    $pdo->exec("USE `{$db_name}`");
} catch (Exception $e) {
    // تلاش برای اتصال مستقیم در صورت محدودیت دسترسی به ساخت پایگاه‌داده
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Exception $ex) {
        die("خطا در برقراری ارتباط با پایگاه‌داده MySQL: " . $ex->getMessage());
    }
}

// =========================================================================
// ۲. سیستم خودکار مهاجرت و ایجاد امن جداول (Self-Healing Table Migrations)
// تک‌تک جداول در بلاک‌های مجزا ایجاد می‌شوند تا خطای یکی مانع بقیه نشود.
// =========================================================================

// ۲-۱. جدول منبع پذیرفته‌شدگان (admitted_students)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admitted_students` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `national_id` VARCHAR(10) NOT NULL UNIQUE COMMENT 'کد ملی ده رقمی دانشجو',
            `student_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'شماره دانشجویی اختصاصی',
            `full_name` VARCHAR(150) NOT NULL COMMENT 'نام و نام خانوادگی در پرونده سنجش',
            `campus` ENUM('پردیس فناوری', 'پردیس مهارت') NOT NULL COMMENT 'پردیس قبولی',
            `field_of_study` VARCHAR(150) NOT NULL COMMENT 'رشته قبولی',
            `is_registered` TINYINT(1) DEFAULT 0 COMMENT 'آیا قبلاً ثبت نام کرده است؟',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ۲-۲. جدول اصلی دانشجویان ثبت‌نام شده (students)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `students` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `national_id` VARCHAR(10) NOT NULL UNIQUE,
            `student_id` VARCHAR(20) NOT NULL UNIQUE,
            `full_name` VARCHAR(150) NOT NULL,
            `mobile` VARCHAR(15) NOT NULL,
            `campus` ENUM('پردیس فناوری', 'پردیس مهارت') NOT NULL,
            `field_of_study` VARCHAR(150) NOT NULL,
            `study_shift` ENUM('روزانه', 'شبانه (نوبت دوم)') NOT NULL,
            `province` VARCHAR(80) NOT NULL,
            `city` VARCHAR(80) NOT NULL,
            `is_foreign_student` TINYINT(1) DEFAULT 0,
            `foreign_student_code` VARCHAR(50) DEFAULT NULL,
            `residential_address` TEXT NOT NULL,
            `request_dormitory` TINYINT(1) DEFAULT 0,
            `dormitory_note` TEXT DEFAULT NULL,
            `photo_path` VARCHAR(255) DEFAULT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `wallet_balance` BIGINT NOT NULL DEFAULT 250000 COMMENT 'موجودی کیف پول الکترونیک به ریال',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_national_id` (`national_id`),
            INDEX `idx_student_id` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ۲-۳. جدول کاربران ادمین (مدیر دانشگاه و سرپرست خوابگاه) (admins)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `national_id` VARCHAR(30) NOT NULL UNIQUE COMMENT 'شناسه یا کد ملی ادمین جهت ورود',
            `password_hash` VARCHAR(255) NOT NULL COMMENT 'کلمه عبور هش شده یا مستقیم',
            `full_name` VARCHAR(150) NOT NULL COMMENT 'نام و سمت',
            `role` ENUM('manager', 'supervisor') NOT NULL COMMENT 'manager: مدیر دانشگاه | supervisor: سرپرست خوابگاه',
            `created_by_national_id` VARCHAR(30) DEFAULT NULL COMMENT 'شناسه مدیر ایجادکننده کاربر',
            `shift_hours` VARCHAR(150) DEFAULT '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)',
            `shift_days` VARCHAR(150) DEFAULT 'شنبه تا چهارشنبه',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");

    try {
        $pdo->exec("ALTER TABLE `admins` MODIFY COLUMN `national_id` VARCHAR(30) NOT NULL");
    } catch (Exception $eCol) {}

    $chkAdmins = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($chkAdmins == 0) {
        $stmtAdminSeed = $pdo->prepare("
            INSERT IGNORE INTO `admins` (`national_id`, `password_hash`, `full_name`, `role`)
            VALUES (?, ?, ?, ?)
        ");
        $stmtAdminSeed->execute(['Admin', password_hash('Admin@12345', PASSWORD_BCRYPT), 'مدیر ارشد سامانه (Admin)', 'manager']);
    }
} catch (Exception $ex) {}

// ۲-۴. جدول اتاق‌های خوابگاه (dorm_rooms)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `dorm_rooms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_number` VARCHAR(10) NOT NULL UNIQUE COMMENT 'شماره اتاق (مانند G01، 100، 101، 201)',
            `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL COMMENT 'طبقه استقرار اتاق',
            `capacity` INT NOT NULL DEFAULT 4 COMMENT 'ظرفیت کل اتاق (تعداد تخت)',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: فعال و دایر | 0: غیرفعال و در دست تعمیر',
            `is_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: در انتظار تکمیل امکانات | 1: تکمیل‌شده',
            `description` VARCHAR(255) DEFAULT NULL COMMENT 'توضیحات (دارای بالکن، نزدیک راهرو و ...)',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ۲-۵. جدول تخصیص اتاق به دانشجویان (dorm_allocations)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `dorm_allocations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL COMMENT 'شماره دانشجویی',
            `room_id` INT NOT NULL COMMENT 'اتاق تخصیص یافته',
            `dorm_status` ENUM('میهمان', 'دارای شهریه', 'بدون شهریه') NOT NULL DEFAULT 'بدون شهریه' COMMENT 'وضعیت اسکان دانشجو',
            `allocation_date` DATE NOT NULL COMMENT 'تاریخ اسکان',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'وضعیت سکونت جاری',
            `guest_days_total` INT DEFAULT NULL COMMENT 'تعداد کل روزهای مجاز اقامت میهمانی',
            `guest_days_remaining` INT DEFAULT NULL COMMENT 'تعداد روزهای باقی‌مانده از اقامت میهمانی',
            `guest_start_date` DATE DEFAULT NULL COMMENT 'تاریخ شروع اقامت میهمان',
            `guest_end_date` DATE DEFAULT NULL COMMENT 'تاریخ پایان اقامت میهمان',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_alloc_room` (`room_id`),
            INDEX `idx_alloc_student` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول تیم‌های ورزشی دانشجو (student_sports_teams)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_sports_teams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `team_name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_student_sports` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول فراخوان‌های هدفمند جشنواره‌ها (festival_calls)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `festival_calls` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `target_category` ENUM('all', 'sports', 'religious', 'robotics', 'cultural', 'technical', 'skillBased') NOT NULL DEFAULT 'all',
            `target_sports_team` VARCHAR(100) DEFAULT NULL,
            `content` TEXT NOT NULL,
            `deadline_date` VARCHAR(30) NOT NULL,
            `issued_by` VARCHAR(150) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول امکانات و تجهیزات اتاق‌ها (room_facilities)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `room_facilities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `room_number` VARCHAR(10) NOT NULL,
            `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL,
            `refrigerator` VARCHAR(100) NOT NULL DEFAULT 'یخچال ۶ فوت استاندارد (سالم)',
            `fridge_model` VARCHAR(100) DEFAULT 'یخچال ۶ فوت امرسان',
            `wardrobe` VARCHAR(100) NOT NULL DEFAULT 'کمد ۴ درب اختصاصی دانشجویان (سالم)',
            `carpet` VARCHAR(100) NOT NULL DEFAULT 'فرش ۶ متری ماشینی (سالم)',
            `bed_type` VARCHAR(100) DEFAULT 'تخت فلزی دو طبقه استاندارد',
            `ac_heater` VARCHAR(100) DEFAULT 'شوفاژ مرکزی + کولر آبی',
            `tv_model` VARCHAR(100) DEFAULT NULL,
            `has_broom` TINYINT(1) DEFAULT 1,
            `has_trash_bin` TINYINT(1) DEFAULT 1,
            `has_bucket` TINYINT(1) DEFAULT 1,
            `has_carpet` TINYINT(1) DEFAULT 1,
            `has_wardrobe` TINYINT(1) DEFAULT 1,
            `has_desk` TINYINT(1) DEFAULT 0,
            `has_shoe_rack` TINYINT(1) DEFAULT 1,
            `has_mirror` TINYINT(1) DEFAULT 1,
            `secondary_facilities` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `status` ENUM('تکمیل‌شده', 'در انتظار تکمیل') NOT NULL DEFAULT 'در انتظار تکمیل',
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_room_facilities_id` (`room_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");

    // افزودن ستون‌های تفکیکی امکانات اتاق در صورت ایمپورت نسخه قدیمی‌تر database.sql
    $existingFacCols = $pdo->query("SHOW COLUMNS FROM `room_facilities`")->fetchAll(PDO::FETCH_COLUMN);
    $facColumnDefs = [
        'fridge_model'  => "VARCHAR(100) DEFAULT 'یخچال ۶ فوت امرسان' AFTER `refrigerator`",
        'bed_type'      => "VARCHAR(100) DEFAULT 'تخت فلزی دو طبقه استاندارد' AFTER `carpet`",
        'ac_heater'     => "VARCHAR(100) DEFAULT 'شوفاژ مرکزی + کولر آبی' AFTER `bed_type`",
        'tv_model'      => "VARCHAR(100) DEFAULT NULL AFTER `ac_heater`",
        'has_broom'     => "TINYINT(1) DEFAULT 1 AFTER `tv_model`",
        'has_trash_bin' => "TINYINT(1) DEFAULT 1 AFTER `has_broom`",
        'has_bucket'    => "TINYINT(1) DEFAULT 1 AFTER `has_trash_bin`",
        'has_carpet'    => "TINYINT(1) DEFAULT 1 AFTER `has_bucket`",
        'has_wardrobe'  => "TINYINT(1) DEFAULT 1 AFTER `has_carpet`",
        'has_desk'      => "TINYINT(1) DEFAULT 0 AFTER `has_wardrobe`",
        'has_shoe_rack' => "TINYINT(1) DEFAULT 1 AFTER `has_desk`",
        'has_mirror'    => "TINYINT(1) DEFAULT 1 AFTER `has_shoe_rack`",
        'notes'         => "TEXT DEFAULT NULL AFTER `secondary_facilities`",
    ];
    foreach ($facColumnDefs as $colName => $colDef) {
        if (!in_array($colName, $existingFacCols, true)) {
            $pdo->exec("ALTER TABLE `room_facilities` ADD COLUMN `{$colName}` {$colDef}");
        }
    }
} catch (Exception $ex) {}

// جدول ارزیابی نظافت اتاق‌ها (room_cleanliness_records)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `room_cleanliness_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `room_number` VARCHAR(10) NOT NULL,
            `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL,
            `inspection_date` VARCHAR(20) NOT NULL,
            `grade` ENUM('عالی', 'خوب', 'متوسط', 'ضعیف') NOT NULL DEFAULT 'خوب',
            `supervisor_national_id` VARCHAR(10) NOT NULL,
            `supervisor_name` VARCHAR(150) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `recorded_at` VARCHAR(50) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_clean_room` (`room_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول کارتابل خرابی و تعمیرات (maintenance_reports)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `maintenance_reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `room_number` VARCHAR(10) NOT NULL,
            `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL,
            `equipment_name` VARCHAR(150) NOT NULL,
            `maintenance_type` ENUM('تعمیر', 'تعویض / خرید جدید') NOT NULL DEFAULT 'تعمیر',
            `urgency` ENUM('فوری', 'عادی', 'کم‌اهمیت') NOT NULL DEFAULT 'عادی',
            `status` VARCHAR(100) NOT NULL DEFAULT 'در انتظار اقدام',
            `description` TEXT NOT NULL,
            `reported_by_supervisor_id` VARCHAR(20) NOT NULL,
            `reported_by_supervisor_name` VARCHAR(150) NOT NULL,
            `reported_at` VARCHAR(50) NOT NULL,
            `resolved_at` VARCHAR(50) DEFAULT NULL,
            `resolved_by_manager_name` VARCHAR(150) DEFAULT NULL,
            `resolution_action` VARCHAR(100) DEFAULT NULL,
            `resolution_cost` BIGINT DEFAULT NULL,
            `cost_rials` BIGINT DEFAULT NULL,
            `invoice_number` VARCHAR(50) DEFAULT NULL,
            `contractor_or_store_name` VARCHAR(150) DEFAULT NULL,
            `resolution_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_maint_room` (`room_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول سرشماری شبانه (dorm_night_attendance)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `dorm_night_attendance` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `room_id` INT NOT NULL,
            `attendance_date` DATE NOT NULL,
            `status` ENUM('حاضر', 'غایب', 'مرخصی') NOT NULL DEFAULT 'حاضر',
            `supervisor_national_id` VARCHAR(10) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_student_date` (`student_id`, `attendance_date`),
            INDEX `idx_att_room` (`room_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول مهارت‌های دانشجو (student_skills)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_skills` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `skill_name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_skills_std` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// جدول جشنواره‌های انتخابی دانشجو (student_festivals)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_festivals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `festival_key` VARCHAR(50) NOT NULL,
            `festival_title` VARCHAR(150) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_fest_std` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ستون‌های روزهای میهمانی و تاریخ شروع/پایان اقامت در dorm_allocations
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM `dorm_allocations` LIKE 'guest_days_total'")->fetchAll();
    if (empty($checkCols)) {
        $pdo->exec("
            ALTER TABLE `dorm_allocations` 
            ADD COLUMN `guest_days_total` INT DEFAULT NULL COMMENT 'تعداد کل روزهای اقامت میهمانی',
            ADD COLUMN `guest_days_remaining` INT DEFAULT NULL COMMENT 'روزهای باقی‌مانده میهمانی'
        ");
    }
    $checkGuestDates = $pdo->query("SHOW COLUMNS FROM `dorm_allocations` LIKE 'guest_start_date'")->fetchAll();
    if (empty($checkGuestDates)) {
        $pdo->exec("
            ALTER TABLE `dorm_allocations`
            ADD COLUMN `guest_start_date` DATE DEFAULT NULL COMMENT 'تاریخ شروع اقامت میهمان',
            ADD COLUMN `guest_end_date` DATE DEFAULT NULL COMMENT 'تاریخ پایان اقامت میهمان'
        ");
    }

    // بروزرسانی خودکار و بازگرداندن دانشجویان میهمان منقضی‌شده به لیست متقاضیان اسکان
    $pdo->exec("
        UPDATE `dorm_allocations`
        SET `is_active` = 0, `guest_days_remaining` = 0
        WHERE `is_active` = 1 
          AND `dorm_status` = 'میهمان' 
          AND (
              (`guest_end_date` IS NOT NULL AND `guest_end_date` < CURDATE())
              OR `guest_days_remaining` <= 0
          )
    ");
} catch (Exception $ex) {}

// ستون‌های تاریخ شروع و دسته‌بندی در فراخوان‌ها (festival_calls)
try {
    $checkCallStart = $pdo->query("SHOW COLUMNS FROM `festival_calls` LIKE 'start_date'")->fetchAll();
    if (empty($checkCallStart)) {
        $pdo->exec("
            ALTER TABLE `festival_calls`
            ADD COLUMN `start_date` VARCHAR(30) DEFAULT NULL COMMENT 'تاریخ شروع فراخوان',
            ADD COLUMN `target_skill` VARCHAR(100) DEFAULT NULL COMMENT 'مهارت یا تخصص هدف',
            ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 COMMENT 'وضعیت فعال بودن فراخوان'
        ");
    }
} catch (Exception $ex) {}

// ستون تکمیل اطلاعات در dorm_rooms
try {
    $checkRoomComp = $pdo->query("SHOW COLUMNS FROM `dorm_rooms` LIKE 'is_completed'")->fetchAll();
    if (empty($checkRoomComp)) {
        $pdo->exec("ALTER TABLE `dorm_rooms` ADD COLUMN `is_completed` TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("UPDATE `dorm_rooms` SET `is_completed` = 1 WHERE `is_active` = 1");
    }
} catch (Exception $ex) {}

// ستون‌های شماره فاکتور، مبلغ ریالی و پیمانکار در maintenance_reports
try {
    $existingMaintCols = $pdo->query("SHOW COLUMNS FROM `maintenance_reports`")->fetchAll(PDO::FETCH_COLUMN);
    $maintColumnDefs = [
        'invoice_number'           => "VARCHAR(50) DEFAULT NULL",
        'contractor_or_store_name' => "VARCHAR(150) DEFAULT NULL",
        'cost_rials'               => "BIGINT DEFAULT NULL",
        'resolution_cost'          => "BIGINT DEFAULT NULL",
        'resolution_action'        => "VARCHAR(100) DEFAULT NULL",
        'resolution_notes'         => "TEXT DEFAULT NULL",
        'resolved_by_manager_name' => "VARCHAR(150) DEFAULT NULL",
    ];
    foreach ($maintColumnDefs as $colName => $colDef) {
        if (!in_array($colName, $existingMaintCols, true)) {
            $pdo->exec("ALTER TABLE `maintenance_reports` ADD COLUMN `{$colName}` {$colDef}");
        }
    }
    // تصحیح نوع داده‌ها جهت پذیرش انواع مقادیر مالی بزرگ و وضعیت‌های فارسی
    $pdo->exec("ALTER TABLE `maintenance_reports` MODIFY COLUMN `resolution_cost` BIGINT DEFAULT NULL");
    $pdo->exec("ALTER TABLE `maintenance_reports` MODIFY COLUMN `resolution_action` VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE `maintenance_reports` MODIFY COLUMN `status` VARCHAR(100) NOT NULL DEFAULT 'در انتظار اقدام'");
} catch (Exception $ex) {}

// بررسی و انقضای خودکار اقامت میهمانی و بازگشت خودکار به صف متقاضیان خوابگاه
try {
    $todayStr = date('Y-m-d');
    // ۱. غیرفعال‌سازی اقامت‌های میهمانی که مهلت آنها به پایان رسیده است
    $pdo->exec("
        UPDATE `dorm_allocations` 
        SET `is_active` = 0, `guest_days_remaining` = 0 
        WHERE `dorm_status` = 'میهمان' 
          AND `is_active` = 1 
          AND (
              (`guest_end_date` IS NOT NULL AND `guest_end_date` <= '{$todayStr}')
              OR (`guest_days_remaining` IS NOT NULL AND `guest_days_remaining` <= 0)
          )
    ");
    // ۲. بازگرداندن این دانشجویان به صف متقاضیان خوابگاه با تنظیم request_dormitory = 1
    $pdo->exec("
        UPDATE `students` s
        JOIN `dorm_allocations` a ON s.student_id = a.student_id
        SET s.request_dormitory = 1
        WHERE a.dorm_status = 'میهمان' AND a.is_active = 0
    ");
} catch (Exception $ex) {}

// =========================================================================
// ۳. جداول سیستم خدمات رفاهی و نوبت‌دهی ورزشی/سالن بدنسازی (Welfare & Gym System)
// =========================================================================

// ستون موجودی کیف پول در جدول students (در صورت عدم وجود)
try {
    $chkWallet = $pdo->query("SHOW COLUMNS FROM `students` LIKE 'wallet_balance'")->fetchAll();
    if (empty($chkWallet)) {
        $pdo->exec("ALTER TABLE `students` ADD COLUMN `wallet_balance` BIGINT NOT NULL DEFAULT 250000 COMMENT 'موجودی کیف پول الکترونیک به ریال'");
    }
} catch (Exception $ex) {}

// ۱. جدول خدمات رفاهی (welfare_services)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `welfare_services` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL COMMENT 'عنوان خدمت مثلا سالن بدنسازی پردیس',
            `category` VARCHAR(50) NOT NULL DEFAULT 'gym' COMMENT 'gym, sports, general',
            `location` VARCHAR(150) NOT NULL DEFAULT 'زیرزمین خوابگاه مرکزی صمد',
            `hourly_rate_rials` BIGINT NOT NULL DEFAULT 50000 COMMENT 'هزینه هر یک ساعت به ریال',
            `slot_duration_hours` INT NOT NULL DEFAULT 2 COMMENT 'مدت زمان هر سانس بر حسب ساعت',
            `capacity_per_slot` INT NOT NULL DEFAULT 20 COMMENT 'ظرفیت مجاز هر سانس',
            `rules_guidelines` TEXT DEFAULT NULL COMMENT 'قوانین و نکات ورود به سالن',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'وضعیت فعال/غیرفعال بودن سالن',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ۲. جدول سانس‌ها و برنامه‌ریزی هفتگی/روزانه (welfare_service_slots)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `welfare_service_slots` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `service_id` INT NOT NULL,
            `slot_date_jalali` VARCHAR(20) NOT NULL COMMENT 'تاریخ خورشیدی مثلا 1404/08/20',
            `slot_date_gregorian` DATE NOT NULL COMMENT 'تاریخ میلادی جهت جستجو و مرتب‌سازی',
            `day_name_fa` VARCHAR(30) NOT NULL COMMENT 'شنبه، یکشنبه و...',
            `start_time` TIME NOT NULL COMMENT '08:00:00',
            `end_time` TIME NOT NULL COMMENT '10:00:00',
            `duration_hours` INT NOT NULL DEFAULT 2,
            `total_capacity` INT NOT NULL DEFAULT 20,
            `booked_count` INT NOT NULL DEFAULT 0,
            `is_team_reserved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'آیا این سانس مخصوص تیم ورزشی قفل شده است',
            `reserved_team_name` VARCHAR(100) DEFAULT NULL COMMENT 'نام تیم ورزشی رزروکننده',
            `team_notes` VARCHAR(255) DEFAULT NULL COMMENT 'توضیحات تمرین تیم ورزشی',
            `is_blocked` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'مسدود شده مثلا برای ناهار 12 تا 14 یا تعطیلات',
            `block_reason` VARCHAR(150) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_slot_date` (`slot_date_gregorian`),
            INDEX `idx_slot_jalali` (`slot_date_jalali`),
            INDEX `idx_slot_service` (`service_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");
} catch (Exception $ex) {}

// ۳. جدول نوبت‌ها و رزروهای دانشجویان (welfare_bookings) با توکن امنیتی و پشتیبانی تراکنش
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `welfare_bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'توکن امنیتی یکتا برای جلوگیری از جعل و ریس کاندیشن',
            `tracking_code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'کد رهگیری نوبت مثلا GYM-140408-1234',
            `service_id` INT NOT NULL,
            `slot_id` INT NOT NULL,
            `student_id` VARCHAR(20) NOT NULL,
            `student_name` VARCHAR(150) NOT NULL DEFAULT 'دانشجوی پردیس',
            `slot_date_jalali` VARCHAR(20) NOT NULL,
            `start_time` VARCHAR(10) NOT NULL,
            `end_time` VARCHAR(10) NOT NULL,
            `duration_hours` INT NOT NULL DEFAULT 2,
            `amount_paid_rials` BIGINT NOT NULL DEFAULT 0,
            `is_team_booking` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('confirmed', 'cancelled_by_manager', 'attended') NOT NULL DEFAULT 'confirmed',
            `refund_status` ENUM('none', 'refunded_to_wallet') NOT NULL DEFAULT 'none',
            `refunded_at` DATETIME DEFAULT NULL,
            `cancellation_reason` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_book_student` (`student_id`),
            INDEX `idx_book_slot` (`slot_id`),
            INDEX `idx_book_token` (`booking_token`),
            INDEX `idx_book_date` (`slot_date_jalali`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
    ");

    // تنظیم مقدار پیش‌فرض مطمئن برای student_name و بررسی وجود ستون amount_paid_rials
    try {
        $pdo->exec("ALTER TABLE `welfare_bookings` MODIFY COLUMN `student_name` VARCHAR(150) NOT NULL DEFAULT 'دانشجوی پردیس'");
        $cCols = $pdo->query("SHOW COLUMNS FROM `welfare_bookings` LIKE 'amount_paid_rials'")->fetchAll();
        if (empty($cCols)) {
            $pdo->exec("ALTER TABLE `welfare_bookings` ADD COLUMN `amount_paid_rials` BIGINT NOT NULL DEFAULT 0 AFTER `duration_hours`");
        }
    } catch (Exception $ex) {}
} catch (Exception $ex) {}

// =========================================================================
// ۴. جداول مدیریت دسترسی و احراز هویت مبتنی بر توکن JWT و RBAC
// =========================================================================
$jwtExecDdl = function (string $sql) use ($pdo): void {
    $suffixes = [
        " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci",
        " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        " DEFAULT CHARSET=utf8mb4",
        "",
    ];
    foreach ($suffixes as $suffix) {
        try {
            $pdo->exec($sql . $suffix);
            return;
        } catch (Exception $e) {}
    }
};

// ۴-۱. تنظیمات امنیتی سرور و کلید امضای توکن (auth_settings)
try {
    $jwtExecDdl("
        CREATE TABLE IF NOT EXISTS `auth_settings` (
            `setting_key` VARCHAR(80) PRIMARY KEY,
            `setting_value` TEXT NOT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $chkSecret = $pdo->query("SELECT `setting_value` FROM `auth_settings` WHERE `setting_key` = 'jwt_secret' LIMIT 1")->fetchColumn();
    if (!$chkSecret) {
        $generatedSecret = bin2hex(random_bytes(32));
        $stmtSec = $pdo->prepare("INSERT IGNORE INTO `auth_settings` (`setting_key`, `setting_value`) VALUES ('jwt_secret', ?)");
        $stmtSec->execute([$generatedSecret]);
    }
} catch (Exception $ex) {}

// ۴-۲. ماتریس مجوزهای نقش‌ها (auth_role_permissions)
try {
    $jwtExecDdl("
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

    $defaultPerms = [
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
        ['manager', 'dorm:guest_request:manage', 'بررسی، تایید یا رد درخواست‌های اقامت میهمان دانشجویان', 1],
        ['manager', 'dorm:rooms:manage', 'مدیریت اتاق‌ها، طبقات و ظرفیت خوابگاه', 1],
        ['manager', 'dorm:graph:view_all', 'مشاهده گراف جامع کل ساختمان، طبقات و ساکنان', 1],
        ['manager', 'dorm:facilities:manage', 'تکمیل شناسنامه امکانات و تجهیزات اتاق‌ها', 1],
        ['manager', 'dorm:maintenance:resolve', 'رسیدگی به خرابی‌ها و ثبت فاکتور تعمیرات', 1],
        ['manager', 'dorm:cleanliness:manage', 'نظارت بر رتبه‌بندی نظافت و خروجی اکسل', 1],
        ['manager', 'dorm:allocations:manage', 'دانشجویان ساکن، تغییر اتاق و میهمانی', 1],
        ['manager', 'dorm:student_panel:impersonate', 'ورود از کارت دانشجویی به پنل دانشجو (با حفظ نشست مدیر)', 1],
        ['manager', 'dorm:supervisor_panel:impersonate', 'ورود از کارت سرپرست به پنل سرپرست شبانه (با حفظ نشست مدیر)', 1],
        ['manager', 'dorm:attendance:manage', 'سرشماری شبانه، چاپ و خروجی اکسل', 1],
        ['manager', 'dorm:supervisors:manage', 'مدیریت سرپرستان، شیفت‌ها و کاربران مدیر', 1],
        ['manager', 'dorm:festivals:manage', 'جشنواره‌ها، داوطلبان و صدور فراخوان', 1],
        ['manager', 'dorm:skills:view', 'مشاهده بانک مهارت‌ها و استعدادیابی', 1],
        ['manager', 'welfare:manage', 'مدیریت خدمات رفاهی و سانس‌های ورزشی', 1],
        ['manager', 'auth:tokens:manage', 'مدیریت توکن‌های JWT، ابطال نشست و ماتریس دسترسی', 1],
    ];

    $stmtPermSeed = $pdo->prepare("
        INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($defaultPerms as $permRow) {
        $stmtPermSeed->execute($permRow);
    }

    $jwtExecDdl("
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

    $chkAdminCreator = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'created_by_national_id'")->fetchAll();
    if (empty($chkAdminCreator)) {
        $pdo->exec("ALTER TABLE `admins` ADD COLUMN `created_by_national_id` VARCHAR(30) DEFAULT NULL AFTER `role`");
    } else {
        try {
            $pdo->exec("ALTER TABLE `admins` MODIFY COLUMN `created_by_national_id` VARCHAR(30) DEFAULT NULL");
        } catch (Exception $eCol2) {}
    }
} catch (Exception $ex) {}

// ۴-۳. جدول ثبت و ابطال توکن‌های صادرشده (auth_jwt_tokens)
try {
    $jwtExecDdl("
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
} catch (Exception $ex) {}

// ۴-۴. جدول ممیزی رویدادهای امنیتی و دسترسی (auth_audit_logs)
try {
    $jwtExecDdl("
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
} catch (Exception $ex) {}

// ۴-۵. جدول تنظیمات محدودیت اکشن‌های دانشجو (auth_student_action_limits) و گزارش مصرف (auth_student_action_logs)
try {
    $jwtExecDdl("
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
    $jwtExecDdl("
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

    $defaultStudentLimits = [
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
    $stmtLimSeed = $pdo->prepare("
        INSERT IGNORE INTO `auth_student_action_limits`
            (`action_key`, `action_label`, `permission_key`, `category`, `max_count`, `window_days`, `is_limit_enabled`, `sort_order`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($defaultStudentLimits as $limRow) {
        $stmtLimSeed->execute($limRow);
    }
} catch (Exception $ex) {}

// =========================================================================
// ۵. جداول ماژول نقشه مهارت‌ها و علایق دانشجو (University Graph - Skills Module)
// =========================================================================
try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
        CREATE TABLE IF NOT EXISTS `ug_skill_domains` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `skill_id` VARCHAR(80) NOT NULL,
            `student_domain_id` VARCHAR(80) NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_ug_skill_dom` (`skill_id`, `student_domain_id`),
            INDEX `idx_ug_skdom_skill` (`skill_id`),
            INDEX `idx_ug_skdom_dom` (`student_domain_id`)
        )
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
        CREATE TABLE IF NOT EXISTS `ug_graph_layouts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `graph_type` VARCHAR(40) NOT NULL DEFAULT 'student_skills',
            `layout_version` INT NOT NULL DEFAULT 1,
            `layout_data` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_ug_layout_student` (`student_id`, `graph_type`)
        )
    ");
} catch (Exception $ex) {}

try {
    $jwtExecDdl("
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
    ");
} catch (Exception $ex) {}

// =========================================================================
// ۶. جدول درخواست‌های اقامت میهمان دانشجویان (guest_dorm_requests)
// =========================================================================
try {
    $jwtExecDdl("
        CREATE TABLE IF NOT EXISTS `guest_dorm_requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) NOT NULL,
            `full_name` VARCHAR(150) NOT NULL,
            `national_id` VARCHAR(20) NOT NULL,
            `mobile` VARCHAR(20) DEFAULT NULL,
            `field_of_study` VARCHAR(150) DEFAULT NULL,
            `study_shift` VARCHAR(60) DEFAULT NULL,
            `was_dorm_applicant` TINYINT(1) NOT NULL DEFAULT 0,
            `has_active_allocation` TINYINT(1) NOT NULL DEFAULT 0,
            `problem_description` TEXT NOT NULL,
            `requested_days` INT NOT NULL DEFAULT 7,
            `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            `assigned_room_id` INT DEFAULT NULL,
            `assigned_room_number` VARCHAR(20) DEFAULT NULL,
            `approved_days` INT DEFAULT NULL,
            `manager_note` TEXT DEFAULT NULL,
            `reviewed_by_id` VARCHAR(64) DEFAULT NULL,
            `reviewed_by_name` VARCHAR(150) DEFAULT NULL,
            `reviewed_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_gdr_student` (`student_id`),
            INDEX `idx_gdr_status` (`status`)
        )
    ");
} catch (Exception $ex) {}

// مدیریت سراسری تم تاریک / روشن (Dark / Light Theme) در سشن و کوکی
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (isset($_GET['action_toggle_theme']) || isset($_POST['action_toggle_theme'])) {
    $newTheme = (($_SESSION['theme'] ?? 'dark') === 'dark') ? 'light' : 'dark';
    $_SESSION['theme'] = $newTheme;
    @setcookie('portal_theme', $newTheme, time() + (86400 * 365), '/');
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false))) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'theme' => $newTheme]);
        exit;
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? 'manager_panel.php';
    header('Location: ' . $referer);
    exit;
}
if (!isset($_SESSION['theme']) && isset($_COOKIE['portal_theme'])) {
    $_SESSION['theme'] = $_COOKIE['portal_theme'];
}
$currentTheme = $_SESSION['theme'] ?? 'dark';

// تابع کمکی تبدیل ارقام فارسی و عربی به انگلیسی
if (!function_exists('toEnglishDigits')) {
    function toEnglishDigits($str) {
        if (!$str) return '';
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $str = str_replace($persian, $english, (string)$str);
        return str_replace($arabic, $english, (string)$str);
    }
}

// تابع کمکی تبدیل اعداد انگلیسی به فارسی
if (!function_exists('toPersianDigits')) {
    function toPersianDigits($str) {
        if ($str === null || $str === '') return '';
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace($english, $persian, (string)$str);
    }
}
