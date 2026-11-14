export type CampusType = 'پردیس فناوری' | 'پردیس مهارت';

export type ShiftType = 'روزانه' | 'شبانه (نوبت دوم)';

export const UNIVERSITY_SPORTS_TEAMS = [
  'تیم فوتبال',
  'تیم فوتسال',
  'تیم والیبال',
  'تیم بسکتبال',
  'تیم تنیس روی میز',
  'تیم بدمینتون',
  'تیم کشتی آزاد و فرنگی',
  'تیم شنا',
  'تیم شطرنج',
  'تیم دو و میدانی',
  'تیم هندبال',
  'تیم کوهنوردی'
] as const;

export type UniversitySportsTeam = typeof UNIVERSITY_SPORTS_TEAMS[number];

export interface StudentRegistrationData {
  // Step 1: Student Information
  photoUrl: string;
  nationalId: string;
  studentId: string;
  fullName: string;
  mobile: string;
  campus: CampusType;

  // Step 2: Academic and Residence
  fieldOfStudy: string;
  studyShift: ShiftType;
  province: string;
  city: string;
  isForeignStudent: boolean;
  foreignStudentCode?: string;
  residentialAddress: string;
  requestDormitory: boolean;
  dormitoryNote?: string;

  // Step 3: Skills & Festival Interests
  skills: string[];
  festivalInterests: {
    technical: boolean; // جشنواره های فنی
    skillBased: boolean; // جشنواره های مهارتی
    religious: boolean; // مسابقات و جشنواره های مذهبی و قرآنی
    cultural: boolean; // جشنواره فرهنگی و هنری
    robotics: boolean; // رویدادهای رباتیک و هوش مصنوعی
    sports: boolean; // جشنواره‌های ورزشی و مسابقات پردیس
  };
  sportsTeams?: string[]; // تیم‌های ورزشی انتخابی دانشجو

  // Step 4: Password Setup
  password: string;
  confirmPassword: string;
  acceptedTerms: boolean;
}

export interface PasswordValidationResult {
  hasMinLength: boolean;
  hasLowercase: boolean;
  hasUppercase: boolean;
  hasSpecialChar: boolean;
  hasNumber: boolean;
  isValid: boolean;
  strengthScore: number; // 0 to 4
  strengthLabel: string;
  errorMessage?: string;
}

export type DormStatusType = 'میهمان' | 'دارای شهریه' | 'بدون شهریه';

export type DormFloor = 'همکف' | 'طبقه اول' | 'طبقه دوم';

export interface CustomSecondaryItem {
  id: string;
  name: string; // نام قلم فرعی مثل: سطل آشغال، جارو و خاک‌انداز، پنکه، اتو و...
  count: number;
  notes?: string;
}

export interface RoomFacilities {
  // ۱. امکانات اصلی خوابگاه (Mandatory Core Amenities)
  // هر خوابگاه باید حتماً آن موارد را دارا باشد: یخچال، کمد، فرش
  refrigeratorCount: number;
  refrigeratorModel?: string;
  wardrobeCount?: number; // کمد اختصاصی / لباس
  carpetCount: number; // فرش / موکت

  // ظرفیت و تخت جزو الزامات اولیه اتاق است (نه در لیست امکانات)
  // در بخش ویرایش امکانات اتاق فقط ظرفیت اتاق (تعداد تخت) ویرایش می‌شود
  bedCount: number;
  bedType?: string; // "دو طبقه فلزی" | "تک طبقه چوبی"

  // ۲. امکانات فرعی خوابگاه (توسط مدیر به صورت دستی وارد می‌شود)
  secondaryItems?: CustomSecondaryItem[];

  // سازگاری با امکانات جانبی پیشین
  deskChairCount?: number;
  curtainsCount?: number;
  hasTv?: boolean;
  tvModel?: string;
  hasAc?: boolean;
  acType?: string;
  hasHeater?: boolean;
  hasShoeRack?: boolean;
  hasMirror?: boolean;
  cleaningTools?: {
    hasBroom: boolean;
    hasBasin: boolean;
    hasTrashCan: boolean;
  };
  customEquipment?: string[];
  specialNotes?: string;
  updatedAt?: string;
}

export interface DormRoom {
  id: number;
  roomNumber: string;
  floor: DormFloor;
  capacity: number;
  isActive: boolean;
  description?: string;
  isCompleted?: boolean; // آیا اطلاعات و تجهیزات خوابگاه تکمیل شده است یا تکمیل‌نشده
  facilitiesCompletedAt?: string;
  facilities?: RoomFacilities;
}

export type MaintenanceUrgency = 'فوری' | 'عادی' | 'کم‌اهمیت';
export type MaintenanceStatus = 'در انتظار اقدام' | 'در حال تعمیر' | 'رفع‌شده / تعمیر شد' | 'رفع‌شده / خریداری و تعویض شد';
export type MaintenanceType = 'تعمیر' | 'تعویض / خرید جدید';

export interface MaintenanceReport {
  id: number;
  roomId: number;
  roomNumber: string;
  floor: DormFloor;
  equipmentName: string; // نام قطعه یا تجهیز مانند یخچال، شوفاژ، تخت و ...
  maintenanceType: MaintenanceType;
  urgency: MaintenanceUrgency;
  status: MaintenanceStatus;
  description: string;
  reportedBySupervisorId: string;
  reportedBySupervisorName: string;
  reportedAt: string; // تاریخ و زمان ثبت گزارش
  resolvedAt?: string; // تاریخ و زمان رفع عیب
  resolvedByManagerName?: string;
  resolutionAction?: 'تعمیر قطعه' | 'خرید و جایگزینی تجهیز نو';
  resolutionCost?: number;
  resolutionNotes?: string;
  // اطلاعات فاکتور و خرج و مخارج
  invoiceNumber?: string; // شماره فاکتور رسمی یا رسید
  invoiceDate?: string; // تاریخ صدور فاکتور
  contractorOrStoreName?: string; // نام تعمیرکار، تکنسین یا فروشگاه
  invoiceNotes?: string; // شرح اقلام و جزییات فاکتور
}

export type CleanlinessGrade = 'عالی' | 'خوب' | 'متوسط' | 'ضعیف';

export interface RoomCleanlinessRecord {
  id: number;
  roomId: number;
  roomNumber: string;
  floor: DormFloor;
  inspectionDate: string; // e.g. "۱۴۰۳/۰۷/۱۲"
  grade: CleanlinessGrade;
  supervisorNationalId: string;
  supervisorName: string;
  notes?: string;
  recordedAt: string; // timestamp
}

export interface RoomCleanlinessRanking {
  roomId: number;
  roomNumber: string;
  floor: DormFloor;
  totalInspections: number;
  averageScore: number; // 1 to 4
  scorePercentage?: number; // 0 to 100%
  gradeCounts: {
    excellent: number;
    good: number;
    average: number;
    poor: number;
  };
  rank: number;
  inspectionHistory?: RoomCleanlinessRecord[];
  dailyHistory?: { date: string; grade: CleanlinessGrade; supervisorName: string; notes?: string }[];
}

export type OccupantSurplusType = 
  | 'regular_bed' // دانشجوی روزانه دارای تخت رسمی
  | 'regular_surplus' // دانشجوی روزانه مازاد بر ظرفیت (فاقد تخت بر اساس اولویت زمان اسکان)
  | 'guest_surplus' // دانشجوی میهمان مازاد بر ظرفیت
  | 'evening_surplus'; // دانشجوی شبانه مازاد بر ظرفیت

export interface RoomOccupantWithBedInfo extends DormAllocation {
  hasBed: boolean;
  bedNumber?: number; // 1 to capacity
  surplusType: OccupantSurplusType;
  surplusRank?: number; // نفر چندم مازاد
}

export interface DormAllocation {
  id: number;
  studentId: string;
  studentName: string;
  nationalId: string;
  roomId: number;
  dormStatus: DormStatusType;
  studyShift?: ShiftType; // 'روزانه' | 'شبانه (نوبت دوم)' - اولویت اسکان: روزانه > میهمان > شبانه
  allocationDate: string;
  allocationTimestamp?: number; // ترتیب زمانی اسکان جهت تفکیک دانشجوی روزانه دارای تخت از روزانه مازاد
  isActive: boolean;
  guestDaysTotal?: number;
  guestStartDate?: string;
  guestEndDate?: string;
  guestDaysRemaining?: number;
}

export interface FestivalCallAnnouncement {
  id: number;
  title: string;
  targetCategory: 'all' | 'sports' | 'religious' | 'technical' | 'robotics' | 'cultural' | 'skillBased';
  targetSportsTeam?: string; // e.g. 'تیم فوتبال', 'تیم والیبال'
  content: string;
  startDate?: string; // e.g. "۱۴۰۵/۰۲/۰۱" یا "۱۴۰۵/۰۷/۲۸"
  deadlineDate: string; // e.g. "۱۴۰۵/۰۲/۱۵" یا "۱۴۰۵/۰۸/۰۵"
  deadlineTime?: string; // e.g. "۱۸:۰۰"
  issuedAt: string;
  issuedBy: string;
  status?: 'active' | 'upcoming' | 'expired' | 'draft' | string;
}

export type CleanlinessReportPeriod = 'daily' | 'weekly' | 'monthly';
export type MaintenanceReportPeriod = 'all' | 'weekly' | 'monthly';

export type AttendanceStatus = 'حاضر' | 'غایب' | 'مرخصی';

export interface NightAttendanceRecord {
  id: number;
  studentId: string;
  roomId: number;
  attendanceDate: string;
  status: AttendanceStatus;
  supervisorNationalId: string;
  notes?: string;
  recordedAt: string;
}

export interface SupervisorAccount {
  id: number;
  nationalId: string;
  fullName: string;
  mobile: string;
  assignedDuty: string;
  password?: string;
  isActive: boolean;
  createdAt: string;
  shiftHours?: string;
  shiftDays?: string;
  supervisorCode?: string;
  avatarUrl?: string;
}

export interface StudentSkillInfo {
  studentId: string;
  fullName: string;
  nationalId: string;
  mobile?: string;
  campus?: string;
  fieldOfStudy?: string;
  skills: string[];
}

export interface StudentFestivalInfo {
  studentId: string;
  fullName: string;
  nationalId: string;
  mobile?: string;
  campus?: string;
  fieldOfStudy?: string;
  festivals: {
    key: string;
    title: string;
  }[];
}

export interface UnhousedStudent {
  studentId: string;
  fullName: string;
  nationalId: string;
  mobile: string;
  fieldOfStudy: string;
  studyShift: ShiftType;
  province: string;
  city: string;
  isForeignStudent: boolean;
  dormitoryNote?: string;
  requestDate: string;
}

export interface AdmittedStudent {
  id: number;
  nationalId: string;
  studentId: string;
  fullName: string;
  campus: CampusType;
  fieldOfStudy: string;
  isRegistered: boolean;
  createdAt?: string;
}

export type UserRole = 'student' | 'manager' | 'supervisor';

export type ViewMode = 'landing' | 'login' | 'register' | 'success' | 'dashboard' | 'php_export' | 'manager_panel' | 'supervisor_panel';

