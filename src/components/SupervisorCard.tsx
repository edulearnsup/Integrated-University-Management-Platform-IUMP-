import React from 'react';
import { SupervisorAccount } from '../types';
import { ShieldCheck, QrCode, Phone, Clock, Layers, Calendar, Printer, CheckCircle2, Building, User, Sparkles } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface SupervisorCardProps {
  supervisor: SupervisorAccount;
  onClose?: () => void;
}

export const SupervisorCard: React.FC<SupervisorCardProps> = ({ supervisor, onClose }) => {
  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-indigo-500/40 relative overflow-hidden max-w-xl w-full mx-auto animate-fadeIn">
      {/* Background Decorative Rings */}
      <div className="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none" />

      {/* Card Header */}
      <div className="flex items-center justify-between pb-4 border-b border-indigo-700/60 relative z-10">
        <div className="flex items-center gap-3">
          <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center font-bold text-white shadow-md border border-indigo-400/40">
            صمد
          </div>
          <div>
            <div className="text-[11px] text-indigo-300 font-medium">پردیس فناوری و مهارت - امور دانشجویی</div>
            <h2 className="text-sm sm:text-base font-extrabold text-white tracking-wide">
              کارت رسمی شناسایی سرپرست خوابگاه
            </h2>
          </div>
        </div>

        <div className="text-left">
          <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded-full text-[10px] font-bold">
            <CheckCircle2 className="w-3 h-3 text-emerald-400" />
            <span>سرپرست فعال ✓</span>
          </span>
          <span className="block text-[10px] text-indigo-300 font-mono mt-0.5">
            {supervisor.supervisorCode || 'SUP-405-01'}
          </span>
        </div>
      </div>

      {/* Main Body */}
      <div className="mt-5 flex flex-col sm:flex-row gap-5 items-center sm:items-start relative z-10">
        {/* Supervisor Avatar / Photo Box */}
        <div className="w-28 h-36 rounded-2xl bg-slate-800 border-2 border-indigo-400/40 overflow-hidden relative shadow-lg shrink-0 flex flex-col items-center justify-center">
          {supervisor.avatarUrl ? (
            <img
              src={supervisor.avatarUrl}
              alt={supervisor.fullName}
              referrerPolicy="no-referrer"
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="text-center p-2">
              <User className="w-12 h-12 text-indigo-300 mx-auto mb-1 opacity-70" />
              <span className="text-[10px] text-indigo-200 block">تصویر سرپرست</span>
            </div>
          )}
          <div className="absolute bottom-0 inset-x-0 bg-slate-950/80 py-1 text-center text-[10px] text-amber-300 font-bold">
            سرپرست رسمی
          </div>
        </div>

        {/* Info Grid */}
        <div className="flex-1 w-full space-y-3 text-xs">
          <div>
            <span className="text-[11px] text-indigo-300 block">نام و نام خانوادگی:</span>
            <span className="text-base font-bold text-white mt-0.5 block">
              {supervisor.fullName}
            </span>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <span className="text-indigo-300 text-[11px] block">کد ملی (شناسه ورود):</span>
              <span className="font-mono text-sm font-bold text-amber-300 tracking-wider">
                {toPersianDigits(supervisor.nationalId)}
              </span>
            </div>

            <div>
              <span className="text-indigo-300 text-[11px] block">شماره تماس اضطراری:</span>
              <span className="font-mono text-xs font-semibold text-slate-200" dir="ltr">
                {toPersianDigits(supervisor.mobile)}
              </span>
            </div>
          </div>

          {/* Shift & Duty Highlighted Box */}
          <div className="p-3 bg-indigo-900/60 border border-indigo-600/50 rounded-xl space-y-1.5 shadow-xs">
            <div className="flex items-center gap-1.5 text-amber-300 font-bold text-xs">
              <Clock className="w-4 h-4 text-amber-400 shrink-0" />
              <span>شیفت سرپرستی: {supervisor.shiftHours || '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)'}</span>
            </div>
            <div className="flex items-center gap-1.5 text-indigo-200 text-[11px]">
              <Layers className="w-3.5 h-3.5 text-indigo-300 shrink-0" />
              <span>حوزه مسئولیت: <strong>{supervisor.assignedDuty}</strong></span>
            </div>
            <div className="flex items-center gap-1.5 text-slate-300 text-[10px] pt-0.5 border-t border-indigo-700/50">
              <Building className="w-3.5 h-3.5 text-indigo-400 shrink-0" />
              <span>ساختمان خوابگاه مرکزی صمد (پردیس فناوری و مهارت)</span>
            </div>
          </div>
        </div>
      </div>

      {/* Card Footer with Security Hologram, QR Code and Issue Date */}
      <div className="mt-5 pt-3 border-t border-indigo-800/60 flex items-center justify-between text-[11px] text-indigo-300 relative z-10">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-white border border-white/20">
            <QrCode className="w-5 h-5 text-indigo-200" />
          </div>
          <div>
            <span className="block text-[10px] text-slate-400">تاییدیه سرپرستی صمد:</span>
            <span className="font-mono text-[10px] text-amber-300 font-bold">صلاحیت تایید شده امور خوابگاه‌ها</span>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {onClose && (
            <button
              type="button"
              onClick={onClose}
              className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
            >
              بستن
            </button>
          )}
          <button
            type="button"
            onClick={handlePrint}
            className="flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition-colors cursor-pointer"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>چاپ کارت</span>
          </button>
        </div>
      </div>
    </div>
  );
};
