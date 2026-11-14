import { CampusType } from '../types';
import { toEnglishDigits } from '../utils/validators';

export interface AdmittedStudent {
  id: number;
  nationalId: string;
  studentId: string;
  fullName: string;
  campus: CampusType;
  fieldOfStudy: string;
  isRegistered: boolean;
}

/**
 * جدول منبع پذیرفته‌شدگان (admitted_students)
 * این جدول شبیه‌ساز جدول مرجع در دیتابیس MySQL است.
 * دانشجو تنها در صورتی مجاز به ثبت‌نام است که کد ملی و کد دانشجویی او در این جدول وجود داشته و با هم تطابق داشته باشند.
 */
export const INITIAL_ADMITTED_STUDENTS: AdmittedStudent[] = [
  {
    id: 1,
    nationalId: '0021487591',
    studentId: '4012130045',
    fullName: 'علی رضایی مقدم',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی کامپیوتر - هوش مصنوعی و داده',
    isRegistered: false
  },
  {
    id: 2,
    nationalId: '0019845231',
    studentId: '4012130046',
    fullName: 'سارا احمدی',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'گرافیک و طراحی چندرسانه‌ای',
    isRegistered: false
  },
  {
    id: 3,
    nationalId: '0452318974',
    studentId: '4012130047',
    fullName: 'امیرحسین کاظمی',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی برق - الکترونیک و مخابرات',
    isRegistered: false
  },
  {
    id: 4,
    nationalId: '1278965412',
    studentId: '4012130048',
    fullName: 'زهرا محمدی',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'معماری و دکوراسیون داخلی',
    isRegistered: false
  },
  {
    id: 5,
    nationalId: '0032541896',
    studentId: '4012130049',
    fullName: 'مهدی صادقی',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی صنایع و مدیریت سیستم',
    isRegistered: false
  }
];

export interface ValidationMatchResult {
  matched: boolean;
  student?: AdmittedStudent;
  errorCode?: 'NOT_FOUND' | 'MISMATCH' | 'NATIONAL_ID_INVALID' | 'STUDENT_ID_INVALID' | 'ALREADY_REGISTERED';
  errorMessage?: string;
}

/**
 * بررسی تطابق کد ملی و شماره دانشجویی با جدول منبع پذیرفته‌شدگان
 */
export function verifyStudentWithSourceDb(
  nationalIdInput: string,
  studentIdInput: string,
  db: AdmittedStudent[] = INITIAL_ADMITTED_STUDENTS
): ValidationMatchResult {
  const cleanNationalId = toEnglishDigits(nationalIdInput).trim();
  const cleanStudentId = toEnglishDigits(studentIdInput).trim();

  if (!cleanNationalId || !cleanStudentId) {
    return {
      matched: false,
      errorCode: 'NOT_FOUND',
      errorMessage: 'لطفاً هر دو فیلد کد ملی و شماره دانشجویی را وارد فرمایید.'
    };
  }

  // بررسی تطابق دقیق هر دو با یکدیگر
  const exactMatch = db.find(
    (s) => s.nationalId === cleanNationalId && s.studentId === cleanStudentId
  );

  if (exactMatch) {
    if (exactMatch.isRegistered) {
      return {
        matched: false,
        student: exactMatch,
        errorCode: 'ALREADY_REGISTERED',
        errorMessage: `دانشجو «${exactMatch.fullName}» قبلاً در سامانه ثبت‌نام قطعی انجام داده است. لطفاً از فرم ورود وارد شوید.`
      };
    }

    return {
      matched: true,
      student: exactMatch
    };
  }

  // اگر تطابق دقیق پیدا نشد، بررسی می‌کنیم مشکل کجاست:
  const nationalExists = db.find((s) => s.nationalId === cleanNationalId);
  const studentExists = db.find((s) => s.studentId === cleanStudentId);

  if (nationalExists && studentExists) {
    return {
      matched: false,
      errorCode: 'MISMATCH',
      errorMessage: `عدم تطابق هویتی: کد ملی «${cleanNationalId}» متعلق به دانشجو «${nationalExists.fullName}» است، اما شماره دانشجویی «${cleanStudentId}» متعلق به دانشجو «${studentExists.fullName}» می‌باشد! هر دو کد باید مربوط به یک دانشجو باشند.`
    };
  }

  if (nationalExists && !studentExists) {
    return {
      matched: false,
      errorCode: 'STUDENT_ID_INVALID',
      errorMessage: `کد ملی «${cleanNationalId}» در جدول منبع موجود است، اما شماره دانشجویی «${cleanStudentId}» در لیست پذیرفته‌شدگان ثبت نشده است.`
    };
  }

  if (!nationalExists && studentExists) {
    return {
      matched: false,
      errorCode: 'NATIONAL_ID_INVALID',
      errorMessage: `شماره دانشجویی «${cleanStudentId}» در جدول منبع موجود است، اما کد ملی وارد شده «${cleanNationalId}» با اطلاعات این شماره دانشجویی مطابقت ندارد.`
    };
  }

  return {
    matched: false,
    errorCode: 'NOT_FOUND',
    errorMessage: `هیچ اطلاعاتی با کد ملی «${cleanNationalId}» و شماره دانشجویی «${cleanStudentId}» در جدول منبع پذیرفته‌شدگان صمد یافت نشد.`
  };
}
