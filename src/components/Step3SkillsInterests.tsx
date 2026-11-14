import React, { useState, KeyboardEvent } from 'react';
import { StudentRegistrationData, UNIVERSITY_SPORTS_TEAMS } from '../types';
import { SUGGESTED_SKILLS } from '../data/provincesAndCities';
import { Sparkles, Plus, X, Award, CheckSquare, ArrowLeft, ArrowRight, Lightbulb, AlertCircle, Trophy, Check } from 'lucide-react';

interface Step3Props {
  data: StudentRegistrationData;
  onChange: (updates: Partial<StudentRegistrationData>) => void;
  onNext: () => void;
  onPrev: () => void;
}

export const Step3SkillsInterests: React.FC<Step3Props> = ({
  data,
  onChange,
  onNext,
  onPrev
}) => {
  const [currentInput, setCurrentInput] = useState('');
  const [error, setError] = useState<string | null>(null);

  // Add a skill when user presses Enter or clicks Add
  const addSkill = (skillToAdd: string) => {
    const trimmed = skillToAdd.trim();
    if (!trimmed) return;

    if (data.skills.includes(trimmed)) {
      setError(`مهارت «${trimmed}» قبلاً به لیست اضافه شده است.`);
      return;
    }

    onChange({
      skills: [...data.skills, trimmed]
    });
    setCurrentInput('');
    setError(null);
  };

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addSkill(currentInput);
    }
  };

  const removeSkill = (indexToRemove: number) => {
    onChange({
      skills: data.skills.filter((_, idx) => idx !== indexToRemove)
    });
  };

  const toggleFestivalInterest = (key: keyof StudentRegistrationData['festivalInterests']) => {
    const nextState = !data.festivalInterests[key];
    onChange({
      festivalInterests: {
        ...data.festivalInterests,
        [key]: nextState
      },
      // If sports is unticked, optionally keep or reset sportsTeams
      sportsTeams: key === 'sports' && !nextState ? [] : data.sportsTeams
    });
  };

  const toggleSportsTeam = (teamName: string) => {
    const currentTeams = data.sportsTeams || [];
    const updated = currentTeams.includes(teamName)
      ? currentTeams.filter(t => t !== teamName)
      : [...currentTeams, teamName];
    onChange({ sportsTeams: updated });
  };

  const validateAndProceed = (e: React.FormEvent) => {
    e.preventDefault();
    // If there is uncommitted text in the input, auto-commit it
    if (currentInput.trim()) {
      addSkill(currentInput);
    }
    onNext();
  };

  return (
    <form onSubmit={validateAndProceed} className="space-y-6">
      <div className="border-b border-slate-200/80 pb-4">
        <h2 className="text-base font-bold text-slate-900">
          مرحله سوم: مهارت‌ها و علاقه‌مندی در جشنواره‌ها
        </h2>
        <p className="text-xs text-slate-500 mt-1">
          مهارت‌های فنی و فردی خود را بنویسید (با اینتر اضافه کنید) و جشنواره‌های مورد علاقه را مشخص فرمایید.
        </p>
      </div>

      {/* ۱- بخش تکست اریا و اینپوت مهارت‌ها با قابلیت اینتر */}
      <div className="space-y-3 bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200/80">
        <div className="flex items-center justify-between">
          <label className="block text-xs font-bold text-slate-800">
            ورود مهارت‌های فردی و تخصصی (تایپ کنید و کلید اینتر را بزنید)
          </label>
          <span className="text-[11px] text-slate-500 font-mono">
            {data.skills.length} مهارت ثبت‌شده
          </span>
        </div>

        {/* Text Input with Add button and Enter handler */}
        <div className="flex gap-2">
          <div className="relative flex-1">
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <Sparkles className="w-4 h-4 text-indigo-500" />
            </div>
            <input
              type="text"
              value={currentInput}
              onChange={(e) => { setCurrentInput(e.target.value); setError(null); }}
              onKeyDown={handleKeyDown}
              placeholder="یک مهارت بنویسید (مثلاً: پایتون، طراحی مدار، عکاسی) و اینتر ↵ بزنید"
              className="w-full pr-10 pl-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all"
            />
          </div>
          <button
            type="button"
            onClick={() => addSkill(currentInput)}
            disabled={!currentInput.trim()}
            className="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-semibold rounded-xl transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>افزودن</span>
          </button>
        </div>

        {error && (
          <p className="text-[11px] text-rose-600 flex items-center gap-1">
            <AlertCircle className="w-3 h-3" />
            <span>{error}</span>
          </p>
        )}

        {/* Added Skills Badge Cloud */}
        {data.skills.length > 0 ? (
          <div className="pt-2">
            <p className="text-[11px] font-semibold text-slate-600 mb-2">مهارت‌های ثبت‌شده شما:</p>
            <div className="flex flex-wrap gap-2">
              {data.skills.map((skill, index) => (
                <span
                  key={`${skill}-${index}`}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-800 text-xs font-medium rounded-lg border border-indigo-200/80 group"
                >
                  <span>{skill}</span>
                  <button
                    type="button"
                    onClick={() => removeSkill(index)}
                    className="text-indigo-400 hover:text-rose-600 transition-colors cursor-pointer"
                    title="حذف این مهارت"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                </span>
              ))}
            </div>
          </div>
        ) : (
          <div className="p-3 bg-white/80 rounded-xl border border-dashed border-slate-200 text-center">
            <p className="text-xs text-slate-400">
              هنوز مهارتی اضافه نشده است. مهارت خود را در کادر بالا بنویسید و کلید اینتر کیبورد را فشار دهید.
            </p>
          </div>
        )}

        {/* Quick Suggested Skills Chips */}
        <div className="pt-2">
          <div className="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 mb-1.5">
            <Lightbulb className="w-3.5 h-3.5 text-amber-500" />
            <span>پیشنهاد مهارت‌های پرطرفدار (برای افزودن سریع کلیک کنید):</span>
          </div>
          <div className="flex flex-wrap gap-1.5">
            {SUGGESTED_SKILLS.filter(s => !data.skills.includes(s)).slice(0, 8).map((suggested) => (
              <button
                key={suggested}
                type="button"
                onClick={() => addSkill(suggested)}
                className="px-2.5 py-1 text-[11px] text-slate-600 bg-white hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 rounded-lg transition-all cursor-pointer"
              >
                + {suggested}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* ۲- چند چک باکس برای وقتی که دانشجو علاقه منده در جشنواره های فنی ، مهارتی ، مذهبی شرکت کنه */}
      <div className="space-y-3">
        <div className="flex items-center gap-2">
          <Award className="w-4 h-4 text-indigo-600" />
          <h3 className="text-xs font-bold text-slate-900">
            علاقه‌مندی جهت شرکت در جشنواره‌ها، مسابقات و رویدادهای دانشگاهی:
          </h3>
        </div>
        <p className="text-[11px] text-slate-500">
          با انتخاب هر مورد، دعوت‌نامه و اطلاع‌رسانی کارگاه‌ها و مراحل داوری جشنواره از طریق سامانه صمد برای شما ارسال می‌گردد.
        </p>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          {/* جشنواره های فنی */}
          <label className={`flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
            data.festivalInterests.technical
              ? 'border-indigo-500 bg-indigo-50/50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <input
              type="checkbox"
              checked={data.festivalInterests.technical}
              onChange={() => toggleFestivalInterest('technical')}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">
                جشنواره‌ها و المپیادهای فنی و اختراعات
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                مسابقات طراحی مهندسی، ایده‌پردازی، پروژه‌های صنعتی و کارآفرینی فنی
              </p>
            </div>
          </label>

          {/* جشنواره های مهارتی */}
          <label className={`flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
            data.festivalInterests.skillBased
              ? 'border-indigo-500 bg-indigo-50/50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <input
              type="checkbox"
              checked={data.festivalInterests.skillBased}
              onChange={() => toggleFestivalInterest('skillBased')}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">
                جشنواره‌های مهارتی و مسابقات ملی مهارت
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                کارگاه‌های عملی، طراحی وب، مکاترونیک، گرافیک و مهارت‌های شغلی
              </p>
            </div>
          </label>

          {/* مسابقات و جشنواره های مذهبی */}
          <label className={`flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
            data.festivalInterests.religious
              ? 'border-indigo-500 bg-indigo-50/50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <input
              type="checkbox"
              checked={data.festivalInterests.religious}
              onChange={() => toggleFestivalInterest('religious')}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">
                جشنواره‌های مذهبی، قرآنی و معارف اسلامی
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                مسابقات قرآن و عترت دانشگاهیان، تواشیح، مقاله‌نویسی و پژوهش معارفی
              </p>
            </div>
          </label>

          {/* جشنواره های فرهنگی و هنری */}
          <label className={`flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
            data.festivalInterests.cultural
              ? 'border-indigo-500 bg-indigo-50/50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <input
              type="checkbox"
              checked={data.festivalInterests.cultural}
              onChange={() => toggleFestivalInterest('cultural')}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">
                جشنواره‌های فرهنگی، ادبی و هنرهای تجسمی
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                عکاسی، نقاشی، شعر و ادب، تئاتر دانشجویی و نشریات دانشگاهی
              </p>
            </div>
          </label>

          {/* رباتیک و هوش مصنوعی */}
          <label className={`flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all cursor-pointer ${
            data.festivalInterests.robotics
              ? 'border-indigo-500 bg-indigo-50/50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <input
              type="checkbox"
              checked={data.festivalInterests.robotics}
              onChange={() => toggleFestivalInterest('robotics')}
              className="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">
                مسابقات رباتیک، هوش مصنوعی و حل مسئله داده‌محور
              </span>
              <p className="text-[11px] text-slate-500 mt-0.5">
                رویدادهای کدنویسی سریع (Hackathon)، چالش‌های هوش مصنوعی و ربات‌های خودران
              </p>
            </div>
          </label>

          {/* جشنواره‌های ورزشی و مسابقات پردیس (با باز شدن لیست تیم‌ها) */}
          <div className={`sm:col-span-2 rounded-2xl border-2 transition-all overflow-hidden ${
            data.festivalInterests.sports
              ? 'border-emerald-500 bg-emerald-50/30 shadow-xs'
              : 'border-slate-200 bg-white hover:border-slate-300'
          }`}>
            <label className="flex items-start gap-3 p-3.5 cursor-pointer">
              <input
                type="checkbox"
                checked={!!data.festivalInterests.sports}
                onChange={() => toggleFestivalInterest('sports')}
                className="mt-0.5 w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
              />
              <div className="flex-1">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <Trophy className="w-4 h-4 text-emerald-600" />
                    <span>جشنواره‌های ورزشی، المپیادها و مسابقات پردیس</span>
                  </span>
                  {data.festivalInterests.sports && (
                    <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full">
                      {(data.sportsTeams || []).length} تیم انتخاب‌شده
                    </span>
                  )}
                </div>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  تیم‌های منتخب دانشجویی، لیگ‌های بین‌دانشکده‌ای و مسابقات منطقه‌ای دانشگاه ملی مهارت
                </p>
              </div>
            </label>

            {/* لیست تیم‌های دانشگاه که با تیک زدن جشنواره‌های ورزشی باز می‌شود */}
            {data.festivalInterests.sports && (
              <div className="p-4 bg-emerald-50/60 border-t border-emerald-200/80 space-y-3 animate-fadeIn">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-emerald-950 flex items-center gap-1">
                    <span>🏃‍♂️</span>
                    <span>انتخاب و ثبت‌نام در تیم‌های ورزشی دانشگاه (تیک بزنید):</span>
                  </span>
                  <span className="text-[11px] text-emerald-700">
                    امکان انتخاب چند تیم ورزشی همزمان وجود دارد
                  </span>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                  {UNIVERSITY_SPORTS_TEAMS.map((teamName) => {
                    const isSelected = (data.sportsTeams || []).includes(teamName);
                    return (
                      <button
                        key={teamName}
                        type="button"
                        onClick={() => toggleSportsTeam(teamName)}
                        className={`p-2.5 rounded-xl border text-right transition-all flex items-center justify-between cursor-pointer ${
                          isSelected
                            ? 'bg-emerald-600 text-white font-bold border-emerald-600 shadow-xs'
                            : 'bg-white text-slate-700 hover:bg-emerald-100/50 border-slate-200'
                        }`}
                      >
                        <span className="text-xs">{teamName}</span>
                        {isSelected ? (
                          <Check className="w-3.5 h-3.5 text-white" />
                        ) : (
                          <span className="w-3.5 h-3.5 rounded-sm border border-slate-300 inline-block" />
                        )}
                      </button>
                    );
                  })}
                </div>

                {(data.sportsTeams || []).length > 0 ? (
                  <div className="p-2.5 bg-white rounded-xl border border-emerald-200 text-[11px] text-emerald-800 flex items-center gap-2">
                    <CheckSquare className="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>
                      شما برای عضویت در <strong>{(data.sportsTeams || []).join('، ')}</strong> اعلام آمادگی نموده‌اید. اطلاعیه‌های این تیم‌ها مستقیماً در پنل شما قرار می‌گیرد.
                    </span>
                  </div>
                ) : (
                  <p className="text-[11px] text-amber-700 bg-amber-50 p-2 rounded-lg border border-amber-200">
                    ⚠️ لطفاً حداقل یک تیم ورزشی از لیست بالا را جهت دریافت فراخوان‌ها و شرکت در تمرینات انتخاب فرمایید.
                  </p>
                )}
              </div>
            )}
          </div>
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
          <span>مرحله بعدی: تعیین رمز عبور</span>
          <ArrowLeft className="w-4 h-4" />
        </button>
      </div>
    </form>
  );
};
