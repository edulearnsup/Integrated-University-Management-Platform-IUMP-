import React from 'react';
import { MaintenanceReport } from '../types';
import { Printer, X, CheckCircle2, Building2, Wrench, Receipt, ShieldCheck } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface SingleInvoiceModalProps {
  report: MaintenanceReport;
  onClose: () => void;
}

export const SingleInvoiceModal: React.FC<SingleInvoiceModalProps> = ({ report, onClose }) => {
  const handlePrint = () => {
    window.print();
  };

  const invoiceNumber = report.invoiceNumber || `INV-1403-${report.id}`;
  const cost = report.resolutionCost || 0;
  const isReplacement = report.resolutionAction === 'خرید و جایگزینی تجهیز نو';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-[#1e293b] text-white rounded-3xl max-w-2xl w-full border border-slate-700 shadow-2xl overflow-hidden my-8">
        {/* Modal Toolbar (hidden in print) */}
        <div className="print:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-slate-700">
          <div className="flex items-center gap-2">
            <Receipt className="w-5 h-5 text-indigo-400" />
            <span className="font-bold text-sm text-white">مشاهده صورتحساب رسمی و فاکتور مالی</span>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handlePrint}
              className="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>چاپ رسمی صورتحساب (A4)</span>
            </button>
            <button
              type="button"
              onClick={onClose}
              className="text-slate-400 hover:text-white p-1 text-base cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Printable Official Invoice Body */}
        <div className="p-6 sm:p-8 space-y-6 text-slate-800 bg-white print:p-0 print:m-0 print:text-black">
          {/* Invoice Header */}
          <div className="flex items-center justify-between border-b-2 border-slate-900 pb-4">
            <div className="flex items-center gap-3">
              <div className="w-14 h-14 rounded-xl overflow-hidden border border-teal-600/40 p-0.5 shrink-0 bg-teal-50">
                <img
                  src="/src/assets/images/nishapur_seal_emblem.jpg"
                  alt="نشان دانشگاه ملی مهارت واحد پسران نیشابور"
                  className="w-full h-full object-cover rounded-lg"
                />
              </div>
              <div>
                <h2 className="text-base sm:text-lg font-black text-slate-900">
                  دانشگاه ملی مهارت واحد پسران نیشابور
                </h2>
                <p className="text-xs text-teal-700 font-bold">
                  اداره امور خوابگاه‌ها و خدمات رفاهی صمد (پردیس پسران)
                </p>
                <p className="text-[10px] text-slate-500">
                  صورت‌جلسه رسمی رفع عیب تاسیسات و سند هزینه فاکتور مالی
                </p>
              </div>
            </div>

            <div className="text-left font-mono text-xs space-y-1 text-slate-700">
              <div>
                <span className="text-[11px] font-sans text-slate-500">شماره فاکتور: </span>
                <strong className="text-indigo-700 font-bold">{invoiceNumber}</strong>
              </div>
              <div>
                <span className="text-[11px] font-sans text-slate-500">کد رهگیری: </span>
                <strong>#{report.id}</strong>
              </div>
              <div>
                <span className="text-[11px] font-sans text-slate-500">تاریخ رفع عیب: </span>
                <strong>{toPersianDigits(report.resolvedAt || report.reportedAt)}</strong>
              </div>
            </div>
          </div>

          {/* Location & Damage Overview Table */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
            <div>
              <span className="text-slate-500 block text-[10px]">محل استقرار:</span>
              <strong className="text-slate-900">اتاق {toPersianDigits(report.roomNumber)} ({report.floor})</strong>
            </div>
            <div>
              <span className="text-slate-500 block text-[10px]">تجهیز / قطعه:</span>
              <strong className="text-slate-900">{report.equipmentName}</strong>
            </div>
            <div>
              <span className="text-slate-500 block text-[10px]">فوریت گزارش:</span>
              <strong className="text-amber-700">
                {report.urgency === 'فوری' ? 'فوری 🚨' : report.urgency === 'عادی' ? 'عادی' : 'کم‌اهمیت'}
              </strong>
            </div>
            <div>
              <span className="text-slate-500 block text-[10px]">گزارش‌دهنده اولیه:</span>
              <strong className="text-slate-900">{report.reportedBySupervisorName}</strong>
            </div>
          </div>

          {/* Description */}
          <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
            <span className="text-slate-500 block font-bold text-[11px]">شرح عیب گزارش شده توسط سرپرست خوابگاه:</span>
            <p className="text-slate-800 leading-relaxed">{report.description}</p>
          </div>

          {/* Action & Cost Breakdown Table */}
          <div className="border border-slate-300 rounded-xl overflow-hidden">
            <table className="w-full text-right text-xs">
              <thead className="bg-slate-100 border-b border-slate-300 text-slate-700 font-bold">
                <tr>
                  <th className="p-2.5">شرح اقدام اصلاحی انجام‌شده</th>
                  <th className="p-2.5">پیمانکار طرف حساب / تامین‌کننده</th>
                  <th className="p-2.5">وضعیت تجهیز</th>
                  <th className="p-2.5 text-left">مبلغ پرداختی فاکتور</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 text-slate-800">
                <tr>
                  <td className="p-3 font-semibold">
                    {report.resolutionAction || (isReplacement ? 'خرید و جایگزینی تجهیز نو' : 'تعمیر و سرویس قطعه')}
                  </td>
                  <td className="p-3">
                    {report.contractorOrStoreName || 'خدمات فنی و مهندسی پردیس پسران نیشابور'}
                  </td>
                  <td className="p-3">
                    <span className="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                      تحویل و نصب گردید ✓
                    </span>
                  </td>
                  <td className="p-3 text-left font-mono font-bold text-slate-900 text-sm">
                    {cost > 0 ? `${toPersianDigits(cost.toLocaleString())} ریال` : 'رایگان (انبار دانشگاه)'}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Manager Notes */}
          {report.resolutionNotes && (
            <div className="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs">
              <strong className="text-amber-900 block font-bold mb-1">توضیحات و دستور پرداخت مدیریت:</strong>
              <p className="text-amber-800 leading-relaxed">{report.resolutionNotes}</p>
            </div>
          )}

          {/* Signatures & Seal Box */}
          <div className="grid grid-cols-2 gap-8 pt-6 border-t-2 border-dashed border-slate-300 text-xs">
            <div className="space-y-3 text-center border-l border-slate-200 pl-4">
              <span className="text-slate-500 font-bold block">تاییدیه تحویل فنی (سرپرست شیفت):</span>
              <p className="text-slate-800 font-semibold">{report.reportedBySupervisorName}</p>
              <div className="pt-8 text-[10px] text-slate-400">
                [امضای دیجیتال و تایید دریافت قطعه سالم]
              </div>
            </div>

            <div className="space-y-3 text-center">
              <span className="text-slate-500 font-bold block">مدیریت امور رفاهی و ناظر مالی:</span>
              <p className="text-slate-900 font-bold">{report.resolvedByManagerName || 'دکتر علیرضا حسینی'}</p>
              <div className="pt-2 flex items-center justify-center">
                <div className="px-3 py-1 rounded-full border-2 border-teal-700 text-teal-800 text-[10px] font-black inline-flex items-center gap-1">
                  <ShieldCheck className="w-3.5 h-3.5" />
                  <span>تایید مالی و پرداخت شد • پردیس پسران نیشابور</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Modal Footer (hidden in print) */}
        <div className="print:hidden p-4 bg-slate-900 border-t border-slate-700 flex items-center justify-end gap-2">
          <button
            type="button"
            onClick={handlePrint}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <Printer className="w-4 h-4" />
            <span>چاپ صورتحساب رسمی (A4)</span>
          </button>
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-colors cursor-pointer"
          >
            بستن
          </button>
        </div>
      </div>
    </div>
  );
};
