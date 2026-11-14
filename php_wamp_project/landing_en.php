<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta content="web_standard" name="shell-type"/>
<title>Aethelgard Institute of Technology &amp; Science</title>
<link rel="stylesheet" href="assets/fonts/fonts.css"/>
<link rel="stylesheet" href="assets/icons/icons.css"/>
<link rel="stylesheet" href="assets/tailwind/tailwind.css"/>
<style>
@layer base {
  html, body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
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
          <span class="material-symbols-outlined absolute left-2 text-on-surface-variant text-[16px]">search</span>
          <input id="landingSearchInput" oninput="filterLandingPrograms(this.value)" class="pl-7 pr-3 py-1 text-label-sm font-label-sm bg-surface-container-low text-on-surface rounded placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest w-52 lg:w-64 transition-all" placeholder="Search research, directory, programs..." type="text"/>
        </div>
        <div class="hidden md:flex items-center gap-2">
          <span class="material-symbols-outlined text-outline text-[16px]">language</span>
          <div class="flex items-center gap-1.5 text-label-sm font-label-sm text-on-surface-variant">
            <a href="?lang=en" class="text-primary font-semibold hover:text-on-surface">EN</a>
            <span class="text-outline-variant">/</span>
            <a href="?lang=fa" class="hover:text-primary font-medium">فا / FA</a>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3 lg:gap-5">
        <div class="hidden sm:flex items-center gap-4 text-label-sm font-label-sm text-on-surface-variant">
          <a class="hover:text-primary transition-colors" href="#academic-programs">Directory</a>
          <a class="hover:text-primary transition-colors" href="#research-innovation">Library</a>
          <a class="hover:text-primary transition-colors" href="#campus-life">Campus</a>
          <a class="hover:text-primary transition-colors" href="login.php">Portal</a>
        </div>
        <?php if ($loggedInUser): ?>
          <a class="inline-flex items-center gap-1.5 bg-primary-container text-secondary-fixed px-3 py-1 rounded-full text-label-sm font-label-sm hover:bg-primary hover:text-on-primary transition-colors" href="<?= htmlspecialchars($portalUrl) ?>">
            <span class="material-symbols-outlined text-[14px]">school</span>
            <span><?= htmlspecialchars($portalLabelEn) ?> (<?= htmlspecialchars($loggedInUser) ?>)</span>
          </a>
          <a href="logout.php" title="Sign out" class="w-8 h-8 rounded-full bg-surface-container text-primary hover:bg-error-container hover:text-error flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-[18px]">logout</span>
          </a>
        <?php else: ?>
          <a class="inline-flex items-center gap-1.5 bg-primary-container text-secondary-fixed px-3 py-1 rounded-full text-label-sm font-label-sm hover:bg-primary hover:text-on-primary transition-colors" href="login.php">
            <span class="material-symbols-outlined text-[14px]">school</span>
            <span>Student Portal</span>
          </a>
          <a href="login.php" title="Sign in" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex items-center justify-between pt-1">
      <a href="#top" class="flex items-center gap-3">
        <img alt="Aethelgard University Emblem" class="h-8 w-auto object-contain" src="assets/images/university-emblem.png"/>
        <div class="flex flex-col">
          <span class="font-headline-sm text-headline-sm tracking-tight text-primary leading-none">AETHELGARD</span>
          <span class="font-label-sm text-label-sm tracking-widest text-on-surface-variant uppercase">University • Est. 1894</span>
        </div>
      </a>
      <nav class="hidden xl:flex items-center gap-5 text-body-sm font-body-sm" data-active-classes="text-primary font-semibold border-b-2 border-secondary">
        <a aria-current="page" class="transition-colors pb-1 text-primary font-semibold border-b-2 border-secondary" href="#top">Home</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#about-university">About</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#academic-programs">Departments</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#university-stats">Metrics</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#admissions-gateway">Admissions</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#research-innovation">Research</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="<?= htmlspecialchars($portalUrl) ?>">Students</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#news-events">News &amp; Events</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#campus-life">Campus Life</a>
        <a class="text-on-surface-variant hover:text-on-surface transition-colors pb-1" href="#site-footer">Contact</a>
      </nav>
      <div class="flex items-center gap-2 xl:hidden">
        <a href="?lang=fa" class="px-2 py-1 rounded bg-surface-container text-label-sm font-bold text-primary">فا</a>
        <button onclick="toggleMobileDrawer()" aria-label="Toggle Navigation" class="p-1.5 text-on-surface-variant hover:text-on-surface rounded hover:bg-surface-container" type="button">
          <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
      </div>
    </div>
  </div>
  <!-- Mobile Navigation Drawer -->
  <div id="mobileNavDrawer" class="hidden xl:hidden bg-surface-container-lowest border-t border-surface-variant/40 px-6 py-4 shadow-lg">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-body-sm font-medium mb-4">
      <a href="#top" onclick="toggleMobileDrawer()" class="py-1.5 text-primary font-semibold">Home</a>
      <a href="#about-university" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">About</a>
      <a href="#academic-programs" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">Departments</a>
      <a href="#news-events" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">News &amp; Events</a>
      <a href="#research-innovation" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">Research</a>
      <a href="#admissions-gateway" onclick="toggleMobileDrawer()" class="py-1.5 text-on-surface-variant hover:text-primary">Admissions</a>
    </div>
    <div class="flex flex-wrap gap-2 pt-3 border-t border-surface-variant/40">
      <a href="<?= htmlspecialchars($portalUrl) ?>" class="px-4 py-2 rounded bg-primary text-on-primary text-label-md font-semibold"><?= htmlspecialchars($portalLabelEn) ?></a>
      <a href="register.php" class="px-4 py-2 rounded bg-secondary text-on-secondary text-label-md font-semibold">Apply Online</a>
    </div>
  </div>
</header>

<main id="top" class="w-full pt-28 bg-surface min-h-[calc(100vh-28rem)]">
<div class="flex flex-col w-full">
<!-- 1. HERO SECTION -->
<section class="relative w-full overflow-hidden bg-primary-container text-on-primary">
  <div class="absolute inset-0 z-0">
    <img alt="Aethelgard University Historic and Modern Campus Quadrangle" class="w-full h-full object-cover object-center filter brightness-[0.72] contrast-[1.08] scale-[1.01] transform transition-transform duration-1000 ease-out hover:scale-105" src="assets/images/hero-campus.jpg"/>
    <div class="absolute inset-0 bg-gradient-to-t from-primary-container via-primary-container/60 to-primary-container/30 mix-blend-multiply"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-primary-container/90 via-primary-container/40 to-transparent"></div>
  </div>
  <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-12 pt-24 pb-20 md:pt-32 md:pb-28 flex flex-col justify-end min-h-[740px]">
    <div class="max-w-3xl">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary/20 backdrop-blur-md text-secondary-fixed text-label-md font-label-md tracking-wider uppercase mb-6 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-secondary-fixed animate-pulse"></span>
        <span>Admissions Open • Academic Year 2025–2026</span>
      </div>
      <h1 class="font-display-hero text-display-hero text-on-primary tracking-tight leading-tight mb-6">
        Where Intellectual Curiosity Shapes Tomorrow’s World
      </h1>
      <p class="font-body-lg text-body-lg text-surface-variant font-normal leading-relaxed mb-8 max-w-2xl">
        Ranked #12 globally in Engineering and Interdisciplinary Sciences. Aethelgard University unites distinguished faculty, groundbreaking research labs, and a diverse community of scholars committed to global impact.
      </p>
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <a class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded bg-primary text-on-primary hover:bg-on-surface font-title-lg text-body-md transition-all shadow-md group" href="#academic-programs">
          <span>Explore Academic Programs</span>
          <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
        </a>
        <a class="inline-flex items-center gap-2 px-6 py-3.5 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 backdrop-blur-md text-on-primary font-title-lg text-body-md transition-all shadow-sm" href="register.php">
          <span>Apply for Fall 2025</span>
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
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">98%</span>
            <span class="font-label-sm text-label-sm text-surface-variant uppercase tracking-wider">Graduate Placement</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">military_tech</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">14</span>
            <span class="font-label-sm text-label-sm text-surface-variant uppercase tracking-wider">Nobel Laureates</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">public</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">110+</span>
            <span class="font-label-sm text-label-sm text-surface-variant uppercase tracking-wider">Nations Represented</span>
          </div>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="material-symbols-outlined text-secondary-fixed text-[32px]">biotech</span>
          <div class="flex flex-col">
            <span class="font-headline-sm text-headline-sm font-semibold tracking-tight text-on-primary">R1 Doctoral</span>
            <span class="font-label-sm text-label-sm text-surface-variant uppercase tracking-wider">Tier-1 Research Hub</span>
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
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Student Portal</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">SSO credentials, grades, course portals, and tuition ledgers.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="register.php">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">app_registration</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Online Registration</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Course enrollment waitlists and add/drop period scheduling.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#academic-programs">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">view_timeline</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Course Schedule</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Interactive directories of lectures, recitations, and lab sections.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#academic-calendar">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">calendar_month</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Academic Calendar</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Official semester deadlines, reading periods, and examinations.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#research-innovation">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">local_library</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">University Library</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Search 4.2M cataloged volumes, rare manuscripts, and JSTOR access.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="<?= htmlspecialchars($portalUrl) ?>">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">devices</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">E-Learning Canvas</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Course modules, digital submissions, and video recitation streams.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="#research-innovation">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">science</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Research Portal</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Sponsored research opportunities, grants, and high-impact papers.</p>
      </div>
    </a>
    <a class="group p-6 rounded-lg bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" href="<?= htmlspecialchars($loggedRole === 'student' ? 'dashboard.php?tab=dorm' : $portalUrl) ?>">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
          <span class="material-symbols-outlined text-[24px]">support_agent</span>
        </div>
        <span class="material-symbols-outlined text-outline group-hover:text-secondary group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all text-[20px]">north_east</span>
      </div>
      <div>
        <h3 class="font-title-lg text-title-lg text-primary mb-1">Student Services &amp; Housing</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Housing accommodation, visa advising, bursar, and health wellness.</p>
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
        <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Established 1894 • Over a Century of Scholarship</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary mb-6">
        Advancing Knowledge, Cultivating Integrity, Inspiring Discovery
      </h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-8">
        Chartered in 1894 as an independent institute for technological arts and civic thought, Aethelgard University has evolved into one of the globe's foremost crucibles of innovation. We unite classical inquiry with avant-garde scientific instrumentation, preparing scholars who dissect systemic enigmas and forge equitable futures.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <div class="bg-surface-container-low p-6 rounded-lg shadow-sm">
          <div class="flex items-center gap-2 mb-2 text-primary font-title-lg text-title-lg">
            <span class="material-symbols-outlined text-secondary text-[22px]">explore</span>
            <h4>Our Mission</h4>
          </div>
          <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
            To ignite transformative discoveries through unapologetic intellectual rigor, ethical technological stewardship, and interdisciplinary partnership.
          </p>
        </div>
        <div class="bg-surface-container-low p-6 rounded-lg shadow-sm">
          <div class="flex items-center gap-2 mb-2 text-primary font-title-lg text-title-lg">
            <span class="material-symbols-outlined text-secondary text-[22px]">visibility</span>
            <h4>Our Vision</h4>
          </div>
          <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
            To serve as the global paragon of human-centric science, fostering leaders capable of addressing climate resilience, ethical AI, and planetary health.
          </p>
        </div>
      </div>
      <div class="bg-surface-container p-6 rounded-lg mb-8">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-primary text-on-primary rounded">
            <span class="material-symbols-outlined text-[24px]">history_edu</span>
          </div>
          <div>
            <span class="font-label-sm text-label-sm text-secondary font-semibold uppercase tracking-wider">Milestone Chronology</span>
            <p class="font-body-sm text-body-sm text-on-surface mt-1 leading-relaxed">
              From an initial founding class of 40 scholars in a solitary sandstone hall to an expansive network spanning 12 faculties, 42 research centers, and over 168,000 alumni shaping enterprise, diplomacy, and science across 140 countries.
            </p>
          </div>
        </div>
      </div>
      <a class="inline-flex items-center gap-3 font-title-lg text-body-md text-primary hover:text-secondary group transition-colors" href="#university-stats">
        <span>Learn More About Our Heritage &amp; Archives</span>
        <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
      </a>
    </div>
    <div class="lg:col-span-5 flex flex-col gap-6">
      <div class="relative rounded-2xl overflow-hidden shadow-xl bg-surface-container-highest">
        <img alt="University Chancellor in Academic Robes" class="w-full h-80 object-cover object-center" src="assets/images/chancellor-portrait.jpg"/>
        <div class="p-8 bg-surface-container-lowest">
          <span class="material-symbols-outlined text-secondary text-[40px] opacity-40 mb-3 block">format_quote</span>
          <blockquote class="font-headline-sm text-headline-sm text-primary italic leading-snug mb-6">
            “We do not merely teach students to understand the world as it is—we empower them to architect what it must become.”
          </blockquote>
          <div class="flex items-center justify-between pt-4">
            <div>
              <p class="font-title-lg text-title-lg text-primary">Dr. Eleanor Vance, Ph.D.</p>
              <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Chancellor &amp; Professor of Applied Physics</p>
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
          <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Undergraduate &amp; Graduate Excellence</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary">World-Class Academic Programs &amp; Departments</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
        Explore specialized disciplines rigorous enough to produce world-renowned innovators and thinkers across scientific frontiers.
      </p>
    </div>
    <!-- Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 mb-10 pb-2" id="programFilterTabs">
      <button type="button" onclick="filterProgramsByCategory('all', this)" class="program-tab px-5 py-2 rounded-full bg-primary text-on-primary font-body-sm text-body-sm font-medium shadow-sm cursor-pointer">All Faculties</button>
      <button type="button" onclick="filterProgramsByCategory('engineering', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">Engineering &amp; Computing</button>
      <button type="button" onclick="filterProgramsByCategory('sciences', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">Applied Sciences</button>
      <button type="button" onclick="filterProgramsByCategory('business', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">Business &amp; Economics</button>
      <button type="button" onclick="filterProgramsByCategory('humanities', this)" class="program-tab px-5 py-2 rounded-full bg-surface-container-lowest text-on-surface hover:bg-surface-container font-body-sm text-body-sm font-medium transition-colors cursor-pointer">Urban &amp; Infrastructure</button>
    </div>
    <!-- Programs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="programsGrid">
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="engineering" data-search="computer engineering neuromorphic quantum robotics">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold uppercase tracking-wider">ABET Accredited</span>
            <span class="font-label-sm text-label-sm text-outline">B.S. • M.S. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Computer Engineering</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Specialized research pathways in neuromorphic hardware, distributed systems, quantum computing, and collaborative autonomous robotics.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Neural Accelerator Lab Access</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Silicon Prototyping Cleanrooms</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">48 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="engineering" data-search="software engineering cloud cryptography compiler cyber">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant font-label-sm text-label-sm font-bold uppercase tracking-wider">ABET Accredited</span>
            <span class="font-label-sm text-label-sm text-outline">B.S. • M.S. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Software Engineering</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Core immersion into enterprise cloud resilience, verified cryptographic protocols, compiler construction, and human-computer interactions.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>High-Throughput Distributed Clusters</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Cyber Defense War Gaming Sandbox</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">36 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="engineering sciences" data-search="electrical engineering photonics lasers microgrid satellite">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold uppercase tracking-wider">IEEE Partner</span>
            <span class="font-label-sm text-label-sm text-outline">B.S. • M.S. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Electrical Engineering</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Investigation of sub-nanometer optoelectronics, smart renewable transmission grids, quantum optics, and RF satellite telecommunications.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Photonics &amp; Lasers Cleanroom</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Microgrid Test Simulation Facility</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">42 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="sciences engineering" data-search="mechanical engineering aerospace robotics fluid titanium">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant font-label-sm text-label-sm font-bold uppercase tracking-wider">ABET Accredited</span>
            <span class="font-label-sm text-label-sm text-outline">B.S. • M.S. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Mechanical Engineering</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Advancing supersonic aerospace dynamics, robotic prosthesis biomechanics, continuum fluid dynamics, and additive titanium manufacturing.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Mach 4 Subsonic Wind Tunnel</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Rapid Prototyping Foundry</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">51 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="humanities sciences" data-search="civil urban engineering seismic structures hydrology">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold uppercase tracking-wider">ASCE Partner</span>
            <span class="font-label-sm text-label-sm text-outline">B.S. • M.S. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Civil &amp; Urban Engineering</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Engineering seismic resilient structures, carbon-negative cementitious polymers, and closed-loop watershed purification networks.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Multi-Axis Seismic Shake Table</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Hydrology Environmental Flumes</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">34 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
      <div class="program-card p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between" data-category="business humanities" data-search="business administration economics fintech logistics venture">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-2.5 py-1 rounded bg-secondary-fixed/50 text-on-secondary-fixed font-label-sm text-label-sm font-bold uppercase tracking-wider">AACSB Accredited</span>
            <span class="font-label-sm text-label-sm text-outline">B.B.A. • M.B.A. • Ph.D.</span>
          </div>
          <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Business Administration</h3>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            Equipping quantitative executives in fintech algorithms, decarbonized global logistics, behavioral microeconomics, and ethical leadership.
          </p>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Bloomberg Financial Trading Suite</span>
            </div>
            <div class="flex items-center gap-2 text-body-sm font-body-sm text-on-surface">
              <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
              <span>Venture Incubation Studio</span>
            </div>
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">58 Faculty Fellows</span>
          <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="register.php">
            <span>Apply &amp; Curriculum</span>
            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </a>
        </div>
      </div>
    </div>
    <!-- Action Card -->
    <div class="mt-8 p-6 rounded-xl bg-primary text-on-primary flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-secondary-fixed text-[36px]">menu_book</span>
        <div>
          <h4 class="font-title-lg text-title-lg font-semibold">Interested in other disciplines?</h4>
          <p class="font-body-sm text-body-sm text-surface-variant">We offer 48 undergraduate majors and 72 advanced graduate degree tracks.</p>
        </div>
      </div>
      <a class="px-6 py-2.5 rounded bg-surface-container-lowest text-primary font-title-lg text-body-sm hover:bg-surface-container transition-colors whitespace-nowrap" href="register.php">
        View All 48 Departments
      </a>
    </div>
  </div>
</section>

<!-- 5. UNIVERSITY STATISTICS -->
<section id="university-stats" class="w-full bg-primary text-on-primary py-20">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-8 lg:gap-12 text-center md:text-left">
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-semibold">24,800+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-medium">Enrolled Scholars</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">Undergraduate &amp; postgraduate (<?= number_format($stats['students_count']) ?> active in portal)</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-semibold">1,420</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-medium">Distinguished Faculty</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">Full-time professors, investigators, and fellows</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-semibold">12</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-medium">Colleges &amp; Institutes</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1"><?= number_format($stats['rooms_count']) ?> dormitory rooms managed live</span>
      </div>
      <div class="flex flex-col">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-semibold">168,000+</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-medium">Global Alumni</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">Active networks spanning 140 countries globally</span>
      </div>
      <div class="flex flex-col col-span-2 md:col-span-1">
        <span class="font-display-hero text-headline-lg lg:text-display-hero text-secondary-fixed tracking-tight font-semibold">$420M</span>
        <span class="font-title-lg text-title-lg text-on-primary mt-1 font-medium">Annual Research</span>
        <span class="font-body-sm text-body-sm text-surface-variant mt-1">Sponsored grant expenditures and public awards</span>
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
        <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Campus Dispatches</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary">Latest News &amp; Breakthroughs</h2>
    </div>
    <a class="font-title-lg text-body-md text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
      <span>University Press Room</span>
      <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
    </a>
  </div>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="Bioengineering Laboratory" class="w-full h-full object-cover" src="assets/images/news-bioengineering.jpg"/>
          <span class="absolute top-3 left-3 px-2 py-1 rounded bg-primary text-on-primary text-label-sm font-label-sm uppercase tracking-wider font-semibold">Research</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>March 28, 2025</span>
              <span>•</span>
              <span>4 min read</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              Bioengineering Lab Discovers Targeted Enzyme Therapy for Neurodegenerative Conditions
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#research-innovation">
            <span>Read Full Paper</span>
            <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
          </a>
        </div>
      </article>
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="Quantum Computing Center" class="w-full h-full object-cover" src="assets/images/news-quantum-center.jpg"/>
          <span class="absolute top-3 left-3 px-2 py-1 rounded bg-secondary text-on-secondary text-label-sm font-label-sm uppercase tracking-wider font-semibold">Campus</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>March 24, 2025</span>
              <span>•</span>
              <span>3 min read</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              New $85M Quantum Computing Center Breaks Ground on North Quadrangle
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#research-innovation">
            <span>Read Announcement</span>
            <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
          </a>
        </div>
      </article>
      <article class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
        <div class="h-44 overflow-hidden relative">
          <img alt="National Academy of Engineering Faculty" class="w-full h-full object-cover" src="assets/images/news-faculty-award.jpg"/>
          <span class="absolute top-3 left-3 px-2 py-1 rounded bg-inverse-surface text-on-primary text-label-sm font-label-sm uppercase tracking-wider font-semibold">Accolade</span>
        </div>
        <div class="p-6 flex flex-col justify-between flex-1">
          <div>
            <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-2">
              <span>March 19, 2025</span>
              <span>•</span>
              <span>2 min read</span>
            </div>
            <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug mb-3">
              Faculty Trio Named to Prestigious National Academy of Engineering
            </h3>
          </div>
          <a class="inline-flex items-center gap-1 text-label-md font-label-md font-semibold text-primary hover:text-secondary mt-4 group" href="#about-university">
            <span>View Faculty Profiles</span>
            <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
          </a>
        </div>
      </article>
    </div>
    <div class="lg:col-span-4 bg-surface-container p-6 rounded-xl shadow-sm flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-2 mb-4 text-primary">
          <span class="material-symbols-outlined text-secondary text-[24px]">campaign</span>
          <h3 class="font-title-lg text-title-lg font-semibold">Campus Notices &amp; Bulletin</h3>
        </div>
        <div class="space-y-4">
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold mb-1">Registrar Notice</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">Fall 2025 Course Add/Drop Closes</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Online course adjustments finalize at midnight Friday, April 4th.</p>
          </div>
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm font-semibold mb-1">Advisory</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">Bannister Library East Wing Renovation</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Quiet study corridors relocated to West Commons during spring break.</p>
          </div>
          <div class="p-4 bg-surface-container-lowest rounded-lg shadow-sm">
            <span class="inline-block px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold mb-1">Health &amp; Wellness</span>
            <h4 class="font-title-lg text-body-md font-semibold text-primary">Spring Wellness Fair in Quad</h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Complimentary checkups, stress dogs, and mindfulness clinics.</p>
          </div>
        </div>
      </div>
      <a class="inline-flex items-center justify-between text-body-sm font-body-sm font-semibold text-primary hover:text-secondary mt-6 pt-4 border-t border-surface-variant/50" href="<?= htmlspecialchars($portalUrl) ?>">
        <span>View All Official Bulletins</span>
        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
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
          <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Transformative Inquiry</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary">Pioneering Research &amp; Ventures</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
        Bridging speculative fundamental physics with venture-scale commercialization to solve global challenges.
      </p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <div class="lg:col-span-7 space-y-6">
        <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row gap-6 items-center">
          <div class="w-full md:w-48 h-36 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
            <img alt="Autonomous Systems and Robotics Lab" class="w-full h-full object-cover" src="assets/images/lab-robotics.jpg"/>
          </div>
          <div>
            <div class="inline-block px-2.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-label-sm font-label-sm uppercase font-semibold mb-2">Institute Lab</div>
            <h3 class="font-title-lg text-title-lg text-primary mb-2">Autonomous Systems &amp; Robotics Laboratory (ASRL)</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-4">
              Pioneering multi-agent subterranean mapping drones, bio-inspired robotic exoskeletons, and neuromorphic real-time SLAM algorithms.
            </p>
            <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
              <span>Explore Lab Projects</span>
              <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </a>
          </div>
        </div>
        <div class="p-8 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row gap-6 items-center">
          <div class="w-full md:w-48 h-36 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
            <img alt="Center for Sustainable Energy and Carbon Capture" class="w-full h-full object-cover" src="assets/images/lab-clean-energy.jpg"/>
          </div>
          <div>
            <div class="inline-block px-2.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-label-sm font-label-sm uppercase font-semibold mb-2">Clean Energy</div>
            <h3 class="font-title-lg text-title-lg text-primary mb-2">Center for Sustainable Energy &amp; Carbon Capture</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-4">
              Deploying solid-sorbent direct air capture pilot plants and metal-organic frameworks for modular clean hydrogen storage.
            </p>
            <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1 group" href="<?= htmlspecialchars($portalUrl) ?>">
              <span>Explore Research Facility</span>
              <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </a>
          </div>
        </div>
        <div class="p-8 rounded-xl bg-primary text-on-primary shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
          <div>
            <span class="px-2.5 py-1 rounded bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm uppercase font-bold tracking-wider">Student Incubator</span>
            <h3 class="font-headline-sm text-headline-sm text-on-primary mt-2 mb-2">The Foundry Startup Accelerator</h3>
            <p class="font-body-sm text-body-sm text-surface-variant max-w-lg">
              42 student-founded venture startups backed in 2024 with over $65M in subsequent Series A venture financing.
            </p>
          </div>
          <a class="px-5 py-3 rounded bg-secondary text-on-secondary font-title-lg text-body-sm hover:bg-secondary-container hover:text-on-secondary-container transition-colors whitespace-nowrap shadow-sm" href="register.php">
            Apply to Cohort
          </a>
        </div>
      </div>
      <div class="lg:col-span-5 bg-surface-container-lowest p-8 rounded-xl shadow-sm">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-surface-variant/40">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary text-[26px]">library_books</span>
            <h3 class="font-headline-sm text-headline-sm text-primary">High-Impact Papers</h3>
          </div>
          <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">Q1 2025 Repository</span>
        </div>
        <div class="space-y-6">
          <div class="pb-6 border-b border-surface-variant/40">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">Nature Nanotechnology</span>
              <span class="font-label-sm text-label-sm text-outline">Feb 2025</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              Room-Temperature Topological Superconductivity in Twisted Bilayer Chalcogenides
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">Lead: Prof. K. Arisawa, Department of Applied Physics</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">DOI: 10.1038/s41565-025-01824-x</div>
          </div>
          <div class="pb-6 border-b border-surface-variant/40">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">Science</span>
              <span class="font-label-sm text-label-sm text-outline">Jan 2025</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              Enzymatic Hydrolysis Cascade for Rapid Circular Depolymerization of PET Plastics
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">Lead: Dr. Marcus Vance &amp; Prof. Elena Thorne</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">DOI: 10.1126/science.adg8841</div>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container font-semibold text-primary">Physical Review Letters</span>
              <span class="font-label-sm text-label-sm text-outline">Dec 2024</span>
            </div>
            <h4 class="font-title-lg text-body-md font-semibold text-primary hover:text-secondary cursor-pointer leading-snug">
              Verification of Non-Abelian Anyon Braiding in Solid-State Surface Trap Quantum Registers
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5">Lead: Quantum Computing Core Research Initiative</p>
            <div class="mt-2 text-label-sm font-label-sm text-secondary font-medium">DOI: 10.1103/PhysRevLett.134.020501</div>
          </div>
        </div>
        <a class="mt-6 inline-flex items-center gap-2 font-title-lg text-body-sm text-primary font-semibold hover:text-secondary" href="<?= htmlspecialchars($portalUrl) ?>">
          <span>Browse All Faculty Publications (2,400+)</span>
          <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
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
        <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Community &amp; Experience</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-primary">Vibrant Collegiate Life</h2>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
      Beyond classroom mastery, discover an active collegiate ecosystem where intellect, athletics, and cultural expression flourish.
    </p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">apartment</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Modern Campus Facilities</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          24/7 Innovation Commons, Olympic Aquatics Complex, 4 sustainable farm-to-table dining halls, and expansive study lawns.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider">350 Acres Campus</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">groups</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">180+ Student Clubs &amp; Societies</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          From the collegiate Solar Car Team and Parliamentary Debate Union to Model UN and Aethelgard Chamber Philharmonic.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider">Infinite Opportunities</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">fitness_center</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">NCAA Division I Athletics</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          18 Varsity programs, nationally acclaimed rowing boathouse on the river, intramural soccer leagues, and climbing facilities.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider">Home of the Hawks</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">precision_manufacturing</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Advanced Maker Spaces</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          Multi-axis CNC mills, waterjet cutters, automated electronics soldering suites, and rapid laser cutting bays free for student use.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider">Open 24 Hours</span>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">bed</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Residential Colleges</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          10 tight-knit on-campus residential houses with resident faculty masters, common rooms, libraries, and evening colloquia.
        </p>
      </div>
      <a href="<?= htmlspecialchars($portalUrl) ?>" class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider hover:underline">Guaranteed 4-Year Housing →</a>
    </div>
    <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-surface-container-lowest flex flex-col justify-between p-8">
      <div>
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors mb-6">
          <span class="material-symbols-outlined text-[24px]">how_to_vote</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Advocacy &amp; Governance</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">
          Empowered Student Council with direct voting seats on the University Board of Trustees, managing $3.2M in annual student funds.
        </p>
      </div>
      <span class="font-label-md text-label-md text-secondary font-semibold uppercase tracking-wider">Democratic Voice</span>
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
          <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Public Colloquia &amp; Culture</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-primary mb-8">Upcoming Events</h2>
        <div class="space-y-4">
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider">APR</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">04</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>5:30 PM – 7:30 PM EST • McCandless Auditorium</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">Annual Turing Memorial Lecture in Artificial Intelligence</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                Keynote Address: “Beyond Next-Token Prediction: Reasoning Models and Neuromorphic Substrates” by Dr. Hiroshi Sato.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>Reserve Seat (Free)</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider">APR</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">11</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>9:00 AM – 4:00 PM EST • Center for Global Affairs</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">International Climate Resilience Symposium 2025</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                Convening world delegates, environmental hydrologists, and municipal leaders to discuss coastal flood prevention.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>View Conference Agenda</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
          <div class="p-6 rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex items-start gap-6">
            <div class="flex flex-col items-center justify-center w-16 h-18 bg-surface-container rounded-lg p-2 flex-shrink-0 text-center">
              <span class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider">APR</span>
              <span class="font-headline-md text-headline-md font-semibold text-primary leading-none mt-1">18</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 text-label-sm font-label-sm text-outline mb-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>8:00 PM – 10:15 PM EST • Symphony Hall</span>
              </div>
              <h3 class="font-title-lg text-title-lg text-primary hover:text-secondary cursor-pointer leading-snug">Spring Symphony Orchestra Gala: Mahler’s Symphony No. 2</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
                Conducted by Maestro Julian Vance featuring the 120-voice Aethelgard University Chorale.
              </p>
              <div class="mt-3 flex items-center gap-4">
                <a class="font-title-lg text-body-sm text-primary font-semibold hover:text-secondary inline-flex items-center gap-1" href="<?= htmlspecialchars($portalUrl) ?>">
                  <span>Box Office Tickets</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
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
              <h3 class="font-headline-sm text-headline-sm text-primary">Academic Calendar</h3>
            </div>
            <span class="font-label-sm text-label-sm text-secondary font-bold uppercase">Spring &amp; Fall 2025</span>
          </div>
          <div class="space-y-6">
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">APR 15</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">Priority Deadline: Graduate Fall Admission</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Ph.D. and Master of Science departmental fellowship consideration cutoff.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">MAY 02</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">Final Course Drop Deadline</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Last official day to withdraw from Spring undergraduate courses with 'W' notation.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">MAY 19–27</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">Spring Final Examination Period</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Proctored in-person examinations across all university lecture theaters.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 rounded bg-surface-container text-primary font-bold text-label-sm font-label-sm whitespace-nowrap">JUN 06</div>
              <div>
                <h4 class="font-title-lg text-body-md font-semibold text-primary">131st University Commencement</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Conferral of undergraduate and doctoral degrees on the Historic Quadrangle.</p>
              </div>
            </div>
          </div>
        </div>
        <a class="mt-8 p-3 rounded bg-surface-container hover:bg-surface-variant text-primary text-center font-title-lg text-body-sm font-semibold transition-colors" href="<?= htmlspecialchars($portalUrl) ?>">
          Open 2025–2026 Academic Portal
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
      <span class="font-label-md text-label-md text-secondary tracking-widest uppercase font-semibold">Voices of Aethelgard</span>
      <span class="w-8 h-[2px] bg-secondary"></span>
    </div>
    <h2 class="font-headline-lg text-headline-lg text-primary">Scholars Who Lead the World</h2>
    <p class="font-body-md text-body-md text-on-surface-variant mt-2">
      Read firsthand accounts of academic discovery, lifelong fellowship, and global career impact.
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
          “The collaborative culture at Aethelgard is unprecedented. As an undergrad, I was not merely cleaning test tubes—I was co-authoring autonomous perception papers with senior faculty that altered how mobile robots navigate disasters.”
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="Maya Chen" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-maya-chen.jpg"/>
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">Maya Chen, B.S. ’22</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Lead Robotics Engineer, Boston Dynamics</p>
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
          “My doctoral fellowship gave me direct access to synchrotron beamlines and high-performance computing clusters that very few universities in the world can match. The mentorship here prepared me seamlessly for post-doctoral leadership.”
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="Marcus Vance" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-marcus-vance.jpg"/>
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">Marcus Vance, Ph.D. ’24</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Postdoctoral Fellow at CERN</p>
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
          “The Foundry incubator at Aethelgard provided the seed capital, legal guidance, and boardroom practice that transformed my dorm-room solar clean-tech idea into a company that now employs 80 people across four continents.”
        </p>
      </div>
      <div class="flex items-center gap-4 pt-4 border-t border-surface-variant/40">
        <img alt="Sophia Lindqvist" class="w-12 h-12 rounded-full object-cover" src="assets/images/alumni-sophia-lindqvist.jpg"/>
        <div>
          <h4 class="font-title-lg text-body-md font-semibold text-primary">Sophia Lindqvist, M.S. ’21</h4>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Co-Founder &amp; CEO, HelioGrid Power</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 11. ADMISSIONS GATEWAY CTA -->
<section class="w-full bg-primary-container text-on-primary py-20 relative overflow-hidden" id="admissions-gateway">
  <div class="absolute -right-24 -bottom-24 w-96 h-96 rounded-full bg-secondary/10 blur-3xl pointer-events-none"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 flex flex-col lg:flex-row items-center justify-between gap-10">
    <div class="max-w-2xl">
      <span class="px-3 py-1 rounded bg-secondary-fixed text-on-secondary-fixed text-label-sm font-label-sm font-bold uppercase tracking-wider mb-4 inline-block">
        Applications for Fall 2025
      </span>
      <h2 class="font-headline-lg text-headline-lg text-on-primary mb-4">
        Begin Your Intellectual Journey at Aethelgard
      </h2>
      <p class="font-body-lg text-body-lg text-surface-variant leading-relaxed">
        Join a storied tradition of thinkers, researchers, and creators. Financial aid is awarded on a need-blind basis for all accepted domestic and international applicants.
      </p>
    </div>
    <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
      <a class="px-8 py-4 rounded bg-secondary text-on-secondary hover:bg-secondary-container hover:text-on-secondary-container font-title-lg text-body-md text-center transition-colors shadow-lg" href="register.php">
        Start Undergraduate Application
      </a>
      <a class="px-8 py-4 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 backdrop-blur text-on-primary font-title-lg text-body-md text-center transition-colors" href="login.php">
        Portal Sign In
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
          <img alt="Aethelgard University Emblem" class="h-8 w-auto object-contain" src="assets/images/university-emblem.png"/>
          <span class="font-headline-sm text-headline-sm text-primary">AETHELGARD</span>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mb-6">Advancing scientific discovery, humanist ethics, and planetary stewardship through rigorous research and visionary pedagogy since 1894.</p>
        <div class="flex items-center gap-3 text-on-surface-variant">
          <a aria-label="LinkedIn" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#top"><span class="material-symbols-outlined text-[16px]">corporate_fare</span></a>
          <a aria-label="Persian Version" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="?lang=fa"><span class="material-symbols-outlined text-[16px]">public</span></a>
          <a aria-label="Video Channel" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#campus-life"><span class="material-symbols-outlined text-[16px]">play_circle</span></a>
          <a aria-label="Publications" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" href="#research-innovation"><span class="material-symbols-outlined text-[16px]">menu_book</span></a>
        </div>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">Academics &amp; Faculties</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">School of Computing</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">School of Engineering</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">School of Business &amp; Economics</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-programs">School of Architecture</a></li>
          <li><a class="hover:text-primary transition-colors" href="#research-innovation">Graduate School of Sciences</a></li>
          <li><a class="hover:text-primary transition-colors" href="#research-innovation">Interdisciplinary Studies</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">Admissions &amp; Aid</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="register.php">Undergraduate Applications</a></li>
          <li><a class="hover:text-primary transition-colors" href="register.php">Graduate Admissions</a></li>
          <li><a class="hover:text-primary transition-colors" href="login.php">Tuition &amp; Financial Schedule</a></li>
          <li><a class="hover:text-primary transition-colors" href="#admissions-gateway">Endowed Scholarships</a></li>
          <li><a class="hover:text-primary transition-colors" href="#campus-life">Schedule Campus Tour</a></li>
          <li><a class="hover:text-primary transition-colors" href="?lang=fa">Persian Portal (نسخه فارسی)</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">Student Resources</h3>
        <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
          <li><a class="hover:text-primary transition-colors" href="login.php">Student Academic Portal</a></li>
          <li><a class="hover:text-primary transition-colors" href="#academic-calendar">Official Academic Calendar</a></li>
          <li><a class="hover:text-primary transition-colors" href="dashboard.php">Student Dashboard</a></li>
          <li><a class="hover:text-primary transition-colors" href="manager_panel.php">University Manager Panel</a></li>
          <li><a class="hover:text-primary transition-colors" href="supervisor_panel.php">Dormitory Supervisor Panel</a></li>
          <li><a class="hover:text-primary transition-colors" href="dashboard.php?tab=dorm">Campus Housing &amp; Dining</a></li>
        </ul>
      </div>
      <div class="flex flex-col">
        <h3 class="font-title-lg text-title-lg text-primary mb-4">Contact &amp; Campus</h3>
        <div class="space-y-3 font-body-sm text-body-sm text-on-surface-variant">
          <p class="leading-relaxed">104 University Avenue<br/>Cambridge, MA 02138, USA</p>
          <p><span class="font-semibold text-on-surface">Tel:</span> +1 (617) 555-0190</p>
          <p><span class="font-semibold text-on-surface">Emergency Line:</span> +1 (617) 555-0911</p>
          <p><span class="font-semibold text-on-surface">Press Office:</span> press@aethelgard.edu</p>
        </div>
      </div>
    </div>
    <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-label-md font-label-md text-on-surface-variant">
      <div>© 2025 Aethelgard University. All rights reserved.</div>
      <div class="flex flex-wrap items-center gap-6">
        <a class="hover:text-primary transition-colors" href="?lang=fa">نسخه فارسی</a>
        <a class="hover:text-primary transition-colors" href="login.php">Portal Sign In</a>
        <a class="hover:text-primary transition-colors" href="register.php">Online Registration</a>
        <a class="hover:text-primary transition-colors" href="#site-footer">Web Accessibility (WCAG 2.1 AA)</a>
      </div>
    </div>
  </div>
</footer>

<!-- Quick Role Login Bar for Samad Evaluation -->
<div class="sticky bottom-0 z-40 bg-primary-container/95 backdrop-blur-md border-t border-secondary/30 text-on-primary px-4 py-2.5 shadow-2xl">
  <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
    <div class="flex items-center gap-2 text-label-md font-semibold text-secondary-fixed">
      <span class="material-symbols-outlined text-[18px]">bolt</span>
      <span>Quick Access Portal Roles:</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <form method="POST" action="login.php" class="inline">
        <input type="hidden" name="username" value="0011223344">
        <input type="hidden" name="password" value="Admin@12345">
        <input type="hidden" name="captcha" value="8452">
        <button type="submit" class="px-3 py-1.5 rounded bg-secondary text-on-secondary hover:bg-secondary-container hover:text-on-secondary-container text-label-sm font-bold transition-colors cursor-pointer">
          Manager (Dr. Hosseini)
        </button>
      </form>
      <form method="POST" action="login.php" class="inline">
        <input type="hidden" name="username" value="0099887766">
        <input type="hidden" name="password" value="Super@12345">
        <input type="hidden" name="captcha" value="8452">
        <button type="submit" class="px-3 py-1.5 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 text-on-primary text-label-sm font-bold transition-colors cursor-pointer">
          Supervisor (Eng. Ghanbari)
        </button>
      </form>
      <form method="POST" action="login.php" class="inline">
        <input type="hidden" name="username" value="4012130045">
        <input type="hidden" name="password" value="Samad@2026!">
        <input type="hidden" name="captcha" value="8452">
        <button type="submit" class="px-3 py-1.5 rounded bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 text-on-primary text-label-sm font-bold transition-colors cursor-pointer">
          Student (Ali Rezaei)
        </button>
      </form>
      <a href="register.php" class="px-3 py-1.5 rounded border border-secondary-fixed/40 text-secondary-fixed hover:bg-secondary-fixed hover:text-on-secondary-fixed text-label-sm font-bold transition-colors">
        New Registration
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
