import React from 'react';
import { RoomCleanlinessRecord, DormRoom } from '../types';
import { Printer, X, Sparkles, CheckCircle2, AlertTriangle, Building2, Calendar, FileText } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface CleanlinessPrintModalProps {
  records: RoomCleanlinessRecord[];
  rooms: DormRoom[];
  selectedDate: string;
  onClose: () => void;
}

export const CleanlinessPrintModal: React.FC<CleanlinessPrintModalProps> = ({
  records,
  rooms,
  selectedDate,
  onClose
}) => {
  const filtered = records.filter(r => r.inspectionDate === selectedDate);
  const total = filtered.length;
  const excellent = filtered.filter(r => r.grade === 'عالی').length;
  const good = filtered.filter(r => r.grade === 'خوب').length;
  const average = filtered.filter(r => r.grade === 'متوسط').length;
  const poor = filtered.filter(r => r.grade === 'ضعیف').length;
  const hygieneRate = total > 0 ? Math.round(((excellent + good) / total) * 100) : 100;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-white text-slate-900 rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 my-8 print:p-0 print:m-0 print:border-none print:shadow-none shadow-2xl relative max-h-[92vh] overflow-y-auto">
        
        {/* Screen Action Bar (Hidden in Print) */}
        <div className="flex items-center justify-between border-b border-slate-200 pb-4 print:hidden">
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-indigo-50 text-indigo-700">
              <Printer className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-base font-bold text-slate-900">پیش‌نمایش چاپ رسمی کارنامه نظافت شبانه خوابگاه</h3>
              <span className="text-xs text-slate-500">تاریخ سرشماری: {selectedDate}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => window.print()}
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <Printer className="w-4 h-4" />
              <span>چاپ کارنامه (Print)</span>
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
                <span className="text-[11px] text-slate-500">کارنامه و صورتجلسه رسمی ارزیابی شبانه نظافت و بهداشت اتاق‌ها</span>
              </div>
            </div>

            <div className="text-left text-xs space-y-1 font-mono text-slate-600">
              <div>تاریخ بازرسی: <strong className="text-slate-900">{selectedDate}</strong></div>
              <div>نوبت شیفت: <strong className="text-slate-900">شیفت شبانه (۲۲ الی ۰۶)</strong></div>
              <div>شماره سند: <strong className="text-indigo-600">CLN-{selectedDate.replace(/\//g, '')}</strong></div>
            </div>
          </div>

          {/* Statistical Summary Box */}
          <div className="grid grid-cols-5 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs">
            <div>
              <span className="text-slate-500 block text-[11px]">تعداد اتاق‌های بازرسی‌شده</span>
              <strong className="text-slate-900 text-base font-bold font-mono">{toPersianDigits(total)}</strong>
            </div>
            <div>
              <span className="text-emerald-700 block text-[11px]">نظافت عالی (درجه ۱)</span>
              <strong className="text-emerald-700 text-base font-bold font-mono">{toPersianDigits(excellent)}</strong>
            </div>
            <div>
              <span className="text-blue-700 block text-[11px]">نظافت خوب (درجه ۲)</span>
              <strong className="text-blue-700 text-base font-bold font-mono">{toPersianDigits(good)}</strong>
            </div>
            <div>
              <span className="text-amber-700 block text-[11px]">متوسط (درجه ۳)</span>
              <strong className="text-amber-700 text-base font-bold font-mono">{toPersianDigits(average)}</strong>
            </div>
            <div>
              <span className="text-rose-700 block text-[11px]">ضعیف و اخطار (درجه ۴)</span>
              <strong className="text-rose-700 text-base font-bold font-mono">{toPersianDigits(poor)}</strong>
            </div>
          </div>

          {/* Detailed Table */}
          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs border border-slate-300">
              <thead>
                <tr className="bg-slate-100 text-slate-800 border-b border-slate-300">
                  <th className="p-2.5 border-l border-slate-300 text-center w-12">ردیف</th>
                  <th className="p-2.5 border-l border-slate-300">اتاق و طبقه</th>
                  <th className="p-2.5 border-l border-slate-300 text-center">سطح ارزیابی نظافت</th>
                  <th className="p-2.5 border-l border-slate-300">سرپرست بازرس شیفت</th>
                  <th className="p-2.5">توضیحات و تذکرات ثبت‌شده برای ساکنین</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {filtered.map((rec, index) => (
                  <tr key={rec.id} className="hover:bg-slate-50">
                    <td className="p-2.5 border-l border-slate-300 text-center font-mono">{toPersianDigits(index + 1)}</td>
                    <td className="p-2.5 border-l border-slate-300 font-bold">
                      اتاق {rec.roomNumber} ({rec.floor})
                    </td>
                    <td className="p-2.5 border-l border-slate-300 text-center font-bold">
                      <span className={`px-2.5 py-1 rounded-md text-[11px] font-bold ${
                        rec.grade === 'عالی' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
                        rec.grade === 'خوب' ? 'bg-blue-100 text-blue-800 border border-blue-300' :
                        rec.grade === 'متوسط' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                        'bg-rose-100 text-rose-800 border border-rose-300'
                      }`}>
                        {rec.grade}
                      </span>
                    </td>
                    <td className="p-2.5 border-l border-slate-300 text-slate-700">
                      {rec.supervisorName}
                    </td>
                    <td className="p-2.5 text-slate-600 text-[11px] leading-relaxed">
                      {rec.notes || 'مورد خاصی گزارش نشده و وضعیت عمومی قابل قبول است.'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Signatures */}
          <div className="pt-8 border-t border-slate-300 grid grid-cols-2 gap-8 text-xs text-center">
            <div className="space-y-12">
              <span className="font-bold text-slate-800 block">امضای سرپرست شیفت شبانه خوابگاه:</span>
              <div className="border-b border-dashed border-slate-400 w-48 mx-auto" />
              <span className="text-[11px] text-slate-500 block">نام و نام خانوادگی / ساعت تحویل گزارش</span>
            </div>

            <div className="space-y-12">
              <span className="font-bold text-slate-800 block">امضا و تأیید مدیریت امور دانشجویی و خوابگاه‌ها:</span>
              <div className="border-b border-dashed border-slate-400 w-48 mx-auto" />
              <span className="text-[11px] text-slate-500 block">دکتر علیرضا حسینی / مهر دانشگاه</span>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};
