import { PasswordValidationResult } from '../types';

/**
 * Converts Persian and Arabic digits to standard ASCII digits
 */
export function toEnglishDigits(str: string): string {
  if (!str) return '';
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

  let result = str;
  for (let i = 0; i < 10; i++) {
    result = result.replace(new RegExp(persianDigits[i], 'g'), i.toString());
    result = result.replace(new RegExp(arabicDigits[i], 'g'), i.toString());
  }
  return result;
}

/**
 * Formats numbers into Persian digits for display
 */
export function toPersianDigits(num: string | number): string {
  if (num === null || num === undefined) return '';
  const str = num.toString();
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return str.replace(/[0-9]/g, (w) => persianDigits[+w]);
}

/**
 * Evaluates student password based on the user's specific rules:
 * - حروف کوچک و بزرگ لاتین
 * - حداقل یک کاراکتر خاص (symbol)
 * - حداقل 8 حرف
 */
export function validatePassword(password: string): PasswordValidationResult {
  const hasMinLength = password.length >= 8;
  const hasLowercase = /[a-z]/.test(password);
  const hasUppercase = /[A-Z]/.test(password);
  // Special characters: @$!%*?&#^()_-+=~`{}[]|:;"'<>,./? etc.
  const hasSpecialChar = /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?`~]/.test(password);
  const hasNumber = /[0-9]/.test(password);

  const criteriaMet = [
    hasMinLength,
    hasLowercase,
    hasUppercase,
    hasSpecialChar,
    hasNumber
  ].filter(Boolean).length;

  const isValid = hasMinLength && hasLowercase && hasUppercase && hasSpecialChar;

  let strengthLabel: PasswordValidationResult['strengthLabel'] = 'خیلی ضعیف';
  let strengthScore = 0;

  if (password.length === 0) {
    strengthLabel = 'خیلی ضعیف';
    strengthScore = 0;
  } else if (!hasMinLength) {
    strengthLabel = 'کوتاه و نامناسب';
    strengthScore = 1;
  } else if (criteriaMet <= 2) {
    strengthLabel = 'ضعیف';
    strengthScore = 1;
  } else if (criteriaMet === 3 || criteriaMet === 4) {
    strengthLabel = isValid ? 'متوسط و قابل قبول' : 'متوسط (ناقص)';
    strengthScore = 2;
  } else if (criteriaMet === 5) {
    strengthLabel = password.length >= 12 ? 'بسیار امن' : 'قوی';
    strengthScore = password.length >= 12 ? 4 : 3;
  }

  // Construct helpful Arabic/Persian error message if invalid
  let errorMessage: string | undefined = undefined;
  if (password.length > 0 && !isValid) {
    const missing: string[] = [];
    if (!hasMinLength) missing.push('حداقل ۸ کاراکتر');
    if (!hasLowercase) missing.push('حرف کوچک انگلیسی (a-z)');
    if (!hasUppercase) missing.push('حرف بزرگ انگلیسی (A-Z)');
    if (!hasSpecialChar) missing.push('حداقل یک علامت/کاراکتر خاص مانند (@، #، $، %، !)');

    errorMessage = `رمز عبور ضعیف یا ناقص است. موارد باقیمانده: ${missing.join('، ')}`;
  }

  return {
    hasMinLength,
    hasLowercase,
    hasUppercase,
    hasSpecialChar,
    hasNumber,
    isValid,
    strengthScore,
    strengthLabel: strengthLabel as any,
    errorMessage
  };
}

/**
 * Basic Iranian National ID syntax check
 */
export function isValidNationalId(code: string): boolean {
  const clean = toEnglishDigits(code).trim();
  if (!/^\d{10}$/.test(clean)) return false;
  // All repetitive digits check (0000000000, 1111111111, etc.)
  if (/^(\d)\1{9}$/.test(clean)) return false;
  return true;
}
