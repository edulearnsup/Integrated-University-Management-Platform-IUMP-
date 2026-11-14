import React, { useState, useRef, useEffect } from 'react';
import { Calendar, ChevronRight, ChevronLeft, Check, Sparkles } from 'lucide-react';
import {
  PERSIAN_MONTHS,
  gregorianToJalali,
  jalaliToGregorian,
  formatToPersianDateShort
} from '../utils/persianDate';

interface PersianDatePickerProps {
  value: string; // "YYYY/MM/DD" in Jalali or "YYYY-MM-DD" in Gregorian
  onChange: (jalaliDate: string, gregorianDate?: string) => void;
  label?: string;
  placeholder?: string;
  minDate?: string;
  className?: string;
  required?: boolean;
  theme?: 'dark' | 'light';
  helperText?: string;
}

export const PersianDatePicker: React.FC<PersianDatePickerProps> = ({
  value,
  onChange,
  label,
  placeholder = '۱۴۰۵/۰۲/۲۰',
  className = '',
  required = false,
  theme: propTheme,
  helperText
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [showGregorian, setShowGregorian] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  // Detect current theme if not explicitly passed
  const [currentTheme, setCurrentTheme] = useState<'dark' | 'light'>(propTheme || 'dark');

  useEffect(() => {
    if (propTheme) {
      setCurrentTheme(propTheme);
      return;
    }
    const checkTheme = () => {
      const isLight = document.documentElement.classList.contains('theme-light') ||
                      document.body.classList.contains('theme-light');
      setCurrentTheme(isLight ? 'light' : 'dark');
    };
    checkTheme();
    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    return () => observer.disconnect();
  }, [propTheme]);

  // Digits to Persian
  const toPersian = (n: number | string) => {
    const pDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return String(n).replace(/\d/g, d => pDigits[parseInt(d, 10)]);
  };

  // Parse initial Jalali date or fallback to current today in Jalali
  const getInitialJalali = () => {
    if (value && value.includes('/')) {
      const parts = value.split('/').map(Number);
      if (parts.length === 3 && parts[0] > 1300) {
        return { year: parts[0], month: parts[1], day: parts[2] };
      }
    } else if (value && value.includes('-')) {
      const parts = value.split('-').map(Number);
      if (parts.length === 3) {
        const j = gregorianToJalali(parts[0], parts[1], parts[2]);
        return { year: j.jy, month: j.jm, day: j.jd };
      }
    }
    const today = new Date();
    const j = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    return { year: j.jy, month: j.jm, day: j.jd };
  };

  const currentToday = (() => {
    const today = new Date();
    const j = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    return { year: j.jy, month: j.jm, day: j.jd };
  })();

  const initial = getInitialJalali();
  const [viewYear, setViewYear] = useState<number>(initial.year);
  const [viewMonth, setViewMonth] = useState<number>(initial.month);
  const [selectedJalali, setSelectedJalali] = useState<string>(
    value ? (value.includes('/') ? value : formatToPersianDateShort(value)) : ''
  );

  useEffect(() => {
    if (value) {
      const formatted = value.includes('/') ? value : formatToPersianDateShort(value);
      setSelectedJalali(formatted);
      const parts = formatted.split('/').map(Number);
      if (parts.length === 3) {
        setViewYear(parts[0]);
        setViewMonth(parts[1]);
      }
    }
  }, [value]);

  // Close calendar popup when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };
    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  // Days in Jalali months: Months 1-6 have 31 days, 7-11 have 30 days, month 12 has 29 (or 30 in leap year)
  const isJalaliLeapYear = (jy: number) => {
    const r = jy % 33;
    return [1, 5, 9, 13, 17, 22, 26, 30].includes(r);
  };

  const getDaysInMonth = (year: number, month: number) => {
    if (month <= 6) return 31;
    if (month <= 11) return 30;
    return isJalaliLeapYear(year) ? 30 : 29;
  };

  // Get starting day of week for the 1st of Jalali month
  // Persian week: شنبه (0), یکشنبه (1), دوشنبه (2), سه‌شنبه (3), چهارشنبه (4), پنجشنبه (5), جمعه (6)
  const getFirstDayOfWeek = (year: number, month: number) => {
    const g = jalaliToGregorian(year, month, 1);
    const dateObj = new Date(g.gy, g.gm - 1, g.gd);
    const day = dateObj.getDay(); // 0 is Sunday, 6 is Saturday
    return (day + 1) % 7;
  };

  const daysInCurrentMonth = getDaysInMonth(viewYear, viewMonth);
  const firstDayOffset = getFirstDayOfWeek(viewYear, viewMonth);

  // Previous month info for leading filler days
  const prevMonth = viewMonth === 1 ? 12 : viewMonth - 1;
  const prevYear = viewMonth === 1 ? viewYear - 1 : viewYear;
  const daysInPrevMonth = getDaysInMonth(prevYear, prevMonth);

  const handlePrevMonth = () => {
    if (viewMonth === 1) {
      setViewYear(viewYear - 1);
      setViewMonth(12);
    } else {
      setViewMonth(viewMonth - 1);
    }
  };

  const handleNextMonth = () => {
    if (viewMonth === 12) {
      setViewYear(viewYear + 1);
      setViewMonth(1);
    } else {
      setViewMonth(viewMonth + 1);
    }
  };

  const handleSelectDay = (day: number, year = viewYear, month = viewMonth) => {
    const mm = month < 10 ? `0${month}` : `${month}`;
    const dd = day < 10 ? `0${day}` : `${day}`;
    const jalaliStr = `${year}/${mm}/${dd}`;
    setSelectedJalali(jalaliStr);

    const g = jalaliToGregorian(year, month, day);
    const gmm = g.gm < 10 ? `0${g.gm}` : `${g.gm}`;
    const gdd = g.gd < 10 ? `0${g.gd}` : `${g.gd}`;
    const gregStr = `${g.gy}-${gmm}-${gdd}`;

    onChange(jalaliStr, gregStr);
    setIsOpen(false);
  };

  const handleJumpToToday = () => {
    setViewYear(currentToday.year);
    setViewMonth(currentToday.month);
    handleSelectDay(currentToday.day, currentToday.year, currentToday.month);
  };

  // Convert selected Jalali to Gregorian formatted display
  const getGregorianEquivalent = () => {
    if (!selectedJalali) return '';
    const parts = selectedJalali.split('/').map(Number);
    if (parts.length === 3) {
      const g = jalaliToGregorian(parts[0], parts[1], parts[2]);
      const gDate = new Date(g.gy, g.gm - 1, g.gd);
      const enMonth = gDate.toLocaleString('fa-IR', { month: 'long' });
      return `${g.gd} ${enMonth} ${g.gy}`;
    }
    return '';
  };

  const isLight = currentTheme === 'light';

  // Total grid cells: 7 columns x 5 or 6 rows (35 or 42 cells)
  const totalDaysDisplayed = firstDayOffset + daysInCurrentMonth;
  const trailingDaysNeeded = (7 - (totalDaysDisplayed % 7)) % 7;

  return (
    <div className={`relative ${className}`} ref={containerRef} dir="rtl">
      {label && (
        <label className={`block font-semibold mb-1 text-xs flex items-center justify-between ${
          isLight ? 'text-slate-700' : 'text-slate-300'
        }`}>
          <span>{label}</span>
          {helperText && <span className="text-[10px] text-indigo-400 font-medium">{helperText}</span>}
        </label>
      )}

      {/* Main Trigger Input Button */}
      <div
        onClick={() => setIsOpen(!isOpen)}
        className={`w-full px-3.5 py-2.5 rounded-xl border flex items-center justify-between cursor-pointer transition-all duration-200 text-xs shadow-xs select-none ${
          isLight
            ? 'bg-white border-slate-200 hover:border-indigo-400 text-slate-800 shadow-sm'
            : 'bg-[#12131f] border-slate-700/80 hover:border-indigo-500/80 text-white shadow-md'
        }`}
      >
        <div className="flex items-center gap-2.5">
          <Calendar className={`w-4 h-4 shrink-0 ${isLight ? 'text-indigo-600' : 'text-indigo-400'}`} />
          <span className={`font-mono font-bold tracking-wide ${selectedJalali ? (isLight ? 'text-slate-900' : 'text-white') : 'text-slate-400'}`}>
            {selectedJalali ? toPersian(selectedJalali) : placeholder}
          </span>
        </div>
        <div className="flex items-center gap-1.5">
          {selectedJalali && (
            <span className={`text-[10px] px-2 py-0.5 rounded-md font-bold ${
              isLight ? 'bg-indigo-50 text-indigo-700' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
            }`}>
              شمسی
            </span>
          )}
          <span className={`text-[10px] transition-transform duration-200 ${isOpen ? 'rotate-180' : ''} ${
            isLight ? 'text-slate-400' : 'text-slate-500'
          }`}>
            ▼
          </span>
        </div>
      </div>

      {/* DatePicker Popup Dropdown matching uploaded image */}
      {isOpen && (
        <div
          className={`absolute z-50 mt-2 right-0 w-72 sm:w-80 rounded-2xl p-4 shadow-2xl transition-all animate-fadeIn select-none ${
            isLight
              ? 'bg-white border border-slate-100 text-slate-900 shadow-xl shadow-slate-300/40'
              : 'bg-[#181926] border border-slate-800 text-white shadow-2xl shadow-black/80'
          }`}
          style={{
            minWidth: '290px',
            fontFamily: "'Vazirmatn', inherit"
          }}
        >
          {/* Top Navigation Row: Chevrons & Month / Year Center */}
          <div className="flex items-center justify-between px-1 pb-3 mb-2">
            {/* Prev month button (left chevron < in RTL) */}
            <button
              type="button"
              onClick={handlePrevMonth}
              className={`w-7 h-7 rounded-lg flex items-center justify-center transition-colors cursor-pointer ${
                isLight ? 'hover:bg-slate-100 text-slate-500' : 'hover:bg-slate-800 text-slate-400 hover:text-white'
              }`}
              title="ماه قبل"
            >
              <ChevronRight className="w-4 h-4" />
            </button>

            {/* Year & Month title like image: 1405 اردیبهشت */}
            <div className="flex items-center gap-2 text-sm font-bold">
              <span className={`font-mono text-base font-extrabold ${
                isLight ? 'text-[#4f46e5]' : 'text-[#818cf8]'
              }`}>
                {toPersian(viewYear)}
              </span>
              <span className={isLight ? 'text-slate-800' : 'text-white'}>
                {PERSIAN_MONTHS[viewMonth - 1]}
              </span>
            </div>

            {/* Next month button (right chevron > in RTL) */}
            <button
              type="button"
              onClick={handleNextMonth}
              className={`w-7 h-7 rounded-lg flex items-center justify-center transition-colors cursor-pointer ${
                isLight ? 'hover:bg-slate-100 text-slate-500' : 'hover:bg-slate-800 text-slate-400 hover:text-white'
              }`}
              title="ماه بعد"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
          </div>

          {/* Weekday Labels Header: ش ی د س چ پ ج */}
          <div className="grid grid-cols-7 gap-1 text-center font-bold text-xs mb-2">
            {['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map((dayName, idx) => (
              <span
                key={dayName}
                className={`${
                  idx === 6
                    ? (isLight ? 'text-rose-500' : 'text-rose-400')
                    : (isLight ? 'text-slate-400' : 'text-slate-400')
                }`}
              >
                {dayName}
              </span>
            ))}
          </div>

          {/* Days Grid (7 columns) */}
          <div className="grid grid-cols-7 gap-1.5 text-center">
            {/* Trailing days from previous month (Dimmed/faded numbers like image) */}
            {Array.from({ length: firstDayOffset }).map((_, idx) => {
              const dayNum = daysInPrevMonth - firstDayOffset + idx + 1;
              return (
                <button
                  key={`prev-${idx}`}
                  type="button"
                  onClick={() => handleSelectDay(dayNum, prevYear, prevMonth)}
                  className={`h-8 w-8 mx-auto rounded-xl flex items-center justify-center font-mono text-xs transition-opacity cursor-pointer ${
                    isLight ? 'text-slate-300 opacity-50 hover:opacity-100' : 'text-slate-600 opacity-40 hover:opacity-100'
                  }`}
                  title={`ماه قبل - ${dayNum}`}
                >
                  {toPersian(dayNum)}
                </button>
              );
            })}

            {/* Current Month Days */}
            {Array.from({ length: daysInCurrentMonth }).map((_, idx) => {
              const day = idx + 1;
              const mm = viewMonth < 10 ? `0${viewMonth}` : `${viewMonth}`;
              const dd = day < 10 ? `0${day}` : `${day}`;
              const dateKey = `${viewYear}/${mm}/${dd}`;
              const isSelected = selectedJalali === dateKey;
              const isToday =
                currentToday.year === viewYear &&
                currentToday.month === viewMonth &&
                currentToday.day === day;
              const isFriday = (firstDayOffset + idx) % 7 === 6;

              return (
                <button
                  key={`cur-${day}`}
                  type="button"
                  onClick={() => handleSelectDay(day)}
                  className={`h-8 w-8 mx-auto rounded-xl flex items-center justify-center font-mono text-xs font-bold transition-all duration-150 cursor-pointer ${
                    isSelected
                      ? 'bg-[#6366f1] text-white font-black shadow-lg shadow-indigo-500/35 scale-105 ring-2 ring-indigo-400/40'
                      : isToday
                      ? isLight
                        ? 'border-2 border-[#6366f1] text-[#4f46e5] bg-indigo-50/60'
                        : 'border-2 border-[#6366f1] text-indigo-300 bg-[#6366f1]/15'
                      : isFriday
                      ? isLight
                        ? 'text-rose-600 hover:bg-slate-100'
                        : 'text-rose-400 hover:bg-slate-800'
                      : isLight
                      ? 'text-slate-700 hover:bg-slate-100'
                      : 'text-slate-200 hover:bg-slate-800'
                  }`}
                >
                  {toPersian(day)}
                </button>
              );
            })}

            {/* Leading days from next month (Dimmed/faded numbers) */}
            {Array.from({ length: trailingDaysNeeded }).map((_, idx) => {
              const dayNum = idx + 1;
              const nextMonth = viewMonth === 12 ? 1 : viewMonth + 1;
              const nextYear = viewMonth === 12 ? viewYear + 1 : viewYear;
              return (
                <button
                  key={`next-${idx}`}
                  type="button"
                  onClick={() => handleSelectDay(dayNum, nextYear, nextMonth)}
                  className={`h-8 w-8 mx-auto rounded-xl flex items-center justify-center font-mono text-xs transition-opacity cursor-pointer ${
                    isLight ? 'text-slate-300 opacity-50 hover:opacity-100' : 'text-slate-600 opacity-40 hover:opacity-100'
                  }`}
                  title={`ماه بعد - ${dayNum}`}
                >
                  {toPersian(dayNum)}
                </button>
              );
            })}
          </div>

          {/* Footer Bar: "میلادی" on left & "امروز" rounded button on right (Exact image match) */}
          <div className={`mt-4 pt-3 border-t flex items-center justify-between text-xs ${
            isLight ? 'border-slate-100' : 'border-slate-800'
          }`}>
            {/* Left: Gregorian date toggle/text */}
            <button
              type="button"
              onClick={() => setShowGregorian(!showGregorian)}
              className={`font-medium transition-colors cursor-pointer text-xs ${
                isLight ? 'text-slate-400 hover:text-indigo-600' : 'text-slate-400 hover:text-indigo-300'
              }`}
              title="مشاهده معادل میلادی تاریخ انتخاب شده"
            >
              {showGregorian && selectedJalali ? (
                <span className="font-mono text-[11px] text-indigo-400">
                  {getGregorianEquivalent()}
                </span>
              ) : (
                'میلادی'
              )}
            </button>

            {/* Right: "امروز" Pill Button */}
            <button
              type="button"
              onClick={handleJumpToToday}
              className={`px-4 py-1.5 rounded-xl font-bold text-xs transition-all cursor-pointer shadow-xs ${
                isLight
                  ? 'bg-indigo-50 hover:bg-indigo-100 text-[#4f46e5]'
                  : 'bg-[#232438] hover:bg-[#2e3048] text-[#818cf8]'
              }`}
            >
              امروز
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
