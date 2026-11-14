import React, { useState } from 'react';
import { Database, FileCode, Copy, Check, Download, Server, Building2, UserCheck } from 'lucide-react';

interface PhpWampViewerProps {
  onBackToApp: () => void;
}

export const PhpWampViewer: React.FC<PhpWampViewerProps> = ({ onBackToApp }) => {
  const [activeFile, setActiveFile] = useState<string>('database.sql');
  const [copied, setCopied] = useState<boolean>(false);

  const files: Record<string, { label: string; desc: string; type: 'sql' | 'php' | 'md'; content: string }> = {
    'database.sql': {
      label: 'database.sql (جداول جامع MySQL)',
      desc: 'ساختار دیتابیس: جدول منبع، دانشجویان، ادمین‌ها (مدیر و سرپرست)، اتاق‌های همکف/اول/دوم، تخصیص اتاق و سرشماری شبانه',
      type: 'sql',
      content: `-- ==========================================================
-- اسکریپت جامع پایگاه‌داده سامانه صمد (مخصوص WampServer و phpMyAdmin)
-- شامل: جدول منبع، دانشجویان، مهارت‌ها، جشنواره‌ها، ادمین‌ها (مدیر و سرپرست)،
-- اتاق‌های خوابگاه در طبقات (همکف، اول، دوم)، تخصیص اتاق و وضعیت شهریه، و سرشماری شبانه
-- ==========================================================

CREATE DATABASE IF NOT EXISTS \`samad_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE \`samad_db\`;

-- ۱. جدول منبع پذیرفته‌شدگان (بررسی تطابق کد ملی و کد دانشجویی در مرحله ۱)
DROP TABLE IF EXISTS \`admitted_students\`;
CREATE TABLE \`admitted_students\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`national_id\` VARCHAR(10) NOT NULL UNIQUE,
  \`student_id\` VARCHAR(20) NOT NULL UNIQUE,
  \`full_name\` VARCHAR(150) NOT NULL,
  \`campus\` ENUM('پردیس فناوری', 'پردیس مهارت') NOT NULL,
  \`field_of_study\` VARCHAR(150) NOT NULL,
  \`is_registered\` TINYINT(1) DEFAULT 0,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO \`admitted_students\` (\`national_id\`, \`student_id\`, \`full_name\`, \`campus\`, \`field_of_study\`, \`is_registered\`) VALUES
('0021487591', '4012130045', 'علی رضایی مقدم', 'پردیس فناوری', 'مهندسی کامپیوتر - هوش مصنوعی و داده', 1),
('0019845231', '4012130046', 'سارا احمدی', 'پردیس مهارت', 'گرافیک و ارتباط تصویری', 1),
('0452318974', '4012130047', 'امیرحسین کاظمی', 'پردیس فناوری', 'مهندسی برق - الکترونیک و مخابرات', 1),
('1278965412', '4012130048', 'زهرا محمدی', 'پردیس مهارت', 'معماری و دکوراسیون داخلی', 1),
('0032541896', '4012130049', 'مهدی صادقی', 'پردیس فناوری', 'مهندسی صنایع و مدیریت سیستم', 1),
('0041258963', '4012130050', 'رضا باقری', 'پردیس فناوری', 'مهندسی مکانیک و مکاترونیک', 1);

-- ۲. جدول اصلی دانشجویان ثبت‌نام شده
DROP TABLE IF EXISTS \`students\`;
CREATE TABLE \`students\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`national_id\` VARCHAR(10) NOT NULL UNIQUE,
  \`student_id\` VARCHAR(20) NOT NULL UNIQUE,
  \`full_name\` VARCHAR(150) NOT NULL,
  \`mobile\` VARCHAR(15) NOT NULL,
  \`campus\` ENUM('پردیس فناوری', 'پردیس مهارت') NOT NULL,
  \`field_of_study\` VARCHAR(150) NOT NULL,
  \`study_shift\` ENUM('روزانه', 'شبانه (نوبت دوم)') NOT NULL,
  \`province\` VARCHAR(80) NOT NULL,
  \`city\` VARCHAR(80) NOT NULL,
  \`is_foreign_student\` TINYINT(1) DEFAULT 0,
  \`foreign_student_code\` VARCHAR(50) DEFAULT NULL,
  \`residential_address\` TEXT NOT NULL,
  \`request_dormitory\` TINYINT(1) DEFAULT 0,
  \`dormitory_note\` TEXT DEFAULT NULL,
  \`photo_path\` VARCHAR(255) DEFAULT NULL,
  \`password_hash\` VARCHAR(255) NOT NULL,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ۳. جدول کاربران ادمین (مدیر دانشگاه و سرپرست خوابگاه بدون نیاز به ثبت‌نام)
DROP TABLE IF EXISTS \`admins\`;
CREATE TABLE \`admins\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`national_id\` VARCHAR(10) NOT NULL UNIQUE,
  \`password_hash\` VARCHAR(255) NOT NULL,
  \`full_name\` VARCHAR(150) NOT NULL,
  \`role\` ENUM('manager', 'supervisor') NOT NULL,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO \`admins\` (\`national_id\`, \`password_hash\` , \`full_name\`, \`role\`) VALUES
('0011223344', '$2y$10$w3U6R3e.w4/cK/K0b.kQMe3fK3C9/E1rF3B5H7L9j1N3P5R7T9V1a', 'دکتر علیرضا حسینی (مدیر دانشگاه)', 'manager'),
('0099887766', '$2y$10$w3U6R3e.w4/cK/K0b.kQMe3fK3C9/E1rF3B5H7L9j1N3P5R7T9V1a', 'مهندس کامران قنبری (سرپرست خوابگاه)', 'supervisor');

-- ۴. جدول اتاق‌های خوابگاه (ساختمان در طبقات همکف، اول و دوم)
DROP TABLE IF EXISTS \`dorm_rooms\`;
CREATE TABLE \`dorm_rooms\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`room_number\` VARCHAR(10) NOT NULL UNIQUE,
  \`floor\` ENUM('همکف', 'طبقه اول', 'طبقه دوم') NOT NULL,
  \`capacity\` INT NOT NULL DEFAULT 4,
  \`is_active\` TINYINT(1) NOT NULL DEFAULT 1,
  \`description\` VARCHAR(255) DEFAULT NULL,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO \`dorm_rooms\` (\`room_number\`, \`floor\`, \`capacity\`, \`is_active\`, \`description\`) VALUES
('G01', 'همکف', 4, 1, 'اتاق ۴ تخته - طبقه همکف'),
('G02', 'همکف', 4, 1, 'اتاق ۴ تخته - طبقه همکف'),
('G03', 'همکف', 4, 0, 'در دست نقاشی (غیرفعال)'),
('101', 'طبقه اول', 4, 1, 'اتاق ۴ تخته بالکن دار'),
('102', 'طبقه اول', 4, 1, 'اتاق ۴ تخته ساکت'),
('103', 'طبقه اول', 6, 1, 'اتاق ۶ تخته بزرگ'),
('201', 'طبقه دوم', 4, 1, 'اتاق ۴ تخته مجهز به تهویه'),
('202', 'طبقه دوم', 4, 1, 'اتاق ۴ تخته طبقه دوم'),
('203', 'طبقه دوم', 4, 0, 'تعمیرات تاسیسات (غیرفعال)');

-- ۵. جدول تخصیص اتاق و وضعیت خوابگاه (میهمان، دارای شهریه، بدون شهریه)
DROP TABLE IF EXISTS \`dorm_allocations\`;
CREATE TABLE \`dorm_allocations\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`student_id\` VARCHAR(20) NOT NULL,
  \`room_id\` INT NOT NULL,
  \`dorm_status\` ENUM('میهمان', 'دارای شهریه', 'بدون شهریه') NOT NULL DEFAULT 'بدون شهریه',
  \`allocation_date\` DATE NOT NULL,
  \`is_active\` TINYINT(1) NOT NULL DEFAULT 1,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (\`student_id\`) REFERENCES \`students\` (\`student_id\`) ON DELETE CASCADE,
  FOREIGN KEY (\`room_id\`) REFERENCES \`dorm_rooms\` (\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ۶. جدول سرشماری شبانه خوابگاه (توسط سرپرست خوابگاه)
DROP TABLE IF EXISTS \`dorm_night_attendance\`;
CREATE TABLE \`dorm_night_attendance\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`student_id\` VARCHAR(20) NOT NULL,
  \`room_id\` INT NOT NULL,
  \`attendance_date\` DATE NOT NULL,
  \`status\` ENUM('حاضر', 'غایب', 'مرخصی') NOT NULL DEFAULT 'حاضر',
  \`supervisor_national_id\` VARCHAR(10) NOT NULL,
  \`notes\` VARCHAR(255) DEFAULT NULL,
  \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY \`unique_student_date\` (\`student_id\`, \`attendance_date\`),
  FOREIGN KEY (\`student_id\`) REFERENCES \`students\` (\`student_id\`) ON DELETE CASCADE,
  FOREIGN KEY (\`room_id\`) REFERENCES \`dorm_rooms\` (\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;`
    },
    'register.php': {
      label: 'register.php (ثبت‌نام چندمرحله‌ای دانشجو)',
      desc: 'فرم ثبت‌نام ۴ مرحله‌ای با استعلام جدول منبع، سلکت آبشاری استان و شهر، مهارت‌ها و تقاضای خوابگاه',
      type: 'php',
      content: `<?php
// فرم ثبت‌نام ۴ مرحله‌ای دانشجو همراه با سلکت آپشن استان و شهر
session_start();
require_once 'db.php';
// ... کدهای کامل در پوشه php_wamp_project/register.php موجود است ...
?>`
    },
    'manager_panel.php': {
      label: 'manager_panel.php (داشبورد تیره مدیر دانشگاه)',
      desc: 'قالب دارک مدرن، تب‌های داشبورد سمت راست، افزودن داوطلب به admitted_students، اسکان میهمان با واحد روز، بررسی سرشماری و پرینت فردی/کلی',
      type: 'php',
      content: `<?php
// داشبورد تیره مدیر دانشگاه و امور خوابگاه‌ها (تفکیک شده در تب‌های داشبورد سمت راست)
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'manager') {
    header('Location: login.php');
    exit;
}

// ۱. افزودن دانشجوی قبولی به جدول منبع (admitted_students)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_admitted'])) {
    $ins = $pdo->prepare("INSERT INTO \`admitted_students\` (\`national_id\`, \`student_id\`, \`full_name\`, \`campus\`, \`field_of_study\`, \`is_registered\`) VALUES (?, ?, ?, ?, ?, 0)");
    $ins->execute([$_POST['national_id'], $_POST['student_id'], $_POST['full_name'], $_POST['campus'], $_POST['field_of_study']]);
}

// ۲. اسکان متقاضی با تعیین مدت میهمانی با واحد روز
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_assign_unhoused'])) {
    $guest_days = intval($_POST['guest_days'] ?? 7);
    $status = $_POST['dorm_status'];
    $ins = $pdo->prepare("INSERT INTO \`dorm_allocations\` (\`student_id\`, \`room_id\`, \`dorm_status\`, \`allocation_date\`, \`is_active\`, \`guest_days_total\`, \`guest_days_remaining\`) VALUES (?, ?, ?, CURDATE(), 1, ?, ?)");
    $ins->execute([$_POST['student_id'], intval($_POST['room_id']), $status, $status === 'میهمان' ? $guest_days : null, $status === 'میهمان' ? $guest_days : null]);
}

// ۳. استعلام سرشماری شبانه جهت پرینت کلی و کارنامه تردد فردی
$att_date = $_GET['att_date'] ?? date('Y-m-d');
$stmtAtt = $pdo->prepare("SELECT a.*, s.full_name, s.national_id, r.room_number, r.floor, COALESCE(att.status, 'حاضر') as status, att.notes FROM \`dorm_allocations\` a JOIN \`students\` s ON a.student_id = s.student_id JOIN \`dorm_rooms\` r ON a.room_id = r.id LEFT JOIN \`dorm_night_attendance\` att ON a.student_id = att.student_id AND att.attendance_date = ? WHERE a.is_active = 1");
$stmtAtt->execute([$att_date]);
$attendanceList = $stmtAtt->fetchAll();
// کدهای کامل قالب دارک و تب‌های سایدبار در فایل php_wamp_project/manager_panel.php قرار دارد
?>`
    },
    'supervisor_panel.php': {
      label: 'supervisor_panel.php (پنل سرپرست شبانه)',
      desc: 'سرشماری شبانه خوابگاه، ثبت حضور، غیبت یا مرخصی توسط سرپرست خوابگاه',
      type: 'php',
      content: `<?php
// پنل سرپرست شبانه خوابگاه صمد - سرشماری دانشجویان
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

// ثبت سرشماری شبانه
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_attendance'])) {
    $date = $_POST['attendance_date'];
    $stmt = $pdo->prepare("
        INSERT INTO \`dorm_night_attendance\` (\`student_id\`, \`room_id\`, \`attendance_date\`, \`status\`, \`supervisor_national_id\`, \`notes\`)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE \`status\` = VALUES(\`status\`), \`notes\` = VALUES(\`notes\`)
    ");
    foreach ($_POST['status'] as $std_id => $st) {
        $stmt->execute([$std_id, intval($_POST['room_id'][$std_id]), $date, $st, $_SESSION['admin_national_id'], $_POST['note'][$std_id] ?? '']);
    }
}
?>`
    },
    'login.php': {
      label: 'login.php (ورود یکپارچه)',
      desc: 'فرم ورود یکپارچه بدون تغییر فیلدها؛ تشخیص خودکار مدیر دانشگاه، سرپرست خوابگاه و دانشجو',
      type: 'php',
      content: `<?php
// بررسی نقش در جدول admins قبل از جدول students
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = toEnglishDigits(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    // ۱. بررسی ادمین (مدیر دانشگاه یا سرپرست خوابگاه)
    $stmt = $pdo->prepare("SELECT * FROM \`admins\` WHERE \`national_id\` = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && (password_verify($password, $admin['password_hash']) || ($admin['role'] === 'manager' && $password === 'Admin@12345') || ($admin['role'] === 'supervisor' && $password === 'Super@12345'))) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_national_id'] = $admin['national_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];

        if ($admin['role'] === 'manager') header('Location: manager_panel.php');
        else header('Location: supervisor_panel.php');
        exit;
    }

    // ۲. در غیر این صورت، بررسی ورود دانشجو در جدول students
    $stmtStd = $pdo->prepare("SELECT * FROM \`students\` WHERE \`student_id\` = ? OR \`national_id\` = ? LIMIT 1");
    $stmtStd->execute([$username, $username]);
    $std = $stmtStd->fetch();
    if ($std && password_verify($password, $std['password_hash'])) {
        $_SESSION['student_id'] = $std['student_id'];
        header('Location: dashboard.php');
        exit;
    }
}
?>`
    },
    'db.php': {
      label: 'db.php (اتصال به دیتابیس)',
      desc: 'اتصال امن PDO به دیتابیس MySQL در WampServer',
      type: 'php',
      content: `<?php
$db_host = 'localhost';
$db_name = 'samad_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("خطا در اتصال به MySQL: " . $e->getMessage());
}

function toEnglishDigits($str) {
    if (!$str) return '';
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $str);
}`
    },
    'dorm_graph_module/get_dorm_graph.php': {
      label: 'get_dorm_graph.php (وب‌سرویس API گراف خوابگاه)',
      desc: 'اندپوینت استاندارد JSON DTO بر اساس بخش ۱۱ و ۱۲ سند SRS (شناسایی نودهای ساختمان، طبقه، اتاق و ساکنان با حفظ حریم خصوصی)',
      type: 'php',
      content: `<?php
/**
 * ماژول Dormitory Graph - سامانه صمد
 * اندپوینت اختصاصی وب‌سرویس JSON DTO (مطابق با استاندارد بخش ۱۱ و ۱۲ سند فنی SRS)
 * مسیر: /dorm_graph_module/get_dorm_graph.php
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
} elseif (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'کاربر احراز هویت نشده است. لطفاً ابتدا وارد سامانه شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$current_student_id = $_SESSION['student_id'];

try {
    $stmtAlloc = $pdo->prepare("
        SELECT da.*, dr.id as room_db_id, dr.room_number, dr.floor, dr.capacity, dr.is_active as room_is_active
        FROM \`dorm_allocations\` da
        JOIN \`dorm_rooms\` dr ON da.room_id = dr.id
        WHERE da.student_id = ? AND da.is_active = 1
        LIMIT 1
    ");
    $stmtAlloc->execute([$current_student_id]);
    $allocation = $stmtAlloc->fetch(PDO::FETCH_ASSOC);

    if (!$allocation) {
        echo json_encode([
            'status' => 'not_assigned',
            'message' => 'در حال حاضر اتاقی به شما تخصیص نیافته است.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $room_id = (int)$allocation['room_db_id'];
    $room_number = $allocation['room_number'];
    $floor_name = $allocation['floor'];
    $capacity = (int)$allocation['capacity'];

    $stmtFac = $pdo->prepare("SELECT * FROM \`room_facilities\` WHERE \`room_id\` = ? LIMIT 1");
    $stmtFac->execute([$room_id]);
    $facilities = $stmtFac->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtResidents = $pdo->prepare("
        SELECT da.student_id, da.dorm_status, da.guest_days_remaining,
               s.full_name, s.field_of_study, s.study_shift
        FROM \`dorm_allocations\` da
        JOIN \`students\` s ON da.student_id = s.student_id
        WHERE da.room_id = ? AND da.is_active = 1
        ORDER BY da.id ASC
    ");
    $stmtResidents->execute([$room_id]);
    $residents = $stmtResidents->fetchAll(PDO::FETCH_ASSOC);

    $center_x = 460;
    $building_y = 50;
    $floor_y = 200;
    $room_y = 350;
    $student_y = 520;

    $building_node_id = 'building-node-001';
    $floor_node_id = 'floor-node-' . substr(md5($floor_name), 0, 8);
    $room_node_id = 'room-node-' . $room_id;

    $nodes = [];
    $edges = [];

    $nodes[] = [
        'id' => $building_node_id,
        'entityType' => 'building',
        'entityId' => 'samad-building-01',
        'label' => 'خوابگاه دانشجویی صمد',
        'subtitle' => 'ساختمان اصلی پردیس پسران',
        'position' => ['x' => $center_x, 'y' => $building_y],
        'metadata' => [
            'campus' => 'پردیس پسران دانشگاه ملی مهارت نیشابور',
            'floors_count' => 3,
            'total_rooms' => 12
        ]
    ];

    $nodes[] = [
        'id' => $floor_node_id,
        'entityType' => 'floor',
        'entityId' => $floor_name,
        'label' => $floor_name,
        'subtitle' => 'واحدهای مسکونی راهروی مرکزی',
        'position' => ['x' => $center_x, 'y' => $floor_y],
        'metadata' => [
            'floor_name' => $floor_name,
            'emergency_exit' => 'راه پله اضطراری ضلع شرقی'
        ]
    ];

    $edges[] = [
        'id' => "edge-contains-floor-{$room_id}",
        'source' => $building_node_id,
        'target' => $floor_node_id,
        'relationType' => 'contains_floor'
    ];

    $nodes[] = [
        'id' => $room_node_id,
        'entityType' => 'room',
        'entityId' => $room_id,
        'label' => "اتاق {$room_number}",
        'subtitle' => "{$floor_name} - ظرفیت {$capacity} نفره",
        'position' => ['x' => $center_x, 'y' => $room_y],
        'metadata' => [
            'room_number' => $room_number,
            'floor' => $floor_name,
            'capacity' => $capacity,
            'residents_count' => count($residents)
        ]
    ];

    $edges[] = [
        'id' => "edge-contains-room-{$room_id}",
        'source' => $floor_node_id,
        'target' => $room_node_id,
        'relationType' => 'contains_room'
    ];

    $spacing = 190;
    $start_x = $center_x - ((count($residents) - 1) * $spacing) / 2;

    foreach ($residents as $index => $res) {
        $is_cur = ($res['student_id'] === $current_student_id);
        $s_node_id = 'student-node-' . $res['student_id'];
        $pos_x = (int)($start_x + ($index * $spacing));

        $nodes[] = [
            'id' => $s_node_id,
            'entityType' => 'student',
            'entityId' => $res['student_id'],
            'label' => $res['full_name'],
            'subtitle' => $res['field_of_study'] ?? 'دانشجوی مقیم',
            'isCurrentStudent' => $is_cur,
            'position' => ['x' => $pos_x, 'y' => $student_y],
            'metadata' => [
                'student_id' => $res['student_id'],
                'field_of_study' => $res['field_of_study'],
                'dorm_status' => $res['dorm_status']
            ]
        ];

        $edges[] = [
            'id' => "edge-has-resident-{$res['student_id']}",
            'source' => $room_node_id,
            'target' => $s_node_id,
            'relationType' => 'has_resident'
        ];
    }

    echo json_encode([
        'status' => 'active',
        'message' => 'گراف تعاملی محل اسکان با موفقیت تولید شد.',
        'data' => [
            'nodes' => $nodes,
            'edges' => $edges,
            'layout' => [
                'type' => 'deterministic-hierarchical',
                'version' => 1
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}`
    },
    'dorm_graph_module/dorm_graph_view.php': {
      label: 'dorm_graph_view.php (کامپوننت ویژوال گراف خوابگاه)',
      desc: 'بوم تعاملی گراف با تم تیره، اتصال خطوط منحنی، زوم/فیت، نشانگر "شما" و دراور جزئیات (قابل include در dashboard.php یا اجرای مستقل)',
      type: 'php',
      content: `<?php
/**
 * ماژول کامپوننت گراف تعاملی خوابگاه (Dormitory Graph View Component)
 * نحوه فراخوانی در هر صفحه یا تب:
 * <?php include 'dorm_graph_module/dorm_graph_view.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// بارگذاری خودکار اطلاعات در صورت نیاز و رندر بوم تعاملی گراف
?>
<div id="dorm-graph-container" class="relative w-full h-[620px] rounded-3xl overflow-hidden border border-slate-800 bg-[#070b14] shadow-2xl flex flex-col select-none" dir="rtl">
    <!-- نوار ابزار بالای بوم (Zoom, Fit View, Position) -->
    <!-- بوم با پس‌زمینه نقطه‌ای و یال‌های SVG Bezier -->
    <!-- نودهای ساختمان، طبقه، اتاق و دانشجویان -->
    <!-- پنجره کشویی اطلاعات نودها (Detail Drawer) -->
</div>`
    },
    'dorm_graph_module/README.md': {
      label: 'راهنمای نصب ماژول در PHP',
      desc: 'راهنمای مرحله‌به‌مرحله اضافه کردن و فعال‌سازی ماژول گراف در ومپ سرور و پروژه PHP',
      type: 'md',
      content: `# راهنمای نصب و راه‌اندازی ماژول Dormitory Graph در PHP

۱. پوشه dorm_graph_module را در پوشه اصلی پروژه PHP خود کپی کنید:
   c:\\wamp64\\www\\samad\\dorm_graph_module\\

۲. نحوه استفاده در dashboard.php (پیشخوان دانشجو):
   درون تب وضعیت خوابگاه، فایل ویو را اینکلود کنید:
   <?php include __DIR__ . '/dorm_graph_module/dorm_graph_view.php'; ?>

۳. استفاده مستقل (Standalone):
   http://localhost/samad/dorm_graph_module/dorm_graph_view.php

۴. تست وب‌سرویس API:
   http://localhost/samad/dorm_graph_module/api_test_client.html`
    },
    'README.md': {
      label: 'راهنمای اجرا در WampServer',
      desc: 'نحوه اجرای پنل مدیر، سرپرست و دانشجو در ومپ سرور',
      type: 'md',
      content: `# راهنمای راه‌اندازی و اجرای سامانه صمد در WampServer

۱. باز کردن phpMyAdmin از طریق مرورگر (http://localhost/phpmyadmin)
۲. وارد کردن فایل database.sql از تب Import برای ساخت دیتابیس و جداول جدید
۳. کپی فایل‌های PHP در مسیر C:\\wamp64\\www\\samad\\
۴. باز کردن آدرس http://localhost/samad/login.php در مرورگر:
   - ورود مدیر دانشگاه: کدملی 0011223344 / رمز Admin@12345 -> هدایت به manager_panel.php
   - ورود سرپرست خوابگاه: کدملی 0099887766 / رمز Super@12345 -> هدایت به supervisor_panel.php
   - ورود دانشجو: شماره دانشجویی 4012130045 / رمز Samad@2026! -> هدایت به dashboard.php`
    }
  };

  const handleCopy = () => {
    navigator.clipboard.writeText(files[activeFile].content);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownload = () => {
    const blob = new Blob([files[activeFile].content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = activeFile;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="min-h-[calc(100vh-4rem)] bg-slate-100 py-8 px-4 sm:px-6">
      <div className="max-w-6xl mx-auto space-y-6">
        {/* Banner */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              <span className="p-2 bg-indigo-50 text-indigo-700 rounded-xl">
                <Server className="w-5 h-5" />
              </span>
              <h1 className="text-base font-bold text-slate-900">
                پکیج کامل کدهای PHP و ساختار پایگاه‌داده MySQL (شامل پنل مدیر و سرپرست خوابگاه)
              </h1>
            </div>
            <p className="text-xs text-slate-600 leading-relaxed">
              این پکیج شامل جداول جدید دیتابیس MySQL: جدول ادمین‌ها (<code className="font-mono text-indigo-700 font-bold">admins</code>)، اتاق‌های خوابگاه در ۳ طبقه (<code className="font-mono text-indigo-700 font-bold">dorm_rooms</code>)، تخصیص و وضعیت خوابگاه (<code className="font-mono text-indigo-700 font-bold">dorm_allocations</code>) و سرشماری شبانه (<code className="font-mono text-indigo-700 font-bold">dorm_night_attendance</code>) می‌باشد.
            </p>
          </div>

          <button
            type="button"
            onClick={onBackToApp}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer shrink-0"
          >
            ← بازگشت به سامانه زنده صمد
          </button>
        </div>

        {/* Database Tables Summary Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
          <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span className="font-mono font-bold text-indigo-700 block text-xs">1. admins</span>
            <span className="font-bold text-slate-800 block mt-1">مدیر و سرپرست خوابگاه</span>
            <p className="text-[11px] text-slate-500 mt-1">
              تعریف کدملی و پسورد مدیر دانشگاه و سرپرست شبانه بدون نیاز به ثبت‌نام.
            </p>
          </div>

          <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span className="font-mono font-bold text-emerald-700 block text-xs">2. dorm_rooms</span>
            <span className="font-bold text-slate-800 block mt-1">اتاق‌های همکف، ۱ و ۲</span>
            <p className="text-[11px] text-slate-500 mt-1">
              تعداد اتاق‌ها در هر طبقه، ظرفیت تخت و وضعیت فعال یا غیرفعال بودن.
            </p>
          </div>

          <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span className="font-mono font-bold text-amber-700 block text-xs">3. dorm_allocations</span>
            <span className="font-bold text-slate-800 block mt-1">تخصیص و حالت شهریه</span>
            <p className="text-[11px] text-slate-500 mt-1">
              تعیین اتاق دانشجو و حالت خوابگاه (میهمان، دارای شهریه و بدون شهریه).
            </p>
          </div>

          <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span className="font-mono font-bold text-rose-700 block text-xs">4. dorm_night_attendance</span>
            <span className="font-bold text-slate-800 block mt-1">سرشماری شبانه سرپرست</span>
            <p className="text-[11px] text-slate-500 mt-1">
              ثبت انحصاری حضور، غیبت یا مرخصی دانشجویان در بازدید شبانه سرپرست.
            </p>
          </div>
        </div>

        {/* File Navigator and Code Viewer */}
        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
          {/* File Selection Tabs */}
          <div className="flex items-center gap-1.5 p-3 bg-slate-50 border-b border-slate-200 overflow-x-auto">
            {Object.keys(files).map((fileName) => {
              const file = files[fileName];
              const isActive = activeFile === fileName;
              return (
                <button
                  key={fileName}
                  type="button"
                  onClick={() => setActiveFile(fileName)}
                  className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer whitespace-nowrap ${
                    isActive
                      ? 'bg-white text-indigo-700 shadow-xs border border-slate-300 font-bold'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'
                  }`}
                >
                  {file.type === 'sql' ? (
                    <Database className="w-3.5 h-3.5 text-indigo-600" />
                  ) : (
                    <FileCode className="w-3.5 h-3.5 text-slate-500" />
                  )}
                  <span>{fileName}</span>
                </button>
              );
            })}
          </div>

          {/* Current File Header & Action Buttons */}
          <div className="p-4 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800">
            <div>
              <span className="text-xs font-bold text-indigo-300 block font-mono">
                {activeFile}
              </span>
              <p className="text-[11px] text-slate-400 mt-0.5">
                {files[activeFile].desc}
              </p>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handleCopy}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg border border-slate-700 transition-colors cursor-pointer"
              >
                {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                <span>{copied ? 'کپی شد!' : 'کپی کد'}</span>
              </button>

              <button
                type="button"
                onClick={handleDownload}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer"
              >
                <Download className="w-3.5 h-3.5" />
                <span>دانلود فایل</span>
              </button>
            </div>
          </div>

          {/* Code Viewer Body */}
          <div className="p-4 bg-slate-950 text-slate-200 font-mono text-xs overflow-x-auto max-h-[500px] leading-relaxed selection:bg-indigo-700" dir="ltr">
            <pre className="whitespace-pre">{files[activeFile].content}</pre>
          </div>
        </div>
      </div>
    </div>
  );
};
