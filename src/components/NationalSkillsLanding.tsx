import React, { useState } from 'react';
import {
  GraduationCap,
  Settings,
  Lightbulb,
  Users,
  Briefcase,
  Search,
  ChevronLeft,
  Play,
  Palette,
  HardHat,
  Stethoscope,
  Code2,
  Calendar,
  MapPin,
  X,
  Menu,
  Phone,
  Mail,
  Send,
  ExternalLink,
  ShieldCheck,
  Building2,
  Award,
  Sparkles,
  ArrowRight,
  BookOpen,
  LogIn,
  UserPlus
} from 'lucide-react';
import { ViewMode } from '../types';

interface NationalSkillsLandingProps {
  onGoToLogin: () => void;
  onGoToRegister: () => void;
  onGoToManagerPanel: () => void;
  onGoToSupervisorPanel: () => void;
  onGoToPhpWamp: () => void;
}

interface CourseCategory {
  id: string;
  title: string;
  description: string;
  image: string;
  icon: React.ReactNode;
  degrees: string[];
  careers: string[];
  details: string;
}

interface NewsItem {
  id: string;
  title: string;
  date: string;
  image: string;
  summary: string;
  content: string;
  category: string;
}

interface EventItem {
  id: string;
  day: string;
  month: string;
  title: string;
  location: string;
  time: string;
  description: string;
}

export const NationalSkillsLanding: React.FC<NationalSkillsLandingProps> = ({
  onGoToLogin,
  onGoToRegister,
  onGoToManagerPanel,
  onGoToSupervisorPanel,
  onGoToPhpWamp
}) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCourse, setSelectedCourse] = useState<CourseCategory | null>(null);
  const [selectedNews, setSelectedNews] = useState<NewsItem | null>(null);
  const [selectedEvent, setSelectedEvent] = useState<EventItem | null>(null);
  const [videoModalOpen, setVideoModalOpen] = useState(false);
  const [activeNav, setActiveNav] = useState('home');

  // Courses data matching the mockup
  const courses: CourseCategory[] = [
    {
      id: 'art',
      title: 'هنر و طراحی',
      description: 'گرافیک، صنایع دستی، طراحی و...',
      image: '/src/assets/images/course_art_craft_1791092798519.jpg',
      icon: <Palette className="w-5 h-5 text-white" />,
      degrees: ['کاردانی حرفه‌ای گرافیک و هنرهای تجسمی', 'کارشناسی طراحی صنعتی و محصول', 'کاردانی صنایع دستی و چوب'],
      careers: ['طراح محصول و برند', 'هنرمند و مجسمه‌ساز دکوراتیو', 'مدیر آتلیه طراحی و تبلیغات'],
      details: 'دپارتمان هنر و طراحی دانشگاه ملی مهارت با کارگاه‌های مجهز صنایع دستی، طراحی صنعتی، گرافیک رایانه‌ای و سفالگری، بستری پویا برای تربیت طراحان و کارآفرینان حوزه هنر و صنایع خلاق فراهم نموده است.'
    },
    {
      id: 'civil',
      title: 'ساخت و عمران',
      description: 'معماری، عمران، نقشه‌کشی و...',
      image: '/src/assets/images/course_civil_eng_1791092810184.jpg',
      icon: <HardHat className="w-5 h-5 text-white" />,
      degrees: ['کاردانی فنی نقشه‌برداری و ژئوماتیک', 'کارشناسی مهندسی اجرایی عمران', 'کاردانی معماری و طراحی داخلی'],
      careers: ['مهندس ناظر و مجری پروژه‌های عمرانی', 'سرپرست کارگاه ساختمانی و تأسیساتی', 'متخصص مدل‌سازی BIM'],
      details: 'آموزش عملی مهارت‌های نقشه‌کشی، قالب‌بندی بتن، نقشه‌برداری ماهواره‌ای و محاسبات فنی سازه مطابق با آخرین استانداردهای سازمان نظام مهندسی و نیاز بازار ساخت‌وساز کشور.'
    },
    {
      id: 'health',
      title: 'خدمات سلامت',
      description: 'مامایی، بهداشت، آزمایشگاه و...',
      image: '/src/assets/images/course_health_lab_1791092827595.jpg',
      icon: <Stethoscope className="w-5 h-5 text-white" />,
      degrees: ['کاردانی علوم آزمایشگاهی بالینی', 'کاردانی بهداشت عمومی و محیط', 'کارشناسی تکنولوژی تجهیزات پزشکی'],
      careers: ['کارشناس آزمایشگاه‌های تشخیص طبی', 'تکنسین ایمنی بهداشت حرفه‌ای (HSE)', 'اپراتور دستگاه‌های بیمارستانی'],
      details: 'دوره‌های کاربردی پیراپزشکی و سلامت با دسترسی به آزمایشگاه‌های مجهز بیوشیمی، میکروبیولوژی و شبیه‌سازهای پیشرفته بالینی جهت پاسخگویی به کمبود نیروی مهارتی حوزه درمان.'
    },
    {
      id: 'industry',
      title: 'صنایع و تولید',
      description: 'مکانیک، برق، جوشکاری و...',
      image: '/src/assets/images/course_industrial_prod_1791092844209.jpg',
      icon: <Settings className="w-5 h-5 text-white" />,
      degrees: ['کاردانی مکانیک خودرو و ماشین‌افزار', 'کارشناسی مکاترونیک و اتوماسیون', 'کاردانی جوشکاری صنعتی و بازرسی NDT'],
      careers: ['مهندس بهره‌برداری خطوط تولید رباتیک', 'تکنسین ماهر PLC و درایوهای صنعتی', 'مدیر نگهداری و تعمیرات (نت)'],
      details: 'همکاری مستقیم با صنایع خودرو، فولاد و قطعه‌سازی کشور برای اجرای آموزش‌های دوگانه محیط کار واقعی، کارورزی‌های تخصصی و مهارت‌های مورد تقاضای کارخانجات.'
    },
    {
      id: 'it',
      title: 'فناوری اطلاعات',
      description: 'نرم‌افزار، شبکه، امنیت و...',
      image: '/src/assets/images/course_info_tech_1791092855956.jpg',
      icon: <Code2 className="w-5 h-5 text-white" />,
      degrees: ['کارشناسی مهندسی نرم‌افزار و هوش مصنوعی', 'کاردانی فناوری شبکه و کلود', 'کاردانی امنیت سایبری و داده'],
      careers: ['برنامه‌نویس فول‌استک وب و اپلیکیشن', 'ادمین زیرساخت و سرورهای شبکه', 'متخصص امنیت داده و تست نفوذ'],
      details: 'تربیت برنامه‌نویسان و متخصصان شبکه منطبق بر پروژه‌های بازار آزاد و استارتاپ‌ها، با دوره‌های متمرکز بر پایتون، ری‌اکت، هوش مصنوعی، دواپس و لینوکس.'
    }
  ];

  // News matching mockup
  const newsList: NewsItem[] = [
    {
      id: 'n1',
      title: 'آغاز ثبت‌نام دوره‌های مهارتی نیمسال اول ۱۴۰۴',
      date: '۱۴۰۳/۰۵/۱۰',
      category: 'آموزش',
      image: '/src/assets/images/news_tech_students_1791092868652.jpg',
      summary: 'ثبت‌نام متقاضیان دوره‌های کاردانی و کارشناسی مهارتی دانشگاه ملی مهارت در سراسر کشور از طریق درگاه رسمی آغاز شد.',
      content: 'داوطلبان گرامی می‌توانند با مراجعه به سامانه پذیرش و انتخاب رشته‌های مهارتی، بر اساس سوابق تحصیلی و آزمون‌های عملی در پردیس‌های فناوری و مهارت ثبت‌نام نمایند. این دانشگاه با بیش از ۵۰ مرکز در سراسر کشور آماده استقبال از دانشجویان خلاق است.'
    },
    {
      id: 'n2',
      title: 'امضای تفاهم‌نامه همکاری با صنایع بزرگ کشور',
      date: '۱۴۰۳/۰۵/۱۸',
      category: 'ارتباط با صنعت',
      image: '/src/assets/images/news_signing_mou_1791092880372.jpg',
      summary: 'تفاهم‌نامه استراتژیک بورسیه شغلی و کارآموزی صنعتی دانشجویان بین رئیس دانشگاه ملی مهارت و مدیران ارشد صنایع امضا شد.',
      content: 'در راستای تحقق شعار «دانشگاه مهارت، آینده کار»، تفاهم‌نامه سه‌جانبه‌ای منعقد گردید تا دانشجویان برتر پس از گذراندن دوره‌های مهارتی مستقیماً در واحدهای تولیدی و صنعتی همکار جذب و استخدام شوند.'
    },
    {
      id: 'n3',
      title: 'برگزاری جشنواره مهارت‌آموزی در دانشگاه ملی مهارت',
      date: '۱۴۰۳/۰۵/۲۵',
      category: 'فرهنگی و دانشجویی',
      image: '/src/assets/images/news_festival_hall_1791092901038.jpg',
      summary: 'بزرگترین جشنواره سالانه المپیاد و اختراعات مهارتی با حضور نخبگان فنی و اساتید برجسته کشور با شکوه برگزار گردید.',
      content: 'در این رویداد ملی، بیش از ۱۰۰ تیم دانشجویی پروژه‌ها و نوآوری‌های مهارتی خود را در حوزه‌های رباتیک، هوش مصنوعی، صنایع دستی و ساخت تجهیزات در معرض دید داوران و سرمایه‌گذاران خطرپذیر قرار دادند.'
    }
  ];

  // Events matching mockup
  const eventsList: EventItem[] = [
    {
      id: 'e1',
      day: '۲۵',
      month: 'مرداد',
      title: 'وبینار آشنایی با فرصت‌های کارآفرینی در حوزه مهارت',
      location: 'برگزاری آنلاین',
      time: 'ساعت ۱۷:۰۰ الی ۱۹:۰۰',
      description: 'بررسی راهکارهای تاسیس کارگاه‌های تولیدی کوچک، استفاده از تسهیلات اشتغال‌زایی و ورود به بازارهای صادراتی با حضور کارآفرینان موفق.'
    },
    {
      id: 'e2',
      day: '۱۲',
      month: 'شهریور',
      title: 'نمایشگاه دستاوردهای پژوهشی و فناورانه',
      location: 'پردیس پسران نیشابور - سالن همایش‌های خیام',
      time: 'ساعت ۰۹:۰۰ الی ۱۶:۰۰',
      description: 'عرضه بیش از ۵۰ محصول تجاری‌سازی شده دانشجویان دانشگاه ملی مهارت در پاویون اختصاصی فناوری.'
    },
    {
      id: 'e3',
      day: '۲۰',
      month: 'شهریور',
      title: 'کارگاه مهارت‌های شغلی و رزومه‌نویسی',
      location: 'مرکز رشد دانشگاه',
      time: 'ساعت ۱۴:۰۰ الی ۱۸:۰۰',
      description: 'آموزش اصول تدوین رزومه حرفه‌ای، آمادگی برای مصاحبه‌های صنعتی و شبکه‌سازی شغلی در پلتفرم‌های تخصصی.'
    }
  ];

  const handleNavClick = (sectionId: string, navName: string) => {
    setActiveNav(navName);
    setMobileMenuOpen(false);
    const element = document.getElementById(sectionId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  // Filtered search results
  const searchResults = searchQuery.trim()
    ? [
        ...courses.filter(c => c.title.includes(searchQuery) || c.description.includes(searchQuery)).map(c => ({ type: 'رشته آموزشی', title: c.title, action: () => setSelectedCourse(c) })),
        ...newsList.filter(n => n.title.includes(searchQuery) || n.summary.includes(searchQuery)).map(n => ({ type: 'خبر', title: n.title, action: () => setSelectedNews(n) })),
        ...eventsList.filter(e => e.title.includes(searchQuery) || e.description.includes(searchQuery)).map(e => ({ type: 'رویداد', title: e.title, action: () => setSelectedEvent(e) }))
      ]
    : [];

  return (
    <div className="min-h-screen bg-white text-slate-800 antialiased font-sans flex flex-col selection:bg-blue-600 selection:text-white" dir="rtl">
      
      {/* ===================== TOP BANNER NAVBAR ===================== */}
      <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-2xs transition-all">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 sm:h-20 flex items-center justify-between gap-2 sm:gap-4">
          
          {/* Right Brand: Logo & University Name */}
          <div 
            className="flex items-center gap-2.5 sm:gap-3.5 cursor-pointer shrink-0" 
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
          >
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl overflow-hidden flex items-center justify-center p-0.5 shrink-0 bg-teal-50 border border-teal-500/30 shadow-xs">
              <img
                src="/src/assets/images/nishapur_seal_emblem.jpg"
                alt="نشان دانشگاه ملی مهارت واحد پسران نیشابور"
                className="w-full h-full object-cover rounded-lg"
              />
            </div>
            <div className="flex flex-col">
              <h1 className="text-sm sm:text-base md:text-lg font-black tracking-tight text-[#0f2c59] whitespace-nowrap">
                دانشگاه ملی مهارت واحد پسران نیشابور
              </h1>
              <span className="text-[10px] sm:text-[11px] text-teal-700 font-semibold -mt-0.5 whitespace-nowrap">
                پردیس مهارتی و فنی پسران | دیار فیروزه و فرهنگ
              </span>
            </div>
          </div>

          {/* Middle Nav Links (Desktop) */}
          <nav className="hidden lg:flex items-center gap-6 xl:gap-8 text-[13px] xl:text-[13.5px] font-semibold text-slate-700">
            <button
              type="button"
              onClick={() => handleNavClick('hero', 'home')}
              className={`relative py-2 transition-colors cursor-pointer ${
                activeNav === 'home' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              صفحه اصلی
              {activeNav === 'home' && (
                <span className="absolute bottom-0 right-0 left-0 h-[2.5px] bg-[#0056b3] rounded-full" />
              )}
            </button>

            <button
              type="button"
              onClick={() => handleNavClick('about', 'about')}
              className={`py-2 transition-colors cursor-pointer ${
                activeNav === 'about' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              معرفی دانشگاه
            </button>

            <button
              type="button"
              onClick={() => handleNavClick('courses', 'courses')}
              className={`py-2 transition-colors cursor-pointer ${
                activeNav === 'courses' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              رشته‌ها و دوره‌ها
            </button>

            <button
              type="button"
              onClick={() => handleNavClick('features', 'research')}
              className={`py-2 transition-colors cursor-pointer ${
                activeNav === 'research' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              پژوهش و فناوری
            </button>

            <button
              type="button"
              onClick={() => handleNavClick('news-events', 'news')}
              className={`py-2 transition-colors cursor-pointer ${
                activeNav === 'news' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              اخبار و رویدادها
            </button>

            <button
              type="button"
              onClick={() => handleNavClick('footer', 'contact')}
              className={`py-2 transition-colors cursor-pointer ${
                activeNav === 'contact' ? 'text-[#0056b3] font-bold' : 'hover:text-[#0056b3]'
              }`}
            >
              تماس با ما
            </button>
          </nav>

          {/* Left Action Buttons: Search & Login & Hamburger */}
          <div className="flex items-center gap-1.5 sm:gap-2.5">
            <button
              type="button"
              onClick={() => setSearchOpen(true)}
              className="w-9 h-9 sm:w-10 sm:h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors cursor-pointer"
              title="جستجو در سامانه"
              aria-label="جستجو"
            >
              <Search className="w-4 h-4" />
            </button>

            <button
              type="button"
              onClick={onGoToLogin}
              className="px-3.5 sm:px-5 py-2 sm:py-2.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white rounded-xl text-xs font-bold transition-all shadow-2xs hover:shadow-xs cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
            >
              <LogIn className="w-3.5 h-3.5 sm:hidden" />
              <span>ورود / ثبت‌نام</span>
            </button>

            {/* Mobile Hamburger Toggle Button */}
            <button
              type="button"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-700 transition-colors cursor-pointer"
              aria-label="منوی موبایل"
            >
              {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </div>

        {/* Mobile Slide-down Navigation Drawer */}
        {mobileMenuOpen && (
          <div className="lg:hidden bg-white border-b border-slate-200 px-4 py-4 space-y-3 shadow-xl animate-fadeIn">
            <nav className="flex flex-col space-y-1 text-xs font-bold text-slate-700">
              <button
                type="button"
                onClick={() => handleNavClick('hero', 'home')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'home' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>صفحه اصلی</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>

              <button
                type="button"
                onClick={() => handleNavClick('about', 'about')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'about' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>معرفی دانشگاه و آمارها</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>

              <button
                type="button"
                onClick={() => handleNavClick('courses', 'courses')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'courses' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>رشته‌ها و دوره‌های مهارتی</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>

              <button
                type="button"
                onClick={() => handleNavClick('features', 'research')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'research' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>پژوهش، نوآوری و ارتباط با صنعت</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>

              <button
                type="button"
                onClick={() => handleNavClick('news-events', 'news')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'news' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>اخبار و رویدادهای دانشگاه</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>

              <button
                type="button"
                onClick={() => handleNavClick('footer', 'contact')}
                className={`flex items-center justify-between p-2.5 rounded-xl transition-colors text-right ${
                  activeNav === 'contact' ? 'bg-blue-50 text-[#0056b3]' : 'hover:bg-slate-50'
                }`}
              >
                <span>تماس با ما و پردیس‌ها</span>
                <ChevronLeft className="w-4 h-4 text-slate-400" />
              </button>
            </nav>

            {/* Mobile Drawer Action Buttons */}
            <div className="pt-2 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
              <button
                type="button"
                onClick={() => {
                  setMobileMenuOpen(false);
                  onGoToLogin();
                }}
                className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <LogIn className="w-3.5 h-3.5 text-slate-600" />
                <span>ورود به پرتال</span>
              </button>

              <button
                type="button"
                onClick={() => {
                  setMobileMenuOpen(false);
                  onGoToRegister();
                }}
                className="w-full py-2.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white font-bold rounded-xl flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <UserPlus className="w-3.5 h-3.5 text-white" />
                <span>ثبت‌نام جدید</span>
              </button>
            </div>
          </div>
        )}
      </header>

      {/* ===================== HERO SECTION ===================== */}
      <section id="hero" className="relative min-h-[460px] sm:min-h-[540px] md:min-h-[600px] lg:min-h-[640px] flex items-center justify-center overflow-hidden bg-slate-950">
        {/* Background Image with optimized overlay */}
        <div className="absolute inset-0">
          <img
            src="/src/assets/images/nishapur_boys_hero.jpg"
            alt="پردیس دانشگاه ملی مهارت واحد پسران نیشابور"
            className="w-full h-full object-cover object-center transform scale-100"
          />
          {/* Persian geometric stars subtle pattern in underlying layer */}
          <div className="absolute inset-0 bg-persian-stars opacity-40 pointer-events-none" />
          {/* Solid gradient overlay with turquoise/navy tones for clear readability */}
          <div className="absolute inset-0 bg-gradient-to-l from-slate-950/95 via-slate-900/80 to-teal-950/40 lg:w-3/5" />
          <div className="absolute inset-0 bg-teal-950/25" />
        </div>

        {/* Hero Content Container */}
        <div className="relative max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 lg:py-28 z-10 flex flex-col items-start justify-center">
          <div className="max-w-2xl text-right space-y-4 sm:space-y-5">
            
            {/* Small Brand Header Tag with Neyshabur symbols */}
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/40 text-teal-200 text-xs sm:text-sm font-semibold tracking-wide">
              <span className="w-2 h-2 rounded-full bg-teal-400 animate-pulse" />
              <span>پردیس پسران نیشابور • شهر جهانی فیروزه، حکمت خیام و ادب عطار</span>
            </div>

            {/* Giant Hero Headline */}
            <h2 className="text-3xl sm:text-4xl md:text-5xl lg:text-5xl font-black text-white leading-[1.3] tracking-tight">
              دانشگاه ملی مهارت<br />
              <span className="text-teal-300">واحد پسران نیشابور</span>
            </h2>

            {/* Descriptive Paragraph */}
            <p className="text-xs sm:text-sm md:text-base text-slate-200 leading-relaxed font-normal max-w-xl">
              قطب آموزش‌های عالی مهارتی، فنی‌وحرفه‌ای و اشتغال‌محور در شرق کشور؛ مجهز به کارگاه‌های مدرن صنعتی، آزمایشگاه‌های تخصصی مکاترونیک، هوش مصنوعی، گوهرتراشی فیروزه و خوابگاه دانشجویی صمد.
            </p>

            {/* Action Buttons matching mockup */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4 pt-2 sm:pt-3 w-full sm:w-auto">
              <button
                type="button"
                onClick={() => handleNavClick('courses', 'courses')}
                className="w-full sm:w-auto px-6 py-3 bg-white hover:bg-slate-100 text-[#0e4a86] rounded-full text-xs sm:text-sm font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 cursor-pointer group"
              >
                <span>مشاهده رشته‌ها و دوره‌ها</span>
                <ChevronLeft className="w-4 h-4 text-[#0e4a86] group-hover:-translate-x-1 transition-transform" />
              </button>

              <button
                type="button"
                onClick={() => setVideoModalOpen(true)}
                className="w-full sm:w-auto px-5 py-3 rounded-full text-xs sm:text-sm font-semibold text-white bg-slate-900/40 hover:bg-slate-900/60 backdrop-blur-md border border-white/30 transition-all flex items-center justify-center gap-2.5 cursor-pointer"
              >
                <div className="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                  <Play className="w-3 h-3 text-white fill-white ml-0.5" />
                </div>
                <span>معرفی دانشگاه</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* ===================== FLOATING 5-FEATURE HIGHLIGHT BAR ===================== */}
      <section id="features" className="relative -mt-8 sm:-mt-12 lg:-mt-16 z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div className="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-slate-100/90 p-4 sm:p-6 lg:p-8">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 divide-y sm:divide-y-0 lg:divide-x lg:divide-x-reverse divide-slate-100">
            
            {/* Feature 1 (Rightmost) */}
            <div className="flex sm:flex-col items-center sm:text-center text-right gap-3.5 sm:gap-0 px-2 sm:px-3 pt-3 sm:pt-0">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#e6f0fa] text-[#0e4a86] flex items-center justify-center shrink-0 sm:mb-3.5 shadow-2xs">
                <GraduationCap className="w-6 h-6 sm:w-7 sm:h-7" />
              </div>
              <div className="space-y-0.5 sm:space-y-1">
                <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                  آموزش مهارتی و کاربردی
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 leading-normal">
                  با تمرکز بر نیازهای بازار کار و صنعت
                </p>
              </div>
            </div>

            {/* Feature 2 */}
            <div className="flex sm:flex-col items-center sm:text-center text-right gap-3.5 sm:gap-0 px-2 sm:px-3 pt-3 sm:pt-0">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#e6f0fa] text-[#0e4a86] flex items-center justify-center shrink-0 sm:mb-3.5 shadow-2xs">
                <Settings className="w-6 h-6 sm:w-7 sm:h-7" />
              </div>
              <div className="space-y-0.5 sm:space-y-1">
                <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                  ارتباط با صنعت
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 leading-normal">
                  همکاری با بنگاه‌های اقتصادی و کارفرمایان
                </p>
              </div>
            </div>

            {/* Feature 3 */}
            <div className="flex sm:flex-col items-center sm:text-center text-right gap-3.5 sm:gap-0 px-2 sm:px-3 pt-3 sm:pt-0">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#e6f0fa] text-[#0e4a86] flex items-center justify-center shrink-0 sm:mb-3.5 shadow-2xs">
                <Lightbulb className="w-6 h-6 sm:w-7 sm:h-7" />
              </div>
              <div className="space-y-0.5 sm:space-y-1">
                <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                  پژوهش و نوآوری
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 leading-normal">
                  حمایت از ایده‌ها و طرح‌های فناورانه و مهارتی
                </p>
              </div>
            </div>

            {/* Feature 4 */}
            <div className="flex sm:flex-col items-center sm:text-center text-right gap-3.5 sm:gap-0 px-2 sm:px-3 pt-3 sm:pt-0">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#e6f0fa] text-[#0e4a86] flex items-center justify-center shrink-0 sm:mb-3.5 shadow-2xs">
                <Users className="w-6 h-6 sm:w-7 sm:h-7" />
              </div>
              <div className="space-y-0.5 sm:space-y-1">
                <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                  اساتید مجرب
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 leading-normal">
                  بهره‌مندی از اساتید دانشگاهی و متخصصان صنعت
                </p>
              </div>
            </div>

            {/* Feature 5 (Leftmost) */}
            <div className="flex sm:flex-col items-center sm:text-center text-right gap-3.5 sm:gap-0 px-2 sm:px-3 pt-3 sm:pt-0">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#e6f0fa] text-[#0e4a86] flex items-center justify-center shrink-0 sm:mb-3.5 shadow-2xs">
                <Briefcase className="w-6 h-6 sm:w-7 sm:h-7" />
              </div>
              <div className="space-y-0.5 sm:space-y-1">
                <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                  فرصت‌های شغلی
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 leading-normal">
                  ایجاد مسیر شغلی و کارآفرینی برای فارغ‌التحصیلان
                </p>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ===================== COURSES & DEGREE PROGRAMS SECTION ===================== */}
      <section id="courses" className="py-14 sm:py-20 lg:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        {/* Section Header */}
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 sm:gap-6 mb-8 sm:mb-12">
          <div className="space-y-1.5 sm:space-y-2 text-right">
            <span className="text-xs font-bold text-[#0056b3] tracking-wide">
              رشته‌ها و دوره‌ها
            </span>
            <h2 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-[#0f2c59] tracking-tight">
              دوره‌های آموزشی دانشگاه ملی مهارت
            </h2>
            <p className="text-xs sm:text-sm text-slate-500 max-w-2xl leading-relaxed">
              ارائه دوره‌های کاردانی و کارشناسی در حوزه‌های مهارتی و فنی، با رویکرد بازار کار و نیازهای روز صنعت و جامعه.
            </p>
          </div>

          <button
            type="button"
            onClick={() => setSelectedCourse(courses[0])}
            className="px-4 sm:px-5 py-2 sm:py-2.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5 self-start sm:self-auto cursor-pointer shrink-0"
          >
            <span>مشاهده همه رشته‌ها</span>
            <ChevronLeft className="w-4 h-4" />
          </button>
        </div>

        {/* 5 Course Cards Grid matching mockup */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
          {courses.map((course) => (
            <div
              key={course.id}
              onClick={() => setSelectedCourse(course)}
              className="group bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-2xs hover:shadow-xl transition-all duration-300 flex flex-col justify-between cursor-pointer hover:-translate-y-1"
            >
              <div>
                {/* Course Image */}
                <div className="relative h-36 sm:h-40 overflow-hidden bg-slate-100">
                  <img
                    src={course.image}
                    alt={course.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" />
                </div>

                {/* Floating Round Icon intersecting image & card body */}
                <div className="relative -mt-6 mx-auto w-12 h-12 rounded-full bg-[#0e4a86] border-3 border-white shadow-md flex items-center justify-center">
                  {course.icon}
                </div>

                {/* Title & Short Description */}
                <div className="p-4 pt-2 text-center space-y-1.5">
                  <h3 className="font-bold text-sm text-slate-900 group-hover:text-[#0056b3] transition-colors">
                    {course.title}
                  </h3>
                  <p className="text-[11px] text-slate-500 leading-normal line-clamp-1">
                    {course.description}
                  </p>
                </div>
              </div>

              {/* Quiet Card Footer */}
              <div className="p-3 pt-0 pb-4 text-center">
                <span className="text-[10px] font-bold text-[#0056b3] group-hover:underline inline-flex items-center gap-1">
                  <span>جزئیات و ثبت‌نام</span>
                  <ChevronLeft className="w-3 h-3" />
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ===================== UNIVERSITY IN A GLANCE (STATS BANNER) ===================== */}
      <section id="about" className="relative py-12 sm:py-16 lg:py-20 bg-gradient-to-br from-[#0c2a52] via-[#092140] to-[#05152b] text-white overflow-hidden">
        {/* Subtle background tech graphic overlay */}
        <div className="absolute inset-0 opacity-15 pointer-events-none">
          <img
            src="/src/assets/images/news_tech_students_1791092868652.jpg"
            alt="دانشجویان در کارگاه"
            className="w-full h-full object-cover mix-blend-luminosity filter blur-xs"
          />
        </div>

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-10">
          {/* Section Heading */}
          <div className="text-center space-y-1.5 sm:space-y-2 mb-8 sm:mb-12">
            <h2 className="text-xl sm:text-2xl md:text-3xl font-black tracking-tight text-white">
              دانشگاه ملی مهارت در یک نگاه
            </h2>
            <p className="text-xs sm:text-sm text-sky-200">
              با افتخار در مسیر تربیت نیروی ماهر و توسعه کشور
            </p>
          </div>

          {/* 4 Big Metrics Grid with Dividers */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 lg:gap-8 divide-y sm:divide-y-0 lg:divide-x lg:divide-x-reverse divide-white/10">
            
            {/* Stat 1 */}
            <div className="flex flex-col items-center text-center p-3 sm:p-4 rounded-2xl bg-white/5 sm:bg-transparent border border-white/10 sm:border-0">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full border border-sky-400/30 bg-white/5 flex items-center justify-center mb-2 sm:mb-3 text-sky-300">
                <Sparkles className="w-5 h-5 sm:w-6 sm:h-6" />
              </div>
              <span className="text-2xl sm:text-3xl lg:text-4xl font-black text-white font-mono tracking-tight mb-1">
                ۱۰+
              </span>
              <span className="text-[11px] sm:text-xs text-sky-100 font-medium leading-tight">
                سال تجربه در آموزش مهارتی
              </span>
            </div>

            {/* Stat 2 */}
            <div className="flex flex-col items-center text-center p-3 sm:p-4 rounded-2xl bg-white/5 sm:bg-transparent border border-white/10 sm:border-0">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full border border-sky-400/30 bg-white/5 flex items-center justify-center mb-2 sm:mb-3 text-sky-300">
                <Building2 className="w-5 h-5 sm:w-6 sm:h-6" />
              </div>
              <span className="text-2xl sm:text-3xl lg:text-4xl font-black text-white font-mono tracking-tight mb-1">
                ۵۰+
              </span>
              <span className="text-[11px] sm:text-xs text-sky-100 font-medium leading-tight">
                پردیس و مرکز آموزشی
              </span>
            </div>

            {/* Stat 3 */}
            <div className="flex flex-col items-center text-center p-3 sm:p-4 rounded-2xl bg-white/5 sm:bg-transparent border border-white/10 sm:border-0">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full border border-sky-400/30 bg-white/5 flex items-center justify-center mb-2 sm:mb-3 text-sky-300">
                <GraduationCap className="w-5 h-5 sm:w-6 sm:h-6" />
              </div>
              <span className="text-2xl sm:text-3xl lg:text-4xl font-black text-white font-mono tracking-tight mb-1">
                ۳۰۰+
              </span>
              <span className="text-[11px] sm:text-xs text-sky-100 font-medium leading-tight">
                رشته و گرایش
              </span>
            </div>

            {/* Stat 4 */}
            <div className="flex flex-col items-center text-center p-3 sm:p-4 rounded-2xl bg-white/5 sm:bg-transparent border border-white/10 sm:border-0">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full border border-sky-400/30 bg-white/5 flex items-center justify-center mb-2 sm:mb-3 text-sky-300">
                <Users className="w-5 h-5 sm:w-6 sm:h-6" />
              </div>
              <span className="text-2xl sm:text-3xl lg:text-4xl font-black text-white font-mono tracking-tight mb-1">
                ۲۵,۰۰۰+
              </span>
              <span className="text-[11px] sm:text-xs text-sky-100 font-medium leading-tight">
                دانشجوی فعال
              </span>
            </div>

          </div>
        </div>
      </section>

      {/* ===================== NEWS & EVENTS SECTION ===================== */}
      <section id="news-events" className="py-14 sm:py-20 lg:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
          
          {/* Right Column: Events (رویدادها) */}
          <div className="lg:col-span-5 space-y-4 sm:space-y-6">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 className="text-lg sm:text-xl font-bold text-slate-900">
                رویدادها
              </h2>
              <button
                type="button"
                onClick={() => setSelectedEvent(eventsList[0])}
                className="text-xs font-semibold text-[#0056b3] hover:underline flex items-center gap-1 cursor-pointer"
              >
                <span>مشاهده همه</span>
                <ChevronLeft className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* Events List */}
            <div className="space-y-3 sm:space-y-4">
              {eventsList.map((event) => (
                <div
                  key={event.id}
                  onClick={() => setSelectedEvent(event)}
                  className="p-3 sm:p-4 bg-slate-50/80 hover:bg-slate-100/90 rounded-2xl border border-slate-100 flex items-center justify-between gap-3 sm:gap-4 transition-all cursor-pointer group"
                >
                  <div className="space-y-1 sm:space-y-1.5 flex-1 text-right min-w-0">
                    <h4 className="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#0056b3] transition-colors leading-snug line-clamp-2">
                      {event.title}
                    </h4>
                    <div className="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-slate-500">
                      <MapPin className="w-3 h-3 text-slate-400 shrink-0" />
                      <span className="truncate">{event.location}</span>
                    </div>
                  </div>

                  {/* Date badge on left side */}
                  <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white border border-slate-200 shadow-2xs flex flex-col items-center justify-center shrink-0">
                    <span className="text-sm sm:text-base font-black text-[#0056b3] font-mono leading-none">
                      {event.day}
                    </span>
                    <span className="text-[9px] sm:text-[10px] font-bold text-slate-600 mt-0.5">
                      {event.month}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Left Column: Latest News (آخرین اخبار) */}
          <div className="lg:col-span-7 space-y-4 sm:space-y-6">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 className="text-lg sm:text-xl font-bold text-slate-900">
                آخرین اخبار
              </h2>
              <button
                type="button"
                onClick={() => setSelectedNews(newsList[0])}
                className="text-xs font-semibold text-[#0056b3] hover:underline flex items-center gap-1 cursor-pointer"
              >
                <span>مشاهده همه</span>
                <ChevronLeft className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* News Cards Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-5">
              {newsList.map((news) => (
                <div
                  key={news.id}
                  onClick={() => setSelectedNews(news)}
                  className="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-2xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between cursor-pointer group"
                >
                  <div>
                    <div className="relative h-32 sm:h-36 overflow-hidden bg-slate-100">
                      <img
                        src={news.image}
                        alt={news.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      />
                    </div>

                    <div className="p-3 sm:p-3.5 space-y-1.5 sm:space-y-2">
                      <h4 className="font-bold text-xs text-slate-900 group-hover:text-[#0056b3] transition-colors leading-snug line-clamp-2">
                        {news.title}
                      </h4>
                      <div className="flex items-center gap-1 text-[10px] text-slate-400 font-mono">
                        <Calendar className="w-3 h-3 text-slate-400" />
                        <span>{news.date}</span>
                      </div>
                    </div>
                  </div>

                  <div className="p-3 sm:p-3.5 pt-0">
                    <span className="text-[10px] font-bold text-[#0056b3] group-hover:underline inline-flex items-center gap-1">
                      <span>مطالعه خبر</span>
                      <ChevronLeft className="w-3 h-3" />
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>

        </div>
      </section>

      {/* ===================== CALL TO ACTION BANNER ===================== */}
      <section className="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div className="bg-gradient-to-r from-blue-50 via-sky-50 to-indigo-50/60 rounded-2xl sm:rounded-3xl border border-sky-100 p-6 sm:p-8 md:p-10 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xs text-center sm:text-right">
          <div className="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
            <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white shadow-xs border border-sky-200 flex items-center justify-center p-1.5 shrink-0 overflow-hidden">
              <img
                src="/src/assets/images/samad_campus_emblem_1790759744696.jpg"
                alt="دانشگاه ملی مهارت"
                className="w-full h-full object-contain"
              />
            </div>
            <div className="space-y-1 sm:space-y-1.5">
              <h3 className="text-lg sm:text-xl md:text-2xl font-black text-[#0f2c59] tracking-tight">
                آینده شغلی خود را با مهارت بسازید
              </h3>
              <p className="text-xs sm:text-sm text-slate-600 max-w-md">
                همین امروز برای ثبت‌نام در دوره‌های دانشگاه ملی مهارت اقدام کنید.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onGoToRegister}
            className="w-full sm:w-auto px-7 py-3.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer"
          >
            <span>ثبت‌نام در دوره‌ها</span>
            <ChevronLeft className="w-4 h-4" />
          </button>
        </div>
      </section>

      {/* ===================== QUICK ACCESS MANAGEMENT BAR ===================== */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 w-full">
        <div className="p-3.5 sm:p-4 bg-slate-900 text-white rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
          <div className="flex items-center gap-2">
            <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
            <span className="font-bold">دسترسی سریع عوامل اجرایی:</span>
            <span className="text-slate-400 hidden md:inline">ورود مستقیم به بخش‌های مدیریتی دانشگاه و خوابگاه</span>
          </div>

          <div className="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2 w-full sm:w-auto">
            <button
              type="button"
              onClick={onGoToManagerPanel}
              className="px-3 py-2 sm:py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white rounded-lg font-bold transition-colors cursor-pointer text-center"
            >
              🏢 پنل مدیریت خوابگاه‌ها (صمد)
            </button>
            <button
              type="button"
              onClick={onGoToSupervisorPanel}
              className="px-3 py-2 sm:py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 hover:text-white rounded-lg font-bold transition-colors cursor-pointer text-center"
            >
              🛡 پنل سرپرست شبانه
            </button>
            <button
              type="button"
              onClick={onGoToPhpWamp}
              className="px-3 py-2 sm:py-1.5 bg-indigo-700 hover:bg-indigo-600 text-white rounded-lg font-bold transition-colors cursor-pointer text-center"
            >
              💾 نسخه PHP لوکال WAMP
            </button>
          </div>
        </div>
      </div>

      {/* ===================== FOOTER ===================== */}
      <footer id="footer" className="mt-auto bg-[#07172c] bg-neyshabur-tile text-white pt-10 sm:pt-14 pb-8 border-t border-teal-900/60 relative overflow-hidden">
        {/* Subtle decorative turquoise glow overlay */}
        <div className="absolute top-0 right-1/4 w-96 h-96 bg-teal-500/5 rounded-full blur-3xl pointer-events-none" />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-12 relative z-10">
          
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-10 text-right">
            
            {/* Column 1 (Right): University Branding & Contact */}
            <div className="space-y-3 sm:space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-teal-950/60 border border-teal-500/40 p-0.5 overflow-hidden shrink-0 shadow-sm">
                  <img
                    src="/src/assets/images/nishapur_seal_emblem.jpg"
                    alt="نشان دانشگاه ملی مهارت واحد پسران نیشابور"
                    className="w-full h-full object-cover rounded-lg"
                  />
                </div>
                <div>
                  <h4 className="font-black text-sm text-white">دانشگاه ملی مهارت واحد پسران نیشابور</h4>
                  <span className="text-[10px] text-teal-400 block font-medium">پردیس مهارتی و فنی پسران • پایتخت فیروزه ایران</span>
                </div>
              </div>

              <div className="space-y-2 text-xs text-slate-300 pt-2">
                <div className="flex items-start gap-2">
                  <MapPin className="w-4 h-4 text-teal-400 shrink-0 mt-0.5" />
                  <span className="leading-relaxed">خراسان رضوی، نیشابور، میدان دانشگاه، بلوار پژوهش، پردیس پسران دانشگاه ملی مهارت</span>
                </div>
                <div className="flex items-center gap-2">
                  <Phone className="w-4 h-4 text-teal-400 shrink-0" />
                  <span className="font-mono text-teal-300">۰۵۱-۴۲۶۲۵۰۰۱ - ۰۵۱-۴۲۶۲۵۰۰۲</span>
                </div>
                <div className="flex items-center gap-2">
                  <Mail className="w-4 h-4 text-teal-400 shrink-0" />
                  <span className="font-mono">info@neyshabur-skill.ac.ir</span>
                </div>
              </div>
            </div>

            {/* Column 2: Useful Links */}
            <div className="space-y-2.5 sm:space-y-3">
              <h4 className="font-bold text-sm text-teal-300 border-b border-teal-800/40 pb-2">
                لینک‌های مفید دانشگاهی
              </h4>
              <ul className="space-y-2 text-xs text-slate-300">
                <li>
                  <a href="https://msrt.ir" target="_blank" rel="noreferrer" className="hover:text-teal-300 transition-colors flex items-center gap-1.5">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>وزارت علوم، تحقیقات و فناوری</span>
                  </a>
                </li>
                <li>
                  <a href="https://irantvto.ir" target="_blank" rel="noreferrer" className="hover:text-teal-300 transition-colors flex items-center gap-1.5">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>سازمان آموزش فنی و حرفه‌ای کشور</span>
                  </a>
                </li>
                <li>
                  <button type="button" onClick={onGoToRegister} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>سامانه پذیرش نودانشجویان نیشابور</span>
                  </button>
                </li>
                <li>
                  <button type="button" onClick={onGoToLogin} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>سامانه جامع اسکان و خوابگاه صمد</span>
                  </button>
                </li>
              </ul>
            </div>

            {/* Column 3: Quick Access */}
            <div className="space-y-2.5 sm:space-y-3">
              <h4 className="font-bold text-sm text-teal-300 border-b border-teal-800/40 pb-2">
                دسترسی سریع دانشجویی
              </h4>
              <ul className="space-y-2 text-xs text-slate-300">
                <li>
                  <button type="button" onClick={onGoToRegister} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>ثبت‌نام دوره کاردانی و کارشناسی</span>
                  </button>
                </li>
                <li>
                  <button type="button" onClick={() => handleNavClick('about', 'about')} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>کارگاه‌ها و آزمایشگاه‌های پردیس</span>
                  </button>
                </li>
                <li>
                  <button type="button" onClick={() => handleNavClick('features', 'research')} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>مرکز تخصصی گوهرتراشی فیروزه</span>
                  </button>
                </li>
                <li>
                  <button type="button" onClick={onGoToLogin} className="hover:text-teal-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <ChevronLeft className="w-3 h-3 text-teal-500" />
                    <span>ورود به کارتابل رفاهی صمد</span>
                  </button>
                </li>
              </ul>
            </div>

            {/* Column 4 (Left): Social Media & Cultural Emblem */}
            <div className="space-y-3 sm:space-y-4">
              <h4 className="font-bold text-sm text-teal-300 border-b border-teal-800/40 pb-2">
                افتخارات و نماد نیشابور
              </h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                💎 نیشابور، پایتخت کهن علم و هنر و شهر جهانی سنگ فیروزه. پیوند مهارت‌های نوین فنی و مهندسی با اصالت کهن خراسانی.
              </p>

              <div className="flex items-center gap-2 pt-1">
                <a
                  href="#social"
                  className="w-9 h-9 rounded-xl bg-teal-950/60 hover:bg-teal-600 text-white border border-teal-500/30 flex items-center justify-center transition-colors cursor-pointer"
                  title="تلگرام پردیس پسران نیشابور"
                >
                  <Send className="w-4 h-4" />
                </a>
                <a
                  href="#social"
                  className="w-9 h-9 rounded-xl bg-teal-950/60 hover:bg-teal-600 text-white border border-teal-500/30 flex items-center justify-center transition-colors cursor-pointer"
                  title="کانال ایتا دانشگاه ملی مهارت نیشابور"
                >
                  <Award className="w-4 h-4" />
                </a>
                <a
                  href="#social"
                  className="w-9 h-9 rounded-xl bg-teal-950/60 hover:bg-teal-600 text-white border border-teal-500/30 flex items-center justify-center transition-colors cursor-pointer"
                  title="پایگاه مقالات و پژوهش‌های مهارتی"
                >
                  <BookOpen className="w-4 h-4" />
                </a>
              </div>
            </div>

          </div>

          {/* Bottom Copyright Bar */}
          <div className="pt-6 border-t border-teal-900/60 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-3 text-center sm:text-right">
            <p>© ۱۴۰۳ کلیه حقوق مادی و معنوی برای دانشگاه ملی مهارت واحد پسران نیشابور محفوظ است.</p>
            <div className="flex items-center gap-4 text-[11px] text-teal-400">
              <span className="hover:text-white transition-colors cursor-pointer">حکیم عمر خیام</span>
              <span>·</span>
              <span className="hover:text-white transition-colors cursor-pointer">شیخ فریدالدین عطار</span>
              <span>·</span>
              <span className="hover:text-white transition-colors cursor-pointer">پردیس پسران نیشابور</span>
            </div>
          </div>

        </div>
      </footer>

      {/* ===================== MODAL: SEARCH OVERLAY ===================== */}
      {searchOpen && (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-20 bg-black/60 backdrop-blur-xs p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-100 max-h-[85vh] overflow-y-auto">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <Search className="w-4 h-4 text-[#0056b3]" />
                <span>جستجو در دانشگاه ملی مهارت</span>
              </div>
              <button
                type="button"
                onClick={() => setSearchOpen(false)}
                className="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            <div className="relative">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                autoFocus
                placeholder="عنوان رشته، خبر، رویداد یا مهارت مورد نظر خود را تایپ کنید..."
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:border-[#0056b3] focus:bg-white outline-none transition-all shadow-inner"
              />
            </div>

            {searchQuery.trim() && (
              <div className="space-y-2 max-h-60 overflow-y-auto pt-2">
                <span className="text-[11px] text-slate-400 block">نتایج جستجو:</span>
                {searchResults.length > 0 ? (
                  searchResults.map((res, i) => (
                    <div
                      key={i}
                      onClick={() => {
                        res.action();
                        setSearchOpen(false);
                      }}
                      className="p-3 bg-slate-50 hover:bg-blue-50/60 rounded-xl border border-slate-100 flex items-center justify-between cursor-pointer transition-colors"
                    >
                      <span className="text-xs font-bold text-slate-800">{res.title}</span>
                      <span className="px-2 py-0.5 rounded text-[10px] bg-white border border-slate-200 text-slate-500 font-mono">
                        {res.type}
                      </span>
                    </div>
                  ))
                ) : (
                  <p className="text-xs text-slate-500 text-center py-6">
                    موردی منطبق بر «{searchQuery}» یافت نشد.
                  </p>
                )}
              </div>
            )}

            {!searchQuery.trim() && (
              <div className="pt-2 text-xs text-slate-500 space-y-2">
                <span className="text-[11px] text-slate-400 block">پیشنهادهای محبوب:</span>
                <div className="flex flex-wrap gap-1.5">
                  {['فناوری اطلاعات', 'صنایع و تولید', 'ساخت و عمران', 'ثبت‌نام نیمسال اول', 'کارگاه رزومه‌نویسی'].map(tag => (
                    <button
                      key={tag}
                      type="button"
                      onClick={() => setSearchQuery(tag)}
                      className="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs transition-colors cursor-pointer"
                    >
                      #{tag}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ===================== MODAL: COURSE DETAILS ===================== */}
      {selectedCourse && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-5 my-8 relative overflow-hidden max-h-[90vh] overflow-y-auto">
            <div className="relative h-40 sm:h-48 -mx-5 sm:-mx-6 -mt-5 sm:-mt-6 overflow-hidden">
              <img
                src={selectedCourse.image}
                alt={selectedCourse.title}
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-900/85 via-transparent to-transparent flex items-end p-5 sm:p-6">
                <div className="flex items-center gap-3">
                  <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-[#0e4a86] text-white flex items-center justify-center border-2 border-white shadow-md shrink-0">
                    {selectedCourse.icon}
                  </div>
                  <div>
                    <h3 className="text-lg sm:text-xl font-bold text-white">{selectedCourse.title}</h3>
                    <span className="text-xs text-sky-200">{selectedCourse.description}</span>
                  </div>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setSelectedCourse(null)}
                className="absolute top-3 left-3 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/80 transition-colors cursor-pointer"
              >
                ✕
              </button>
            </div>

            <div className="space-y-3.5 text-xs">
              <p className="text-slate-600 leading-relaxed text-xs sm:text-sm">
                {selectedCourse.details}
              </p>

              <div className="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                <span className="font-bold text-slate-900 block text-xs">مقطع‌ها و گرایش‌های فعال:</span>
                <ul className="space-y-1 text-slate-600">
                  {selectedCourse.degrees.map((deg, i) => (
                    <li key={i} className="flex items-center gap-2">
                      <span className="w-1.5 h-1.5 rounded-full bg-[#0056b3] shrink-0" />
                      <span>{deg}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100 space-y-2">
                <span className="font-bold text-[#0e4a86] block text-xs">موقعیت‌های شغلی پس از فارغ‌التحصیلی:</span>
                <div className="flex flex-wrap gap-1.5">
                  {selectedCourse.careers.map((car, i) => (
                    <span key={i} className="px-2.5 py-1 bg-white text-[#0e4a86] font-semibold rounded-lg shadow-2xs border border-blue-200">
                      ✓ {car}
                    </span>
                  ))}
                </div>
              </div>
            </div>

            <div className="pt-2 flex items-center justify-between border-t border-slate-100 gap-3">
              <button
                type="button"
                onClick={() => setSelectedCourse(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
              >
                بستن
              </button>

              <button
                type="button"
                onClick={() => {
                  setSelectedCourse(null);
                  onGoToRegister();
                }}
                className="px-5 py-2.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white rounded-xl text-xs font-bold shadow-md cursor-pointer flex items-center gap-1.5"
              >
                <span>ثبت‌نام در این رشته</span>
                <ChevronLeft className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: NEWS DETAILS ===================== */}
      {selectedNews && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 my-8 relative overflow-hidden max-h-[90vh] overflow-y-auto">
            <div className="relative h-44 sm:h-48 -mx-5 sm:-mx-6 -mt-5 sm:-mt-6 overflow-hidden">
              <img
                src={selectedNews.image}
                alt={selectedNews.title}
                className="w-full h-full object-cover"
              />
              <button
                type="button"
                onClick={() => setSelectedNews(null)}
                className="absolute top-3 left-3 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/80 transition-colors cursor-pointer"
              >
                ✕
              </button>
            </div>

            <div className="space-y-3">
              <div className="flex items-center justify-between text-xs text-slate-400">
                <span className="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0056b3] font-bold">
                  {selectedNews.category}
                </span>
                <span className="font-mono">{selectedNews.date}</span>
              </div>

              <h3 className="text-sm sm:text-base md:text-lg font-bold text-slate-900 leading-snug">
                {selectedNews.title}
              </h3>

              <p className="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                {selectedNews.content}
              </p>
            </div>

            <div className="pt-3 border-t border-slate-100 flex justify-end">
              <button
                type="button"
                onClick={() => setSelectedNews(null)}
                className="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
              >
                بستن پنجره
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: EVENT DETAILS ===================== */}
      {selectedEvent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center gap-3">
                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-blue-50 border border-blue-100 text-[#0056b3] flex flex-col items-center justify-center font-bold shrink-0">
                  <span className="text-sm sm:text-base font-black font-mono leading-none">{selectedEvent.day}</span>
                  <span className="text-[9px] sm:text-[10px] mt-0.5">{selectedEvent.month}</span>
                </div>
                <div>
                  <h3 className="text-xs sm:text-sm font-bold text-slate-900">{selectedEvent.title}</h3>
                  <span className="text-[10px] sm:text-[11px] text-slate-500">{selectedEvent.location}</span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setSelectedEvent(null)}
                className="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            <div className="space-y-3 text-xs text-slate-600">
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <div className="flex items-center gap-2 text-slate-700 font-semibold">
                  <Calendar className="w-3.5 h-3.5 text-[#0056b3] shrink-0" />
                  <span>زمان برگزاری: {selectedEvent.time}</span>
                </div>
                <div className="flex items-center gap-2 text-slate-700 font-semibold">
                  <MapPin className="w-3.5 h-3.5 text-[#0056b3] shrink-0" />
                  <span>مکان: {selectedEvent.location}</span>
                </div>
              </div>

              <p className="leading-relaxed text-xs sm:text-sm">
                {selectedEvent.description}
              </p>
            </div>

            <div className="pt-2 flex items-center justify-between border-t border-slate-100 gap-3">
              <button
                type="button"
                onClick={() => setSelectedEvent(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
              >
                بستن
              </button>

              <button
                type="button"
                onClick={() => {
                  setSelectedEvent(null);
                  onGoToRegister();
                }}
                className="px-5 py-2.5 bg-[#0e4a86] hover:bg-[#0b3b6c] text-white rounded-xl text-xs font-bold shadow-md cursor-pointer"
              >
                ثبت‌نام در این رویداد
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: UNIVERSITY INTRO VIDEO ===================== */}
      {videoModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-fadeIn">
          <div className="bg-[#0f172a] text-white rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-700 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <div className="flex items-center gap-2">
                <Play className="w-4 h-4 text-sky-400 fill-sky-400" />
                <h3 className="text-sm font-bold text-white">تیزر معرفی دانشگاه ملی مهارت</h3>
              </div>
              <button
                type="button"
                onClick={() => setVideoModalOpen(false)}
                className="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            {/* Video Player Mockup with campus imagery */}
            <div className="relative aspect-video rounded-2xl overflow-hidden bg-black border border-slate-800 flex items-center justify-center">
              <img
                src="/src/assets/images/university_hero_campus_1791092785319.jpg"
                alt="تیزر پردیس"
                className="w-full h-full object-cover opacity-80"
              />
              <div className="absolute inset-0 bg-black/40 flex flex-col items-center justify-center gap-3 p-4 text-center">
                <div className="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white/20 backdrop-blur-md border border-white/40 flex items-center justify-center hover:scale-110 transition-transform cursor-pointer shadow-xl">
                  <Play className="w-7 h-7 sm:w-8 sm:h-8 text-white fill-white ml-1" />
                </div>
                <span className="text-[11px] sm:text-xs font-bold text-white tracking-wide">
                  پخش مستند دانشگاه مهارت، آینده کار (مدت: ۳:۴۵)
                </span>
              </div>
            </div>

            <div className="text-xs text-slate-300 leading-relaxed">
              معرفی زیرساخت‌های کارگاهی، مراکز رشد، خوابگاه‌های دانشجویی، آزمایشگاه‌های پیشرفته و تفاهم‌نامه‌های بین‌المللی دانشگاه ملی مهارت.
            </div>

            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={() => setVideoModalOpen(false)}
                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold cursor-pointer"
              >
                بستن پنجره
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
