import React from 'react';
import { RoomCleanlinessRanking, DormRoom } from '../types';
import { Printer, X, Sparkles, Trophy, Award, Building2, Calendar, FileText, CheckCircle2, AlertTriangle } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface WeeklyMonthlyCleanlinessPrintModalProps {
  rankings: RoomCleanlinessRanking[];
  periodTitle: string; // e.g. "عملکرد هفتگی (۷ روز اخیر)" | "عملکرد ماهانه (۳۰ روز اخیر)"
  periodType: 'weekly' | 'monthly';
  onClose: () => void;
}

export const WeeklyMonthlyCleanlinessPrintModal: React.FC<WeeklyMonthlyCleanlinessPrintModalProps> = ({
  rankings,
  periodTitle,
  periodType,
  onClose
}) => {
  const totalRooms = rankings.length;
  const avgScoreAll = totalRooms > 0
    ? (rankings.reduce((acc, r) => acc + r.averageScore, 0) / totalRooms).toFixed(2)
    : '0';

  const totalInspections = rankings.reduce((acc, r) => acc + r.totalInspections, 0);
  const excellentTotal = rankings.reduce((acc, r) => acc + r.gradeCounts.excellent, 0);
  const goodTotal = rankings.reduce((acc, r) => acc + r.gradeCounts.good, 0);
  const averageTotal = rankings.reduce((acc, r) => acc + r.gradeCounts.average, 0);
  const poorTotal = rankings.reduce((acc, r) => acc + r.gradeCounts.poor, 0);

  const bestRoom = rankings[0];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-white text-slate-900 rounded-3xl max-w-5xl w-full p-6 sm:p-8 space-y-6 my-8 print:p-0 print:m-0 print:border-none print:shadow-none shadow-2xl relative max-h-[92vh] overflow-y-auto">
        
        {/* Screen Action Bar (Hidden in Print) */}
        <div className="flex items-center justify-between border-b border-slate-200 pb-4 print:hidden">
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-cyan-50 text-cyan-700">
              <Trophy className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-base font-bold text-slate-900">
                پیش‌نمایش چاپ رسمی کارنامه رتبه‌بندی عملکرد نظافت خوابگاه‌ها
              </h3>
              <span className="text-xs text-slate-500">دوره گزارش: {periodTitle}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => window.print()}
              className="px-4 py-2 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <Printer className="w-4 h-4" />
              <span>چاپ گزارش (Print A4)</span>
            </button>
            <button
              type="button"
              onClick={onClose}
              className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
            >
              بستن
            </button>
          </div>
        </div>

        {/* ===================== PRINTABLE OFFICIAL DOCUMENT BODY ===================== */}
        <div className="border border-slate-300 rounded-2xl p-6 sm:p-8 space-y-6 bg-white text-slate-900 print:border-none print:p-2">
          
          {/* Official Letterhead */}
          <div className="flex items-center justify-between border-b-2 border-slate-900 pb-4">
            <div className="flex items-center gap-3">
              <div className="w-16 h-16 rounded-xl overflow-hidden border border-slate-300 p-1 flex items-center justify-center">
                <img
                  src="/src/assets/images/samad_campus_emblem_1790759744696.jpg"
                  alt="لوگو صمد"
                  className="w-full h-full object-contain"
                />
              </div>
              <div className="space-y-0.5">
                <h1 className="text-lg font-black tracking-tight text-slate-900">دانشگاه ملی مهارت</h1>
                <h2 className="text-xs font-bold text-slate-700">معاونت دانشجویی و امور خوابگاه‌ها - سامانه هوشمند صمد</h2>
                <span className="text-[11px] text-slate-500">
                  کارنامه رسمی تحلیل عملکرد و رتبه‌بندی کیفیت نظافت اتاق‌های خوابگاه ({periodTitle})
                </span>
              </div>
            </div>

            <div className="text-left text-xs space-y-1 font-mono text-slate-600">
              <div>تاریخ صدور: <strong className="text-slate-900">۱۴۰۳/۰۷/۱۴</strong></div>
              <div>بازه پایش: <strong className="text-slate-900">{periodTitle}</strong></div>
              <div>شماره سند: <strong className="text-indigo-600">RANK-{periodType.toUpperCase()}-1403</strong></div>
            </div>
          </div>

          {/* Statistical KPI Box */}
          <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs">
            <div>
              <span className="text-slate-500 block text-[10px]">تعداد اتاق‌های پایش‌شده</span>
              <strong className="text-slate-900 text-base font-bold font-mono">{toPersianDigits(totalRooms)} اتاق</strong>
            </div>
            <div>
              <span className="text-slate-500 block text-[10px]">میانگین نمره کل پردیس</span>
              <strong className="text-indigo-700 text-base font-bold font-mono">{toPersianDigits(avgScoreAll)} از ۴</strong>
            </div>
            <div>
              <span className="text-emerald-700 block text-[10px]">کل نمرات عالی</span>
              <strong className="text-emerald-700 text-base font-bold font-mono">{toPersianDigits(excellentTotal)} نوبت</strong>
            </div>
            <div>
              <span className="text-blue-700 block text-[10px]">کل نمرات خوب</span>
              <strong className="text-blue-700 text-base font-bold font-mono">{toPersianDigits(goodTotal)} نوبت</strong>
            </div>
            <div>
              <span className="text-rose-700 block text-[10px]">نمرات ضعیف (اخطار)</span>
              <strong className="text-rose-700 text-base font-bold font-mono">{toPersianDigits(poorTotal)} نوبت</strong>
            </div>
          </div>

          {/* Best Room Banner */}
          {bestRoom && (
            <div className="p-3 bg-gradient-to-r from-amber-50 to-emerald-50 border border-amber-300 rounded-xl flex items-center justify-between text-xs">
              <div className="flex items-center gap-2">
                <span className="text-xl">🏆</span>
                <div>
                  <span className="font-bold text-slate-900">
                    اتاق نمونه و برتر دوره: <strong className="text-emerald-800">اتاق {bestRoom.roomNumber} ({bestRoom.floor})</strong>
                  </span>
                  <p className="text-[10px] text-slate-600 mt-0.5">
                    با میانگین نمره {toPersianDigits(bestRoom.averageScore.toFixed(2))} از ۴ ({toPersianDigits(Math.round(bestRoom.scorePercentage ?? 0))}٪ کیفیت) و ثبت بیشترین نمرات عالی
                  </p>
                </div>
              </div>
              <span className="px-3 py-1 bg-amber-400 text-slate-950 font-bold rounded-lg text-xs">
                رتبه ۱ کل پردیس 🥇
              </span>
            </div>
          )}

          {/* Ranking Table: Sorted from Best to Worst */}
          <div className="space-y-2">
            <h4 className="text-xs font-bold text-slate-900">
              جدول رتبه‌بندی عملکرد اتاق‌ها (مرتب‌شده از بهترین به ضعیف‌ترین اتاق در طول دوره):
            </h4>

            <table className="w-full text-right text-xs border border-slate-300 rounded-lg overflow-hidden">
              <thead className="bg-slate-100 text-slate-700 border-b border-slate-300 font-bold">
                <tr>
                  <th className="p-2.5 text-center w-14">رتبه</th>
                  <th className="p-2.5">شماره اتاق و طبقه</th>
                  <th className="p-2.5 text-center">تعداد بازرسی</th>
                  <th className="p-2.5 text-center">عالی</th>
                  <th className="p-2.5 text-center">خوب</th>
                  <th className="p-2.5 text-center">متوسط</th>
                  <th className="p-2.5 text-center">ضعیف</th>
                  <th className="p-2.5 text-center">میانگین نمره (از ۴)</th>
                  <th className="p-2.5 text-center">درصد کیفیت</th>
                  <th className="p-2.5">وضعیت و ارزیابی نهایی</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {rankings.map((rankItem) => {
                  const isTop = rankItem.rank === 1;
                  const isSecond = rankItem.rank === 2;
                  const isThird = rankItem.rank === 3;
                  const isWarning = rankItem.averageScore < 2.5;

                  return (
                    <tr
                      key={rankItem.roomId}
                      className={
                        isTop
                          ? 'bg-amber-50/60 font-semibold'
                          : isWarning
                          ? 'bg-rose-50/50'
                          : 'hover:bg-slate-50'
                      }
                    >
                      <td className="p-2.5 text-center font-bold">
                        {isTop ? '🥇 ۱' : isSecond ? '🥈 ۲' : isThird ? '🥉 ۳' : toPersianDigits(rankItem.rank)}
                      </td>
                      <td className="p-2.5 font-bold">
                        <span>اتاق {rankItem.roomNumber}</span>
                        <span className="text-slate-500 text-[10px] mr-1.5 font-normal">({rankItem.floor})</span>
                      </td>
                      <td className="p-2.5 text-center font-mono">{toPersianDigits(rankItem.totalInspections)}</td>
                      <td className="p-2.5 text-center font-mono text-emerald-700 font-bold">
                        {toPersianDigits(rankItem.gradeCounts.excellent)}
                      </td>
                      <td className="p-2.5 text-center font-mono text-blue-700">
                        {toPersianDigits(rankItem.gradeCounts.good)}
                      </td>
                      <td className="p-2.5 text-center font-mono text-amber-700">
                        {toPersianDigits(rankItem.gradeCounts.average)}
                      </td>
                      <td className="p-2.5 text-center font-mono text-rose-700 font-bold">
                        {toPersianDigits(rankItem.gradeCounts.poor)}
                      </td>
                      <td className="p-2.5 text-center font-mono font-bold text-sm">
                        {toPersianDigits(rankItem.averageScore.toFixed(2))}
                      </td>
                      <td className="p-2.5 text-center font-mono font-bold text-indigo-700">
                        {toPersianDigits(Math.round(rankItem.scorePercentage ?? 0))}٪
                      </td>
                      <td className="p-2.5">
                        {isTop ? (
                          <span className="text-emerald-700 font-bold text-[11px]">اتاق برتر و نمونه پردیس ✓</span>
                        ) : isWarning ? (
                          <span className="text-rose-700 font-bold text-[11px]">نیازمند اخطار و بهبود نظافت ⚠️</span>
                        ) : (
                          <span className="text-slate-600 text-[11px]">مطلوب و مورد تایید</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Signatures Footer */}
          <div className="pt-8 border-t-2 border-slate-800 grid grid-cols-3 gap-6 text-center text-xs">
            <div className="space-y-12">
              <span className="text-slate-600 font-bold block">سرپرست ارشد شیفت شبانه</span>
              <div className="text-[11px] text-slate-500 font-serif">امضاء و تایید گزارش</div>
            </div>

            <div className="space-y-12">
              <span className="text-slate-600 font-bold block">کارشناس بهداشت و امور خوابگاه‌ها</span>
              <div className="text-[11px] text-slate-500 font-serif">امضاء و تایید</div>
            </div>

            <div className="space-y-12">
              <span className="text-slate-900 font-bold block">مدیریت امور دانشجویی و رفاهی دانشگاه</span>
              <div className="text-[11px] text-slate-500 font-serif">مهر و امضای ریاست امور خوابگاه‌ها</div>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};
