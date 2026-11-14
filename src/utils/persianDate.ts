/**
 * Persian (Jalali / Shamsi) Calendar helper functions
 */

// Converts Gregorian date string (YYYY-MM-DD) or Date to Jalali { year, month, day }
export function gregorianToJalali(gy: number, gm: number, gd: number): { jy: number; jm: number; jd: number } {
  const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
  let jy = (gy <= 1600) ? 0 : 979;
  gy -= (gy <= 1600) ? 621 : 1600;
  const gy2 = (gm > 2) ? (gy + 1) : gy;
  let days = (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
    + Math.floor((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
  jy += 33 * Math.floor(days / 12053);
  days %= 12053;
  jy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) {
    jy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }
  const jm = (days < 186) ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
  const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
  return { jy, jm, jd };
}

// Converts Jalali { jy, jm, jd } to Gregorian { gy, gm, gd }
export function jalaliToGregorian(jy: number, jm: number, jd: number): { gy: number; gm: number; gd: number } {
  let gy = (jy <= 979) ? 621 : 1600;
  jy -= (jy <= 979) ? 0 : 979;
  let days = (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4)
    + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
  gy += 400 * Math.floor(days / 146097);
  days %= 146097;
  if (days > 36524) {
    gy += 100 * Math.floor(--days / 36524);
    days %= 36524;
    if (days >= 365) days++;
  }
  gy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) {
    gy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }
  const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
  let gm = 0;
  while (gm < 13 && days >= sal_a[gm]) {
    days -= sal_a[gm];
    gm++;
  }
  const gd = days + 1;
  return { gy, gm, gd };
}

export const PERSIAN_MONTHS = [
  'فروردین', 'اردیبهشت', 'خرداد',
  'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر',
  'دی', 'بهمن', 'اسفند'
];

export const PERSIAN_WEEK_DAYS = [
  'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'
];

export function parseDateStringToGregorian(dateStr: string): { gy: number; gm: number; gd: number } {
  const parts = dateStr.split('-').map(Number);
  if (parts.length === 3 && !isNaN(parts[0]) && !isNaN(parts[1]) && !isNaN(parts[2])) {
    return { gy: parts[0], gm: parts[1], gd: parts[2] };
  }
  const now = new Date();
  return { gy: now.getFullYear(), gm: now.getMonth() + 1, gd: now.getDate() };
}

export function formatToPersianDateText(gregorianDateStr: string): string {
  const { gy, gm, gd } = parseDateStringToGregorian(gregorianDateStr);
  const { jy, jm, jd } = gregorianToJalali(gy, gm, gd);
  const monthName = PERSIAN_MONTHS[jm - 1] || '';
  
  // Calculate day of week
  const dateObj = new Date(gy, gm - 1, gd);
  const dayOfWeekIndex = dateObj.getDay(); // 0 is Sunday
  const dayName = PERSIAN_WEEK_DAYS[dayOfWeekIndex] || '';

  return `${dayName}، ${jd} ${monthName} ${jy}`;
}

export function formatToPersianDateShort(gregorianDateStr: string): string {
  const { gy, gm, gd } = parseDateStringToGregorian(gregorianDateStr);
  const { jy, jm, jd } = gregorianToJalali(gy, gm, gd);
  const mm = jm < 10 ? `0${jm}` : `${jm}`;
  const dd = jd < 10 ? `0${jd}` : `${jd}`;
  return `${jy}/${mm}/${dd}`;
}

export function jalaliToDateString(jy: number, jm: number, jd: number): string {
  const { gy, gm, gd } = jalaliToGregorian(jy, jm, jd);
  const mm = gm < 10 ? `0${gm}` : `${gm}`;
  const dd = gd < 10 ? `0${gd}` : `${gd}`;
  return `${gy}-${mm}-${dd}`;
}

export function addDaysToDateString(dateStr: string, daysToAdd: number): string {
  const { gy, gm, gd } = parseDateStringToGregorian(dateStr);
  const d = new Date(gy, gm - 1, gd);
  d.setDate(d.getDate() + daysToAdd);
  const nGy = d.getFullYear();
  const nGm = d.getMonth() + 1;
  const nGd = d.getDate();
  const mm = nGm < 10 ? `0${nGm}` : `${nGm}`;
  const dd = nGd < 10 ? `0${nGd}` : `${nGd}`;
  return `${nGy}-${mm}-${dd}`;
}

export function addDaysToJalali(jalaliStr: string, daysToAdd: number): string {
  try {
    const parts = jalaliStr.split('/').map(Number);
    if (parts.length === 3 && parts[0] > 1300) {
      const g = jalaliToGregorian(parts[0], parts[1], parts[2]);
      const d = new Date(g.gy, g.gm - 1, g.gd);
      d.setDate(d.getDate() + daysToAdd);
      const resJ = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
      const mm = resJ.jm < 10 ? `0${resJ.jm}` : `${resJ.jm}`;
      const dd = resJ.jd < 10 ? `0${resJ.jd}` : `${resJ.jd}`;
      return `${resJ.jy}/${mm}/${dd}`;
    }
  } catch (e) {}
  return jalaliStr;
}

export function getTodayJalali(): string {
  const d = new Date();
  const j = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
  const mm = j.jm < 10 ? `0${j.jm}` : `${j.jm}`;
  const dd = j.jd < 10 ? `0${j.jd}` : `${j.jd}`;
  return `${j.jy}/${mm}/${dd}`;
}
