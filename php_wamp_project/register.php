<?php
/**
 * صفحه ثبت‌نام چندمرحله‌ای دانشجو به زبان PHP + HTML + Tailwind CSS
 * شامل بررسی تطابق کد ملی با شماره دانشجویی در جدول منبع (admitted_students)
 */
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام دانشجو - سامانه یکپارچه صمد</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="assets/tailwind/tailwind.css">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-indigo-50/50 text-slate-800 antialiased flex flex-col">

    <!-- هدر مدرن روشن -->
    <header class="sticky top-0 z-30 bg-white/85 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center font-black text-base shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                    صمد
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-tight">سامانه یکپارچه صمد</h1>
                    <p class="text-[11px] text-slate-500">ثبت‌نام دانشجویان پذیرفته‌شده · پردیس فناوری و مهارت</p>
                </div>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="index.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                    <span>پورتال دانشگاه</span>
                </a>
                <a href="login.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/80 rounded-xl transition-colors">
                    <span>قبلاً ثبت‌نام کرده‌اید؟ ورود</span>
                    <span>←</span>
                </a>
            </div>
        </div>
    </header>

    <!-- بخش اصلی صفحه -->
    <main class="flex-1 w-full max-w-3xl mx-auto px-4 py-6 sm:py-8 space-y-5">

        <!-- نوار پیشرفت ۴ مرحله‌ای مدرن -->
        <section class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-4 sm:p-5">
            <div class="relative">
                <!-- خط پس‌زمینه و خط پیشرفت -->
                <div class="hidden sm:block absolute top-4 right-10 left-10 h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div id="stepper-progress-bar" class="h-full bg-gradient-to-l from-indigo-600 to-violet-500 transition-all duration-300 rounded-full" style="width: 0%;"></div>
                </div>

                <div class="relative z-10 grid grid-cols-4 gap-2 text-center">
                    <div id="step-node-1" class="flex flex-col items-center gap-1.5 text-indigo-700 font-bold">
                        <div class="step-circle w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-md shadow-indigo-500/25 ring-4 ring-indigo-50 transition-all">۱</div>
                        <span class="text-[11px] sm:text-xs leading-tight">احراز هویت</span>
                    </div>
                    <div id="step-node-2" class="flex flex-col items-center gap-1.5 text-slate-400 font-semibold">
                        <div class="step-circle w-9 h-9 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center text-xs font-bold transition-all">۲</div>
                        <span class="text-[11px] sm:text-xs leading-tight">تحصیلی و سکونت</span>
                    </div>
                    <div id="step-node-3" class="flex flex-col items-center gap-1.5 text-slate-400 font-semibold">
                        <div class="step-circle w-9 h-9 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center text-xs font-bold transition-all">۳</div>
                        <span class="text-[11px] sm:text-xs leading-tight">مهارت و جشنواره</span>
                    </div>
                    <div id="step-node-4" class="flex flex-col items-center gap-1.5 text-slate-400 font-semibold">
                        <div class="step-circle w-9 h-9 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center text-xs font-bold transition-all">۴</div>
                        <span class="text-[11px] sm:text-xs leading-tight">امنیت و رمز عبور</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- بنر راهنمای مختصر -->
        <div class="p-3.5 sm:px-4 bg-amber-50/80 border border-amber-200/80 rounded-2xl text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-start sm:items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-sm font-bold">ℹ</span>
                <p class="leading-relaxed">
                    <span class="font-bold">راهنمای پذیرش سنجش:</span>
                    کد ملی و شماره دانشجویی واردشده باید با فهرست پذیرفته‌شدگان دانشگاه مطابقت داشته باشد.
                </p>
            </div>
            <span id="mobile-step-badge" class="self-end sm:self-auto px-2.5 py-1 bg-white border border-amber-200 rounded-lg text-[11px] font-bold text-amber-800 shrink-0 tabular-nums">
                مرحله ۱ از ۴
            </span>
        </div>

        <!-- کارت اصلی فرم ثبت‌نام -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-5 sm:p-8">
            <form id="regForm" enctype="multipart/form-data">

                <!-- ==================== مرحله ۱: اطلاعات دانشجویی ==================== -->
                <div id="step-1" class="space-y-6">
                    <div class="border-b border-slate-100 pb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900">اطلاعات هویتی و استعلام قبولی</h2>
                            <p class="text-xs text-slate-500 mt-1">اطلاعات پایه خود را جهت تطبیق با جدول پذیرفته‌شدگان وارد نمایید.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-100">گام اول</span>
                    </div>

                    <div id="step1-error" class="hidden p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold"></div>
                    <div id="step1-success" class="hidden p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold"></div>

                    <!-- آپلود عکس پرسنلی با پیش‌نمایش زنده -->
                    <div class="p-4 sm:p-5 bg-slate-50/80 rounded-2xl border border-slate-200/90 flex flex-col sm:flex-row items-center gap-4">
                        <div id="photoPreviewBox" class="w-20 h-20 rounded-2xl bg-white border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0 shadow-xs">
                            <span id="photoPlaceholderIcon" class="text-2xl text-slate-400">📷</span>
                            <img id="photoPreviewImg" src="" alt="پیش‌نمایش عکس پرسنلی" class="hidden w-full h-full object-cover">
                        </div>
                        <div class="flex-1 text-center sm:text-right space-y-1.5">
                            <label for="photoInput" class="block text-xs font-bold text-slate-800">عکس پرسنلی دانشجو (اختیاری - برای صدور کارت)</label>
                            <p class="text-[11px] text-slate-500">فرمت‌های مجاز: JPG، PNG یا WEBP با کادر واضح پرسنلی</p>
                            <div class="pt-1 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <label for="photoInput" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold cursor-pointer shadow-xs transition-colors">
                                    <span>انتخاب تصویر</span>
                                </label>
                                <span id="photoFileName" class="text-[11px] text-slate-500 truncate max-w-[200px]">فایلی انتخاب نشده است</span>
                            </div>
                            <input type="file" name="photo" id="photoInput" accept="image/*" class="sr-only">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="national_id" class="block text-xs font-bold text-slate-700 mb-1.5">کد ملی ۱۰ رقمی <span class="text-rose-500">*</span></label>
                            <input type="text" name="national_id" id="national_id" required maxlength="10" placeholder="مثال: 0012345678"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" dir="ltr">
                        </div>
                        <div>
                            <label for="student_id" class="block text-xs font-bold text-slate-700 mb-1.5">شماره دانشجویی <span class="text-rose-500">*</span></label>
                            <input type="text" name="student_id" id="student_id" required placeholder="مثال: 4012130011"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" dir="ltr">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="full_name" class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی دانشجو <span class="text-rose-500">*</span></label>
                            <input type="text" name="full_name" id="full_name" required placeholder="نام و نام خانوادگی مطابق شناسنامه و کارت ملی"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-xs font-bold text-slate-700 mb-2">انتخاب پردیس محل تحصیل <span class="text-rose-500">*</span></span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="campus" value="پردیس فناوری" checked class="peer sr-only">
                                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white peer-checked:border-indigo-500 peer-checked:bg-indigo-50/70 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:[&_.campus-dot]:border-[5px] peer-checked:[&_.campus-dot]:border-indigo-600 peer-checked:[&_.campus-dot]:bg-white transition-all flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-sm shadow-xs">🏛</span>
                                            <div>
                                                <span class="text-xs font-extrabold text-slate-900 block">پردیس فناوری</span>
                                                <span class="text-[10px] text-slate-500">رشته‌های مهندسی و فناوری</span>
                                            </div>
                                        </div>
                                        <span class="campus-dot w-4 h-4 rounded-full border-2 border-slate-300 transition-all"></span>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="campus" value="پردیس مهارت" class="peer sr-only">
                                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white peer-checked:border-indigo-500 peer-checked:bg-indigo-50/70 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:[&_.campus-dot]:border-[5px] peer-checked:[&_.campus-dot]:border-indigo-600 peer-checked:[&_.campus-dot]:bg-white transition-all flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-sm shadow-xs">🛠</span>
                                            <div>
                                                <span class="text-xs font-extrabold text-slate-900 block">پردیس مهارت</span>
                                                <span class="text-[10px] text-slate-500">آموزش‌های تخصصی و کاربردی</span>
                                            </div>
                                        </div>
                                        <span class="campus-dot w-4 h-4 rounded-full border-2 border-slate-300 transition-all"></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="mobile" class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن همراه <span class="text-rose-500">*</span></label>
                            <input type="tel" name="mobile" id="mobile" required placeholder="09120000000"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" dir="ltr">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="button" id="btn-to-step-2" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-500/25 transition-all cursor-pointer">
                            بررسی انطباق با دیتابیس و مرحله بعدی ←
                        </button>
                    </div>
                </div>

                <!-- ==================== مرحله ۲: اطلاعات درسی و محل زندگی ==================== -->
                <div id="step-2" class="hidden space-y-6">
                    <div class="border-b border-slate-100 pb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900">اطلاعات تحصیلی و محل سکونت</h2>
                            <p class="text-xs text-slate-500 mt-1">مشخصات رشته، نوبت تحصیلی، نشانی و وضعیت درخواست خوابگاه را مشخص کنید.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-100">گام دوم</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="field_of_study" class="block text-xs font-bold text-slate-700 mb-1.5">رشته تحصیلی <span class="text-rose-500">*</span></label>
                            <select name="field_of_study" id="field_of_study" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                <option value="مهندسی کامپیوتر - هوش مصنوعی و داده">مهندسی کامپیوتر - هوش مصنوعی و داده</option>
                                <option value="مهندسی کامپیوتر - نرم‌افزار">مهندسی کامپیوتر - نرم‌افزار</option>
                                <option value="مهندسی برق - الکترونیک">مهندسی برق - الکترونیک</option>
                                <option value="گرافیک و طراحی چندرسانه‌ای">گرافیک و طراحی چندرسانه‌ای</option>
                                <option value="حسابداری و مدیریت مالی">حسابداری و مدیریت مالی</option>
                                <option value="معماری و دکوراسیون">معماری و دکوراسیون</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">دوره تحصیلی <span class="text-rose-500">*</span></label>
                            <select name="study_shift" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                <option value="روزانه">دوره روزانه</option>
                                <option value="شبانه (نوبت دوم)">دوره شبانه (نوبت دوم)</option>
                            </select>
                        </div>

                        <div>
                            <label for="provinceSelect" class="block text-xs font-bold text-slate-700 mb-1.5">استان محل زندگی <span class="text-rose-500">*</span></label>
                            <select name="province" id="provinceSelect" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                <option value="">-- انتخاب استان --</option>
                            </select>
                        </div>

                        <div>
                            <label for="citySelect" class="block text-xs font-bold text-slate-700 mb-1.5">شهر محل زندگی <span class="text-rose-500">*</span></label>
                            <select name="city" id="citySelect" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                <option value="">-- ابتدا استان را انتخاب کنید --</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 p-4 bg-slate-50/90 rounded-2xl border border-slate-200 transition-colors">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" name="is_foreign_student" value="1" id="is_foreign_student" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-bold text-slate-800">دانشجوی غیرایرانی / اتباع خارجی هستم</span>
                            </label>
                            <div id="foreign_field" class="hidden mt-3">
                                <input type="text" name="foreign_student_code" placeholder="شماره گذرنامه یا کد یکتای اقامت را وارد نمایید" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs focus:border-indigo-500 outline-none">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس دقیق محل سکونت <span class="text-rose-500">*</span></label>
                            <textarea name="residential_address" required rows="2" placeholder="خیابان، کوچه، پلاک و کد پستی" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all"></textarea>
                        </div>

                        <div class="sm:col-span-2 p-4 bg-indigo-50/70 rounded-2xl border border-indigo-200/90 transition-colors">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" name="request_dormitory" value="1" id="request_dormitory" class="w-4 h-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-extrabold text-indigo-950">متقاضی استفاده از خوابگاه دانشجویی صمد هستم</span>
                            </label>
                            <p class="text-[11px] text-indigo-700/90 mt-1 mr-6">در صورت انتخاب، پرونده شما در صف بررسی و اسکان امور خوابگاه‌ها قرار می‌گیرد.</p>
                            <div id="dorm_note_field" class="hidden mt-3">
                                <input type="text" name="dormitory_note" placeholder="توضیحات تکمیلی (شرایط خاص، غیربومی بودن یا مسافت)" class="w-full px-3.5 py-2 bg-white border border-indigo-200 rounded-xl text-xs focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <button type="button" class="btn-prev px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer" data-target="1">→ مرحله قبل</button>
                        <button type="button" id="btn-to-step-3" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-500/20 transition-all cursor-pointer">مرحله بعدی: مهارت‌ها ←</button>
                    </div>
                </div>

                <!-- ==================== مرحله ۳: اطلاعات مهارتی و جشنواره‌ها ==================== -->
                <div id="step-3" class="hidden space-y-6">
                    <div class="border-b border-slate-100 pb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900">توانمندی‌ها، جشنواره‌ها و تیم‌های ورزشی</h2>
                            <p class="text-xs text-slate-500 mt-1">مهارت‌های تخصصی و حوزه‌های مورد علاقه خود را برای فعالیت‌های دانشگاهی ثبت کنید.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-100">گام سوم</span>
                    </div>

                    <div class="p-4 sm:p-5 bg-slate-50/90 rounded-2xl border border-slate-200 space-y-3">
                        <label for="skillInput" class="block text-xs font-extrabold text-slate-800">افزودن مهارت‌ها (تایپ کنید و Enter بزنید):</label>
                        <div class="flex gap-2">
                            <input type="text" id="skillInput" placeholder="مثلاً: برنامه‌نویسی پایتون، طراحی رابط کاربری، برق صنعتی..." class="flex-1 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                            <button type="button" id="btnAddSkill" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded-xl font-bold shadow-xs transition-colors cursor-pointer shrink-0">+ افزودن</button>
                        </div>
                        <div id="skillsContainer" class="flex flex-wrap gap-2 pt-1 min-h-[28px]"></div>
                        <input type="hidden" name="skills" id="skillsHidden">
                    </div>

                    <div class="space-y-3">
                        <span class="block text-xs font-extrabold text-slate-800">علاقه‌مندی در جشنواره‌ها و رویدادهای دانشگاهی:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[technical]" value="1" checked class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-white peer-checked:border-indigo-400 peer-checked:bg-indigo-50/60 peer-checked:[&_.fest-badge]:opacity-100 transition-all">
                                    <span class="font-bold text-slate-800">⚙️ جشنواره‌های فنی و اختراعات</span>
                                    <span class="fest-badge text-[10px] font-bold text-indigo-600 opacity-0 transition-opacity">انتخاب‌شده ✓</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[skillBased]" value="1" checked class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-white peer-checked:border-indigo-400 peer-checked:bg-indigo-50/60 peer-checked:[&_.fest-badge]:opacity-100 transition-all">
                                    <span class="font-bold text-slate-800">🛠 مسابقات ملی مهارت و کارگاهی</span>
                                    <span class="fest-badge text-[10px] font-bold text-indigo-600 opacity-0 transition-opacity">انتخاب‌شده ✓</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[religious]" value="1" class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-white peer-checked:border-indigo-400 peer-checked:bg-indigo-50/60 peer-checked:[&_.fest-badge]:opacity-100 transition-all">
                                    <span class="font-bold text-slate-800">📖 جشنواره قرآن و عترت</span>
                                    <span class="fest-badge text-[10px] font-bold text-indigo-600 opacity-0 transition-opacity">انتخاب‌شده ✓</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[cultural]" value="1" class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-white peer-checked:border-indigo-400 peer-checked:bg-indigo-50/60 peer-checked:[&_.fest-badge]:opacity-100 transition-all">
                                    <span class="font-bold text-slate-800">🎨 جشنواره فرهنگی و هنری</span>
                                    <span class="fest-badge text-[10px] font-bold text-indigo-600 opacity-0 transition-opacity">انتخاب‌شده ✓</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[robotics]" value="1" checked class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-white peer-checked:border-indigo-400 peer-checked:bg-indigo-50/60 peer-checked:[&_.fest-badge]:opacity-100 transition-all">
                                    <span class="font-bold text-slate-800">🤖 مسابقات رباتیک و هوش مصنوعی</span>
                                    <span class="fest-badge text-[10px] font-bold text-indigo-600 opacity-0 transition-opacity">انتخاب‌شده ✓</span>
                                </div>
                            </label>

                            <!-- جشنواره ورزشی -->
                            <label class="cursor-pointer">
                                <input type="checkbox" name="festivals[sports]" id="cb_sports_festivals" value="1" onchange="toggleSportsTeams(this.checked)" class="peer sr-only">
                                <div class="flex items-center justify-between p-3.5 border-2 border-emerald-300 rounded-2xl bg-emerald-50/50 hover:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-100/70 transition-all">
                                    <span class="font-extrabold text-emerald-950">⚽ جشنواره‌های ورزشی و مسابقات پردیس</span>
                                    <span class="text-[10px] font-bold text-emerald-700">تیم‌های دانشگاه</span>
                                </div>
                            </label>
                        </div>

                        <!-- بخش بازشونده لیست تیم‌های ورزشی دانشگاه -->
                        <div id="sportsTeamsContainer" class="hidden p-4 sm:p-5 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🏆</span>
                                    <h3 class="text-xs font-extrabold text-emerald-950">عضویت در تیم‌های ورزشی منتخب دانشگاه ملی مهارت</h3>
                                </div>
                                <span class="text-[10px] text-emerald-800 bg-white border border-emerald-200 px-2.5 py-0.5 rounded-lg font-bold">ثبت‌نام مستقیم تیم‌ها</span>
                            </div>
                            <p class="text-[11px] text-emerald-900/90 leading-relaxed">
                                با انتخاب تیم‌های مورد نظر، اطلاعات شما در بانک تربیت‌بدنی دانشگاه ثبت شده و فراخوان‌های تمرینی تیم‌ها برایتان نمایش داده می‌شود:
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تیم فوتبال دانشگاه" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">⚽ تیم فوتبال دانشگاه</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="فوتسال" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🥅 فوتسال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="والیبال" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏐 والیبال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="بسکتبال" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏀 بسکتبال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تنیس روی میز" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏓 تنیس روی میز</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="شنا" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏊 شنا و واترپلو</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="بدمینتون" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏸 بدمینتون</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="دو و میدانی" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🏃 دو و میدانی</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="کشتی آزاد و فرنگی" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🤼 کشتی آزاد و فرنگی</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 bg-white border border-emerald-200 rounded-xl hover:border-emerald-400 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تکواندو و کاراته" class="sports-team-cb rounded text-emerald-600">
                                    <span class="font-bold text-slate-800">🥋 تکواندو و کاراته</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <button type="button" class="btn-prev px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer" data-target="2">→ مرحله قبل</button>
                        <button type="button" id="btn-to-step-4" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-500/20 transition-all cursor-pointer">مرحله بعدی: تعیین رمز عبور ←</button>
                    </div>
                </div>

                <!-- ==================== مرحله ۴: تعیین کلمه عبور ==================== -->
                <div id="step-4" class="hidden space-y-6">
                    <div class="border-b border-slate-100 pb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900">تعیین کلمه عبور و نهایی‌سازی ثبت‌نام</h2>
                            <p class="text-xs text-slate-500 mt-1">کلمه عبور ایمن انتخاب کنید تا حساب کاربری و کارت دانشجویی شما صادر شود.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">گام نهایی</span>
                    </div>

                    <div id="step4-error" class="hidden p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold"></div>
                    <div id="step4-success" class="hidden p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold"></div>

                    <div class="space-y-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">کلمه عبور جدید <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required placeholder="حداقل ۸ کاراکتر شامل حروف کوچک، بزرگ و علامت خاص"
                                    class="w-full px-3.5 py-2.5 pl-11 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" dir="ltr">
                                <button type="button" onclick="toggleRegPassword('password', this)" class="absolute left-3 top-2.5 text-slate-400 hover:text-slate-700 text-sm cursor-pointer" title="نمایش/مخفی کردن رمز عبور">👁️</button>
                            </div>

                            <!-- نوار قدرت رمز عبور -->
                            <div class="mt-2.5 flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div id="pwd-strength-bar" class="h-full w-0 bg-rose-500 transition-all duration-300 rounded-full"></div>
                                </div>
                                <span id="pwd-strength-label" class="text-[11px] font-bold text-slate-400 min-w-[65px] text-left"></span>
                            </div>

                            <div id="password-feedback" class="hidden mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold"></div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1.5">تکرار کلمه عبور <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirm_password" required placeholder="تکرار دقیق کلمه عبور"
                                    class="w-full px-3.5 py-2.5 pl-11 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" dir="ltr">
                                <button type="button" onclick="toggleRegPassword('confirm_password', this)" class="absolute left-3 top-2.5 text-slate-400 hover:text-slate-700 text-sm cursor-pointer" title="نمایش/مخفی کردن تکرار رمز">👁️</button>
                            </div>
                            <div id="match-feedback" class="hidden mt-1.5 text-xs font-semibold text-rose-600">کلمه عبور و تکرار آن یکسان نیستند.</div>
                        </div>

                        <div class="p-4 bg-slate-50/90 border border-slate-200 rounded-2xl text-[11px] text-slate-600 space-y-2">
                            <div class="font-extrabold text-slate-800">الزامات امنیتی کلمه عبور:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <div id="req-len" class="flex items-center gap-1.5 transition-colors"><span class="req-dot">○</span><span>حداقل ۸ کاراکتر</span></div>
                                <div id="req-lower" class="flex items-center gap-1.5 transition-colors"><span class="req-dot">○</span><span>حروف کوچک لاتین (a-z)</span></div>
                                <div id="req-upper" class="flex items-center gap-1.5 transition-colors"><span class="req-dot">○</span><span>حروف بزرگ لاتین (A-Z)</span></div>
                                <div id="req-spec" class="flex items-center gap-1.5 transition-colors"><span class="req-dot">○</span><span>کاراکتر خاص (!@#$%^&amp;*)</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <button type="button" class="btn-prev px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer" data-target="3">→ مرحله قبل</button>
                        <button type="submit" id="btn-submit" class="px-7 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all cursor-pointer">
                            ثبت نهایی و ورود به داشبورد ✓
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </main>

    <footer class="py-5 text-center text-[11px] text-slate-400">
        دانشگاه ملی مهارت واحد پسران نیشابور · سامانه یکپارچه امور دانشجویی صمد
    </footer>

    <!-- اسکریپت کنترل مراحل، پیش‌نمایش تصویر و ارتباط با سرور -->
    <script>
    const skills = [];
    renderSkills();

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // پیش‌نمایش زنده عکس پرسنلی دانشجو
    const photoInputEl = document.getElementById('photoInput');
    if (photoInputEl) {
        photoInputEl.addEventListener('change', function() {
            const file = this.files && this.files[0];
            const imgEl = document.getElementById('photoPreviewImg');
            const iconEl = document.getElementById('photoPlaceholderIcon');
            const nameEl = document.getElementById('photoFileName');
            if (file) {
                if (nameEl) nameEl.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    if (imgEl && ev.target && ev.target.result) {
                        imgEl.src = ev.target.result;
                        imgEl.classList.remove('hidden');
                        if (iconEl) iconEl.classList.add('hidden');
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if (nameEl) nameEl.textContent = 'فایلی انتخاب نشده است';
                if (imgEl) {
                    imgEl.src = '';
                    imgEl.classList.add('hidden');
                }
                if (iconEl) iconEl.classList.remove('hidden');
            }
        });
    }

    // سوئیچ چک‌باکس‌ها
    document.getElementById('is_foreign_student').addEventListener('change', function() {
        document.getElementById('foreign_field').classList.toggle('hidden', !this.checked);
    });
    document.getElementById('request_dormitory').addEventListener('change', function() {
        document.getElementById('dorm_note_field').classList.toggle('hidden', !this.checked);
    });

    // مدیریت تگ مهارت‌ها
    const skillInput = document.getElementById('skillInput');
    skillInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill(this.value);
        }
    });
    document.getElementById('btnAddSkill').addEventListener('click', function() {
        addSkill(skillInput.value);
    });

    function addSkill(val) {
        val = val.trim();
        if (val && !skills.includes(val)) {
            skills.push(val);
            renderSkills();
            skillInput.value = '';
        }
    }
    function removeSkill(idx) {
        skills.splice(idx, 1);
        renderSkills();
    }
    function renderSkills() {
        const container = document.getElementById('skillsContainer');
        if (skills.length === 0) {
            container.innerHTML = '<span class="text-[11px] text-slate-400">هنوز مهارتی اضافه نشده است.</span>';
        } else {
            container.innerHTML = skills.map((s, idx) => `
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200/80 shadow-xs">
                    <span>${escapeHtml(s)}</span>
                    <button type="button" onclick="removeSkill(${idx})" class="w-4 h-4 rounded-full hover:bg-rose-100 text-rose-500 font-bold flex items-center justify-center cursor-pointer leading-none">×</button>
                </span>
            `).join('');
        }
        document.getElementById('skillsHidden').value = JSON.stringify(skills);
    }

    window.toggleSportsTeams = function(isChecked) {
        const container = document.getElementById('sportsTeamsContainer');
        if (isChecked) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            document.querySelectorAll('.sports-team-cb').forEach(cb => cb.checked = false);
        }
    };

    function toggleRegPassword(inputId, btn) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        if (inp.type === 'password') {
            inp.type = 'text';
            if (btn) btn.textContent = '🙈';
        } else {
            inp.type = 'password';
            if (btn) btn.textContent = '👁️';
        }
    }

    // لیست کامل استان‌ها و شهرهای ایران
    const IRAN_PROVINCES_DATA = {
        'تهران': ['تهران', 'ری', 'شمیرانات', 'شهریار', 'اسلامشهر', 'پاکدشت', 'دماوند', 'ورامین', 'رباط‌کریم', 'پردیس', 'قرچک'],
        'اصفهان': ['اصفهان', 'کاشان', 'نجف‌آباد', 'شاهین‌شهر', 'خمینی‌شهر', 'لنجان', 'مبارکه', 'شهرضا', 'فلاورجان'],
        'فارس': ['شیراز', 'مرودشت', 'جهرم', 'فسا', 'کازرون', 'لارستان', 'داراب', 'اقلید', 'آباده'],
        'خراسان رضوی': ['مشهد', 'نیشابور', 'سبزوار', 'تربت حیدریه', 'قوچان', 'کاشمر', 'چناران', 'سرخس', 'گناباد'],
        'آذربایجان شرقی': ['تبریز', 'مراغه', 'مرند', 'میانه', 'اهر', 'بناب', 'سراب', 'آذرشهر'],
        'آذربایجان غربی': ['ارومیه', 'خوی', 'میاندوآب', 'بوکان', 'مهاباد', 'سلماس', 'پیرانشهر', 'نقده'],
        'مازندران': ['ساری', 'بابل', 'آمل', 'قائم‌شهر', 'بابلسر', 'تنکابن', 'چالوس', 'نکا', 'نور', 'رامسر'],
        'گیلان': ['رشت', 'بندر انزلی', 'لاهیجان', 'لنگرود', 'تالش', 'آستارا', 'صومعه‌سرا', 'رودسر'],
        'خوزستان': ['اهواز', 'دزفول', 'آبادان', 'خرمشهر', 'ماهشهر', 'شوش', 'شوشتر', 'ایذه', 'بهبهان'],
        'البرز': ['کرج', 'فردیس', 'کمال‌شهر', 'نظرآباد', 'محمدشهر', 'هشتگرد', 'اشتهارد'],
        'کرمان': ['کرمان', 'سیرجان', 'رفسنجان', 'جیرفت', 'بم', 'زرند', 'کهنوج', 'شهربابک'],
        'یزد': ['یزد', 'میبد', 'اردکان', 'بافق', 'مهریز', 'ابرکوه', 'تفت'],
        'مرکزی': ['اراک', 'ساوه', 'خمین', 'محلات', 'دلیجان', 'شازند'],
        'قم': ['قم', 'قنوات', 'جعفریه', 'کهک'],
        'همدان': ['همدان', 'ملایر', 'نهاوند', 'تویسرکان', 'اسدآباد', 'کبودرآهنگ'],
        'قزوین': ['قزوین', 'الوند', 'تاکستان', 'آبیک', 'بویین‌زهرا', 'محمدیه'],
        'کرمانشاه': ['کرمانشاه', 'اسلام‌آباد غرب', 'کنگاور', 'سنقر', 'جوانرود', 'صحنه'],
        'کردستان': ['سنندج', 'سقز', 'مریوان', 'بانه', 'قروه', 'کامیاران', 'بیجار'],
        'هرمزگان': ['بندرعباس', 'میناب', 'قشم', 'کیش', 'بندرلنگه', 'جاسک', 'حاجی‌آباد'],
        'سیستان و بلوچستان': ['زاهدان', 'زابل', 'ایرانشهر', 'چابهار', 'سراوان', 'خاش', 'نیک‌شهر'],
        'بوشهر': ['بوشهر', 'برازجان', 'بندر گناوه', 'بندر کنگان', 'خورموج', 'عسلویه'],
        'زنجان': ['زنجان', 'ابهر', 'خرمدره', 'قیدار'],
        'سمنان': ['سمنان', 'شاهرود', 'دامغان', 'گرمسار', 'مهدیشهر'],
        'گلستان': ['گرگان', 'گنبد کاووس', 'علی‌آباد کتول', 'بندر ترکمن', 'آق‌قلا'],
        'لرستان': ['خرم‌آباد', 'بروجرد', 'دورود', 'کوهدشت', 'الیگودرز', 'نورآباد'],
        'اردبیل': ['اردبیل', 'پارس‌آباد', 'مشگین‌شهر', 'خلخال', 'گرمی'],
        'چهارمحال و بختیاری': ['شهرکرد', 'بروجن', 'لردگان', 'فرخ‌شهر', 'فارسان'],
        'کهگیلویه و بویراحمد': ['یاسوج', 'دوگنبدان', 'دهدشت', 'سی‌سخت'],
        'ایلام': ['ایلام', 'ایوان', 'دهلران', 'آبدانان', 'مهران'],
        'خراسان جنوبی': ['بیرجند', 'قائن', 'طبس', 'فردوس', 'نهبندان'],
        'خراسان شمالی': ['بجنورد', 'شیروان', 'اسفراین', 'آشخانه']
    };

    const provSel = document.getElementById('provinceSelect');
    const citySel = document.getElementById('citySelect');

    Object.keys(IRAN_PROVINCES_DATA).forEach(prov => {
        const opt = document.createElement('option');
        opt.value = prov;
        opt.textContent = prov;
        if (prov === 'تهران') opt.selected = true;
        provSel.appendChild(opt);
    });

    function updateCities(provName) {
        citySel.innerHTML = '';
        const cities = IRAN_PROVINCES_DATA[provName] || [];
        if (cities.length === 0) {
            citySel.innerHTML = '<option value="">-- هیچ شهری یافت نشد --</option>';
            return;
        }
        cities.forEach((c, idx) => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (idx === 0) opt.selected = true;
            citySel.appendChild(opt);
        });
    }

    provSel.addEventListener('change', function() {
        updateCities(this.value);
    });
    updateCities('تهران');

    // استعلام و تطابق جدول منبع در مرحله اول
    document.getElementById('btn-to-step-2').addEventListener('click', async function() {
        const natId = document.getElementById('national_id').value.trim();
        const stdId = document.getElementById('student_id').value.trim();
        const errBox = document.getElementById('step1-error');
        const sucBox = document.getElementById('step1-success');

        errBox.classList.add('hidden');
        sucBox.classList.add('hidden');

        if (!natId || !stdId) {
            errBox.textContent = 'لطفاً هر دو فیلد کد ملی و شماره دانشجویی را وارد فرمایید.';
            errBox.classList.remove('hidden');
            return;
        }

        this.disabled = true;
        this.textContent = 'در حال استعلام از جدول منبع پذیرفته‌شدگان...';

        try {
            const formData = new FormData();
            formData.append('national_id', natId);
            formData.append('student_id', stdId);

            const res = await fetch('check_student.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                sucBox.textContent = 'تطابق تایید شد: ' + data.message;
                sucBox.classList.remove('hidden');

                if (data.data) {
                    if (data.data.full_name) {
                        document.getElementById('full_name').value = data.data.full_name;
                    }
                    if (data.data.campus) {
                        const radio = document.querySelector(`input[name="campus"][value="${CSS.escape(data.data.campus)}"]`);
                        if (radio) radio.checked = true;
                    }
                    if (data.data.field_of_study) {
                        const fieldSel = document.getElementById('field_of_study');
                        if (fieldSel) {
                            const matchedOpt = Array.from(fieldSel.options).find(o => o.value === data.data.field_of_study);
                            if (matchedOpt) {
                                matchedOpt.selected = true;
                            } else {
                                const newOpt = document.createElement('option');
                                newOpt.value = data.data.field_of_study;
                                newOpt.textContent = data.data.field_of_study;
                                newOpt.selected = true;
                                fieldSel.appendChild(newOpt);
                            }
                        }
                    }
                }

                setTimeout(() => goToStep(2), 450);
            } else {
                errBox.textContent = 'خطای عدم تطابق: ' + data.message;
                errBox.classList.remove('hidden');
            }
        } catch (e) {
            errBox.textContent = 'خطا در ارتباط با سرور. لطفاً اتصال پایگاه‌داده را بررسی نمایید.';
            errBox.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'بررسی انطباق با دیتابیس و مرحله بعدی ←';
        }
    });

    document.getElementById('btn-to-step-3').addEventListener('click', () => goToStep(3));
    document.getElementById('btn-to-step-4').addEventListener('click', () => goToStep(4));

    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', function() {
            goToStep(parseInt(this.getAttribute('data-target'), 10));
        });
    });

    const persianDigits = ['۰', '۱', '۲', '۳', '۴'];

    function goToStep(num) {
        [1, 2, 3, 4].forEach(s => {
            document.getElementById(`step-${s}`).classList.toggle('hidden', s !== num);
            const node = document.getElementById(`step-node-${s}`);
            const circle = node.querySelector('.step-circle');
            if (s < num) {
                node.className = 'flex flex-col items-center gap-1.5 text-emerald-700 font-bold';
                circle.className = 'step-circle w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs font-black shadow-sm transition-all';
                circle.textContent = '✓';
            } else if (s === num) {
                node.className = 'flex flex-col items-center gap-1.5 text-indigo-700 font-bold';
                circle.className = 'step-circle w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-md shadow-indigo-500/25 ring-4 ring-indigo-50 transition-all';
                circle.textContent = persianDigits[s];
            } else {
                node.className = 'flex flex-col items-center gap-1.5 text-slate-400 font-semibold';
                circle.className = 'step-circle w-9 h-9 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center text-xs font-bold transition-all';
                circle.textContent = persianDigits[s];
            }
        });

        const bar = document.getElementById('stepper-progress-bar');
        if (bar) {
            bar.style.width = `${((num - 1) / 3) * 100}%`;
        }
        const badge = document.getElementById('mobile-step-badge');
        if (badge) {
            badge.textContent = `مرحله ${persianDigits[num]} از ۴`;
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // اعتبارسنجی بلادرنگ رمز عبور و نوار قدرت
    const pwdInput = document.getElementById('password');
    const cpwdInput = document.getElementById('confirm_password');
    const pwdFeedback = document.getElementById('password-feedback');
    const matchFeedback = document.getElementById('match-feedback');

    function updateRequirementItem(id, passed) {
        const el = document.getElementById(id);
        if (!el) return;
        const dot = el.querySelector('.req-dot');
        if (passed) {
            el.className = 'flex items-center gap-1.5 text-emerald-600 font-bold transition-colors';
            if (dot) dot.textContent = '✓';
        } else {
            el.className = 'flex items-center gap-1.5 text-slate-600 transition-colors';
            if (dot) dot.textContent = '○';
        }
    }

    pwdInput.addEventListener('input', function() {
        const val = this.value;
        const hasLen = val.length >= 8;
        const hasLow = /[a-z]/.test(val);
        const hasUp = /[A-Z]/.test(val);
        const hasSpec = /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>\/?]/.test(val);

        updateRequirementItem('req-len', hasLen);
        updateRequirementItem('req-lower', hasLow);
        updateRequirementItem('req-upper', hasUp);
        updateRequirementItem('req-spec', hasSpec);

        const score = [hasLen, hasLow, hasUp, hasSpec].filter(Boolean).length;
        const barEl = document.getElementById('pwd-strength-bar');
        const lblEl = document.getElementById('pwd-strength-label');
        if (barEl && lblEl) {
            if (val.length === 0) {
                barEl.style.width = '0%';
                lblEl.textContent = '';
            } else if (score <= 1) {
                barEl.style.width = '25%';
                barEl.className = 'h-full bg-rose-500 transition-all duration-300 rounded-full';
                lblEl.textContent = 'ضعیف';
                lblEl.className = 'text-[11px] font-bold text-rose-600 min-w-[65px] text-left';
            } else if (score <= 3) {
                barEl.style.width = '65%';
                barEl.className = 'h-full bg-amber-500 transition-all duration-300 rounded-full';
                lblEl.textContent = 'متوسط';
                lblEl.className = 'text-[11px] font-bold text-amber-600 min-w-[65px] text-left';
            } else {
                barEl.style.width = '100%';
                barEl.className = 'h-full bg-emerald-500 transition-all duration-300 rounded-full';
                lblEl.textContent = 'قدرتمند ✓';
                lblEl.className = 'text-[11px] font-bold text-emerald-600 min-w-[65px] text-left';
            }
        }

        if (val.length > 0 && score < 4) {
            pwdFeedback.textContent = 'رمز عبور باید حداقل ۸ کاراکتر و شامل حروف کوچک، بزرگ و کاراکتر خاص باشد.';
            pwdFeedback.classList.remove('hidden');
        } else {
            pwdFeedback.classList.add('hidden');
        }

        if (cpwdInput.value.length > 0) {
            matchFeedback.classList.toggle('hidden', cpwdInput.value === val);
        }
    });

    cpwdInput.addEventListener('input', function() {
        matchFeedback.classList.toggle('hidden', this.value === pwdInput.value || this.value.length === 0);
    });

    // نمایش خودکار مرحله دارای فیلد نامعتبر در زمان اعتبارسنجی مرورگر
    document.getElementById('regForm').addEventListener('invalid', function(e) {
        const target = e.target;
        const stepEl = target && target.closest ? target.closest('[id^="step-"]') : null;
        if (stepEl) {
            const match = /^step-(\d+)$/.exec(stepEl.id);
            if (match && stepEl.classList.contains('hidden')) {
                goToStep(parseInt(match[1], 10));
            }
        }
    }, true);

    // ثبت نهایی فرم به سمت process_register.php
    document.getElementById('regForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const pwd = pwdInput.value;
        const cpwd = cpwdInput.value;
        const err4 = document.getElementById('step4-error');
        const suc4 = document.getElementById('step4-success');

        err4.classList.add('hidden');
        suc4.classList.add('hidden');

        if (pwd !== cpwd) {
            matchFeedback.classList.remove('hidden');
            err4.textContent = 'کلمه عبور و تکرار آن یکسان نیستند.';
            err4.classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.textContent = 'در حال ذخیره‌سازی اطلاعات و صدور حساب...';

        const formData = new FormData(this);
        try {
            const res = await fetch('process_register.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                suc4.textContent = 'ثبت‌نام با موفقیت انجام شد! در حال انتقال به داشبورد دانشجو...';
                suc4.classList.remove('hidden');
                setTimeout(() => {
                    window.location.href = data.redirect || 'dashboard.php';
                }, 700);
            } else {
                err4.textContent = 'خطا: ' + (data.message || 'ثبت‌نام انجام نشد.');
                err4.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'ثبت نهایی و ورود به داشبورد ✓';
            }
        } catch (err) {
            err4.textContent = 'خطای ارتباط با سرور: ' + err.toString();
            err4.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'ثبت نهایی و ورود به داشبورد ✓';
        }
    });
    </script>
</body>
</html>
