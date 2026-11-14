<?php
/**
 * صفحه ثبت نام چندمرحله ای دانشجو به زبان PHP + HTML + Tailwind CSS
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
    <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen pb-16">
    <!-- هدر بالای صفحه -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">صمد</div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900">سامانه یکپارچه صمد</h1>
                    <p class="text-[11px] text-slate-500">پردیس فناوری و پردیس مهارت</p>
                </div>
            </div>
            <a href="login.php" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">قبلاً ثبت‌نام کرده‌اید؟ ورود</a>
        </div>
    </header>

    <!-- نوار پیشرفت مراحل ۴ گانه -->
    <div class="bg-white border-b border-slate-200 py-4 px-4 mb-6 shadow-xs">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between text-xs font-bold text-center">
                <div id="step-node-1" class="flex-1 text-indigo-600">
                    <div class="w-8 h-8 mx-auto rounded-full bg-indigo-600 text-white flex items-center justify-center mb-1">۱</div>
                    <span>اطلاعات دانشجویی</span>
                </div>
                <div id="step-node-2" class="flex-1 text-slate-400">
                    <div class="w-8 h-8 mx-auto rounded-full bg-slate-200 text-slate-600 flex items-center justify-center mb-1">۲</div>
                    <span>تحصیلی و محل زندگی</span>
                </div>
                <div id="step-node-3" class="flex-1 text-slate-400">
                    <div class="w-8 h-8 mx-auto rounded-full bg-slate-200 text-slate-600 flex items-center justify-center mb-1">۳</div>
                    <span>مهارت‌ها و جشنواره‌ها</span>
                </div>
                <div id="step-node-4" class="flex-1 text-slate-400">
                    <div class="w-8 h-8 mx-auto rounded-full bg-slate-200 text-slate-600 flex items-center justify-center mb-1">۴</div>
                    <span>تعیین کلمه عبور</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4">
        <!-- پیام راهنما و تکمیل خودکار -->
        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex flex-wrap items-center justify-between gap-2 shadow-xs">
            <div>
                <span class="font-bold">راهنمای آزمون سنجش:</span> کد ملی و شماره دانشجویی باید با جدول منبع سنجش مطابقت داشته باشد.
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="autofillDemoRegister()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    ✨ تکمیل خودکار فرم تستی
                </button>
                <a href="index.php" class="text-xs text-indigo-700 hover:text-indigo-900 font-bold">پورتال دانشگاه ←</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <form id="regForm" enctype="multipart/form-data">
                
                <!-- فرم اول: اطلاعات دانشجویی -->
                <div id="step-1" class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b pb-3">مرحله اول: اطلاعات دانشجویی و احراز هویت با جدول منبع</h2>
                    
                    <div id="step1-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs"></div>
                    <div id="step1-success" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs"></div>

                    <!-- آپلود عکس -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold text-slate-700 mb-1">عکس پرسنلی دانشجو (JPG / PNG):</label>
                        <input type="file" name="photo" id="photoInput" accept="image/*" class="text-xs text-slate-600">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۱- کد ملی <span class="text-rose-500">*</span></label>
                            <input type="text" name="national_id" id="national_id" required maxlength="10" placeholder="0021487591"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۲- کد دانشجویی <span class="text-rose-500">*</span></label>
                            <input type="text" name="student_id" id="student_id" required placeholder="4012130045"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left" dir="ltr">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">نام و نام خانوادگی دانشجو <span class="text-rose-500">*</span></label>
                            <input type="text" name="full_name" id="full_name" required placeholder="نام و نام خانوادگی طبق کارت ملی"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۳- انتخاب پردیس صمد <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 p-3 border rounded-xl bg-slate-50 cursor-pointer">
                                    <input type="radio" name="campus" value="پردیس فناوری" checked>
                                    <span class="text-xs font-bold">پردیس فناوری</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded-xl bg-slate-50 cursor-pointer">
                                    <input type="radio" name="campus" value="پردیس مهارت">
                                    <span class="text-xs font-bold">پردیس مهارت</span>
                                </label>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">شماره همراه دانشجو <span class="text-rose-500">*</span></label>
                            <input type="tel" name="mobile" id="mobile" required placeholder="09121234567"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left" dir="ltr">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="button" id="btn-to-step-2" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow">
                            بررسی انطباق با دیتابیس و مرحله بعدی ←
                        </button>
                    </div>
                </div>

                <!-- فرم دوم: اطلاعات درسی و محل زندگی -->
                <div id="step-2" class="hidden space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b pb-3">مرحله دوم: اطلاعات درسی و محل زندگی</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۱- رشته تحصیلی <span class="text-rose-500">*</span></label>
                            <select name="field_of_study" id="field_of_study" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                                <option value="مهندسی کامپیوتر - هوش مصنوعی و داده">مهندسی کامپیوتر - هوش مصنوعی و داده</option>
                                <option value="مهندسی کامپیوتر - نرم‌افزار">مهندسی کامپیوتر - نرم‌افزار</option>
                                <option value="مهندسی برق - الکترونیک">مهندسی برق - الکترونیک</option>
                                <option value="گرافیک و طراحی چندرسانه‌ای">گرافیک و طراحی چندرسانه‌ای</option>
                                <option value="حسابداری و مدیریت مالی">حسابداری و مدیریت مالی</option>
                                <option value="معماری و دکوراسیون">معماری و دکوراسیون</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۲- دوره تحصیلی (روزانه / شبانه) <span class="text-rose-500">*</span></label>
                            <select name="study_shift" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                                <option value="روزانه">دوره روزانه</option>
                                <option value="شبانه (نوبت دوم)">دوره شبانه (نوبت دوم)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۳- استان محل زندگی <span class="text-rose-500">*</span></label>
                            <select name="province" id="provinceSelect" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs cursor-pointer">
                                <option value="">-- انتخاب استان --</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">۴- شهر محل زندگی <span class="text-rose-500">*</span></label>
                            <select name="city" id="citySelect" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs cursor-pointer">
                                <option value="">-- ابتدا استان را انتخاب کنید --</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 p-3 bg-slate-50 rounded-xl border">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_foreign_student" value="1" id="is_foreign_student">
                                <span class="text-xs font-bold">دانشجوی غیرایرانی / اتباع خارجی هستم</span>
                            </label>
                            <div id="foreign_field" class="hidden mt-2">
                                <input type="text" name="foreign_student_code" placeholder="شماره گذرنامه یا کد یکتای اقامت" class="w-full px-3 py-1.5 bg-white border rounded-lg text-xs">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">آدرس دقیق محل زندگی <span class="text-rose-500">*</span></label>
                            <textarea name="residential_address" required rows="2" placeholder="خیابان، پلاک، کوچه و کد پستی" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs"></textarea>
                        </div>

                        <div class="sm:col-span-2 p-3 bg-indigo-50/60 rounded-xl border border-indigo-200">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="request_dormitory" value="1" id="request_dormitory">
                                <span class="text-xs font-bold text-indigo-900">متقاضی استفاده از خوابگاه دانشجویی دولتی / خودگردان صمد هستم</span>
                            </label>
                            <div id="dorm_note_field" class="hidden mt-2">
                                <input type="text" name="dormitory_note" placeholder="توضیحات در صورت شرایط خاص یا غیربومی بودن" class="w-full px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between">
                        <button type="button" class="btn-prev px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl" data-target="1">مرحله قبل</button>
                        <button type="button" id="btn-to-step-3" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow">مرحله بعدی: مهارت‌ها ←</button>
                    </div>
                </div>

                <!-- فرم سوم: اطلاعات مهارتی -->
                <div id="step-3" class="hidden space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b pb-3">مرحله سوم: اطلاعات مهارتی و جشنواره‌ها</h2>

                    <div class="p-4 bg-slate-50 rounded-xl border space-y-3">
                        <label class="block text-xs font-bold text-slate-800">ورود مهارت‌ها (تایپ کنید و اینتر را فشار دهید):</label>
                        <div class="flex gap-2">
                            <input type="text" id="skillInput" placeholder="مهارت خود را تایپ کرده و Enter را بزنید (مثال: پایتون)" class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs">
                            <button type="button" id="btnAddSkill" class="px-4 py-2 bg-indigo-600 text-white text-xs rounded-xl font-bold">افزودن</button>
                        </div>
                        <div id="skillsContainer" class="flex flex-wrap gap-2 pt-2"></div>
                        <input type="hidden" name="skills" id="skillsHidden">
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-800">چک‌باکس علاقه‌مندی در جشنواره‌ها و رویدادها:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-3 border rounded-xl bg-white"><input type="checkbox" name="festivals[technical]" value="1" checked> <span>جشنواره‌های فنی و اختراعات</span></label>
                            <label class="flex items-center gap-2 p-3 border rounded-xl bg-white"><input type="checkbox" name="festivals[skillBased]" value="1" checked> <span>مسابقات ملی مهارت و کارگاهی</span></label>
                            <label class="flex items-center gap-2 p-3 border rounded-xl bg-white"><input type="checkbox" name="festivals[religious]" value="1"> <span>جشنواره قرآن و عترت و مذهبی</span></label>
                            <label class="flex items-center gap-2 p-3 border rounded-xl bg-white"><input type="checkbox" name="festivals[cultural]" value="1"> <span>جشنواره فرهنگی و هنری</span></label>
                            <label class="flex items-center gap-2 p-3 border rounded-xl bg-white"><input type="checkbox" name="festivals[robotics]" value="1" checked> <span>مسابقات رباتیک و هوش مصنوعی</span></label>
                            
                            <!-- جشنواره ورزشی با باز شدن فهرست تیم‌های دانشگاه -->
                            <label class="flex items-center gap-2 p-3 border-2 border-emerald-500/60 rounded-xl bg-emerald-50/50 cursor-pointer">
                                <input type="checkbox" name="festivals[sports]" id="cb_sports_festivals" value="1" onchange="toggleSportsTeams(this.checked)"> 
                                <span class="font-bold text-emerald-900">⚽ جشنواره‌های ورزشی و مسابقات پردیس</span>
                            </label>
                        </div>

                        <!-- بخش بازشونده لیست تیم‌های ورزشی دانشگاه -->
                        <div id="sportsTeamsContainer" class="hidden p-4 bg-emerald-50/80 border border-emerald-300 rounded-2xl space-y-3 transition-all animate-fadeIn">
                            <div class="flex items-center justify-between border-b border-emerald-200 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🏆</span>
                                    <h3 class="text-xs font-bold text-emerald-950">عضویت در تیم‌های ورزشی منتخب دانشگاه ملی مهارت:</h3>
                                </div>
                                <span class="text-[10px] text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full font-bold">ثبت‌نام مستقیم تیم‌ها</span>
                            </div>
                            <p class="text-[11px] text-emerald-800">
                                با انتخاب تیم‌های مورد نظر، اطلاعات شما مستقیماً در بانک تربیت‌بدنی دانشگاه و کارتابل مربیان ثبت شده و اطلاعیه‌های اختصاصی هر تیم را دریافت خواهید کرد:
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تیم فوتبال دانشگاه" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">⚽ تیم فوتبال دانشگاه</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="فوتسال" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🥅 فوتسال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="والیبال" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏐 والیبال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="بسکتبال" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏀 بسکتبال</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تنیس روی میز" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏓 تنیس روی میز</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="شنا" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏊 شنا و واترپلو</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="بدمینتون" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏸 بدمینتون</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="دو و میدانی" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🏃 دو و میدانی</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="کشتی آزاد و فرنگی" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🤼 کشتی آزاد و فرنگی</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="sports_teams[]" value="تکواندو و کاراته" class="sports-team-cb">
                                    <span class="font-bold text-slate-800">🥋 تکواندو و کاراته</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between">
                        <button type="button" class="btn-prev px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl" data-target="2">مرحله قبل</button>
                        <button type="button" id="btn-to-step-4" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow">مرحله بعدی: تعیین رمز عبور ←</button>
                    </div>
                </div>

                <!-- فرم چهارم: تعیین رمز عبور -->
                <div id="step-4" class="hidden space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b pb-3">مرحله چهارم: تعیین کلمه عبور و نهایی‌سازی در MySQL</h2>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">کلمه عبور جدید <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" id="password" required placeholder="شامل حروف کوچک، بزرگ و کاراکتر خاص"
                                class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left" dir="ltr">
                            
                            <!-- پیام مناسب در صورت ضعیف بودن رمز عبور زیر ورودی -->
                            <div id="password-feedback" class="hidden mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs"></div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">تکرار کلمه عبور <span class="text-rose-500">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" required placeholder="تکرار کلمه عبور"
                                class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-left" dir="ltr">
                            <div id="match-feedback" class="hidden mt-1 text-xs text-rose-600">کلمه عبور و تکرار آن یکسان نیستند.</div>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-xl text-[11px] text-slate-600 space-y-1">
                            <div class="font-bold text-slate-800">الزامات رمز عبور:</div>
                            <div id="req-len">• حداقل ۸ حرف</div>
                            <div id="req-lower">• حروف کوچک لاتین (a-z)</div>
                            <div id="req-upper">• حروف بزرگ لاتین (A-Z)</div>
                            <div id="req-spec">• حداقل یک کاراکتر ویژه (!@#$%^&*)</div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between">
                        <button type="button" class="btn-prev px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl" data-target="3">مرحله قبل</button>
                        <button type="submit" id="btn-submit" class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow">
                            ثبت نهایی در دیتابیس MySQL و صدور کارت ✓
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- اسکریپت جاوااسکریپت کنترل مراحل و استعلام جدول منبع -->
    <script>
    const skills = ['پایتون', 'طراحی وب'];
    renderSkills();

    // سوئیچ چک‌باکس‌ها
    document.getElementById('is_foreign_student').addEventListener('change', function() {
        document.getElementById('foreign_field').classList.toggle('hidden', !this.checked);
    });
    document.getElementById('request_dormitory').addEventListener('change', function() {
        document.getElementById('dorm_note_field').classList.toggle('hidden', !this.checked);
    });

    // مدیریت تگ مهارت‌ها با Enter
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
        container.innerHTML = skills.map((s, idx) => `
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">
                ${s}
                <button type="button" onclick="removeSkill(${idx})" class="text-rose-500 font-bold">×</button>
            </span>
        `).join('');
        document.getElementById('skillsHidden').value = JSON.stringify(skills);
    }

    // باز و بسته کردن داینامیک لیست تیم‌های ورزشی دانشگاه با تیک زدن جشنواره ورزشی
    window.toggleSportsTeams = function(isChecked) {
        const container = document.getElementById('sportsTeamsContainer');
        if (isChecked) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            // در صورت برداشتن تیک، تیک تیم‌های ورزشی برداشته شود
            document.querySelectorAll('.sports-team-cb').forEach(cb => cb.checked = false);
        }
    };

    // لیست کامل استان‌ها و شهرهای ایران جهت جلوگیری از ورود مقدار نامعتبر توسط دانشجو
    const IRAN_PROVINCES_DATA = {
        'تهران': ['تهران', 'ری', 'شمیرانات', 'شهریار', 'اسلامشهر', 'پاکدشت', 'دماوند', 'ورامین', 'رباط‌کریم', 'پردیس', 'قرچک'],
        'اصفهان': ['اصفهان', 'کاشان', 'نجف‌آباد', 'شاهین‌شهر', 'خمینی‌شهر', 'لنجان', 'مبارکه', 'شهرضا', 'فلاورجان'],
        'فارس': ['شیراز', 'مرودشت', 'جهرم', 'فسا', 'کازرون', 'لارستان', 'داراب', 'اقلید', 'آباده'],
        'خراسان رضوی': ['مشهد', 'نیشابور', 'سبزوار', 'تربت حیدریه', 'قوچان', 'کاشمر', 'چناران', 'سرخس', 'گناباد'],
        'آذربایجان شرقی': ['تبریز', 'مراغه', 'مرند', 'میانه', 'اهر', 'بناب', 'سراب', 'آذرشهر'],
        'آذربایجان غربی': ['ارومیه', 'خوی', 'میاندوآب', 'بوکان', 'مهاباد', 'سلماس', 'پیرانشهر', 'نقده'],
        'مازندران': ['ساری', 'بابل', 'آمل', 'قائم‌شهر', 'بابل‌سر', 'تنکابن', 'چالوس', 'نکا', 'نور', 'رامسر'],
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
        'سمنان': ['سمنان', 'شاهرود', 'دامغان', 'گرمسار', 'مهدی‌شهر'],
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

    // پر کردن سلکت آپشن استان‌ها
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
    // مقداردهی اولیه شهرها بر اساس تهران
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
                
                // مقداردهی خودکار فیلدهای واکشی شده از جدول منبع
                if (data.data.full_name) document.getElementById('full_name').value = data.data.full_name;
                
                setTimeout(() => goToStep(2), 500);
            } else {
                errBox.textContent = 'خطای عدم تطابق: ' + data.message;
                errBox.classList.remove('hidden');
            }
        } catch (e) {
            errBox.textContent = 'خطا در ارتباط با سرور WampServer. لطفاً بررسی کنید دیتابیس MySQL فعال باشد.';
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
            goToStep(parseInt(this.getAttribute('data-target')));
        });
    });

    function goToStep(num) {
        [1, 2, 3, 4].forEach(s => {
            document.getElementById(`step-${s}`).classList.toggle('hidden', s !== num);
            const node = document.getElementById(`step-node-${s}`);
            if (s <= num) {
                node.className = 'flex-1 text-indigo-600 font-bold';
                node.querySelector('div').className = 'w-8 h-8 mx-auto rounded-full bg-indigo-600 text-white flex items-center justify-center mb-1';
            } else {
                node.className = 'flex-1 text-slate-400';
                node.querySelector('div').className = 'w-8 h-8 mx-auto rounded-full bg-slate-200 text-slate-600 flex items-center justify-center mb-1';
            }
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // اعتبارسنجی بلادرنگ رمز عبور
    const pwdInput = document.getElementById('password');
    const pwdFeedback = document.getElementById('password-feedback');

    pwdInput.addEventListener('input', function() {
        const val = this.value;
        const hasLen = val.length >= 8;
        const hasLow = /[a-z]/.test(val);
        const hasUp = /[A-Z]/.test(val);
        const hasSpec = /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>\/?]/.test(val);

        document.getElementById('req-len').className = hasLen ? 'text-emerald-600 font-bold' : '';
        document.getElementById('req-lower').className = hasLow ? 'text-emerald-600 font-bold' : '';
        document.getElementById('req-upper').className = hasUp ? 'text-emerald-600 font-bold' : '';
        document.getElementById('req-spec').className = hasSpec ? 'text-emerald-600 font-bold' : '';

        if (val.length > 0 && (!hasLen || !hasLow || !hasUp || !hasSpec)) {
            pwdFeedback.textContent = 'رمز عبور ضعیف است: باید حداقل ۸ کاراکتر و شامل حروف کوچک، بزرگ و کاراکتر خاص باشد.';
            pwdFeedback.classList.remove('hidden');
        } else {
            pwdFeedback.classList.add('hidden');
        }
    });

    // ثبت نهایی فرم به سمت process_register.php
    document.getElementById('regForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const pwd = pwdInput.value;
        const cpwd = document.getElementById('confirm_password').value;

        if (pwd !== cpwd) {
            document.getElementById('match-feedback').classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.textContent = 'در حال ذخیره‌سازی در دیتابیس MySQL...';

        const formData = new FormData(this);
        try {
            const res = await fetch('process_register.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                alert('ثبت‌نام با موفقیت انجام شد و در پایگاه‌داده MySQL ذخیره گردید.');
                window.location.href = data.redirect || 'dashboard.php';
            } else {
                alert('خطا: ' + data.message);
                btn.disabled = false;
                btn.textContent = 'ثبت نهایی در دیتابیس MySQL و صدور کارت ✓';
            }
        } catch (e) {
            alert('خطای ارتباط با اسکریپت PHP سرور: ' + e.toString());
            btn.disabled = false;
        }
    });

    function autofillDemoRegister() {
        document.getElementById('national_id').value = '0021487591';
        document.getElementById('student_id').value = '4012130045';
        document.getElementById('full_name').value = 'علی رضایی مقدم';
        document.getElementById('mobile').value = '09121234567';
        const pSel = document.getElementById('provinceSelect');
        if (pSel && pSel.options.length > 1) {
            pSel.selectedIndex = 1;
            pSel.dispatchEvent(new Event('change'));
        }
        document.getElementById('field_of_study').value = 'مهندسی کامپیوتر - هوش مصنوعی و داده';
        document.getElementById('request_dormitory').checked = true;
        document.getElementById('password').value = 'Samad@2026!';
        document.getElementById('confirm_password').value = 'Samad@2026!';
        document.getElementById('password').dispatchEvent(new Event('input'));
        skills = ['پایتون (Python)', 'هوش مصنوعی', 'طراحی وب'];
        renderSkills();
        alert('اطلاعات دانشجوی نمونه در فرم بارگذاری شد.');
    }
    </script>
</body>
</html>
