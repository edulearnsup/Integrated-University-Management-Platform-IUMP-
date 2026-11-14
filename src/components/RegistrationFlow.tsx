import React, { useState } from 'react';
import { StudentRegistrationData } from '../types';
import { ProgressBar } from './ProgressBar';
import { Step1StudentInfo } from './Step1StudentInfo';
import { Step2AcademicResidence } from './Step2AcademicResidence';
import { Step3SkillsInterests } from './Step3SkillsInterests';
import { Step4PasswordSetup } from './Step4PasswordSetup';
import { ArrowRight, LogIn } from 'lucide-react';

interface RegistrationFlowProps {
  initialData: StudentRegistrationData;
  onComplete: (data: StudentRegistrationData) => void;
  onGoToLogin: () => void;
  onUpdateData: (updates: Partial<StudentRegistrationData>) => void;
}

export const RegistrationFlow: React.FC<RegistrationFlowProps> = ({
  initialData,
  onComplete,
  onGoToLogin,
  onUpdateData
}) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [completedSteps, setCompletedSteps] = useState<number[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const markStepCompleted = (stepNum: number) => {
    if (!completedSteps.includes(stepNum)) {
      setCompletedSteps((prev) => [...prev, stepNum]);
    }
  };

  const handleNextFromStep1 = () => {
    markStepCompleted(1);
    setCurrentStep(2);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleNextFromStep2 = () => {
    markStepCompleted(2);
    setCurrentStep(3);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleNextFromStep3 = () => {
    markStepCompleted(3);
    setCurrentStep(4);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleFinalSubmit = () => {
    markStepCompleted(4);
    setIsSubmitting(true);
    setTimeout(() => {
      setIsSubmitting(false);
      onComplete(initialData);
    }, 700);
  };

  const handleStepClick = (stepNumber: number) => {
    // Can only jump to current or already completed steps or next immediate step
    if (completedSteps.includes(stepNumber) || stepNumber === currentStep) {
      setCurrentStep(stepNumber);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  return (
    <div className="min-h-[calc(100vh-4rem)] bg-slate-50/70 pb-16">
      {/* Top Progress Bar */}
      <ProgressBar
        currentStep={currentStep}
        totalSteps={4}
        completedSteps={completedSteps}
        onStepClick={handleStepClick}
      />

      {/* Main Container */}
      <div className="max-w-3xl mx-auto px-4 sm:px-6 mt-6 sm:mt-8">
        {/* Navigation Breadcrumb / Return to login */}
        <div className="flex items-center justify-between mb-4 text-xs">
          <button
            type="button"
            onClick={onGoToLogin}
            className="inline-flex items-center gap-1.5 text-slate-500 hover:text-indigo-600 transition-colors cursor-pointer"
          >
            <ArrowRight className="w-4 h-4" />
            <span>بازگشت به صفحه ورود صمد</span>
          </button>

          <span className="text-slate-400">
            سامانه ثبت‌نام دانشجویان ورودی جدید
          </span>
        </div>

        {/* Card Form */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
          {currentStep === 1 && (
            <Step1StudentInfo
              data={initialData}
              onChange={onUpdateData}
              onNext={handleNextFromStep1}
            />
          )}

          {currentStep === 2 && (
            <Step2AcademicResidence
              data={initialData}
              onChange={onUpdateData}
              onNext={handleNextFromStep2}
              onPrev={() => setCurrentStep(1)}
            />
          )}

          {currentStep === 3 && (
            <Step3SkillsInterests
              data={initialData}
              onChange={onUpdateData}
              onNext={handleNextFromStep3}
              onPrev={() => setCurrentStep(2)}
            />
          )}

          {currentStep === 4 && (
            <Step4PasswordSetup
              data={initialData}
              onChange={onUpdateData}
              onSubmit={handleFinalSubmit}
              onPrev={() => setCurrentStep(3)}
              isSubmitting={isSubmitting}
            />
          )}
        </div>

        {/* Footer Helper */}
        <div className="mt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2 border-t border-slate-200/60 pt-4">
          <p>
            قبلاً در پردیس ثبت‌نام کرده‌اید؟{' '}
            <button
              type="button"
              onClick={onGoToLogin}
              className="text-indigo-600 hover:text-indigo-800 font-bold hover:underline cursor-pointer"
            >
              وارد حساب کاربری خود شوید
            </button>
          </p>
          <span className="text-[11px] text-slate-400">
            واحد فناوری اطلاعات و ثبت‌نام الکترونیکی صمد
          </span>
        </div>
      </div>
    </div>
  );
};
