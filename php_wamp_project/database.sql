-- ==========================================================
-- اسکریپت جامع پایگاه‌داده سامانه صمد (مخصوص WampServer و phpMyAdmin)
-- شامل: جدول منبع، دانشجویان، مهارت‌ها، جشنواره‌ها، ادمین‌ها (مدیر و سرپرست)،
-- اتاق‌های خوابگاه در طبقات (همکف، اول، دوم)، تخصیص اتاق و وضعیت شهریه، و سرشماری شبانه
-- Encoding: UTF-8 Unicode (utf8mb4)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `samad_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE `samad_db`;

-- ----------------------------------------------------------
-- ۱. جدول منبع پذیرفته‌شدگان (جهت بررسی تطابق کد ملی و کد دانشجویی در مرحله ۱ ثبت‌نام)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `admitted_students`;
CREATE TABLE `admitted_students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `national_id` VARCHAR(10) NOT NULL UNIQUE COMMENT 'کد ملی ده رقمی دانشجو',
  `student_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'شماره دانشجویی اختصاصی',
  `full_name` VARCHAR(150) NOT NULL COMMENT 'نام و نام خانوادگی در پرونده سنجش',
  `campus` ENUM('پردیس فناوری', 'پردیس مهارت') NOT NULL COMMENT 'پردیس قبولی',
  `field_of_study` VARCHAR(150) NOT NULL COMMENT 'رشته قبولی',
  `is_registered` TINYINT(1) DEFAULT 0 COMMENT 'آیا قبلاً ثبت نام کرده است؟',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- داده‌های اولیه نمونه در جدول منبع
INSERT INTO `admitted_students` (`national_id`, `student_id`, `full_name`, `campus`, `field_of_study`, `is_registered`) VALUES
('0021487591', '4012130045', 'علی رضایی مقدم', 'پردیس فناوری', 'مهندسی کامپیوتر - هوش مصنوعی و داده', 1),
('0019845231', '4012130046', 'سارا احمدی', 'پردیس مهارت', 'گرافیک و ارتباط تصویری', 1),
('0452318974', '4012130047', 'امیرحسین کاظمی', 'پردیس فناوری', 'مهندسی برق - الکترونیک و مخابرات', 1),
('1278965412', '4012130048', 'زهرا محمدی', 'پردیس مهارت', 'معماری و دکوراسیون داخلی', 1),
('0032541896', '4012130049', 'مهدی صادقی', 'پردیس فناوری', 'مهندسی صنایع و مدیریت سیستم', 1),
('0041258963', '4012130050', 'رضا باقری', 'پردیس فناوری', 'مهندسی مکانیک و مکاترونیک', 1),
('0078965412', '4012130051', 'نیما حسینی', 'پردیس مهارت', 'تکنولوژی طراحی و ساخت', 1),
('0055443322', '4012130070', 'سینا محمدپور (داوطلب قبولی جدید)', 'پردیس فناوری', 'مهندسی کامپیوتر - شبکه', 0),
('0066554433', '4012130071', 'مریم شفیعی (داوطلب قبولی جدید)', 'پردیس مهارت', 'طراحی لباس و پارچه', 0),
('0077665544', '4012130072', 'پرهام رفیعی (داوطلب قبولی جدید)', 'پردیس فناوری', 'مکاترونیک و اتوماسیون صنعتی', 0);

-- ----------------------------------------------------------
-- ۲. جدول اصلی دانشجویان ثبت‌نام شده
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_national_id` (`national_id`),
  INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- دانشجویان ثبت‌نام شده اولیه برای تست پنل‌ها (رمز همه: Samad@2026!)
INSERT INTO `students` (`national_id`, `student_id`, `full_name`, `mobile`, `campus`, `field_of_study`, `study_shift`, `province`, `city`, `residential_address`, `request_dormitory`, `password_hash`) VALUES
('0021487591', '4012130045', 'علی رضایی مقدم', '09121234567', 'پردیس فناوری', 'مهندسی کامپیوتر - هوش مصنوعی و داده', 'روزانه', 'اصفهان', 'کاشان', 'کاشان خیابان شهید بهشتی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0019845231', '4012130046', 'سارا احمدی', '09129876543', 'پردیس مهارت', 'گرافیک و ارتباط تصویری', 'روزانه', 'فارس', 'شیراز', 'شیراز معالی آباد', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0452318974', '4012130047', 'امیرحسین کاظمی', '09351239988', 'پردیس فناوری', 'مهندسی برق - الکترونیک و مخابرات', 'روزانه', 'خراسان رضوی', 'مشهد', 'مشهد احمدآباد', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('1278965412', '4012130048', 'زهرا محمدی', '09135554433', 'پردیس مهارت', 'معماری و دکوراسیون داخلی', 'شبانه (نوبت دوم)', 'یزد', 'یزد', 'یزد میدان امیرچخماق', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0032541896', '4012130049', 'مهدی صادقی', '09194443322', 'پردیس فناوری', 'مهندسی صنایع و مدیریت سیستم', 'روزانه', 'آذربایجان شرقی', 'تبریز', 'تبریز آبرسان', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0041258963', '4012130050', 'رضا باقری', '09127778899', 'پردیس فناوری', 'مهندسی مکانیک و مکاترونیک', 'روزانه', 'گیلان', 'رشت', 'رشت گلسار', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0078965412', '4012130051', 'نیما حسینی', '09381112233', 'پردیس مهارت', 'تکنولوژی ساخت', 'روزانه', 'مازندران', 'ساری', 'ساری خ فرهنگ', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
-- دانشجویان سناریوی اتاق ۱۰۰ با ظرفیت ۱۰ و اسکان ۱۳ نفر (شامل مازاد بر ظرفیت)
('0081234567', '4012130052', 'آرمین شجاعی', '09120000001', 'پردیس فناوری', 'مهندسی کامپیوتر', 'روزانه', 'البرز', 'کرج', 'کرج عظیمیه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234568', '4012130053', 'پارسا یوسفی', '09120000002', 'پردیس فناوری', 'مهندسی برق', 'روزانه', 'خوزستان', 'اهواز', 'اهواز کیانپارس', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234569', '4012130054', 'سینا فلاحی', '09120000003', 'پردیس فناوری', 'مهندسی صنایع', 'روزانه', 'کرمان', 'کرمان', 'کرمان میدان آزادی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234570', '4012130055', 'پویا عباسی', '09120000004', 'پردیس فناوری', 'مهندسی مکانیک', 'روزانه', 'همدان', 'همدان', 'همدان بوعلی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234571', '4012130056', 'کیان منصوری', '09120000005', 'پردیس فناوری', 'مهندسی عمران', 'روزانه', 'مرکزی', 'اراک', 'اراک خیابان دانشگاه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234572', '4012130057', 'دانیال کریمی', '09120000006', 'پردیس فناوری', 'مهندسی مواد', 'روزانه', 'زنجان', 'زنجان', 'زنجان سعدی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234573', '4012130058', 'شایان دهقان', '09120000007', 'پردیس فناوری', 'مهندسی شیمی', 'روزانه', 'قم', 'قم', 'قم صفاییه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234574', '4012130059', 'عرفان ابراهیمی', '09120000008', 'پردیس فناوری', 'مهندسی کامپیوتر', 'روزانه', 'لرستان', 'خرم‌آباد', 'خرم‌آباد علوی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234575', '4012130060', 'سروش نظری', '09120000009', 'پردیس فناوری', 'مهندسی نفت', 'روزانه', 'کرمانشاه', 'کرمانشاه', 'کرمانشاه نوبهار', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234576', '4012130061', 'برنا رادمنش (روزانه مازاد)', '09120000010', 'پردیس فناوری', 'مهندسی کامپیوتر', 'روزانه', 'سمنان', 'سمنان', 'سمنان دانشگاه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234577', '4012130062', 'فرزاد محمودی (دانشجوی میهمان)', '09120000011', 'پردیس مهارت', 'معماری داخلی', 'روزانه', 'یزد', 'یزد', 'یزد صفائیه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0081234578', '4012130063', 'نوید صالحی (دانشجوی شبانه)', '09120000012', 'پردیس فناوری', 'مهندسی مکانیک', 'شبانه (نوبت دوم)', 'قزوین', 'قزوین', 'قزوین دانشگاه', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0091234561', '4012130081', 'امیررضا کریمی (در انتظار اسکان)', '09351112233', 'پردیس فناوری', 'مهندسی کامپیوتر', 'روزانه', 'آذربایجان شرقی', 'تبریز', 'تبریز، میدان آبرسان', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0091234562', '4012130082', 'محمدحسین نوری (متقاضی میهمانی)', '09362223344', 'پردیس مهارت', 'گرافیک و انیمیشن', 'روزانه', 'اصفهان', 'کاشان', 'کاشان، خیابان امیرکبیر', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6'),
('0091234563', '4012130083', 'سجاد موسوی (در انتظار اسکان)', '09373334455', 'پردیس فناوری', 'مهندسی برق صنعتی', 'شبانه (نوبت دوم)', 'خوزستان', 'دزفول', 'دزفول، خیابان شریعتی', 1, '$2y$10$tHvhI5v0lTqVqXfXj4mYlOq5bV5H/Z3L9dG1A/q5Z6aK2Y2E7sP/6');

-- ----------------------------------------------------------
-- ۳. جدول مهارت‌های دانشجو
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `student_skills`;
CREATE TABLE `student_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `student_skills` (`student_id`, `skill_name`) VALUES
('4012130045', 'پایتون (Python)'),
('4012130045', 'هوش مصنوعی'),
('4012130047', 'طراحی مدار الکترونیک');

-- ----------------------------------------------------------
-- ۴. جدول علاقه‌مندی دانشجو در جشنواره‌ها
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `student_festivals`;
CREATE TABLE `student_festivals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL,
  `festival_key` VARCHAR(50) NOT NULL,
  `festival_title` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `student_festivals` (`student_id`, `festival_key`, `festival_title`) VALUES
('4012130045', 'technical', 'جشنواره‌ها و المپیادهای فنی و اختراعات'),
('4012130045', 'robotics', 'مسابقات رباتیک و هوش مصنوعی'),
('4012130045', 'sports', 'جشنواره‌های ورزشی و مسابقات پردیس');

-- ----------------------------------------------------------
-- ۴.۱. جدول تیم‌های ورزشی ثبت‌نامی دانشجو
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `student_sports_teams`;
CREATE TABLE `student_sports_teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL,
  `team_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `student_sports_teams` (`student_id`, `team_name`) VALUES
('4012130045', 'تیم فوتبال دانشگاه'),
('4012130045', 'والیبال');

-- ----------------------------------------------------------
-- ۴.۲. جدول فراخوان‌ها و اطلاعیه‌های هدفمند جشنواره‌ها (صادرشده توسط مدیر)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `festival_calls`;
CREATE TABLE `festival_calls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `target_category` ENUM('all', 'sports', 'religious', 'robotics', 'cultural', 'technical', 'skillBased') NOT NULL DEFAULT 'all',
  `target_sports_team` VARCHAR(100) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `deadline_date` VARCHAR(30) NOT NULL,
  `issued_by` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `festival_calls` (`title`, `target_category`, `target_sports_team`, `content`, `deadline_date`, `issued_by`) VALUES
('فراخوان انتخابی تیم فوتبال دانشجویان برای المپیاد کشوری', 'sports', 'تیم فوتبال دانشگاه', 'از کلیه دانشجویان عضو تیم فوتبال دعوت می‌شود روز سه‌شنبه ساعت ۱۷ جهت تمرین انتخابی در زمین چمن پردیس حضور به هم رسانند.', '۱۴۰۳/۰۸/۱۰', 'مدیریت تربیت بدنی و امور خوابگاه‌ها'),
('ثبت‌نام مسابقات سراسری قرآن و عترت و تواشیح دانشگاهیان', 'religious', NULL, 'کلیه دانشجویان علاقه‌مند به جشنواره‌های مذهبی و قرآنی می‌توانند جهت ثبت‌نام به دفتر نهاد رهبری مراجعه فرمایند.', '۱۴۰۳/۰۸/۱۵', 'معاونت فرهنگی و دانشجویی'),
('آغاز اردوی تدارکاتی تیم والیبال پردیس', 'sports', 'والیبال', 'تمرینات فشرده تیم والیبال دانشگاه جهت اعزام به لیگ بین‌دانشگاهی از هفته آینده آغاز می‌گردد.', '۱۴۰۳/۰۸/۰۵', 'کمیته ورزش خوابگاه صمد');

-- ==========================================================
-- جداول جدید مدیریت خوابگاه و سرپرستی (مدیر دانشگاه و سرپرست خوابگاه)
-- ==========================================================

-- ----------------------------------------------------------
-- ۵. جدول کاربران ادمین (مدیر دانشگاه و سرپرست خوابگاه)
-- نیازی به ثبت نام ندارند و مستقیماً با کدملی و پسورد لاگین می‌کنند
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `national_id` VARCHAR(10) NOT NULL UNIQUE COMMENT 'کد ملی ادمین جهت ورود',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'کلمه عبور هش شده یا مستقیم',
  `full_name` VARCHAR(150) NOT NULL COMMENT 'نام و سمت',
  `role` ENUM('manager', 'supervisor') NOT NULL COMMENT 'manager: مدیر دانشگاه | supervisor: سرپرست خوابگاه',
  `shift_hours` VARCHAR(150) DEFAULT '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)',
  `shift_days` VARCHAR(150) DEFAULT 'شنبه تا چهارشنبه',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ثبت اطلاعات ادمین‌ها در دیتابیس:
-- ۱. مدیر دانشگاه: کدملی: 0011223344 / رمز: Admin@12345
-- ۲. سرپرست خوابگاه: کدملی: 0099887766 / رمز: Super@12345
INSERT INTO `admins` (`national_id`, `password_hash`, `full_name`, `role`) VALUES
('0011223344', '$2y$10$w3U6R3e.w4/cK/K0b.kQMe3fK3C9/E1rF3B5H7L9j1N3P5R7T9V1a', 'دکتر علیرضا حسینی (مدیر دانشگاه و امور رفاهی)', 'manager'),
('0099887766', '$2y$10$w3U6R3e.w4/cK/K0b.kQMe3fK3C9/E1rF3B5H7L9j1N3P5R7T9V1a', 'مهندس کامران قنبری (سرپرست شبانه خوابگاه صمد)', 'supervisor');

-- ----------------------------------------------------------
-- ۶. جدول اتاق‌های خوابگاه (ساختمان در طبقات همکف، اول و دوم)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `dorm_rooms`;
CREATE TABLE `dorm_rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(10) NOT NULL UNIQUE COMMENT 'شماره اتاق (مانند 001، 100، 101، 201)',
  `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL COMMENT 'طبقه استقرار اتاق',
  `capacity` INT NOT NULL DEFAULT 4 COMMENT 'ظرفیت کل اتاق (تعداد تخت)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: فعال و دایر | 0: غیرفعال و در دست تعمیر',
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: در انتظار تکمیل امکانات | 1: تکمیل‌شده',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'توضیحات (دارای بالکن، نزدیک راهرو و ...)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- نمونه اتاق‌های خوابگاه در سه طبقه (همکف، طبقه اول، طبقه دوم)
INSERT INTO `dorm_rooms` (`id`, `room_number`, `floor`, `capacity`, `is_active`, `is_completed`, `description`) VALUES
-- طبقه همکف
(1, 'G01', 'همکف', 4, 1, 1, 'اتاق ۴ تخته مجهز به یخچال - طبقه همکف'),
(2, 'G02', 'همکف', 4, 1, 1, 'اتاق ۴ تخته نورگیر - طبقه همکف'),
(3, 'G03', 'همکف', 4, 0, 0, 'اتاق در دست نقاشی و نوسازی تاسیسات (تکمیل‌نشده)'),
-- طبقه اول
(4, '100', 'طبقه اول', 10, 1, 1, 'اتاق نمونه ۱۰ نفره با ۱۳ اسکان‌یافته (شامل پذیرش مازاد بر ظرفیت)'),
(5, '101', 'طبقه اول', 4, 1, 1, 'اتاق ۴ تخته بالکن‌دار - طبقه اول'),
(6, '102', 'طبقه اول', 4, 1, 1, 'اتاق ۴ تخته ساکت و مناسب مطالعه - طبقه اول'),
(7, '103', 'طبقه اول', 6, 1, 1, 'اتاق ۶ تخته بزرگ ویژه نخبگان مهارتی - طبقه اول'),
(8, '104', 'طبقه اول', 4, 1, 0, 'اتاق ۴ تخته رو به حیاط پردیس (تکمیل‌نشده)'),
-- طبقه دوم
(9, '201', 'طبقه دوم', 4, 1, 1, 'اتاق ۴ تخته مجهز به سیستم تهویه - طبقه دوم'),
(10, '202', 'طبقه دوم', 4, 1, 1, 'اتاق ۴ تخته - طبقه دوم'),
(11, '203', 'طبقه دوم', 4, 0, 0, 'اتاق نیازمند تعمیرات سرمایشی (غیرفعال)'),
(12, '204', 'طبقه دوم', 4, 1, 0, 'اتاق ۴ تخته انتهای راهرو - در انتظار تکمیل اطلاعات');

-- ----------------------------------------------------------
-- ۶.۱. جدول موجودیت امکانات اتاق خوابگاه (امکانات اصلی و فرعی)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `room_facilities`;
CREATE TABLE `room_facilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL UNIQUE,
  `room_number` VARCHAR(10) NOT NULL,
  `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL,
  `refrigerator` VARCHAR(100) NOT NULL DEFAULT 'یخچال ۶ فوت استاندارد (سالم)',
  `wardrobe` VARCHAR(100) NOT NULL DEFAULT 'کمد ۴ درب اختصاصی دانشجویان (سالم)',
  `carpet` VARCHAR(100) NOT NULL DEFAULT 'فرش ۶ متری ماشینی (سالم)',
  `secondary_facilities` TEXT DEFAULT NULL COMMENT 'JSON یا متن امکانات فرعی مثل سطل آشغال، جارو، پرده، تلویزیون، اسپلیت...',
  `status` ENUM('تکمیل‌شده', 'در انتظار تکمیل') NOT NULL DEFAULT 'در انتظار تکمیل',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `room_facilities` (`room_id`, `room_number`, `floor`, `refrigerator`, `wardrobe`, `carpet`, `secondary_facilities`, `status`) VALUES
(1, 'G01', 'همکف', 'یخچال ۶ فوت امرسان', 'کمد ۴ درب چوبی', 'فرش ۶ متری ۱۲۰۰ شانه', '[{"name":"سطل زباله پدالی","count":1,"condition":"سالم"},{"name":"جارو نپتون و خاک‌انداز","count":1,"condition":"سالم"},{"name":"پرده حریر","count":2,"condition":"سالم"}]', 'تکمیل‌شده'),
(2, 'G02', 'همکف', 'یخچال ۵ فوت ایستکول', 'کمد ۴ درب فلزی', 'فرش ۶ متری ۷۰۰ شانه', '[{"name":"سطل زباله","count":1,"condition":"سالم"},{"name":"جارو دستی","count":1,"condition":"سالم"}]', 'تکمیل‌شده'),
(4, '100', 'طبقه اول', 'یخچال دوقلو ۱۲ فوت بزرگ ویژه اتاق ۱۰ نفره', 'کمد ۱۰ درب تفکیکی جادار', '۲ تخته فرش ۹ متری کاشان', '[{"name":"تلویزیون ۴۳ اینچ ال‌ای‌دی","count":1,"condition":"سالم"},{"name":"اسپلیت ۲۴ هزار ال‌جی","count":1,"condition":"سالم"},{"name":"سطل زباله بزرگ اداری","count":2,"condition":"سالم"},{"name":"جاروبرقی سطلی پردیس","count":1,"condition":"سالم"},{"name":"جاکفشی طبقاتی راهرو","count":1,"condition":"سالم"}]', 'تکمیل‌شده'),
(5, '101', 'طبقه اول', 'یخچال ۷ فوت اسنوا', 'کمد ۴ درب اختصاصی', 'فرش ۶ متری طرح افشان', '[{"name":"سطل زباله","count":1,"condition":"سالم"},{"name":"جارو نپتون","count":1,"condition":"سالم"},{"name":"بند رخت بالکن","count":1,"condition":"سالم"}]', 'تکمیل‌شده'),
(6, '102', 'طبقه اول', 'یخچال ۵ فوت فیلور', 'کمد ۴ درب چوبی', 'فرش ۶ متری مشهد', '[{"name":"سطل زباله فلزی","count":1,"condition":"سالم"},{"name":"چراغ مطالعه رومیزی","count":2,"condition":"سالم"}]', 'تکمیل‌شده'),
(7, '103', 'طبقه اول', 'یخچال ۹ فوت دونار', 'کمد ۶ درب ام‌دی‌اف نخبگان', 'فرش ۹ متری تبریز', '[{"name":"سطل زباله","count":1,"condition":"سالم"},{"name":"پرده زبرا","count":2,"condition":"سالم"}]', 'تکمیل‌شده'),
(9, '201', 'طبقه دوم', 'یخچال ۶ فوت امرسان', 'کمد ۴ درب', 'فرش ۶ متری', '[{"name":"سطل زباله","count":1,"condition":"سالم"},{"name":"اسپلیت سرمایشی","count":1,"condition":"سالم"}]', 'تکمیل‌شده'),
(10, '202', 'طبقه دوم', 'یخچال ۶ فوت هیمالیا', 'کمد ۴ درب', 'فرش ۶ متری', '[{"name":"سطل زباله","count":1,"condition":"سالم"}]', 'تکمیل‌شده');

-- ----------------------------------------------------------
-- ۷. جدول تخصیص اتاق به دانشجویان و وضعیت خوابگاه (میهمان، دارای شهریه، بدون شهریه)
-- با قابلیت پذیرش مازاد بر ظرفیت و اولویت‌بندی هوشمند اسکان
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `dorm_allocations`;
CREATE TABLE `dorm_allocations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL COMMENT 'شماره دانشجویی',
  `room_id` INT NOT NULL COMMENT 'اتاق تخصیص یافته',
  `dorm_status` ENUM('میهمان', 'دارای شهریه', 'بدون شهریه') NOT NULL DEFAULT 'بدون شهریه' COMMENT 'وضعیت اسکان دانشجو',
  `allocation_date` DATE NOT NULL COMMENT 'تاریخ اسکان',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'وضعیت سکونت جاری',
  `guest_days_total` INT DEFAULT NULL COMMENT 'تعداد کل روزهای مجاز اقامت میهمانی',
  `guest_days_remaining` INT DEFAULT NULL COMMENT 'تعداد روزهای باقی‌مانده از اقامت میهمانی',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE,
  INDEX `idx_alloc_room` (`room_id`),
  INDEX `idx_alloc_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- تخصیص دانشجویان نمونه به اتاق‌ها
INSERT INTO `dorm_allocations` (`student_id`, `room_id`, `dorm_status`, `allocation_date`, `is_active`, `guest_days_total`, `guest_days_remaining`, `created_at`) VALUES
('4012130045', 5, 'بدون شهریه', '2026-09-20', 1, NULL, NULL, '2026-09-20 08:00:00'),
('4012130047', 5, 'دارای شهریه', '2026-09-20', 1, NULL, NULL, '2026-09-20 08:30:00'),
('4012130049', 6, 'میهمان', '2026-09-22', 1, 7, 4, '2026-09-22 09:00:00'),
('4012130050', 1, 'بدون شهریه', '2026-09-21', 1, NULL, NULL, '2026-09-21 10:00:00'),
('4012130046', 9, 'بدون شهریه', '2026-09-21', 1, NULL, NULL, '2026-09-21 10:30:00'),
('4012130048', 10, 'دارای شهریه', '2026-09-21', 1, NULL, NULL, '2026-09-21 11:00:00'),

-- تخصیص سناریوی درخواست کاربر: اتاق ۱۰۰ با ظرفیت ۱۰ تخت و ۱۳ دانشجو
-- ۱۰ دانشجوی اول روزانه دارای تخت مصوب (به ترتیب زمان اسکان):
('4012130051', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 08:00:00'),
('4012130052', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 08:15:00'),
('4012130053', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 08:30:00'),
('4012130054', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 08:45:00'),
('4012130055', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 09:00:00'),
('4012130056', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 09:15:00'),
('4012130057', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 09:30:00'),
('4012130058', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 09:45:00'),
('4012130059', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 10:00:00'),
('4012130060', 4, 'بدون شهریه', '2026-09-10', 1, NULL, NULL, '2026-09-10 10:15:00'),
-- ۳ دانشجوی مازاد بر ظرفیت:
-- ۱. دانشجوی روزانه مازاد (اسکان‌یافته پس از تکمیل ظرفیت تخت‌ها با تاریخ دیرتر):
('4012130061', 4, 'بدون شهریه', '2026-09-15', 1, NULL, NULL, '2026-09-15 11:00:00'),
-- ۲. دانشجوی میهمان مازاد:
('4012130062', 4, 'میهمان', '2026-09-16', 1, 10, 8, '2026-09-16 12:00:00'),
-- ۳. دانشجوی شبانه مازاد:
('4012130063', 4, 'دارای شهریه', '2026-09-17', 1, NULL, NULL, '2026-09-17 14:00:00');

-- ----------------------------------------------------------
-- ۸. جدول سرشماری شبانه خوابگاه (توسط سرپرست خوابگاه)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `dorm_night_attendance`;
CREATE TABLE `dorm_night_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL COMMENT 'شماره دانشجویی',
  `room_id` INT NOT NULL COMMENT 'اتاق دانشجو در زمان سرشماری',
  `attendance_date` DATE NOT NULL COMMENT 'تاریخ سرشماری شبانه (مثال 2026-09-30)',
  `status` ENUM('حاضر', 'غایب', 'مرخصی') NOT NULL DEFAULT 'حاضر' COMMENT 'وضعیت حضور دانشجو',
  `supervisor_national_id` VARCHAR(10) NOT NULL COMMENT 'کدملی سرپرست ثبت کننده',
  `notes` VARCHAR(255) DEFAULT NULL COMMENT 'توضیحات سرپرست (مثال: تاخیر با اطلاع آموزش یا غیبت غیرموجه)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_date` (`student_id`, `attendance_date`),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `dorm_night_attendance` (`student_id`, `room_id`, `attendance_date`, `status`, `supervisor_national_id`, `notes`) VALUES
('4012130045', 5, CURDATE(), 'حاضر', '0099887766', 'سرشماری ساعت ۲۲:۳۰ - حضور در اتاق تایید شد'),
('4012130047', 5, CURDATE(), 'غایب', '0099887766', 'بدون برگه تردد شبانه تا ساعت ۲۳ حاضر نشد'),
('4012130049', 6, CURDATE(), 'مرخصی', '0099887766', 'دارای برگه مرخصی پایان هفته با تایید سرپرستی'),
('4012130050', 1, CURDATE(), 'حاضر', '0099887766', 'حاضر در اتاق G01 همکف');

-- ----------------------------------------------------------
-- ۹. جدول اطلاع‌رسانی خرابی و درخواست تعمیر/تعویض تجهیزات خوابگاه
-- شامل اطلاعات کامل مخارج، فاکتورها، پیمانکار، و زمان دقیق ثبت و رفع اشکال
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `maintenance_reports`;
CREATE TABLE `maintenance_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL COMMENT 'شناسه اتاق مربوطه',
  `room_number` VARCHAR(10) NOT NULL COMMENT 'شماره اتاق',
  `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL COMMENT 'طبقه اتاق',
  `equipment_name` VARCHAR(150) NOT NULL COMMENT 'نام قطعه یا تجهیز (یخچال، کمد، فرش، تخت، شوفاژ، تلویزیون، اسپلیت...)',
  `maintenance_type` ENUM('تعمیر', 'تعویض / خرید جدید') NOT NULL DEFAULT 'تعمیر',
  `urgency` ENUM('فوری', 'عادی', 'کم‌اهمیت') NOT NULL DEFAULT 'عادی',
  `status` ENUM('در انتظار اقدام', 'در حال تعمیر', 'رفع‌شده / تعمیر شد', 'رفع‌شده / خریداری و تعویض شد') NOT NULL DEFAULT 'در انتظار اقدام',
  `description` TEXT NOT NULL COMMENT 'شرح خرابی ثبت‌شده توسط سرپرست',
  `reported_by_supervisor_id` VARCHAR(20) NOT NULL COMMENT 'کد سرپرست گزارش‌کننده',
  `reported_by_supervisor_name` VARCHAR(150) NOT NULL COMMENT 'نام سرپرست گزارش‌کننده',
  `reported_at` VARCHAR(50) NOT NULL COMMENT 'تاریخ و زمان دقیق ثبت گزارش خرابی',
  `resolved_at` VARCHAR(50) DEFAULT NULL COMMENT 'تاریخ و زمان رفع عیب توسط مدیر',
  `resolved_by_manager_name` VARCHAR(150) DEFAULT NULL COMMENT 'نام مدیر رفع‌کننده اشکال',
  `resolution_action` ENUM('تعمیر قطعه', 'خرید و جایگزینی تجهیز نو') DEFAULT NULL,
  `resolution_cost` INT DEFAULT NULL COMMENT 'هزینه انجام شده به ریال',
  `invoice_number` VARCHAR(50) DEFAULT NULL COMMENT 'شماره فاکتور مالی رسمی',
  `contractor_or_store_name` VARCHAR(150) DEFAULT NULL COMMENT 'فروشگاه یا پیمانکار طرف حساب',
  `resolution_notes` TEXT DEFAULT NULL COMMENT 'توضیحات و گزارش فاکتور مدیر',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `maintenance_reports` (`room_id`, `room_number`, `floor`, `equipment_name`, `maintenance_type`, `urgency`, `status`, `description`, `reported_by_supervisor_id`, `reported_by_supervisor_name`, `reported_at`, `resolved_at`, `resolved_by_manager_name`, `resolution_action`, `resolution_cost`, `invoice_number`, `contractor_or_store_name`, `resolution_notes`) VALUES
(4, '100', 'طبقه اول', 'تخت طبقه دوم شماره ۳', 'تعمیر', 'فوری', 'در انتظار اقدام', 'جوش پایه نردبان تخت فلزی شل شده و برای بالا رفتن دانشجو خطرآفرین است.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۵ - ۲۲:۱۵', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '101', 'طبقه اول', 'دستگیره پنجره بالکن', 'تعمیر', 'عادی', 'در انتظار اقدام', 'دستگیره دوجداره لق شده و به درستی قفل نمی‌شود.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۴ - ۲۲:۳۰', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '201', 'طبقه دوم', 'رادیاتور شوفاژ اتاق', 'تعمیر', 'فوری', 'در حال تعمیر', 'نشتی جزئی آب از شیر رفت شوفاژ؛ نیازمند آچارکشی و تعویض واشر.', 'SUP-405-02', 'حسین مرادی', '۱۴۰۳/۰۷/۱۴ - ۲۲:۴۵', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 'G01', 'همکف', 'ترموستات یخچال امرسان', 'تعمیر', 'عادی', 'رفع‌شده / تعمیر شد', 'موتور یخچال یکسره کار می‌کرد و دمای داخل بیش از حد سرد بود.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۱۰ - ۲۱:۰۰', '۱۴۰۳/۰۷/۱۱ - ۱۰:۳۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'تعمیر قطعه', 3500000, 'INV-1403-1001', 'تعمیرگاه مجاز لوازم خانگی پردیس', 'سرویسکار مجاز فراخوانده شد و رله استارت و ترموستات تنظیم و تعویض گردید.'),
(6, '102', 'طبقه اول', 'قفل درب کمد دیواری', 'تعمیر', 'عادی', 'رفع‌شده / تعمیر شد', 'مغزی قفل کمد گیر کرده بود و کلید نمی‌چرخید.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۰۸ - ۲۳:۰۰', '۱۴۰۳/۰۷/۰۹ - ۱۱:۰۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'تعمیر قطعه', 1800000, 'INV-1403-1002', 'ابزار و یراق پردیس مرکزی', 'مغزی قفل کمد تعویض و کلید جدید به سرپرست تحویل شد.'),
(7, '103', 'طبقه اول', 'مهتابی ال‌ای‌دی سقف', 'تعویض / خرید جدید', 'عادی', 'رفع‌شده / خریداری و تعویض شد', 'ترانس لامپ مهتابی سوخته بود و روشنایی کافی نداشت.', 'SUP-405-01', 'مهندس کامران قنبری', '۱۴۰۳/۰۷/۰۵ - ۲۲:۰۰', '۱۴۰۳/۰۷/۰۶ - ۰۹:۳۰', 'دکتر علیرضا حسینی (مدیر امور رفاهی)', 'خرید و جایگزینی تجهیز نو', 2400000, 'INV-1403-1003', 'کالای برق و روشنایی نوین', 'پنل ال‌ای‌دی ۶۰ در ۶۰ پرنور نو نصب و جایگزین گردید.');

-- ----------------------------------------------------------
-- ۱۰. جدول ارزیابی شبانه نظافت خوابگاه‌ها (۴ سطحی: عالی، خوب، متوسط، ضعیف)
-- جهت ارزیابی‌های روز به روز و رتبه‌بندی عملکرد هفتگی و ماهانه
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `room_cleanliness_records`;
CREATE TABLE `room_cleanliness_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL COMMENT 'شناسه اتاق',
  `room_number` VARCHAR(10) NOT NULL COMMENT 'شماره اتاق',
  `floor` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL COMMENT 'طبقه اتاق',
  `inspection_date` VARCHAR(20) NOT NULL COMMENT 'تاریخ سرشماری شبانه نظافت (مثال ۱۴۰۳/۰۷/۱۲)',
  `grade` ENUM('عالی', 'خوب', 'متوسط', 'ضعیف') NOT NULL DEFAULT 'خوب' COMMENT 'کیفیت نظافت اتاق در ۴ سطح',
  `supervisor_national_id` VARCHAR(10) NOT NULL COMMENT 'کدملی سرپرست ثبت‌کننده',
  `supervisor_name` VARCHAR(150) NOT NULL COMMENT 'نام سرپرست ارزیاب',
  `notes` TEXT DEFAULT NULL COMMENT 'توضیحات و تذکرات سرپرست',
  `recorded_at` VARCHAR(50) NOT NULL COMMENT 'زمان دقیق ثبت بازرسی',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `room_cleanliness_records` (`room_id`, `room_number`, `floor`, `inspection_date`, `grade`, `supervisor_national_id`, `supervisor_name`, `notes`, `recorded_at`) VALUES
-- ارزیابی‌های تاریخ ۱۴۰۳/۰۷/۱۴
(4, '100', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'نظافت استثنایی اتاق ۱۰ نفره، کف بسیار تمیز و مرتب، تهویه عالی', '۱۴۰۳/۰۷/۱۴ - ۲۳:۳۰'),
(5, '101', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'تخت‌های مرتب و آنکادر، یخچال بسیار پاکیزه', '۱۴۰۳/۰۷/۱۴ - ۲۳:۳۵'),
(6, '102', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'خوب', '0099887766', 'مهندس کامران قنبری', 'مرتب و منظم، میز مطالعه کمی شلوغ', '۱۴۰۳/۰۷/۱۴ - ۲۳:۴۰'),
(7, '103', 'طبقه اول', '۱۴۰۳/۰۷/۱۴', 'عالی', '0099887766', 'مهندس کامران قنبری', 'اتاق نخبگان بسیار منضبط و پاکیزه', '۱۴۰۳/۰۷/۱۴ - ۲۳:۴۵'),
(9, '201', 'طبقه دوم', '۱۴۰۳/۰۷/۱۴', 'متوسط', '0099887766', 'مهندس کامران قنبری', 'نیاز به شستشوی ظروف سینک و جمع‌آوری لباس‌ها', '۱۴۰۳/۰۷/۱۴ - ۲۳:۵۰'),
(10, '202', 'طبقه دوم', '۱۴۰۳/۰۷/۱۴', 'ضعیف', '0099887766', 'مهندس کامران قنبری', 'انباشت زباله در سطل، تذکر کتبی به ساکنین اتاق داده شد', '۱۴۰۳/۰۷/۱۴ - ۲۳:۵۵'),
(1, 'G01', 'همکف', '۱۴۰۳/۰۷/۱۴', 'خوب', '0099887766', 'مهندس کامران قنبری', 'کف تی کشیده شده و اتاق آماده است', '۱۴۰۳/۰۷/۱۵ - ۰۰:۰۰'),
-- ارزیابی‌های تاریخ ۱۴۰۳/۰۷/۱۳
(4, '100', 'طبقه اول', '۱۴۰۳/۰۷/۱۳', 'عالی', '0099887766', 'مهندس کامران قنبری', 'چیدمان فوق‌العاده و تمیز', '۱۴۰۳/۰۷/۱۳ - ۲۳:۳۰'),
(5, '101', 'طبقه اول', '۱۴۰۳/۰۷/۱۳', 'عالی', '0099887766', 'مهندس کامران قنبری', 'بسیار تمیز', '۱۴۰۳/۰۷/۱۳ - ۲۳:۳۵'),
(6, '102', 'طبقه اول', '۱۴۰۳/۰۷/۱۳', 'خوب', '0099887766', 'مهندس کامران قنبری', 'خوب و بدون مشکل', '۱۴۰۳/۰۷/۱۳ - ۲۳:۴۰'),
(7, '103', 'طبقه اول', '۱۴۰۳/۰۷/۱۳', 'عالی', '0099887766', 'مهندس کامران قنبری', 'عالی', '۱۴۰۳/۰۷/۱۳ - ۲۳:۴۵'),
(9, '201', 'طبقه دوم', '۱۴۰۳/۰۷/۱۳', 'خوب', '0099887766', 'مهندس کامران قنبری', 'بهبود نسبت به قبل', '۱۴۰۳/۰۷/۱۳ - ۲۳:۵۰'),
(10, '202', 'طبقه دوم', '۱۴۰۳/۰۷/۱۳', 'ضعیف', '0099887766', 'مهندس کامران قنبری', 'همچنان سطل تخلیه نشده است', '۱۴۰۳/۰7/۱۳ - ۲۳:۵۵'),
(1, 'G01', 'همکف', '۱۴۰۳/۰۷/۱۳', 'خوب', '0099887766', 'مهندس کامران قنبری', 'مرتب', '۱۴۰۳/۰۷/۱۴ - ۰۰:۰۰'),
-- ارزیابی‌های تاریخ ۱۴۰۳/۰۷/۱۲
(4, '100', 'طبقه اول', '۱۴۰۳/۰۷/۱۲', 'عالی', '0099887766', 'مهندس کامران قنبری', 'اتاق نمونه با کیفیت بسیار بالا', '۱۴۰۳/۰۷/۱۲ - ۲۳:۳۰'),
(5, '101', 'طبقه اول', '۱۴۰۳/۰۷/۱۲', 'عالی', '0099887766', 'مهندس کامران قنبری', 'نظم بسیار عالی تخت‌ها', '۱۴۰۳/۰۷/۱۲ - ۲۳:۴۰'),
(6, '102', 'طبقه اول', '۱۴۰۳/۰۷/۱۲', 'خوب', '0099887766', 'مهندس کامران قنبری', 'وضعیت عمومی خوب', '۱۴۰۳/۰۷/۱۲ - ۲۳:۴۵'),
(7, '103', 'طبقه اول', '۱۴۰۳/۰۷/۱۲', 'عالی', '0099887766', 'مهندس کامران قنبری', 'اتاق نمونه نخبگان', '۱۴۰۳/۰۷/۱۲ - ۲۳:۵۰'),
(9, '201', 'طبقه دوم', '۱۴۰۳/۰۷/۱۲', 'متوسط', '0099887766', 'حسین مرادی', 'تذکر شفاهی بابت سینک', '۱۴۰۳/۰۷/۱۳ - ۰۰:۱۰'),
(10, '202', 'طبقه دوم', '۱۴۰۳/۰۷/۱۲', 'ضعیف', '0099887766', 'حسین مرادی', 'انباشت کیسه‌های زباله پشت درب', '۱۴۰۳/۰۷/۱۳ - ۰۰:۲۰'),
(1, 'G01', 'همکف', '۱۴۰۳/۰۷/۱۲', 'خوب', '0099887766', 'مهندس کامران قنبری', 'کف تی کشیده شده', '۱۴۰۳/۰۷/۱۲ - ۲۳:۳۰');

-- ==========================================================
-- جدول فراخوان‌های هدفمند جشنواره‌ها و مسابقات (festival_calls)
-- ==========================================================
CREATE TABLE IF NOT EXISTS `festival_calls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `target_category` ENUM('all', 'sports', 'religious', 'robotics', 'cultural', 'technical', 'skillBased') NOT NULL DEFAULT 'all',
  `target_sports_team` VARCHAR(100) DEFAULT NULL,
  `target_skill` VARCHAR(100) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `start_date` VARCHAR(30) DEFAULT NULL COMMENT 'تاریخ شروع بازه فراخوان',
  `deadline_date` VARCHAR(30) NOT NULL COMMENT 'تاریخ و مهلت پایان فراخوان',
  `issued_by` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `festival_calls` (`title`, `target_category`, `target_sports_team`, `target_skill`, `content`, `start_date`, `deadline_date`, `issued_by`, `is_active`) VALUES
('فراخوان عضویت و انتخابی تیم فوتبال دانشجویان در مسابقات منطقه‌ای', 'sports', 'فوتبال', 'فوتبال چمنی', 'از کلیه دانشجویان عضو تیم فوتبال و علاقه‌مندان جهت شرکت در تمرینات انتخابی مسابقات منطقه‌ای دعوت به عمل می‌آید.', '۱۴۰۳/۰۷/۱۵', '۱۴۰۳/۰۸/۰۵', 'دکتر علیرضا حسینی (مدیریت امور رفاهی و دانشجویی)', 1),
('فراخوان دومین المپیاد ملی مهارت‌آموزی و رباتیک دانشجویی', 'robotics', NULL, 'برنامه‌نویسی و ساخت ربات', 'ثبت‌نام متقاضیان رقابت‌های ملی ساخت ربات‌های صنعتی و هوش مصنوعی آغاز شد. جهت هماهنگی با کارشناس پردیس مراجعه فرمایید.', '۱۴۰۳/۰۷/۲۰', '۱۴۰۳/۰۸/۲۰', 'معاونت پژوهش و فناوری پردیس', 1);

-- ==========================================================
-- ۱۱. جداول مدیریت دسترسی و احراز هویت مبتنی بر JWT و RBAC
-- ==========================================================
CREATE TABLE IF NOT EXISTS `auth_settings` (
  `setting_key` VARCHAR(80) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('student', 'supervisor', 'manager') NOT NULL,
  `permission_key` VARCHAR(80) NOT NULL,
  `permission_label` VARCHAR(150) NOT NULL,
  `is_allowed` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_role_perm` (`role`, `permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`) VALUES
('student', 'dorm:room:view_self', 'مشاهده پرونده اسکان و کارت شناسایی خود', 1),
('student', 'dorm:graph:view_self', 'مشاهده گراف تعاملی اتاق و هم‌اتاقی‌ها', 1),
('student', 'welfare:book', 'رزرو سانس سالن بدنسازی و مدیریت کیف پول', 1),
('student', 'student:profile:edit', 'ویرایش شماره تماس و کلمه عبور دانشجویی', 1),
('supervisor', 'dorm:attendance:manage', 'ثبت و مدیریت سرشماری شبانه دانشجویان', 1),
('supervisor', 'dorm:cleanliness:manage', 'ارزیابی شب به شب کیفیت نظافت اتاق‌ها', 1),
('supervisor', 'dorm:maintenance:report', 'ثبت گزارش خرابی و درخواست تعمیر تجهیزات', 1),
('supervisor', 'dorm:facilities:view', 'مشاهده شناسنامه امکانات اتاق‌ها', 1),
('manager', 'dorm:graph:view_all', 'مشاهده گراف جامع کل ساختمان، طبقات و ساکنان', 1),
('manager', 'dorm:rooms:manage', 'مدیریت اتاق‌ها، طبقات و ظرفیت خوابگاه', 1),
('manager', 'dorm:allocations:manage', 'اسکان دانشجویان، تغییر اتاق و میهمانی', 1),
('manager', 'dorm:attendance:manage', 'نظارت و چاپ گزارش‌های سرشماری شبانه', 1),
('manager', 'dorm:cleanliness:manage', 'نظارت بر رتبه‌بندی نظافت خوابگاه', 1),
('manager', 'dorm:maintenance:resolve', 'رسیدگی به خرابی‌ها و ثبت فاکتور تعمیرات', 1),
('manager', 'welfare:manage', 'مدیریت خدمات رفاهی و سانس‌های ورزشی', 1),
('manager', 'auth:tokens:manage', 'مدیریت توکن‌های JWT، ابطال نشست و ماتریس دسترسی', 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- ۱۲. جداول ماژول نقشه مهارت‌ها و علایق دانشجو (University Graph)
-- ==========================================================
INSERT IGNORE INTO `auth_role_permissions` (`role`, `permission_key`, `permission_label`, `is_allowed`) VALUES
('student', 'skills:graph:view_self', 'مشاهده نقشه تعاملی مهارت‌ها و علایق دانشجو', 1),
('student', 'skills:graph:manage_self', 'افزودن، ویرایش و چیدمان مهارت‌ها در گراف شخصی', 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT IGNORE INTO `ug_domains` (`id`, `title`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_active`) VALUES
('domain-tech', 'فناوری و برنامه‌نویسی', 'technology', 'توسعه نرم‌افزار، هوش مصنوعی، شبکه، پایگاه داده و وب', 'code', '#06b6d4', 1, 1),
('domain-edu', 'علمی و آموزشی', 'education', 'علوم پایه، دروس تخصصی، ریاضیات، فیزیک و زبان‌های خارجه', 'book', '#6366f1', 2, 1),
('domain-research', 'پژوهش و نوآوری', 'research', 'مقالات علمی، ثبت اختراع، پروژه‌های دانش‌بنیان و آزمایشگاهی', 'lightbulb', '#10b981', 3, 1),
('domain-sport', 'ورزش و تندرستی', 'sport', 'رشته‌های ورزشی تیمی و انفرادی، آمادگی جسمانی و مسابقات', 'activity', '#ef4444', 4, 1),
('domain-culture', 'فرهنگی و رسانه', 'culture', 'تولید محتوا، نشریات دانشجویی، عکاسی، فیلم و فعالیت‌های عمومی', 'palette', '#f59e0b', 5, 1),
('domain-art', 'هنر و طراحی', 'art', 'طراحی گرافیک، معماری، موسیقی، تدوین و هنرهای تجسمی', 'pen', '#ec4899', 6, 1),
('domain-quran', 'عترت و قرآن', 'quran', 'قرائت، حفظ، مفاهیم قرآنی و فعالیت‌های دینی و معارفی', 'bookmark', '#8b5cf6', 7, 1),
('domain-workshop', 'مهارت‌های فنی و کارگاهی', 'workshop', 'الکترونیک، ساخت و تولید، بردهای سخت‌افزاری و مکاترونیک', 'wrench', '#f97316', 8, 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

CREATE TABLE IF NOT EXISTS `ug_skill_domains` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_id` VARCHAR(80) NOT NULL,
  `student_domain_id` VARCHAR(80) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_ug_skill_dom` (`skill_id`, `student_domain_id`),
  INDEX `idx_ug_skdom_skill` (`skill_id`),
  INDEX `idx_ug_skdom_dom` (`student_domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

CREATE TABLE IF NOT EXISTS `ug_graph_layouts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL,
  `graph_type` VARCHAR(40) NOT NULL DEFAULT 'student_skills',
  `layout_version` INT NOT NULL DEFAULT 1,
  `layout_data` LONGTEXT NOT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_ug_layout_student` (`student_id`, `graph_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
