import React from 'react';
import { Check, User, MapPin, Sparkles, LockKeyhole } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface ProgressBarProps {
  currentStep: number;
  totalSteps: number;
  onStepClick?: (stepNumber: number) => void;
  completedSteps: number[];
}

export const ProgressBar: React.FC<ProgressBarProps> = ({
  currentStep,
  totalSteps = 4,
  onStepClick,
  completedSteps
}) => {
  const steps = [
    {
      number: 1,
      title: 'اطلاعات دانشجویی',
      subtitle: 'عکس، کد ملی، پردیس',
      icon: User
    },
    {
      number: 2,
      title: 'اطلاعات درسی و سکونت',
      subtitle: 'رشته، روزانه/شبانه، استان و شهر',
      icon: MapPin
    },
    {
      number: 3,
      title: 'مهارت‌ها و جشنواره‌ها',
      subtitle: 'تگ مهارت‌ها، علایق جشنواره‌ای',
      icon: Sparkles
    },
    {
      number: 4,
      title: 'تعیین رمز عبور',
      subtitle: 'رمز امن، اعتبارسنجی',
      icon: LockKeyhole
    }
  ];

  // Calculate percentage for progress line
  const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;

  return (
    <div className="w-full bg-white border-b border-slate-200/80 py-4 px-4 sm:px-6 shadow-xs">
      <div className="max-w-4xl mx-auto">
        {/* Mobile Step Header */}
        <div className="flex sm:hidden items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <span className="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center font-mono">
              {toPersianDigits(currentStep)}
            </span>
            <span className="text-xs font-bold text-slate-800">
              {steps[currentStep - 1].title}
            </span>
          </div>
          <span className="text-xs text-slate-500 font-mono">
            مرحله {toPersianDigits(currentStep)} از {toPersianDigits(totalSteps)}
          </span>
        </div>

        {/* Desktop Step Bar */}
        <div className="relative">
          {/* Background Track Line */}
          <div className="absolute top-5 right-6 left-6 h-0.5 bg-slate-200 z-0" />
          
          {/* Active Fill Line */}
          <div 
            className="absolute top-5 right-6 h-0.5 bg-indigo-600 transition-all duration-500 ease-out z-0"
            style={{ width: `calc(${progressPercentage}% * 0.92)` }}
          />

          {/* Step Nodes */}
          <div className="relative z-10 flex items-start justify-between">
            {steps.map((step) => {
              const isCompleted = completedSteps.includes(step.number);
              const isCurrent = currentStep === step.number;
              const isClickable = isCompleted || isCurrent;
              const Icon = step.icon;

              return (
                <div
                  key={step.number}
                  onClick={() => isClickable && onStepClick && onStepClick(step.number)}
                  className={`flex flex-col items-center group transition-all ${
                    isClickable ? 'cursor-pointer' : 'cursor-not-allowed opacity-75'
                  }`}
                  style={{ width: `${100 / totalSteps}%` }}
                >
                  {/* Step Bubble */}
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center font-semibold text-xs transition-all duration-300 shadow-xs ${
                      isCompleted
                        ? 'bg-emerald-600 text-white ring-4 ring-emerald-100'
                        : isCurrent
                        ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 scale-105'
                        : 'bg-white border-2 border-slate-300 text-slate-500'
                    }`}
                  >
                    {isCompleted ? (
                      <Check className="w-5 h-5 stroke-[2.5]" />
                    ) : (
                      <span className="font-mono">{toPersianDigits(step.number)}</span>
                    )}
                  </div>

                  {/* Step Label (Desktop) */}
                  <div className="hidden sm:flex flex-col items-center text-center mt-2.5 px-1">
                    <span
                      className={`text-xs font-bold leading-tight transition-colors ${
                        isCurrent
                          ? 'text-indigo-700'
                          : isCompleted
                          ? 'text-slate-800'
                          : 'text-slate-500'
                      }`}
                    >
                      {step.title}
                    </span>
                    <span className="text-[10px] text-slate-400 mt-0.5 leading-tight line-clamp-1">
                      {step.subtitle}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Global Progress Bar Thin Strip */}
        <div className="mt-3.5 sm:mt-4 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
          <div 
            className="bg-indigo-600 h-1.5 rounded-full transition-all duration-500 ease-out"
            style={{ width: `${(currentStep / totalSteps) * 100}%` }}
          />
        </div>
      </div>
    </div>
  );
};
