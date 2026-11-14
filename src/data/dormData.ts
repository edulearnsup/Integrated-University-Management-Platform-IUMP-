import { DormRoom, DormAllocation, NightAttendanceRecord, MaintenanceReport, RoomCleanlinessRecord, RoomFacilities } from '../types';

export const INITIAL_DORM_ROOMS: DormRoom[] = [
  // طبقه همکف
  {
    id: 1,
    roomNumber: 'G01',
    floor: 'همکف',
    capacity: 4,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۶/۲۵',
    description: 'اتاق ۴ تخته مجهز به یخچال - طبقه همکف',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'امرTagged ۵ فوت استاندارد',
      bedCount: 4,
      bedType: 'دو طبقه فلزی نردبان‌دار',
      carpetCount: 1,
      deskChairCount: 2,
      curtainsCount: 1,
      hasTv: false,
      hasAc: true,
      acType: 'کولر آبی سلولزی',
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: true,
        hasTrashCan: true
      },
      customEquipment: ['کمد دیواری ۴ درب با قفل مجزا', 'پریز محافظ برق'],
      specialNotes: 'تجهیزات و رنگ‌آمیزی در شهریور ۱۴۰۳ بررسی شده است.'
    }
  },
  {
    id: 2,
    roomNumber: 'G02',
    floor: 'همکف',
    capacity: 4,
    isActive: true,
    isCompleted: false, // تکمیل‌نشده
    description: 'اتاق ۴ تخته نورگیر - طبقه همکف (در انتظار تکمیل مشخصات و امکانات توسط مدیر)'
  },
  {
    id: 3,
    roomNumber: 'G03',
    floor: 'همکف',
    capacity: 4,
    isActive: false,
    isCompleted: false,
    description: 'در دست نقاشی و نوسازی تاسیسات (غیرفعال)'
  },

  // طبقه اول
  {
    id: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    capacity: 4,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۶/۲۸',
    description: 'اتاق ۴ تخته بالکن‌دار - طبقه اول',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'یخچال ایستکول ۶ فوت',
      bedCount: 4,
      bedType: 'تک طبقه چوبی با کفی فلزی',
      carpetCount: 2,
      deskChairCount: 4,
      curtainsCount: 2,
      hasTv: true,
      tvModel: 'تلویزیون ۳۲ اینچ LED دیواری',
      hasAc: true,
      acType: 'اسپلیت اینورتر سرمایشی گرمایشی',
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: true,
        hasTrashCan: true
      },
      customEquipment: ['چراغ مطالعه اختصاصی هر تخت', 'روفرشی نو'],
      specialNotes: 'اتاق نمونه بهداشتی و مجهز پردیس'
    }
  },
  {
    id: 5,
    roomNumber: '102',
    floor: 'طبقه اول',
    capacity: 4,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۷/۰۱',
    description: 'اتاق ۴ تخته مناسب مطالعه - طبقه اول',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'یخچال فیلور ۵ فوت',
      bedCount: 4,
      bedType: 'دو طبقه فلزی سنگین',
      carpetCount: 1,
      deskChairCount: 2,
      curtainsCount: 1,
      hasTv: false,
      hasAc: false,
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: false, // لگن نیاز دارد
        hasTrashCan: true
      },
      customEquipment: ['جا لباسی استیل پشت درب'],
      specialNotes: 'نیاز به تهیه یک عدد لگن شستشو'
    }
  },
  {
    id: 6,
    roomNumber: '103',
    floor: 'طبقه اول',
    capacity: 6,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۶/۲۹',
    description: 'اتاق ۶ تخته بزرگ ویژه نخبگان مهارتی',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'یخچال فریزر تکنو ۹ فوت',
      bedCount: 6,
      bedType: 'دو طبقه فلزی استاندارد صنعتی',
      carpetCount: 2,
      deskChairCount: 4,
      curtainsCount: 2,
      hasTv: true,
      tvModel: 'تلویزیون هوشمند ۴۰ اینچ',
      hasAc: true,
      acType: 'کولر گازی اسپلیت',
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: true,
        hasTrashCan: true
      },
      customEquipment: ['تخته وایت‌برد پژوهشی', 'مودم وای‌فای داخلی'],
      specialNotes: 'اتاق اختصاصی تیم‌های مسابقات ملی مهارت'
    }
  },
  {
    id: 7,
    roomNumber: '104',
    floor: 'طبقه اول',
    capacity: 4,
    isActive: true,
    isCompleted: false, // تکمیل نشده
    description: 'اتاق ۴ تخته رو به فضای سبز پردیس (در انتظار تکمیل مشخصات توسط مدیر)'
  },

  // طبقه دوم
  {
    id: 8,
    roomNumber: '201',
    floor: 'طبقه دوم',
    capacity: 4,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۷/۰۲',
    description: 'اتاق ۴ تخته مجهز به سیستم تهویه مطبوع',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'یخچال سینجر ۶ فوت',
      bedCount: 4,
      bedType: 'دو طبقه فلزی',
      carpetCount: 1,
      deskChairCount: 2,
      curtainsCount: 1,
      hasTv: false,
      hasAc: true,
      acType: 'اسپلیت ۲۴۰۰۰',
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: true,
        hasTrashCan: true
      },
      customEquipment: ['کمد چوبی ۲ درب']
    }
  },
  {
    id: 9,
    roomNumber: '202',
    floor: 'طبقه دوم',
    capacity: 4,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۷/۰۳',
    description: 'اتاق ۴ تخته - طبقه دوم',
    facilities: {
      refrigeratorCount: 1,
      refrigeratorModel: 'یخچال پارس ۵ فوت',
      bedCount: 4,
      bedType: 'دو طبقه فلزی',
      carpetCount: 1,
      deskChairCount: 2,
      curtainsCount: 1,
      hasTv: false,
      hasAc: false,
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: false, // جارو خراب شده
        hasBasin: true,
        hasTrashCan: true
      }
    }
  },
  {
    id: 10,
    roomNumber: '203',
    floor: 'طبقه دوم',
    capacity: 4,
    isActive: false,
    isCompleted: false,
    description: 'نیازمند تعمیرات تاسیسات سرمایشی (غیرفعال)'
  },
  {
    id: 11,
    roomNumber: '204',
    floor: 'طبقه دوم',
    capacity: 4,
    isActive: true,
    isCompleted: false,
    description: 'اتاق ۴ تخته انتهای راهرو - طبقه دوم (در انتظار تکمیل)'
  },
  {
    id: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    capacity: 10,
    isActive: true,
    isCompleted: true,
    facilitiesCompletedAt: '۱۴۰۳/۰۶/۲۵',
    description: 'خوابگاه ۱۰ نفره بزرگ - طبقه اول (دارای ۱۰ تخت رسمی و امکان پذیرش مازاد بر ظرفیت)',
    facilities: {
      refrigeratorCount: 2,
      refrigeratorModel: 'یخچال دونار ۱۰ فوت دو قلو',
      bedCount: 10,
      bedType: 'دو طبقه فلزی نردبان‌دار استاندارد',
      carpetCount: 3,
      deskChairCount: 6,
      curtainsCount: 3,
      hasTv: true,
      tvModel: 'تلویزیون ۴۳ اینچ هوشمند پردیس',
      hasAc: true,
      acType: 'اسپلیت ۲۴۰۰۰ اینورتر سرمایشی گرمایشی',
      hasHeater: true,
      hasShoeRack: true,
      hasMirror: true,
      cleaningTools: {
        hasBroom: true,
        hasBasin: true,
        hasTrashCan: true
      },
      secondaryItems: [
        { id: 'sec-1', name: 'سطل زباله پدالی بزرگ', count: 2, notes: 'ورودی و کنار سینک' },
        { id: 'sec-2', name: 'جارو و خاک‌انداز استاندارد', count: 2, notes: 'نظافت روزانه اتاق' },
        { id: 'sec-3', name: 'لگن شستشوی لباس', count: 2 },
        { id: 'sec-4', name: 'پریز محافظ برق چندراهی', count: 4 }
      ],
      customEquipment: ['کمد دیواری ۱۰ درب مجزا با قفل', 'میز مطالعه گروهی ۶ نفره'],
      specialNotes: 'اتاق نمونه بزرگ ۱۰ نفره ویژه دانشجویان برتر'
    }
  }
];

export const INITIAL_DORM_ALLOCATIONS: DormAllocation[] = [
  {
    id: 1,
    studentId: '4012130045',
    studentName: 'علی رضایی مقدم',
    nationalId: '0021487591',
    roomId: 4, // 101
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-20',
    allocationTimestamp: 1700000001000,
    isActive: true
  },
  {
    id: 2,
    studentId: '4012130047',
    studentName: 'امیرحسین کاظمی',
    nationalId: '0452318974',
    roomId: 4, // 101
    dormStatus: 'دارای شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-20',
    allocationTimestamp: 1700000002000,
    isActive: true
  },
  {
    id: 3,
    studentId: '4012130049',
    studentName: 'مهدی صادقی',
    nationalId: '0032541896',
    roomId: 5, // 102
    dormStatus: 'میهمان',
    studyShift: 'روزانه',
    allocationDate: '2026-09-22',
    allocationTimestamp: 1700000003000,
    isActive: true,
    guestDaysTotal: 7,
    guestStartDate: '1405/07/04',
    guestEndDate: '1405/07/11',
    guestDaysRemaining: 4
  },
  {
    id: 4,
    studentId: '4012130050',
    studentName: 'رضا باقری',
    nationalId: '0041258963',
    roomId: 1, // G01
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-21',
    allocationTimestamp: 1700000004000,
    isActive: true
  },
  {
    id: 5,
    studentId: '4012130046',
    studentName: 'سارا احمدی',
    nationalId: '0019845231',
    roomId: 8, // 201
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-21',
    allocationTimestamp: 1700000005000,
    isActive: true
  },
  {
    id: 6,
    studentId: '4012130048',
    studentName: 'زهرا محمدی',
    nationalId: '1278965412',
    roomId: 9, // 202
    dormStatus: 'دارای شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-21',
    allocationTimestamp: 1700000006000,
    isActive: true
  },

  // ========================================================
  // نمونه عملی اتاق ۱۰۰ (ظرفیت ۱۰ نفر - اسکان ۱۳ نفر با مازاد)
  // طبق سناریوی دقیق کاربر:
  // ۱۰ دانشجو روزانه زودتر اسکان یافته -> ۱۰ تخت رسمی (سبز)
  // ۱ دانشجو روزانه بعد از ظرفیت اسکان یافته -> روزانه مازاد (زرد)
  // ۱ دانشجو میهمان -> میهمان مازاد (نارنجی)
  // ۱ دانشجو شبانه -> شبانه مازاد (بنفش)
  // ========================================================
  {
    id: 7,
    studentId: '4012130101',
    studentName: 'پوریا علوی (روزانه - تخت ۱)',
    nationalId: '0011111111',
    roomId: 12, // اتاق ۱۰۰
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-15',
    allocationTimestamp: 1700000010000,
    isActive: true
  },
  {
    id: 8,
    studentId: '4012130102',
    studentName: 'سینا حسینی (روزانه - تخت ۲)',
    nationalId: '0011111112',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-15',
    allocationTimestamp: 1700000020000,
    isActive: true
  },
  {
    id: 9,
    studentId: '4012130103',
    studentName: 'آرش کمالی (روزانه - تخت ۳)',
    nationalId: '0011111113',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-15',
    allocationTimestamp: 1700000030000,
    isActive: true
  },
  {
    id: 10,
    studentId: '4012130104',
    studentName: 'مانی راد (روزانه - تخت ۴)',
    nationalId: '0011111114',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-16',
    allocationTimestamp: 1700000040000,
    isActive: true
  },
  {
    id: 11,
    studentId: '4012130105',
    studentName: 'کیان سلطانی (روزانه - تخت ۵)',
    nationalId: '0011111115',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-16',
    allocationTimestamp: 1700000050000,
    isActive: true
  },
  {
    id: 12,
    studentId: '4012130106',
    studentName: 'نوید افشار (روزانه - تخت ۶)',
    nationalId: '0011111116',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-17',
    allocationTimestamp: 1700000060000,
    isActive: true
  },
  {
    id: 13,
    studentId: '4012130107',
    studentName: 'سامان یزدانی (روزانه - تخت ۷)',
    nationalId: '0011111117',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-17',
    allocationTimestamp: 1700000070000,
    isActive: true
  },
  {
    id: 14,
    studentId: '4012130108',
    studentName: 'پارسا نادری (روزانه - تخت ۸)',
    nationalId: '0011111118',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-18',
    allocationTimestamp: 1700000080000,
    isActive: true
  },
  {
    id: 15,
    studentId: '4012130109',
    studentName: 'امید نجفی (روزانه - تخت ۹)',
    nationalId: '0011111119',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-18',
    allocationTimestamp: 1700000090000,
    isActive: true
  },
  {
    id: 16,
    studentId: '4012130110',
    studentName: 'بردیا فیاض (روزانه - تخت ۱۰)',
    nationalId: '0011111120',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-19',
    allocationTimestamp: 1700000100000,
    isActive: true
  },

  // نفر یازدهم: روزانه مازاد بر ظرفیت (چون بعد از تکمیل ظرفیت اسکان یافته)
  {
    id: 17,
    studentId: '4012130111',
    studentName: 'فرزاد اکبری (روزانه مازاد - بعد از ظرفیت ۱۰)',
    nationalId: '0011111121',
    roomId: 12,
    dormStatus: 'بدون شهریه',
    studyShift: 'روزانه',
    allocationDate: '2026-09-24',
    allocationTimestamp: 1700000200000, // زمان اسکان دیرتر از ۱۰ نفر اول
    isActive: true
  },

  // نفر دوازدهم: میهمان مازاد بر ظرفیت
  {
    id: 18,
    studentId: '4012130112',
    studentName: 'محسن کریمی (میهمان مازاد بر ظرفیت)',
    nationalId: '0011111122',
    roomId: 12,
    dormStatus: 'میهمان',
    studyShift: 'روزانه',
    allocationDate: '2026-09-25',
    allocationTimestamp: 1700000250000,
    isActive: true,
    guestDaysTotal: 10,
    guestStartDate: '1405/07/01',
    guestEndDate: '1405/07/11',
    guestDaysRemaining: 6
  },

  // نفر سیزدهم: شبانه مازاد بر ظرفیت
  {
    id: 19,
    studentId: '4012130113',
    studentName: 'شایان دهقان (شبانه نوبت دوم مازاد بر ظرفیت)',
    nationalId: '0011111123',
    roomId: 12,
    dormStatus: 'دارای شهریه',
    studyShift: 'شبانه (نوبت دوم)',
    allocationDate: '2026-09-26',
    allocationTimestamp: 1700000300000,
    isActive: true
  }
];

export const INITIAL_NIGHT_ATTENDANCE: NightAttendanceRecord[] = [
  {
    id: 1,
    studentId: '4012130045',
    roomId: 4,
    attendanceDate: '2026-09-30',
    status: 'حاضر',
    supervisorNationalId: '0099887766',
    notes: 'سرشماری ساعت ۲۲:۳۰ - حضور در اتاق تایید شد',
    recordedAt: '2026-09-30 22:30:00'
  },
  {
    id: 2,
    studentId: '4012130047',
    roomId: 4,
    attendanceDate: '2026-09-30',
    status: 'غایب',
    supervisorNationalId: '0099887766',
    notes: 'بدون برگه تردد تا ساعت ۲۳ حاضر نشد',
    recordedAt: '2026-09-30 23:00:00'
  },
  {
    id: 3,
    studentId: '4012130049',
    roomId: 5,
    attendanceDate: '2026-09-30',
    status: 'مرخصی',
    supervisorNationalId: '0099887766',
    notes: 'دارای برگه مرخصی ثبت‌شده در سرپرستی',
    recordedAt: '2026-09-30 22:15:00'
  },
  {
    id: 4,
    studentId: '4012130050',
    roomId: 1,
    attendanceDate: '2026-09-30',
    status: 'حاضر',
    supervisorNationalId: '0099887766',
    notes: 'حاضر در اتاق همکف',
    recordedAt: '2026-09-30 22:40:00'
  }
];

export interface AdminAccount {
  id: number;
  nationalId: string;
  password: string;
  fullName: string;
  role: 'manager' | 'supervisor';
  roleTitle: string;
}

export const ADMIN_ACCOUNTS: AdminAccount[] = [
  {
    id: 1,
    nationalId: '0011223344',
    password: 'Admin@12345',
    fullName: 'دکتر علیرضا حسینی',
    role: 'manager',
    roleTitle: 'مدیر دانشگاه و امور رفاهی صمد'
  },
  {
    id: 2,
    nationalId: '0099887766',
    password: 'Super@12345',
    fullName: 'مهندس کامران قنبری',
    role: 'supervisor',
    roleTitle: 'سرپرست شبانه خوابگاه صمد'
  }
];

export const INITIAL_SUPERVISORS: import('../types').SupervisorAccount[] = [
  {
    id: 1,
    nationalId: '0099887766',
    fullName: 'مهندس کامران قنبری',
    mobile: '09129876543',
    assignedDuty: 'سرپرست شیفت شب - طبقات همکف و اول',
    password: 'Super@12345',
    isActive: true,
    createdAt: '1405/06/15',
    shiftHours: '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شبانه)',
    shiftDays: 'شنبه تا چهارشنبه',
    supervisorCode: 'SUP-405-01',
    avatarUrl: '/src/assets/images/student_sample_avatar_1790759772433.jpg'
  },
  {
    id: 2,
    nationalId: '0088776655',
    fullName: 'آقای صادق میرزایی',
    mobile: '09351239874',
    assignedDuty: 'سرپرست کشیک شب - طبقه دوم',
    password: 'Super@12345',
    isActive: true,
    createdAt: '1405/06/20',
    shiftHours: '۲۲:۳۰ الی ۰۶:۳۰ (شیفت کشیک شب)',
    shiftDays: 'پنج‌شنبه و جمعه (آخر هفته)',
    supervisorCode: 'SUP-405-02'
  }
];

export const INITIAL_UNHOUSED_STUDENTS: import('../types').UnhousedStudent[] = [
  {
    studentId: '4012130051',
    fullName: 'پژمان مرادی',
    nationalId: '0923412589',
    mobile: '09151234589',
    fieldOfStudy: 'مهندسی برق - مکاترونیک',
    studyShift: 'روزانه',
    province: 'خراسان رضوی',
    city: 'مشهد',
    isForeignStudent: false,
    dormitoryNote: 'دانشجوی غیربومی روزانه، رتبه برتر کنکور سراسری، متقاضی اسکان در طبقه اول یا همکف',
    requestDate: '1405/07/02'
  },
  {
    studentId: '4012130052',
    fullName: 'نیما صادقی',
    nationalId: '2298745123',
    mobile: '09176543210',
    fieldOfStudy: 'مهندسی کامپیوتر - نرم‌افزار',
    studyShift: 'شبانه (نوبت دوم)',
    province: 'فارس',
    city: 'شیراز',
    isForeignStudent: false,
    dormitoryNote: 'متقاضی خوابگاه با پرداخت شهریه مصوب صمد',
    requestDate: '1405/07/05'
  },
  {
    studentId: '4012130053',
    fullName: 'سجاد کریمی',
    nationalId: '1365478921',
    mobile: '09149876543',
    fieldOfStudy: 'مهندسی مکانیک خودرو',
    studyShift: 'روزانه',
    province: 'آذربایجان شرقی',
    city: 'تبریز',
    isForeignStudent: false,
    dormitoryNote: 'دانشجوی نخبه مسابقات ملی مهارت',
    requestDate: '1405/07/07'
  }
];

export const INITIAL_ADMITTED_STUDENTS: import('../types').AdmittedStudent[] = [
  { id: 1, nationalId: '0021487591', studentId: '4012130045', fullName: 'علی رضایی مقدم', campus: 'پردیس فناوری', fieldOfStudy: 'مهندسی کامپیوتر - هوش مصنوعی و داده', isRegistered: true, createdAt: '1405/06/10' },
  { id: 2, nationalId: '0019845231', studentId: '4012130046', fullName: 'سارا احمدی', campus: 'پردیس مهارت', fieldOfStudy: 'گرافیک و ارتباط تصویری', isRegistered: true, createdAt: '1405/06/10' },
  { id: 3, nationalId: '0452318974', studentId: '4012130047', fullName: 'امیرحسین کاظمی', campus: 'پردیس فناوری', fieldOfStudy: 'مهندسی برق - الکترونیک و مخابرات', isRegistered: true, createdAt: '1405/06/11' },
  { id: 4, nationalId: '1278965412', studentId: '4012130048', fullName: 'زهرا محمدی', campus: 'پردیس مهارت', fieldOfStudy: 'معماری و دکوراسیون داخلی', isRegistered: true, createdAt: '1405/06/11' },
  { id: 5, nationalId: '0032541896', studentId: '4012130049', fullName: 'مهدی صادقی', campus: 'پردیس فناوری', fieldOfStudy: 'مهندسی صنایع و مدیریت سیستم', isRegistered: true, createdAt: '1405/06/12' },
  { id: 6, nationalId: '0041258963', studentId: '4012130050', fullName: 'رضا باقری', campus: 'پردیس فناوری', fieldOfStudy: 'مهندسی مکانیک و مکاترونیک', isRegistered: true, createdAt: '1405/06/12' },
  { id: 7, nationalId: '0078965412', studentId: '4012130051', fullName: 'نیما حسینی', campus: 'پردیس مهارت', fieldOfStudy: 'تکنولوژی طراحی و ساخت', isRegistered: false, createdAt: '1405/06/15' },
  { id: 8, nationalId: '0091234567', studentId: '4012130052', fullName: 'آرین قاسمی', campus: 'پردیس فناوری', fieldOfStudy: 'مهندسی هوش مصنوعی', isRegistered: false, createdAt: '1405/06/18' }
];

export const INITIAL_STUDENT_SKILLS: import('../types').StudentSkillInfo[] = [
  {
    studentId: '4012130045',
    fullName: 'علی رضایی مقدم',
    nationalId: '0021487591',
    mobile: '09121234567',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی کامپیوتر - هوش مصنوعی و داده',
    skills: ['پایتون (Python)', 'یادگیری عمیق (Deep Learning)', 'هوش مصنوعی', 'طراحی پایگاه‌داده MySQL', 'شبکه و لینوکس', 'توسعه وب React']
  },
  {
    studentId: '4012130046',
    fullName: 'سارا احمدی',
    nationalId: '0019845231',
    mobile: '09129876543',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'گرافیک و ارتباط تصویری',
    skills: ['فتوشاپ (Photoshop)', 'ایلوستریتور (Illustrator)', 'طراحی رابط کاربری UI/UX', 'طراحی هویت بصری و لوگو', 'تدوین ویدیو Premiere']
  },
  {
    studentId: '4012130047',
    fullName: 'امیرحسین کاظمی',
    nationalId: '0452318974',
    mobile: '09351239988',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی برق - الکترونیک و مخابرات',
    skills: ['طراحی مدارهای الکترونیک', 'میکروکنترلر AVR و ARM', 'آردوینو و اینترنت اشیا (IoT)', 'سیم‌کشی و برق صنعتی', 'برنامه‌نویسی C++']
  },
  {
    studentId: '4012130048',
    fullName: 'زهرا محمدی',
    nationalId: '1278965412',
    mobile: '09135554433',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'معماری و دکوراسیون داخلی',
    skills: ['اتوکد (AutoCAD)', 'تری‌دی مکس (3Ds Max)', 'طراحی پلان و دکوراسیون', 'رندرسازی با V-Ray', 'ماکت‌سازی تخصصی']
  },
  {
    studentId: '4012130049',
    fullName: 'مهدی صادقی',
    nationalId: '0032541896',
    mobile: '09194443322',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی صنایع و مدیریت سیستم',
    skills: ['مدیریت پروژه و MSP', 'اکسل پیشرفته و داشبورد مدیریتی', 'کنترل کیفیت آماری', 'برنامه‌ریزی تولید', 'پژوهش عملیاتی']
  },
  {
    studentId: '4012130050',
    fullName: 'رضا باقری',
    nationalId: '0041258963',
    mobile: '09127778899',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی مکانیک و مکاترونیک',
    skills: ['سالیدورکس (SolidWorks)', 'کتیا (CATIA)', 'مکاترونیک و هیدرولیک', 'تراشکاری و فرزکاری', 'تحلیل تنش ANSYS', 'جوشکاری و تاسیسات']
  }
];

export const INITIAL_STUDENT_FESTIVALS: import('../types').StudentFestivalInfo[] = [
  {
    studentId: '4012130045',
    fullName: 'علی رضایی مقدم',
    nationalId: '0021487591',
    mobile: '09121234567',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی کامپیوتر - هوش مصنوعی و داده',
    festivals: [
      { key: 'technical', title: 'جشنواره‌ها و المپیادهای فنی و اختراعات' },
      { key: 'robotics', title: 'رویدادهای رباتیک و هوش مصنوعی' }
    ]
  },
  {
    studentId: '4012130046',
    fullName: 'سارا احمدی',
    nationalId: '0019845231',
    mobile: '09129876543',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'گرافیک و ارتباط تصویری',
    festivals: [
      { key: 'cultural', title: 'جشنواره فرهنگی، ادبی و هنری' },
      { key: 'skillBased', title: 'جشنواره ملی مهارت و کارآفرینی' }
    ]
  },
  {
    studentId: '4012130047',
    fullName: 'امیرحسین کاظمی',
    nationalId: '0452318974',
    mobile: '09351239988',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی برق - الکترونیک و مخابرات',
    festivals: [
      { key: 'technical', title: 'جشنواره‌ها و المپیادهای فنی و اختراعات' },
      { key: 'robotics', title: 'رویدادهای رباتیک و هوش مصنوعی' },
      { key: 'religious', title: 'مسابقات و جشنواره‌های مذهبی و قرآنی' }
    ]
  },
  {
    studentId: '4012130048',
    fullName: 'زهرا محمدی',
    nationalId: '1278965412',
    mobile: '09135554433',
    campus: 'پردیس مهارت',
    fieldOfStudy: 'معماری و دکوراسیون داخلی',
    festivals: [
      { key: 'cultural', title: 'جشنواره فرهنگی، ادبی و هنری' },
      { key: 'skillBased', title: 'جشنواره ملی مهارت و کارآفرینی' }
    ]
  },
  {
    studentId: '4012130050',
    fullName: 'رضا باقری',
    nationalId: '0041258963',
    mobile: '09127778899',
    campus: 'پردیس فناوری',
    fieldOfStudy: 'مهندسی مکانیک و مکاترونیک',
    festivals: [
      { key: 'technical', title: 'جشنواره‌ها و المپیادهای فنی و اختراعات' },
      { key: 'skillBased', title: 'جشنواره ملی مهارت و کارآفرینی' }
    ]
  }
];

export const INITIAL_MAINTENANCE_REPORTS: MaintenanceReport[] = [
  {
    id: 1,
    roomId: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    equipmentName: 'شیر آب روشویی و لوله خروجی',
    maintenanceType: 'تعمیر',
    urgency: 'فوری',
    status: 'در انتظار اقدام',
    description: 'چکه‌کردن شدید شیر آب روشویی اتاق ۱۰۱ که موجب نشت آب به زیر کابینت شده است.',
    reportedBySupervisorId: '0012345678',
    reportedBySupervisorName: 'محمدرضا علیزاده',
    reportedAt: '۱۴۰۳/۰۷/۱۲ - ۲۳:۱۵'
  },
  {
    id: 2,
    roomId: 9,
    roomNumber: '202',
    floor: 'طبقه دوم',
    equipmentName: 'جارو و خاک‌انداز دسته بلند',
    maintenanceType: 'تعویض / خرید جدید',
    urgency: 'عادی',
    status: 'در انتظار اقدام',
    description: 'شکستگی دسته جارو و فرسودگی لبه خاک‌انداز اتاق ۲۰۲؛ دانشجویان درخواست جایگزینی دارند.',
    reportedBySupervisorId: '0023456789',
    reportedBySupervisorName: 'حسین مرادی',
    reportedAt: '۱۴۰۳/۰۷/۱۳ - ۰۰:۳۰'
  },
  {
    id: 3,
    roomId: 1,
    roomNumber: 'G01',
    floor: 'همکف',
    equipmentName: 'ترموستات یخچال امرسان',
    maintenanceType: 'تعمیر',
    urgency: 'عادی',
    status: 'رفع‌شده / تعمیر شد',
    description: 'یخچال به صورت یکسره کار می‌کرد و اتومات نمی‌کرد.',
    reportedBySupervisorId: '0012345678',
    reportedBySupervisorName: 'محمدرضا علیزاده',
    reportedAt: '۱۴۰۳/۰۷/۱۰ - ۲۲:۰۰',
    resolvedAt: '۱۴۰۳/۰۷/۱۱ - ۱۱:۳۰',
    resolvedByManagerName: 'دکتر علیرضا حسینی (مدیر رفاهی)',
    resolutionAction: 'تعمیر قطعه',
    resolutionCost: 350000,
    resolutionNotes: 'سرویسکار مجاز فراخوانده شد و رله استارت و ترموستات تنظیم و تعویض گردید.',
    invoiceNumber: 'INV-1403-8821',
    invoiceDate: '۱۴۰۳/۰۷/۱۱',
    contractorOrStoreName: 'خدمات فنی برودتی آریا (تکنسین: مهندس باقری)',
    invoiceNotes: 'تعویض رله و ترموستات دانفوس اصل + اجرت شارژ و سرویس'
  },
  {
    id: 4,
    roomId: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    equipmentName: 'پایه و کفی فلزی تخت شماره ۳',
    maintenanceType: 'تعمیر',
    urgency: 'عادی',
    status: 'رفع‌شده / تعمیر شد',
    description: 'لقی پیچ‌های شاسی طبقه دوم تخت شماره ۳ و صدای فنر کفی.',
    reportedBySupervisorId: '0012345678',
    reportedBySupervisorName: 'محمدرضا علیزاده',
    reportedAt: '۱۴۰۳/۰۷/۰۸ - ۲۲:۴۵',
    resolvedAt: '۱۴۰۳/۰۷/۰۹ - ۱۰:۱۵',
    resolvedByManagerName: 'دکتر علیرضا حسینی (مدیر رفاهی)',
    resolutionAction: 'تعمیر قطعه',
    resolutionCost: 180000,
    resolutionNotes: 'شاسی جوشکاری تقویت شد و پیچ‌های فولادی نو نصب گردید.',
    invoiceNumber: 'INV-1403-8790',
    invoiceDate: '۱۴۰۳/۰۷/۰۹',
    contractorOrStoreName: 'کارگاه جوشکاری و اسکلت فلزی البرز',
    invoiceNotes: 'تقویت پایه تخت خواب دو طبقه و تعویض ۴ عدد پیچ مهره شش‌گوش'
  },
  {
    id: 5,
    roomId: 8,
    roomNumber: '201',
    floor: 'طبقه دوم',
    equipmentName: 'سطل زباله پدالی بزرگ استیل',
    maintenanceType: 'تعویض / خرید جدید',
    urgency: 'کم‌اهمیت',
    status: 'رفع‌شده / خریداری و تعویض شد',
    description: 'شکستگی پدال و لولای درب سطل پلاستیکی قبلی.',
    reportedBySupervisorId: '0023456789',
    reportedBySupervisorName: 'حسین مرادی',
    reportedAt: '۱۴۰۳/۰۷/۰۵ - ۲۳:۰۰',
    resolvedAt: '۱۴۰3/۰۷/۰۶ - ۱۵:۳۰',
    resolvedByManagerName: 'دکتر علیرضا حسینی (مدیر رفاهی)',
    resolutionAction: 'خرید و جایگزینی تجهیز نو',
    resolutionCost: 420000,
    resolutionNotes: 'یک عدد سطل زباله پدالی تمام استیل ۲۰ لیتری با مخزن مجزا خریداری و تحویل اتاق شد.',
    invoiceNumber: 'INV-1403-8742',
    invoiceDate: '۱۴۰۳/۰۷/۰۶',
    contractorOrStoreName: 'فروشگاه تجهیزات اداری و بهداشتی صبا',
    invoiceNotes: 'سطل پدالی استیل ۲۰ لیتری ضدزنگ با ضمانت ۱ ساله'
  }
];

export const INITIAL_CLEANLINESS_RECORDS: RoomCleanlinessRecord[] = [
  // تاریخ ۱۴۰۳/۰۷/۱۴ (امروز)
  {
    id: 1,
    roomId: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۴',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'نظافت استثنایی اتاق ۱۰ نفره، کف و راهرو کاملا براق، تفکیک زباله انجام شد',
    recordedAt: '۱۴۰۳/۰۷/۱۴ - ۲۲:۳۰'
  },
  {
    id: 2,
    roomId: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۴',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'چیدمان فوق‌العاده منظم تخت‌ها و نظافت یخچال',
    recordedAt: '۱۴۰۳/۰۷/۱۴ - ۲۲:۴۰'
  },
  {
    id: 3,
    roomId: 5,
    roomNumber: '102',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۴',
    grade: 'خوب',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'وضعیت عمومی خوب و رضایت‌بخش',
    recordedAt: '۱۴۰۳/۰۷/۱۴ - ۲۲:۵۰'
  },
  {
    id: 4,
    roomId: 9,
    roomNumber: '202',
    floor: 'طبقه دوم',
    inspectionDate: '۱۴۰۳/۰۷/۱۴',
    grade: 'ضعیف',
    supervisorNationalId: '0023456789',
    supervisorName: 'حسین مرادی',
    notes: 'کف نامرتب و انباشت ظروف کثیف؛ اخطار کتبی دوم صادر شد',
    recordedAt: '۱۴۰۳/۰۷/۱۴ - ۲۳:۰۰'
  },

  // تاریخ ۱۴۰۳/۰۷/۱۳
  {
    id: 5,
    roomId: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۳',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'تخت‌ها آنکادر و تهویه اتاق بسیار مطبوع',
    recordedAt: '۱۴۰۳/۰۷/۱۳ - ۲۲:۲۰'
  },
  {
    id: 6,
    roomId: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۳',
    grade: 'خوب',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'کف تمیز، کتاب‌های روی میز اندکی نامرتب',
    recordedAt: '۱۴۰۳/۰۷/۱۳ - ۲۲:۳۵'
  },
  {
    id: 7,
    roomId: 8,
    roomNumber: '201',
    floor: 'طبقه دوم',
    inspectionDate: '۱۴۰۳/۰۷/۱۳',
    grade: 'متوسط',
    supervisorNationalId: '0023456789',
    supervisorName: 'حسین مرادی',
    notes: 'تذکر بابت پر بودن سطل زباله',
    recordedAt: '۱۴۰۳/۰۷/۱۳ - ۲۳:۱۰'
  },

  // تاریخ ۱۴۰۳/۰۷/۱۲
  {
    id: 8,
    roomId: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'بهداشت کامل و همکاری عالی دانشجویان اتاق ۱۰۰',
    recordedAt: '۱۴۰۳/۰۷/۱۲ - ۲۲:۱۵'
  },
  {
    id: 9,
    roomId: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'نظم بسیار عالی تخت‌ها، کف تمیز و بدون زباله',
    recordedAt: '۱۴۰۳/۰۷/۱۲ - ۲۳:۴۰'
  },
  {
    id: 10,
    roomId: 5,
    roomNumber: '102',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'خوب',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'وضعیت عمومی خوب',
    recordedAt: '۱۴۰۳/۰۷/۱۲ - ۲۳:۴۵'
  },
  {
    id: 11,
    roomId: 6,
    roomNumber: '103',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'اتاق نمونه نخبگان با چیدمان استاندارد و تهویه عالی',
    recordedAt: '۱۴۰۳/۰۷/۱۲ - ۲۳:۵۰'
  },
  {
    id: 12,
    roomId: 8,
    roomNumber: '201',
    floor: 'طبقه دوم',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'متوسط',
    supervisorNationalId: '0023456789',
    supervisorName: 'حسین مرادی',
    notes: 'تذکر شفاهی بابت شستشوی ظروف',
    recordedAt: '۱۴۰۳/۰۷/۱۳ - ۰۰:۱۰'
  },
  {
    id: 13,
    roomId: 9,
    roomNumber: '202',
    floor: 'طبقه دوم',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'ضعیف',
    supervisorNationalId: '0023456789',
    supervisorName: 'حسین مرادی',
    notes: 'انباشت کیسه‌های زباله پشت درب و کف نامرتب',
    recordedAt: '۱۴۰۳/۰۷/۱۳ - ۰۰:۲۰'
  },
  {
    id: 14,
    roomId: 1,
    roomNumber: 'G01',
    floor: 'همکف',
    inspectionDate: '۱۴۰۳/۰۷/۱۲',
    grade: 'خوب',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'کف تی کشیده شده و مرتب',
    recordedAt: '۱۴۰۳/۰۷/۱۲ - ۲۳:۳۰'
  },

  // تاریخ ۱۴۰۳/۰۷/۱۰
  {
    id: 15,
    roomId: 12,
    roomNumber: '100',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۰',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'نظافت عالی و منظم',
    recordedAt: '۱۴۰۳/۰۷/۱۰ - ۲۲:۳۰'
  },
  {
    id: 16,
    roomId: 4,
    roomNumber: '101',
    floor: 'طبقه اول',
    inspectionDate: '۱۴۰۳/۰۷/۱۰',
    grade: 'عالی',
    supervisorNationalId: '0012345678',
    supervisorName: 'محمدرضا علیزاده',
    notes: 'اتاق بسیار پاکیزه و مرتب',
    recordedAt: '۱۴۰۳/۰۷/۱۰ - ۲۲:۴۵'
  },
  {
    id: 17,
    roomId: 9,
    roomNumber: '202',
    floor: 'طبقه دوم',
    inspectionDate: '۱۴۰۳/۰۷/۱۰',
    grade: 'متوسط',
    supervisorNationalId: '0023456789',
    supervisorName: 'حسین مرادی',
    notes: 'نیاز به نظافت عمیق‌تر کف اتاق',
    recordedAt: '۱۴۰۳/۰۷/۱۰ - ۲۳:۱۵'
  }
];

export const INITIAL_FESTIVAL_CALLS: import('../types').FestivalCallAnnouncement[] = [
  {
    id: 1,
    title: 'فراخوان تشکیل تیم منتخب فوتبال و فوتسال دانشگاه ملی مهارت جهت المپیاد کشوری',
    targetCategory: 'sports',
    targetSportsTeam: 'تیم فوتبال',
    content: 'دانشجویان دارای مهارت در رشته فوتبال چمنی و سالنی که در فرم ثبت‌نام گزینه تیم فوتبال را انتخاب کرده‌اند، جهت تست فنی و انتخابی در زمین چمن پردیس حضور یابند.',
    startDate: '۱۴۰۵/۰۷/۱۵',
    deadlineDate: '۱۴۰۵/۰۸/۱۰',
    deadlineTime: '۱۸:۰۰',
    issuedAt: '۱۴۰۵/۰۷/۱۴ - ۰۹:۰۰',
    issuedBy: 'مدیر تربیت بدنی و امور رفاهی دانشگاه'
  },
  {
    id: 2,
    title: 'فراخوان تست آمادگی جسمانی و عضویت در تیم والیبال پردیس',
    targetCategory: 'sports',
    targetSportsTeam: 'تیم والیبال',
    content: 'علاقه‌مندان و ثبت‌نام‌کنندگان تیم والیبال دانشگاه جهت سنجش مهارتی و تمرینات هفتگی در سالن ورزشی صمد حضور به‌هم رسانند.',
    startDate: '۱۴۰۵/۰۷/۱۸',
    deadlineDate: '۱۴۰۵/۰۸/۰۵',
    deadlineTime: '۲۰:۰۰',
    issuedAt: '۱۴۰۵/۰۷/۱۴ - ۱۰:۳۰',
    issuedBy: 'اداره تربیت بدنی پردیس'
  },
  {
    id: 3,
    title: 'فراخوان بیست و هشتمین جشنواره ملی قرآن، عترت و معارف اسلامی دانشگاهیان',
    targetCategory: 'religious',
    content: 'دعوت از نخبگان قرآنی و پژوهشگران معارف دینی جهت ثبت‌نام و ارسال مقاله در بخش‌های آوایی، معارفی، ادبی و هنری جشنواره ملی عترت.',
    startDate: '۱۴۰۵/۰۸/۰۱', // ۵ روز دیگر (آینده - بردر زرد)
    deadlineDate: '۱۴۰۵/۰۸/۱۵',
    deadlineTime: '۲۳:۵۹',
    issuedAt: '۱۴۰۵/۰۷/۲۲ - ۱۱:۰۰',
    issuedBy: 'معاونت فرهنگی و دانشجویی دانشگاه'
  },
  {
    id: 4,
    title: 'فراخوان المپیاد رباتیک و چالش‌های کدنویسی هوش مصنوعی (Hackathon 2026)',
    targetCategory: 'robotics',
    content: 'تشکیل تیم‌های رباتیک و برنامه‌نویسی برای حل چالش‌های پردازش داده و بینایی ماشین؛ شروع ثبت‌نام از اواسط آبان‌ماه به‌صورت آنلاین.',
    startDate: '۱۴۰۵/۰۸/۰۵', // ۱۰ روز دیگر (آینده - بردر زرد)
    deadlineDate: '۱۴۰۵/۰۸/۲۵',
    deadlineTime: '۱۶:۰۰',
    issuedAt: '۱۴۰۵/۰۷/۲۴ - ۱۴:۰۰',
    issuedBy: 'مرکز نوآوری و رشد مهارت پردیس'
  },
  {
    id: 5,
    title: 'فراخوان رویداد هنرهای تجسمی و عکاسی خلاق صمد (دوره تابستان)',
    targetCategory: 'cultural',
    content: 'برگزاری نمایشگاه فصلی آثار برگزیده دانشجویان در نگارخانه مرکزی دانشگاه ملی مهارت. مهلت این دوره خاتمه یافته و آثار در دست داوری است.',
    startDate: '۱۴۰۵/۰۶/۱۰',
    deadlineDate: '۱۴۰۵/۰۷/۱۰', // پایان‌یافته (خاکستری - نباید حذف شود)
    deadlineTime: '۱۸:۰۰',
    issuedAt: '۱۴۰۵/۰۶/۰۵ - ۱۶:۳۰',
    issuedBy: 'کمیته فرهنگی و اجتماعی صمد'
  }
];
