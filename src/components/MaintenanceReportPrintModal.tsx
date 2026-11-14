import React from 'react';
import { MaintenanceReport } from '../types';
import { Printer, X, Wrench, DollarSign, Building2, Calendar, FileText, CheckCircle2, AlertTriangle } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface MaintenanceReportPrintModalProps {
  reports: MaintenanceReport[];
  periodTitle: string; // e.g. "گزارش کلی" | "گزارش هفتگی (۷ روز اخیر)" | "گزارش ماهانه (۳۰ روز اخیر)"
  onClose: () => void;
}

export const MaintenanceReportPrintModal: React.FC<MaintenanceReportPrintModalProps> = ({
  reports,
  periodTitle,
  onClose
}) => {
  const totalReports = reports.length;
  const resolvedCount = reports.filter(r => r.status.includes('رفع‌شده')).length;
  const pendingCount = reports.filter(r => r.status === 'در انتظار اقدام' || r.status === 'در حال تعمیر').length;
  const totalCost = reports.reduce((acc, r) => acc + (r.resolutionCost || 0), 0);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-white text-slate-900 rounded-3xl max-w-5xl w-full p-6 sm:p-8 space-y-6 my-8 print:p-0 print:m-0 print:border-none print:shadow-none shadow-2xl relative max-h-[92vh] overflow-y-auto">
        
        {/* Screen Action Bar (Hidden in Print) */}
        <div className="flex items-center justify-between border-b border-slate-200 pb-4 print:hidden">
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-amber-50 text-amber-700">
              <Wrench className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-base font-bold text-slate-900">
                پیش‌نمایش چاپ رسمی گزارش تعمیرات، خرابی‌ها و اسناد مالی خوابگاه
              </h3>
              <span className="text-xs text-slate-500">دوره پایش: {periodTitle}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => window.print()}
              className="px-4 py-2 bg-gradient-to-r from-amber-600 to-indigo-600 hover:from-amber-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <Printer className="w-4 h-4" />
              <span>چاپ گزارش مالی و فاکتورها (Print A4)</span>
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
                <h2 className="text-xs font-bold text-slate-700">مدیریت امور رفاهی و خدمات دانشجویی - سامانه صمد</h2>
                <span className="text-[11px] text-slate-500">
                  صورتجلسه و گزارش جامع تعمیرات، تعویض تجهیزات و فاکتورهای مالی خوابگاه ({periodTitle})
                </span>
              </div>
            </div>

            <div className="text-left text-xs space-y-1 font-mono text-slate-600">
              <div>تاریخ گزارش: <strong className="text-slate-900">۱۴۰۳/۰۷/۱۴</strong></div>
              <div>دوره گزارش: <strong className="text-slate-900">{periodTitle}</strong></div>
              <div>شماره سند مالی: <strong className="text-amber-700">MNT-FIN-1403</strong></div>
            </div>
          </div>

          {/* Statistical Financial Box */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs">
            <div>
              <span className="text-slate-500 block text-[11px]">کل موارد خرابی و نقص</span>
              <strong className="text-slate-900 text-base font-bold font-mono">{toPersianDigits(totalReports)} مورد</strong>
            </div>
            <div>
              <span className="text-emerald-700 block text-[11px]">موارد رفع‌شده / فاکتور شده</span>
              <strong className="text-emerald-700 text-base font-bold font-mono">{toPersianDigits(resolvedCount)} مورد</strong>
            </div>
            <div>
              <span className="text-amber-700 block text-[11px]">در انتظار اقدام و تعمیر</span>
              <strong className="text-amber-700 text-base font-bold font-mono">{toPersianDigits(pendingCount)} مورد</strong>
            </div>
            <div>
              <span className="text-slate-700 block text-[11px]">مجموع کل هزینه‌ها و فاکتورها</span>
              <strong className="text-indigo-800 text-base font-bold font-mono">
                {toPersianDigits(totalCost.toLocaleString('fa-IR'))} تومان
              </strong>
            </div>
          </div>

          {/* Reports & Invoices Table */}
          <div className="space-y-2">
            <h4 className="text-xs font-bold text-slate-900">
              ریز موارد تعمیری، اقلام آسیب‌دیده و مشخصات فاکتورهای صادره:
            </h4>

            <table className="w-full text-right text-xs border border-slate-300 rounded-lg overflow-hidden">
              <thead className="bg-slate-100 text-slate-700 border-b border-slate-300 font-bold">
                <tr>
                  <th className="p-2.5 text-center w-12">ردیف</th>
                  <th className="p-2.5">اتاق و طبقه</th>
                  <th className="p-2.5">تجهیز آسیب‌دیده</th>
                  <th className="p-2.5">سرپرست گزارش‌دهنده و زمان</th>
                  <th className="p-2.5">نوع اقدام و رفع عیب مدیر</th>
                  <th className="p-2.5">شماره و تاریخ فاکتور</th>
                  <th className="p-2.5">پیمانکار / فروشگاه</th>
                  <th className="p-2.5 text-center">مبلغ فاکتور (تومان)</th>
                  <th className="p-2.5 text-center">وضعیت نهایی</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {reports.map((rep, idx) => {
                  const isResolved = rep.status.includes('رفع‌شده');

                  return (
                    <tr key={rep.id} className={isResolved ? 'hover:bg-slate-50' : 'bg-amber-50/40'}>
                      <td className="p-2.5 text-center font-mono">{toPersianDigits(idx + 1)}</td>
                      <td className="p-2.5 font-bold">
                        <span>اتاق {rep.roomNumber}</span>
                        <span className="text-slate-500 text-[10px] mr-1 block font-normal">{rep.floor}</span>
                      </td>
                      <td className="p-2.5 font-semibold text-slate-900">
                        <span>{rep.equipmentName}</span>
                        <span className="text-slate-500 text-[10px] block mt-0.5 line-clamp-1">{rep.description}</span>
                      </td>
                      <td className="p-2.5 text-[11px] text-slate-600">
                        <div>{rep.reportedBySupervisorName}</div>
                        <div className="text-[10px] text-slate-400 font-mono mt-0.5">{rep.reportedAt}</div>
                      </td>
                      <td className="p-2.5 text-[11px]">
                        {isResolved ? (
                          <div>
                            <strong className="text-emerald-700">{rep.resolutionAction}</strong>
                            <div className="text-[10px] text-slate-500 mt-0.5">{rep.resolutionNotes}</div>
                          </div>
                        ) : (
                          <span className="text-amber-700">در حال بررسی و برآورد هزینه</span>
                        )}
                      </td>
                      <td className="p-2.5 text-[11px] font-mono">
                        {rep.invoiceNumber ? (
                          <div>
                            <span className="font-bold text-indigo-700">{rep.invoiceNumber}</span>
                            <div className="text-[10px] text-slate-500">{rep.invoiceDate}</div>
                          </div>
                        ) : (
                          <span className="text-slate-400">فاقد فاکتور</span>
                        )}
                      </td>
                      <td className="p-2.5 text-[11px] text-slate-700">
                        {rep.contractorOrStoreName || '—'}
                      </td>
                      <td className="p-2.5 text-center font-mono font-bold text-sm">
                        {rep.resolutionCost ? toPersianDigits(rep.resolutionCost.toLocaleString('fa-IR')) : '۰'}
                      </td>
                      <td className="p-2.5 text-center">
                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                          isResolved
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                        }`}>
                          {rep.status}
                        </span>
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
              <span className="text-slate-600 font-bold block">امور تاسیسات و تعمیرات خوابگاه</span>
              <div className="text-[11px] text-slate-500 font-serif">امضاء و تایید فنی</div>
            </div>

            <div className="space-y-12">
              <span className="text-slate-600 font-bold block">امور مالی و حسابداری دانشگاه</span>
              <div className="text-[11px] text-slate-500 font-serif">تایید پرداخت و ثبت اسناد فاکتور</div>
            </div>

            <div className="space-y-12">
              <span className="text-slate-900 font-bold block">معاونت دانشجویی و امور رفاهی</span>
              <div className="text-[11px] text-slate-500 font-serif">مهر و امضای نهایی</div>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};
