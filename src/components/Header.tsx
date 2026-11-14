import React from 'react';
import { ViewMode } from '../types';
import { LogIn, UserPlus, Sparkles, PhoneCall, Home, Sun, Moon } from 'lucide-react';

interface HeaderProps {
  currentView: ViewMode;
  onSelectView: (view: ViewMode) => void;
  onFillSampleData?: () => void;
  theme?: 'dark' | 'light';
  onToggleTheme?: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  currentView,
  onSelectView,
  onFillSampleData,
  theme = 'dark',
  onToggleTheme
}) => {
  const isLight = theme === 'light';

  return (
    <header className={`sticky top-0 z-40 backdrop-blur-md border-b transition-colors duration-200 ${
      isLight ? 'bg-white/95 border-slate-200 text-slate-800 shadow-xs' : 'bg-slate-900/95 border-slate-800 text-white'
    }`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        {/* Zone 1: Wordmark & Logo */}
        <div 
          onClick={() => onSelectView('landing')}
          className="flex items-center gap-3 cursor-pointer group shrink-0"
        >
          <div className="w-10 h-10 rounded-xl overflow-hidden shadow-sm border border-teal-500/40 bg-teal-950/40 flex items-center justify-center p-0.5">
            <img 
              src="/src/assets/images/nishapur_seal_emblem.jpg" 
              alt="نشان دانشگاه ملی مهارت واحد پسران نیشابور" 
              referrerPolicy="no-referrer"
              className="w-full h-full object-cover rounded-lg"
            />
          </div>
          <div className="flex flex-col">
            <span className={`text-sm sm:text-base font-black tracking-tight transition-colors ${
              isLight ? 'text-slate-900 group-hover:text-teal-600' : 'text-white group-hover:text-teal-300'
            }`}>
              دانشگاه ملی مهارت واحد پسران نیشابور
            </span>
            <span className="text-[10px] sm:text-[11px] text-teal-500 font-medium -mt-0.5">
              سامانه یکپارچه خدمات رفاهی و دانشجویی صمد
            </span>
          </div>
        </div>

        {/* Zone 2: Navigation Links / Mode Switcher */}
        <nav className={`hidden md:flex items-center gap-1.5 p-1 rounded-xl border transition-colors ${
          isLight ? 'bg-slate-100 border-slate-200' : 'bg-slate-800/80 border-slate-700/80'
        }`}>
          <button
            type="button"
            onClick={() => onSelectView('landing')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'landing'
                ? 'bg-teal-600 text-white shadow-sm font-bold'
                : isLight
                ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
            }`}
          >
            <Home className="w-3.5 h-3.5" />
            <span>صفحه اصلی (نیشابور)</span>
          </button>

          <button
            type="button"
            onClick={() => onSelectView('login')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'login'
                ? 'bg-indigo-600 text-white shadow-sm font-bold'
                : isLight
                ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
            }`}
          >
            <LogIn className="w-3.5 h-3.5" />
            <span>ورود دانشجو</span>
          </button>

          <button
            type="button"
            onClick={() => onSelectView('register')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'register'
                ? 'bg-indigo-600 text-white shadow-sm font-bold'
                : isLight
                ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
            }`}
          >
            <UserPlus className="w-3.5 h-3.5" />
            <span>ثبت‌نام نودانشجو</span>
          </button>

          <button
            type="button"
            onClick={() => onSelectView('manager_panel')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'manager_panel'
                ? 'bg-amber-600 text-white shadow-sm font-bold'
                : isLight
                ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
            }`}
          >
            <span>🏢 پنل مدیر خوابگاه</span>
          </button>

          <button
            type="button"
            onClick={() => onSelectView('supervisor_panel')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'supervisor_panel'
                ? 'bg-emerald-600 text-white shadow-sm font-bold'
                : isLight
                ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
            }`}
          >
            <span>🛡 سرپرست شبانه</span>
          </button>

          <button
            type="button"
            onClick={() => onSelectView('php_export')}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap cursor-pointer ${
              currentView === 'php_export'
                ? 'bg-cyan-700 text-white shadow-sm'
                : 'text-cyan-300 hover:bg-cyan-950 font-bold'
            }`}
          >
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>💾 کدهای WAMP</span>
          </button>
        </nav>

        {/* Zone 3: Actions, Theme Switcher & Support */}
        <div className="flex items-center gap-2.5">
          {/* Theme Toggle Button */}
          {onToggleTheme && (
            <button
              type="button"
              onClick={onToggleTheme}
              className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 text-yellow-300 hover:text-yellow-200 transition-colors cursor-pointer"
              title="تغییر تم سامانه (تاریک / روشن) - ذخیره در سشن"
            >
              {theme === 'dark' ? (
                <>
                  <Sun className="w-3.5 h-3.5 text-amber-400" />
                  <span className="hidden sm:inline text-[11px] text-slate-200">تم روشن</span>
                </>
              ) : (
                <>
                  <Moon className="w-3.5 h-3.5 text-indigo-400" />
                  <span className="hidden sm:inline text-[11px] text-slate-200">تم تاریک</span>
                </>
              )}
            </button>
          )}

          {onFillSampleData && currentView === 'register' && (
            <button
              type="button"
              onClick={onFillSampleData}
              className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-300 bg-amber-950/60 hover:bg-amber-900 border border-amber-600/60 rounded-xl transition-colors cursor-pointer"
              title="پر کردن فرم با اطلاعات تستی برای تست سریع"
            >
              <Sparkles className="w-3.5 h-3.5 text-amber-400" />
              <span>تکمیل آزمایشی</span>
            </button>
          )}

          <div className="hidden xl:flex items-center gap-1.5 text-xs text-slate-400 border-r border-slate-800 pr-3 mr-1">
            <PhoneCall className="w-3.5 h-3.5 text-teal-400" />
            <span>پردیس نیشابور:</span>
            <span className="font-mono text-teal-300 dir-ltr text-left">۰۵۱-۴۲۶۲۵۰۰۱</span>
          </div>
        </div>
      </div>
    </header>
  );
};
