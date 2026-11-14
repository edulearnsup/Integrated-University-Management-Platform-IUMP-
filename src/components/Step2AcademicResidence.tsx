import React, { useState, useMemo } from 'react';
import { StudentRegistrationData, ShiftType } from '../types';
import { IRAN_PROVINCES, FIELDS_OF_STUDY } from '../data/provincesAndCities';
import { BookOpen, Clock, MapPin, Building, Home, Globe, AlertCircle, ArrowLeft, ArrowRight, BedDouble } from 'lucide-react';

interface Step2Props {
  data: StudentRegistrationData;
  onChange: (updates: Partial<StudentRegistrationData>) => void;
  onNext: () => void;
  onPrev: () => void;
}

export const Step2AcademicResidence: React.FC<Step2Props> = ({
  data,
  onChange,
  onNext,
  onPrev
}) => {
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Dynamic cities based on chosen province
  const availableCities = useMemo(() => {
    const found = IRAN_PROVINCES.find((p) => p.province === data.province);
    return found ? found.cities : [];
  }, [data.province]);

  const handleProvinceChange = (province: string) => {
    const found = IRAN_PROVINCES.find((p) => p.province === province);
    const defaultCity = found && found.cities.length > 0 ? found.cities[0] : '';
    onChange({ province, city: defaultCity });
  };

  const validateAndProceed = (e: React.FormEvent) => {
    e.preventDefault();
    const newErrors: Record<string, string> = {};

    if (!data.fieldOfStudy) {
      newErrors.fieldOfStudy = 'لطفاً رشته تحصیلی خود را انتخاب فرمایید.';
    }

    if (!data.studyShift) {
      newErrors.studyShift = 'انتخاب دوره روزانه یا شبانه الزامی است.';
    }

    if (!data.province) {
      newErrors.province = 'لطفاً استان محل سکونت را انتخاب کنید.';
    }

    if (!data.city) {
      newErrors.city = 'لطفاً شهر محل سکونت را انتخاب کنید.';
    }

    if (!data.residentialAddress.trim()) {
      newErrors.residentialAddress = 'لطفاً آدرس دقیق محل زندگی را وارد نمایید.';
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
          مرحله دوم: اطلاعات درسی و محل زندگی
        </h2>
        <p className="text-xs text-slate-500 mt-1">
          رشته تحصیلی، نوبت پذیرش و نشانی سکونت جهت خدمات رفاهی و تسهیلات دانشجویی.
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* 1- سلکت آپشن رشته تحصیلی */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۱- رشته تحصیلی <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <BookOpen className="w-4 h-4" />
            </div>
            <select
              required
              value={data.fieldOfStudy}
              onChange={(e) => onChange({ fieldOfStudy: e.target.value })}
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all cursor-pointer"
            >
              <option value="">-- انتخاب رشته دانشگاهی --</option>
              {FIELDS_OF_STUDY.map((field) => (
                <option key={field} value={field}>
                  {field}
                </option>
              ))}
            </select>
          </div>
          {errors.fieldOfStudy && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.fieldOfStudy}</span>
            </p>
          )}
        </div>

        {/* 2- سلکت آپشن برای روزانه یا شبانه بودن */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۲- دوره تحصیلی (روزانه / شبانه) <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <Clock className="w-4 h-4" />
            </div>
            <select
              required
              value={data.studyShift}
              onChange={(e) => onChange({ studyShift: e.target.value as ShiftType })}
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all cursor-pointer"
            >
              <option value="روزانه">دوره روزانه (آموزش رایگان دولتی)</option>
              <option value="شبانه (نوبت دوم)">دوره شبانه (نوبت دوم - شهریه‌پرداز)</option>
            </select>
          </div>
          {errors.studyShift && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.studyShift}</span>
            </p>
          )}
        </div>

        {/* 3- استان محل زندگی */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۳- استان محل زندگی <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <MapPin className="w-4 h-4" />
            </div>
            <select
              required
              value={data.province}
              onChange={(e) => handleProvinceChange(e.target.value)}
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all cursor-pointer"
            >
              <option value="">-- انتخاب استان --</option>
              {IRAN_PROVINCES.map((p) => (
                <option key={p.province} value={p.province}>
                  {p.province}
                </option>
              ))}
            </select>
          </div>
          {errors.province && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.province}</span>
            </p>
          )}
        </div>

        {/* 4- شهر محل زندگی */}
        <div>
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            ۴- شهر محل زندگی <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <Building className="w-4 h-4" />
            </div>
            <select
              required
              disabled={!data.province}
              value={data.city}
              onChange={(e) => onChange({ city: e.target.value })}
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all disabled:opacity-60 cursor-pointer"
            >
              <option value="">-- انتخاب شهر --</option>
              {availableCities.map((city) => (
                <option key={city} value={city}>
                  {city}
                </option>
              ))}
            </select>
          </div>
          {errors.city && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.city}</span>
            </p>
          )}
        </div>

        {/* چک باکس برای دانشجویان اتباع */}
        <div className="sm:col-span-2 p-3.5 bg-slate-50 rounded-xl border border-slate-200">
          <label className="flex items-start gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={data.isForeignStudent}
              onChange={(e) => onChange({ isForeignStudent: e.target.checked })}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <Globe className="w-3.5 h-3.5 text-indigo-600" />
                <span>دانشجوی غیرایرانی / اتباع خارجی هستم</span>
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                در صورت انتخاب، پرونده آموزشی شما در اداره امور بین‌الملل و اتباع سامانه صمد ثبت خواهد شد.
              </p>
            </div>
          </label>

          {data.isForeignStudent && (
            <div className="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                  شماره گذرنامه یا کد یکتای اقامت
                </label>
                <input
                  type="text"
                  value={data.foreignStudentCode || ''}
                  onChange={(e) => onChange({ foreignStudentCode: e.target.value })}
                  placeholder="مثال: P98234112 یا کد ۱۰ رقمی فراگیر"
                  className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-left"
                  dir="ltr"
                />
              </div>
            </div>
          )}
        </div>

        {/* - آدرس محل زندگی */}
        <div className="sm:col-span-2">
          <label className="block text-xs font-semibold text-slate-700 mb-1.5">
            آدرس دقیق محل زندگی و سکونت <span className="text-rose-500">*</span>
          </label>
          <div className="relative">
            <div className="absolute top-2.5 right-0 pr-3 pointer-events-none text-slate-400">
              <Home className="w-4 h-4" />
            </div>
            <textarea
              required
              rows={2}
              value={data.residentialAddress}
              onChange={(e) => onChange({ residentialAddress: e.target.value })}
              placeholder="خیابان اصلی، کوچه، پلاک، طبقه و کد پستی ۱۰ رقمی"
              className="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all resize-none"
            />
          </div>
          {errors.residentialAddress && (
            <p className="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" />
              <span>{errors.residentialAddress}</span>
            </p>
          )}
        </div>

        {/* یک چک باکس برای دانشجو هایی که درخواست خوابگاه دارن */}
        <div className="sm:col-span-2 p-3.5 bg-indigo-50/60 rounded-xl border border-indigo-200/80">
          <label className="flex items-start gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={data.requestDormitory}
              onChange={(e) => onChange({ requestDormitory: e.target.checked })}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-indigo-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <BedDouble className="w-4 h-4 text-indigo-600" />
                <span>متقاضی اسکان و درخواست خوابگاه دانشجویی دولتی / خودگردان هستم</span>
              </span>
              <p className="text-[11px] text-slate-600 mt-0.5">
                اولویت واگذاری خوابگاه طبق آیین‌نامه رفاهی با دانشجویان غیربومی، روزانه و پذیرفته‌شدگان برتر است.
              </p>
            </div>
          </label>

          {data.requestDormitory && (
            <div className="mt-3 pt-3 border-t border-indigo-200/60">
              <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                توضیحات تکمیلی یا شرایط خاص اسکان (اختیاری):
              </label>
              <input
                type="text"
                value={data.dormitoryNote || ''}
                onChange={(e) => onChange({ dormitoryNote: e.target.value })}
                placeholder="مثال: دانشجوی غیربومی فاصله بالای ۱۰۰ کیلومتر، نیاز به خوابگاه متأهلی یا پردیس مهارت"
                className="w-full px-3 py-2 bg-white border border-indigo-200 rounded-lg text-xs text-slate-800"
              />
            </div>
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
          className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition-all cursor-pointer"
        >
          <span>مرحله بعدی: اطلاعات مهارتی</span>
          <ArrowLeft className="w-4 h-4" />
        </button>
      </div>
    </form>
  );
};
