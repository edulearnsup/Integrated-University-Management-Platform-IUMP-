import React from 'react';
import { StudentRegistrationData, DormAllocation, DormRoom } from '../types';
import { CheckCircle2, QrCode, Sparkles, LogIn, ArrowLeft, Printer, Share2, BedDouble, ShieldCheck, Clock, Building } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface SuccessProps {
  data: StudentRegistrationData;
  dormAllocation?: DormAllocation;
  dormRoom?: DormRoom;
  onGoToLogin: () => void;
  onGoToDashboard: () => void;
}

export const SuccessStudentCard: React.FC<SuccessProps> = ({
  data,
  dormAllocation,
  dormRoom,
  onGoToLogin,
  onGoToDashboard
}) => {
  return (
    <div className="min-h-[calc(100vh-4rem)] bg-slate-50 py-10 px-4 sm:px-6">
      <div className="max-w-2xl mx-auto space-y-6">
        {/* Success Alert Banner */}
        <div className="p-4 sm:p-5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3.5 shadow-xs">
          <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-sm font-bold text-emerald-950">
              ثبت‌نام دانشجو با موفقیت انجام شد!
            </h2>
            <p className="text-xs text-emerald-800 mt-1 leading-relaxed">
              پرونده الکترونیکی شما در سامانه یکپارچه صمد ثبت گردید و کارت دانشجویی موقت صادر شد. اکنون می‌توانید با شماره دانشجویی و کلمه عبور خود وارد پرتال شوید.
            </p>
          </div>
        </div>

        {/* Digital Student Card */}
        <div className="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-indigo-900/60 relative overflow-hidden">
          {/* Decorative Background Elements */}
          <div className="absolute top-0 left-0 right-0 h-32 bg-radial from-indigo-500/15 via-transparent to-transparent pointer-events-none" />
          <div className="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-600/10 rounded-full blur-2xl pointer-events-none" />

          {/* Card Header */}
          <div className="flex items-center justify-between pb-5 border-b border-indigo-800/60">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-xl bg-white p-1 overflow-hidden shrink-0 border border-indigo-200/20 shadow-xs">
                <img
                  src="/src/assets/images/samad_campus_emblem_1790759744696.jpg"
                  alt="لوگو"
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-contain"
                />
              </div>
              <div>
                <span className="text-sm font-bold tracking-tight block">
                  کارت شناسایی هوشمند دانشجویی صمد
                </span>
                <span className="text-[11px] text-indigo-300 font-medium">
                  {data.campus} · سال تحصیلی ۱۴۰۵-۱۴۰۴
                </span>
              </div>
            </div>

            <div className="flex items-center gap-1.5 px-2.5 py-1 bg-indigo-900/80 border border-indigo-700/60 rounded-lg text-[10px] text-indigo-200">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
              <span>معتبر و تاییدشده</span>
            </div>
          </div>

          {/* Card Body */}
          <div className="pt-6 flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {/* Student Photo */}
            <div className="relative w-28 h-36 rounded-2xl overflow-hidden border-2 border-indigo-400/40 shadow-lg bg-slate-800 shrink-0">
              {data.photoUrl ? (
                <img
                  src={data.photoUrl}
                  alt={data.fullName}
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover"
                />
              ) : (
                <img
                  src="/src/assets/images/student_sample_avatar_1790759772433.jpg"
                  alt={data.fullName}
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover"
                />
              )}
              <div className="absolute bottom-0 inset-x-0 bg-slate-950/80 py-1 text-center text-[10px] text-indigo-200">
                دانشجو
              </div>
            </div>

            {/* Info Grid */}
            <div className="flex-1 w-full space-y-3">
              <div>
                <span className="text-xs text-indigo-300 block">نام و نام خانوادگی:</span>
                <span className="text-base font-bold text-white mt-0.5 block">
                  {data.fullName || 'محمد امینی'}
                </span>
              </div>

              <div className="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span className="text-indigo-300 text-[11px] block">شماره دانشجویی:</span>
                  <span className="font-mono text-sm font-bold text-amber-300 tracking-wider">
                    {data.studentId || '4012130045'}
                  </span>
                </div>

                <div>
                  <span className="text-indigo-300 text-[11px] block">کد ملی:</span>
                  <span className="font-mono text-sm font-semibold text-white tracking-wider">
                    {data.nationalId || '0021458971'}
                  </span>
                </div>

                <div>
                  <span className="text-indigo-300 text-[11px] block">رشته تحصیلی:</span>
                  <span className="text-white font-medium text-[11px]">
                    {data.fieldOfStudy || 'مهندسی کامپیوتر'}
                  </span>
                </div>

                <div>
                  <span className="text-indigo-300 text-[11px] block">دوره / سکونت:</span>
                  <span className="text-white font-medium text-[11px]">
                    {data.studyShift} ({data.city || 'تهران'})
                  </span>
                </div>
              </div>

              {/* Dormitory Allocation Summary or Request Badge */}
              <div className="pt-2 text-xs space-y-2">
                {dormAllocation && dormRoom ? (
                  <div className={`p-2.5 rounded-xl border space-y-1.5 ${
                    dormRoom.isActive
                      ? 'bg-emerald-950/70 border-emerald-500/50'
                      : 'bg-rose-950/60 border-rose-500/50'
                  }`}>
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-1.5 text-emerald-300 font-bold">
                        <BedDouble className="w-4 h-4 text-emerald-400 shrink-0" />
                        <span>خوابگاه صمد: {dormRoom.floor} - اتاق {dormRoom.roomNumber}</span>
                      </div>
                      {/* Room Active / Inactive Status */}
                      {dormRoom.isActive ? (
                        <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded text-[10px] font-bold">
                          اتاق فعال و دایر ✓
                        </span>
                      ) : (
                        <span className="px-2 py-0.5 bg-rose-500/30 text-rose-200 border border-rose-400/40 rounded text-[10px] font-bold">
                          اتاق غیرفعال (تعمیرات) ⚠️
                        </span>
                      )}
                    </div>

                    <div className="flex items-center justify-between text-[11px] text-emerald-200/90 pt-0.5">
                      <span>حالت اقامت: <strong>{dormAllocation.dormStatus}</strong></span>
                      <span>ظرفیت اتاق: {toPersianDigits(dormRoom.capacity)} نفره</span>
                    </div>

                    {/* Guest Remaining Days Notice */}
                    {dormAllocation.dormStatus === 'میهمان' && (
                      <div className="mt-1 p-2 bg-amber-500/20 border border-amber-400/40 rounded-lg text-amber-200 text-[11px] flex items-center justify-between">
                        <span className="flex items-center gap-1">
                          <Clock className="w-3.5 h-3.5 text-amber-300" />
                          <span>مهلت اقامت میهمان:</span>
                        </span>
                        <span className="font-bold text-amber-300 font-mono">
                          {dormAllocation.guestDaysRemaining !== undefined
                            ? dormAllocation.guestDaysRemaining > 0
                              ? `${toPersianDigits(dormAllocation.guestDaysRemaining)} روز باقی‌مانده (از ${toPersianDigits(dormAllocation.guestDaysTotal || 7)} روز)`
                              : 'مهلت منقضی شده (در صف تخلیه)'
                            : '۷ روز مهلت اقامت میهمان'}
                        </span>
                      </div>
                    )}
                  </div>
                ) : data.requestDormitory ? (
                  <div className="p-2 bg-indigo-900/60 border border-indigo-700/60 rounded-xl flex items-center gap-1.5 text-[11px] text-indigo-200">
                    <Clock className="w-3.5 h-3.5 text-amber-300 shrink-0" />
                    <span>متقاضی خوابگاه صمد (در انتظار بررسی و تخصیص اتاق توسط مدیر دانشگاه)</span>
                  </div>
                ) : null}

                {data.isForeignStudent && (
                  <div className="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-900/60 text-amber-200 rounded-md border border-amber-700 text-[10px]">
                    <span>دانشجوی بین‌الملل (اتباع خارجی)</span>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Card Footer with Skills & Barcode */}
          <div className="mt-6 pt-4 border-t border-indigo-800/60 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex-1 w-full text-center sm:text-right">
              {data.skills.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5 justify-center sm:justify-start">
                  <span className="text-[10px] text-indigo-300">مهارت‌ها:</span>
                  {data.skills.slice(0, 4).map((skill, i) => (
                    <span
                      key={i}
                      className="px-2 py-0.5 bg-white/10 text-white text-[10px] rounded"
                    >
                      {skill}
                    </span>
                  ))}
                  {data.skills.length > 4 && (
                    <span className="text-[10px] text-indigo-300">
                      +{toPersianDigits(data.skills.length - 4)} مهارت دیگر
                    </span>
                  )}
                </div>
              )}
            </div>

            {/* Simulated Barcode */}
            <div className="flex items-center gap-2 bg-white p-2 rounded-xl text-slate-900 shrink-0">
              <QrCode className="w-8 h-8" />
              <div className="flex flex-col text-[9px] font-mono leading-tight">
                <span className="font-bold">SAMAD-ID</span>
                <span>{data.studentId || '4012130045'}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Action Controls */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <button
            type="button"
            onClick={onGoToLogin}
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition-all cursor-pointer"
          >
            <LogIn className="w-4 h-4" />
            <span>ورود به سامانه صمد با حساب کاربری</span>
          </button>

          <button
            type="button"
            onClick={onGoToDashboard}
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-800 text-xs font-semibold rounded-xl border border-slate-300 shadow-xs transition-colors cursor-pointer"
          >
            <Sparkles className="w-4 h-4 text-indigo-600" />
            <span>ورود مستقیم به میزکار دانشجو (داشبورد صمد)</span>
          </button>

          <button
            type="button"
            onClick={() => window.print()}
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer"
            title="چاپ کارت موقت"
          >
            <Printer className="w-4 h-4" />
            <span>چاپ کارت</span>
          </button>
        </div>
      </div>
    </div>
  );
};
