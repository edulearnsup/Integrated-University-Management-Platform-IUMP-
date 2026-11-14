import React, { useRef, useState, useEffect } from 'react';
import { StudentRegistrationData, CampusType } from '../types';
import { Upload, Camera, Trash2, User, CreditCard, School, AlertCircle, ArrowLeft, Image as ImageIcon, Database, CheckCircle2, ChevronDown, ChevronUp, AlertTriangle } from 'lucide-react';
import { toEnglishDigits, isValidNationalId } from '../utils/validators';
import { INITIAL_ADMITTED_STUDENTS, verifyStudentWithSourceDb, AdmittedStudent } from '../data/admittedStudents';

interface Step1Props {
  data: StudentRegistrationData;
  onChange: (updates: Partial<StudentRegistrationData>) => void;
  onNext: () => void;
}

export const Step1StudentInfo: React.FC<Step1Props> = ({
  data,
  onChange,
  onNext
}) => {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [sourceMatchStatus, setSourceMatchStatus] = useState<{
    checked: boolean;
    matched: boolean;
    student?: AdmittedStudent;
    message?: string;
  }>({ checked: false, matched: false });

  const [showSourceDbPanel, setShowSourceDbPanel] = useState<boolean>(true);

  // Check matching on nationalId / studentId change
  useEffect(() => {
    const cleanNat = toEnglishDigits(data.nationalId).trim();
    const cleanStd = toEnglishDigits(data.studentId).trim();

    if (cleanNat.length === 10 && cleanStd.length >= 5) {
      const result = verifyStudentWithSourceDb(cleanNat, cleanStd);
      if (result.matched && result.student) {
        setSourceMatchStatus({
          checked: true,
          matched: true,
          student: result.student,
          message: `تطابق تایید شد: پرونده قبولی متعلق به «${result.student.fullName}» در ${result.student.campus} می‌باشد.`
        });
      } else {
        setSourceMatchStatus({
          checked: true,
          matched: false,
          message: result.errorMessage
        });
      }
    } else {
      setSourceMatchStatus({ checked: false, matched: false });
    }
  }, [data.nationalId, data.studentId]);

  const handleSelectSample = (sample: AdmittedStudent) => {
    onChange({
      nationalId: sample.nationalId,
      studentId: sample.studentId,
      fullName: sample.fullName,
      campus: sample.campus,
      fieldOfStudy: sample.fieldOfStudy
    });
    setErrors({});
  };

  const handleSetMismatchedTest = () => {
    onChange({
      nationalId: '0021487591', // Ali's national ID
      studentId: '4012130046', // Sara's student ID!
      fullName: 'علی رضایی مقدم',
      campus: 'پردیس فناوری'
    });
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      setErrors((prev) => ({ ...prev, photo: 'فایل انتخابی باید از نوع تصویر (JPG یا PNG) باشد.' }));
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      setErrors((prev) => ({ ...prev, photo: 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.' }));
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      onChange({ photoUrl: event.target?.result as string });
      setErrors((prev) => {
        const copy = { ...prev };
        delete copy.photo;
        return copy;
      });
    };
    reader.readAsDataURL(file);
  };

  const handleRemovePhoto = () => {
    onChange({ photoUrl: '' });
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleUsePresetPhoto = () => {
    onChange({ photoUrl: '/src/assets/images/student_sample_avatar_1790759772433.jpg' });
    setErrors((prev) => {
      const copy = { ...prev };
      delete copy.photo;
      return copy;
    });
  };

  const validateAndProceed = (e: React.FormEvent) => {
    e.preventDefault();
    const newErrors: Record<string, string> = {};

    const cleanNationalId = toEnglishDigits(data.nationalId).trim();
    const cleanStudentId = toEnglishDigits(data.studentId).trim();

    if (!data.fullName.trim()) {
      newErrors.fullName = 'لطفاً نام و نام خانوادگی دانشجو را وارد نمایید.';
    }

    if (!cleanNationalId) {
      newErrors.nationalId = 'کد ملی ۱۰ رقمی الزامی است.';
    } else if (!isValidNationalId(cleanNationalId)) {
      newErrors.nationalId = 'فرمت کد ملی نامعتبر است (باید ۱۰ رقم باشد).';
    }

    if (!cleanStudentId) {
      newErrors.studentId = 'کد دانشجویی الزامی است.';
    } else if (cleanStudentId.length < 5) {
      newErrors.studentId = 'کد دانشجویی باید حداقل ۵ رقم باشد.';
    }

    if (!data.campus) {
      newErrors.campus = 'لطفاً پردیس دانشگاهی را انتخاب فرمایید.';
    }

    // بررسی تطابق با جدول منبع (admitted_students)
    if (cleanNationalId && cleanStudentId) {
      const matchResult = verifyStudentWithSourceDb(cleanNationalId, cleanStudentId);
      if (!matchResult.matched) {
        newErrors.sourceDb = matchResult.errorMessage || 'کد ملی با شماره دانشجویی در جدول منبع پذیرفته‌شدگان مطابقت ندارد.';
      } else if (matchResult.student) {
        // همگام‌سازی خودکار نام و پردیس با مشخصات رسمی جدول منبع
        if (!data.fullName) {
          onChange({ fullName: matchResult.student.fullName });
        }
      }
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setErrors({});
    onNext();
  };

  return (
    <form onSubmit={validateAndProceed} className="space-y-6">
      <div className="border-b border-slate-200/80 pb-4">
        <h2 className="text-base font-bold text-slate-900">
          مرحله اول: اطلاعات هویتی و احراز هویت با جدول منبع
        </h2>
        <p className="text-xs text-slate-500 mt-1">
          کد ملی و شماره دانشجویی ورودی مستقیماً با جدول منبع قبولی (<code className="font-mono text-indigo-700 font-bold">admitted_students</code>) تطبیق داده می‌شود.
        </p>
      </div>

      {/* Database Source Table Interactive Helper */}
      <div className="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-800 space-y-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="p-1.5 bg-indigo-500/20 text-indigo-300 rounded-lg">
              <Database className="w-4 h-4" />
            </span>
            <div>
              <span className="text-xs font-bold text-white block">
                جدول منبع پذیرفته‌شدگان در دیتابیس (admitted_students)
              </span>
              <span className="text-[11px] text-slate-400">
                تنها در صورت تطابق کد ملی با شماره دانشجویی در این جدول، ثبت‌نام مجاز خواهد بود.
              </span>
            </div>
          </div>
          <button
            type="button"
            onClick={() => setShowSourceDbPanel(!showSourceDbPanel)}
            className="text-slate-400 hover:text-white p-1 cursor-pointer"
          >
            {showSourceDbPanel ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
          </button>
        </div>

        {showSourceDbPanel && (
          <div className="space-y-2.5 pt-2 border-t border-slate-800">
            <p className="text-[11px] text-indigo-200">
              برای آزمایش صحت تطابق یا مشاهده خطای عدم تطابق، از دکمه‌های زیر استفاده کنید:
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              {INITIAL_ADMITTED_STUDENTS.slice(0, 3).map((st) => (
                <button
                  key={st.id}
                  type="button"
                  onClick={() => handleSelectSample(st)}
                  className="p-2.5 bg-slate-800/80 hover:bg-slate-800 hover:border-indigo-400 border border-slate-700 rounded-xl text-right transition-all cursor-pointer group"
                >
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-white group-hover:text-indigo-300">
                      {st.fullName}
                    </span>
                    <span className="text-[10px] text-indigo-400 font-mono">تست تطابق ✓</span>
                  </div>
                  <div className="text-[10px] text-slate-400 font-mono mt-1 flex justify-between">
                    <span>ملی: {st.nationalId}</span>
                    <span>دانشجویی: {st.studentId}</span>
                  </div>
                </button>
              ))}
            </div>

            <div className="flex items-center justify-between pt-1">
              <button
                type="button"
                onClick={handleSetMismatchedTest}
                className="text-[11px] text-rose-300 hover:text-rose-200 hover:underline flex items-center gap-1 cursor-pointer"
                title="تست خطای عدم تطابق با قرار دادن کد ملی علی و شماره دانشجویی سارا"
              >
                <AlertTriangle className="w-3.5 h-3.5 text-rose-400" />
                <span>تست حالت نامنطبق (جهت مشاهده خطای رد ثبت‌نام)</span>
              </button>
              <span className="text-[10px] text-slate-500">
                بررسی خودکار بلادرنگ با هر تغییر کد
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Real-time Match Status Feedback Banner */}
      {sourceMatchStatus.checked && (
        <div className={`p-4 rounded-xl border flex items-start gap-3 text-xs animate-fadeIn ${
          sourceMatchStatus.matched
            ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
            : 'bg-rose-50 border-rose-200 text-rose-900'
        }`}>
          {sourceMatchStatus.matched ? (
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
          ) : (
            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
          )}
          <div className="space-y-0.5">
            <span className="font-bold block">
              {sourceMatchStatus.matched ? 'تطابق هویتی با جدول منبع تایید شد' : 'خطای عدم تطابق با جدول منبع دیتابیس'}
            </span>
            <p className="text-[11px] leading-relaxed">
              {sourceMatchStatus.message}
            </p>
          </div>
        </div>
      )}

      {errors.sourceDb && (
        <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2">
          <AlertCircle className="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
          <span>{errors.sourceDb}</span>
        </div>
      )}

      {/* Photo Upload Section */}
      <div className="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200/80">
        <label className="block text-xs font-bold text-slate-700 mb-2">
          عکس پرسنلی دانشجو (جهت ذخیره در جدول students و صدور کارت هوشمند)
        </label>
        
        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
          <div className="relative w-24 h-28 rounded-xl border-2 border-dashed border-slate-300 bg-white flex flex-col items-center justify-center overflow-hidden shrink-0 shadow-xs">
            {data.photoUrl ? (
              <img
                src={data.photoUrl}
                alt="عکس پرسنلی دانشجو"
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                <Camera className="w-6 h-6 mb-1 text-slate-300" />
                <span className="text-[10px]">عکس ۳×۴</span>
              </div>
            )}
          </div>

          <div className="flex-1 space-y-2 text-center sm:text-right">
            <input
              type="file"
              ref={fileInputRef}
              onChange={handleFileChange}
              accept="image/png, image/jpeg, image/webp"
              className="hidden"
              id="student-photo-upload"
            />

            <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <label
                htmlFor="student-photo-upload"
                className="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-indigo-700 text-xs font-semibold rounded-xl border border-slate-200 shadow-xs cursor-pointer transition-colors"
              >
                <Upload className="w-3.5 h-3.5" />
                <span>بارگذاری عکس از سیستم</span>
              </label>

              {!data.photoUrl && (
                <button
                  type="button"
                  onClick={handleUsePresetPhoto}
                  className="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl border border-indigo-200/60 transition-colors cursor-pointer"
                >
                  <ImageIcon className="w-3.5 h-3.5" />
                  <span>انتخاب عکس نمونه</span>
                </button>
              )}

              {data.photoUrl && (
                <button
                  type="button"
                  onClick={handleRemovePhoto}
                  className="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors cursor-pointer"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                  <span>حذف عکس</span>
                </button>
              )}
            </div>

            <p className="text-[11px] text-slate-500 leading-normal">
              حداکثر حجم ۲ مگابایت (JPG یا PNG). تصویر در مسیر uploads سرور MySQL ذخیره می‌شود.
            </p>

            {errors.photo && (
              <p className="text-xs text-rose-600 flex items-center gap-1">
                <AlertCircle className="w-3.5 h-3.5" />
                <span>{errors.photo}</span>
              </p>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* 1- کد ملی */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۱- کد ملی <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <CreditCard className="w-4 h-4" />
            </div>
            <input
              type="text"
              maxLength={10}
              required
              value={data.nationalId}
              onChange={(e) => onChange({ nationalId: e.target.value })}
              placeholder="مثال: 0021487591"
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-left"
              dir="ltr"
            />
          </div>
          {errors.nationalId && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.nationalId}</span>
            </p>
          )}
        </div>

        {/* 2- کد دانشجویی */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۲- کد دانشجویی <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <School className="w-4 h-4" />
            </div>
            <input
              type="text"
              required
              value={data.studentId}
              onChange={(e) => onChange({ studentId: e.target.value })}
              placeholder="مثال: 4012130045"
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-left"
              dir="ltr"
            />
          </div>
          {errors.studentId && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.studentId}</span>
            </p>
          )}
        </div>

        {/* Full Name */}
        <div className="sm:col-span-2">
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            نام و نام خانوادگی دانشجو <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <User className="w-4 h-4" />
            </div>
            <input
              type="text"
              required
              value={data.fullName}
              onChange={(e) => onChange({ fullName: e.target.value })}
              placeholder="طبق شناسنامه و کارنامه قبولی"
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all"
            />
          </div>
          {errors.fullName && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.fullName}</span>
            </p>
          )}
        </div>

        {/* 3- سلکت آپشن با دو گزینه: پردیس فناوری - پردیس مهارت */}
        <div className="sm:col-span-2">
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۳- انتخاب پردیس دانشگاهی صمد <span className="text-rose-500">*</span>
          </label>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label
              className={`flex items-center gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
                data.campus === 'پردیس فناوری'
                  ? 'border-indigo-600 bg-indigo-50/60 shadow-xs'
                  : 'border-slate-200 bg-white hover:border-slate-300'
              }`}
            >
              <input
                type="radio"
                name="campus"
                value="پردیس فناوری"
                checked={data.campus === 'پردیس فناوری'}
                onChange={() => onChange({ campus: 'پردیس فناوری' })}
                className="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
              />
              <div className="flex flex-col">
                <span className="text-xs font-bold text-slate-900">پردیس فناوری</span>
                <span className="text-[11px] text-slate-500">رشته‌های فنی، مهندسی و علوم داده</span>
              </div>
            </label>

            <label
              className={`flex items-center gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
                data.campus === 'پردیس مهارت'
                  ? 'border-indigo-600 bg-indigo-50/60 shadow-xs'
                  : 'border-slate-200 bg-white hover:border-slate-300'
              }`}
            >
              <input
                type="radio"
                name="campus"
                value="پردیس مهارت"
                checked={data.campus === 'پردیس مهارت'}
                onChange={() => onChange({ campus: 'پردیس مهارت' })}
                className="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
              />
              <div className="flex flex-col">
                <span className="text-xs font-bold text-slate-900">پردیس مهارت</span>
                <span className="text-[11px] text-slate-500">کارگاه‌های تخصصی، طراحی و آموزش مهارتی</span>
              </div>
            </label>
          </div>
          {errors.campus && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.campus}</span>
            </p>
          )}
        </div>
      </div>

      {/* Action Button */}
      <div className="pt-4 flex items-center justify-between">
        <span className="text-[11px] text-slate-500">
          استعلام زنده از جدول <code className="font-mono text-indigo-600 font-bold">admitted_students</code>
        </span>
        <button
          type="submit"
          className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition-all cursor-pointer"
        >
          <span>تایید تطابق با جدول منبع و مرحله بعدی</span>
          <ArrowLeft className="w-4 h-4" />
        </button>
      </div>
    </form>
  );
};
