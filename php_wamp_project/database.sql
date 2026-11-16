-- ==========================================================
-- اسکریپت جامع پایگاه‌داده سامانه صمد (مخصوص WampServer و phpMyAdmin)
-- شامل: جدول منبع، دانشجویان، مهارت‌ها، جشنواره‌ها، ادمین‌ها (مدیر و سرپرست)،
-- اتاق‌های خوابگاه در طبقات (همکف، اول، دوم)، تخصیص اتاق و وضعیت شهریه، و سرشماری شبانه
-- Encoding: UTF-8 Unicode (utf8mb4)
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

-- جدول منبع پذیرفته‌شدگان بدون داده نمونه (آماده ورود اطلاعات واقعی)


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
  `wallet_balance` BIGINT NOT NULL DEFAULT 250000 COMMENT 'موجودی کیف پول الکترونیک به ریال',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_national_id` (`national_id`),
  INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول دانشجویان ثبت‌نام‌شده بدون داده نمونه


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



-- ----------------------------------------------------------
-- ۴.۲. جدول فراخوان‌ها و اطلاعیه‌های هدفمند جشنواره‌ها (صادرشده توسط مدیر)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `festival_calls`;
CREATE TABLE `festival_calls` (
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
  `national_id` VARCHAR(30) NOT NULL UNIQUE COMMENT 'شناسه یا کد ملی ادمین جهت ورود',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'کلمه عبور هش شده یا مستقیم',
  `full_name` VARCHAR(150) NOT NULL COMMENT 'نام و سمت',
  `role` ENUM('manager', 'supervisor') NOT NULL COMMENT 'manager: مدیر دانشگاه | supervisor: سرپرست خوابگاه',
  `created_by_national_id` VARCHAR(30) DEFAULT NULL COMMENT 'شناسه مدیر ایجادکننده کاربر',
  `shift_hours` VARCHAR(150) DEFAULT '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)',
  `shift_days` VARCHAR(150) DEFAULT 'شنبه تا چهارشنبه',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ثبت صرفاً یک کاربر ادمین ارشد پایه سامانه:
-- نام کاربری: Admin / رمز: Admin@12345
INSERT INTO `admins` (`national_id`, `password_hash`, `full_name`, `role`) VALUES
('Admin', '$2y$10$r9xL0mN2pQ4sT6vW8yZ0aO1bC3dE5fG7hI9jK1lM3nO5pQ7rS9tU2', 'مدیر ارشد سامانه (Admin)', 'manager');

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

-- جدول اتاق‌های خوابگاه بدون داده نمونه


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
  `secondary_facilities` TEXT DEFAULT NULL COMMENT 'JSON یا متن امکانات فرعی مثل سطل آشغال، جارو، پرده، تلویزیون، اسپلیت...',
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('تکمیل‌شده', 'در انتظار تکمیل') NOT NULL DEFAULT 'در انتظار تکمیل',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;



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
  `guest_start_date` DATE DEFAULT NULL COMMENT 'تاریخ شروع اقامت میهمان',
  `guest_end_date` DATE DEFAULT NULL COMMENT 'تاریخ پایان اقامت میهمان',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE,
  INDEX `idx_alloc_room` (`room_id`),
  INDEX `idx_alloc_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول تخصیص اتاق بدون داده نمونه


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
  `status` VARCHAR(100) NOT NULL DEFAULT 'در انتظار اقدام',
  `description` TEXT NOT NULL COMMENT 'شرح خرابی ثبت‌شده توسط سرپرست',
  `reported_by_supervisor_id` VARCHAR(20) NOT NULL COMMENT 'کد سرپرست گزارش‌کننده',
  `reported_by_supervisor_name` VARCHAR(150) NOT NULL COMMENT 'نام سرپرست گزارش‌کننده',
  `reported_at` VARCHAR(50) NOT NULL COMMENT 'تاریخ و زمان دقیق ثبت گزارش خرابی',
  `resolved_at` VARCHAR(50) DEFAULT NULL COMMENT 'تاریخ و زمان رفع عیب توسط مدیر',
  `resolved_by_manager_name` VARCHAR(150) DEFAULT NULL COMMENT 'نام مدیر رفع‌کننده اشکال',
  `resolution_action` VARCHAR(100) DEFAULT NULL,
  `resolution_cost` BIGINT DEFAULT NULL COMMENT 'هزینه انجام شده به ریال',
  `cost_rials` BIGINT DEFAULT NULL COMMENT 'هزینه پرداختی به ریال',
  `invoice_number` VARCHAR(50) DEFAULT NULL COMMENT 'شماره فاکتور مالی رسمی',
  `contractor_or_store_name` VARCHAR(150) DEFAULT NULL COMMENT 'فروشگاه یا پیمانکار طرف حساب',
  `resolution_notes` TEXT DEFAULT NULL COMMENT 'توضیحات و گزارش فاکتور مدیر',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;



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



-- (جدول festival_calls در بخش ۴.۲ فوق با تمامی ستون‌ها تعریف شده است)

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
('student', 'dorm:card:view_self', 'مشاهده و چاپ کارت هوشمند دانشجویی', 1),
('student', 'dorm:room:view_self', 'مشاهده پرونده اسکان، هم‌اتاقی‌ها و گواهی سکونت', 1),
('student', 'dorm:graph:view_self', 'مشاهده گراف تعاملی اتاق و هم‌اتاقی‌ها', 1),
('student', 'dorm:guest_request:submit', 'ثبت و پیگیری درخواست اقامت میهمان در خوابگاه', 1),
('student', 'student:festivals:view', 'مشاهده تب مهارت‌ها، تیم‌های ورزشی و فراخوان‌ها', 1),
('student', 'skills:graph:view_self', 'مشاهده نقشه تعاملی مهارت‌ها و علایق دانشجو', 1),
('student', 'skills:graph:manage_self', 'افزودن، ویرایش و چیدمان مهارت‌ها در گراف شخصی', 1),
('student', 'welfare:book', 'مشاهده خدمات رفاهی، شارژ کیف پول و رزرو سالن بدنسازی', 1),
('student', 'student:profile:edit', 'ویرایش شماره تماس و کلمه عبور دانشجویی', 1),
('supervisor', 'dorm:attendance:manage', 'ثبت و مدیریت سرشماری شبانه دانشجویان', 1),
('supervisor', 'dorm:cleanliness:manage', 'ارزیابی شب به شب کیفیت نظافت اتاق‌ها', 1),
('supervisor', 'dorm:maintenance:report', 'ثبت گزارش خرابی و درخواست تعمیر تجهیزات', 1),
('supervisor', 'dorm:facilities:view', 'مشاهده شناسنامه امکانات اتاق‌ها', 1),
('supervisor', 'dorm:guest_request:view', 'مشاهده درخواست‌های اقامت میهمان دانشجویان (فقط خواندنی)', 1),
('manager', 'dorm:overview:view', 'پیشخوان، آمار کلی و وضعیت طبقات', 1),
('manager', 'dorm:admitted:manage', 'پذیرش دانشجوی جدید در جدول منبع', 1),
('manager', 'dorm:unhoused:manage', 'مدیریت متقاضیان خوابگاه در صف اسکان', 1),
('manager', 'dorm:student:purge', 'حذف کلی دانشجوی متقاضی خوابگاه از تمامی جداول و سامانه', 1),
('manager', 'dorm:guest_request:manage', 'بررسی، تایید یا رد درخواست‌های اقامت میهمان دانشجویان', 1),
('manager', 'dorm:rooms:manage', 'مدیریت اتاق‌ها، طبقات و ظرفیت خوابگاه', 1),
('manager', 'dorm:graph:view_all', 'مشاهده گراف جامع کل ساختمان، طبقات و ساکنان', 1),
('manager', 'dorm:facilities:manage', 'تکمیل شناسنامه امکانات و تجهیزات اتاق‌ها', 1),
('manager', 'dorm:maintenance:resolve', 'رسیدگی به خرابی‌ها و ثبت فاکتور تعمیرات', 1),
('manager', 'dorm:cleanliness:manage', 'نظارت بر رتبه‌بندی نظافت و خروجی اکسل', 1),
('manager', 'dorm:allocations:manage', 'دانشجویان ساکن، تغییر اتاق و میهمانی', 1),
('manager', 'dorm:student_panel:impersonate', 'ورود از کارت دانشجویی به پنل دانشجو (با حفظ نشست مدیر)', 1),
('manager', 'dorm:supervisor_panel:impersonate', 'ورود از کارت سرپرست به پنل سرپرست شبانه (با حفظ نشست مدیر)', 1),
('manager', 'dorm:attendance:manage', 'سرشماری شبانه، چاپ و خروجی اکسل', 1),
('manager', 'dorm:supervisors:manage', 'مدیریت سرپرستان، شیفت‌ها و کاربران مدیر', 1),
('manager', 'dorm:festivals:manage', 'جشنواره‌ها، داوطلبان و صدور فراخوان', 1),
('manager', 'dorm:skills:view', 'مشاهده بانک مهارت‌ها و استعدادیابی', 1),
('manager', 'welfare:manage', 'مدیریت خدمات رفاهی و سانس‌های ورزشی', 1),
('manager', 'auth:tokens:manage', 'مدیریت توکن‌های JWT، ابطال نشست و ماتریس دسترسی', 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `auth_student_action_limits`
(`action_key`, `action_label`, `permission_key`, `category`, `max_count`, `window_days`, `is_limit_enabled`, `sort_order`) VALUES
('student:profile:change_mobile', 'تغییر شماره تلفن همراه دانشجو', 'student:profile:edit', 'پروفایل و هویت', 1, 30, 1, 10),
('student:profile:change_password', 'تغییر کلمه عبور حساب دانشجویی', 'student:profile:edit', 'پروفایل و هویت', 2, 30, 1, 20),
('dorm:guest_request:submit', 'ثبت درخواست اقامت میهمان در خوابگاه', 'dorm:guest_request:submit', 'خوابگاه و اسکان', 2, 30, 1, 30),
('dorm:card:print', 'چاپ رسمی کارت هوشمند دانشجویی (A4)', 'dorm:card:view_self', 'خوابگاه و اسکان', 5, 30, 1, 40),
('dorm:certificate:print', 'چاپ گواهی رسمی سکونت در خوابگاه (A4)', 'dorm:room:view_self', 'خوابگاه و اسکان', 3, 30, 1, 50),
('dorm:graph:view_self', 'مشاهده و بارگذاری گراف تعاملی اتاق و هم‌اتاقی‌ها', 'dorm:graph:view_self', 'خوابگاه و اسکان', 60, 1, 1, 60),
('welfare:wallet_charge', 'شارژ آنلاین کیف پول الکترونیک', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 5, 7, 1, 70),
('welfare:book_gym', 'رزرو سانس سالن بدنسازی', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 4, 7, 1, 80),
('welfare:gym_ticket:print', 'چاپ بلیت دیجیتال سالن بدنسازی (A4)', 'welfare:book', 'خدمات رفاهی و سالن بدنسازی', 10, 7, 1, 90),
('skills:graph:view_self', 'مشاهده و بارگذاری نقشه گراف مهارت‌ها', 'skills:graph:view_self', 'گراف مهارت‌ها و علایق', 60, 1, 1, 100),
('skills:graph:attach_domain', 'افزودن حوزه رسمی دانشگاه به گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 10, 30, 1, 110),
('skills:graph:detach_domain', 'حذف / جدا کردن حوزه از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 10, 30, 1, 120),
('skills:graph:create_node', 'ایجاد باکس جدید (مهارت / علاقه / دستاورد / رویداد)', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 15, 7, 1, 130),
('skills:graph:update_node', 'ویرایش مشخصات باکس در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 140),
('skills:graph:delete_node', 'حذف باکس شخصی از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 15, 7, 1, 150),
('skills:graph:create_edge', 'ایجاد خط ارتباطی جدید در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 160),
('skills:graph:update_edge', 'ویرایش خط ارتباطی در گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 30, 7, 1, 170),
('skills:graph:delete_edge', 'حذف خط ارتباطی از گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 20, 7, 1, 180),
('skills:graph:save_layout', 'ذخیره چیدمان و مختصات گراف مهارت‌ها', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 100, 1, 1, 190),
('skills:graph:reset', 'بازنشانی کامل گراف مهارت‌ها به حالت پیش‌فرض', 'skills:graph:manage_self', 'گراف مهارت‌ها و علایق', 2, 30, 1, 200),
('auth:jwt:refresh', 'تمدید و چرخش توکن JWT نشست دانشجو', 'student:profile:edit', 'امنیت و نشست JWT', 10, 1, 1, 210);

CREATE TABLE IF NOT EXISTS `auth_student_action_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL,
  `action_key` VARCHAR(80) NOT NULL,
  `detail` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sal_student_action_time` (`student_id`, `action_key`, `created_at`),
  INDEX `idx_sal_action_time` (`action_key`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- ۱۲. جداول ماژول نقشه مهارت‌ها و علایق دانشجو (University Graph)
-- ==========================================================


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

-- ==========================================================
-- ۱۳. جداول خدمات رفاهی و نوبت‌دهی سالن بدنسازی (Welfare & Gym)
-- ==========================================================
CREATE TABLE IF NOT EXISTS `welfare_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'gym',
  `location` VARCHAR(150) NOT NULL DEFAULT 'زیرزمین خوابگاه مرکزی صمد',
  `hourly_rate_rials` BIGINT NOT NULL DEFAULT 50000,
  `slot_duration_hours` INT NOT NULL DEFAULT 2,
  `capacity_per_slot` INT NOT NULL DEFAULT 20,
  `rules_guidelines` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;



CREATE TABLE IF NOT EXISTS `welfare_service_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT NOT NULL,
  `slot_date_jalali` VARCHAR(20) NOT NULL,
  `slot_date_gregorian` DATE NOT NULL,
  `day_name_fa` VARCHAR(30) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `duration_hours` INT NOT NULL DEFAULT 2,
  `total_capacity` INT NOT NULL DEFAULT 20,
  `booked_count` INT NOT NULL DEFAULT 0,
  `is_team_reserved` TINYINT(1) NOT NULL DEFAULT 0,
  `reserved_team_name` VARCHAR(100) DEFAULT NULL,
  `team_notes` VARCHAR(255) DEFAULT NULL,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `block_reason` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_slot_date` (`slot_date_gregorian`),
  INDEX `idx_slot_jalali` (`slot_date_jalali`),
  INDEX `idx_slot_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

CREATE TABLE IF NOT EXISTS `welfare_bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_token` VARCHAR(64) NOT NULL UNIQUE,
  `tracking_code` VARCHAR(30) NOT NULL UNIQUE,
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

-- ==========================================================
-- ۱۴. جدول درخواست‌های اقامت میهمان دانشجویان (Guest Dormitory Requests)
-- ==========================================================
DROP TABLE IF EXISTS `guest_dorm_requests`;
CREATE TABLE `guest_dorm_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL COMMENT 'شماره دانشجویی متقاضی میهمان',
  `full_name` VARCHAR(150) NOT NULL,
  `national_id` VARCHAR(20) NOT NULL,
  `mobile` VARCHAR(20) DEFAULT NULL,
  `field_of_study` VARCHAR(150) DEFAULT NULL,
  `study_shift` VARCHAR(60) DEFAULT NULL,
  `was_dorm_applicant` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: متقاضی خوابگاه از پیش نبوده | 1: متقاضی قبلی خوابگاه بوده است',
  `has_active_allocation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: بدون اتاق فعال هنگام ثبت | 1: دارای اسکان قبلی',
  `problem_description` TEXT NOT NULL COMMENT 'شرح مشکل و دلیل درخواست اقامت میهمان',
  `requested_days` INT NOT NULL DEFAULT 7 COMMENT 'تعداد روز پیشنهادی دانشجو',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

SET FOREIGN_KEY_CHECKS = 1;
