<!DOCTYPE html>
<html dir="rtl" lang="fa" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta content="web_standard" name="shell-type">
<title>دانشگاه ملی مهارت — واحد نیشابور | سامانه یکپارچه صمد</title>
<link rel="stylesheet" href="assets/fonts/fonts.css">
<link rel="stylesheet" href="assets/icons/icons.css">
<link rel="stylesheet" href="assets/tailwind/tailwind.css">
<style>
@layer base {
  html, body { margin: 0; padding: 0; font-family: 'Vazirmatn', sans-serif; }
  body { overscroll-behavior: none; }
  main > :first-child { margin-top: 0 !important; }
  main > :last-child { margin-bottom: 0 !important; }
}
::-webkit-scrollbar { display: none; }
</style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased">
<header class="fixed top-0 left-0 w-full z-50 bg-surface-container-lowest/95 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
  <div class="h-28 max-w-7xl mx-auto px-6 lg:px-12 flex flex-col justify-between py-2">
    <div class="flex items-center justify-between border-b border-surface-variant/40 pb-1.5">
      <div class="flex items-center gap-6">
        <div class="relative flex items-center">
          <span class="material-symbols-outlined absolute right-2 text-on-surface-variant text-[16px]">search</span>
          <input id="landingSearchInput" oninput="filterLandingPrograms(this.value)" class="pr-7 pl-3 py-1 text-label-sm font-label-sm bg-surface-container-low text-on-surface rounded placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest w-52 lg:w-64 transition-all" placeholder="جستجوی رشته‌ها، کارگاه‌ها، اطلاعیه‌ها..." type="text">
        </div>
        <div class="hidden md:flex items-center gap-2">
          <span class="material-symbols-outlined text-outline text-[16px]">language</span>
          <div class="flex items-center gap-1.5 text-label-sm font-label-sm text-on-surface-variant">
            <a href="?lang=fa" class="text-primary font-bold hover:text-on-surface">فا / FA</a>
            <span class="text-outline-variant">/</span>
            <a href="?lang=en" class="hover:text-primary font-medium">EN</a>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3 lg:gap-5">
        <div class="hidden sm:flex items-center gap-4 text-label-sm font-label-sm text-on-surface-variant">
          <a class="hover:text-primary transition-colors" href="#academic-programs">گروه‌های آموزشی</a>
          <a class="hover:text-primary transition-colors" href="#research-innovation">کارگاه‌ها و فناوری</a>
          <a class="hover:text-primary transition-colors" href="#campus-life">پردیس و خوابگاه</a>
          <a class="hover:text-primary transition-colors" href="login.php">پرتال سامانه صمد</a>
        </div>
        <?php if ($loggedInUser): ?>
          <a class="inline-flex items-center gap-1.5 bg-primary-container text-secondary-fixed px-3 py-1 rounded-full text-label-sm font-label-sm hover:bg-primary hover:text-on-primary transition-colors" href="<?= htmlspecialchars($portalUrl) ?>">
            <span class="material-symbols-outlined text-[14px]">school</span>
            <span><?= htmlspecialchars($portalLabelFa) ?> (<?= htmlspecialchars($loggedInUser) ?>)</span>
          </a>
          <a href="logout.php" title="خروج از حساب" class="w-8 h-8 rounded-full bg-surface-container text-primary hover:bg-error-container hover:text-error flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-[18px]">logout</span>
          </a>
        <?php else: ?>
          <a class="inline-flex items-center gap-1.5 bg-primary-container text-secondary-fixed px-3 py-1 rounded-full text-label-sm font-label-sm hover:bg-primary hover:text-on-primary transition-colors" href="login.php">
            <span class="material-symbols-outlined text-[14px]">school</span>
            <span>پرتال دانشجویی</span>
          </a>
          <a href="login.php" title="ورود به حساب کاربری" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex items-center justify-between pt-1">
      <a href="#top" class="flex items-center gap-3">
        <img alt="نشان دانشگاه ملی مهارت نیشابور" class="h-8 w-auto object-contain" src="assets/images/university-emblem.png">
        <div class="flex flex-col">
          <span class="font-headline-sm text-headline-sm tracking-tight text-primary leading-none">دانشگاه ملی مهارت نیشابور</span>
          <span class="font-label-sm text-label-sm tracking-widest text-on-surface-variant">NATIONAL UNIVERSITY OF SKILLS • واحد نیشابور</span>
        </div>
      </a>
      <nav class="hidden xl:flex items-center gap-5 text-body-sm font-body-sm" data-active-classes="text-primary font-semibold border-b-2 border-secondary">
        <a aria-current="page" class="transition-colors pb-1 text-primary font-semibold border-b-2 border-secondary" href="#top">خانه</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#about-university">معرفی واحد</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#academic-programs">رشته‌ها</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#university-stats">آمار و ظرفیت</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#admissions-gateway">پذیرش و ثبت‌نام</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#research-innovation">کارگاه‌ها و نوآوری</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="<?= htmlspecialchars($portalUrl) ?>">دانشجویان</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#news-events">اخبار و اطلاعیه‌ها</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#campus-life">زندگی دانشجویی</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#site-footer">تماس با ما</a>
      </nav>
      <div class="flex items-center gap-2 xl:hidden">
        <a href="?lang=en" class="px-2 py-1 rounded bg-surface-container text-label-sm font-bold text-primary">EN</a>
        <button onclick="toggleMobileDrawer()" aria-label="منوی ناوبری" class="p-1.5 text-on-surface-variant hover:text-on-surface rounded hover:bg-surface-container" type="button">
          <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
      </div>
    </div>
  </div>
  <!-- منوی موبایل و تبلت -->
  <div id="mobileNavDrawer" class="hidden xl:hidden bg-surface-container-lowest border-t border-surface-variant/40 px-6 py-4 shadow-lg">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-body-sm font-medium mb-4">
      <a href="#top" onclick="toggleMobileDrawer()" class="py-1.5 text-primary font-semibold">خانه</a>
      <a href="#about-university" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">معرفی واحد</a>
      <a href="#academic-programs" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">رشته‌ها</a>
      <a href="#news-events" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">اخبار و اطلاعیه‌ها</a>
      <a href="#research-innovation" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">کارگاه‌ها و نوآوری</a>
      <a href="#admissions-gateway" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">پذیرش و ثبت‌نام</a>
    </div>
    <div class="flex flex-wrap gap-2 pt-3 border-t border-surface-variant/40">
      <a href="<?= htmlspecialchars($portalUrl) ?>" class="px-4 py-2 rounded bg-primary text-on-primary text-label-md font-semibold"><?= htmlspecialchars($portalLabelFa) ?></a>
      <a href="register.php" class="px-4 py-2 rounded bg-secondary text-on-secondary text-label-md font-semibold">ثبت‌نام جدید</a>
    </div>
  </div>
</header>

<main id="top" class="w-full pt-28 bg-surface min-h-[calc(100vh-28rem)]">
<div class="flex flex-col w-full">
<!-- 1. HERO SECTION -->
<section class="relative w-full overflow-hidden bg-primary-container text-on-primary">
  <div class="absolute inset-0 z-0">
    <img alt="پردیس دانشگاه ملی مهارت واحد نیشابور" class="w-full h-full object-cover object-center filter brightness-[0.72] contrast-[1.08] scale-[1.01] transform transition-transform duration-1000 ease-out hover:scale-105" src="assets/images/hero-campus.jpg">
    <div class="absolute inset-0 bg-gradient-to-t from-primary-container via-primary-container/60 to-primary-container/30 mix-blend-multiply"></div>
    <div class="absolute inset-0 bg-gradient-to-l from-primary-container/90 via-primary-container/40 to-transparent"></div>
  </div>
  <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-12 pt-24 pb-20 md:pt-32 md:pb-28 flex flex-col justify-end min-h-[740px]">
    <div class="max-w-3xl">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary/20 backdrop-blur-md text-secondary-fixed text-label-md font-label-md mb-6 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-secondary-fixed animate-pulse"></span>
        <span>آغاز پذیرش و ثبت‌نام دانشجویان • سال تحصیلی ۱۴۰۴–۱۴۰۵</span>
      </div>
      <h1 class="font-display-hero text-display-hero text-on-primary tracking-tight leading-tight mb-6">
        مهارت‌آموزی تخصصی، پیوند آموزش عالی با صنعت و بازار کار
      </h1>
      <p class="font-body-lg text-body-lg text-surface-variant font-normal leading-relaxed mb-8 max-w-2xl">
        دانشگاه ملی مهارت واحد نیشابور به عنوان کانون آموزش‌های مهارتی و فنی‌مهندسی شرق کشور، با تکیه بر کارگاه‌های مجهز، اساتید مجرب و رویکرد کارآفرینی، تکنسین‌ها و مهندسان کاربردی فردا را پرورش می‌دهد.
      </p>
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <a class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded bg-primary text-on-primary hover:bg-on-surface font-title-lg text-body-md transition-all shadow-md group" href="#academic-programs">
          <span>مشاهده رشته‌ها و گروه‌های آموزشی</span>
          <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
        </a>
        <a class="inline-flex items-center gap-2 px-6 py-3.5 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 backdrop-blur-md text-on-primary font-title-lg text-body-md transition-all shadow-sm" href="register.php">
          <span>ثبت‌نام پذیرفته‌شدگان ۱۴۰۴</span>
          <span class="material-symbols-outlined text-[18px]">open_in_new</span>
        </a>
      </div>
    </div>
    <!-- Trust Ribbon / Stat Badges -->
    <div class="mt-14 pt-8 bg-surface-container-lowest/10 backdrop-blur-md rounded-xl p-6 shadow-xl">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-on-primary">
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">workspace_premium</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">۷۵٪+</span>
            <span class="font-label-sm text-label-sm text-surface-variant">نرخ اشتغال مهارت‌آموختگان</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">precision_manufacturing</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">۶۵٪</span>
            <span class="font-label-sm text-label-sm text-surface-variant">سهم آموزش عملی و کارگاهی</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">school</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">۱۶+</span>
            <span class="font-label-sm text-label-sm text-surface-variant">رشته کاردانی و کارشناسی</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">biotech</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">۲۲</span>
            <span class="font-label-sm text-label-sm text-surface-variant">کارگاه و آزمایشگاه تخصصی</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 2. QUICK ACCESS SHORTCUT SECTION -->
<section class="max-w-7xl mx-auto px-6 lg:px-12 py-16 w-full -mt-6 z-20">
  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="<?= htmlspecialchars($portalUrl) ?>">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">account_circle</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">پرتال دانشجویی صمد</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">احراز هویت یکپارچه، گراف مهارت‌ها، وضعیت خوابگاه و امور رفاهی.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="register.php">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">app_registration</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">ثبت‌نام پذیرفته‌شدگان</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">تکمیل پرونده ورود، ثبت مهارت‌های فردی و درخواست اسکان خوابگاه.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#academic-programs">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">view_timeline</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">گروه‌های آموزشی و رشته‌ها</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">معرفی رشته‌های کاردانی پیوسته، کارشناسی ناپیوسته و کارگاه‌های عملی.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#academic-calendar">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">calendar_month</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">تقویم آموزشی نیم‌سال</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">زمان‌بندی انتخاب واحد، حذف و اضافه، کارآموزی و امتحانات پایانی.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#research-innovation">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">local_library</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">کتابخانه و مرکز اسناد</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">دسترسی به منابع تخصصی فنی و مهندسی، پایان‌نامه‌ها و سالن مطالعه واحد.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="<?= htmlspecialchars($portalUrl) ?>">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">devices</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">سامانه کارآموزی و مهارت</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">مدیریت دروس کارگاهی، گزارش‌های کارآموزی صنعتی و نقشه مهارت‌ها.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#research-innovation">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">science</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">پژوهش و جشنواره‌های مهارت</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">ثبت طرح‌های فنی، المپیادهای ملی مهارت، رباتیک و هسته‌های فناور.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="<?= htmlspecialchars($loggedRole === 'student' ? 'dashboard.php?tab=dorm' : $portalUrl) ?>">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">support_agent</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px] -scale-x-100">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">خدمات دانشجویی و خوابگاه</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">امور اسکان و خوابگاه‌های نیشابور، تغذیه، مشاوره و رفاه دانشجویان.</p>
      </div>
    </a>
  </div>
</section>

<!-- 3. ABOUT THE UNIVERSITY -->
<section id="about-university" class="max-w-7xl mx-auto px-6 lg:px-12 py-20 w-full">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
    <div class="lg:col-span-7 flex flex-col">
      <div class="inline-flex items-center gap-2 mb-3">
        <span class="w-8 h-[2px] bg-secondary"></span>
        <span class="font-label-md text-label-md text-secondary font-semibold">دانشگاه ملی مهارت • واحد نیشابور (استان خراسان رضوی)</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary mb-6">
        آموزش مهارت‌محور، تربیت نیروی کارآمد و پشتیبانی از صنعت بومی
      </h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-8">
        دانشگاه ملی مهارت واحد نیشابور (دانشکده و آموزشکده‌های فنی و حرفه‌ای نیشابور) با بیش از سه دهه سابقه درخشان در استان خراسان رضوی، مأموریت خود را بر آموزش کاربردی، تقویت مهارت‌های کارگاهی و آماده‌سازی مستقیم دانشجویان برای ورود به صنایع منطقه، شهرک‌های صنعتی و کارآفرینی دانش‌بنیان استوار کرده است.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <div class="bg-surface-container-low p-6 rounded-lg shadow-sm">
          <div class="flex items-center gap-2 mb-2 text-primary font-title-lg text-title-lg">
            <span class="material-symbols-outlined text-secondary text-[22px]">explore</span>
            <h4>رسالت ما</h4>
          </div>
          <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
            تربیت تکنسین‌ها و مهندسان مجرب با تلفیق دانش نظری و تمرین عملی مستمر در کارگاه‌ها و آزمایشگاه‌های استاندارد صنعتی.
          </p>
        </div>
        <div class="bg-surface-container-low p-6 rounded-lg shadow-sm">
          <div class="flex items-center gap-2 mb-2 text-primary font-title-lg text-title-lg">
            <span class="material-symbols-outlined text-secondary text-[22px]">visibility</span>
            <h4>چشم‌انداز ما</h4>
          </div>
          <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
            تبدیل شدن به مرجع برتر آموزش‌های فنی و مهارتی در خراسان رضوی و قطب نوآوری صنعتی و اشتغال‌آفرینی دانشجویی.
          </p>
        </div>
      </div>
      <div class="bg-surface-container p-6 rounded-lg mb-8">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-primary text-on-primary rounded">
            <span class="material-symbols-outlined text-[24px]">history_edu</span>
          </div>
          <div>
            <span class="font-label-sm text-label-sm text-secondary font-semibold">پیشینه و ظرفیت‌های آموزشی واحد نیشابور</span>
            <p class="font-body-sm text-body-sm text-on-surface mt-1 leading-relaxed">
              از آموزشکده‌های فنی و مهندسی پیشگام در شهرستان نیشابور تا شبکه‌ای یکپارچه شامل ۲۲ کارگاه تخصصی در حوزه‌های مکانیک، برق، کامپیوتر، معماری و عمران و تربیت بیش از ۱۵,۰۰۰ فارغ‌التحصیل شاغل در صنایع کشور.
            </p>
          </div>
        </div>
      </div>
      <a class="inline-flex items-center gap-3 font-title-lg text-body-md text-primary hover:text-secondary group transition-colors" href="#university-stats">
        <span>آشنایی بیشتر با آمار، ظرفیت‌ها و افتخارات واحد نیشابور</span>
        <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
      </a>
    </div>
    <div class="lg:col-span-5 flex flex-col gap-6">
      <div class="relative rounded-2xl overflow-hidden shadow-xl bg-surface-container-highest">
        <img alt="ساختمان آموزشی و کارگاه‌های دانشگاه ملی مهارت نیشابور" class="w-full h-80 object-cover object-center" src="assets/images/chancellor-portrait.jpg">
        <div class="p-8 bg-surface-container-lowest">
          <span class="material-symbols-outlined text-secondary text-[40px] opacity-40 mb-3 block">format_quote</span>
          <blockquote class="font-headline-sm text-headline-sm text-primary italic leading-snug mb-6">
            «هدف ما در دانشگاه ملی مهارت نیشابور تنها اعطای مدرک دانشگاهی نیست؛ بلکه هر دانشجو باید در کنار دانش علمی، با یک مهارت کاربردی و آمادگی کامل برای ورود به صنعت و کارآفرینی فارغ‌التحصیل شود.»
          </blockquote>
          <div class="flex items-center justify-between pt-4">
            <div>
              <p class="font-title-lg text-title-lg text-primary">ریاست دانشگاه ملی مهارت — واحد نیشابور</p>
              <p class="font-label-sm text-label-sm text-on-surface-variant">استان خراسان رضوی • تربیت نیروی متخصص و کارآفرین</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-secondary">
              <span class="material-symbols-outlined text-[28px]">verified</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. ACADEMIC PROGRAMS -->
<section class="bg-surface-container-low py-20 w-full" id="academic-programs">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
      <div>
        <div class="inline-flex items-center gap-2 mb-2">
          <span class="w-8 h-[2px] bg-secondary"></span>
          <span class="font-label-md text-label-md text-secondary font-semibold">آموزش کاربردی در مقاطع کاردانی پیوسته و کارشناسی ناپیوسته</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary">گروه‌های آموزشی و رشته‌های تخصصی واحد نیشابور</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
        رشته‌های مهارتی منطبق با نیاز روز صنایع شرق کشور با بیش از ۶۵ درصد واحدهای عملی و کارگاهی.
      </p>
    </div>
    <!-- Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 mb-10 pb-2" id="programFilterTabs">
      <button type="button" onclick="filterProgramsByCategory('all', this)" class="program-tab px-5 py-2 rounded-full bg-primary text-on-primary font-body-sm text-body-sm font-medium shadow-sm cursor-pointer">همه گروه‌های آموزشی</button>
      <button type="button" onclick="filterProgramsByCategory('engineering', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">برق، رایانه و فناوری</button>
      <button type="button" onclick="filterProgramsByCategory('sciences', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">مکانیک و ساخت و تولید</button>
      <button type="button" onclick="filterProgramsByCategory('business', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">حسابداری و مدیریت</button>
      <button type="button" onclick="filterProgramsByCategory('humanities', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">عمران، معماری و هنر</button>
    </div>
    <!-- Programs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="programsGrid">
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="engineering" data-search="مهندسی کامپیوتر نرم افزار فناوری اطلاعات شبکه وب پایگاه داده">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold">گروه کامپیوتر و IT</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">مهندسی فناوری اطلاعات و نرم‌افزار</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            آموزش عملی طراحی و توسعه وب، برنامه‌نویسی تحت شبکه، مدیریت پایگاه داده، امنیت سیستم‌ها و مهندسی نرم‌افزار کاربردی.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>سایت‌های کامپیوتری مجهز به شبکه داخلی و لینوکس</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>آزمایشگاه سخت‌افزار، میکروکنترلرها و اینترنت اشیاء</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="engineering" data-search="مهندسی برق الکترونیک قدرت plc اتوماسیون صنعتی تابلو برق">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant font-label-sm text-label-sm font-bold">گروه برق و قدرت</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">مهندسی برق و الکترونیک صنعتی</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            مهارت‌آموزی در طراحی و مونتاژ تابلوهای برق صنعتی، برنامه‌نویسی PLC، مدارهای فرمان، تأسیسات الکتریکی و تعمیر تجهیزات الکترونیکی.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>کارگاه سیم‌کشی، تابلو برق و مدارهای فرمان صنعتی</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>آزمایشگاه ماشین‌های الکتریکی، درایو و PLC</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="sciences engineering" data-search="مکانیک خودرو ساخت و تولید cnc تراشکاری جوشکاری نقشه کشی صنعتی">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold">گروه مکانیک و ساخت</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">مکانیک خودرو و ساخت و تولید</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            آموزش عیب‌یابی سیستم‌های انژکتوری و برق خودرو، تراشکاری، فرزکاری CNC، جوشکاری تخصصی و طراحی قطعات با SolidWorks و CATIA.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>کارگاه مجهز ماشین‌افزار، تراش و CNC</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>کارگاه تخصصی عیب‌یابی، دیاگ و تعمیرات خودرو</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="humanities sciences" data-search="مهندسی عمران ساختمان نقشه برداری بتن سازه متره">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant font-label-sm text-label-sm font-bold">گروه عمران و نقشه‌برداری</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">مهندسی اجرایی عمران و ساختمان</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            اجرای پروژه‌های ساختمانی بتنی و فلزی، نقشه‌برداری با دوربین‌های توتال‌استیشن، متره و برآورد و آزمایش‌های کنترل کیفیت مصالح.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>آزمایشگاه مکانیک خاک و مقاومت مصالح و بتن</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>عملیات میدانی نقشه‌برداری و طراحی با AutoCAD</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="humanities" data-search="معماری نقشه کشی طراحی داخلی گرافیک هنر ماکت سازی">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold">گروه معماری و هنر</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">معماری، طراحی داخلی و گرافیک</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            طراحی پلان‌های مسکونی و اداری، ماکت‌سازی، رندرینگ سه‌بعدی معماری، اصول دکوراسیون داخلی و طراحی گرافیک و هویت بصری.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>آتلیه‌های تخصصی رسم فنی، طراحی و ماکت‌سازی</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>کارگاه نرم‌افزارهای 3ds Max، Revit و فتوشاپ</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="business" data-search="حسابداری مالی مدیریت کسب و کار مالیاتی حسابرسی">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold">گروه حسابداری و مالی</span>
            <span class="font-label-sm text-label-sm text-outline">کاردانی پیوسته • کارشناسی ناپیوسته</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">حسابداری و مدیریت کسب‌وکار</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            آموزش کاربردی حسابداری صنعتی و مالیاتی، تنظیم اظهارنامه، کار با نرم‌افزارهای جامع مالی و حقوق و دستمزد و مدیریت واحدهای صنفی.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>کارگاه نرم‌افزارهای حسابداری و سامانه‌های مالیاتی</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>پروژه‌های عملی حسابرسی واحدهای تولیدی شهرستان</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">نوبت روزانه و شبانه</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>ثبت‌نام و جزئیات رشته</span>
            <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </div>
    </div>
    <!-- Action Card -->
    <div class="mt-8 p-6 rounded-xl bg-primary text-on-primary flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-secondary-fixed text-[36px]">menu_book</span>
        <div>
          <h4 class="font-title-lg text-title-lg font-semibold">به سایر رشته‌های فنی و مهارتی علاقه‌مندید؟</h4>
          <p class="font-body-sm text-body-sm text-surface-variant">دانشگاه ملی مهارت واحد نیشابور در رشته‌های متنوع کاردانی پیوسته و کارشناسی ناپیوسته (روزانه و شبانه) دانشجو می‌پذیرد.</p>
        </div>
      </div>
      <a class="px-6 py-2.5 rounded bg-surface-container-lowest text-primary font-title-lg text-body-sm hover:bg-surface-container transition-colors whitespace-nowrap" href="register.php">
        ثبت‌نام و مشاهده فهرست رشته‌ها
      </a>
    </div>
  </div>
</section>

<!-- 5. UNIVERSITY STATISTICS -->
<section id="university-stats" class="w-full bg-primary text-on-primary py-20">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-8 lg:gap-12 text-center md:text-right">
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-bold">۲,۲۰۰+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-semibold">دانشجوی مهارتی شاغل به تحصیل</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">دانشجویان کاردانی و کارشناسی نیشابور (<?= number_format($stats['students_count']) ?> فعال در صمد)</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-bold">۸۵+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-semibold">استاد و مربی کارگاه</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">اعضای هیئت علمی و مدرسان باتجربه صنعتی</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-bold">۲۲</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-semibold">کارگاه و آزمایشگاه تخصصی</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1"><?= number_format($stats['rooms_count']) ?> اتاق خوابگاهی ثبت‌شده در سامانه</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-bold">۱۵,۰۰۰+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-semibold">فارغ‌التحصیل شاغل در صنعت</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">نیروی متخصص شاغل در خراسان رضوی و سراسر کشور</span>
      </div>
      <div class="flex flex-col col-span-2 md:col-span-1">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-bold">۷۵٪+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-semibold">نرخ اشتغال پس از تحصیل</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">از بالاترین آمارهای جذب در بازار کار آموزش عالی</span>
      </div>
    </div>
  </div>
</section>

<!-- 6. LATEST NEWS & ANNOUNCEMENTS -->
<section id="news-events" class="max-w-7xl mx-auto px-6 lg:px-12 py-20 w-full">
  <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
    <div>
      <div class="inline-flex items-center gap-2 mb-2">
        <span class="w-8 h-[2px] bg-secondary"></span>
        <span class="font-label-md text-label-md text-secondary font-semibold">اخبار واحد نیشابور</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary">آخرین اخبار و دستاوردهای مهارتی</h2>
    </div>
    <a class="font-title-lg text-body-md text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
      <span>مشاهده اخبار در پرتال صمد</span>
      <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
    </a>
  </div>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="مسابقات رباتیک و مهارتی دانشجویان نیشابور" class="w-full h-full object-cover" src="assets/images/news-bioengineering.jpg">
          <span class="absolute top-3 right-3 px-2 py-1 rounded bg-primary text-on-primary text-label-sm font-label-sm font-semibold">فناوری و مهارت</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>۸ فروردین ۱۴۰۴</span>
              <span>•</span>
              <span>۳ دقیقه مطالعه</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              درخشش تیم رباتیک و اتوماسیون دانشجویان واحد نیشابور در مسابقات ملی مهارت
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#research-innovation">
            <span>مشاهده جزئیات خبر</span>
            <span class="material-symbols-outlined text-[14px] group-hover:-translate-x-0.5 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </article>
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="تجهیز کارگاه CNC و برق صنعتی در نیشابور" class="w-full h-full object-cover" src="assets/images/news-quantum-center.jpg">
          <span class="absolute top-3 right-3 px-2 py-1 rounded bg-secondary text-on-secondary text-label-sm font-label-sm font-semibold">توسعه کارگاه‌ها</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>۴ فروردین ۱۴۰۴</span>
              <span>•</span>
              <span>۳ دقیقه مطالعه</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              راه‌اندازی تجهیزات جدید کارگاه ماشین‌آلات CNC و برق صنعتی در پردیس نیشابور
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#research-innovation">
            <span>مشاهده گزارش تصویری</span>
            <span class="material-symbols-outlined text-[14px] group-hover:-translate-x-0.5 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </article>
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="همکاری دانشگاه ملی مهارت نیشابور با شهرک صنعتی خیام" class="w-full h-full object-cover" src="assets/images/news-faculty-award.jpg">
          <span class="absolute top-3 right-3 px-2 py-1 rounded bg-inverse-surface text-on-primary text-label-sm font-label-sm font-semibold">اشتغال و صنعت</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>۲۹ اسفند ۱۴۰۳</span>
              <span>•</span>
              <span>۲ دقیقه مطالعه</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              انعقاد تفاهم‌نامه کارآموزی و جذب نیرو با واحدهای شهرک صنعتی خیام نیشابور
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#about-university">
            <span>مطالعه متن تفاهم‌نامه</span>
            <span class="material-symbols-outlined text-[14px] group-hover:-translate-x-0.5 transition-transform rotate-180">arrow_forward</span>
          </a>
        </div>
      </article>
    </div>
    <div class="lg:col-span-4 bg-surface-container p-6 rounded-xl shadow-sm flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-2 mb-4 text-primary">
          <span class="material-symbols-outlined text-secondary text-[24px]">campaign</span>
          <h3 class="font-title-lg text-title-lg font-semibold">تابلو اعلانات واحد نیشابور</h3>
        </div>
        <div class="space-y-4">
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold mb-1">اطلاعیه آموزش</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">مهلت حذف و اضافه نیم‌سال دوم</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">تغییرات انتخاب واحد دروس نظری و کارگاهی تا ساعت ۲۴ روز جمعه ۱۵ فروردین فعال است.</p>
          </div>
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm font-semibold mb-1">امور خوابگاه‌ها</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">ثبت وضعیت تجهیزات اتاق‌های خوابگاه</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">دانشجویان خوابگاهی می‌توانند گزارش امکانات اتاق خود را در بخش میزکار صمد بررسی و ثبت کنند.</p>
          </div>
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold mb-1">فرهنگی و ورزشی</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">فراخوان جشنواره‌های مهارتی و تیم‌های ورزشی</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">عضویت در تیم‌های فوتسال، والیبال و ثبت علایق در گراف مهارت‌های پرتال دانشجویی آغاز شد.</p>
          </div>
        </div>
      </div>
      <a class="inline-flex items-center justify-between text-body-sm font-body-sm font-semibold text-primary hover:text-secondary mt-6 pt-4 border-t border-surface-variant/50" href="<?= htmlspecialchars($portalUrl) ?>">
        <span>مشاهده تمامی اطلاعیه‌های واحد در سامانه صمد</span>
        <span class="material-symbols-outlined text-[18px] rotate-180">arrow_forward</span>
      </a>
    </div>
  </div>
</section>

<!-- 7. RESEARCH & INNOVATION -->
<section id="research-innovation" class="bg-surface-container-low py-20 w-full">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
      <div>
        <div class="inline-flex items-center gap-2 mb-2">
          <span class="w-8 h-[2px] bg-secondary"></span>
          <span class="font-label-md text-label-md text-secondary font-semibold">کارآفرینی، مهارت و ارتباط با صنعت</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary">کارگاه‌های مرجع و مراکز نوآوری نیشابور</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
        تبدیل ایده‌های دانشجویی به نمونه‌های صنعتی و تربیت نیروی آماده کار در تعامل نزدیک با صنایع شهرستان نیشابور.
      </p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <div class="lg:col-span-7 space-y-6">
        <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row gap-6 items-center">
          <div class="w-full md:w-48 h-36 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
            <img alt="کارگاه رباتیک و اتوماسیون صنعتی واحد نیشابور" class="w-full h-full object-cover" src="assets/images/lab-robotics.jpg">
          </div>
          <div>
            <div class="inline-block px-2.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-label-sm font-label-sm font-semibold mb-2">کارگاه مرجع</div>
            <h3 class="font-title-lg text-title-lg text-primary mb-2">مرکز رباتیک، مکاترونیک و اتوماسیون صنعتی (PLC)</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-4">
              طراحی مدارهای فرمان هوشمند، ساخت ربات‌های مسیریاب و امدادگر و پیاده‌سازی پروژه‌های کنترل صنعتی ویژه المپیادهای کشوری مهارت.
            </p>
            <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
              <span>مشاهده فعالیت‌ها در پرتال</span>
              <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
            </a>
          </div>
        </div>
        <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row gap-6 items-center">
          <div class="w-full md:w-48 h-36 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
            <img alt="کارگاه انرژی‌های تجدیدپذیر و الکترونیک" class="w-full h-full object-cover" src="assets/images/lab-clean-energy.jpg">
          </div>
          <div>
            <div class="inline-block px-2.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-label-sm font-label-sm font-semibold mb-2">انرژی و تأسیسات</div>
            <h3 class="font-title-lg text-title-lg text-primary mb-2">کارگاه انرژی‌های تجدیدپذیر، تأسیسات و الکترونیک</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-4">
              آموزش عملی نصب سامانه‌های خورشیدی (فتوولتائیک)، بهینه‌سازی مصرف انرژی در ساختمان و طراحی بردهای الکترونیکی کاربردی.
            </p>
            <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
              <span>آشنایی با تجهیزات کارگاه</span>
              <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform rotate-180">arrow_forward</span>
            </a>
          </div>
        </div>
        <div class="p-8 rounded-xl bg-primary text-on-primary shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
          <div>
            <span class="px-2.5 py-1 rounded bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm font-bold">مرکز رشد و کارآفرینی</span>
            <h3 class="font-headline-sm text-headline-sm text-on-primary mt-2 mb-2">مرکز نوآوری و هسته‌های فناور واحد نیشابور</h3>
            <p class="font-body-sm text-body-sm text-surface-variant max-w-lg">
              حمایت از تیم‌ها و استارتاپ‌های دانشجویی دانشگاه ملی مهارت نیشابور در مسیر نمونه‌سازی محصول، دریافت مشاوره تخصصی و تجاری‌سازی ایده‌های مهارتی.
            </p>
          </div>
          <a class="px-5 py-3 rounded bg-secondary text-on-secondary font-title-lg text-body-sm hover:bg-secondary-container hover:text-on-secondary-container transition-colors whitespace-nowrap shadow-sm" href="register.php">
            ثبت ایده و عضویت دانشجویی
          </a>
        </div>
      </div>
      <div class="lg:col-span-5 bg-surface-container-lowest p-8 rounded-xl shadow-sm">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-surface-variant/40">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary text-[26px]">library_books</span>
            <h3 class="font-headline-sm text-headline-sm text-primary">دستاوردها و پروژه‌های منتخب</h3>
          </div>
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">سال تحصیلی ۱۴۰۳–۱۴۰۴</span>
        </div>
        <div class="space-y-6">
          <div class="pb-6 border-b border-surface-variant/40">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">المپیاد ملی مهارت</span>
              <span class="font-label-sm text-label-sm text-outline">اسفند ۱۴۰۳</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              کسب رتبه‌های برتر استانی در گرایش‌های طراحی وب، تراش CNC و تأسیسات الکتریکی
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">توسط دانشجویان کارشناسی ناپیوسته دانشگاه ملی مهارت نیشابور</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">حوزه: مهارت‌های صنعتی و فناوری اطلاعات</div>
          </div>
          <div class="pb-6 border-b border-surface-variant/40">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">طرح کاربردی بومی</span>
              <span class="font-label-sm text-label-sm text-outline">بهمن ۱۴۰۳</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              طراحی و ساخت سامانه هوشمند پایش دما و رطوبت گلخانه‌های صنعتی منطقه نیشابور
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">پروژه مشترک گروه برق، الکترونیک و فناوری اطلاعات</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">حوزه: اتوماسیون و اینترنت اشیاء (IoT)</div>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">تحول دیجیتال دانشگاهی</span>
              <span class="font-label-sm text-label-sm text-outline">آذر ۱۴۰۳</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              راه‌اندازی سامانه یکپارچه مدیریت خوابگاه و گراف مهارت‌های دانشجویی (صمد)
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">ویژه پایش مهارت‌ها، جشنواره‌ها و امور رفاهی دانشجویان واحد نیشابور</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">حوزه: نرم‌افزارهای سازمانی دانشجویی</div>
          </div>
        </div>
        <a class="mt-6 inline-flex items-center gap-2 font-title-lg text-body-sm text-primary font-semibold hover:text-secondary" href="<?= htmlspecialchars($portalUrl) ?>">
          <span>مشاهده گراف مهارت‌ها و جشنواره‌ها در پرتال صمد</span>
          <span class="material-symbols-outlined text-[16px] rotate-180">arrow_forward</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- 8. CAMPUS LIFE -->
<section id="campus-life" class="max-w-7xl mx-auto px-6 lg:px-12 py-20 w-full">
  <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
    <div>
      <div class="inline-flex items-center gap-2 mb-2">
        <span class="w-8 h-[2px] bg-secondary"></span>
        <span class="font-label-md text-label-md text-secondary font-semibold">محیط علمی، ورزشی و رفاهی واحد نیشابور</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary">زندگی دانشجویی در دانشگاه ملی مهارت نیشابور</h2>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
      فضایی پویا برای یادگیری عملی، فعالیت‌های فرهنگی، ورزش دانشگاهی و زندگی جمعی در خوابگاه‌های دانشجویی.
    </p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">apartment</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">ساختمان‌های آموزشی و پردیس</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          کلاس‌های استاندارد، سالن آمفی‌تئاتر، کتابخانه تخصصی، سلف سرویس و فضاهای سبز آرام در شهرستان نیشابور.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold">فضای آموزشی استاندارد</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">groups</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">انجمن‌های علمی و کانون‌های فرهنگی</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          انجمن‌های علمی برق، کامپیوتر، مکانیک و عمران در کنار کانون‌های هلال احمر، عکاسی، قرآن و عترت و نشریات دانشجویی.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold">پویایی علمی و فرهنگی</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">fitness_center</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">تربیت بدنی و تیم‌های ورزشی</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          سالن ورزشی سرپوشیده، زمین چمن و تیم‌های فعال فوتسال، والیبال، تنیس روی میز و شطرنج در المپیادهای ورزشی دانشگاه ملی مهارت.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold">تندرستی و نشاط دانشجویی</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">precision_manufacturing</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">کارگاه‌ها و آزمایشگاه‌های مجهز</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          دسترسی عملی دانشجویان به دستگاه‌های جوش، تراش و فرز CNC، تجهیزات دیاگ خودرو، بردهای الکترونیک و سایت‌های کامپیوتری.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold">آموزش کاربردی و کارگاهی</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">bed</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">خوابگاه‌های دانشجویی و سامانه صمد</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          مدیریت شفاف اتاق‌های خوابگاه واحد نیشابور، سرپرستی شبانه مستمر، بررسی امکانات اتاق و ثبت درخواست‌ها در سامانه صمد.
        </p>
      </div>
      <a href="<?= htmlspecialchars($portalUrl) ?>" class="font-label-md text-label-md text-secondary font-semibold hover:underline">ورود به بخش خوابگاه در سامانه صمد ←</a>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">how_to_vote</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">شورای صنفی و مرکز مشاوره</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          پیگیری مطالبات رفاهی و صنفی دانشجویان، خدمات مشاوره تحصیلی و شغلی و تسهیلات وام و تغذیه زیر نظر معاونت دانشجویی.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold">همراهی مستمر با دانشجو</span>
    </div>
  </div>
</section>

<!-- 9. EVENTS & ACADEMIC CALENDAR -->
<section id="academic-calendar" class="bg-surface-container-low py-20 w-full">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
      <div class="lg:col-span-7">
        <div class="inline-flex items-center gap-2 mb-2">
          <span class="w-8 h-[2px] bg-secondary"></span>
          <span class="font-label-md text-label-md text-secondary font-semibold">همایش‌ها، جشنواره‌ها و برنامه‌های دانشجویی</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary mb-8">رویدادهای پیش‌رو در واحد نیشابور</h2>
        <div class="space-y-4">
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold">فروردین</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">۱۵</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>ساعت ۱۰:۰۰ تا ۱۲:۳۰ • سالن اجتماعات واحد نیشابور</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">کارگاه تخصصی آشنایی با بازار کار و مسیرهای شغلی مهارت‌محور</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                نشست تخصصی با حضور مدیران فنی واحدهای صنعتی شهرک صنعتی خیام نیشابور و اساتید گروه‌های مهندسی.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>ثبت حضور در پرتال دانشجویی</span>
                  <span class="material-symbols-outlined text-[16px] rotate-180">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold">فروردین</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">۲۲</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>ساعت ۹:۰۰ تا ۱۴:۰۰ • محوطه کارگاه‌های مرکزی</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">نمایشگاه دستاوردهای فنی، رباتیک و اختراعات دانشجویی نیشابور</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                معرفی پروژه‌های برگزیده دانشجویان جهت شرکت در جشنواره‌های منطقه‌ای و کشوری دانشگاه ملی مهارت.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>مشاهده فراخوان‌های جشنواره</span>
                  <span class="material-symbols-outlined text-[16px] rotate-180">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold">اردیبهشت</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">۲۹</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>ساعت ۱۶:۰۰ تا ۱۸:۳۰ • سالن ورزشی پردیس</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">مسابقات جام بهاره فوتسال و والیبال خوابگاه‌های دانشجویی</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                رقابت دوستانه بین تیم‌های خوابگاهی و رشته‌های مختلف دانشگاه ملی مهارت واحد نیشابور.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>ثبت‌نام تیم‌های ورزشی</span>
                  <span class="material-symbols-outlined text-[16px] rotate-180">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:col-span-5 bg-surface-container-lowest p-8 rounded-xl shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-6 mb-6 border-b border-surface-variant/40">
            <div class="flex items-center gap-3">
              <span class="material-symbols-outlined text-secondary text-[26px]">event_note</span>
              <h3 class="font-headline-sm text-headline-sm text-primary">تقویم آموزشی</h3>
            </div>
            <span class="font-label-sm text-label-sm text-secondary font-bold">نیم‌سال بهار و پاییز ۱۴۰۴</span>
          </div>
          <div class="space-y-6">
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">۲۶ فروردین</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">پایان ثبت‌نام جشنواره‌های مهارتی</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">آخرین مهلت ثبت مهارت‌ها و اعلام آمادگی در جشنواره‌های فنی، فرهنگی و ورزشی صمد.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">۱۲ اردیبهشت</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">مهلت نهایی حذف اضطراری یک درس</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">آخرین روز ثبت درخواست حذف اضطراری دروس نظری مقطع کاردانی و کارشناسی.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">۲۹ اردیبهشت – ۱۴ خرداد</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">بازه امتحانات عملی و نظری پایان ترم</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">برگزاری آزمون‌های کارگاهی و امتحانات پایانی در ساختمان‌های آموزشی واحد نیشابور.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">۲۰ خرداد</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">شروع دوره کارآموزی تابستانی در صنایع</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">معرفی دانشجویان واجد شرایط به کارخانه‌ها و شرکت‌های فنی شهرستان نیشابور و استان.</p>
              </div>
            </div>
          </div>
        </div>
        <a class="mt-8 p-3 rounded bg-surface-container hover:bg-surface-variant text-primary text-center font-title-lg text-body-sm font-semibold transition-colors" href="<?= htmlspecialchars($portalUrl) ?>">
          دریافت تقویم آموزشی سال تحصیلی ۱۴۰۴–۱۴۰۵
        </a>
      </div>
    </div>
  </div>
</section>

<!-- 10. STUDENT & ALUMNI TESTIMONIALS -->
<section class="max-w-7xl mx-auto px-6 lg:px-12 py-20 w-full">
  <div class="text-center max-w-2xl mx-auto mb-16">
    <div class="inline-flex items-center gap-2 mb-2">
      <span class="w-8 h-[2px] bg-secondary"></span>
      <span class="font-label-md text-label-md text-secondary font-semibold">تجربه دانشجویان و دانش‌آموختگان</span>
      <span class="w-8 h-[2px] bg-secondary"></span>
    </div>
    <h2 class="font-headline-lg text-headline-lg text-primary">مهارت‌آموختگانی که وارد بازار کار شده‌اند</h2>
    <p class="font-body-md text-body-md text-on-surface-variant mt-2">
      روایت‌های واقعی از فضای صمیمی کارگاه‌ها، آموزش عملی و آمادگی شغلی در دانشگاه ملی مهارت واحد نیشابور.
    </p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-1 text-secondary mb-4">
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
        </div>
        <p class="font-body-md text-body-md text-on-surface italic leading-relaxed mb-6">
          «محیط کارگاهی و پروژه‌محور گروه کامپیوتر در واحد نیشابور باعث شد پیش از فارغ‌التحصیلی نمونه‌کار واقعی وب و نرم‌افزار داشته باشم و بلافاصله بعد از دوره کارآموزی وارد تیم توسعه شرکت شوم.»
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="محمدجواد قاسمی" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-maya-chen.jpg">
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">محمدجواد قاسمی، کارشناسی نرم‌افزار (۱۴۰۱)</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">توسعه‌دهنده وب و نرم‌افزار در مشهد</p>
        </div>
      </div>
    </div>
    <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-1 text-secondary mb-4">
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
        </div>
        <p class="font-body-md text-body-md text-on-surface italic leading-relaxed mb-6">
          «کار عملی با دستگاه‌های تراش و فرز CNC در کارگاه ساخت و تولید نیشابور بهترین نقطه شروع شغلی من بود. تجربه کارگاهی دقیقاً همان مهارتی بود که صنایع شهرک صنعتی خیام به آن نیاز داشتند.»
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="سجاد رضوی" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-marcus-vance.jpg">
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">مهندس سجاد رضوی، ساخت و تولید (۱۴۰۰)</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">مسئول برنامه‌نویسی CNC، شهرک صنعتی نیشابور</p>
        </div>
      </div>
    </div>
    <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-1 text-secondary mb-4">
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
          <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
        </div>
        <p class="font-body-md text-body-md text-on-surface italic leading-relaxed mb-6">
          «اساتید گروه معماری و امکانات آتلیه طراحی باعث شد از همان ترم‌های اول با نقشه‌کشی اجرایی و رندرینگ حرفه‌ای آشنا شویم و به‌صورت مستقل پروژه‌های طراحی داخلی بگیریم.»
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="زهرا حسینی‌پور" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-sophia-lindqvist.jpg">
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">زهرا حسینی‌پور، کاردانی معماری (۱۴۰۲)</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">طراح داخلی و مجری پروژه‌های ساختمانی</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 11. ADMISSIONS GATEWAY CTA -->
<section class="w-full bg-primary-container text-on-primary py-20 relative overflow-hidden" id="admissions-gateway">
  <div class="absolute -left-24 -bottom-24 w-96 h-96 rounded-full bg-secondary/10 blur-3xl pointer-events-none"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 flex flex-col lg:flex-row items-center justify-between gap-10">
    <div class="max-w-2xl">
      <span class="px-3 py-1 rounded bg-secondary-fixed text-on-secondary-fixed text-label-sm font-label-sm font-bold mb-4 inline-block">
        پذیرش مهر و بهمن ۱۴۰۴ • دانشگاه ملی مهارت نیشابور
      </span>
      <h2 class="font-headline-lg text-headline-lg text-on-primary mb-4">
        مسیر مهارت‌آموزی و آینده شغلی خود را از نیشابور آغاز کنید
      </h2>
      <p class="font-body-lg text-body-lg text-surface-variant leading-relaxed">
        با ثبت‌نام در سامانه یکپارچه صمد، پرونده دانشجویی خود را تکمیل کنید، مهارت‌ها و علایق خود را ثبت نمایید و از خدمات خوابگاهی و جشنواره‌های دانشگاهی بهره‌مند شوید.
      </p>
    </div>
    <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
      <a class="px-8 py-4 rounded bg-secondary text-on-secondary hover:bg-secondary-container hover:text-on-secondary-container font-title-lg text-body-md text-center transition-colors shadow-lg" href="register.php">
        ثبت‌نام دانشجوی جدید
      </a>
      <a class="px-8 py-4 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 backdrop-blur text-on-primary font-title-lg text-body-md text-center transition-colors" href="login.php">
        ورود به پرتال کاربری صمد
      </a>
    </div>
  </div>
</section>
</div>
</main>

<footer id="site-footer" class="w-full bg-surface-container-low text-on-surface mt-16 pt-16 pb-8 border-t border-surface-variant/40">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-surface-variant/50">
      <div class="lg:col-span-1">
        <div class="flex items-center gap-3 mb-4">
          <img alt="نشان دانشگاه ملی مهارت نیشابور" class="h-8 w-auto object-contain" src="assets/images/university-emblem.png">
          <span class="font-headline-sm text-headline-sm text-primary">دانشگاه ملی مهارت نیشابور</span>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">واحد نیشابور دانشگاه ملی مهارت (خراسان رضوی)؛ پیشرو در آموزش عالی مهارتی، تربیت تکنسین‌های کارآمد و پیوند دانشگاه با صنعت.</p>
        <div class="flex items-center gap-3 text-on-surface-variant">
          <a aria-label="ساختار سازمانی" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#top"><span class="material-symbols-outlined text-[16px]">corporate_fare</span></a>
          <a aria-label="نسخه انگلیسی" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="?lang=en"><span class="material-symbols-outlined text-[16px]">public</span></a>
          <a aria-label="فضای پردیس" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#campus-life"><span class="material-symbols-outlined text-[16px]">play_circle</span></a>
          <a aria-label="پژوهش و مهارت" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#research-innovation"><span class="material-symbols-outlined text-[16px]">menu_book</span></a>
        </div>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">گروه‌های آموزشی</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه کامپیوتر و فناوری اطلاعات</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه برق و الکترونیک صنعتی</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه مکانیک خودرو و ساخت و تولید</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه عمران و نقشه‌برداری</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه معماری و طراحی داخلی</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">گروه حسابداری و مدیریت</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">پذیرش و ثبت‌نام</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="register.php">پذیرش کاردانی پیوسته</a></li>
          <li><a class="hover:text-primary transition-colors" href="register.php">پذیرش کارشناسی ناپیوسته</a></li>
          <li><a class="hover:text-primary transition-colors" href="login.php">شرایط اسکان خوابگاه و امور رفاهی</a></li>
          <li><a class="hover:text-primary transition-colors" href="#research-innovation">مرکز رشد و هسته‌های فناور</a></li>
          <li><a class="hover:text-primary transition-colors" href="#campus-life">آشنایی با کارگاه‌ها و پردیس</a></li>
          <li><a class="hover:text-primary transition-colors" href="?lang=en">نسخه انگلیسی (EN)</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">امکانات دانشجویی و صمد</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="login.php">پرتال جامع دانشجویی واحد نیشابور</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-calendar">تقویم رسمی سال تحصیلی</a></li>
          <li><a class="hover:text-primary transition-colors" href="dashboard.php">میزکار دانشجویی صمد</a></li>
          <li><a class="hover:text-primary transition-colors" href="manager_panel.php">پنل مدیریت کل دانشگاه</a></li>
          <li><a class="hover:text-primary transition-colors" href="supervisor_panel.php">پنل سرپرست خوابگاه</a></li>
          <li><a class="hover:text-primary transition-colors" href="dashboard.php?tab=dorm">امور اسکان، خوابگاه‌ها و تغذیه</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">ارتباط با واحد نیشابور</h3>
        <div class="space-y-3 font-body-sm text-body-sm text-on-surface-variant">
          <p class="leading-relaxed">استان خراسان رضوی، نیشابور<br>بلوار دانشگاه، دانشگاه ملی مهارت واحد نیشابور</p>
          <p><span class="font-semibold text-on-surface">تلفن واحد:</span> <span dir="ltr">051-43350000</span></p>
          <p><span class="font-semibold text-on-surface">آموزش و خوابگاه:</span> <span dir="ltr">051-43350110</span></p>
          <p><span class="font-semibold text-on-surface">روابط عمومی:</span> <span dir="ltr">info@neyshabur.nus.ac.ir</span></p>
        </div>
      </div>
    </div>
    <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-label-md font-label-md text-on-surface-variant">
      <div>© ۱۴۰۴ تمامی حقوق برای دانشگاه ملی مهارت — واحد نیشابور محفوظ است.</div>
      <div class="flex flex-wrap items-center gap-6">
        <a class="hover:text-primary transition-colors" href="?lang=en">English Version</a>
        <a class="hover:text-primary transition-colors" href="login.php">ورود به سامانه</a>
        <a class="hover:text-primary transition-colors" href="register.php">ثبت‌نام دانشجو</a>
        <a class="hover:text-primary transition-colors" href="#site-footer">راهنمای خدمات الکترونیکی</a>
      </div>
    </div>
  </div>
</footer>

<!-- نوار دسترسی سریع ۱-کلیکی مدیر ارشد سامانه صمد -->
<div class="sticky bottom-0 z-40 bg-primary-container/95 backdrop-blur-md border-t border-secondary/30 text-on-primary px-4 py-2.5 shadow-2xl">
  <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
    <div class="flex items-center gap-2 text-label-md font-semibold text-secondary-fixed">
      <span class="material-symbols-outlined text-[18px]">bolt</span>
      <span>دسترسی سریع مدیر ارشد سامانه صمد:</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <form method="POST" action="login.php" class="inline">
        <input type="hidden" name="username" value="Admin">
        <input type="hidden" name="password" value="Admin@12345">
        <input type="hidden" name="captcha" value="8452">
        <button type="submit" class="px-3 py-1.5 rounded bg-secondary text-on-secondary hover:bg-secondary-container hover:text-on-secondary-container text-label-sm font-bold transition-colors cursor-pointer">
          ورود مدیر ارشد سامانه (Admin)
        </button>
      </form>
      <a href="login.php" class="px-3 py-1.5 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 text-on-primary text-label-sm font-bold transition-colors">
        صفحه ورود
      </a>
      <a href="register.php" class="px-3 py-1.5 rounded border border-secondary-fixed/40 text-secondary-fixed hover:bg-secondary-fixed hover:text-on-secondary-fixed text-label-sm font-bold transition-colors">
        ثبت‌نام جدید
      </a>
    </div>
  </div>
</div>

<script>
function toggleMobileDrawer() {
  const drawer = document.getElementById('mobileNavDrawer');
  if (drawer) drawer.classList.toggle('hidden');
}

function filterProgramsByCategory(category, btn) {
  document.querySelectorAll('.program-tab').forEach(b => {
    b.classList.remove('bg-primary', 'text-on-primary', 'shadow-sm');
    b.classList.add('bg-surface-container-lowest', 'text-on-surface');
  });
  if (btn) {
    btn.classList.remove('bg-surface-container-lowest', 'text-on-surface');
    btn.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
  }
  document.querySelectorAll('.program-card').forEach(card => {
    const cats = (card.getAttribute('data-category') || '').split(/\s+/);
    card.style.display = (category === 'all' || cats.includes(category)) ? '' : 'none';
  });
}

function filterLandingPrograms(query) {
  const q = (query || '').toLowerCase().trim();
  const cards = document.querySelectorAll('.program-card');
  cards.forEach(card => {
    const hay = (card.getAttribute('data-search') || '') + ' ' + (card.textContent || '');
    card.style.display = (!q || hay.toLowerCase().includes(q)) ? '' : 'none';
  });
  if (q.length >= 2) {
    const target = document.getElementById('academic-programs');
    if (target) {
      const rect = target.getBoundingClientRect();
      if (rect.top > window.innerHeight || rect.bottom < 0) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  }
}
</script>
</body>
</html>
