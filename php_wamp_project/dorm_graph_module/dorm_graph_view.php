<?php
/**
 * ماژول کامپوننت گراف تعاملی خوابگاه (Dormitory Graph View Component)
 * قابل استفاده به دو روش:
 * ۱. Include مستقیم در داخل هر صفحه یا تب (مانند dashboard.php):
 *    <?php include 'dorm_graph_module/dorm_graph_view.php'; ?>
 * ۲. باز شدن به صورت مستقل (Standalone) در مرورگر:
 *    http://localhost/php_wamp_project/dorm_graph_module/dorm_graph_view.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_standalone_page = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'dorm_graph_view.php');

// در صورتی که دیتابیس قبلاً واکشی نشده باشد، اقدام به بارگذاری می‌کنیم
if (!isset($dorm) && isset($_SESSION['student_id'])) {
    if (file_exists(__DIR__ . '/../db.php')) {
        require_once __DIR__ . '/../db.php';
    } elseif (file_exists(__DIR__ . '/db.php')) {
        require_once __DIR__ . '/db.php';
    }

    if (isset($pdo)) {
        $stId = $_SESSION['student_id'];
        
        // استعلام اطلاعات دانشجو
        if (!isset($student)) {
            $stmtSt = $pdo->prepare("SELECT * FROM `students` WHERE `student_id` = ? LIMIT 1");
            $stmtSt->execute([$stId]);
            $student = $stmtSt->fetch(PDO::FETCH_ASSOC);
        }

        // استعلام اطلاعات اتاق
        $stmtDorm = $pdo->prepare("
            SELECT da.*, dr.id as dorm_room_id, dr.room_number, dr.floor, dr.capacity, dr.is_active as room_is_active
            FROM `dorm_allocations` da
            JOIN `dorm_rooms` dr ON da.room_id = dr.id
            WHERE da.student_id = ? AND da.is_active = 1
            LIMIT 1
        ");
        $stmtDorm->execute([$stId]);
        $dorm = $stmtDorm->fetch(PDO::FETCH_ASSOC);

        // استعلام هم‌اتاقی‌ها
        $roommates = [];
        if ($dorm) {
            $stmtMates = $pdo->prepare("
                SELECT da.*, s.full_name, s.field_of_study, s.study_shift
                FROM `dorm_allocations` da
                JOIN `students` s ON da.student_id = s.student_id
                WHERE da.room_id = ? AND da.student_id != ? AND da.is_active = 1
                ORDER BY da.id ASC
            ");
            $stmtMates->execute([$dorm['room_id'], $stId]);
            $roommates = $stmtMates->fetchAll(PDO::FETCH_ASSOC);

            // استعلام تجهیزات اتاق
            $stmtFac = $pdo->prepare("SELECT * FROM `room_facilities` WHERE `room_id` = ? LIMIT 1");
            $stmtFac->execute([$dorm['dorm_room_id']]);
            $roomFacility = $stmtFac->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    }
}
?>

<?php if ($is_standalone_page): ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقشه تعاملی خوابگاه دانشجویی صمد (Dormitory Graph)</title>
    <link rel="stylesheet" href="../assets/fonts/fonts.css" />
    <link rel="stylesheet" href="../assets/tailwind/tailwind.css" />
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background-color: #0b0f19; color: #f1f5f9; }
    </style>
</head>
<body class="p-4 sm:p-6 max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-black text-white flex items-center gap-2">
            <span>🗺️</span>
            <span>ماژول نقشه تعاملی خوابگاه دانشجویان (Dormitory Graph)</span>
        </h1>
        <a href="../dashboard.php" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all">
            بازگشت به پیشخوان ›
        </a>
    </div>
<?php endif; ?>

<!-- محفظه اصلی گراف تعاملی خوابگاه -->
<div id="dorm-graph-module-wrapper" class="w-full space-y-4" dir="rtl">
    <?php if (!empty($dorm)): ?>
        <div class="relative w-full h-[620px] rounded-3xl overflow-hidden border border-slate-800 bg-[#070b14] shadow-2xl flex flex-col select-none">
            <!-- نوار ابزار بالا (Toolbar) -->
            <div class="h-14 px-5 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between z-10 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-teal-500/20 border border-teal-500/30 flex items-center justify-center text-teal-300">
                        <span>🧭</span>
                    </div>
                    <div>
                        <h3 class="text-xs font-black text-white flex items-center gap-2">
                            <span>نقشه تعاملی خوابگاه صمد (Dormitory Graph)</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono">
                                SRS v1.0
                            </span>
                        </h3>
                        <p class="text-[10px] text-slate-400">
                            خوابگاه صمد &gt; <?= htmlspecialchars($dorm['floor']) ?> &gt; اتاق <?= htmlspecialchars($dorm['room_number']) ?>
                        </p>
                    </div>
                </div>

                <!-- کنترل‌های کاربری: Zoom, Fit View, My Position -->
                <div class="flex items-center gap-2 text-xs">
                    <button type="button" onclick="moduleZoomDormGraph(0.15)" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 flex items-center justify-center font-bold text-sm transition-all cursor-pointer" title="بزرگ‌نمایی">
                        +
                    </button>
                    <button type="button" onclick="moduleZoomDormGraph(-0.15)" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 flex items-center justify-center font-bold text-sm transition-all cursor-pointer" title="کوچک‌نمایی">
                        -
                    </button>
                    <button type="button" onclick="moduleResetDormGraphView()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 flex items-center gap-1.5 transition-all cursor-pointer font-bold text-[11px]" title="تنظیم مجدد کادر">
                        <span>⛶</span>
                        <span>تنظیم نما (Fit View)</span>
                    </button>
                    <button type="button" onclick="moduleSelectGraphNode('student', '<?= htmlspecialchars($student['student_id'] ?? '') ?>')" class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 transition-all cursor-pointer font-bold text-[11px]">
                        <span>✨</span>
                        <span>موقعیت من در گراف</span>
                    </button>
                </div>
            </div>

            <!-- بوم اصلی با پس‌زمینه نقطه‌ای و قابلیت جابه‌جایی و زوم -->
            <div id="module-canvas-viewport" class="flex-1 w-full h-full relative overflow-hidden cursor-grab active:cursor-grabbing" style="background-color: #080d1a; background-image: radial-gradient(#334155 1.2px, transparent 1.2px); background-size: 20px 20px;">
                <div id="module-graph-stage" class="absolute w-[920px] h-[640px] transition-transform duration-200 origin-center" style="transform: translate(-50%, -50%) scale(1); left: 50%; top: 50%;">
                    
                    <!-- خطوط اتصالات گراف (SVG Bezier Curves) -->
                    <svg class="absolute inset-0 w-full h-full pointer-events-none" style="z-index: 1;">
                        <!-- خط ساختمان به طبقه -->
                        <path d="M 460 115 C 460 160, 460 160, 460 195" stroke="#6366f1" stroke-width="2.5" stroke-dasharray="4 4" fill="none" />
                        <!-- خط طبقه به اتاق -->
                        <path d="M 460 260 C 460 300, 460 300, 460 335" stroke="#14b8a6" stroke-width="2.5" fill="none" />

                        <!-- خطوط انشعابی اتاق به دانشجویان ساکن -->
                        <?php
                        $mod_residents = array_merge([$student ?? ['student_id' => '', 'full_name' => 'شما']], $roommates ?? []);
                        $total_mod_res = count($mod_residents);
                        $mod_spacing = 190;
                        $mod_center_x = 460;
                        $mod_start_x = $mod_center_x - (($total_mod_res - 1) * $mod_spacing) / 2;

                        foreach ($mod_residents as $idx => $r):
                            $rx = $mod_start_x + ($idx * $mod_spacing);
                            $is_cur = (($r['student_id'] ?? '') === ($student['student_id'] ?? ''));
                            $s_color = $is_cur ? '#f59e0b' : '#38bdf8';
                            $s_width = $is_cur ? '3' : '2';
                        ?>
                            <path d="M 460 410 C 460 460, <?= $rx ?> 460, <?= $rx ?> 495" stroke="<?= $s_color ?>" stroke-width="<?= $s_width ?>" fill="none" <?= $is_cur ? '' : 'stroke-dasharray="4 4"' ?> />
                        <?php endforeach; ?>
                    </svg>

                    <!-- نود ۱: ساختمان (Building Node) -->
                    <div onclick="moduleSelectGraphNode('building', 'samad-01')" class="absolute cursor-pointer transition-all duration-200 hover:scale-105" style="left: 350px; top: 40px; z-index: 5;">
                        <div class="w-[220px] px-4 py-3 rounded-2xl bg-slate-900/95 border-2 border-blue-500/70 hover:border-blue-400 shadow-xl shadow-blue-500/10 text-right">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/40 flex items-center justify-center text-blue-400 shrink-0 text-lg">
                                    🏢
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-[10px] font-bold text-blue-400 block">خوابگاه و ساختمان</span>
                                    <h4 class="text-xs font-black text-white truncate">خوابگاه دانشجویی صمد</h4>
                                    <span class="text-[10px] text-slate-400 block truncate">ساختمان اصلی پردیس</span>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                                <span>پردیس پسران</span>
                                <span class="text-emerald-400 font-bold">فعال ✓</span>
                            </div>
                        </div>
                    </div>

                    <!-- نود ۲: طبقه (Floor Node) -->
                    <div onclick="moduleSelectGraphNode('floor', '<?= htmlspecialchars($dorm['floor'] ?? '') ?>')" class="absolute cursor-pointer transition-all duration-200 hover:scale-105" style="left: 360px; top: 195px; z-index: 5;">
                        <div class="w-[200px] px-4 py-3 rounded-2xl bg-slate-900/95 border-2 border-indigo-500/70 hover:border-indigo-400 shadow-xl shadow-indigo-500/10 text-right">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-400 shrink-0 text-base">
                                    📑
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-[10px] font-bold text-indigo-400 block">سطح طبقه</span>
                                    <h4 class="text-xs font-black text-white truncate"><?= htmlspecialchars($dorm['floor'] ?? 'طبقه اول') ?></h4>
                                    <span class="text-[10px] text-slate-400 block truncate">واحدهای مسکونی</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نود ۳: اتاق مسکونی (Room Node) -->
                    <div onclick="moduleSelectGraphNode('room', '<?= htmlspecialchars($dorm['dorm_room_id'] ?? $dorm['room_id'] ?? '1') ?>')" class="absolute cursor-pointer transition-all duration-200 hover:scale-105" style="left: 350px; top: 335px; z-index: 5;">
                        <div class="w-[220px] px-4 py-3 rounded-2xl bg-slate-900/95 border-2 border-teal-500/70 hover:border-teal-400 shadow-xl shadow-teal-500/10 text-right">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-teal-500/20 border border-teal-500/40 flex items-center justify-center text-teal-400 shrink-0 text-base">
                                    🚪
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-teal-400">اتاق محل سکونت</span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-teal-950 text-teal-300 border border-teal-600/40 font-mono">
                                            <?= count($mod_residents) ?>/<?= htmlspecialchars($dorm['capacity'] ?? '4') ?>
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-black text-white truncate">اتاق <?= htmlspecialchars($dorm['room_number'] ?? '') ?></h4>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                                <span>امکانات رفاهی کامل</span>
                                <span class="text-teal-400 font-bold">جزئیات ›</span>
                            </div>
                        </div>
                    </div>

                    <!-- نودهای ۴: دانشجویان (Student Nodes) -->
                    <?php foreach ($mod_residents as $idx => $r):
                        $rx = $mod_start_x + ($idx * $mod_spacing) - 90;
                        $is_me = (($r['student_id'] ?? '') === ($student['student_id'] ?? ''));
                    ?>
                        <div onclick="moduleSelectGraphNode('student', '<?= htmlspecialchars($r['student_id'] ?? '') ?>')" class="absolute cursor-pointer transition-all duration-200 hover:scale-105" style="left: <?= $rx ?>px; top: 495px; z-index: 5;">
                            <div class="w-[180px] px-3.5 py-3 rounded-2xl border-2 text-right relative shadow-xl <?= $is_me ? 'bg-gradient-to-br from-slate-900 via-amber-950/30 to-slate-900 border-amber-400 shadow-amber-500/20' : 'bg-slate-900/95 border-slate-700 hover:border-cyan-400' ?>">
                                <?php if ($is_me): ?>
                                    <div class="absolute -top-2.5 right-3 px-2 py-0.5 rounded-full bg-amber-500 text-slate-950 text-[9px] font-black flex items-center gap-1 shadow-md">
                                        <span>✨</span>
                                        <span>شما</span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border <?= $is_me ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-cyan-500/20 border-cyan-500/40 text-cyan-400' ?>">
                                        <span>👤</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-black truncate <?= $is_me ? 'text-amber-200' : 'text-white' ?>">
                                            <?= htmlspecialchars($r['full_name'] ?? 'دانشجو') ?>
                                        </h4>
                                        <span class="text-[10px] text-slate-400 block truncate">
                                            <?= htmlspecialchars($r['field_of_study'] ?? 'مهندسی') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 pt-1.5 border-t border-slate-800 flex items-center justify-between text-[9px] text-slate-400">
                                    <span><?= htmlspecialchars($r['study_shift'] ?? 'روزانه') ?></span>
                                    <span class="<?= $is_me ? 'text-amber-400 font-bold' : 'text-cyan-400' ?>">
                                        <?= htmlspecialchars($r['dorm_status'] ?? 'اسکان عادی') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- پنل کشویی جزئیات نود انتخابی (Node Detail Drawer) -->
                <div id="module-graph-node-drawer" class="hidden absolute top-4 right-4 bottom-4 w-80 bg-slate-900/95 border border-slate-700 rounded-2xl p-4 shadow-2xl backdrop-blur-xl z-20 flex-col justify-between overflow-y-auto text-right" dir="rtl">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <span class="text-[10px] text-slate-400 block" id="mod-drawer-type">جزئیات نود</span>
                                <h4 class="text-xs font-black text-white" id="mod-drawer-title">عنوان</h4>
                            </div>
                            <button type="button" onclick="moduleCloseGraphDrawer()" class="p-1 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-colors cursor-pointer text-xs">
                                ✕
                            </button>
                        </div>
                        <div id="mod-drawer-content" class="space-y-3 text-xs text-slate-300">
                            <!-- به صورت داینامیک توسط توابع جاوااسکریپت پر می‌شود -->
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 mt-4 flex items-center justify-between">
                        <span class="text-[10px] text-slate-500">حالت فقط‌خواندنی (Read-Only)</span>
                        <button type="button" onclick="moduleCloseGraphDrawer()" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold transition-all cursor-pointer">
                            بستن
                        </button>
                    </div>
                </div>

                <!-- راهنمای علائم گراف در گوشه تصویر (Legend) -->
                <div class="absolute bottom-4 right-4 bg-slate-900/90 border border-slate-800 rounded-2xl p-3 shadow-xl text-[10px] space-y-1.5 z-10 pointer-events-none select-none text-right" dir="rtl">
                    <span class="font-bold text-slate-300 block mb-1 text-[11px]">راهنمای علائم گراف:</span>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="text-slate-400">ساختمان و پردیس</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        <span class="text-slate-400">سطح طبقه</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                        <span class="text-slate-400">اتاق مسکونی</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="text-amber-300 font-bold">دانشجوی جاری (شما)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                        <span class="text-slate-400">هم‌اتاقی‌ها</span>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let modGraphScale = 1;

            function moduleZoomDormGraph(delta) {
                modGraphScale = Math.min(Math.max(0.4, modGraphScale + delta), 1.8);
                const stage = document.getElementById('module-graph-stage');
                if (stage) {
                    stage.style.transform = `translate(-50%, -50%) scale(${modGraphScale})`;
                }
            }

            function moduleResetDormGraphView() {
                modGraphScale = 1;
                const stage = document.getElementById('module-graph-stage');
                if (stage) {
                    stage.style.transform = 'translate(-50%, -50%) scale(1)';
                }
            }

            function moduleCloseGraphDrawer() {
                const drawer = document.getElementById('module-graph-node-drawer');
                if (drawer) {
                    drawer.classList.add('hidden');
                    drawer.classList.remove('flex');
                }
            }

            const modGraphDataset = {
                building: {
                    title: 'خوابگاه دانشجویی صمد',
                    type: 'ساختمان و خوابگاه',
                    campus: 'پردیس پسران دانشگاه ملی مهارت نیشابور',
                    floors: '۳ طبقه مسکونی',
                    totalRooms: '۱۲ اتاق فعال',
                    address: 'نیشابور، بلوار دانشگاه، مجتمع بزرگ خوابگاهی صمد'
                },
                floor: {
                    title: '<?= addslashes($dorm['floor'] ?? 'طبقه اول') ?>',
                    type: 'سطح طبقه',
                    exit: 'راه پله اضطراری ضلع شرقی',
                    facilities: ['سرویس بهداشتی و حمام', 'آبدارخانه و اجاق گاز', 'اتاق مطالعه']
                },
                room: {
                    title: 'اتاق <?= addslashes($dorm['room_number'] ?? '') ?>',
                    type: 'اتاق محل سکونت',
                    capacity: '<?= addslashes($dorm['capacity'] ?? '4') ?> نفره',
                    residents: '<?= count($mod_residents) ?> نفر ساکن',
                    refrigerator: '<?= addslashes($roomFacility['refrigerator_model'] ?? 'امرسان ۵ فوت') ?>',
                    bedType: '<?= addslashes($roomFacility['bed_type'] ?? 'تخت فلزی دو طبقه') ?>',
                    wardrobes: '<?= addslashes($roomFacility['wardrobe_count'] ?? '4') ?> کمد تفکیکی'
                },
                students: {
                    '<?= addslashes($student['student_id'] ?? '') ?>': {
                        isMe: true,
                        name: '<?= addslashes($student['full_name'] ?? 'شما') ?>',
                        studentId: '<?= addslashes($student['student_id'] ?? '') ?>',
                        field: '<?= addslashes($student['field_of_study'] ?? '') ?>',
                        status: '<?= addslashes($dorm['dorm_status'] ?? 'بدون شهریه') ?>',
                        shift: '<?= addslashes($student['study_shift'] ?? 'روزانه') ?>'
                    }
                    <?php if (!empty($roommates)): foreach ($roommates as $m): ?>
                    ,'<?= addslashes($m['student_id'] ?? '') ?>': {
                        isMe: false,
                        name: '<?= addslashes($m['full_name'] ?? 'هم‌اتاقی') ?>',
                        studentId: '<?= addslashes($m['student_id'] ?? '') ?>',
                        field: '<?= addslashes($m['field_of_study'] ?? '') ?>',
                        status: '<?= addslashes($m['dorm_status'] ?? 'اسکان عادی') ?>',
                        shift: '<?= addslashes($m['study_shift'] ?? 'روزانه') ?>'
                    }
                    <?php endforeach; endif; ?>
                }
            };

            function moduleSelectGraphNode(type, id) {
                const drawer = document.getElementById('module-graph-node-drawer');
                const dType = document.getElementById('mod-drawer-type');
                const dTitle = document.getElementById('mod-drawer-title');
                const dContent = document.getElementById('mod-drawer-content');

                if (!drawer || !dContent) return;

                drawer.classList.remove('hidden');
                drawer.classList.add('flex');

                if (type === 'building') {
                    const b = modGraphDataset.building;
                    dType.innerText = b.type;
                    dTitle.innerText = b.title;
                    dContent.innerHTML = `
                        <div class="p-3 bg-slate-800/70 rounded-xl space-y-1">
                            <span class="text-[10px] text-slate-400 block">مرکز دانشگاهی:</span>
                            <strong class="text-white">${b.campus}</strong>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div class="p-2 bg-slate-800/70 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">طبقات</span>
                                <strong class="text-blue-400">${b.floors}</strong>
                            </div>
                            <div class="p-2 bg-slate-800/70 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">کل اتاق‌ها</span>
                                <strong class="text-blue-400">${b.totalRooms}</strong>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-800/70 rounded-xl space-y-1">
                            <span class="text-[10px] text-slate-400 block">نشانی مجتمع:</span>
                            <p class="text-[11px] text-slate-300 leading-relaxed">${b.address}</p>
                        </div>
                    `;
                } else if (type === 'floor') {
                    const f = modGraphDataset.floor;
                    dType.innerText = f.type;
                    dTitle.innerText = f.title;
                    dContent.innerHTML = `
                        <div class="p-3 bg-slate-800/70 rounded-xl space-y-1">
                            <span class="text-[10px] text-slate-400 block">خروج اضطراری:</span>
                            <strong class="text-indigo-300">${f.exit}</strong>
                        </div>
                        <div class="p-3 bg-slate-800/70 rounded-xl space-y-1.5">
                            <span class="text-[10px] text-slate-400 block">امکانات عمومی این طبقه:</span>
                            <ul class="space-y-1 text-[11px] text-slate-300">
                                ${f.facilities.map(item => `<li>✓ ${item}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                } else if (type === 'room') {
                    const r = modGraphDataset.room;
                    dType.innerText = r.type;
                    dTitle.innerText = r.title;
                    dContent.innerHTML = `
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div class="p-2 bg-slate-800/70 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">ظرفیت اسمی</span>
                                <strong class="text-teal-400">${r.capacity}</strong>
                            </div>
                            <div class="p-2 bg-slate-800/70 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">ساکنان فعلی</span>
                                <strong class="text-teal-400">${r.residents}</strong>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-800/70 rounded-xl space-y-2">
                            <span class="text-[10px] text-teal-400 font-bold block">تجهیزات ثبت‌شده اتاق:</span>
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="p-1.5 bg-slate-900/50 rounded">یخچال: ${r.refrigerator}</div>
                                <div class="p-1.5 bg-slate-900/50 rounded">تخت: ${r.bedType}</div>
                                <div class="p-1.5 bg-slate-900/50 rounded">کمد: ${r.wardrobes}</div>
                                <div class="p-1.5 bg-slate-900/50 rounded">سرمایش: کولر پردیس</div>
                            </div>
                        </div>
                    `;
                } else if (type === 'student') {
                    const s = modGraphDataset.students[id];
                    if (!s) return;
                    dType.innerText = s.isMe ? 'پروفایل شما در گراف' : 'اطلاعات هم‌اتاقی';
                    dTitle.innerText = s.name;
                    dContent.innerHTML = `
                        ${s.isMe ? `
                        <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-300 text-[11px] font-bold">
                            ✨ شما در این اتاق اسکان دارید.
                        </div>
                        ` : `
                        <div class="p-2.5 bg-cyan-500/10 border border-cyan-500/20 rounded-xl text-cyan-300 text-[10px]">
                            اطلاعات تماس خصوصی طبق سیاست حریم خصوصی محفوظ است.
                        </div>
                        `}
                        <div class="space-y-2">
                            <div class="p-2 bg-slate-800/70 rounded-xl flex items-center justify-between">
                                <span class="text-slate-400 text-[10px]">شماره دانشجویی:</span>
                                <strong class="text-slate-200 font-mono text-[11px]">${s.studentId}</strong>
                            </div>
                            <div class="p-2 bg-slate-800/70 rounded-xl flex items-center justify-between">
                                <span class="text-slate-400 text-[10px]">رشته تحصیلی:</span>
                                <strong class="text-teal-300 text-[11px]">${s.field}</strong>
                            </div>
                            <div class="p-2 bg-slate-800/70 rounded-xl flex items-center justify-between">
                                <span class="text-slate-400 text-[10px]">نوع اسکان:</span>
                                <strong class="text-emerald-400 text-[11px]">${s.status}</strong>
                            </div>
                        </div>
                    `;
                }
            }
        </script>
    <?php else: ?>
        <div class="p-8 text-center text-xs text-slate-400 bg-slate-900/50 rounded-2xl border border-dashed border-slate-700">
            <div class="text-amber-400 text-3xl mb-2">⚠️</div>
            <h4 class="text-sm font-bold text-white mb-1">اتاقی به شما تخصیص نیافته است</h4>
            <p>پس از تأیید تخصیص توسط مدیر دانشگاه، گراف تعاملی خوابگاه به‌صورت خودکار ترسیم می‌گردد.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_standalone_page): ?>
</body>
</html>
<?php endif; ?>
