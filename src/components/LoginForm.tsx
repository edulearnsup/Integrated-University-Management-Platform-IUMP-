import React, { useState } from 'react';
import { Eye, EyeOff, ShieldCheck, RefreshCw, KeyRound, User, Smartphone, ArrowLeft, CheckCircle2, AlertCircle } from 'lucide-react';
import { toEnglishDigits, toPersianDigits } from '../utils/validators';
import { SupervisorAccount } from '../types';

interface LoginFormProps {
  supervisors?: SupervisorAccount[];
  onGoToRegister: () => void;
  onLoginSuccess: (userData?: any) => void;
}

export const LoginForm: React.FC<LoginFormProps> = ({
  supervisors = [],
  onGoToRegister,
  onLoginSuccess
}) => {
  const [authMethod, setAuthMethod] = useState<'password' | 'otp'>('password');
  const [username, setUsername] = useState('4012130045');
  const [password, setPassword] = useState('Samad@2026!');
  const [showPassword, setShowPassword] = useState(false);
  const [captchaInput, setCaptchaInput] = useState('8452');
  const [captchaCode, setCaptchaCode] = useState('8452');
  const [mobileNumber, setMobileNumber] = useState('09121234567');
  const [otpSent, setOtpSent] = useState(false);
  const [otpInput, setOtpInput] = useState('');
  const [rememberMe, setRememberMe] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [showForgotModal, setShowForgotModal] = useState(false);

  // Generate random 4-digit captcha
  const refreshCaptcha = () => {
    const random = Math.floor(1000 + Math.random() * 9000).toString();
    setCaptchaCode(random);
    setCaptchaInput('');
  };

  const handlePasswordLogin = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    const cleanUsername = toEnglishDigits(username).trim();
    const cleanCaptcha = toEnglishDigits(captchaInput).trim();

    if (!cleanUsername) {
      setErrorMessage('لطفاً شماره دانشجویی یا کد ملی خود را وارد نمایید.');
      return;
    }
    if (!password) {
      setErrorMessage('لطفاً کلمه عبور را وارد فرمایید.');
      return;
    }
    if (cleanCaptcha !== captchaCode) {
      setErrorMessage('کد امنیتی وارد شده اشتباه است. لطفاً مجدداً امتحان کنید.');
      refreshCaptcha();
      return;
    }

    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);

      // بررسی ورود مدیر دانشگاه (کد ملی 0011223344)
      if (cleanUsername === '0011223344') {
        onLoginSuccess({
          role: 'manager',
          fullName: 'دکتر علیرضا حسینی',
          nationalId: '0011223344',
          campus: 'پردیس فناوری و مهارت صمد'
        });
        return;
      }

      // بررسی ورود سرپرستان شبانه خوابگاه (کد ملی 0099887766 یا هر سرپرست ثبت‌شده توسط مدیر)
      const matchingSupervisor = supervisors.find(s => s.nationalId === cleanUsername);
      if (matchingSupervisor || cleanUsername === '0099887766') {
        onLoginSuccess({
          role: 'supervisor',
          fullName: matchingSupervisor?.fullName || 'مهندس کامران قنبری',
          nationalId: cleanUsername,
          campus: 'پردیس فناوری و مهارت صمد'
        });
        return;
      }

      // ورود دانشجو
      onLoginSuccess({
        role: 'student',
        fullName: 'علی رضایی مقدم',
        studentId: cleanUsername,
        campus: 'پردیس فناوری',
        fieldOfStudy: 'مهندسی کامپیوتر - هوش مصنوعی و داده',
        photoUrl: '/src/assets/images/student_sample_avatar_1790759772433.jpg'
      });
    }, 600);
  };

  const handleSendOtp = () => {
    const cleanMobile = toEnglishDigits(mobileNumber).trim();
    if (cleanMobile.length !== 11 || !cleanMobile.startsWith('09')) {
      setErrorMessage('شماره موبایل وارد شده باید ۱۱ رقمی و با ۰۹ شروع شود.');
      return;
    }
    setErrorMessage(null);
    setOtpSent(true);
  };

  const handleOtpLogin = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    const cleanOtp = toEnglishDigits(otpInput).trim();
    if (cleanOtp.length < 4) {
      setErrorMessage('لطفاً کد تایید ۵ رقمی ارسال شده به شماره خود را وارد نمایید.');
      return;
    }
    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      onLoginSuccess({
        fullName: 'دانشجوی گرامی',
        studentId: '4012130045',
        campus: 'پردیس مهارت',
        fieldOfStudy: 'مهندسی فناوری اطلاعات (IT)',
        photoUrl: '/src/assets/images/student_sample_avatar_1790759772433.jpg'
      });
    }, 600);
  };

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-slate-50">
      {/* Background Ambience */}
      <div 
        className="absolute inset-0 z-0 opacity-15 pointer-events-none bg-cover bg-center"
        style={{ backgroundImage: `url('/src/assets/images/samad_auth_backdrop_1790759760837.jpg')` }}
      />
      <div className="absolute inset-0 z-0 bg-gradient-to-b from-white/80 via-slate-50/90 to-slate-100 pointer-events-none" />

      <div className="relative z-10 sm:mx-auto sm:w-full sm:max-w-md">
        {/* Brand Header */}
        <div className="text-center mb-6">
          <div className="mx-auto w-16 h-16 rounded-2xl p-1 bg-white shadow-md border border-slate-200/80 flex items-center justify-center">
            <img 
              src="/src/assets/images/samad_campus_emblem_1790759744696.jpg" 
              alt="لوگوی صمد" 
              referrerPolicy="no-referrer"
              className="w-full h-full object-contain rounded-xl"
            />
          </div>
          <h1 className="mt-4 text-2xl font-bold tracking-tight text-slate-900">
            ورود به سامانه دانشجویی صمد
          </h1>
          <p className="mt-1.5 text-xs text-slate-500">
            پرتال یکپارچه خدمات پردیس فناوری و پردیس مهارت
          </p>
        </div>

        {/* Card Container */}
        <div className="bg-white py-8 px-6 sm:px-8 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-200">
          {/* Method Tabs */}
          <div className="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl mb-4 border border-slate-200/70">
            <button
              type="button"
              onClick={() => { setAuthMethod('password'); setErrorMessage(null); }}
              className={`flex items-center justify-center gap-2 py-2 text-xs font-semibold rounded-lg transition-all cursor-pointer ${
                authMethod === 'password'
                  ? 'bg-white text-indigo-700 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <KeyRound className="w-3.5 h-3.5" />
              <span>کلمه عبور</span>
            </button>
            <button
              type="button"
              onClick={() => { setAuthMethod('otp'); setErrorMessage(null); }}
              className={`flex items-center justify-center gap-2 py-2 text-xs font-semibold rounded-lg transition-all cursor-pointer ${
                authMethod === 'otp'
                  ? 'bg-white text-indigo-700 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Smartphone className="w-3.5 h-3.5" />
              <span>رمز یکبار مصرف (SMS)</span>
            </button>
          </div>

          {/* Quick Admin & Student Test Presets */}
          <div className="mb-5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
            <div className="flex items-center justify-between text-[11px] text-slate-500">
              <span className="font-bold text-slate-700">تست سریع نقش‌های کاربری دیتابیس:</span>
              <span className="text-[10px] text-indigo-600">انتخاب با ۱ کلیک</span>
            </div>
            <div className="grid grid-cols-3 gap-1.5 text-[11px]">
              <button
                type="button"
                onClick={() => {
                  setUsername('0011223344');
                  setPassword('Admin@12345');
                  setCaptchaInput(captchaCode);
                }}
                className={`py-1.5 px-2 rounded-lg border text-center font-bold transition-all cursor-pointer ${
                  username === '0011223344'
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300'
                }`}
              >
                مدیر دانشگاه
              </button>

              <button
                type="button"
                onClick={() => {
                  setUsername('0099887766');
                  setPassword('Super@12345');
                  setCaptchaInput(captchaCode);
                }}
                className={`py-1.5 px-2 rounded-lg border text-center font-bold transition-all cursor-pointer ${
                  username === '0099887766'
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300'
                }`}
              >
                سرپرست خوابگاه
              </button>

              <button
                type="button"
                onClick={() => {
                  setUsername('4012130045');
                  setPassword('Samad@2026!');
                  setCaptchaInput(captchaCode);
                }}
                className={`py-1.5 px-2 rounded-lg border text-center font-bold transition-all cursor-pointer ${
                  username === '4012130045'
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300'
                }`}
              >
                دانشجو
              </button>
            </div>
          </div>

          {/* Error Alert */}
          {errorMessage && (
            <div className="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-2.5 text-rose-800 text-xs">
              <AlertCircle className="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
              <span>{errorMessage}</span>
            </div>
          )}

          {authMethod === 'password' ? (
            <form onSubmit={handlePasswordLogin} className="space-y-4">
              {/* Username Input */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                  شماره دانشجویی یا کد ملی <span className="text-rose-500">*</span>
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <User className="w-4 h-4" />
                  </div>
                  <input
                    type="text"
                    required
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="مثال: 4012130045 یا کد ملی ده‌رقمی"
                    className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-mono"
                    dir="ltr"
                  />
                </div>
              </div>

              {/* Password Input */}
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <label className="block text-xs font-semibold text-slate-700">
                    کلمه عبور <span className="text-rose-500">*</span>
                  </label>
                  <button
                    type="button"
                    onClick={() => setShowForgotModal(true)}
                    className="text-[11px] text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer"
                  >
                    فراموشی رمز؟
                  </button>
                </div>
                <div className="relative">
                  <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <KeyRound className="w-4 h-4" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="کلمه عبور را وارد فرمایید"
                    className="w-full pr-10 pl-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-mono"
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
              </div>

              {/* Captcha Field */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                  کد امنیتی تصویر <span className="text-rose-500">*</span>
                </label>
                <div className="flex items-center gap-2">
                  <div className="relative flex-1">
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                      <ShieldCheck className="w-4 h-4" />
                    </div>
                    <input
                      type="text"
                      maxLength={4}
                      value={captchaInput}
                      onChange={(e) => setCaptchaInput(e.target.value)}
                      placeholder="کد تصویر"
                      className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 text-center font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                    />
                  </div>

                  {/* Visual Captcha Display */}
                  <div className="flex items-center gap-1.5 bg-slate-100 border border-slate-300 px-3 py-1.5 rounded-xl select-none">
                    <span className="font-mono text-base font-bold tracking-widest text-slate-800 line-through decoration-slate-400 select-none">
                      {toPersianDigits(captchaCode)}
                    </span>
                    <button
                      type="button"
                      onClick={refreshCaptcha}
                      className="text-slate-400 hover:text-indigo-600 p-1 transition-colors cursor-pointer"
                      title="تصویر جدید"
                    >
                      <RefreshCw className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>

              {/* Remember me */}
              <div className="flex items-center justify-between pt-1">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={rememberMe}
                    onChange={(e) => setRememberMe(e.target.checked)}
                    className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                  />
                  <span className="text-xs text-slate-600">ذخیره نشست در این مرورگر</span>
                </label>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-70"
              >
                {isLoading ? (
                  <>
                    <RefreshCw className="w-4 h-4 animate-spin" />
                    <span>در حال اعتبارسنجی...</span>
                  </>
                ) : (
                  <span>ورود به سامانه صمد</span>
                )}
              </button>
            </form>
          ) : (
            /* OTP Method */
            <form onSubmit={handleOtpLogin} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                  شماره تلفن همراه دانشجو <span className="text-rose-500">*</span>
                </label>
                <div className="flex gap-2">
                  <input
                    type="tel"
                    required
                    value={mobileNumber}
                    onChange={(e) => setMobileNumber(e.target.value)}
                    placeholder="09121234567"
                    className="flex-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono text-left focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                    dir="ltr"
                  />
                  <button
                    type="button"
                    onClick={handleSendOtp}
                    className="px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-colors shrink-0 cursor-pointer"
                  >
                    {otpSent ? 'ارسال مجدد' : 'دریافت کد'}
                  </button>
                </div>
              </div>

              {otpSent && (
                <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>کد تایید ۵ رقمی به شماره همراه ارسال گردید.</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                  کد تایید پیامک‌شده <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  maxLength={5}
                  value={otpInput}
                  onChange={(e) => setOtpInput(e.target.value)}
                  placeholder="کد ۵ رقمی"
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-center font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                  dir="ltr"
                />
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-70"
              >
                {isLoading ? (
                  <>
                    <RefreshCw className="w-4 h-4 animate-spin" />
                    <span>بررسی کد...</span>
                  </>
                ) : (
                  <span>تایید و ورود به سامانه</span>
                )}
              </button>
            </form>
          )}

          {/* Divider & Switch to Register */}
          <div className="mt-6 pt-5 border-t border-slate-100 text-center">
            <p className="text-xs text-slate-500 mb-3">
              هنوز حساب کاربری دانشجویی ندارید؟
            </p>
            <button
              type="button"
              onClick={onGoToRegister}
              className="w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 text-indigo-700 hover:text-indigo-800 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>ثبت‌نام دانشجو در سامانه صمد (فرم چندمرحله‌ای)</span>
              <ArrowLeft className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Footer Notes */}
        <div className="mt-6 text-center text-[11px] text-slate-400 space-y-1">
          <p>سامانه یکپارچه مدیریت امور دانشجویی و رفاهی صمد</p>
          <p>پشتیبانی پردیس فناوری و پردیس مهارت دانشگاهی</p>
        </div>
      </div>

      {/* Forgot Password Modal */}
      {showForgotModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
          <div className="bg-white rounded-2xl p-6 max-w-sm w-full border border-slate-200 shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-slate-900">بازیابی کلمه عبور</h3>
            <p className="text-xs text-slate-600 leading-relaxed">
              جهت بازیابی کلمه عبور، کد ملی و شماره همراه ثبت‌شده خود را وارد نمایید یا با شماره داخلی آموزش و فناوری پردیس تماس حاصل فرمایید.
            </p>
            <div className="pt-2 flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setShowForgotModal(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl cursor-pointer"
              >
                متوجه شدم
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
