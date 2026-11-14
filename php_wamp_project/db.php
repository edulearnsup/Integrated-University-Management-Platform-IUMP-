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

    // درج نمونه اولیه در صورت خالی بودن
    $chkTeams = $pdo->query("SELECT COUNT(*) FROM `student_sports_teams`")->fetchColumn();
    if ($chkTeams == 0) {
        $pdo->exec("
            INSERT IGNORE INTO `student_sports_teams` (`student_id`, `team_name`) VALUES
            ('4012130045', 'تیم فوتبال دانشگاه'),
            ('4012130045', 'والیبال');
        ");
    }
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

    $chkCalls = $pdo->query("SELECT COUNT(*) FROM `festival_calls`")->fetchColumn();
    if ($chkCalls == 0) {
        $pdo->exec("
            INSERT IGNORE INTO `festival_calls` (`title`, `target_category`, `target_sports_team`, `content`, `deadline_date`, `issued_by`) VALUES
            ('فراخوان عضویت و انتخابی تیم فوتبال دانشجویان در مسابقات منطقه ای', 'sports', 'تیم فوتبال دانشگاه', 'از کلیه دانشجویان عضو تیم فوتبال و علاقه‌مندان جهت شرکت در تمرینات انتخابی مسابقات منطقه‌ای دانشگاه ملی مهارت دعوت به عمل می‌آید.', '۱۴۰۳/۰۸/۰۵', 'دکتر علیرضا حسینی (مدیر تربیت بدنی)'),
            ('فراخوان دومین المپیاد ملی مهارت‌آموزی و رباتیک دانشجویی', 'robotics', NULL, 'ثبت‌نام متقاضیان رقابت‌های ملی ساخت ربات‌های صنعتی و هوش مصنوعی آغاز شد. جهت هماهنگی با کارشناس پردیس مراجعه فرمایید.', '۱۴۰۳/۰۸/۱۵', 'معاونت پژوهش و فناوری پردیس');
        ");
    }
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

    $chkFac = $pdo->query("SELECT COUNT(*) FROM `room_facilities`")->fetchColumn();
    if ($chkFac == 0) {
        $pdo->exec("
            INSERT IGNORE INTO `room_facilities` (`room_id`, `room_number`, `floor`, `refrigerator`, `fridge_model`, `wardrobe`, `carpet`, `bed_type`, `ac_heater`, `has_broom`, `has_trash_bin`, `has_bucket`, `has_carpet`, `has_wardrobe`, `status`) VALUES
            (1, 'G01', 'همکف', 'یخچال ۶ فوت امرسان', 'یخچال ۶ فوت امرسان', 'کمد ۴ درب چوبی', 'فرش ۶ متری ۱۲۰۰ شانه', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (2, 'G02', 'همکف', 'یخچال ۵ فوت ایستکول', 'یخچال ۵ فوت ایستکول', 'کمد ۴ درب فلزی', 'فرش ۶ متری ۷۰۰ شانه', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (4, '100', 'طبقه اول', 'یخچال دوقلو ۱۲ فوت بزرگ', 'یخچال دوقلو ۱۲ فوت امرسان', 'کمد ۱۰ درب تفکیکی جادار', '۲ تخته فرش ۹ متری کاشان', 'تخت‌های استاندارد ۱۰ نفره', 'اسپلیت ۲۴ هزار ال‌جی', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (5, '101', 'طبقه اول', 'یخچال ۷ فوت اسنوا', 'یخچال ۷ فوت اسنوا', 'کمد ۴ درب اختصاصی', 'فرش ۶ متری طرح افشان', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (6, '102', 'طبقه اول', 'یخچال ۵ فوت فیلور', 'یخچال ۵ فوت فیلور', 'کمد ۴ درب چوبی', 'فرش ۶ متری مشهد', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (7, '103', 'طبقه اول', 'یخچال ۹ فوت دونار', 'یخچال ۹ فوت دونار', 'کمد ۶ درب ام‌دی‌اف نخبگان', 'فرش ۹ متری تبریز', 'تخت فلزی دو طبقه', 'شوفاژ + اسپلیت', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (9, '201', 'طبقه دوم', 'یخچال ۶ فوت امرسان', 'یخچال ۶ فوت امرسان', 'کمد ۴ درب', 'فرش ۶ متری', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده'),
            (10, '202', 'طبقه دوم', 'یخچال ۶ فوت هیمالیا', 'یخچال ۶ فوت هیمالیا', 'کمد ۴ درب', 'فرش ۶ متری', 'تخت فلزی دو طبقه', 'شوفاژ + کولر', 1, 1, 1, 1, 1, 'تکمیل‌شده');
        ");
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

    $chkClean = $pdo->query("SELECT COUNT(*) FROM `room_cleanliness_records`")->fetchColumn();
    if ($chkClean == 0) {
        $pdo->exec("
            INSERT IGNORE INTO `room_cleanliness_records` (`room_id`, `room_number`, `floor`, `inspection_date`, `grade`, `supervisor_national_id`, `supervisor_name`, `notes`, `recorded_at`) VALUES
            (4, '100', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'نظافت استثنایی اتاق ۱۰ نفره، کف بسیار تمیز و مرتب، تهویه عالی', '۱۴۰۳/۰۷/۱۴ - ۲۳:۳۰'),
            (5, '101', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'تخت‌های مرتب و آنکادر، یخچال بسیار پاکیزه', '۱۴۰۳/۰۷/۱۴ - ۲۳:۳۵'),
            (6, '102', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'خوب', '0099887766', 'مهندس کامران قنبری', 'مرتب و منظم، میز مطالعه کمی شلوغ', '۱۴۰۳/۰۷/۱۴ - ۲۳:۴۰'),
            (7, '103', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'اتاق نخبگان بسیار منضبط و پاکیزه', '۱۴۰۳/۰۷/۱۴ - ۲۳:۴۵'),
            (9, '201', 'طبقه دوم', '۱۴۰۳/۰۷/۱۴', 'متوسط', '0099887766', 'مهندس کامران قنبری', 'نیاز به شستشوی ظروف سینک و جمع‌آوری لباس‌ها', '۱۴۰۳/۰۷/۱۴ - ۲۳:۵۰'),
            (10, '202', 'طبقه دوم', '۱۴۰۳/۰۷/۱۴', 'ضعیف', '0099887766', 'مهندس کامران قنبری', 'انباشت زباله در سطل، تذکر کتبی به ساکنین اتاق داده شد', '۱۴۰۳/۰۷/۱۴ - ۲۳:۵۵'),
            (1, 'G01', 'همکف', '۱۴۰۳/۰۷/۱۴', 'خوب', '0099887766', 'مهندس کامران قنبری', 'کف تی کشیده شده و اتاق آماده است', '۱۴۰۳/۰۷/۱۵ - ۰۰:۰۰');
        ");
    }
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

    $chkMaint = $pdo->query("SELECT COUNT(*) FROM `maintenance_reports`")->fetchColumn();
    if ($chkMaint == 0) {
        $pdo->exec("
            INSERT IGNORE INTO `maintenance_reports` (`room_id`, `room_number`, `floor`, `equipment_name`, `maintenance_type`, `urgency`, `status`, `description`, `reported_by_supervisor_id`, `reported_by_supervisor_name`, `reported_at`, `resolved_at`, `resolved_by_manager_name`, `resolution_action`, `resolution_cost`, `cost_rials`, `invoice_number`, `contractor_or_store_name`, `resolution_notes`) VALUES
            (4, '100', 'طبقه اول', 'تخت طبقه دوم شماره ۳', 'تعمیر', 'فوری', 'در انتظار اقدام', 'جوش پایه نردبان تخت فلزی شل شده و برای بالا رفتن دانشجو خطرآفرین است.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۵ - ۲۲:۱۵', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
            (5, '101', 'طبقه اول', 'دستگیره پنجره بالکن', 'تعمیر', 'عادی', 'در انتظار اقدام', 'دستگیره دوجداره لق شده و به درستی قفل نمی‌شود.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۴ - ۲۲:۳۰', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
            (9, '201', 'طبقه دوم', 'رادیاتور شوفاژ اتاق', 'تعمیر', 'فوری', 'در حال تعمیر', 'نشتی جزئی آب از شیر رفت شوفاژ؛ نیازمند آچارکشی و تعویض واشر.', 'SUP-405-02', 'حسین مرادی', '۱۴۰۳/۰۷/۱۴ - ۲۲:۴۵', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
            (1, 'G01', 'همکف', 'ترموستات یخچال امرسان', 'تعمیر', 'عادی', 'رفع‌شده / تعمیر شد', 'موتور یخچال یکسره کار می‌کرد و دمای داخل بیش از حد سرد بود.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۰ - ۲۱:۰۰', '۱۴۰۳/۰۷/۱۱ - ۱۰:۳۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'تعمیر قطعه', 3500000, 3500000, 'INV-1403-1001', 'تعمیرگاه مجاز لوازم خانگی پردیس', 'سرویسکار مجاز فراخوانده شد و رله استارت و ترموستات تنظیم و تعویض گردید.'),
            (6, '102', 'طبقه اول', 'قفل درب کمد دیواری', 'تعمیر', 'عادی', 'رفع‌شده / تعمیر شد', 'مغزی قفل کمد گیر کرده بود و کلید نمی‌چرخید.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۰۸ - ۲۳:۰۰', '۱۴۰۳/۰۷/۰۹ - ۱۱:۰۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'تعمیر قطعه', 1800000, 1800000, 'INV-1403-1002', 'ابزار و یراق پردیس مرکزی', 'مغزی قفل کمد تعویض و کلید جدید به سرپرست تحویل شد.'),
            (7, '103', 'طبقه اول', 'مهتابی ال‌ای‌دی سقف', 'تعویض / خرید جدید', 'عادی', 'رفع‌شده / خریداری و تعویض شد', 'ترانس لامپ مهتابی سوخته بود و روشنایی کافی نداشت.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۰۵ - ۲۲:۰۰', '۱۴۰۳/۰۷/۰۶ - ۰۹:۳۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'خرید و جایگزینی تجهیز نو', 2400000, 2400000, 'INV-1403-1003', 'کالای برق و روشنایی نوین', 'پنل ال‌ای‌دی ۶۰ در ۶۰ پرنور نو نصب و جایگزین گردید.');
        ");
    }

    // تضمین وجود متقاضیان پذیرفته‌شده و دانشجویان در انتظار اسکان
    $pdo->exec("
        INSERT IGNORE INTO `admitted_students` (`national_id`, `student_id`, `full_name`, `campus`, `field_of_study`, `is_registered`) VALUES
        ('0055443322', '4012130070', 'سینا محمدپور (داوطلب قبولی جدید)', 'پردیس فناوری', 'مهندسی کامپیوتر - شبکه', 0),
        ('0066554433', '4012130071', 'مریم شفیعی (داوطلب قبولی جدید)', 'پردیس مهارت', 'طراحی لباس و پارچه', 0),
        ('0077665544', '4012130072', 'پرهام رفیعی (داوطلب قبولی جدید)', 'پردیس فناوری', 'مکاترونیک و اتوماسیون صنعتی', 0);
    ");

    $defaultHash = '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6';
    $pdo->exec("
        INSERT IGNORE INTO `students` (`national_id`, `student_id`, `full_name`, `mobile`, `campus`, `field_of_study`, `study_shift`, `province`, `city`, `residential_address`, `request_dormitory`, `password_hash`) VALUES
        ('0091234561', '4012130081', 'امیررضا کریمی (در انتظار اسکان)', '09351112233', 'پردیس فناوری', 'مهندسی کامپیوتر', 'روزانه', 'آذربایجان شرقی', 'تبریز', 'تبریز، میدان آبرسان', 1, '{$defaultHash}'),
        ('0091234562', '4012130082', 'محمدحسین نوری (متقاضی میهمانی)', '09362223344', 'پردیس مهارت', 'گرافیک و انیمیشن', 'روزانه', 'اصفهان', 'کاشان', 'کاشان، خیابان امیرکبیر', 1, '{$defaultHash}'),
        ('0091234563', '4012130083', 'سجاد موسوی (در انتظار اسکان)', '09373334455', 'پردیس فناوری', 'مهندسی برق صنعتی', 'شبانه (نوبت دوم)', 'خوزستان', 'دزفول', 'دزفول، خیابان شریعتی', 1, '{$defaultHash}');
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

// ستون‌های شماره فاکتور و پیمانکار در maintenance_reports
try {
    $checkMaintInv = $pdo->query("SHOW COLUMNS FROM `maintenance_reports` LIKE 'invoice_number'")->fetchAll();
    if (empty($checkMaintInv)) {
        $pdo->exec("
            ALTER TABLE `maintenance_reports` 
            ADD COLUMN `invoice_number` VARCHAR(50) DEFAULT NULL,
            ADD COLUMN `contractor_or_store_name` VARCHAR(150) DEFAULT NULL,
            ADD COLUMN `cost_rials` BIGINT DEFAULT NULL,
            ADD COLUMN `resolution_cost` BIGINT DEFAULT NULL,
            ADD COLUMN `resolution_action` VARCHAR(100) DEFAULT NULL,
            ADD COLUMN `resolution_notes` TEXT DEFAULT NULL,
            ADD COLUMN `resolved_by_manager_name` VARCHAR(150) DEFAULT NULL
        ");
    }
    // تصحیح نوع داده status جهت پذیرش انواع وضعیت‌های فارسی
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
              (`guest_end_date` IS NOT NULL AND `guest_end_date` != '' AND `guest_end_date` <= '{$todayStr}')
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

    $chkServ = $pdo->query("SELECT COUNT(*) FROM `welfare_services`")->fetchColumn();
    if ($chkServ == 0) {
        $pdo->exec("
            INSERT INTO `welfare_services` 
                (`title`, `category`, `location`, `hourly_rate_rials`, `slot_duration_hours`, `capacity_per_slot`, `rules_guidelines`, `is_active`)
            VALUES 
                ('سالن بدنسازی و پرورش اندام پردیس', 'gym', 'زیرزمین خوابگاه مرکزی صمد - سالن شماره ۱', 50000, 2, 20, 'همراه داشتن کفش ورزشی تمیز مخصوص سالن، حوله شخصی و بطری آب الزامی است. ورود با لباس غیرورزشی ممنوع می‌باشد.', 1)
        ");
    }
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

    $stmtPermSeed = $pdo->prepare("
        INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($defaultPerms as $permRow) {
        $stmtPermSeed->execute($permRow);
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

    $defaultDomains = [
        ['domain-tech', 'فناوری و برنامه‌نویسی', 'technology', 'توسعه نرم‌افزار، هوش مصنوعی، شبکه، پایگاه داده و وب', 'code', '#06b6d4', 1],
        ['domain-edu', 'علمی و آموزشی', 'education', 'علوم پایه، دروس تخصصی، ریاضیات، فیزیک و زبان‌های خارجه', 'book', '#6366f1', 2],
        ['domain-research', 'پژوهش و نوآوری', 'research', 'مقالات علمی، ثبت اختراع، پروژه‌های دانش‌بنیان و آزمایشگاهی', 'lightbulb', '#10b981', 3],
        ['domain-sport', 'ورزش و تندرستی', 'sport', 'رشته‌های ورزشی تیمی و انفرادی، آمادگی جسمانی و مسابقات', 'activity', '#ef4444', 4],
        ['domain-culture', 'فرهنگی و رسانه', 'culture', 'تولید محتوا، نشریات دانشجویی، عکاسی، فیلم و فعالیت‌های عمومی', 'palette', '#f59e0b', 5],
        ['domain-art', 'هنر و طراحی', 'art', 'طراحی گرافیک، معماری، موسیقی، تدوین و هنرهای تجسمی', 'pen', '#ec4899', 6],
        ['domain-quran', 'عترت و قرآن', 'quran', 'قرائت، حفظ، مفاهیم قرآنی و فعالیت‌های دینی و معارفی', 'bookmark', '#8b5cf6', 7],
        ['domain-workshop', 'مهارت‌های فنی و کارگاهی', 'workshop', 'الکترونیک، ساخت و تولید، بردهای سخت‌افزاری و مکاترونیک', 'wrench', '#f97316', 8],
    ];
    $stmtDomSeed = $pdo->prepare("
        INSERT IGNORE INTO `ug_domains` (`id`, `title`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_active`)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    foreach ($defaultDomains as $domRow) {
        $stmtDomSeed->execute($domRow);
    }
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
