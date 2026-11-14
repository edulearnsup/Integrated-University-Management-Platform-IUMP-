import React, { useState } from 'react';
import { MaintenanceReport } from '../types';
import { Wrench, ShoppingCart, CheckCircle2, Clock, AlertTriangle, UserCheck, DollarSign, X, FileText, Store } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface MaintenanceResolutionModalProps {
  report: MaintenanceReport | null;
  onClose: () => void;
  onResolve: (
    reportId: number,
    action: 'تعمیر قطعه' | 'خرید و جایگزینی تجهیز نو',
    cost: number,
    notes: string,
    invoiceNumber?: string,
    invoiceDate?: string,
    contractorOrStoreName?: string,
    invoiceNotes?: string
  ) => void;
}

export const MaintenanceResolutionModal: React.FC<MaintenanceResolutionModalProps> = ({
  report,
  onClose,
  onResolve
}) => {
  if (!report) return null;

  const [actionType, setActionType] = useState<'تعمیر قطعه' | 'خرید و جایگزینی تجهیز نو'>(
    report.maintenanceType === 'تعویض / خرید جدید' ? 'خرید و جایگزینی تجهیز نو' : 'تعمیر قطعه'
  );
  const [cost, setCost] = useState<string>('250000');
  const [notes, setNotes] = useState<string>('اقدام لازم انجام شد و قطعه تحویل و تست گردید.');
  const [invoiceNumber, setInvoiceNumber] = useState<string>(`INV-1403-${Math.floor(1000 + Math.random() * 9000)}`);
  const [invoiceDate, setInvoiceDate] = useState<string>('۱۴۰۳/۰۷/۱۴');
  const [contractorOrStoreName, setContractorOrStoreName] = useState<string>('خدمات فنی و تاسیساتی پردیس (مهندس باقری)');
  const [invoiceNotes, setInvoiceNotes] = useState<string>('اجرت سرویس و تعویض قطعه یدکی با ضمانت ۶ ماهه');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const numericCost = parseInt(cost.replace(/[^\d]/g, '')) || 0;
    onResolve(
      report.id,
      actionType,
      numericCost,
      notes.trim(),
      invoiceNumber.trim() || undefined,
      invoiceDate.trim() || undefined,
      contractorOrStoreName.trim() || undefined,
      invoiceNotes.trim() || undefined
    );
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-[#1e293b] text-white rounded-3xl max-w-lg w-full border border-slate-700 shadow-2xl p-6 space-y-5 my-8">
        
        {/* Header */}
        <div className="flex items-center justify-between border-b border-slate-700 pb-3">
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-amber-500/20 text-amber-300">
              <Wrench className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-sm font-bold text-white">ثبت اقدام مدیر، صدور فاکتور و رفع خرابی خوابگاه</h3>
              <span className="text-[11px] text-slate-400">اتاق {report.roomNumber} ({report.floor})</span>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Report Overview Box */}
        <div className="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <div className="flex justify-between items-center">
            <span className="text-slate-400">تجهیز آسیب‌دیده:</span>
            <strong className="text-amber-300 text-sm">{report.equipmentName}</strong>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-slate-400">سرپرست گزارش‌کننده:</span>
            <span className="text-white font-semibold flex items-center gap-1">
              <UserCheck className="w-3.5 h-3.5 text-indigo-400" />
              <span>{report.reportedBySupervisorName}</span>
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-slate-400">زمان دقیق گزارش:</span>
            <span className="text-slate-300 font-mono flex items-center gap-1">
              <Clock className="w-3.5 h-3.5 text-slate-500" />
              <span>{report.reportedAt}</span>
            </span>
          </div>
          <div className="pt-2 border-t border-slate-800 text-slate-300 leading-relaxed bg-slate-950/60 p-2.5 rounded-xl">
            <span className="text-slate-400 block text-[10px] mb-0.5">شرح نقص توسط سرپرست:</span>
            {report.description}
          </div>
        </div>

        {/* Resolution Form */}
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block text-slate-300 font-semibold mb-2">نوع اقدام و رفع اشکال مدیر:</label>
            <div className="grid grid-cols-2 gap-3">
              <label className={`p-3 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors ${
                actionType === 'تعمیر قطعه'
                  ? 'bg-indigo-950/60 border-indigo-500 text-white font-bold'
                  : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="actionType"
                  checked={actionType === 'تعمیر قطعه'}
                  onChange={() => setActionType('تعمیر قطعه')}
                  className="accent-indigo-600"
                />
                <Wrench className="w-4 h-4 text-indigo-400" />
                <span>تعمیر قطعه 🛠</span>
              </label>

              <label className={`p-3 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors ${
                actionType === 'خرید و جایگزینی تجهیز نو'
                  ? 'bg-emerald-950/60 border-emerald-500 text-white font-bold'
                  : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="actionType"
                  checked={actionType === 'خرید و جایگزینی تجهیز نو'}
                  onChange={() => setActionType('خرید و جایگزینی تجهیز نو')}
                  className="accent-emerald-600"
                />
                <ShoppingCart className="w-4 h-4 text-emerald-400" />
                <span>خرید و جایگزینی نو 🛒</span>
              </label>
            </div>
          </div>

          {/* اطلاعات مالی و فاکتور */}
          <div className="p-3.5 bg-slate-900 rounded-2xl border border-slate-800 space-y-3">
            <span className="font-bold text-amber-300 flex items-center gap-1.5 text-xs">
              <FileText className="w-4 h-4 text-amber-400" />
              <span>اطلاعات فاکتور، خرج و مخارج و اسناد مالی</span>
            </span>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-slate-400 mb-1 text-[11px]">مبلغ خرج‌شده (تومان):</label>
                <div className="relative">
                  <input
                    type="text"
                    value={cost}
                    onChange={(e) => setCost(e.target.value)}
                    placeholder="مثال: ۲۵۰,۰۰۰"
                    className="w-full px-3 py-1.5 pl-12 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none font-mono"
                  />
                  <span className="absolute left-3 top-2 text-slate-400 text-[10px]">تومان</span>
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1 text-[11px]">شماره فاکتور / رسید خرید:</label>
                <input
                  type="text"
                  value={invoiceNumber}
                  onChange={(e) => setInvoiceNumber(e.target.value)}
                  placeholder="INV-1403-XXXX"
                  className="w-full px-3 py-1.5 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none font-mono text-xs"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-slate-400 mb-1 text-[11px]">نام تعمیرکار / فروشگاه طرف حساب:</label>
                <input
                  type="text"
                  value={contractorOrStoreName}
                  onChange={(e) => setContractorOrStoreName(e.target.value)}
                  placeholder="مثال: خدمات فنی آریا، فروشگاه بهداشت"
                  className="w-full px-3 py-1.5 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none text-xs"
                />
              </div>

              <div>
                <label className="block text-slate-400 mb-1 text-[11px]">تاریخ فاکتور:</label>
                <input
                  type="text"
                  value={invoiceDate}
                  onChange={(e) => setInvoiceDate(e.target.value)}
                  placeholder="۱۴۰۳/۰۷/۱۴"
                  className="w-full px-3 py-1.5 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none text-xs font-mono"
                />
              </div>
            </div>

            <div>
              <label className="block text-slate-400 mb-1 text-[11px]">اقلام فاکتور و ضمانت:</label>
              <input
                type="text"
                value={invoiceNotes}
                onChange={(e) => setInvoiceNotes(e.target.value)}
                placeholder="شرح قطعات مصرفی، گارانتی یا اجرت..."
                className="w-full px-3 py-1.5 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none text-xs"
              />
            </div>
          </div>

          <div>
            <label className="block text-slate-300 font-semibold mb-1">توضیحات و گزارش مدیر دانشگاه:</label>
            <textarea
              rows={2}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="توضیحات اقدامات انجام‌شده توسط مدیریت امور دانشجویی..."
              className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none resize-none"
            />
          </div>

          <div className="pt-3 border-t border-slate-700 flex items-center justify-between">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer"
            >
              انصراف
            </button>

            <button
              type="submit"
              className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold flex items-center gap-1.5 shadow-lg shadow-emerald-600/30 cursor-pointer"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>ثبت رفع اشکال و فاکتور در سامانه ✓</span>
            </button>
          </div>
        </form>

      </div>
    </div>
  );
};
