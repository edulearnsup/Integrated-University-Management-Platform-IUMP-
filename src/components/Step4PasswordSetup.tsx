import React, { useState } from 'react';
import { StudentRegistrationData } from '../types';
import { validatePassword } from '../utils/validators';
import { LockKeyhole, Eye, EyeOff, Check, X, ShieldAlert, ShieldCheck, ArrowRight, CheckCircle2, AlertTriangle, FileText } from 'lucide-react';

interface Step4Props {
  data: StudentRegistrationData;
  onChange: (updates: Partial<StudentRegistrationData>) => void;
  onSubmit: () => void;
  onPrev: () => void;
  isSubmitting?: boolean;
}

export const Step4PasswordSetup: React.FC<Step4Props> = ({
  data,
  onChange,
  onSubmit,
  onPrev,
  isSubmitting = false
}) => {
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [touched, setTouched] = useState(false);
  const [submitAttempted, setSubmitAttempted] = useState(false);

  const validation = validatePassword(data.password);
  const passwordsMatch = data.password.length > 0 && data.password === data.confirmPassword;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitAttempted(true);
    setTouched(true);

    if (!validation.isValid) {
      return;
    }

    if (!passwordsMatch) {
      return;
    }

    if (!data.acceptedTerms) {
      return;
    }

    onSubmit();
  };

  // Determine meter color
  const getMeterColor = () => {
    if (data.password.length === 0) return 'bg-slate-200';
    if (!validation.hasMinLength || validation.strengthScore === 1) return 'bg-rose-500';
    if (validation.strengthScore === 2) return 'bg-amber-500';
    if (validation.strengthScore >= 3) return 'bg-emerald-500';
    return 'bg-slate-200';
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="border-b border-slate-200/80 pb-4">
        <h2 className="text-base font-bold text-slate-900">
          مرحله چهارم: تعیین کلمه عبور و نهایی‌سازی حساب کاربری
        </h2>
        <p className="text-xs text-slate-500 mt-1">
          جهت ورود به سامانه‌های آموزشی و رفاهی صمد، کلمه عبور اختصاصی خود را طبق اصول امنیتی مشخص نمایید.
        </p>
      </div>

      <div className="space-y-5">
        {/* Password Input */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            کلمه عبور جدید <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <LockKeyhole className="w-4 h-4" />
            </div>
            <input
              type={showPassword ? 'text' : 'password'}
              required
              value={data.password}
              onChange={(e) => {
                onChange({ password: e.target.value });
                setTouched(true);
              }}
              placeholder="کلمه عبور لاتین را وارد فرمایید"
              className={`w-full pr-10 pl-10 py-2.5 bg-slate-50 border rounded-xl text-xs text-slate-900 font-mono text-left focus:outline-none focus:ring-2 transition-all ${
                touched && !validation.isValid && data.password.length > 0
                  ? 'border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 bg-rose-50/20'
                  : touched && validation.isValid
                  ? 'border-emerald-300 focus:ring-emerald-500/20 focus:border-emerald-500 bg-emerald-50/20'
                  : 'border-slate-300 focus:ring-indigo-500/20 focus:border-indigo-600'
              }`}
              dir="ltr"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
            >
              {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          </div>

          {/* Strength Bar */}
          <div className="mt-2 space-y-1">
            <div className="flex items-center justify-between text-[11px]">
              <span className="text-slate-500">کیفیت رمز عبور:</span>
              <span className={`font-semibold ${
                validation.isValid ? 'text-emerald-600' : data.password.length > 0 ? 'text-amber-600' : 'text-slate-400'
              }`}>
                {validation.strengthLabel}
              </span>
            </div>
            <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden flex gap-1">
              <div
                className={`h-full rounded-full transition-all duration-300 ${getMeterColor()}`}
                style={{ width: `${Math.max(10, (validation.strengthScore + 1) * 20)}%` }}
              />
            </div>
          </div>

          {/* DYNAMIC ERROR MESSAGE UNDERNEATH PASSWORD INPUT AS REQUIRED */}
          {touched && data.password.length > 0 && !validation.isValid && (
            <div className="mt-2.5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-2.5 animate-fadeIn">
              <AlertTriangle className="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
              <div className="text-xs">
                <p className="font-bold text-rose-900">رمز عبور ضعیف یا ناقص است!</p>
                <p className="mt-0.5 text-rose-700 leading-relaxed">
                  {validation.errorMessage || 'رمز عبور باید حداقل ۸ کاراکتر و شامل حروف بزرگ، کوچک لاتین و علامت خاص باشد.'}
                </p>
              </div>
            </div>
          )}

          {/* Interactive Checklist of Criteria */}
          <div className="mt-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200/90 space-y-2">
            <span className="block text-[11px] font-bold text-slate-700">
              الزامات امنیتی رمز عبور صمد:
            </span>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
              {/* حداقل ۸ کاراکتر */}
              <div className={`flex items-center gap-1.5 ${validation.hasMinLength ? 'text-emerald-700 font-medium' : 'text-slate-500'}`}>
                {validation.hasMinLength ? (
                  <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                ) : (
                  <X className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                )}
                <span>حداقل ۸ حرف یا کاراکتر</span>
              </div>

              {/* حروف کوچک لاتین */}
              <div className={`flex items-center gap-1.5 ${validation.hasLowercase ? 'text-emerald-700 font-medium' : 'text-slate-500'}`}>
                {validation.hasLowercase ? (
                  <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                ) : (
                  <X className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                )}
                <span>حروف کوچک لاتین (a-z)</span>
              </div>

              {/* حروف بزرگ لاتین */}
              <div className={`flex items-center gap-1.5 ${validation.hasUppercase ? 'text-emerald-700 font-medium' : 'text-slate-500'}`}>
                {validation.hasUppercase ? (
                  <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                ) : (
                  <X className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                )}
                <span>حروف بزرگ لاتین (A-Z)</span>
              </div>

              {/* حداقل یک کاراکتر خاص */}
              <div className={`flex items-center gap-1.5 ${validation.hasSpecialChar ? 'text-emerald-700 font-medium' : 'text-slate-500'}`}>
                {validation.hasSpecialChar ? (
                  <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                ) : (
                  <X className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                )}
                <span>حداقل یک کاراکتر خاص (!@#$%^&*)</span>
              </div>
            </div>
          </div>
        </div>

        {/* Confirm Password Input */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            تکرار کلمه عبور <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <LockKeyhole className="w-4 h-4" />
            </div>
            <input
              type={showConfirmPassword ? 'text' : 'password'}
              required
              value={data.confirmPassword}
              onChange={(e) => onChange({ confirmPassword: e.target.value })}
              placeholder="تکرار کلمه عبور جدید"
              className={`w-full pr-10 pl-10 py-2.5 bg-slate-50 border rounded-xl text-xs text-slate-900 font-mono text-left focus:outline-none focus:ring-2 transition-all ${
                data.confirmPassword.length > 0 && !passwordsMatch
                  ? 'border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 bg-rose-50/20'
                  : data.confirmPassword.length > 0 && passwordsMatch
                  ? 'border-emerald-300 focus:ring-emerald-500/20 focus:border-emerald-500 bg-emerald-50/20'
                  : 'border-slate-300 focus:ring-indigo-500/20 focus:border-indigo-600'
              }`}
              dir="ltr"
            />
            <button
              type="button"
              onClick={() => setShowConfirmPassword(!showConfirmPassword)}
              className="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
            >
              {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          </div>

          {data.confirmPassword.length > 0 && !passwordsMatch && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertTriangle className="w-3 h-3" />
              <span>کلمه عبور و تکرار آن مطابقت ندارند.</span>
            </p>
          )}

          {passwordsMatch && (
            <p className="text-[11px] text-emerald-600 mt-1 flex items-center gap-1">
              <CheckCircle2 className="w-3 h-3" />
              <span>کلمه عبور و تکرار آن یکسان هستند.</span>
            </p>
          )}
        </div>

        {/* Accepted Terms */}
        <div className="pt-2">
          <label className="flex items-start gap-2.5 cursor-pointer">
            <input
              type="checkbox"
              required
              checked={data.acceptedTerms}
              onChange={(e) => onChange({ acceptedTerms: e.target.checked })}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <span className="text-xs text-slate-700 leading-relaxed">
              صحت کلیه اطلاعات درج‌شده در مراحل چهارگانه ثبت‌نام را تایید نموده و آیین‌نامه انضباطی، آموزشی و رفاهی پردیس‌های فناوری و مهارت صمد را می‌پذیرم.
            </span>
          </label>
          {submitAttempted && !data.acceptedTerms && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertTriangle className="w-3 h-3" />
              <span>پذیرش آیین‌نامه جهت تکمیل ثبت‌نام الزامی است.</span>
            </p>
          )}
        </div>
      </div>

      {/* Action Buttons */}
      <div className="pt-4 flex items-center justify-between border-t border-slate-100">
        <button
          type="button"
          onClick={onPrev}
          className="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer"
        >
          <ArrowRight className="w-4 h-4" />
          <span>مرحله قبل</span>
        </button>

        <button
          type="submit"
          disabled={isSubmitting}
          className="inline-flex items-center gap-2 px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-70 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
        >
          <ShieldCheck className="w-4 h-4" />
          <span>{isSubmitting ? 'در حال صدور کارت و ثبت پرونده...' : 'تایید نهایی و تکمیل ثبت‌نام'}</span>
        </button>
      </div>
    </form>
  );
};
