import React, { useState, useMemo } from 'react';
import {
  DormRoom, DormAllocation, DormFloor, DormStatusType,
  UnhousedStudent, SupervisorAccount, AdmittedStudent,
  NightAttendanceRecord, AttendanceStatus, CampusType,
  StudentSkillInfo, StudentFestivalInfo, MaintenanceReport,
  RoomCleanlinessRecord, CleanlinessGrade, RoomCleanlinessRanking,
  RoomOccupantWithBedInfo, FestivalCallAnnouncement, UNIVERSITY_SPORTS_TEAMS
} from '../types';
import {
  LayoutDashboard, UserCheck, Users, Building2, BedDouble,
  ClipboardList, ShieldCheck, Plus, CheckCircle2, AlertCircle,
  Power, LogOut, Layers, Trash2, Search, UserPlus, Phone, Lock,
  MapPin, Clock, Check, X, Shield, ArrowUpDown, CreditCard,
  Printer, FileText, ChevronRight, Download, Filter, Eye,
  Calendar, Sparkles, Hash, AlertTriangle, RefreshCw, Trophy,
  Cpu, Wrench, Star, Edit, QrCode, CheckCheck, HelpCircle,
  ShoppingCart, ShieldAlert, CheckSquare, Bell, Send, Megaphone, Receipt
} from 'lucide-react';
import { toPersianDigits } from '../utils/validators';
import { formatToPersianDateText, formatToPersianDateShort, addDaysToJalali, getTodayJalali } from '../utils/persianDate';
import { PersianDatePicker } from './PersianDatePicker';
import { SingleInvoiceModal } from './SingleInvoiceModal';
import { SupervisorCard } from './SupervisorCard';
import { RoomFacilitiesModal } from './RoomFacilitiesModal';
import { MaintenanceResolutionModal } from './MaintenanceResolutionModal';
import { CleanlinessPrintModal } from './CleanlinessPrintModal';
import { WeeklyMonthlyCleanlinessPrintModal } from './WeeklyMonthlyCleanlinessPrintModal';
import { MaintenanceReportPrintModal } from './MaintenanceReportPrintModal';
import { INITIAL_STUDENT_SKILLS, INITIAL_STUDENT_FESTIVALS, INITIAL_FESTIVAL_CALLS } from '../data/dormData';

export type ManagerTabType =
  | 'overview'
  | 'admitted'
  | 'unhoused'
  | 'rooms'
  | 'facilities'
  | 'maintenance'
  | 'cleanliness'
  | 'students'
  | 'attendance'
  | 'supervisors'
  | 'festivals'
  | 'skills';

interface ManagerDormPanelProps {
  rooms: DormRoom[];
  allocations: DormAllocation[];
  unhousedStudents: UnhousedStudent[];
  supervisors: SupervisorAccount[];
  admittedStudents?: AdmittedStudent[];
  attendanceRecords?: NightAttendanceRecord[];
  studentSkills?: StudentSkillInfo[];
  studentFestivals?: StudentFestivalInfo[];
  maintenanceReports?: MaintenanceReport[];
  cleanlinessRecords?: RoomCleanlinessRecord[];
  festivalCalls?: FestivalCallAnnouncement[];
  onUpdateRooms: (rooms: DormRoom[]) => void;
  onUpdateAllocations: (allocations: DormAllocation[]) => void;
  onUpdateUnhousedStudents: (students: UnhousedStudent[]) => void;
  onUpdateSupervisors: (supervisors: SupervisorAccount[]) => void;
  onUpdateAdmittedStudents?: (students: AdmittedStudent[]) => void;
  onUpdateMaintenanceReports?: (reports: MaintenanceReport[]) => void;
  onUpdateCleanlinessRecords?: (records: RoomCleanlinessRecord[]) => void;
  onUpdateFestivalCalls?: (calls: FestivalCallAnnouncement[]) => void;
  theme?: 'dark' | 'light';
  onToggleTheme?: () => void;
  onLogout: () => void;
}

export const ManagerDormPanel: React.FC<ManagerDormPanelProps> = ({
  rooms,
  allocations,
  unhousedStudents,
  supervisors,
  admittedStudents = [],
  attendanceRecords = [],
  studentSkills = INITIAL_STUDENT_SKILLS,
  studentFestivals = INITIAL_STUDENT_FESTIVALS,
  maintenanceReports = [],
  cleanlinessRecords = [],
  festivalCalls = INITIAL_FESTIVAL_CALLS,
  theme = 'dark',
  onToggleTheme,
  onUpdateRooms,
  onUpdateAllocations,
  onUpdateUnhousedStudents,
  onUpdateSupervisors,
  onUpdateAdmittedStudents,
  onUpdateMaintenanceReports,
  onUpdateCleanlinessRecords,
  onUpdateFestivalCalls,
  onLogout
}) => {
  // Navigation State
  const [activeTab, setActiveTab] = useState<ManagerTabType>('overview');

  // Notifications
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Filters & Search for Rooms
  const [activeFloorFilter, setActiveFloorFilter] = useState<'all' | DormFloor>('all');
  const [activeStatusFilter, setActiveStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
  const [hasFreeBedFilter, setHasFreeBedFilter] = useState<boolean>(false);
  const [roomStudentSearchQuery, setRoomStudentSearchQuery] = useState<string>('');

  // Add Room form state
  const [showAddRoom, setShowAddRoom] = useState(false);
  const [newRoomNumber, setNewRoomNumber] = useState('');
  const [newRoomFloor, setNewRoomFloor] = useState<DormFloor>('همکف');
  const [newRoomCapacity, setNewRoomCapacity] = useState(4);
  const [newRoomDesc, setNewRoomDesc] = useState('');

  // New Supervisor form state with Shift Days & Hours
  const [newSupFullName, setNewSupFullName] = useState('');
  const [newSupNationalId, setNewSupNationalId] = useState('');
  const [newSupMobile, setNewSupMobile] = useState('');
  const [newSupDuty, setNewSupDuty] = useState('سرپرست شیفت شب - طبقات ۱ و ۲');
  const [newSupShiftDays, setNewSupShiftDays] = useState('شنبه تا چهارشنبه');
  const [newSupShiftHours, setNewSupShiftHours] = useState('۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)');
  const [newSupPassword, setNewSupPassword] = useState('Super@12345');
  const [editingSupShift, setEditingSupShift] = useState<SupervisorAccount | null>(null);
  const [editShiftDays, setEditShiftDays] = useState('');
  const [editShiftHours, setEditShiftHours] = useState('');

  // New Admitted Student Form state
  const [newAdmNationalId, setNewAdmNationalId] = useState('');
  const [newAdmStudentId, setNewAdmStudentId] = useState('');
  const [newAdmFullName, setNewAdmFullName] = useState('');
  const [newAdmCampus, setNewAdmCampus] = useState<CampusType>('پردیس فناوری');
  const [newAdmField, setNewAdmField] = useState('');
  const [admittedSearch, setAdmittedSearch] = useState('');

  // Unhoused students search
  const [unhousedSearchQuery, setUnhousedSearchQuery] = useState('');

  // Housed students search & Student ID Card Modal
  const [housedSearchQuery, setHousedSearchQuery] = useState('');
  const [selectedStudentForCard, setSelectedStudentForCard] = useState<{
    studentId: string;
    fullName: string;
    nationalId: string;
    fieldOfStudy?: string;
    campus?: string;
    roomNumber?: string;
    floor?: string;
    dormStatus?: string;
    province?: string;
    city?: string;
    mobile?: string;
  } | null>(null);

  // Room Details Modal (students in that room with photo, province, etc.)
  const [selectedRoomForDetails, setSelectedRoomForDetails] = useState<DormRoom | null>(null);

  // Festivals Tab state
  const [festivalCategoryFilter, setFestivalCategoryFilter] = useState<string>('all');
  const [festivalSearchQuery, setFestivalSearchQuery] = useState<string>('');

  // Skills Live Search state
  const [skillLiveQuery, setSkillLiveQuery] = useState<string>('');

  // Allocation Modal for Unhoused Student (with guest stay in days)
  const [assigningStudent, setAssigningStudent] = useState<UnhousedStudent | null>(null);
  const [assignTargetRoomId, setAssignTargetRoomId] = useState<number>(0);
  const [assignDormStatus, setAssignDormStatus] = useState<DormStatusType>('بدون شهریه');
  const [assignGuestDays, setAssignGuestDays] = useState<number>(7);
  const [assignGuestStartDate, setAssignGuestStartDate] = useState<string>('۱۴۰۳/۰۷/۱۵');

  // Edit existing student allocation modal
  const [selectedStudentId, setSelectedStudentId] = useState<string | null>(null);
  const [editRoomId, setEditRoomId] = useState<number>(0);
  const [editDormStatus, setEditDormStatus] = useState<DormStatusType>('بدون شهریه');
  const [editGuestDays, setEditGuestDays] = useState<number>(7);

  // Supervisor Card Modal for Manager
  const [selectedSupervisorForCard, setSelectedSupervisorForCard] = useState<SupervisorAccount | null>(null);

  // Facilities & Room Completion state
  const [selectedRoomForFacilities, setSelectedRoomForFacilities] = useState<DormRoom | null>(null);
  const [facilityFloorFilter, setFacilityFloorFilter] = useState<'all' | DormFloor>('all');
  const [facilityCompletionFilter, setFacilityCompletionFilter] = useState<'all' | 'completed' | 'incomplete'>('all');
  const [facilitySearchQuery, setFacilitySearchQuery] = useState<string>('');

  // Cleanliness Tab State & Print Modals (Daily & Periodic Ranking)
  const [showCleanlinessPrintModal, setShowCleanlinessPrintModal] = useState<boolean>(false);
  const [showWeeklyMonthlyPrintModal, setShowWeeklyMonthlyPrintModal] = useState<boolean>(false);
  const [cleanlinessViewMode, setCleanlinessViewMode] = useState<'ranking' | 'daily'>('ranking');
  const [cleanlinessRankingPeriod, setCleanlinessRankingPeriod] = useState<'weekly' | 'monthly' | 'all'>('weekly');
  const [cleanlinessRankingSort, setCleanlinessRankingSort] = useState<'best_to_worst' | 'worst_to_best'>('best_to_worst');
  const [selectedCleanlinessDate, setSelectedCleanlinessDate] = useState<string>('۱۴۰۳/۰۷/۱۴');
  const [cleanlinessFloorFilter, setCleanlinessFloorFilter] = useState<'all' | DormFloor>('all');
  const [cleanlinessGradeFilter, setCleanlinessGradeFilter] = useState<'all' | CleanlinessGrade>('all');

  // Maintenance Reporting State & Print Modal
  const [selectedReportForResolution, setSelectedReportForResolution] = useState<MaintenanceReport | null>(null);
  const [selectedReportForSingleInvoice, setSelectedReportForSingleInvoice] = useState<MaintenanceReport | null>(null);
  const [showMaintenancePrintModal, setShowMaintenancePrintModal] = useState<boolean>(false);
  const [maintenancePeriodFilter, setMaintenancePeriodFilter] = useState<'all' | 'weekly' | 'monthly'>('all');
  const [maintenanceStatusFilter, setMaintenanceStatusFilter] = useState<'all' | 'pending' | 'resolved'>('all');
  const [maintenanceUrgencyFilter, setMaintenanceUrgencyFilter] = useState<'all' | 'فوری' | 'عادی' | 'کم‌اهمیت'>('all');
  const [maintenanceSearchQuery, setMaintenanceSearchQuery] = useState<string>('');

  // Festival Calls State & Add Call Modal
  const [localFestivalCalls, setLocalFestivalCalls] = useState<FestivalCallAnnouncement[]>(festivalCalls);
  const [showAddCallModal, setShowAddCallModal] = useState<boolean>(false);
  const [newCallTitle, setNewCallTitle] = useState<string>('');
  const [newCallCategory, setNewCallCategory] = useState<FestivalCallAnnouncement['targetCategory']>('sports');
  const [newCallSportsTeam, setNewCallSportsTeam] = useState<string>('تیم فوتبال');
  const [newCallContent, setNewCallContent] = useState<string>('');
  const [newCallStartDate, setNewCallStartDate] = useState<string>(() => getTodayJalali());
  const [newCallDeadline, setNewCallDeadline] = useState<string>(() => addDaysToJalali(getTodayJalali(), 10));
  const [newCallTime, setNewCallTime] = useState<string>('۱۸:۰۰');
  const [callFilter, setCallFilter] = useState<'all' | 'active' | 'upcoming' | 'expired'>('all');

  // تابع هوشمند تشخیص وضعیت فراخوان (فعال، آینده، پایان‌یافته) بر اساس تاریخ شروع و مهلت
  const getCallStatus = (call: FestivalCallAnnouncement): 'active' | 'upcoming' | 'expired' => {
    const normalize = (s?: string) => {
      if (!s) return '';
      const pDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
      let res = s;
      pDigits.forEach((p, idx) => {
        res = res.split(p).join(String(idx));
      });
      const m = res.match(/\d{4}\/\d{2}\/\d{2}/);
      return m ? m[0] : res.slice(0, 10);
    };

    const todayStr = getTodayJalali();
    const start = normalize(call.startDate);
    const end = normalize(call.deadlineDate);

    if (start && todayStr < start) {
      return 'upcoming';
    }
    if (end && todayStr > end) {
      return 'expired';
    }
    return 'active';
  };

  // Helper for Over-Capacity and Bed Priority:
  // اولویت ۱: دانشجویان روزانه (بر اساس زمان اسکان) -> تا سقف ظرفیت اتاق تخت می‌گیرند
  // اولویت ۲: روزانه مازاد (بدون تخت بر اساس اسکان دیرتر)
  // اولویت ۳: میهمان مازاد (بدون تخت)
  // اولویت ۴: شبانه مازاد (بدون تخت)
  const computeBedMatrix = (room: DormRoom, roomOccupants: DormAllocation[]) => {
    const dayStudents = roomOccupants
      .filter(a => a.dormStatus !== 'میهمان' && (a.studyShift === 'روزانه' || !a.studyShift))
      .sort((a, b) => (a.allocationTimestamp || a.id) - (b.allocationTimestamp || b.id));

    const guestStudents = roomOccupants
      .filter(a => a.dormStatus === 'میهمان')
      .sort((a, b) => (a.allocationTimestamp || a.id) - (b.allocationTimestamp || b.id));

    const eveningStudents = roomOccupants
      .filter(a => a.dormStatus !== 'میهمان' && a.studyShift === 'شبانه (نوبت دوم)')
      .sort((a, b) => (a.allocationTimestamp || a.id) - (b.allocationTimestamp || b.id));

    const result: RoomOccupantWithBedInfo[] = [];
    let nextBedNumber = 1;
    let surplusRank = 1;

    // ۱. دانشجویان روزانه بر اساس زمان اسکان زودتر تخت‌های مجاز را پر می‌کنند
    dayStudents.forEach((student) => {
      if (nextBedNumber <= room.capacity) {
        result.push({
          ...student,
          hasBed: true,
          bedNumber: nextBedNumber++,
          surplusType: 'regular_bed'
        });
      } else {
        result.push({
          ...student,
          hasBed: false,
          surplusType: 'regular_surplus',
          surplusRank: surplusRank++
        });
      }
    });

    // ۲. دانشجویان میهمان (در صورت پر بودن ظرفیت، مازاد می‌شوند)
    guestStudents.forEach((student) => {
      if (nextBedNumber <= room.capacity) {
        result.push({
          ...student,
          hasBed: true,
          bedNumber: nextBedNumber++,
          surplusType: 'regular_bed'
        });
      } else {
        result.push({
          ...student,
          hasBed: false,
          surplusType: 'guest_surplus',
          surplusRank: surplusRank++
        });
      }
    });

    // ۳. دانشجویان شبانه نوبت دوم (در صورت پر بودن ظرفیت، مازاد می‌شوند)
    eveningStudents.forEach((student) => {
      if (nextBedNumber <= room.capacity) {
        result.push({
          ...student,
          hasBed: true,
          bedNumber: nextBedNumber++,
          surplusType: 'regular_bed'
        });
      } else {
        result.push({
          ...student,
          hasBed: false,
          surplusType: 'evening_surplus',
          surplusRank: surplusRank++
        });
      }
    });

    const regularBedCount = result.filter(o => o.hasBed).length;
    const regularSurplusCount = result.filter(o => o.surplusType === 'regular_surplus').length;
    const guestSurplusCount = result.filter(o => o.surplusType === 'guest_surplus').length;
    const eveningSurplusCount = result.filter(o => o.surplusType === 'evening_surplus').length;
    const surplusCount = regularSurplusCount + guestSurplusCount + eveningSurplusCount;

    return {
      occupantsWithBeds: result,
      regularBedCount,
      surplusCount,
      regularSurplusCount,
      guestSurplusCount,
      eveningSurplusCount
    };
  };

  // Attendance Tab State & Print Modals
  const [attendanceDate, setAttendanceDate] = useState<string>('2026-09-30');
  const [attendanceFloorFilter, setAttendanceFloorFilter] = useState<'all' | DormFloor>('all');
  const [attendanceStatusFilter, setAttendanceStatusFilter] = useState<'all' | AttendanceStatus>('all');
  const [attendanceSearch, setAttendanceSearch] = useState<string>('');

  // Print Modals
  const [showBatchPrintModal, setShowBatchPrintModal] = useState<boolean>(false);
  const [individualPrintStudent, setIndividualPrintStudent] = useState<{
    alloc: DormAllocation;
    room?: DormRoom;
    record?: NightAttendanceRecord;
  } | null>(null);

  const showNotification = (msg: string) => {
    setSuccessMessage(msg);
    setTimeout(() => setSuccessMessage(null), 4000);
  };

  const showError = (msg: string) => {
    setErrorMessage(msg);
    setTimeout(() => setErrorMessage(null), 4000);
  };

  // 1. Add Admitted Student into admitted_students
  const handleAddAdmittedStudent = (e: React.FormEvent) => {
    e.preventDefault();
    const nid = newAdmNationalId.trim();
    const sid = newAdmStudentId.trim();
    const name = newAdmFullName.trim();
    const field = newAdmField.trim();

    if (!nid || !sid || !name || !field) {
      showError('لطفاً تمامی فیلدهای الزامی کد ملی، شماره دانشجویی، نام و رشته تحصیلی را تکمیل فرمایید.');
      return;
    }

    if (nid.length !== 10) {
      showError('کد ملی ده رقمی باید به صورت کامل وارد شود.');
      return;
    }

    const exists = admittedStudents.some(s => s.nationalId === nid || s.studentId === sid);
    if (exists) {
      showError('دانشجویی با این کد ملی یا شماره دانشجویی قبلاً در جدول پذیرفته‌شدگان ثبت شده است.');
      return;
    }

    const newAdmitted: AdmittedStudent = {
      id: Math.max(...admittedStudents.map(a => a.id), 0) + 1,
      nationalId: nid,
      studentId: sid,
      fullName: name,
      campus: newAdmCampus,
      fieldOfStudy: field,
      isRegistered: false,
      createdAt: '1405/07/08'
    };

    if (onUpdateAdmittedStudents) {
      onUpdateAdmittedStudents([newAdmitted, ...admittedStudents]);
    }
    setNewAdmNationalId('');
    setNewAdmStudentId('');
    setNewAdmFullName('');
    setNewAdmField('');
    showNotification(`دانشجوی پذیرفته‌شده جدید «${name}» با موفقیت در جدول منبع (admitted_students) ثبت شد.`);
  };

  const handleDeleteAdmittedStudent = (id: number) => {
    const target = admittedStudents.find(a => a.id === id);
    if (!target) return;
    if (window.confirm(`آیا از حذف داوطلب پذیرفته‌شده «${target.fullName}» از جدول منبع اطمینان دارید؟`)) {
      if (onUpdateAdmittedStudents) {
        onUpdateAdmittedStudents(admittedStudents.filter(a => a.id !== id));
      }
      showNotification(`دانشجو «${target.fullName}» از جدول منبع حذف گردید.`);
    }
  };

  // Toggle active/inactive room
  const handleToggleRoomStatus = (roomId: number) => {
    const targetRoom = rooms.find(r => r.id === roomId);
    if (!targetRoom) return;

    const updated = rooms.map(r => {
      if (r.id === roomId) {
        return { ...r, isActive: !r.isActive };
      }
      return r;
    });
    onUpdateRooms(updated);
    showNotification(`وضعیت اتاق ${targetRoom.roomNumber} به «${!targetRoom.isActive ? 'فعال' : 'غیرفعال'}» تغییر یافت.`);
  };

  // Delete Room & Move its students to unhoused list
  const handleDeleteRoom = (roomId: number) => {
    const targetRoom = rooms.find(r => r.id === roomId);
    if (!targetRoom) return;

    const residingAllocations = allocations.filter(a => a.isActive && a.roomId === roomId);
    const confirmPrompt = residingAllocations.length > 0
      ? `آیا از حذف اتاق ${targetRoom.roomNumber} اطمینان دارید؟ تعداد ${residingAllocations.length} دانشجو در این اتاق اسکان دارند که با حذف اتاق، به لیست دانشجویان فاقد اسکان منتقل خواهند شد.`
      : `آیا از حذف کامل اتاق ${targetRoom.roomNumber} اطمینان دارید؟`;

    if (!window.confirm(confirmPrompt)) {
      return;
    }

    if (residingAllocations.length > 0) {
      const newlyUnhoused: UnhousedStudent[] = residingAllocations.map(alloc => ({
        studentId: alloc.studentId,
        fullName: alloc.studentName,
        nationalId: alloc.nationalId,
        mobile: '09120000000',
        fieldOfStudy: 'رشته ثبت شده دانشجو',
        studyShift: 'روزانه',
        province: 'استان ثبت‌شده',
        city: 'شهر سکونت',
        isForeignStudent: false,
        dormitoryNote: `تخلیه خودکار ناشی از حذف اتاق ${targetRoom.roomNumber} (${targetRoom.floor})`,
        requestDate: 'امروز'
      }));

      onUpdateUnhousedStudents([...unhousedStudents, ...newlyUnhoused]);
    }

    onUpdateAllocations(allocations.filter(a => a.roomId !== roomId));
    onUpdateRooms(rooms.filter(r => r.id !== roomId));

    showNotification(
      residingAllocations.length > 0
        ? `اتاق ${targetRoom.roomNumber} حذف گردید و ${toPersianDigits(residingAllocations.length)} دانشجو به لیست فاقدین اسکان منتقل شدند.`
        : `اتاق ${targetRoom.roomNumber} با موفقیت حذف گردید.`
    );
  };

  // Add Room (starts as incomplete room without facilities)
  const handleAddRoom = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newRoomNumber.trim()) return;

    const exists = rooms.some(r => r.roomNumber.toLowerCase() === newRoomNumber.trim().toLowerCase());
    if (exists) {
      showError('شماره اتاق وارد شده قبلاً در سیستم تعریف گردیده است.');
      return;
    }

    const newId = Math.max(...rooms.map(r => r.id), 0) + 1;
    const newRoom: DormRoom = {
      id: newId,
      roomNumber: newRoomNumber.trim(),
      floor: newRoomFloor,
      capacity: newRoomCapacity,
      isActive: true,
      isCompleted: false, // ایجاد خوابگاه به صورت تکمیل‌نشده طبق نیازمندی
      description: newRoomDesc.trim() || undefined
    };

    onUpdateRooms([...rooms, newRoom]);
    setNewRoomNumber('');
    setNewRoomDesc('');
    setShowAddRoom(false);
    showNotification(`اتاق جدید ${newRoom.roomNumber} در ${newRoom.floor} به عنوان «تکمیل‌نشده» ایجاد گردید. اکنون از بخش «تکمیل اطلاعات خوابگاه» می‌توانید امکانات آن را ثبت فرمایید.`);
  };

  // Complete / Save Room Facilities
  const handleSaveRoomFacilities = (updatedRoom: DormRoom) => {
    const updated = rooms.map(r => (r.id === updatedRoom.id ? updatedRoom : r));
    onUpdateRooms(updated);
    setSelectedRoomForFacilities(null);
    showNotification(`اطلاعات و امکانات اتاق ${updatedRoom.roomNumber} با موفقیت ثبت و وضعیت خوابگاه «تکمیل‌شده» گردید.`);
  };

  // Resolve Maintenance / Damage Report by Manager
  const handleResolveMaintenance = (
    reportId: number,
    action: 'تعمیر قطعه' | 'خرید و جایگزینی تجهیز نو',
    cost: number,
    notes: string,
    invoiceNumber?: string,
    invoiceDate?: string,
    contractorOrStoreName?: string,
    invoiceNotes?: string
  ) => {
    if (!onUpdateMaintenanceReports) return;
    const nowJalali = '۱۴۰۳/۰۷/۱۴';
    const updated = maintenanceReports.map(rep => {
      if (rep.id === reportId) {
        return {
          ...rep,
          status: (action === 'خرید و جایگزینی تجهیز نو' ? 'رفع‌شده / خریداری و تعویض شد' : 'رفع‌شده / تعمیر شد') as any,
          resolvedAt: `${nowJalali} - ساعت ۲۳:۴۰`,
          resolvedByManagerName: 'دکتر علیرضا حسینی (مدیر امور رفاهی و خوابگاه)',
          resolutionAction: action,
          resolutionCost: cost,
          resolutionNotes: notes,
          invoiceNumber: invoiceNumber || rep.invoiceNumber || `INV-1403-${Math.floor(1000 + Math.random() * 9000)}`,
          invoiceDate: invoiceDate || rep.invoiceDate || nowJalali,
          contractorOrStoreName: contractorOrStoreName || rep.contractorOrStoreName || 'خدمات فنی و تاسیسات پردیس',
          invoiceNotes: invoiceNotes || rep.invoiceNotes || notes
        };
      }
      return rep;
    });
    onUpdateMaintenanceReports(updated);
    showNotification(`اقدام مدیر ثبت گردید: عیب رفع شد و فاکتور شماره ${invoiceNumber || ''} به اسناد مالی سیستم اضافه گردید ✓`);
  };

  // Add Supervisor
  const handleAddSupervisor = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newSupFullName.trim() || !newSupNationalId.trim()) {
      showError('لطفاً نام و کد ملی سرپرست را وارد فرمایید.');
      return;
    }

    const exists = supervisors.some(s => s.nationalId === newSupNationalId.trim());
    if (exists) {
      showError('این کد ملی قبلاً برای یک سرپرست دیگر ثبت شده است.');
      return;
    }

    const newSup: SupervisorAccount = {
      id: Math.max(...supervisors.map(s => s.id), 0) + 1,
      fullName: newSupFullName.trim(),
      nationalId: newSupNationalId.trim(),
      mobile: newSupMobile.trim() || '09120000000',
      assignedDuty: newSupDuty.trim(),
      shiftHours: newSupShiftHours.trim() || '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)',
      shiftDays: newSupShiftDays.trim() || 'شنبه تا چهارشنبه',
      password: newSupPassword.trim() || 'Super@12345',
      isActive: true,
      createdAt: '1405/07/08',
      supervisorCode: `SUP-405-0${supervisors.length + 1}`
    };

    onUpdateSupervisors([...supervisors, newSup]);
    setNewSupFullName('');
    setNewSupNationalId('');
    setNewSupMobile('');
    showNotification(`سرپرست جدید «${newSup.fullName}» با موفقیت ایجاد گردید.`);
  };

  const handleOpenEditSupervisorShift = (sup: SupervisorAccount) => {
    setEditingSupShift(sup);
    setEditShiftDays(sup.shiftDays || 'شنبه تا چهارشنبه');
    setEditShiftHours(sup.shiftHours || '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)');
  };

  const handleUpdateSupervisorShift = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingSupShift) return;
    const updated = supervisors.map(s => {
      if (s.id === editingSupShift.id) {
        return {
          ...s,
          shiftDays: editShiftDays.trim() || s.shiftDays,
          shiftHours: editShiftHours.trim() || s.shiftHours
        };
      }
      return s;
    });
    onUpdateSupervisors(updated);
    setEditingSupShift(null);
    showNotification(`روزها و ساعات شیفت کاری سرپرست «${editingSupShift.fullName}» به‌روزرسانی شد.`);
  };

  const handleDeleteSupervisor = (supId: number) => {
    const target = supervisors.find(s => s.id === supId);
    if (!target) return;
    if (supervisors.length <= 1) {
      showError('حداقل یک سرپرست باید در سیستم باقی بماند.');
      return;
    }
    if (window.confirm(`آیا از حذف دسترسی سرپرست «${target.fullName}» اطمینان دارید؟`)) {
      onUpdateSupervisors(supervisors.filter(s => s.id !== supId));
      showNotification(`سرپرست «${target.fullName}» از سامانه حذف گردید.`);
    }
  };

  // Open Assign Room Modal
  const handleOpenAssignModal = (student: UnhousedStudent) => {
    const availableRoom = rooms.find(r => {
      if (!r.isActive) return false;
      const count = allocations.filter(a => a.isActive && a.roomId === r.id).length;
      return count < r.capacity;
    });

    setAssigningStudent(student);
    setAssignTargetRoomId(availableRoom ? availableRoom.id : (rooms[0]?.id || 1));
    setAssignDormStatus(student.studyShift === 'روزانه' ? 'بدون شهریه' : 'دارای شهریه');
    setAssignGuestDays(7);
  };

  // Confirm Assignment with Guest Days support
  const handleConfirmAssignment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!assigningStudent) return;

    const targetRoom = rooms.find(r => r.id === assignTargetRoomId);
    if (!targetRoom) {
      showError('اتاق انتخاب‌شده نامعتبر است.');
      return;
    }

    const currentOccupants = allocations.filter(a => a.isActive && a.roomId === assignTargetRoomId).length;
    const isSurplus = currentOccupants >= targetRoom.capacity;

    const newAllocation: DormAllocation = {
      id: Math.max(...allocations.map(a => a.id), 0) + 1,
      studentId: assigningStudent.studentId,
      studentName: assigningStudent.fullName,
      nationalId: assigningStudent.nationalId,
      roomId: assignTargetRoomId,
      dormStatus: assignDormStatus,
      studyShift: assigningStudent.studyShift || 'روزانه',
      allocationDate: new Date().toISOString().split('T')[0],
      allocationTimestamp: Date.now(),
      isActive: true,
      guestDaysTotal: assignDormStatus === 'میهمان' ? assignGuestDays : undefined,
      guestStartDate: assignDormStatus === 'میهمان' ? assignGuestStartDate : undefined,
      guestEndDate: assignDormStatus === 'میهمان' ? addDaysToJalali(assignGuestStartDate, assignGuestDays) : undefined,
      guestDaysRemaining: assignDormStatus === 'میهمان' ? assignGuestDays : undefined
    };

    onUpdateAllocations([...allocations, newAllocation]);
    onUpdateUnhousedStudents(unhousedStudents.filter(s => s.studentId !== assigningStudent.studentId));

    if (isSurplus) {
      showNotification(
        `دانشجو «${assigningStudent.fullName}» به عنوان اسکان مازاد بر ظرفیت (نفر ${toPersianDigits(currentOccupants + 1)} در اتاق ${toPersianDigits(targetRoom.capacity)} نفره ${targetRoom.roomNumber}) با موفقیت پذیرش شد ⚠️`
      );
    } else {
      showNotification(
        assignDormStatus === 'میهمان'
          ? `دانشجو «${assigningStudent.fullName}» به صورت میهمان (${toPersianDigits(assignGuestDays)} روزه) در اتاق ${targetRoom.roomNumber} اسکان یافت.`
          : `دانشجو «${assigningStudent.fullName}» در اتاق ${targetRoom.roomNumber} (${targetRoom.floor}) اسکان یافت.`
      );
    }
    setAssigningStudent(null);
  };

  // Open edit modal for student allocation
  const handleStartEditStudent = (alloc: DormAllocation) => {
    setSelectedStudentId(alloc.studentId);
    setEditRoomId(alloc.roomId);
    setEditDormStatus(alloc.dormStatus);
    setEditGuestDays(alloc.guestDaysRemaining || alloc.guestDaysTotal || 7);
  };

  // Save student allocation
  const handleSaveStudentAllocation = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStudentId) return;

    const updated = allocations.map(a => {
      if (a.studentId === selectedStudentId) {
        return {
          ...a,
          roomId: editRoomId,
          dormStatus: editDormStatus,
          guestDaysTotal: editDormStatus === 'میهمان' ? (a.guestDaysTotal || editGuestDays) : undefined,
          guestDaysRemaining: editDormStatus === 'میهمان' ? editGuestDays : undefined,
          guestStartDate: editDormStatus === 'میهمان' ? (a.guestStartDate || '1405/07/08') : undefined,
          guestEndDate: editDormStatus === 'میهمان' ? `1405/07/${8 + editGuestDays}` : undefined
        };
      }
      return a;
    });

    onUpdateAllocations(updated);
    setSelectedStudentId(null);
    showNotification('اتاق و وضعیت اسکان دانشجو با موفقیت در سیستم به‌روزرسانی شد.');
  };

  // Reduce 1 day simulation
  const handleReduceGuestDay = (allocId: number) => {
    const target = allocations.find(a => a.id === allocId);
    if (!target) return;

    const currentRemaining = target.guestDaysRemaining !== undefined ? target.guestDaysRemaining : 7;
    const newRemaining = currentRemaining - 1;

    if (newRemaining <= 0) {
      const newlyUnhoused: UnhousedStudent = {
        studentId: target.studentId,
        fullName: target.studentName,
        nationalId: target.nationalId,
        mobile: '09120000000',
        fieldOfStudy: 'رشته ثبت شده دانشجو',
        studyShift: 'روزانه',
        province: 'استان ثبت‌شده',
        city: 'شهر سکونت',
        isForeignStudent: false,
        dormitoryNote: `انقضای مهلت اقامت میهمانی (${toPersianDigits(target.guestDaysTotal || 7)} روزه) - انتقال خودکار به صف انتظار`,
        requestDate: 'امروز'
      };
      onUpdateAllocations(allocations.filter(a => a.id !== allocId));
      onUpdateUnhousedStudents([...unhousedStudents, newlyUnhoused]);
      showNotification(`مهلت اقامت میهمان «${target.studentName}» منقضی شد و به صورت خودکار به لیست فاقدین اسکان منتقل گردید.`);
    } else {
      const updated = allocations.map(a => {
        if (a.id === allocId) {
          return { ...a, guestDaysRemaining: newRemaining };
        }
        return a;
      });
      onUpdateAllocations(updated);
      showNotification(`یک روز از مهلت اقامت «${target.studentName}» کسر شد (${toPersianDigits(newRemaining)} روز باقی‌مانده).`);
    }
  };

  // Evict student to unhoused
  const handleEvictToUnhoused = (alloc: DormAllocation) => {
    if (!window.confirm(`آیا مایل به لغو اسکان «${alloc.studentName}» و انتقال به لیست متقاضیان فاقد اسکان هستید؟`)) {
      return;
    }

    const unhousedItem: UnhousedStudent = {
      studentId: alloc.studentId,
      fullName: alloc.studentName,
      nationalId: alloc.nationalId,
      mobile: '09120000000',
      fieldOfStudy: 'رشته دانشجو',
      studyShift: 'روزانه',
      province: 'نامشخص',
      city: 'نامشخص',
      isForeignStudent: false,
      dormitoryNote: 'تخلیه شده توسط مدیریت و در صف بازتخصیص اتاق',
      requestDate: 'امروز'
    };

    onUpdateAllocations(allocations.filter(a => a.id !== alloc.id));
    onUpdateUnhousedStudents([...unhousedStudents, unhousedItem]);
    showNotification(`اسکان دانشجو «${alloc.studentName}» لغو و به لیست فاقدین اسکان منتقل شد.`);
  };

  // Floor stats
  const floorStats = useMemo(() => {
    const floors: DormFloor[] = ['همکف', 'طبقه اول', 'طبقه دوم'];
    return floors.map(floorName => {
      const floorRooms = rooms.filter(r => r.floor === floorName);
      const activeRooms = floorRooms.filter(r => r.isActive);
      const inactiveRooms = floorRooms.filter(r => !r.isActive);
      const totalCapacity = floorRooms.reduce((acc, r) => acc + r.capacity, 0);

      const floorRoomIds = floorRooms.map(r => r.id);
      const occupiedStudents = allocations.filter(
        a => a.isActive && floorRoomIds.includes(a.roomId)
      ).length;

      const freeBeds = Math.max(0, totalCapacity - occupiedStudents);

      return {
        floorName,
        totalRooms: floorRooms.length,
        activeRooms: activeRooms.length,
        inactiveRooms: inactiveRooms.length,
        totalCapacity,
        occupiedStudents,
        freeBeds
      };
    });
  }, [rooms, allocations]);

  // Filtered rooms
  const filteredRooms = useMemo(() => {
    return rooms.filter(room => {
      if (activeFloorFilter !== 'all' && room.floor !== activeFloorFilter) return false;
      if (activeStatusFilter === 'active' && !room.isActive) return false;
      if (activeStatusFilter === 'inactive' && room.isActive) return false;

      const occupants = allocations.filter(a => a.isActive && a.roomId === room.id);
      if (hasFreeBedFilter && occupants.length >= room.capacity) return false;

      if (roomStudentSearchQuery.trim()) {
        const q = roomStudentSearchQuery.trim().toLowerCase();
        const matchesRoom = room.roomNumber.toLowerCase().includes(q);
        const matchesStudent = occupants.some(
          o => o.studentName.toLowerCase().includes(q) ||
               o.studentId.includes(q) ||
               (o.nationalId && o.nationalId.includes(q))
        );
        if (!matchesRoom && !matchesStudent) return false;
      }

      return true;
    });
  }, [rooms, allocations, activeFloorFilter, activeStatusFilter, hasFreeBedFilter, roomStudentSearchQuery]);

  // Filtered attendance records for Attendance Tab
  const activeAllocations = allocations.filter(a => a.isActive);
  const attendanceFilteredList = useMemo(() => {
    return activeAllocations.filter(alloc => {
      const room = rooms.find(r => r.id === alloc.roomId);
      if (attendanceFloorFilter !== 'all' && room?.floor !== attendanceFloorFilter) {
        return false;
      }

      const match = attendanceRecords.find(
        rec => rec.studentId === alloc.studentId && rec.attendanceDate === attendanceDate
      );
      const currStatus = match ? match.status : 'حاضر';

      if (attendanceStatusFilter !== 'all' && currStatus !== attendanceStatusFilter) {
        return false;
      }

      if (attendanceSearch.trim()) {
        const q = attendanceSearch.trim().toLowerCase();
        const matchName = alloc.studentName.toLowerCase().includes(q);
        const matchId = alloc.studentId.includes(q);
        const matchNid = alloc.nationalId && alloc.nationalId.includes(q);
        const matchRoom = room?.roomNumber.toLowerCase().includes(q);
        if (!matchName && !matchId && !matchNid && !matchRoom) return false;
      }

      return true;
    });
  }, [activeAllocations, rooms, attendanceRecords, attendanceDate, attendanceFloorFilter, attendanceStatusFilter, attendanceSearch]);

  // Attendance Statistics
  const attendanceStats = useMemo(() => {
    const total = activeAllocations.length;
    let p = 0;
    let a = 0;
    let l = 0;

    activeAllocations.forEach(alloc => {
      const match = attendanceRecords.find(
        rec => rec.studentId === alloc.studentId && rec.attendanceDate === attendanceDate
      );
      const st = match ? match.status : 'حاضر';
      if (st === 'حاضر') p++;
      else if (st === 'غایب') a++;
      else if (st === 'مرخصی') l++;
    });

    const percent = total > 0 ? Math.round((p / total) * 100) : 100;
    return { total, present: p, absent: a, leave: l, percent };
  }, [activeAllocations, attendanceRecords, attendanceDate]);

  // Filtered Admitted Students (search by nationalId, studentId, name, field)
  const filteredAdmitted = useMemo(() => {
    if (!admittedSearch.trim()) return admittedStudents;
    const q = admittedSearch.trim().toLowerCase();
    return admittedStudents.filter(
      s => s.fullName.toLowerCase().includes(q) || s.studentId.includes(q) || s.nationalId.includes(q) || s.fieldOfStudy.toLowerCase().includes(q)
    );
  }, [admittedStudents, admittedSearch]);

  // Filtered Unhoused Students (search by nationalId, studentId, fullName, province, city)
  const filteredUnhoused = useMemo(() => {
    if (!unhousedSearchQuery.trim()) return unhousedStudents;
    const q = unhousedSearchQuery.trim().toLowerCase();
    return unhousedStudents.filter(
      s => s.fullName.toLowerCase().includes(q) ||
           s.studentId.includes(q) ||
           s.nationalId.includes(q) ||
           s.fieldOfStudy.toLowerCase().includes(q) ||
           s.province.toLowerCase().includes(q) ||
           s.city.toLowerCase().includes(q)
    );
  }, [unhousedStudents, unhousedSearchQuery]);

  // Filtered Housed Students (search by nationalId, studentId, fullName, roomNumber)
  const filteredHoused = useMemo(() => {
    const list = allocations.filter(a => a.isActive);
    if (!housedSearchQuery.trim()) return list;
    const q = housedSearchQuery.trim().toLowerCase();
    return list.filter(alloc => {
      const room = rooms.find(r => r.id === alloc.roomId);
      return alloc.studentName.toLowerCase().includes(q) ||
             alloc.studentId.includes(q) ||
             alloc.nationalId.includes(q) ||
             (room && room.roomNumber.toLowerCase().includes(q)) ||
             alloc.dormStatus.toLowerCase().includes(q);
    });
  }, [allocations, rooms, housedSearchQuery]);

  // Filtered Festivals
  const filteredFestivals = useMemo(() => {
    return studentFestivals.filter(f => {
      if (festivalCategoryFilter !== 'all') {
        const hasKey = f.festivals.some(item => item.key === festivalCategoryFilter);
        if (!hasKey) return false;
      }
      if (festivalSearchQuery.trim()) {
        const q = festivalSearchQuery.trim().toLowerCase();
        const matchName = f.fullName.toLowerCase().includes(q);
        const matchSid = f.studentId.includes(q);
        const matchNid = f.nationalId.includes(q);
        const matchFest = f.festivals.some(item => item.title.toLowerCase().includes(q));
        if (!matchName && !matchSid && !matchNid && !matchFest) return false;
      }
      return true;
    });
  }, [studentFestivals, festivalCategoryFilter, festivalSearchQuery]);

  // Filtered Skills (Live Search)
  const filteredSkills = useMemo(() => {
    if (!skillLiveQuery.trim()) return studentSkills;
    const q = skillLiveQuery.trim().toLowerCase();
    return studentSkills.filter(s => {
      const matchSkill = s.skills.some(skill => skill.toLowerCase().includes(q));
      const matchName = s.fullName.toLowerCase().includes(q);
      const matchNid = s.nationalId.includes(q);
      const matchSid = s.studentId.includes(q);
      const matchField = s.fieldOfStudy ? s.fieldOfStudy.toLowerCase().includes(q) : false;
      return matchSkill || matchName || matchNid || matchSid || matchField;
    });
  }, [studentSkills, skillLiveQuery]);

  // Periodic Cleanliness Performance Rankings (Weekly / Monthly / All)
  const cleanlinessRankings = useMemo<RoomCleanlinessRanking[]>(() => {
    const allDates = Array.from(new Set(cleanlinessRecords.map(c => c.inspectionDate))).sort().reverse();
    let relevantDates = allDates;
    if (cleanlinessRankingPeriod === 'weekly') {
      relevantDates = allDates.slice(0, 7);
    } else if (cleanlinessRankingPeriod === 'monthly') {
      relevantDates = allDates.slice(0, 30);
    }

    const relevantRecords = cleanlinessRecords.filter(c => relevantDates.includes(c.inspectionDate));

    const roomMap = new Map<number, RoomCleanlinessRecord[]>();
    rooms.forEach(r => roomMap.set(r.id, []));
    relevantRecords.forEach(rec => {
      const existing = roomMap.get(rec.roomId) || [];
      existing.push(rec);
      roomMap.set(rec.roomId, existing);
    });

    const result: RoomCleanlinessRanking[] = [];

    rooms.forEach(room => {
      const recs = roomMap.get(room.id) || [];
      const totalInspections = recs.length;

      const gradeCounts = {
        excellent: recs.filter(r => r.grade === 'عالی').length,
        good: recs.filter(r => r.grade === 'خوب').length,
        average: recs.filter(r => r.grade === 'متوسط').length,
        poor: recs.filter(r => r.grade === 'ضعیف').length
      };

      let averageScore = 0;
      if (totalInspections > 0) {
        const totalPoints =
          gradeCounts.excellent * 20 +
          gradeCounts.good * 16 +
          gradeCounts.average * 12 +
          gradeCounts.poor * 6;
        averageScore = Number((totalPoints / totalInspections).toFixed(1));
      }

      const dailyHistory = recs
        .sort((a, b) => b.inspectionDate.localeCompare(a.inspectionDate))
        .map(r => ({
          date: r.inspectionDate,
          grade: r.grade,
          notes: r.notes,
          supervisorName: r.supervisorName
        }));

      result.push({
        roomId: room.id,
        roomNumber: room.roomNumber,
        floor: room.floor,
        totalInspections,
        averageScore,
        gradeCounts,
        dailyHistory,
        rank: 1
      });
    });

    if (cleanlinessRankingSort === 'best_to_worst') {
      result.sort((a, b) => b.averageScore - a.averageScore || b.gradeCounts.excellent - a.gradeCounts.excellent);
    } else {
      result.sort((a, b) => a.averageScore - b.averageScore || b.gradeCounts.poor - a.gradeCounts.poor);
    }

    result.forEach((item, index) => {
      item.rank = index + 1;
    });

    return result;
  }, [cleanlinessRecords, rooms, cleanlinessRankingPeriod, cleanlinessRankingSort]);

  // Periodic Maintenance & Expenses Filtering
  const filteredMaintenanceReports = useMemo(() => {
    return maintenanceReports.filter(rep => {
      if (maintenanceStatusFilter === 'pending' && rep.status !== 'در انتظار اقدام' && rep.status !== 'در حال تعمیر') return false;
      if (maintenanceStatusFilter === 'resolved' && !rep.status.includes('رفع‌شده')) return false;
      if (maintenanceUrgencyFilter !== 'all' && rep.urgency !== maintenanceUrgencyFilter) return false;
      if (maintenanceSearchQuery.trim()) {
        const q = maintenanceSearchQuery.trim().toLowerCase();
        const matchRoom = rep.roomNumber.toLowerCase().includes(q);
        const matchEq = rep.equipmentName.toLowerCase().includes(q);
        const matchSup = rep.reportedBySupervisorName.toLowerCase().includes(q);
        const matchDesc = rep.description.toLowerCase().includes(q);
        const matchInv = (rep.invoiceNumber || '').toLowerCase().includes(q);
        const matchContractor = (rep.contractorOrStoreName || '').toLowerCase().includes(q);
        if (!matchRoom && !matchEq && !matchSup && !matchDesc && !matchInv && !matchContractor) return false;
      }
      return true;
    });
  }, [maintenanceReports, maintenanceStatusFilter, maintenanceUrgencyFilter, maintenanceSearchQuery]);

  // Maintenance Financial & Expense Summary
  const maintenanceExpensesStats = useMemo(() => {
    const totalReports = filteredMaintenanceReports.length;
    const resolved = filteredMaintenanceReports.filter(r => r.status.includes('رفع‌شده'));
    const pending = filteredMaintenanceReports.filter(r => r.status === 'در انتظار اقدام' || r.status === 'در حال تعمیر');
    const totalCost = resolved.reduce((acc, r) => acc + (r.resolutionCost || 0), 0);
    const invoiceCount = resolved.filter(r => !!r.invoiceNumber).length;
    return {
      totalReports,
      resolvedCount: resolved.length,
      pendingCount: pending.length,
      totalCost,
      invoiceCount
    };
  }, [filteredMaintenanceReports]);

  // Handlers for Festival Calls
  const handleAddFestivalCall = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCallTitle.trim() || !newCallContent.trim()) {
      showError('لطفاً عنوان و متن فراخوان را تکمیل فرمایید.');
      return;
    }

    const startDate = newCallStartDate.trim() || getTodayJalali();
    const deadlineDate = newCallDeadline.trim();
    const isFuture = startDate > getTodayJalali();

    const newCall: FestivalCallAnnouncement = {
      id: Math.max(...localFestivalCalls.map(c => c.id), 0) + 1,
      title: newCallTitle.trim(),
      targetCategory: newCallCategory,
      targetSportsTeam: newCallCategory === 'sports' ? newCallSportsTeam : undefined,
      content: newCallContent.trim(),
      startDate: startDate,
      deadlineDate: `${deadlineDate} (ساعت ${newCallTime.trim()})`,
      deadlineTime: newCallTime.trim(),
      issuedBy: 'دکتر علیرضا حسینی (مدیریت امور رفاهی و دانشجویی)',
      issuedAt: `${getTodayJalali()} - ساعت ۱۲:۰۰`,
      status: isFuture ? 'upcoming' : 'active'
    };

    const updated = [newCall, ...localFestivalCalls];
    setLocalFestivalCalls(updated);
    if (onUpdateFestivalCalls) {
      onUpdateFestivalCalls(updated);
    }
    setShowAddCallModal(false);
    setNewCallTitle('');
    setNewCallContent('');
    showNotification(`فراخوان هدفمند «${newCall.title}» با موفقیت صادر و به کارتابل دانشجویان ارسال شد.`);
  };

  const handleDeleteFestivalCall = (id: number) => {
    const updated = localFestivalCalls.filter(c => c.id !== id);
    setLocalFestivalCalls(updated);
    if (onUpdateFestivalCalls) {
      onUpdateFestivalCalls(updated);
    }
    showNotification('فراخوان مورد نظر با موفقیت حذف گردید.');
  };

  return (
    <div className={`min-h-screen flex flex-col md:flex-row antialiased font-sans transition-colors duration-200 ${
      theme === 'light' ? 'theme-light bg-slate-50 text-slate-900' : 'theme-dark bg-[#0b0f19] text-slate-100'
    }`} dir="rtl">
      
      {/* ===================== SIDEBAR (RIGHT SIDE - RTL) ===================== */}
      <aside className={`w-full md:w-64 lg:w-72 border-l flex flex-col shrink-0 z-30 shadow-2xl transition-colors duration-200 ${
        theme === 'light' ? 'bg-white border-slate-200 text-slate-900' : 'bg-[#0f172a] border-slate-800/80 text-slate-100'
      }`}>
        {/* Brand & Campus Header */}
        <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-700 flex items-center justify-center font-black text-white text-base shadow-lg shadow-indigo-500/20 border border-indigo-400/30">
              صمد
            </div>
            <div>
              <h1 className="text-sm font-extrabold text-white tracking-wide">سامانه هوشمند صمد</h1>
              <span className="text-[10px] text-indigo-400 font-medium block">پنل مدیریت دانشگاه و خوابگاه</span>
            </div>
          </div>
          <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse" title="آنلاین" />
        </div>

        {/* Manager Profile Snapshot Card */}
        <div className="p-4 mx-3 my-3 bg-gradient-to-br from-slate-900 to-indigo-950/40 rounded-2xl border border-slate-800/90 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold">
              👨‍💼
            </div>
            <div className="flex-1 min-w-0">
              <span className="text-xs font-bold text-white block truncate">دکتر علیرضا حسینی</span>
              <span className="text-[10px] text-slate-400 block truncate">مدیر امور دانشجویی و رفاهی</span>
            </div>
          </div>
          <div className="mt-2.5 pt-2 border-t border-slate-800/60 flex items-center justify-between text-[10px] text-slate-400">
            <span>کد پرسنلی: <strong>ADM-101</strong></span>
            <span className="text-emerald-400 font-semibold">دسترسی کل ✓</span>
          </div>
        </div>

        {/* Navigation Menu Links */}
        <nav className="flex-1 px-3 py-2 space-y-1.5 overflow-y-auto">
          {/* 1. Overview */}
          <button
            type="button"
            onClick={() => setActiveTab('overview')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'overview'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <LayoutDashboard className="w-4 h-4 shrink-0" />
              <span>پیشخوان و آمار کلی</span>
            </div>
          </button>

          {/* 2. Admitted Students (Source Table) */}
          <button
            type="button"
            onClick={() => setActiveTab('admitted')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'admitted'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <UserCheck className="w-4 h-4 shrink-0 text-cyan-400" />
              <span>پذیرش جدید (جدول منبع)</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
              {toPersianDigits(admittedStudents.length)}
            </span>
          </button>

          {/* 3. Unhoused Applicants */}
          <button
            type="button"
            onClick={() => setActiveTab('unhoused')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'unhoused'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Users className="w-4 h-4 shrink-0 text-amber-400" />
              <span>متقاضیان خوابگاه (صف اسکان)</span>
            </div>
            {unhousedStudents.length > 0 && (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                {toPersianDigits(unhousedStudents.length)}
              </span>
            )}
          </button>

          {/* 4. Rooms & Floors */}
          <button
            type="button"
            onClick={() => setActiveTab('rooms')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'rooms'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Building2 className="w-4 h-4 shrink-0 text-emerald-400" />
              <span>مدیریت اتاق‌ها و طبقات</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
              {toPersianDigits(rooms.length)} اتاق
            </span>
          </button>

          {/* 4.1. Complete Dorm Facilities & Amenities */}
          <button
            type="button"
            onClick={() => setActiveTab('facilities')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'facilities'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Wrench className="w-4 h-4 shrink-0 text-amber-300" />
              <span>تکمیل اطلاعات و امکانات خوابگاه</span>
            </div>
            {rooms.filter(r => !r.isCompleted).length > 0 ? (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">
                {toPersianDigits(rooms.filter(r => !r.isCompleted).length)} ناقص
              </span>
            ) : (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                تکمیل ✓
              </span>
            )}
          </button>

          {/* 4.2. Maintenance & Damage Reports Notifications */}
          <button
            type="button"
            onClick={() => setActiveTab('maintenance')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'maintenance'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Bell className="w-4 h-4 shrink-0 text-rose-400" />
              <span>اطلاع‌رسانی و تعمیرات خوابگاه</span>
            </div>
            {maintenanceReports.filter(m => m.status === 'در انتظار اقدام' || m.status === 'در حال تعمیر').length > 0 && (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                {toPersianDigits(maintenanceReports.filter(m => m.status === 'در انتظار اقدام' || m.status === 'در حال تعمیر').length)} گزارش
              </span>
            )}
          </button>

          {/* 4.3. Cleanliness Reports */}
          <button
            type="button"
            onClick={() => setActiveTab('cleanliness')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'cleanliness'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Sparkles className="w-4 h-4 shrink-0 text-cyan-400" />
              <span>نظافت خوابگاه‌ها (گزارش شبانه)</span>
            </div>
            <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
              پرینت 🖨
            </span>
          </button>

          {/* 5. Housed Students */}
          <button
            type="button"
            onClick={() => setActiveTab('students')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'students'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <BedDouble className="w-4 h-4 shrink-0 text-indigo-400" />
              <span>دانشجویان ساکن و تغییر اتاق</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
              {toPersianDigits(allocations.filter(a => a.isActive).length)}
            </span>
          </button>

          {/* 6. Night Attendance */}
          <button
            type="button"
            onClick={() => setActiveTab('attendance')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'attendance'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <ClipboardList className="w-4 h-4 shrink-0 text-rose-400" />
              <span>سرشماری و حضور و غیاب</span>
            </div>
            <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
              پرینت 🖨
            </span>
          </button>

          {/* 7. Supervisors Management */}
          <button
            type="button"
            onClick={() => setActiveTab('supervisors')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'supervisors'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <ShieldCheck className="w-4 h-4 shrink-0 text-amber-300" />
              <span>مدیریت سرپرستان و شیفت‌ها</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
              {toPersianDigits(supervisors.length)}
            </span>
          </button>

          {/* 8. Festivals & Competitions */}
          <button
            type="button"
            onClick={() => setActiveTab('festivals')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'festivals'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Trophy className="w-4 h-4 shrink-0 text-yellow-400" />
              <span>جشنواره‌ها و داوطلبان</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">
              {toPersianDigits(studentFestivals.length)}
            </span>
          </button>

          {/* 9. Skills Bank & Live Search */}
          <button
            type="button"
            onClick={() => setActiveTab('skills')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'skills'
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <div className="flex items-center gap-2.5">
              <Sparkles className="w-4 h-4 shrink-0 text-purple-400" />
              <span>بانک مهارت‌ها (جستجوی زنده)</span>
            </div>
            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
              {toPersianDigits(studentSkills.length)}
            </span>
          </button>
        </nav>

        {/* Sidebar Footer */}
        <div className="p-4 border-t border-slate-800/80 space-y-2">
          <button
            type="button"
            onClick={onLogout}
            className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600/15 hover:bg-rose-600 text-rose-300 hover:text-white rounded-xl text-xs font-bold transition-all border border-rose-500/30 cursor-pointer"
          >
            <LogOut className="w-4 h-4" />
            <span>خروج از پنل مدیریت</span>
          </button>
        </div>
      </aside>

      {/* ===================== MAIN CONTENT AREA ===================== */}
      <main className={`flex-1 flex flex-col min-w-0 overflow-y-auto transition-colors duration-200 ${
        theme === 'light' ? 'bg-[#f8fafc] text-slate-900' : 'bg-[#0b0f19] text-slate-100'
      }`}>
        {/* Top Navbar */}
        <header className={`h-16 px-6 backdrop-blur-md border-b flex items-center justify-between sticky top-0 z-20 transition-colors duration-200 ${
          theme === 'light' ? 'bg-white/95 border-slate-200 text-slate-900 shadow-xs' : 'bg-[#0f172a]/90 border-slate-800 text-white'
        }`}>
          <div className="flex items-center gap-3">
            <h2 className={`text-sm font-bold flex items-center gap-2 ${theme === 'light' ? 'text-slate-900' : 'text-white'}`}>
              {activeTab === 'overview' && 'پیشخوان و داشبورد مدیریتی صمد'}
              {activeTab === 'admitted' && 'پذیرش دانشجوی جدید در جدول منبع (admitted_students)'}
              {activeTab === 'unhoused' && 'دانشجویان متقاضی خوابگاه در انتظار اسکان'}
              {activeTab === 'rooms' && 'مدیریت و نظارت بر اتاق‌ها و طبقات خوابگاه'}
              {activeTab === 'facilities' && 'تکمیل اطلاعات خوابگاه و تجهیزات و امکانات اتاق‌ها'}
              {activeTab === 'maintenance' && 'بخش اطلاع‌رسانی خرابی و درخواست تعمیر/خرید تجهیزات'}
              {activeTab === 'cleanliness' && 'گزارش‌های شبانه نظافت خوابگاه‌ها و صدور کارنامه چاپی'}
              {activeTab === 'students' && 'فهرست دانشجویان اسکان‌یافته و مدیریت میهمانان'}
              {activeTab === 'attendance' && 'بررسی سرشماری شبانه خوابگاه و صدور کارنامه تردد'}
              {activeTab === 'supervisors' && 'فهرست سرپرستان خوابگاه و صدور کارت شناسایی'}
              {activeTab === 'festivals' && 'جشنواره‌ها و المپیادهای ملی مهارت'}
              {activeTab === 'skills' && 'بانک جامع مهارت‌ها و استعدادهای دانشجویی'}
            </h2>
          </div>

          <div className="flex items-center gap-3">
            {onToggleTheme && (
              <button
                type="button"
                onClick={onToggleTheme}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-xs border ${
                  theme === 'light'
                    ? 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200'
                    : 'bg-slate-800/80 hover:bg-slate-700 text-yellow-300 border-slate-700'
                }`}
                title="تغییر تم تاریک / روشن سامانه (سشن)"
              >
                <span>{theme === 'dark' ? '☀️ تم روشن' : '🌙 تم تاریک'}</span>
              </button>
            )}

            <div className={`hidden sm:flex items-center gap-1.5 px-3 py-1.5 border rounded-xl text-xs ${
              theme === 'light' ? 'bg-slate-100 border-slate-200 text-slate-700' : 'bg-slate-800/80 border-slate-700/60 text-slate-300'
            }`}>
              <Calendar className="w-3.5 h-3.5 text-indigo-400" />
              <span>امروز: {formatToPersianDateText('2026-09-30')}</span>
            </div>

            {activeTab === 'cleanliness' && (
              <button
                type="button"
                onClick={() => setShowCleanlinessPrintModal(true)}
                className="flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer"
              >
                <Printer className="w-3.5 h-3.5" />
                <span>چاپ کارنامه نظافت (Print)</span>
              </button>
            )}

            {activeTab === 'attendance' && (
              <button
                type="button"
                onClick={() => setShowBatchPrintModal(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer"
              >
                <Printer className="w-3.5 h-3.5" />
                <span>چاپ گزارش کلی A4</span>
              </button>
            )}
          </div>
        </header>

        {/* Content Body */}
        <div className="p-6 space-y-6">
          {/* Notifications */}
          {successMessage && (
            <div className="p-4 bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 rounded-2xl text-xs font-bold flex items-center gap-2 animate-fadeIn shadow-lg">
              <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
              <span>{successMessage}</span>
            </div>
          )}

          {errorMessage && (
            <div className="p-4 bg-rose-950/80 border border-rose-500/50 text-rose-200 rounded-2xl text-xs font-bold flex items-center gap-2 animate-fadeIn shadow-lg">
              <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
              <span>{errorMessage}</span>
            </div>
          )}

          {/* ===================== TAB 1: OVERVIEW ===================== */}
          {activeTab === 'overview' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Quick Summary Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                  <div className="flex justify-between items-start">
                    <div>
                      <span className="text-xs text-slate-400 block mb-1">کل دانشجویان اسکان‌یافته</span>
                      <span className="text-2xl font-black text-white font-mono">
                        {toPersianDigits(allocations.filter(a => a.isActive).length)}
                      </span>
                    </div>
                    <span className="p-3 bg-indigo-500/10 text-indigo-400 rounded-2xl border border-indigo-500/20">
                      <BedDouble className="w-5 h-5" />
                    </span>
                  </div>
                  <div className="mt-3 text-[11px] text-indigo-300 flex items-center gap-1">
                    <span>ظرفیت خوابگاه: {toPersianDigits(rooms.reduce((acc, r) => acc + r.capacity, 0))} تخت</span>
                  </div>
                </div>

                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                  <div className="flex justify-between items-start">
                    <div>
                      <span className="text-xs text-slate-400 block mb-1">متقاضیان در انتظار اسکان</span>
                      <span className="text-2xl font-black text-amber-400 font-mono">
                        {toPersianDigits(unhousedStudents.length)}
                      </span>
                    </div>
                    <span className="p-3 bg-amber-500/10 text-amber-400 rounded-2xl border border-amber-500/20">
                      <Users className="w-5 h-5" />
                    </span>
                  </div>
                  <div className="mt-3 text-[11px] text-amber-300/80 flex items-center gap-1">
                    <span>نیازمند بررسی و تخصیص اتاق</span>
                  </div>
                </div>

                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                  <div className="flex justify-between items-start">
                    <div>
                      <span className="text-xs text-slate-400 block mb-1">دانشجویان میهمان</span>
                      <span className="text-2xl font-black text-cyan-400 font-mono">
                        {toPersianDigits(allocations.filter(a => a.isActive && a.dormStatus === 'میهمان').length)}
                      </span>
                    </div>
                    <span className="p-3 bg-cyan-500/10 text-cyan-400 rounded-2xl border border-cyan-500/20">
                      <Clock className="w-5 h-5" />
                    </span>
                  </div>
                  <div className="mt-3 text-[11px] text-cyan-300/80 flex items-center gap-1">
                    <span>دارای مهلت اقامت روزشمار</span>
                  </div>
                </div>

                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                  <div className="flex justify-between items-start">
                    <div>
                      <span className="text-xs text-slate-400 block mb-1">درصد حضور در آخرین سرشماری</span>
                      <span className="text-2xl font-black text-emerald-400 font-mono">
                        ٪{toPersianDigits(attendanceStats.percent)}
                      </span>
                    </div>
                    <span className="p-3 bg-emerald-500/10 text-emerald-400 rounded-2xl border border-emerald-500/20">
                      <ClipboardList className="w-5 h-5" />
                    </span>
                  </div>
                  <div className="mt-3 text-[11px] text-emerald-300/80 flex items-center gap-1">
                    <span>{toPersianDigits(attendanceStats.present)} حاضر از {toPersianDigits(attendanceStats.total)} دانشجو</span>
                  </div>
                </div>
              </div>

              {/* Floor Occupancy Cards */}
              <div className="bg-[#1e293b]/50 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <Layers className="w-4 h-4 text-indigo-400" />
                    <span>وضعیت طبقات ساختمان خوابگاه مرکزی صمد (همکف، ۱ و ۲)</span>
                  </h3>
                  <button
                    type="button"
                    onClick={() => setActiveTab('rooms')}
                    className="text-xs text-indigo-400 hover:text-indigo-300 font-bold transition-colors cursor-pointer"
                  >
                    مدیریت کامل اتاق‌ها ←
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {floorStats.map(stat => (
                    <div key={stat.floorName} className="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm font-bold text-white">{stat.floorName}</span>
                        <span className="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded text-xs font-bold">
                          {toPersianDigits(stat.totalRooms)} اتاق
                        </span>
                      </div>
                      <div className="grid grid-cols-2 gap-2 text-xs">
                        <div className="p-2 bg-slate-800/60 rounded-lg">
                          <span className="text-slate-400 text-[10px] block">اتاق‌های فعال:</span>
                          <span className="font-bold text-emerald-400">{toPersianDigits(stat.activeRooms)}</span>
                        </div>
                        <div className="p-2 bg-slate-800/60 rounded-lg">
                          <span className="text-slate-400 text-[10px] block">اتاق‌های غیرفعال:</span>
                          <span className="font-bold text-rose-400">{toPersianDigits(stat.inactiveRooms)}</span>
                        </div>
                        <div className="p-2 bg-slate-800/60 rounded-lg">
                          <span className="text-slate-400 text-[10px] block">دانشجویان ساکن:</span>
                          <span className="font-bold text-indigo-300">{toPersianDigits(stat.occupiedStudents)}</span>
                        </div>
                        <div className="p-2 bg-slate-800/60 rounded-lg">
                          <span className="text-slate-400 text-[10px] block">تخت‌های خالی:</span>
                          <span className="font-bold text-amber-400">{toPersianDigits(stat.freeBeds)}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Cleanliness & Maintenance Management Section in Overview */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {/* 1. Maintenance Notifications Widget */}
                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-3">
                  <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div className="flex items-center gap-2">
                      <span className="p-2 rounded-xl bg-amber-500/20 text-amber-300">
                        <Wrench className="w-4 h-4" />
                      </span>
                      <div>
                        <h4 className="text-xs font-bold text-white">گزارش‌های خرابی و نیاز به تعمیر/تعویض</h4>
                        <span className="text-[10px] text-slate-400">اطلاع‌رسانی ثبت‌شده توسط سرپرستان شیفت</span>
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => setActiveTab('maintenance')}
                      className="text-[11px] text-amber-400 hover:text-amber-300 font-bold cursor-pointer"
                    >
                      مشاهده همه ({toPersianDigits(maintenanceReports.length)}) ←
                    </button>
                  </div>

                  {maintenanceReports.filter(m => m.status === 'در انتظار اقدام' || m.status === 'در حال تعمیر').length === 0 ? (
                    <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-center text-slate-400 text-xs">
                      هیچ گزارش خرابی در انتظار اقدامی وجود ندارد ✓
                    </div>
                  ) : (
                    <div className="space-y-2 max-h-56 overflow-y-auto">
                      {maintenanceReports
                        .filter(m => m.status === 'در انتظار اقدام' || m.status === 'در حال تعمیر')
                        .slice(0, 3)
                        .map(rep => (
                          <div key={rep.id} className="p-3 bg-slate-900/90 border border-slate-800 rounded-xl flex items-center justify-between text-xs gap-2">
                            <div className="space-y-1 min-w-0">
                              <div className="flex items-center gap-2">
                                <span className="font-bold text-white">اتاق {rep.roomNumber} ({rep.floor})</span>
                                <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                  rep.urgency === 'فوری'
                                    ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse'
                                    : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                }`}>
                                  {rep.urgency}
                                </span>
                              </div>
                              <p className="text-[11px] text-slate-300 truncate">
                                <strong className="text-amber-300">{rep.equipmentName}:</strong> {rep.description}
                              </p>
                              <span className="text-[10px] text-slate-400 block">گزارش‌دهنده: {rep.reportedBySupervisorName} • {rep.reportedAt}</span>
                            </div>
                            <button
                              type="button"
                              onClick={() => setSelectedReportForResolution(rep)}
                              className="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-[11px] shrink-0 transition-colors cursor-pointer shadow-sm"
                            >
                              اقدام و رفع عیب
                            </button>
                          </div>
                        ))}
                    </div>
                  )}
                </div>

                {/* 2. Nightly Cleanliness Summary Widget */}
                <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-3">
                  <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div className="flex items-center gap-2">
                      <span className="p-2 rounded-xl bg-cyan-500/20 text-cyan-300">
                        <Sparkles className="w-4 h-4" />
                      </span>
                      <div>
                        <h4 className="text-xs font-bold text-white">گزارش شبانه نظافت خوابگاه‌ها</h4>
                        <span className="text-[10px] text-slate-400">ارزیابی ۴ سطحی سرپرستان در تاریخ {selectedCleanlinessDate}</span>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => setShowCleanlinessPrintModal(true)}
                        className="px-2.5 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-colors shadow-sm"
                        title="چاپ کارنامه رسمی نظافت شبانه"
                      >
                        <Printer className="w-3.5 h-3.5" />
                        <span>چاپ کارنامه</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => setActiveTab('cleanliness')}
                        className="text-[11px] text-cyan-400 hover:text-cyan-300 font-bold cursor-pointer"
                      >
                        جزییات ←
                      </button>
                    </div>
                  </div>

                  <div className="grid grid-cols-4 gap-2 text-center text-xs">
                    <div className="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                      <span className="text-[10px] text-emerald-400 block mb-0.5">عالی</span>
                      <strong className="text-base text-emerald-300 font-mono">
                        {toPersianDigits(cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate && c.grade === 'عالی').length)}
                      </strong>
                    </div>
                    <div className="p-2.5 rounded-xl bg-blue-500/10 border border-blue-500/20">
                      <span className="text-[10px] text-blue-400 block mb-0.5">خوب</span>
                      <strong className="text-base text-blue-300 font-mono">
                        {toPersianDigits(cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate && c.grade === 'خوب').length)}
                      </strong>
                    </div>
                    <div className="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20">
                      <span className="text-[10px] text-amber-400 block mb-0.5">متوسط</span>
                      <strong className="text-base text-amber-300 font-mono">
                        {toPersianDigits(cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate && c.grade === 'متوسط').length)}
                      </strong>
                    </div>
                    <div className="p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20">
                      <span className="text-[10px] text-rose-400 block mb-0.5">ضعیف</span>
                      <strong className="text-base text-rose-300 font-mono">
                        {toPersianDigits(cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate && c.grade === 'ضعیف').length)}
                      </strong>
                    </div>
                  </div>

                  <div className="p-3 bg-slate-900/60 rounded-xl border border-slate-800 text-[11px] text-slate-300 flex items-center justify-between">
                    <span>شاخص سلامت و پاکیزگی عمومی خوابگاه:</span>
                    <strong className="text-cyan-300 font-mono text-xs font-bold">
                      ٪{toPersianDigits(
                        Math.round(
                          ((cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate && (c.grade === 'عالی' || c.grade === 'خوب')).length) /
                            Math.max(1, cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate).length)) * 100
                        )
                      )} (مطلوب)
                    </strong>
                  </div>
                </div>
              </div>

              {/* Quick Actions Shortcuts */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button
                  type="button"
                  onClick={() => setActiveTab('admitted')}
                  className="p-4 bg-slate-900/60 hover:bg-slate-900 border border-slate-800 hover:border-cyan-500/40 rounded-2xl flex items-center gap-3.5 transition-all cursor-pointer text-right group"
                >
                  <div className="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <UserPlus className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-xs font-bold text-white block">پذیرش دانشجوی جدید</span>
                    <span className="text-[10px] text-slate-400">ثبت در جدول منبع آزمون سنجش</span>
                  </div>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab('unhoused')}
                  className="p-4 bg-slate-900/60 hover:bg-slate-900 border border-slate-800 hover:border-amber-500/40 rounded-2xl flex items-center gap-3.5 transition-all cursor-pointer text-right group"
                >
                  <div className="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <Users className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-xs font-bold text-white block">اسکان متقاضیان خوابگاه</span>
                    <span className="text-[10px] text-slate-400">تخصیص فوری اتاق با تعیین روز میهمانی</span>
                  </div>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab('attendance')}
                  className="p-4 bg-slate-900/60 hover:bg-slate-900 border border-slate-800 hover:border-rose-500/40 rounded-2xl flex items-center gap-3.5 transition-all cursor-pointer text-right group"
                >
                  <div className="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/30 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <Printer className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-xs font-bold text-white block">بررسی سرشماری و صدور گواهی</span>
                    <span className="text-[10px] text-slate-400">پرینت کارنامه تردد فردی و گزارش کلی</span>
                  </div>
                </button>
              </div>
            </div>
          )}

          {/* ===================== TAB 2: ADMITTED STUDENTS (SOURCE TABLE) ===================== */}
          {activeTab === 'admitted' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Form to add admitted student */}
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="border-b border-slate-800 pb-3 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="p-1.5 bg-cyan-500/20 text-cyan-400 rounded-xl border border-cyan-500/30">
                      <UserPlus className="w-4 h-4" />
                    </span>
                    <div>
                      <h3 className="text-sm font-bold text-white">افزودن داوطلب قبولی به جدول منبع (admitted_students)</h3>
                      <p className="text-[11px] text-slate-400 mt-0.5">دانشجو در مرحله ۱ ثبت‌نام با تطابق این کد ملی و شماره دانشجویی احراز هویت می‌شود</p>
                    </div>
                  </div>
                </div>

                <form onSubmit={handleAddAdmittedStudent} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 text-xs">
                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">کد ملی داوطلب (۱۰ رقم) *</label>
                    <input
                      type="text"
                      maxLength={10}
                      value={newAdmNationalId}
                      onChange={(e) => setNewAdmNationalId(e.target.value)}
                      placeholder="مثال: 0098765432"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-cyan-500 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">شماره دانشجویی اختصاصی *</label>
                    <input
                      type="text"
                      value={newAdmStudentId}
                      onChange={(e) => setNewAdmStudentId(e.target.value)}
                      placeholder="مثال: 4012130099"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-cyan-500 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">نام و نام خانوادگی داوطلب *</label>
                    <input
                      type="text"
                      value={newAdmFullName}
                      onChange={(e) => setNewAdmFullName(e.target.value)}
                      placeholder="مثال: سهراب سپهری"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-cyan-500 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">پردیس قبولی *</label>
                    <select
                      value={newAdmCampus}
                      onChange={(e) => setNewAdmCampus(e.target.value as CampusType)}
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-cyan-500 outline-none"
                    >
                      <option value="پردیس فناوری">پردیس فناوری</option>
                      <option value="پردیس مهارت">پردیس مهارت</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">رشته قبولی داوطلب *</label>
                    <input
                      type="text"
                      value={newAdmField}
                      onChange={(e) => setNewAdmField(e.target.value)}
                      placeholder="مثال: مهندسی کامپیوتر"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-cyan-500 outline-none"
                    />
                  </div>

                  <div className="sm:col-span-2 lg:col-span-5 flex justify-end pt-2">
                    <button
                      type="submit"
                      className="flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-600 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer"
                    >
                      <UserPlus className="w-4 h-4" />
                      <span>ثبت در جدول پذیرفته‌شدگان MySQL</span>
                    </button>
                  </div>
                </form>
              </div>

              {/* Table of admitted students */}
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                  <div className="flex items-center gap-2">
                    <h3 className="text-sm font-bold text-white">فهرست پذیرفته‌شدگان سنجش</h3>
                    <span className="px-2 py-0.5 bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 rounded-full text-xs font-bold font-mono">
                      {toPersianDigits(filteredAdmitted.length)} داوطلب
                    </span>
                  </div>

                  <div className="w-full sm:w-72 relative">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={admittedSearch}
                      onChange={(e) => setAdmittedSearch(e.target.value)}
                      placeholder="جستجوی نام، کدملی یا شماره دانشجویی..."
                      className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-cyan-500"
                    />
                  </div>
                </div>

                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead>
                      <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                        <th className="p-3">ردیف</th>
                        <th className="p-3">نام و نام خانوادگی</th>
                        <th className="p-3">کد ملی</th>
                        <th className="p-3">شماره دانشجویی</th>
                        <th className="p-3">پردیس و رشته</th>
                        <th className="p-3">وضعیت ثبت‌نام در سامانه</th>
                        <th className="p-3 text-center">عملیات</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/60">
                      {filteredAdmitted.map((adm, idx) => (
                        <tr key={adm.id} className="hover:bg-slate-800/40 transition-colors">
                          <td className="p-3 text-slate-400 font-mono">{toPersianDigits(idx + 1)}</td>
                          <td className="p-3 font-bold text-white">{adm.fullName}</td>
                          <td className="p-3 font-mono text-cyan-300">{toPersianDigits(adm.nationalId)}</td>
                          <td className="p-3 font-mono text-amber-300 font-bold">{toPersianDigits(adm.studentId)}</td>
                          <td className="p-3">
                            <span className="block text-slate-200">{adm.fieldOfStudy}</span>
                            <span className="text-[10px] text-slate-400">{adm.campus}</span>
                          </td>
                          <td className="p-3">
                            {adm.isRegistered ? (
                              <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">
                                ثبت‌نام نهایی شده ✓
                              </span>
                            ) : (
                              <span className="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded text-[10px] font-bold">
                                در انتظار تکمیل ثبت‌نام ⏳
                              </span>
                            )}
                          </td>
                          <td className="p-3 text-center">
                            <button
                              type="button"
                              onClick={() => handleDeleteAdmittedStudent(adm.id)}
                              className="p-1.5 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                              title="حذف از جدول منبع"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 3: UNHOUSED APPLICANTS ===================== */}
          {activeTab === 'unhoused' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Users className="w-4 h-4 text-amber-400" />
                      <span>دانشجویان متقاضی خوابگاه در انتظار اسکان (فاقد اتاق)</span>
                      <span className="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full text-xs font-bold font-mono">
                        {toPersianDigits(filteredUnhoused.length)} نفر
                      </span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      دانشجویانی که در زمان ثبت‌نام تقاضای خوابگاه داشته‌اند یا نیازمند بازتخصیص اتاق هستند
                    </p>
                  </div>

                  <div className="w-full sm:w-72 relative">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={unhousedSearchQuery}
                      onChange={(e) => setUnhousedSearchQuery(e.target.value)}
                      placeholder="جستجوی کدملی، نام، شماره دانشجویی یا شهر..."
                      className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500"
                    />
                  </div>
                </div>

                {filteredUnhoused.length === 0 ? (
                  <div className="p-8 text-center bg-slate-900/60 rounded-xl border border-dashed border-slate-700/80">
                    <CheckCircle2 className="w-10 h-10 text-emerald-400 mx-auto mb-2" />
                    <p className="text-sm font-bold text-white">موردی برای نمایش با این مشخصات یافت نشد.</p>
                    <p className="text-xs text-slate-400 mt-1">عبارت جستجو یا فیلتر را تغییر دهید.</p>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-right text-xs">
                      <thead>
                        <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                          <th className="p-3">نام دانشجو</th>
                          <th className="p-3">شماره دانشجویی / کد ملی</th>
                          <th className="p-3">رشته و نوبت تحصیلی</th>
                          <th className="p-3">محل سکونت</th>
                          <th className="p-3">توضیحات و اولویت</th>
                          <th className="p-3 text-center">عملیات اسکان</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-800/60">
                        {filteredUnhoused.map(student => (
                          <tr key={student.studentId} className="hover:bg-slate-800/40 transition-colors">
                            <td className="p-3 font-bold text-white">{student.fullName}</td>
                            <td className="p-3 font-mono">
                              <span className="text-indigo-400 font-bold block">{toPersianDigits(student.studentId)}</span>
                              <span className="text-slate-400 text-[10px]">{toPersianDigits(student.nationalId)}</span>
                            </td>
                            <td className="p-3">
                              <span className="text-slate-200 block">{student.fieldOfStudy}</span>
                              <span className="text-[10px] text-slate-400">{student.studyShift}</span>
                            </td>
                            <td className="p-3">
                              <span className="text-slate-300 flex items-center gap-1">
                                <MapPin className="w-3 h-3 text-slate-500" />
                                <span>{student.province} ({student.city})</span>
                              </span>
                            </td>
                            <td className="p-3 text-[11px] text-slate-300 max-w-xs leading-relaxed">
                              {student.dormitoryNote || 'متقاضی خوابگاه صمد'}
                            </td>
                            <td className="p-3 text-center">
                              <button
                                type="button"
                                onClick={() => handleOpenAssignModal(student)}
                                className="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer"
                              >
                                اسکان در اتاق ←
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ===================== TAB 4: ROOMS MANAGEMENT ===================== */}
          {activeTab === 'rooms' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Building2 className="w-4 h-4 text-emerald-400" />
                      <span>مدیریت اتاق‌های خوابگاه و تعیین وضعیت فعالیت</span>
                      <span className="px-2 py-0.5 bg-slate-800 text-slate-300 border border-slate-700 rounded-full text-xs font-mono">
                        {toPersianDigits(filteredRooms.length)} اتاق
                      </span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">مشاهده اتاق‌ها، فعال/غیرفعال کردن، حذف اتاق و هدایت سکنه به لیست بدون اسکان</p>
                  </div>

                  {/* Filters Bar */}
                  <div className="flex flex-wrap items-center gap-2 text-xs">
                    <select
                      value={activeFloorFilter}
                      onChange={(e) => setActiveFloorFilter(e.target.value as any)}
                      className="px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی طبقات</option>
                      <option value="همکف">همکف</option>
                      <option value="طبقه اول">طبقه اول</option>
                      <option value="طبقه دوم">طبقه دوم</option>
                    </select>

                    <select
                      value={activeStatusFilter}
                      onChange={(e) => setActiveStatusFilter(e.target.value as any)}
                      className="px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی وضعیت‌ها</option>
                      <option value="active">اتاق‌های فعال ✓</option>
                      <option value="inactive">اتاق‌های غیرفعال ⚠️</option>
                    </select>

                    <button
                      type="button"
                      onClick={() => setHasFreeBedFilter(!hasFreeBedFilter)}
                      className={`px-3 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer ${
                        hasFreeBedFilter
                          ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
                          : 'bg-slate-900 text-slate-400 border-slate-700'
                      }`}
                    >
                      فقط تخت خالی
                    </button>

                    <div className="relative">
                      <Search className="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" />
                      <input
                        type="text"
                        value={roomStudentSearchQuery}
                        onChange={(e) => setRoomStudentSearchQuery(e.target.value)}
                        placeholder="جستجوی شماره اتاق یا دانشجو..."
                        className="pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none"
                      />
                    </div>
                  </div>
                </div>

                {/* Rooms Table */}
                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead>
                      <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                        <th className="p-3">شماره اتاق</th>
                        <th className="p-3">طبقه</th>
                        <th className="p-3">وضعیت فعالیت</th>
                        <th className="p-3">وضعیت اطلاعات و امکانات</th>
                        <th className="p-3">ظرفیت و تخت خالی</th>
                        <th className="p-3">دانشجویان مستقر</th>
                        <th className="p-3 text-center">عملیات</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/60">
                      {filteredRooms.map(room => {
                        const occupants = allocations.filter(a => a.isActive && a.roomId === room.id);
                        const freeBeds = Math.max(0, room.capacity - occupants.length);

                        return (
                          <tr key={room.id} className="hover:bg-slate-800/40 transition-colors">
                            <td className="p-3 font-bold font-mono text-emerald-400 text-sm">
                              <button
                                type="button"
                                onClick={() => setSelectedRoomForDetails(room)}
                                className="hover:underline flex items-center gap-1.5 cursor-pointer text-emerald-400 hover:text-emerald-300 text-right"
                                title="کلیک جهت مشاهده اطلاعات کامل دانشجویان این اتاق"
                              >
                                <span>اتاق {room.roomNumber}</span>
                                <Eye className="w-3.5 h-3.5 text-emerald-400 opacity-80" />
                              </button>
                            </td>
                            <td className="p-3 text-slate-300">{room.floor}</td>
                            <td className="p-3">
                              {room.isActive ? (
                                <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">
                                  فعال و دایر ✓
                                </span>
                              ) : (
                                <span className="px-2 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold">
                                  غیرفعال (تعمیرات) ⚠️
                                </span>
                              )}
                            </td>
                            <td className="p-3">
                              {room.isCompleted ? (
                                <div className="space-y-1">
                                  <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold inline-block">
                                    تکمیل شده ✓
                                  </span>
                                  <button
                                    type="button"
                                    onClick={() => setSelectedRoomForFacilities(room)}
                                    className="block text-[10px] text-cyan-400 hover:text-cyan-300 underline cursor-pointer"
                                  >
                                    ویرایش امکانات
                                  </button>
                                </div>
                              ) : (
                                <div className="space-y-1">
                                  <span className="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded text-[10px] font-bold animate-pulse inline-block">
                                    تکمیل‌نشده ⚠️
                                  </span>
                                  <button
                                    type="button"
                                    onClick={() => setSelectedRoomForFacilities(room)}
                                    className="px-2 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded text-[10px] block transition-colors cursor-pointer shadow-xs"
                                  >
                                    + تکمیل اطلاعات
                                  </button>
                                </div>
                              )}
                            </td>
                            <td className="p-3 font-mono">
                              {(() => {
                                const matrix = computeBedMatrix(room, occupants);
                                return (
                                  <div className="space-y-1">
                                    <div className="flex items-center gap-1">
                                      <span className="text-white font-bold">{toPersianDigits(occupants.length)}</span>
                                      <span className="text-slate-400"> از {toPersianDigits(room.capacity)} نفر </span>
                                    </div>
                                    {matrix.surplusCount > 0 ? (
                                      <span className="text-[10px] font-bold text-rose-300 bg-rose-500/20 border border-rose-500/30 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                        <span>⚠️ {toPersianDigits(matrix.surplusCount)} مازاد</span>
                                      </span>
                                    ) : (
                                      <span className={`text-[11px] font-bold ${freeBeds > 0 ? 'text-amber-400' : 'text-slate-500'}`}>
                                        ({toPersianDigits(freeBeds)} تخت خالی)
                                      </span>
                                    )}
                                  </div>
                                );
                              })()}
                            </td>
                            <td className="p-3">
                              {occupants.length === 0 ? (
                                <span className="text-slate-500 text-[11px]">اتاق خالی</span>
                              ) : (
                                <button
                                  type="button"
                                  onClick={() => setSelectedRoomForDetails(room)}
                                  className="flex flex-col gap-1 text-right cursor-pointer group"
                                  title="کلیک جهت مشاهده کامل پرونده و برگه مشخصات اتاق"
                                >
                                  {(() => {
                                    const matrix = computeBedMatrix(room, occupants);
                                    const withBeds = matrix.occupantsWithBeds.filter(o => o.hasBed);
                                    const surplus = matrix.occupantsWithBeds.filter(o => !o.hasBed);

                                    return (
                                      <div className="space-y-1.5">
                                        {/* Beds occupied by Day students */}
                                        <div className="flex flex-wrap gap-1">
                                          {withBeds.map(o => (
                                            <span
                                              key={o.id}
                                              className="px-2 py-0.5 bg-emerald-950/40 text-emerald-300 border border-emerald-500/40 rounded text-[10px] font-medium"
                                              title={`تخت ${o.bedNumber} مصوب - روزانه`}
                                            >
                                              تخت {toPersianDigits(o.bedNumber || 1)}: {o.studentName}
                                            </span>
                                          ))}
                                        </div>

                                        {/* Surplus Students with distinct color */}
                                        {surplus.length > 0 && (
                                          <div className="flex flex-wrap gap-1 pt-0.5 border-t border-slate-800">
                                            {surplus.map(o => (
                                              <span
                                                key={o.id}
                                                className={`px-2 py-0.5 rounded text-[10px] font-bold border ${
                                                  o.surplusType === 'regular_surplus'
                                                    ? 'bg-amber-500/20 text-amber-300 border-amber-500/50'
                                                    : o.surplusType === 'guest_surplus'
                                                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/50'
                                                    : 'bg-rose-500/20 text-rose-300 border-rose-500/50'
                                                }`}
                                                title={o.surplusType === 'regular_surplus' ? 'روزانه مازاد بدون تخت (بر اساس زمان اسکان)' : o.surplusType === 'guest_surplus' ? 'میهمان مازاد' : 'شبانه مازاد'}
                                              >
                                                {o.studentName} (
                                                {o.surplusType === 'regular_surplus' ? 'روزانه مازاد ⚠️' :
                                                 o.surplusType === 'guest_surplus' ? 'میهمان مازاد ⏳' : 'شبانه مازاد 🌙'}
                                                )
                                              </span>
                                            ))}
                                          </div>
                                        )}
                                      </div>
                                    );
                                  })()}
                                  <span className="text-[10px] text-cyan-400 underline opacity-0 group-hover:opacity-100 transition-opacity">
                                    مشاهده مشخصات و اولویت‌ها ←
                                  </span>
                                </button>
                              )}
                            </td>
                            <td className="p-3 text-center">
                              <div className="flex items-center justify-center gap-1.5">
                                <button
                                  type="button"
                                  onClick={() => setSelectedRoomForFacilities(room)}
                                  className="p-1 text-slate-400 hover:text-amber-300 transition-colors cursor-pointer"
                                  title="تکمیل یا مشاهده امکانات و محیط اتاق"
                                >
                                  <Wrench className="w-3.5 h-3.5" />
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleToggleRoomStatus(room.id)}
                                  className={`px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors cursor-pointer ${
                                    room.isActive
                                      ? 'bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 border border-rose-500/30'
                                      : 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 border border-emerald-500/30'
                                  }`}
                                >
                                  {room.isActive ? 'غیرفعال‌سازی' : 'فعال‌سازی'}
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleDeleteRoom(room.id)}
                                  className="p-1 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                                  title="حذف اتاق و تخلیه دانشجویان"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>

                {/* Form to add new room (at the bottom) */}
                <div className="pt-4 border-t border-slate-800">
                  {!showAddRoom ? (
                    <button
                      type="button"
                      onClick={() => setShowAddRoom(true)}
                      className="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                      <Plus className="w-4 h-4 text-emerald-400" />
                      <span>ایجاد اتاق جدید در خوابگاه</span>
                    </button>
                  ) : (
                    <form onSubmit={handleAddRoom} className="p-4 bg-slate-900 border border-slate-700 rounded-xl space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-white">مشخصات اتاق جدید:</span>
                        <button
                          type="button"
                          onClick={() => setShowAddRoom(false)}
                          className="text-slate-400 hover:text-slate-200 text-xs cursor-pointer"
                        >
                          ✕ انصراف
                        </button>
                      </div>
                      <div className="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                          <label className="block text-slate-400 mb-1">شماره اتاق *</label>
                          <input
                            type="text"
                            value={newRoomNumber}
                            onChange={(e) => setNewRoomNumber(e.target.value)}
                            placeholder="مثال: 105 یا G04"
                            className="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg outline-none font-mono"
                          />
                        </div>
                        <div>
                          <label className="block text-slate-400 mb-1">طبقه استقرار *</label>
                          <select
                            value={newRoomFloor}
                            onChange={(e) => setNewRoomFloor(e.target.value as DormFloor)}
                            className="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg outline-none"
                          >
                            <option value="همکف">همکف</option>
                            <option value="طبقه اول">طبقه اول</option>
                            <option value="طبقه دوم">طبقه دوم</option>
                          </select>
                        </div>
                        <div>
                          <div className="flex items-center justify-between mb-1">
                            <label className="block text-slate-400">ظرفیت تخت (دل‌بخواهی) *</label>
                            <span className="text-[10px] text-emerald-400 font-mono font-bold">{toPersianDigits(newRoomCapacity)} تخت</span>
                          </div>
                          <input
                            type="number"
                            min={1}
                            value={newRoomCapacity}
                            onChange={(e) => setNewRoomCapacity(Math.max(1, Number(e.target.value) || 1))}
                            placeholder="تعداد تخت دلخواه..."
                            className="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg outline-none font-mono"
                          />
                          <div className="flex flex-wrap gap-1 mt-1.5">
                            {[2, 4, 6, 8, 10, 12, 16].map(num => (
                              <button
                                key={num}
                                type="button"
                                onClick={() => setNewRoomCapacity(num)}
                                className={`px-1.5 py-0.5 rounded text-[10px] font-mono cursor-pointer transition-colors ${
                                  newRoomCapacity === num
                                    ? 'bg-emerald-600 text-white font-bold'
                                    : 'bg-slate-800 text-slate-400 hover:text-white'
                                }`}
                              >
                                {toPersianDigits(num)}
                              </button>
                            ))}
                          </div>
                        </div>
                        <div>
                          <label className="block text-slate-400 mb-1">توضیحات اتاق</label>
                          <input
                            type="text"
                            value={newRoomDesc}
                            onChange={(e) => setNewRoomDesc(e.target.value)}
                            placeholder="ویژگی‌ها (نورگیر، یخچال...)"
                            className="w-full px-3 py-1.5 bg-slate-800 border border-slate-700 text-white rounded-lg outline-none"
                          />
                        </div>
                      </div>
                      <div className="flex justify-end pt-1">
                        <button
                          type="submit"
                          className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-colors cursor-pointer"
                        >
                          ثبت نهایی اتاق
                        </button>
                      </div>
                    </form>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 4.1: FACILITIES & DORM INFO COMPLETION ===================== */}
          {activeTab === 'facilities' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                {/* Header */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Wrench className="w-4 h-4 text-amber-300" />
                      <span>تکمیل اطلاعات خوابگاه و تجهیزات و امکانات اتاق‌ها</span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      در این ریزسیستم، هر اتاق یک موجودیت دارای محیط و امکانات (یخچال، تخت، جارو و لگن، سطل، تلویزیون، اسپلیت و...) است که باید توسط مدیر دانشگاه تکمیل گردد.
                    </p>
                  </div>

                  {/* Summary Counters */}
                  <div className="flex flex-wrap items-center gap-2 text-xs">
                    <span className="px-3 py-1 bg-slate-900 border border-slate-700 text-slate-300 rounded-xl font-mono">
                      کل اتاق‌ها: <strong className="text-white">{toPersianDigits(rooms.length)}</strong>
                    </span>
                    <span className="px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-xl font-mono">
                      تکمیل شده: <strong className="text-emerald-400">{toPersianDigits(rooms.filter(r => r.isCompleted).length)}</strong>
                    </span>
                    <span className="px-3 py-1 bg-amber-500/10 border border-amber-500/30 text-amber-300 rounded-xl font-mono">
                      تکمیل‌نشده: <strong className="text-amber-400">{toPersianDigits(rooms.filter(r => !r.isCompleted).length)}</strong>
                    </span>
                  </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="flex flex-wrap items-center justify-between gap-3 text-xs">
                  <div className="flex flex-wrap items-center gap-2">
                    <select
                      value={facilityFloorFilter}
                      onChange={(e) => setFacilityFloorFilter(e.target.value as any)}
                      className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی طبقات</option>
                      <option value="همکف">همکف</option>
                      <option value="طبقه اول">طبقه اول</option>
                      <option value="طبقه دوم">طبقه دوم</option>
                    </select>

                    <select
                      value={facilityCompletionFilter}
                      onChange={(e) => setFacilityCompletionFilter(e.target.value as any)}
                      className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی وضعیت‌ها</option>
                      <option value="incomplete">فقط تکمیل‌نشده ⚠️</option>
                      <option value="completed">تکمیل‌شده ✓</option>
                    </select>
                  </div>

                  <div className="relative w-full sm:w-72">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={facilitySearchQuery}
                      onChange={(e) => setFacilitySearchQuery(e.target.value)}
                      placeholder="جستجوی شماره اتاق یا امکانات (یخچال، تلویزیون...)"
                      className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500"
                    />
                  </div>
                </div>

                {/* Rooms Facility Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {rooms
                    .filter(r => {
                      if (facilityFloorFilter !== 'all' && r.floor !== facilityFloorFilter) return false;
                      if (facilityCompletionFilter === 'completed' && !r.isCompleted) return false;
                      if (facilityCompletionFilter === 'incomplete' && r.isCompleted) return false;
                      if (facilitySearchQuery.trim()) {
                        const q = facilitySearchQuery.trim().toLowerCase();
                        const matchNumber = r.roomNumber.toLowerCase().includes(q);
                        const matchFloor = r.floor.toLowerCase().includes(q);
                        const matchCustom = (r.facilities?.customEquipment || []).some(c => c.toLowerCase().includes(q));
                        const matchRef = (r.facilities?.refrigeratorModel || '').toLowerCase().includes(q);
                        if (!matchNumber && !matchFloor && !matchCustom && !matchRef) return false;
                      }
                      return true;
                    })
                    .map(room => {
                      const occupants = allocations.filter(a => a.isActive && a.roomId === room.id);
                      const isComplete = !!room.isCompleted;

                      return (
                        <div
                          key={room.id}
                          className={`rounded-2xl border p-5 space-y-4 transition-all relative overflow-hidden flex flex-col justify-between ${
                            isComplete
                              ? 'bg-slate-900/80 border-slate-800 hover:border-slate-700'
                              : 'bg-amber-950/20 border-amber-500/40 hover:border-amber-500 shadow-md'
                          }`}
                        >
                          <div>
                            {/* Card Header */}
                            <div className="flex items-center justify-between border-b border-slate-800/80 pb-3">
                              <div className="flex items-center gap-2">
                                <span className={`p-2 rounded-xl text-xs font-mono font-bold ${
                                  isComplete ? 'bg-indigo-500/20 text-indigo-300' : 'bg-amber-500/20 text-amber-300'
                                }`}>
                                  اتاق {room.roomNumber}
                                </span>
                                <div>
                                  <span className="text-xs font-bold text-white block">{room.floor}</span>
                                  <span className="text-[10px] text-slate-400">
                                    ظرفیت: {toPersianDigits(room.capacity)} تخت ({toPersianDigits(occupants.length)} دانشجو ساکن)
                                  </span>
                                </div>
                              </div>

                              <div>
                                {isComplete ? (
                                  <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">
                                    تکمیل شده ✓
                                  </span>
                                ) : (
                                  <span className="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded text-[10px] font-bold animate-pulse">
                                    تکمیل‌نشده ⚠️
                                  </span>
                                )}
                              </div>
                            </div>

                            {/* Facilities Overview List */}
                            <div className="pt-3 space-y-2 text-xs">
                              {isComplete && room.facilities ? (
                                <div className="space-y-1.5 text-slate-300">
                                  <div className="flex items-center justify-between text-[11px] p-1.5 bg-slate-800/50 rounded-lg">
                                    <span className="text-slate-400">یخچال اتاق:</span>
                                    <strong className="text-white">
                                      {toPersianDigits(room.facilities.refrigeratorCount)} عدد ({room.facilities.refrigeratorModel || 'استاندارد'})
                                    </strong>
                                  </div>

                                  <div className="flex items-center justify-between text-[11px] p-1.5 bg-slate-800/50 rounded-lg">
                                    <span className="text-slate-400">تخت‌ها:</span>
                                    <strong className="text-white">
                                      {toPersianDigits(room.facilities.bedCount)} تخت ({room.facilities.bedType || 'دو طبقه'})
                                    </strong>
                                  </div>

                                  <div className="flex items-center justify-between text-[11px] p-1.5 bg-slate-800/50 rounded-lg">
                                    <span className="text-slate-400">ابزار نظافت (جارو، لگن، سطل):</span>
                                    <span className="text-emerald-400 font-bold">
                                      {room.facilities.cleaningTools?.hasBroom ? 'جارو ✓ ' : ''}
                                      {room.facilities.cleaningTools?.hasBasin ? 'لگن ✓ ' : ''}
                                      {room.facilities.cleaningTools?.hasTrashCan ? 'سطل ✓' : ''}
                                    </span>
                                  </div>

                                  <div className="flex items-center justify-between text-[11px] p-1.5 bg-slate-800/50 rounded-lg">
                                    <span className="text-slate-400">امکانات ویژه:</span>
                                    <span className="text-amber-300">
                                      {room.facilities.hasTv ? 'تلویزیون دارد • ' : ''}
                                      {room.facilities.hasAc ? 'اسپلیت/کولر دارد • ' : ''}
                                      {room.facilities.hasHeater ? 'بخاری/شوفاژ' : ''}
                                    </span>
                                  </div>

                                  {room.facilities.customEquipment && room.facilities.customEquipment.length > 0 && (
                                    <div className="flex flex-wrap gap-1 pt-1">
                                      {room.facilities.customEquipment.map((eq, i) => (
                                        <span key={i} className="px-1.5 py-0.5 rounded bg-indigo-900/50 border border-indigo-700/50 text-indigo-200 text-[10px]">
                                          {eq}
                                        </span>
                                      ))}
                                    </div>
                                  )}
                                </div>
                              ) : (
                                <div className="p-4 rounded-xl bg-slate-900/60 border border-dashed border-amber-500/30 text-center space-y-1">
                                  <AlertCircle className="w-6 h-6 text-amber-400 mx-auto" />
                                  <span className="text-amber-300 font-bold block text-xs">اطلاعات امکانات ثبت نشده است</span>
                                  <p className="text-[10px] text-slate-400">
                                    برای تعیین یخچال، نوع تخت، تجهیزات نظافت و امکانات خاص اتاق روی دکمه زیر کلیک کنید.
                                  </p>
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Action Button */}
                          <div className="pt-3 border-t border-slate-800/80">
                            {isComplete ? (
                              <button
                                type="button"
                                onClick={() => setSelectedRoomForFacilities(room)}
                                className="w-full py-2 px-3 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center justify-center gap-1.5 border border-slate-700"
                              >
                                <Edit className="w-3.5 h-3.5" />
                                <span>ویرایش و به‌روزرسانی امکانات اتاق</span>
                              </button>
                            ) : (
                              <button
                                type="button"
                                onClick={() => setSelectedRoomForFacilities(room)}
                                className="w-full py-2 px-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/20"
                              >
                                <Plus className="w-3.5 h-3.5" />
                                <span>تکمیل اطلاعات و ثبت امکانات این خوابگاه</span>
                              </button>
                            )}
                          </div>
                        </div>
                      );
                    })}
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 4.2: MAINTENANCE & DAMAGE REPORTS (NOTIFICATIONS & EXPENSES) ===================== */}
          {activeTab === 'maintenance' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                {/* Header */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Bell className="w-4 h-4 text-rose-400" />
                      <span>اطلاع‌رسانی خرابی‌ها، تعمیرات و گزارش مالی مخارج و فاکتورها</span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      گزارش‌گیری کلی موارد تعمیری و خرابی در بازه‌های هفتگی و ماهانه به همراه اسناد خرج و مخارج، فاکتورهای رسمی و ثبت زمان گزارش و رفع اشکال در دیتابیس.
                    </p>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => setShowMaintenancePrintModal(true)}
                      className="px-4 py-2 bg-gradient-to-r from-amber-600 to-indigo-600 hover:from-amber-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer"
                    >
                      <Receipt className="w-4 h-4" />
                      <span>چاپ رسمی صورت مخارج و فاکتورها (A4)</span>
                    </button>
                  </div>
                </div>

                {/* Period Selector & Financial Summary Widgets */}
                <div className="space-y-3">
                  <div className="flex flex-wrap items-center justify-between gap-2 bg-slate-900/80 p-2.5 rounded-2xl border border-slate-800 text-xs">
                    <div className="flex items-center gap-1.5">
                      <span className="text-slate-400 font-bold flex items-center gap-1">
                        <Calendar className="w-3.5 h-3.5 text-amber-400" />
                        <span>بازه زمانی گزارش‌گیری:</span>
                      </span>
                      <div className="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
                        <button
                          type="button"
                          onClick={() => setMaintenancePeriodFilter('weekly')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            maintenancePeriodFilter === 'weekly' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          بازه هفتگی (۷ روز اخیر)
                        </button>
                        <button
                          type="button"
                          onClick={() => setMaintenancePeriodFilter('monthly')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            maintenancePeriodFilter === 'monthly' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          بازه ماهانه (۳۰ روز اخیر)
                        </button>
                        <button
                          type="button"
                          onClick={() => setMaintenancePeriodFilter('all')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            maintenancePeriodFilter === 'all' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          گزارش کل دوره‌ها
                        </button>
                      </div>
                    </div>

                    <div className="text-[11px] text-amber-300 font-mono">
                      {maintenancePeriodFilter === 'weekly' ? '📅 بازه: ۷ روز اخیر' : maintenancePeriodFilter === 'monthly' ? '📅 بازه: ۳۰ روز اخیر' : '📅 کل سوابق ثبت‌شده'}
                    </div>
                  </div>

                  {/* 4 Expense & Maintenance Metric Cards */}
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div className="p-3.5 bg-gradient-to-br from-amber-500/10 to-yellow-500/5 border border-amber-500/30 rounded-2xl">
                      <span className="text-[10px] text-amber-400 block mb-0.5">جمع کل مخارج و هزینه‌های تعمیر</span>
                      <strong className="text-xl font-black text-amber-300 font-mono block">
                        {toPersianDigits(maintenanceExpensesStats.totalCost.toLocaleString())} <span className="text-xs font-normal">ریال</span>
                      </strong>
                      <span className="text-[10px] text-slate-400 mt-1 block">
                        معادل {toPersianDigits((maintenanceExpensesStats.totalCost / 10).toLocaleString())} تومان
                      </span>
                    </div>

                    <div className="p-3.5 bg-slate-900/80 border border-slate-800 rounded-2xl">
                      <span className="text-[10px] text-slate-400 block mb-0.5">کل موارد خرابی در بازه</span>
                      <strong className="text-xl font-black text-white font-mono block">
                        {toPersianDigits(maintenanceExpensesStats.totalReports)} مورد
                      </strong>
                      <span className="text-[10px] text-slate-400 mt-1 block">
                        ثبت‌شده توسط سرپرستان
                      </span>
                    </div>

                    <div className="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl">
                      <span className="text-[10px] text-emerald-400 block mb-0.5">تعمیر / خرید انجام‌شده</span>
                      <strong className="text-xl font-black text-emerald-300 font-mono block">
                        {toPersianDigits(maintenanceExpensesStats.resolvedCount)} مورد
                      </strong>
                      <span className="text-[10px] text-emerald-400/80 mt-1 block">
                        {toPersianDigits(maintenanceExpensesStats.invoiceCount)} فاکتور رسمی
                      </span>
                    </div>

                    <div className="p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-2xl">
                      <span className="text-[10px] text-rose-400 block mb-0.5">در انتظار اقدام مدیر</span>
                      <strong className="text-xl font-black text-rose-300 font-mono block">
                        {toPersianDigits(maintenanceExpensesStats.pendingCount)} مورد
                      </strong>
                      <span className="text-[10px] text-rose-400/80 mt-1 block">
                        نیازمند بررسی و خرید قطعه
                      </span>
                    </div>
                  </div>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-center justify-between gap-3 text-xs">
                  <div className="flex flex-wrap items-center gap-2">
                    <select
                      value={maintenanceStatusFilter}
                      onChange={(e) => setMaintenanceStatusFilter(e.target.value as any)}
                      className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی وضعیت‌ها</option>
                      <option value="pending">فقط در انتظار اقدام و تعمیر</option>
                      <option value="resolved">فقط رفع‌شده / مختومه</option>
                    </select>

                    <select
                      value={maintenanceUrgencyFilter}
                      onChange={(e) => setMaintenanceUrgencyFilter(e.target.value as any)}
                      className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی اولویت‌ها</option>
                      <option value="فوری">فوری ⚠️</option>
                      <option value="عادی">عادی</option>
                      <option value="کم‌اهمیت">کم‌اهمیت</option>
                    </select>
                  </div>

                  <div className="relative w-full sm:w-72">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={maintenanceSearchQuery}
                      onChange={(e) => setMaintenanceSearchQuery(e.target.value)}
                      placeholder="جستجوی شماره فاکتور، پیمانکار، قطعه یا اتاق..."
                      className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500"
                    />
                  </div>
                </div>

                {/* Reports List */}
                <div className="space-y-3">
                  {filteredMaintenanceReports.length === 0 ? (
                    <div className="p-8 text-center bg-slate-900/60 rounded-xl border border-dashed border-slate-700">
                      <CheckCircle2 className="w-8 h-8 text-emerald-400 mx-auto mb-2" />
                      <p className="text-sm font-bold text-white">هیچ موردی مطابق فیلترهای انتخابی یافت نشد.</p>
                      <p className="text-xs text-slate-400 mt-1">بازه زمانی یا عبارت جستجو را تغییر دهید.</p>
                    </div>
                  ) : (
                    filteredMaintenanceReports.map(rep => {
                      const isResolved = rep.status.includes('رفع‌شده');

                      return (
                        <div
                          key={rep.id}
                          className={`p-4 rounded-2xl border transition-all text-xs space-y-3 ${
                            isResolved
                              ? 'bg-slate-900/60 border-slate-800'
                              : 'bg-slate-900/90 border-amber-500/40 shadow-md'
                          }`}
                        >
                          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div className="flex items-center gap-2.5">
                              <span className="p-2 rounded-xl bg-amber-500/20 text-amber-300 font-mono font-bold">
                                اتاق {rep.roomNumber} ({rep.floor})
                              </span>
                              <div>
                                <div className="flex items-center gap-2">
                                  <strong className="text-white text-sm">{rep.equipmentName}</strong>
                                  <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                    rep.maintenanceType === 'تعویض / خرید جدید'
                                      ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
                                      : 'bg-blue-500/20 text-blue-300 border border-blue-500/30'
                                  }`}>
                                    {rep.maintenanceType}
                                  </span>
                                  <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                    rep.urgency === 'فوری'
                                      ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse'
                                      : 'bg-slate-800 text-slate-300 border border-slate-700'
                                  }`}>
                                    {rep.urgency}
                                  </span>
                                </div>
                                <span className="text-[10px] text-slate-400 mt-0.5 block">
                                  سرپرست گزارش‌کننده: <strong className="text-indigo-300">{rep.reportedBySupervisorName}</strong> (کد: {rep.reportedBySupervisorId}) • تاریخ و زمان گزارش: {rep.reportedAt}
                                </span>
                              </div>
                            </div>

                            <div className="flex items-center gap-2">
                              {isResolved ? (
                                <span className="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-xl text-xs font-bold flex items-center gap-1">
                                  <CheckCircle2 className="w-3.5 h-3.5" />
                                  <span>{rep.status}</span>
                                </span>
                              ) : (
                                <button
                                  type="button"
                                  onClick={() => setSelectedReportForResolution(rep)}
                                  className="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 rounded-xl text-xs font-bold transition-all shadow-md cursor-pointer flex items-center gap-1.5"
                                >
                                  <Wrench className="w-3.5 h-3.5" />
                                  <span>ثبت اقدام مدیر / صدور فاکتور و رفع عیب</span>
                                </button>
                              )}
                            </div>
                          </div>

                          {/* Supervisor's Description */}
                          <div className="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                            <span className="text-[11px] text-slate-400 block mb-0.5">شرح نقص توسط سرپرست:</span>
                            <p className="text-slate-200 text-xs">{rep.description}</p>
                          </div>

                          {/* If Resolved, show resolution details and Invoice information */}
                          {isResolved && (
                            <div className="p-3.5 bg-emerald-950/25 border border-emerald-500/30 rounded-xl space-y-2 text-[11px] text-emerald-200">
                              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-500/20 pb-2">
                                <span className="font-bold flex items-center gap-1.5 text-white">
                                  <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                                  <span>اقدام انجام‌شده: {rep.resolutionAction}</span>
                                </span>
                                <span>زمان رفع نقص: <strong className="font-mono text-white">{rep.resolvedAt}</strong></span>
                                <span>اقدام‌کننده: <strong>{rep.resolvedByManagerName}</strong></span>
                              </div>

                              {/* Invoice Details Box */}
                              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 bg-slate-950/50 p-2.5 rounded-xl border border-emerald-500/20">
                                <div>
                                  <span className="text-slate-400 block text-[10px]">شماره فاکتور مالی:</span>
                                  <strong className="font-mono text-cyan-300 text-xs">
                                    {rep.invoiceNumber || 'INV-1403-1001'}
                                  </strong>
                                </div>
                                <div>
                                  <span className="text-slate-400 block text-[10px]">فروشگاه / پیمانکار طرف حساب:</span>
                                  <strong className="text-slate-200 text-xs">
                                    {rep.contractorOrStoreName || 'خدمات فنی و تاسیسات پردیس'}
                                  </strong>
                                </div>
                                <div>
                                  <span className="text-slate-400 block text-[10px]">مبلغ پرداختی فاکتور:</span>
                                  <strong className="font-mono text-amber-300 text-sm">
                                    {rep.resolutionCost !== undefined
                                      ? `${toPersianDigits(rep.resolutionCost.toLocaleString())} ریال`
                                      : 'رایگان / از انبار'}
                                  </strong>
                                </div>
                              </div>

                              {rep.resolutionNotes && (
                                <p className="text-slate-300 text-[11px] pt-1">
                                  <strong>یادداشت و تاییدیه مالی مدیر:</strong> {rep.resolutionNotes}
                                </p>
                              )}

                              <div className="pt-2 border-t border-emerald-500/20 flex items-center justify-between gap-2 flex-wrap">
                                <span className="text-[10px] text-emerald-300 flex items-center gap-1 font-bold">
                                  <span>✓</span>
                                  <span>سند مالی و فاکتور رسمی تایید و تسویه شد</span>
                                </span>
                                <button
                                  type="button"
                                  onClick={() => setSelectedReportForSingleInvoice(rep)}
                                  className="px-3.5 py-1.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
                                >
                                  <FileText className="w-3.5 h-3.5" />
                                  <span>مشاهده فاکتور و چاپ صورتحساب 📄</span>
                                </button>
                              </div>
                            </div>
                          )}
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 4.3: NIGHTLY CLEANLINESS & PERFORMANCE RANKINGS ===================== */}
          {activeTab === 'cleanliness' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                {/* Header */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Sparkles className="w-4 h-4 text-cyan-400" />
                      <span>ارزیابی نظافت خوابگاه‌ها و کارنامه عملکرد هفتگی / ماهانه اتاق‌ها</span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      ثبت نمرات روز به روز توسط سرپرستان (در ۴ سطح عالی، خوب، متوسط، ضعیف) و گزارش‌گیری رتبه‌بندی عملکرد در بازه‌های هفتگی و ماهانه از بهترین تا بدترین اتاق به همراه صدور کارنامه چاپی رسمی.
                    </p>
                  </div>

                  <div className="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      onClick={() => setShowWeeklyMonthlyPrintModal(true)}
                      className="px-3.5 py-2 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
                    >
                      <Trophy className="w-4 h-4 text-yellow-300" />
                      <span>چاپ کارنامه رتبه‌بندی عملکرد (A4)</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setShowCleanlinessPrintModal(true)}
                      className="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-cyan-300 rounded-xl text-xs font-bold transition-all border border-slate-700 flex items-center gap-1.5 cursor-pointer"
                    >
                      <Printer className="w-4 h-4" />
                      <span>چاپ سرشماری شبانه (A4)</span>
                    </button>
                  </div>
                </div>

                {/* View Mode Selector Tabs */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/80 p-2 rounded-2xl border border-slate-800">
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => setCleanlinessViewMode('ranking')}
                      className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
                        cleanlinessViewMode === 'ranking'
                          ? 'bg-gradient-to-r from-cyan-600 to-indigo-600 text-white shadow-md'
                          : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                      }`}
                    >
                      <Trophy className="w-3.5 h-3.5 text-yellow-300" />
                      <span>رتبه‌بندی عملکرد هفتگی و ماهانه اتاق‌ها (از بهترین تا بدترین)</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setCleanlinessViewMode('daily')}
                      className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
                        cleanlinessViewMode === 'daily'
                          ? 'bg-gradient-to-r from-cyan-600 to-indigo-600 text-white shadow-md'
                          : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                      }`}
                    >
                      <Calendar className="w-3.5 h-3.5 text-cyan-300" />
                      <span>مشاهده سرشماری روزانه (بر اساس تاریخ سرپرستی)</span>
                    </button>
                  </div>

                  {cleanlinessViewMode === 'ranking' && (
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                      <div className="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
                        <button
                          type="button"
                          onClick={() => setCleanlinessRankingPeriod('weekly')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            cleanlinessRankingPeriod === 'weekly' ? 'bg-cyan-600 text-white' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          هفتگی (۷ روز اخیر)
                        </button>
                        <button
                          type="button"
                          onClick={() => setCleanlinessRankingPeriod('monthly')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            cleanlinessRankingPeriod === 'monthly' ? 'bg-cyan-600 text-white' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          ماهانه (۳۰ روز اخیر)
                        </button>
                        <button
                          type="button"
                          onClick={() => setCleanlinessRankingPeriod('all')}
                          className={`px-3 py-1 rounded-lg font-bold transition-colors cursor-pointer ${
                            cleanlinessRankingPeriod === 'all' ? 'bg-cyan-600 text-white' : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          کل دوره‌ها
                        </button>
                      </div>

                      <select
                        value={cleanlinessRankingSort}
                        onChange={(e) => setCleanlinessRankingSort(e.target.value as any)}
                        className="px-3 py-1.5 bg-slate-950 border border-slate-800 text-cyan-300 rounded-xl outline-none font-bold"
                      >
                        <option value="best_to_worst">مرتب‌سازی: از بهترین به بدترین اتاق 🏆</option>
                        <option value="worst_to_best">مرتب‌سازی: از ضعیف‌ترین به بهترین اتاق ⚠️</option>
                      </select>
                    </div>
                  )}
                </div>

                {/* ===================== VIEW 1: PERIODIC RANKING MODE ===================== */}
                {cleanlinessViewMode === 'ranking' && (
                  <div className="space-y-4">
                    {/* Top Room Banner & Overall Stats */}
                    {(() => {
                      const topRoom = cleanlinessRankings[0];
                      const avgAll = cleanlinessRankings.length > 0
                        ? (cleanlinessRankings.reduce((acc, r) => acc + r.averageScore, 0) / cleanlinessRankings.length).toFixed(1)
                        : '0';
                      const needyRooms = cleanlinessRankings.filter(r => r.gradeCounts.poor > 0).length;

                      return (
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                          {topRoom && (
                            <div className="p-4 bg-gradient-to-r from-yellow-500/10 via-amber-500/10 to-transparent border border-yellow-500/40 rounded-2xl flex items-center justify-between col-span-1 md:col-span-2">
                              <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-2xl bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 flex items-center justify-center text-2xl font-black shrink-0">
                                  🏆
                                </div>
                                <div>
                                  <div className="flex items-center gap-2">
                                    <span className="text-[10px] bg-yellow-500/20 text-yellow-300 px-2 py-0.5 rounded-full font-bold border border-yellow-500/30">
                                      برترین اتاق دوره ({cleanlinessRankingPeriod === 'weekly' ? 'هفتگی' : 'ماهانه'})
                                    </span>
                                  </div>
                                  <h4 className="text-sm font-black text-white mt-0.5">
                                    اتاق {topRoom.roomNumber} ({topRoom.floor})
                                  </h4>
                                  <span className="text-[11px] text-slate-300 block">
                                    نمره درخشان: <strong className="text-yellow-300 font-mono">{toPersianDigits(topRoom.averageScore)} از ۲۰</strong> ({toPersianDigits(topRoom.totalInspections)} ارزیابی شبانه)
                                  </span>
                                </div>
                              </div>
                              <span className="text-xs font-mono font-bold text-yellow-400 bg-yellow-950/60 px-3 py-1 rounded-xl border border-yellow-700/50">
                                رتبه ۱ کل خوابگاه
                              </span>
                            </div>
                          )}

                          <div className="p-3.5 bg-slate-900/80 border border-slate-800 rounded-2xl flex flex-col justify-between">
                            <span className="text-[10px] text-slate-400 block mb-0.5">میانگین کل نظافت خوابگاه</span>
                            <div className="flex items-baseline justify-between">
                              <strong className="text-2xl font-black text-cyan-300 font-mono">{toPersianDigits(avgAll)}</strong>
                              <span className="text-slate-400 text-[11px]">از ۲۰ نمره</span>
                            </div>
                            <span className="text-[10px] text-slate-400 mt-1 block">مبتنی بر تمامی ارزیابی‌ها</span>
                          </div>

                          <div className="p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex flex-col justify-between">
                            <span className="text-[10px] text-rose-300 block mb-0.5">اتاق‌های نیازمند تذکر و بهبود</span>
                            <div className="flex items-baseline justify-between">
                              <strong className="text-2xl font-black text-rose-300 font-mono">{toPersianDigits(needyRooms)} اتاق</strong>
                              <span className="text-rose-400 text-[11px]">دارای نمره ضعیف</span>
                            </div>
                            <span className="text-[10px] text-rose-300/80 mt-1 block">نیازمند پیگیری توسط سرپرست شب</span>
                          </div>
                        </div>
                      );
                    })()}

                    {/* Rankings Table with Daily Scores Log */}
                    <div className="overflow-x-auto">
                      <table className="w-full text-right text-xs">
                        <thead>
                          <tr className="bg-slate-900/90 text-slate-300 border-b border-slate-800">
                            <th className="p-3 text-center">رتبه</th>
                            <th className="p-3">شماره اتاق و طبقه</th>
                            <th className="p-3">نمره عملکرد (از ۲۰)</th>
                            <th className="p-3">تفکیک سطوح ۴گانه (دوره)</th>
                            <th className="p-3">نمرات هر روز ثبت شده (تاریخچه روز به روز)</th>
                            <th className="p-3 text-center">وضعیت کارنامه</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                          {cleanlinessRankings.map((ranking) => {
                            const isTopThree = ranking.rank <= 3;
                            const hasPoor = ranking.gradeCounts.poor > 0;

                            return (
                              <tr key={ranking.roomId} className="hover:bg-slate-800/40 transition-colors">
                                <td className="p-3 text-center">
                                  {ranking.rank === 1 && (
                                    <span className="w-8 h-8 rounded-xl bg-yellow-500/20 text-yellow-300 border border-yellow-500/40 inline-flex items-center justify-center font-black text-sm">
                                      🥇 ۱
                                    </span>
                                  )}
                                  {ranking.rank === 2 && (
                                    <span className="w-8 h-8 rounded-xl bg-slate-300/20 text-slate-200 border border-slate-400/40 inline-flex items-center justify-center font-black text-sm">
                                      🥈 ۲
                                    </span>
                                  )}
                                  {ranking.rank === 3 && (
                                    <span className="w-8 h-8 rounded-xl bg-amber-700/20 text-amber-400 border border-amber-600/40 inline-flex items-center justify-center font-black text-sm">
                                      🥉 ۳
                                    </span>
                                  )}
                                  {ranking.rank > 3 && (
                                    <span className="font-mono font-bold text-slate-400 text-xs">
                                      #{toPersianDigits(ranking.rank)}
                                    </span>
                                  )}
                                </td>

                                <td className="p-3">
                                  <div className="flex items-center gap-2">
                                    <button
                                      type="button"
                                      onClick={() => {
                                        const r = rooms.find(item => item.id === ranking.roomId);
                                        if (r) setSelectedRoomForDetails(r);
                                      }}
                                      className="font-bold font-mono text-cyan-300 text-sm hover:underline cursor-pointer"
                                    >
                                      اتاق {ranking.roomNumber}
                                    </button>
                                    <span className="text-[10px] text-slate-400">({ranking.floor})</span>
                                  </div>
                                </td>

                                <td className="p-3">
                                  <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                      <strong className={`font-mono text-base font-extrabold ${
                                        ranking.averageScore >= 18 ? 'text-emerald-400' :
                                        ranking.averageScore >= 14 ? 'text-blue-400' :
                                        ranking.averageScore >= 10 ? 'text-amber-400' : 'text-rose-400'
                                      }`}>
                                        {toPersianDigits(ranking.averageScore)}
                                      </strong>
                                      <span className="text-slate-500 text-[10px]">/ ۲۰</span>
                                    </div>
                                    <div className="flex gap-0.5 text-xs text-yellow-400">
                                      {'★'.repeat(Math.round(ranking.averageScore / 4))}
                                      <span className="text-slate-700">{'★'.repeat(Math.max(0, 5 - Math.round(ranking.averageScore / 4)))}</span>
                                    </div>
                                  </div>
                                </td>

                                <td className="p-3">
                                  <div className="flex flex-wrap gap-1 text-[10px] font-mono">
                                    {ranking.gradeCounts.excellent > 0 && (
                                      <span className="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        عالی: {toPersianDigits(ranking.gradeCounts.excellent)}
                                      </span>
                                    )}
                                    {ranking.gradeCounts.good > 0 && (
                                      <span className="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                        خوب: {toPersianDigits(ranking.gradeCounts.good)}
                                      </span>
                                    )}
                                    {ranking.gradeCounts.average > 0 && (
                                      <span className="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        متوسط: {toPersianDigits(ranking.gradeCounts.average)}
                                      </span>
                                    )}
                                    {ranking.gradeCounts.poor > 0 && (
                                      <span className="px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse font-bold">
                                        ضعیف: {toPersianDigits(ranking.gradeCounts.poor)} ⚠️
                                      </span>
                                    )}
                                  </div>
                                </td>

                                {/* Daily Inspection Scores */}
                                <td className="p-3 max-w-md">
                                  {!ranking.dailyHistory || ranking.dailyHistory.length === 0 ? (
                                    <span className="text-slate-500 text-[10px]">بدون ثبت ارزیابی در این دوره</span>
                                  ) : (
                                    <div className="flex flex-wrap gap-1.5">
                                      {(ranking.dailyHistory || []).map((d, idx) => (
                                        <div
                                          key={idx}
                                          className={`px-2 py-1 rounded-lg border text-[10px] flex items-center gap-1 ${
                                            d.grade === 'عالی' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30' :
                                            d.grade === 'خوب' ? 'bg-blue-500/10 text-blue-300 border-blue-500/30' :
                                            d.grade === 'متوسط' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' :
                                            'bg-rose-500/20 text-rose-300 border-rose-500/40 animate-pulse'
                                          }`}
                                          title={`تاریخ: ${d.date} | ارزیاب: ${d.supervisorName} | توضیحات: ${d.notes || 'ندارد'}`}
                                        >
                                          <span className="font-mono">{d.date.slice(5)}:</span>
                                          <strong>{d.grade}</strong>
                                        </div>
                                      ))}
                                    </div>
                                  )}
                                </td>

                                <td className="p-3 text-center">
                                  {isTopThree ? (
                                    <span className="px-2.5 py-1 bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 rounded-xl text-[10px] font-bold inline-flex items-center gap-1">
                                      <span>🏆 اتاق نمونه دوره</span>
                                    </span>
                                  ) : hasPoor ? (
                                    <span className="px-2.5 py-1 bg-rose-500/20 text-rose-300 border border-rose-500/40 rounded-xl text-[10px] font-bold inline-flex items-center gap-1">
                                      <span>⚠️ اخطار و لزوم بهبود</span>
                                    </span>
                                  ) : (
                                    <span className="px-2.5 py-1 bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-[10px] font-bold">
                                      استاندارد ✓
                                    </span>
                                  )}
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}

                {/* ===================== VIEW 2: DAILY INSPECTION LOG MODE ===================== */}
                {cleanlinessViewMode === 'daily' && (
                  <div className="space-y-4">
                    {/* Filter and Date Bar */}
                    <div className="flex flex-wrap items-center justify-between gap-3 text-xs">
                      <div className="flex flex-wrap items-center gap-2">
                        <div className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-white">
                          <Calendar className="w-3.5 h-3.5 text-indigo-400" />
                          <span>تاریخ سرشماری:</span>
                          <select
                            value={selectedCleanlinessDate}
                            onChange={(e) => setSelectedCleanlinessDate(e.target.value)}
                            className="bg-transparent border-none text-cyan-300 font-mono font-bold outline-none cursor-pointer"
                          >
                            {Array.from(new Set(cleanlinessRecords.map(c => c.inspectionDate))).map(d => (
                              <option key={d} value={d} className="bg-slate-900 text-white">
                                {d}
                              </option>
                            ))}
                          </select>
                        </div>

                        <select
                          value={cleanlinessFloorFilter}
                          onChange={(e) => setCleanlinessFloorFilter(e.target.value as any)}
                          className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                        >
                          <option value="all">تمامی طبقات</option>
                          <option value="همکف">همکف</option>
                          <option value="طبقه اول">طبقه اول</option>
                          <option value="طبقه دوم">طبقه دوم</option>
                        </select>

                        <select
                          value={cleanlinessGradeFilter}
                          onChange={(e) => setCleanlinessGradeFilter(e.target.value as any)}
                          className="px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                        >
                          <option value="all">تمامی سطوح نظافت</option>
                          <option value="عالی">عالی (⭐️⭐️⭐️⭐️)</option>
                          <option value="خوب">خوب (⭐️⭐️⭐️)</option>
                          <option value="متوسط">متوسط (⭐️⭐️)</option>
                          <option value="ضعیف">ضعیف (⚠️ اخطار)</option>
                        </select>
                      </div>
                    </div>

                    {/* Summary Metrics for selected date */}
                    {(() => {
                      const dayRecords = cleanlinessRecords.filter(c => c.inspectionDate === selectedCleanlinessDate);
                      const total = dayRecords.length;
                      const exc = dayRecords.filter(c => c.grade === 'عالی').length;
                      const gd = dayRecords.filter(c => c.grade === 'خوب').length;
                      const avg = dayRecords.filter(c => c.grade === 'متوسط').length;
                      const poor = dayRecords.filter(c => c.grade === 'ضعیف').length;

                      return (
                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                          <div className="p-3.5 bg-slate-900/80 border border-slate-800 rounded-xl">
                            <span className="text-[10px] text-slate-400 block mb-0.5">اتاق‌های ارزیابی شده</span>
                            <strong className="text-xl font-black text-white font-mono">{toPersianDigits(total)} اتاق</strong>
                          </div>
                          <div className="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                            <span className="text-[10px] text-emerald-400 block mb-0.5">سطح ۱: عالی</span>
                            <strong className="text-xl font-black text-emerald-300 font-mono">{toPersianDigits(exc)}</strong>
                          </div>
                          <div className="p-3.5 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                            <span className="text-[10px] text-blue-400 block mb-0.5">سطح ۲: خوب</span>
                            <strong className="text-xl font-black text-blue-300 font-mono">{toPersianDigits(gd)}</strong>
                          </div>
                          <div className="p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <span className="text-[10px] text-amber-400 block mb-0.5">سطح ۳: متوسط</span>
                            <strong className="text-xl font-black text-amber-300 font-mono">{toPersianDigits(avg)}</strong>
                          </div>
                          <div className="p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-xl">
                            <span className="text-[10px] text-rose-400 block mb-0.5">سطح ۴: ضعیف</span>
                            <strong className="text-xl font-black text-rose-300 font-mono">{toPersianDigits(poor)}</strong>
                          </div>
                        </div>
                      );
                    })()}

                    {/* Cleanliness Records Table */}
                    <div className="overflow-x-auto">
                      <table className="w-full text-right text-xs">
                        <thead>
                          <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                            <th className="p-3">شماره اتاق</th>
                            <th className="p-3">طبقه</th>
                            <th className="p-3">سطح نظافت (۴ سطح)</th>
                            <th className="p-3">توضیحات و نکات سرپرست</th>
                            <th className="p-3">سرپرست ثبت‌کننده</th>
                            <th className="p-3">زمان دقیق ثبت</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                          {cleanlinessRecords
                            .filter(c => {
                              if (c.inspectionDate !== selectedCleanlinessDate) return false;
                              if (cleanlinessFloorFilter !== 'all' && c.floor !== cleanlinessFloorFilter) return false;
                              if (cleanlinessGradeFilter !== 'all' && c.grade !== cleanlinessGradeFilter) return false;
                              return true;
                            })
                            .map(record => (
                              <tr key={record.id} className="hover:bg-slate-800/40 transition-colors">
                                <td className="p-3 font-bold font-mono text-cyan-300 text-sm">
                                  اتاق {record.roomNumber}
                                </td>
                                <td className="p-3 text-slate-300">{record.floor}</td>
                                <td className="p-3">
                                  {record.grade === 'عالی' && (
                                    <span className="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-lg text-[10px] font-bold inline-flex items-center gap-1">
                                      <span>⭐️ عالی (کاملاً مرتب)</span>
                                    </span>
                                  )}
                                  {record.grade === 'خوب' && (
                                    <span className="px-2.5 py-1 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded-lg text-[10px] font-bold inline-flex items-center gap-1">
                                      <span>خوب (استاندارد)</span>
                                    </span>
                                  )}
                                  {record.grade === 'متوسط' && (
                                    <span className="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-lg text-[10px] font-bold inline-flex items-center gap-1">
                                      <span>متوسط (نیاز به توجه)</span>
                                    </span>
                                  )}
                                  {record.grade === 'ضعیف' && (
                                    <span className="px-2.5 py-1 bg-rose-500/20 text-rose-300 border border-rose-500/40 rounded-lg text-[10px] font-bold inline-flex items-center gap-1 animate-pulse">
                                      <span>⚠️ ضعیف (نامرتب / اخطار)</span>
                                    </span>
                                  )}
                                </td>
                                <td className="p-3 text-slate-300 max-w-xs">
                                  {record.notes || <span className="text-slate-500 italic">بدون یادداشت تکمیلی</span>}
                                </td>
                                <td className="p-3 text-slate-300 font-semibold">
                                  {record.supervisorName}
                                </td>
                                <td className="p-3 text-slate-400 font-mono text-[11px]">
                                  {record.recordedAt}
                                </td>
                              </tr>
                            ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ===================== TAB 5: HOUSED STUDENTS & GUEST TIME ===================== */}
          {activeTab === 'students' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <BedDouble className="w-4 h-4 text-indigo-400" />
                      <span>فهرست دانشجویان اسکان‌یافته و تعیین حالت شهریه / میهمانی</span>
                      <span className="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full text-xs font-bold font-mono">
                        {toPersianDigits(filteredHoused.length)} نفر
                      </span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">تغییر اتاق، تغییر وضعیت شهریه و مشاهده کارت شناسایی دانشجویی با کلیک بر روی نام</p>
                  </div>

                  <div className="w-full sm:w-72 relative">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={housedSearchQuery}
                      onChange={(e) => setHousedSearchQuery(e.target.value)}
                      placeholder="جستجوی کدملی، نام، شماره دانشجویی یا اتاق..."
                      className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-indigo-500"
                    />
                  </div>
                </div>

                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead>
                      <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                        <th className="p-3">دانشجو (کلیک جهت کارت)</th>
                        <th className="p-3">شماره دانشجویی / کدملی</th>
                        <th className="p-3">اتاق و طبقه</th>
                        <th className="p-3">حالت خوابگاه</th>
                        <th className="p-3">مهلت اقامت میهمانی</th>
                        <th className="p-3 text-center">عملیات</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/60">
                      {filteredHoused.map(alloc => {
                        const room = rooms.find(r => r.id === alloc.roomId);

                        return (
                          <tr key={alloc.id} className="hover:bg-slate-800/40 transition-colors">
                            <td className="p-3">
                              <button
                                type="button"
                                onClick={() => {
                                  const adm = admittedStudents.find(s => s.studentId === alloc.studentId || s.nationalId === alloc.nationalId);
                                  setSelectedStudentForCard({
                                    studentId: alloc.studentId,
                                    fullName: alloc.studentName,
                                    nationalId: alloc.nationalId,
                                    fieldOfStudy: adm?.fieldOfStudy || 'مهندسی و فناوری',
                                    campus: adm?.campus || 'پردیس فناوری',
                                    roomNumber: room?.roomNumber,
                                    floor: room?.floor,
                                    dormStatus: alloc.dormStatus,
                                    province: 'مرکزی',
                                    city: 'اراک',
                                    mobile: '09120000000'
                                  });
                                }}
                                className="font-bold text-white hover:text-indigo-400 flex items-center gap-1.5 cursor-pointer text-right group"
                                title="کلیک جهت مشاهده کارت دانشجویی هوشمند"
                              >
                                <span>{alloc.studentName}</span>
                                <CreditCard className="w-3.5 h-3.5 text-indigo-400 group-hover:scale-110 transition-transform" />
                              </button>
                            </td>
                            <td className="p-3 font-mono">
                              <span className="text-indigo-400 font-bold block">{toPersianDigits(alloc.studentId)}</span>
                              <span className="text-slate-400 text-[10px]">{toPersianDigits(alloc.nationalId)}</span>
                            </td>
                            <td className="p-3">
                              <span className="font-bold text-emerald-400">اتاق {room?.roomNumber}</span>
                              <span className="text-[10px] text-slate-400 block">{room?.floor}</span>
                            </td>
                            <td className="p-3">
                              <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                alloc.dormStatus === 'میهمان'
                                  ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40'
                                  : alloc.dormStatus === 'بدون شهریه'
                                  ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                                  : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40'
                              }`}>
                                {alloc.dormStatus}
                              </span>
                            </td>
                            <td className="p-3">
                              {alloc.dormStatus === 'میهمان' ? (
                                <div className="space-y-1">
                                  <div className="flex items-center gap-1 text-[11px] text-amber-300 font-mono font-bold">
                                    <Clock className="w-3.5 h-3.5 text-amber-400 shrink-0" />
                                    <span>
                                      {alloc.guestDaysRemaining !== undefined
                                        ? `${toPersianDigits(alloc.guestDaysRemaining)} روز باقی‌مانده (از ${toPersianDigits(alloc.guestDaysTotal || 7)})`
                                        : '۷ روز میهمان'}
                                    </span>
                                  </div>
                                  <button
                                    type="button"
                                    onClick={() => handleReduceGuestDay(alloc.id)}
                                    className="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded text-[10px] transition-colors cursor-pointer"
                                    title="شبیه‌سازی گذر ۱ روز برای اقامت میهمانی"
                                  >
                                    -۱ روز (شبیه‌سازی)
                                  </button>
                                </div>
                              ) : (
                                <span className="text-slate-500 text-[11px]">اسکان دائمی ترمی</span>
                              )}
                            </td>
                            <td className="p-3 text-center">
                              <div className="flex items-center justify-center gap-1.5">
                                <button
                                  type="button"
                                  onClick={() => handleStartEditStudent(alloc)}
                                  className="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 hover:text-white rounded-lg text-[10px] font-bold transition-all border border-indigo-500/30 cursor-pointer"
                                >
                                  تغییر اتاق / وضعیت
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleEvictToUnhoused(alloc)}
                                  className="px-2 py-1 bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white rounded-lg text-[10px] font-bold transition-all border border-rose-500/30 cursor-pointer"
                                  title="لغو اسکان و انتقال به لیست فاقدین اتاق"
                                >
                                  لغو اسکان
                                </button>
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 6: NIGHT ATTENDANCE & PRINTING ===================== */}
          {activeTab === 'attendance' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Attendance Stats Cards */}
              <div className="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
                <div className="p-4 bg-[#1e293b]/70 border border-slate-800 rounded-2xl">
                  <span className="text-[11px] text-slate-400 block mb-1">کل دانشجویان</span>
                  <span className="text-xl font-black text-white font-mono">{toPersianDigits(attendanceStats.total)}</span>
                </div>
                <div className="p-4 bg-emerald-950/40 border border-emerald-500/30 rounded-2xl">
                  <span className="text-[11px] text-emerald-400 block mb-1">حاضر در اتاق</span>
                  <span className="text-xl font-black text-emerald-400 font-mono">{toPersianDigits(attendanceStats.present)}</span>
                </div>
                <div className="p-4 bg-rose-950/40 border border-rose-500/30 rounded-2xl">
                  <span className="text-[11px] text-rose-400 block mb-1">غایب</span>
                  <span className="text-xl font-black text-rose-400 font-mono">{toPersianDigits(attendanceStats.absent)}</span>
                </div>
                <div className="p-4 bg-amber-950/40 border border-amber-500/30 rounded-2xl">
                  <span className="text-[11px] text-amber-400 block mb-1">مرخصی</span>
                  <span className="text-xl font-black text-amber-400 font-mono">{toPersianDigits(attendanceStats.leave)}</span>
                </div>
                <div className="p-4 bg-indigo-950/40 border border-indigo-500/30 rounded-2xl">
                  <span className="text-[11px] text-indigo-400 block mb-1">درصد حضور</span>
                  <span className="text-xl font-black text-indigo-300 font-mono">٪{toPersianDigits(attendanceStats.percent)}</span>
                </div>
              </div>

              {/* Main Attendance Inspection Table */}
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <ClipboardList className="w-4 h-4 text-rose-400" />
                      <span>بررسی وضعیت سرشماری شبانه خوابگاه</span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">ثبت‌شده توسط سرپرست خوابگاه با امکان پرینت فردی و پرینت جامع</p>
                  </div>

                  <div className="flex flex-wrap items-center gap-2.5 text-xs">
                    <div className="w-44">
                      <PersianDatePicker
                        value={attendanceDate}
                        onChange={(jalali, greg) => setAttendanceDate(greg || jalali)}
                        placeholder="انتخاب تاریخ سرشماری"
                      />
                    </div>

                    <select
                      value={attendanceFloorFilter}
                      onChange={(e) => setAttendanceFloorFilter(e.target.value as any)}
                      className="px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی طبقات</option>
                      <option value="همکف">همکف</option>
                      <option value="طبقه اول">طبقه اول</option>
                      <option value="طبقه دوم">طبقه دوم</option>
                    </select>

                    <select
                      value={attendanceStatusFilter}
                      onChange={(e) => setAttendanceStatusFilter(e.target.value as any)}
                      className="px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                    >
                      <option value="all">تمامی وضعیت‌ها</option>
                      <option value="حاضر">حاضر</option>
                      <option value="غایب">غایب</option>
                      <option value="مرخصی">مرخصی</option>
                    </select>

                    <div className="relative">
                      <Search className="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" />
                      <input
                        type="text"
                        value={attendanceSearch}
                        onChange={(e) => setAttendanceSearch(e.target.value)}
                        placeholder="جستجوی کدملی، نام یا شماره دانشجویی..."
                        className="pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none"
                      />
                    </div>

                    <button
                      type="button"
                      onClick={() => setShowBatchPrintModal(true)}
                      className="flex items-center gap-1.5 px-4 py-1.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer"
                    >
                      <Printer className="w-4 h-4" />
                      <span>چاپ گزارش کلی سرشماری</span>
                    </button>
                  </div>
                </div>

                {/* Table */}
                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead>
                      <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                        <th className="p-3">دانشجو</th>
                        <th className="p-3">شماره دانشجویی / کدملی</th>
                        <th className="p-3">اتاق و طبقه</th>
                        <th className="p-3">وضعیت سرشماری</th>
                        <th className="p-3">توضیحات و یادداشت سرپرست</th>
                        <th className="p-3 text-center">چاپ گواهی / پرینت فردی</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/60">
                      {attendanceFilteredList.map(alloc => {
                        const room = rooms.find(r => r.id === alloc.roomId);
                        const record = attendanceRecords.find(
                          r => r.studentId === alloc.studentId && r.attendanceDate === attendanceDate
                        );
                        const status: AttendanceStatus = record ? record.status : 'حاضر';

                        return (
                          <tr key={alloc.id} className="hover:bg-slate-800/40 transition-colors">
                            <td className="p-3 font-bold text-white">{alloc.studentName}</td>
                            <td className="p-3 font-mono">
                              <span className="text-indigo-400 font-bold block">{toPersianDigits(alloc.studentId)}</span>
                              <span className="text-slate-400 text-[10px]">{toPersianDigits(alloc.nationalId)}</span>
                            </td>
                            <td className="p-3">
                              <span className="font-bold text-slate-200">اتاق {room?.roomNumber}</span>
                              <span className="text-[10px] text-slate-400 block">{room?.floor}</span>
                            </td>
                            <td className="p-3">
                              <span className={`px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1 ${
                                status === 'حاضر'
                                  ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                                  : status === 'غایب'
                                  ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40'
                                  : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'
                              }`}>
                                {status === 'حاضر' && 'حاضر ✓'}
                                {status === 'غایب' && 'غایب ✕'}
                                {status === 'مرخصی' && 'مرخصی ⏱'}
                              </span>
                            </td>
                            <td className="p-3 text-[11px] text-slate-300 max-w-xs">
                              {record?.notes || 'سرشماری توسط سرپرست شبانه ثبت گردیده است.'}
                            </td>
                            <td className="p-3 text-center">
                              <button
                                type="button"
                                onClick={() => setIndividualPrintStudent({ alloc, room, record })}
                                className="flex items-center justify-center gap-1.5 mx-auto px-3 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white rounded-lg text-xs font-bold border border-slate-700 hover:border-indigo-500 transition-all cursor-pointer"
                              >
                                <Printer className="w-3.5 h-3.5" />
                                <span>پرینت فردی 🖨</span>
                              </button>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 8: FESTIVALS & VOLUNTEERS & CALLS ===================== */}
          {activeTab === 'festivals' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Top Banner and Call Issuing Button */}
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Trophy className="w-4 h-4 text-yellow-400" />
                      <span>جشنواره‌ها، تیم‌های ورزشی، داوطلبان و صدور فراخوان هدفمند</span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      مدیر می‌تواند برای دانشجویان علاقه‌مند به جشنواره‌های خاص (مذهبی، مهارتی، رباتیک، هنری) یا تیم‌های مشخص ورزشی (فوتبال، والیبال و...) فراخوان هدفمند صادر نماید.
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={() => setShowAddCallModal(true)}
                    className="px-4 py-2.5 bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-400 hover:to-amber-500 text-slate-950 rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer shrink-0"
                  >
                    <Plus className="w-4 h-4" />
                    <span>صدور فراخوان هدفمند جدید +</span>
                  </button>
                </div>

                {/* Section A: Active, Upcoming & Expired Calls */}
                <div className="space-y-4">
                  {/* Status counts and Filter Chips */}
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                    <div className="flex items-center gap-2">
                      <Megaphone className="w-4 h-4 text-amber-400" />
                      <span className="text-xs font-bold text-white">فراخوان‌های ثبت‌شده در پردیس نیشابور:</span>
                    </div>

                    {/* Filter Tabs */}
                    {(() => {
                      const activeCalls = localFestivalCalls.filter(c => getCallStatus(c) === 'active');
                      const upcomingCalls = localFestivalCalls.filter(c => getCallStatus(c) === 'upcoming');
                      const expiredCalls = localFestivalCalls.filter(c => getCallStatus(c) === 'expired');

                      return (
                        <div className="flex flex-wrap items-center gap-1.5 text-[11px]">
                          <button
                            type="button"
                            onClick={() => setCallFilter('all')}
                            className={`px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer ${
                              callFilter === 'all'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                            }`}
                          >
                            همه ({toPersianDigits(localFestivalCalls.length)})
                          </button>
                          <button
                            type="button"
                            onClick={() => setCallFilter('active')}
                            className={`px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5 ${
                              callFilter === 'active'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-emerald-950/40 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-900/40'
                            }`}
                          >
                            <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>فعال ({toPersianDigits(activeCalls.length)})</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => setCallFilter('upcoming')}
                            className={`px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5 ${
                              callFilter === 'upcoming'
                                ? 'bg-amber-600 text-slate-950 shadow-xs'
                                : 'bg-amber-950/40 text-amber-300 border border-amber-500/30 hover:bg-amber-900/40'
                            }`}
                          >
                            <span className="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span>آینده / به‌زودی ({toPersianDigits(upcomingCalls.length)})</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => setCallFilter('expired')}
                            className={`px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5 ${
                              callFilter === 'expired'
                                ? 'bg-slate-600 text-white shadow-xs'
                                : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-750'
                            }`}
                          >
                            <span className="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>پایان‌یافته ({toPersianDigits(expiredCalls.length)})</span>
                          </button>
                        </div>
                      );
                    })()}
                  </div>

                  {/* Calls Grid */}
                  {(() => {
                    const filteredCalls = localFestivalCalls.filter(c => {
                      if (callFilter === 'all') return true;
                      return getCallStatus(c) === callFilter;
                    });

                    if (filteredCalls.length === 0) {
                      return (
                        <div className="p-8 text-center bg-slate-900/60 rounded-2xl border border-dashed border-slate-700 text-slate-400 text-xs space-y-2">
                          <AlertCircle className="w-6 h-6 text-slate-500 mx-auto" />
                          <p>فراخوانی در این دسته‌بندی یافت نشد.</p>
                        </div>
                      );
                    }

                    return (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {filteredCalls.map(call => {
                          const status = getCallStatus(call);

                          // Style according to requirement:
                          // - Active: green border
                          // - Upcoming: yellow border
                          // - Expired: gray, not deleted
                          const cardBorderClass =
                            status === 'active'
                              ? 'border-2 border-emerald-500 bg-slate-900/95 shadow-lg shadow-emerald-500/10'
                              : status === 'upcoming'
                              ? 'border-2 border-amber-400 bg-slate-900/95 shadow-lg shadow-amber-500/10'
                              : 'border-2 border-slate-600 bg-slate-900/50 text-slate-400 opacity-75';

                          return (
                            <div
                              key={call.id}
                              className={`p-4 rounded-2xl space-y-3 text-xs relative transition-all ${cardBorderClass}`}
                            >
                              {/* Header & Badges */}
                              <div className="flex items-start justify-between gap-2 border-b border-slate-800 pb-2.5">
                                <div className="space-y-1.5">
                                  <div className="flex flex-wrap items-center gap-1.5">
                                    {/* Status Badge */}
                                    {status === 'active' && (
                                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center gap-1">
                                        <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                                        🟢 فراخوان فعال و در حال ثبت‌نام
                                      </span>
                                    )}
                                    {status === 'upcoming' && (
                                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1">
                                        <Clock className="w-3 h-3 text-amber-400" />
                                        🟡 شروع در آینده (به‌زودی)
                                      </span>
                                    )}
                                    {status === 'expired' && (
                                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700 flex items-center gap-1">
                                        <AlertCircle className="w-3 h-3 text-slate-500" />
                                        ⚪ مهلت پایان‌یافته (غیرقابل دسترس)
                                      </span>
                                    )}

                                    {/* Category Badge */}
                                    <span className="px-2 py-0.5 bg-slate-800 text-slate-300 rounded-md text-[10px] font-medium">
                                      {call.targetCategory === 'sports' ? (call.targetSportsTeam ? `🎯 ${call.targetSportsTeam}` : 'ورزشی') :
                                       call.targetCategory === 'religious' ? 'قرآنی و معارف' :
                                       call.targetCategory === 'robotics' ? 'رباتیک و هوش مصنوعی' :
                                       call.targetCategory === 'cultural' ? 'فرهنگی و هنری' :
                                       call.targetCategory === 'technical' ? 'المپیاد فنی' : 'عمومی'}
                                    </span>
                                  </div>

                                  <h4 className="font-black text-white text-xs sm:text-sm mt-1">
                                    {call.title}
                                  </h4>
                                </div>

                                <button
                                  type="button"
                                  onClick={() => handleDeleteFestivalCall(call.id)}
                                  className="text-slate-500 hover:text-rose-400 p-1 cursor-pointer shrink-0 transition-colors"
                                  title="حذف دستی از لیست"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </div>

                              {/* Content description */}
                              <p className={`text-[11px] leading-relaxed ${status === 'expired' ? 'text-slate-400 line-clamp-3' : 'text-slate-300'}`}>
                                {call.content}
                              </p>

                              {/* Dates Footer Bar */}
                              <div className="pt-2.5 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-[10px]">
                                <div className="flex items-center gap-3">
                                  {call.startDate && (
                                    <span className="flex items-center gap-1 text-slate-300">
                                      <Calendar className="w-3 h-3 text-indigo-400" />
                                      <span>شروع:</span>
                                      <strong className="font-mono text-indigo-300">{toPersianDigits(call.startDate)}</strong>
                                    </span>
                                  )}
                                  <span className="flex items-center gap-1 text-slate-300">
                                    <Clock className="w-3 h-3 text-rose-400" />
                                    <span>مهلت پایان:</span>
                                    <strong className={`font-mono ${status === 'expired' ? 'text-slate-400 line-through' : 'text-rose-400'}`}>
                                      {toPersianDigits(call.deadlineDate)}
                                    </strong>
                                  </span>
                                </div>

                                <div className="text-slate-400 text-[10px]">
                                  {status === 'upcoming' ? (
                                    <span className="text-amber-400 font-bold">
                                      ⚡ فعال‌سازی خودکار با فرارسیدن تاریخ شروع
                                    </span>
                                  ) : status === 'expired' ? (
                                    <span className="text-slate-400 font-medium">
                                      📁 آرشیو شده (حفظ در سامانه)
                                    </span>
                                  ) : (
                                    <span className="text-emerald-400 font-bold">
                                      ✓ امکان ثبت‌نام فعال
                                    </span>
                                  )}
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    );
                  })()}
                </div>

                {/* Section B: Placeholder Option for Future Announcements Panel (as requested by user) */}
                <div className="p-4 bg-gradient-to-r from-indigo-950/40 via-purple-950/30 to-slate-900 border-2 border-dashed border-indigo-500/40 rounded-2xl space-y-3">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div className="flex items-center gap-2.5">
                      <span className="p-2 rounded-xl bg-indigo-500/20 text-indigo-300">
                        <Megaphone className="w-4 h-4" />
                      </span>
                      <div>
                        <h4 className="text-xs font-bold text-white flex items-center gap-2">
                          <span>سامانه هوشمند و پیشرفته ارسال اطلاعیه و پیامک‌های خوابگاه</span>
                          <span className="px-2 py-0.5 rounded-full text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-mono">
                            در دست طراحی برای نسخه آتی
                          </span>
                        </h4>
                        <span className="text-[11px] text-slate-400 block mt-0.5">
                          طبق درخواست، برای این بخش بعداً پنل مخصوص ارسال اطلاعیه طراحی خواهد شد و فعلاً این گزینه خالی برای پیاده‌سازی آتی تعبیه شده است.
                        </span>
                      </div>
                    </div>

                    <button
                      type="button"
                      disabled
                      className="px-3 py-1.5 bg-slate-800 text-slate-500 rounded-xl text-[11px] font-bold border border-slate-700 cursor-not-allowed opacity-60 self-start sm:self-auto"
                    >
                      پنل اختصاصی اطلاعیه‌ها (به‌زودی)
                    </button>
                  </div>
                </div>

                {/* Section C: Volunteer Students List & Search */}
                <div className="pt-3 border-t border-slate-800 space-y-3">
                  <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                      <h4 className="text-xs font-bold text-white flex items-center gap-1.5">
                        <Users className="w-4 h-4 text-cyan-400" />
                        <span>فهرست داوطلبان ثبت‌نامی پردیس و تیم‌ها</span>
                        <span className="px-2 py-0.5 bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 rounded-full text-[11px] font-mono">
                          {toPersianDigits(filteredFestivals.length)} داوطلب
                        </span>
                      </h4>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 text-xs w-full sm:w-auto">
                      <select
                        value={festivalCategoryFilter}
                        onChange={(e) => setFestivalCategoryFilter(e.target.value)}
                        className="px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none"
                      >
                        <option value="all">همه حوزه‌ها و رویدادها</option>
                        <option value="sports">جشنواره‌های ورزشی و مسابقات پردیس</option>
                        <option value="technical">المپیادها و جشنواره‌های فنی و اختراعات</option>
                        <option value="robotics">رباتیک و هوش مصنوعی</option>
                        <option value="cultural">فرهنگی، ادبی و هنری</option>
                        <option value="religious">مذهبی و قرآنی</option>
                        <option value="skillBased">ملی مهارت و کارآفرینی</option>
                      </select>

                      <div className="relative flex-1 sm:w-64">
                        <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                        <input
                          type="text"
                          value={festivalSearchQuery}
                          onChange={(e) => setFestivalSearchQuery(e.target.value)}
                          placeholder="جستجوی کدملی، نام یا رویداد..."
                          className="w-full pr-8 pl-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-yellow-500"
                        />
                      </div>
                    </div>
                  </div>

                  {filteredFestivals.length === 0 ? (
                    <div className="p-8 text-center bg-slate-900/60 rounded-xl border border-dashed border-slate-700">
                      <Trophy className="w-8 h-8 text-slate-500 mx-auto mb-2" />
                      <p className="text-sm font-bold text-white">دانشجویی در این حوزه یا با این مشخصات یافت نشد.</p>
                      <p className="text-xs text-slate-400 mt-1">فیلتر حوزه یا عبارت جستجو را تغییر دهید.</p>
                    </div>
                  ) : (
                    <div className="overflow-x-auto">
                      <table className="w-full text-right text-xs">
                        <thead>
                          <tr className="bg-slate-900/80 text-slate-300 border-b border-slate-800">
                            <th className="p-3">دانشجو</th>
                            <th className="p-3">شماره دانشجویی / کدملی</th>
                            <th className="p-3">پردیس و رشته</th>
                            <th className="p-3">حوزه‌های ثبت‌نام و داوطلبی</th>
                            <th className="p-3">تماس</th>
                            <th className="p-3 text-center">کارت دانشجویی</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                          {filteredFestivals.map(fest => (
                            <tr key={fest.studentId} className="hover:bg-slate-800/40 transition-colors">
                              <td className="p-3 font-bold text-white">{fest.fullName}</td>
                              <td className="p-3 font-mono">
                                <span className="text-yellow-400 font-bold block">{toPersianDigits(fest.studentId)}</span>
                                <span className="text-slate-400 text-[10px]">{toPersianDigits(fest.nationalId)}</span>
                              </td>
                              <td className="p-3">
                                <span className="block text-slate-200">{fest.fieldOfStudy || 'مهندسی و فناوری'}</span>
                                <span className="text-[10px] text-slate-400">{fest.campus || 'پردیس فناوری'}</span>
                              </td>
                              <td className="p-3">
                                <div className="flex flex-wrap gap-1">
                                  {fest.festivals.map(item => (
                                    <span
                                      key={item.key}
                                      className="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-500/15 text-yellow-300 border border-yellow-500/30"
                                    >
                                      ★ {item.title}
                                    </span>
                                  ))}
                                </div>
                              </td>
                              <td className="p-3 font-mono text-slate-300">{toPersianDigits(fest.mobile || '09120000000')}</td>
                              <td className="p-3 text-center">
                                <button
                                  type="button"
                                  onClick={() => setSelectedStudentForCard({
                                    studentId: fest.studentId,
                                    fullName: fest.fullName,
                                    nationalId: fest.nationalId,
                                    fieldOfStudy: fest.fieldOfStudy,
                                    campus: fest.campus,
                                    mobile: fest.mobile
                                  })}
                                  className="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-yellow-300 rounded-lg text-[10px] font-bold border border-slate-700 hover:border-yellow-500 transition-all cursor-pointer"
                                >
                                  مشاهده کارت 🪪
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* ===================== TAB 9: SKILLS LIVE SEARCH ===================== */}
          {activeTab === 'skills' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                <div className="border-b border-slate-800 pb-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                  <div>
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                      <Sparkles className="w-4 h-4 text-purple-400" />
                      <span>بانک مهارت‌های دانشجویی و استعدادیابی (جستجوی زنده و لحظه‌ای)</span>
                      <span className="px-2 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-full text-xs font-bold font-mono">
                        {toPersianDigits(filteredSkills.length)} دانشجو
                      </span>
                    </h3>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      با تایپ عنوان هر مهارت، دانشجویانی که مهارت‌های نزدیک به عبارت جستجو دارند به صورت لحظه‌ای فیلتر می‌شوند
                    </p>
                  </div>
                </div>

                {/* Big Live Search Box */}
                <div className="space-y-3">
                  <div className="relative">
                    <Search className="w-5 h-5 text-purple-400 absolute right-4 top-3.5" />
                    <input
                      type="text"
                      value={skillLiveQuery}
                      onChange={(e) => setSkillLiveQuery(e.target.value)}
                      placeholder="عنوان مهارت یا نام و کدملی را تایپ کنید (مثلاً: پایتون، فتوشاپ، برق، لینوکس، طراحی وب، تاسیسات...)"
                      className="w-full pr-12 pl-4 py-3 bg-slate-900 border-2 border-purple-500/40 focus:border-purple-500 text-white rounded-2xl text-sm outline-none transition-all shadow-inner"
                      autoFocus
                    />
                    {skillLiveQuery && (
                      <button
                        type="button"
                        onClick={() => setSkillLiveQuery('')}
                        className="absolute left-3 top-3 text-slate-400 hover:text-white text-xs px-2 py-1 bg-slate-800 rounded-lg cursor-pointer"
                      >
                        ✕ پاک کردن
                      </button>
                    )}
                  </div>

                  {/* Quick Skill Suggestion Chips */}
                  <div className="flex flex-wrap items-center gap-1.5 text-xs">
                    <span className="text-slate-400 text-[11px] ml-1">پیشنهادهای پرتکرار:</span>
                    {['پایتون', 'هوش مصنوعی', 'فتوشاپ', 'برق صنعتی', 'الکترونیک', 'اتوکد', 'سالیدورکس', 'مدیریت پروژه', 'تاسیسات', 'طراحی وب'].map(tag => (
                      <button
                        key={tag}
                        type="button"
                        onClick={() => setSkillLiveQuery(tag)}
                        className={`px-2.5 py-1 rounded-xl text-xs font-medium transition-all cursor-pointer ${
                          skillLiveQuery.includes(tag)
                            ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                            : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700'
                        }`}
                      >
                        #{tag}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Students Skills Cards Grid */}
                {filteredSkills.length === 0 ? (
                  <div className="p-10 text-center bg-slate-900/60 rounded-2xl border border-dashed border-slate-700">
                    <AlertCircle className="w-10 h-10 text-purple-400 mx-auto mb-2 opacity-80" />
                    <p className="text-sm font-bold text-white">دانشجویی با مهارت نزدیک به «{skillLiveQuery}» یافت نشد.</p>
                    <p className="text-xs text-slate-400 mt-1">عبارت جستجوی دیگری را وارد کنید یا از تگ‌های پیشنهادی بالا استفاده فرمایید.</p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                    {filteredSkills.map(student => (
                      <div
                        key={student.studentId}
                        className="bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-5 shadow-lg space-y-3.5 transition-all hover:shadow-purple-500/10 flex flex-col justify-between"
                      >
                        <div className="space-y-3">
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                              <div className="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-300 font-bold text-sm">
                                🎓
                              </div>
                              <div>
                                <h4 className="text-sm font-bold text-white">{student.fullName}</h4>
                                <span className="text-[10px] text-slate-400 font-mono">
                                  کدملی: {toPersianDigits(student.nationalId)} | ش.د: {toPersianDigits(student.studentId)}
                                </span>
                              </div>
                            </div>
                          </div>

                          <div className="text-xs text-slate-300">
                            <span className="text-slate-400">رشته: </span>
                            <span className="font-semibold text-slate-200">{student.fieldOfStudy}</span>
                            <span className="text-[10px] text-purple-400 block mt-0.5">{student.campus}</span>
                          </div>

                          {/* Skills List with Highlight for Matching Query */}
                          <div>
                            <span className="text-[11px] text-slate-400 font-bold block mb-1.5">مهارت‌ها و تخصص‌ها:</span>
                            <div className="flex flex-wrap gap-1.5">
                              {student.skills.map((skill, sIdx) => {
                                const isMatch = skillLiveQuery.trim() && skill.toLowerCase().includes(skillLiveQuery.trim().toLowerCase());
                                return (
                                  <span
                                    key={sIdx}
                                    className={`px-2 py-0.5 rounded-lg text-[10px] font-bold transition-all ${
                                      isMatch
                                        ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-sm shadow-amber-500/30 ring-1 ring-amber-300 font-extrabold'
                                        : 'bg-purple-950/40 text-purple-300 border border-purple-500/30'
                                    }`}
                                  >
                                    {isMatch ? '★ ' : ''}{skill}
                                  </span>
                                );
                              })}
                            </div>
                          </div>
                        </div>

                        <div className="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                          <span className="text-slate-400 font-mono text-[11px]">
                            📞 {toPersianDigits(student.mobile || '09120000000')}
                          </span>
                          <button
                            type="button"
                            onClick={() => setSelectedStudentForCard({
                              studentId: student.studentId,
                              fullName: student.fullName,
                              nationalId: student.nationalId,
                              fieldOfStudy: student.fieldOfStudy,
                              campus: student.campus,
                              mobile: student.mobile
                            })}
                            className="px-2.5 py-1 bg-purple-600/30 hover:bg-purple-600 text-purple-200 hover:text-white rounded-lg text-[10px] font-bold border border-purple-500/30 transition-all cursor-pointer"
                          >
                            مشاهده پرونده / کارت
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ===================== TAB 7: SUPERVISORS ===================== */}
          {activeTab === 'supervisors' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div className="border-b border-slate-800 pb-3 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="p-1.5 bg-amber-500/20 text-amber-300 rounded-xl border border-amber-500/30">
                      <ShieldCheck className="w-4 h-4" />
                    </span>
                    <div>
                      <h3 className="text-sm font-bold text-white">افزودن سرپرست جدید به سامانه خوابگاه</h3>
                      <p className="text-[11px] text-slate-400 mt-0.5">سرپرست نیازی به ثبت نام ندارد و با کد ملی و کلمه عبور وارد پنل می‌شود</p>
                    </div>
                  </div>
                </div>

                <form onSubmit={handleAddSupervisor} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 text-xs">
                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">کد ملی سرپرست (۱۰ رقم) *</label>
                    <input
                      type="text"
                      maxLength={10}
                      value={newSupNationalId}
                      onChange={(e) => setNewSupNationalId(e.target.value)}
                      placeholder="مثال: 0099887766"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-amber-500 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">نام و نام خانوادگی *</label>
                    <input
                      type="text"
                      value={newSupFullName}
                      onChange={(e) => setNewSupFullName(e.target.value)}
                      placeholder="مثال: مهندس حسام داوودی"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">شماره تماس اضطراری</label>
                    <input
                      type="text"
                      value={newSupMobile}
                      onChange={(e) => setNewSupMobile(e.target.value)}
                      placeholder="09120000000"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-amber-500 outline-none"
                      dir="ltr"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">روزهای شیفت کاری *</label>
                    <input
                      type="text"
                      value={newSupShiftDays}
                      onChange={(e) => setNewSupShiftDays(e.target.value)}
                      placeholder="مثال: شنبه تا چهارشنبه"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none"
                    />
                    <div className="flex flex-wrap gap-1 mt-1">
                      {['شنبه تا چهارشنبه', 'پنج‌شنبه و جمعه', 'روزهای زوج', 'روزهای فرد'].map(d => (
                        <button
                          key={d}
                          type="button"
                          onClick={() => setNewSupShiftDays(d)}
                          className="px-1.5 py-0.5 rounded text-[9px] bg-slate-800 text-slate-400 hover:text-white cursor-pointer"
                        >
                          {d}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">ساعت کاری شیفت *</label>
                    <input
                      type="text"
                      value={newSupShiftHours}
                      onChange={(e) => setNewSupShiftHours(e.target.value)}
                      placeholder="مثال: ۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)"
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none"
                    />
                    <div className="flex flex-wrap gap-1 mt-1">
                      {['۲۲:۰۰ الی ۰۶:۰۰ (شب)', '۰۸:۰۰ الی ۱۶:۰۰ (صبح)', '۱۶:۰۰ الی ۲۴:۰۰ (عصر)'].map(h => (
                        <button
                          key={h}
                          type="button"
                          onClick={() => setNewSupShiftHours(h)}
                          className="px-1.5 py-0.5 rounded text-[9px] bg-slate-800 text-slate-400 hover:text-white cursor-pointer"
                        >
                          {h}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">کلمه عبور اختصاصی *</label>
                    <input
                      type="text"
                      value={newSupPassword}
                      onChange={(e) => setNewSupPassword(e.target.value)}
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-mono focus:border-amber-500 outline-none"
                    />
                  </div>

                  <div className="sm:col-span-2 lg:col-span-6 flex justify-end pt-1">
                    <button
                      type="submit"
                      className="flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer"
                    >
                      <UserPlus className="w-4 h-4" />
                      <span>ثبت سرپرست و صدور دسترسی</span>
                    </button>
                  </div>
                </form>
              </div>

              {/* Supervisors Cards Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {supervisors.map(sup => (
                  <div key={sup.id} className="bg-[#1e293b]/70 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-3 relative group">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300 font-bold text-sm">
                          👨‍💼
                        </div>
                        <div>
                          <h4 className="text-sm font-bold text-white">{sup.fullName}</h4>
                          <span className="text-[10px] text-slate-400 font-mono">کدملی: {toPersianDigits(sup.nationalId)}</span>
                        </div>
                      </div>
                      <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded text-[10px] font-bold">
                        سرپرست رسمی ✓
                      </span>
                    </div>

                    <div className="p-3 bg-slate-900/80 rounded-xl space-y-2 text-xs">
                      <div className="flex items-center justify-between text-slate-300">
                        <span className="text-slate-400">📅 روزهای شیفت:</span>
                        <span className="font-bold text-cyan-300">{sup.shiftDays || 'شنبه تا چهارشنبه'}</span>
                      </div>
                      <div className="flex items-center justify-between text-slate-300">
                        <span className="text-slate-400">⏰ ساعات کاری:</span>
                        <span className="font-bold text-amber-300">{sup.shiftHours || '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)'}</span>
                      </div>
                      <div className="flex items-center justify-between text-slate-300">
                        <span className="text-slate-400">مسئولیت:</span>
                        <span className="font-medium text-slate-200">{sup.assignedDuty}</span>
                      </div>
                      <div className="flex items-center justify-between text-slate-300 font-mono">
                        <span className="text-slate-400">تماس:</span>
                        <span>{toPersianDigits(sup.mobile)}</span>
                      </div>
                    </div>

                    <div className="flex items-center justify-between pt-2 border-t border-slate-800 gap-2">
                      <div className="flex items-center gap-1.5">
                        <button
                          type="button"
                          onClick={() => setSelectedSupervisorForCard(sup)}
                          className="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-xl text-xs font-bold transition-all border border-indigo-500/30 cursor-pointer"
                        >
                          <CreditCard className="w-3.5 h-3.5" />
                          <span>کارت سرپرست</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => handleOpenEditSupervisorShift(sup)}
                          className="flex items-center gap-1 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 rounded-xl text-xs font-bold transition-all border border-slate-700 cursor-pointer"
                          title="تعیین و ویرایش روزهای شیفت و ساعات کاری"
                        >
                          <Edit className="w-3.5 h-3.5" />
                          <span>تنظیم شیفت</span>
                        </button>
                      </div>

                      <button
                        type="button"
                        onClick={() => handleDeleteSupervisor(sup.id)}
                        className="p-1.5 text-slate-500 hover:text-rose-400 transition-colors cursor-pointer"
                        title="حذف سرپرست"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </main>

      {/* ===================== MODAL: ASSIGN ROOM WITH GUEST DAYS ===================== */}
      {assigningStudent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 animate-fadeIn">
          <div className="bg-[#1e293b] rounded-2xl p-6 max-w-lg w-full border border-slate-700 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2">
                <span className="p-1.5 bg-amber-500/20 text-amber-300 rounded-lg">
                  <BedDouble className="w-5 h-5" />
                </span>
                <h3 className="text-sm font-bold text-white">
                  اسکان دانشجو «{assigningStudent.fullName}» در خوابگاه
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setAssigningStudent(null)}
                className="text-slate-400 hover:text-white font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            {/* Quick student info */}
            <div className="p-3 bg-slate-900 rounded-xl text-xs space-y-1.5 border border-slate-800">
              <div className="flex justify-between">
                <span className="text-slate-400">شماره دانشجویی / کدملی:</span>
                <span className="font-mono text-cyan-300 font-bold">{assigningStudent.studentId} | {assigningStudent.nationalId}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">رشته و نوبت:</span>
                <span className="text-white font-medium">{assigningStudent.fieldOfStudy} ({assigningStudent.studyShift})</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">محل سکونت:</span>
                <span className="text-slate-300">{assigningStudent.province} ({assigningStudent.city})</span>
              </div>
              {assigningStudent.dormitoryNote && (
                <div className="text-[11px] text-amber-300/90 pt-1 border-t border-slate-800">
                  <strong>توضیحات دانشجو:</strong> {assigningStudent.dormitoryNote}
                </div>
              )}
            </div>

            <form onSubmit={handleConfirmAssignment} className="space-y-4 text-xs">
              <div>
                <label className="block font-semibold text-slate-300 mb-1.5">
                  انتخاب اتاق و طبقه خوابگاه (فقط اتاق‌های فعال و دارای ظرفیت خالی):
                </label>
                <select
                  value={assignTargetRoomId}
                  onChange={(e) => setAssignTargetRoomId(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-bold cursor-pointer outline-none focus:border-indigo-500"
                >
                  {rooms.filter(r => r.isActive).map(r => {
                    const count = allocations.filter(a => a.isActive && a.roomId === r.id).length;
                    const free = Math.max(0, r.capacity - count);
                    const isFull = free === 0;

                    return (
                      <option key={r.id} value={r.id} disabled={isFull}>
                        اتاق {r.roomNumber} - {r.floor} ({toPersianDigits(free)} تخت خالی از {toPersianDigits(r.capacity)}) {isFull ? '[تکمیل]' : ''}
                      </option>
                    );
                  })}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-300 mb-1.5">
                  تعیین وضعیت اسکان دانشجو:
                </label>
                <div className="grid grid-cols-3 gap-2">
                  {(['بدون شهریه', 'دارای شهریه', 'میهمان'] as DormStatusType[]).map(st => (
                    <button
                      key={st}
                      type="button"
                      onClick={() => setAssignDormStatus(st)}
                      className={`p-2.5 rounded-xl border text-center font-bold transition-all cursor-pointer ${
                        assignDormStatus === st
                          ? 'bg-indigo-600 text-white border-indigo-500 shadow-md'
                          : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800'
                      }`}
                    >
                      {st}
                    </button>
                  ))}
                </div>
              </div>

              {/* Guest Duration Input in DAYS & Shamsi Start Date */}
              {assignDormStatus === 'میهمان' && (
                <div className="p-3 bg-amber-950/40 border border-amber-500/40 rounded-xl space-y-3 animate-fadeIn">
                  <div className="flex items-center justify-between">
                    <label className="text-amber-200 font-bold text-xs flex items-center gap-1.5">
                      <Clock className="w-4 h-4 text-amber-400" />
                      <span>مدت زمان اقامت میهمان (با واحد روز):</span>
                    </label>
                    <span className="font-mono text-amber-300 bg-amber-500/20 border border-amber-500/30 px-2 py-0.5 rounded text-xs font-bold">
                      {toPersianDigits(assignGuestDays)} روزه
                    </span>
                  </div>

                  <div className="flex flex-wrap gap-1.5">
                    {[3, 5, 7, 10, 14, 21, 30].map(days => (
                      <button
                        key={days}
                        type="button"
                        onClick={() => setAssignGuestDays(days)}
                        className={`px-3 py-1 text-xs rounded-lg border font-bold cursor-pointer transition-all ${
                          assignGuestDays === days
                            ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-sm'
                            : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800'
                        }`}
                      >
                        {toPersianDigits(days)} روز
                      </button>
                    ))}
                  </div>

                  <div className="flex items-center gap-2 pt-1">
                    <span className="text-slate-400 text-[11px]">یا تعداد روز دلخواه:</span>
                    <input
                      type="number"
                      min={1}
                      max={90}
                      value={assignGuestDays}
                      onChange={(e) => setAssignGuestDays(Math.max(1, Number(e.target.value)))}
                      className="w-20 px-2 py-1 bg-slate-900 border border-slate-700 text-white rounded-lg text-center font-mono font-bold"
                    />
                    <span className="text-slate-400 text-xs">روز</span>
                  </div>

                  {/* Shamsi Start Date Picker */}
                  <div className="pt-2 border-t border-amber-500/20 space-y-1.5">
                    <label className="block text-amber-200 font-semibold text-xs flex items-center gap-1.5">
                      <Calendar className="w-3.5 h-3.5 text-amber-400" />
                      <span>تاریخ شروع اسکان میهمانی (تقویم شمسی):</span>
                    </label>
                    <PersianDatePicker
                      value={assignGuestStartDate}
                      onChange={(jalali) => setAssignGuestStartDate(jalali)}
                      placeholder="۱۴۰۳/۰۷/۱۵"
                    />
                  </div>

                  {/* Calculated End Date Preview */}
                  <div className="p-2 bg-slate-950/70 border border-amber-500/30 rounded-lg text-xs space-y-0.5">
                    <span className="text-slate-400 text-[10px] block">پیش‌نمایش بازه اقامت میهمانی:</span>
                    <div className="text-amber-300 font-bold flex items-center justify-between">
                      <span>از {assignGuestStartDate}</span>
                      <span>تا {addDaysToJalali(assignGuestStartDate, assignGuestDays)}</span>
                      <span className="text-emerald-400 text-[11px]">({toPersianDigits(assignGuestDays)} روز کامل)</span>
                    </div>
                  </div>

                  <p className="text-[10px] text-amber-300/80 leading-relaxed">
                    ℹ️ پس از پایان این مدت، دانشجو به صورت خودکار از اتاق خارج شده و به صف دانشجویان متقاضی خوابگاه بازمی‌گردد.
                  </p>
                </div>
              )}

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-700">
                <button
                  type="button"
                  onClick={() => setAssigningStudent(null)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl cursor-pointer"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-6 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl shadow-md cursor-pointer"
                >
                  تایید اسکان دانشجو
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: EDIT STUDENT ALLOCATION ===================== */}
      {selectedStudentId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 animate-fadeIn">
          <div className="bg-[#1e293b] rounded-2xl p-6 max-w-md w-full border border-slate-700 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <h3 className="text-sm font-bold text-white">
                ویرایش اتاق و وضعیت اسکان ({selectedStudentId})
              </h3>
              <button
                type="button"
                onClick={() => setSelectedStudentId(null)}
                className="text-slate-400 hover:text-white font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSaveStudentAllocation} className="space-y-4 text-xs">
              <div>
                <label className="block text-slate-300 font-semibold mb-1">انتقال به اتاق جدید:</label>
                <select
                  value={editRoomId}
                  onChange={(e) => setEditRoomId(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl font-bold outline-none"
                >
                  {rooms.filter(r => r.isActive).map(r => (
                    <option key={r.id} value={r.id}>
                      اتاق {r.roomNumber} - {r.floor}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-slate-300 font-semibold mb-1">حالت خوابگاه:</label>
                <div className="grid grid-cols-3 gap-2">
                  {(['بدون شهریه', 'دارای شهریه', 'میهمان'] as DormStatusType[]).map(st => (
                    <button
                      key={st}
                      type="button"
                      onClick={() => setEditDormStatus(st)}
                      className={`p-2 rounded-xl border text-center font-bold transition-all cursor-pointer ${
                        editDormStatus === st
                          ? 'bg-indigo-600 text-white border-indigo-500 shadow-sm'
                          : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800'
                      }`}
                    >
                      {st}
                    </button>
                  ))}
                </div>
              </div>

              {editDormStatus === 'میهمان' && (
                <div className="p-3 bg-amber-950/40 border border-amber-500/40 rounded-xl space-y-2">
                  <label className="block text-amber-200 font-bold text-xs">روزهای باقی‌مانده میهمانی:</label>
                  <input
                    type="number"
                    min={1}
                    max={90}
                    value={editGuestDays}
                    onChange={(e) => setEditGuestDays(Math.max(1, Number(e.target.value)))}
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-lg font-mono font-bold text-center"
                  />
                </div>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-700">
                <button
                  type="button"
                  onClick={() => setSelectedStudentId(null)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl cursor-pointer"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl cursor-pointer"
                >
                  ذخیره تغییرات
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: SUPERVISOR CARD ===================== */}
      {selectedSupervisorForCard && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 animate-fadeIn">
          <SupervisorCard
            supervisor={selectedSupervisorForCard}
            onClose={() => setSelectedSupervisorForCard(null)}
          />
        </div>
      )}

      {/* ===================== MODAL: BATCH ATTENDANCE PRINT (OFFICIAL A4 REPORT) ===================== */}
      {showBatchPrintModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-white text-slate-900 rounded-2xl max-w-4xl w-full p-8 shadow-2xl space-y-6 my-8">
            {/* Header with Print / Close Actions */}
            <div className="flex items-center justify-between border-b pb-4 print:hidden">
              <div className="flex items-center gap-2">
                <Printer className="w-5 h-5 text-indigo-600" />
                <h3 className="text-base font-bold text-slate-900">پیش‌نمایش چاپ رسمی گزارش سرشماری شبانه خوابگاه صمد</h3>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => window.print()}
                  className="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer transition-colors"
                >
                  <Printer className="w-4 h-4" />
                  <span>چاپ روی برگه A4</span>
                </button>
                <button
                  type="button"
                  onClick={() => setShowBatchPrintModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
                >
                  بستن
                </button>
              </div>
            </div>

            {/* Official Printable Document Container */}
            <div className="p-6 border-2 border-slate-900 rounded-xl space-y-6">
              {/* Document Header */}
              <div className="flex items-center justify-between border-b-2 border-slate-900 pb-4">
                <div className="w-16 h-16 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xl">
                  صمد
                </div>
                <div className="text-center space-y-1">
                  <h1 className="text-base font-extrabold text-slate-900">پردیس فناوری و مهارت - معاونت دانشجویی و فرهنگی</h1>
                  <h2 className="text-sm font-bold text-indigo-900">گزارش رسمی سرشماری شبانه خوابگاه دانشجویی صمد</h2>
                  <p className="text-xs text-slate-600">صورت‌جلسه حضور و غیاب شبانه ساکنین مجتمع خوابگاهی</p>
                </div>
                <div className="text-left text-xs font-mono space-y-1 text-slate-700">
                  <div>تاریخ سرشماری: <strong>{formatToPersianDateText(attendanceDate)}</strong></div>
                  <div>کد گزارش: <strong>REP-405-{attendanceDate.replace(/-/g, '')}</strong></div>
                  <div>طبقه انتخابی: <strong>{attendanceFloorFilter === 'all' ? 'تمامی طبقات' : attendanceFloorFilter}</strong></div>
                </div>
              </div>

              {/* Statistics Pill */}
              <div className="grid grid-cols-4 gap-2 text-center text-xs p-3 bg-slate-50 border border-slate-300 rounded-xl">
                <div>کل دانشجویان: <strong className="font-mono text-sm">{toPersianDigits(attendanceStats.total)}</strong> نفر</div>
                <div>حاضرین: <strong className="font-mono text-sm text-emerald-700">{toPersianDigits(attendanceStats.present)}</strong> نفر</div>
                <div>غایبین: <strong className="font-mono text-sm text-rose-700">{toPersianDigits(attendanceStats.absent)}</strong> نفر</div>
                <div>دارای مرخصی: <strong className="font-mono text-sm text-amber-700">{toPersianDigits(attendanceStats.leave)}</strong> نفر</div>
              </div>

              {/* Table of Roll Call */}
              <table className="w-full text-right text-xs border border-slate-300">
                <thead>
                  <tr className="bg-slate-100 text-slate-800 border-b border-slate-300 font-bold">
                    <th className="p-2 border-l border-slate-300 text-center w-10">ردیف</th>
                    <th className="p-2 border-l border-slate-300">نام و نام خانوادگی</th>
                    <th className="p-2 border-l border-slate-300 text-center">شماره دانشجویی</th>
                    <th className="p-2 border-l border-slate-300 text-center">اتاق و طبقه</th>
                    <th className="p-2 border-l border-slate-300 text-center">وضعیت حضور</th>
                    <th className="p-2">توضیحات سرپرست شب</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {attendanceFilteredList.map((alloc, idx) => {
                    const room = rooms.find(r => r.id === alloc.roomId);
                    const record = attendanceRecords.find(
                      r => r.studentId === alloc.studentId && r.attendanceDate === attendanceDate
                    );
                    const status = record ? record.status : 'حاضر';

                    return (
                      <tr key={alloc.id} className="text-slate-800">
                        <td className="p-2 border-l border-slate-200 text-center font-mono">{toPersianDigits(idx + 1)}</td>
                        <td className="p-2 border-l border-slate-200 font-bold">{alloc.studentName}</td>
                        <td className="p-2 border-l border-slate-200 text-center font-mono">{toPersianDigits(alloc.studentId)}</td>
                        <td className="p-2 border-l border-slate-200 text-center">اتاق {room?.roomNumber} ({room?.floor})</td>
                        <td className="p-2 border-l border-slate-200 text-center font-bold">
                          <span className={`px-2 py-0.5 rounded text-[11px] ${
                            status === 'حاضر' ? 'bg-emerald-100 text-emerald-800' : status === 'غایب' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'
                          }`}>
                            {status}
                          </span>
                        </td>
                        <td className="p-2 text-[11px] text-slate-600">
                          {record?.notes || 'سرشماری در ساعت ۲۳:۰۰ تکمیل شد.'}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>

              {/* Signatures Footer */}
              <div className="pt-6 grid grid-cols-2 gap-8 text-center text-xs">
                <div className="space-y-12">
                  <span className="font-bold block">مهر و امضای سرپرست شیفت شب خوابگاه:</span>
                  <span className="text-slate-600 block">مهندس کامران قنبری</span>
                </div>
                <div className="space-y-12">
                  <span className="font-bold block">تایید و امضای مدیر امور خوابگاه‌ها و دانشجویی:</span>
                  <span className="text-slate-600 block">دکتر علیرضا حسینی</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: INDIVIDUAL ATTENDANCE PRINT (CERTIFICATE) ===================== */}
      {individualPrintStudent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-white text-slate-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5 my-8">
            <div className="flex items-center justify-between border-b pb-3 print:hidden">
              <div className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-indigo-600" />
                <h3 className="text-sm font-bold text-slate-900">
                  کارنامه تردد و برگه گواهی حضور دانشجو «{individualPrintStudent.alloc.studentName}»
                </h3>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => window.print()}
                  className="flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold cursor-pointer"
                >
                  <Printer className="w-4 h-4" />
                  <span>چاپ گواهی</span>
                </button>
                <button
                  type="button"
                  onClick={() => setIndividualPrintStudent(null)}
                  className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer"
                >
                  بستن
                </button>
              </div>
            </div>

            {/* Individual Certificate Container */}
            <div className="p-6 border-2 border-indigo-900 rounded-2xl space-y-5">
              <div className="flex items-center justify-between border-b border-indigo-900 pb-3">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-xl bg-indigo-900 text-white flex items-center justify-center font-bold text-base">
                    صمد
                  </div>
                  <div>
                    <h2 className="text-xs font-bold text-slate-700">پردیس فناوری و مهارت - امور خوابگاه‌ها</h2>
                    <h1 className="text-sm font-extrabold text-slate-900">گواهی حضور و کارنامه تردد سرشماری شبانه</h1>
                  </div>
                </div>
                <span className="font-mono text-xs text-indigo-800 font-bold">
                  {formatToPersianDateShort(attendanceDate)}
                </span>
              </div>

              {/* Student Details Box */}
              <div className="grid grid-cols-2 gap-3 text-xs p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                <div>نام و نام خانوادگی: <strong className="text-slate-900">{individualPrintStudent.alloc.studentName}</strong></div>
                <div>شماره دانشجویی: <strong className="font-mono text-indigo-700">{individualPrintStudent.alloc.studentId}</strong></div>
                <div>کد ملی دانشجو: <strong className="font-mono">{individualPrintStudent.alloc.nationalId}</strong></div>
                <div>اتاق و طبقه: <strong>اتاق {individualPrintStudent.room?.roomNumber} ({individualPrintStudent.room?.floor})</strong></div>
                <div>وضعیت اقامت: <strong>{individualPrintStudent.alloc.dormStatus}</strong></div>
                <div>وضعیت اتاق: <strong>{individualPrintStudent.room?.isActive ? 'فعال ✓' : 'تعمیرات ⚠️'}</strong></div>
              </div>

              {/* Attendance Record Details */}
              <div className="p-4 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-2 text-xs">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-indigo-950">وضعیت ثبت‌شده در سرشماری شبانه:</span>
                  <span className={`px-3 py-1 rounded-lg text-xs font-bold ${
                    (individualPrintStudent.record?.status || 'حاضر') === 'حاضر'
                      ? 'bg-emerald-600 text-white'
                      : (individualPrintStudent.record?.status) === 'غایب'
                      ? 'bg-rose-600 text-white'
                      : 'bg-amber-600 text-white'
                  }`}>
                    {individualPrintStudent.record?.status || 'حاضر'}
                  </span>
                </div>
                <div className="text-slate-700">
                  <span>تاریخ سرشماری: <strong>{formatToPersianDateText(attendanceDate)}</strong></span>
                </div>
                <div className="text-slate-700">
                  <span>یادداشت و ملاحظات سرپرستی: </span>
                  <span className="font-medium text-slate-900">
                    {individualPrintStudent.record?.notes || 'حضور دانشجو در اتاق در ساعت سرشماری حضوری تایید گردید.'}
                  </span>
                </div>
              </div>

              {/* Seal & Sign */}
              <div className="pt-6 flex items-center justify-between text-xs text-center">
                <div className="space-y-8">
                  <span className="block font-bold">مهر و امضای سرپرست خوابگاه:</span>
                  <span className="block text-slate-500 font-mono">تاییدیه رسمی سرپرستی شب</span>
                </div>
                <div className="space-y-8">
                  <span className="block font-bold">مدیر امور دانشجویی دانشگاه:</span>
                  <span className="block text-slate-500">دکتر علیرضا حسینی</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: ROOM FULL DETAILS & OCCUPANTS ===================== */}
      {selectedRoomForDetails && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-[#1e293b] rounded-2xl max-w-2xl w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8">
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2.5">
                <span className="p-2 bg-emerald-500/20 text-emerald-300 rounded-xl">
                  <Building2 className="w-5 h-5" />
                </span>
                <div>
                  <h3 className="text-sm font-bold text-white">
                    اطلاعات کامل دانشجویان و مشخصات اتاق {selectedRoomForDetails.roomNumber} ({selectedRoomForDetails.floor})
                  </h3>
                  <span className="text-[11px] text-slate-400">
                    ظرفیت کل: {toPersianDigits(selectedRoomForDetails.capacity)} تخت | تخت‌های آزاد: {toPersianDigits(Math.max(0, selectedRoomForDetails.capacity - allocations.filter(a => a.isActive && a.roomId === selectedRoomForDetails.id).length))} تخت
                  </span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setSelectedRoomForDetails(null)}
                className="text-slate-400 hover:text-white p-1 text-base font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            {/* Occupants list with Overcapacity & Bed Priority Rules */}
            {(() => {
              const occupants = allocations.filter(a => a.isActive && a.roomId === selectedRoomForDetails.id);
              if (occupants.length === 0) {
                return (
                  <div className="p-8 text-center bg-slate-900/60 rounded-xl border border-dashed border-slate-700">
                    <p className="text-sm font-bold text-slate-300">در حال حاضر هیچ دانشجویی در این اتاق اسکان ندارد.</p>
                    <p className="text-xs text-slate-500 mt-1">این اتاق دارای {toPersianDigits(selectedRoomForDetails.capacity)} تخت کاملاً خالی و آماده پذیرش است.</p>
                  </div>
                );
              }

              const matrix = computeBedMatrix(selectedRoomForDetails, occupants);
              const withBedStudents = matrix.occupantsWithBeds.filter(o => o.hasBed);
              const surplusStudents = matrix.occupantsWithBeds.filter(o => !o.hasBed);

              return (
                <div className="space-y-4">
                  {/* Capacity & Surplus Status Header Banner */}
                  <div className="p-3.5 bg-slate-900/90 rounded-2xl border border-slate-700/80 space-y-2 text-xs">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-white">
                          وضعیت اسکان اتاق {selectedRoomForDetails.roomNumber}:
                        </span>
                        <span className="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-800 text-slate-200 border border-slate-700">
                          ظرفیت مصوب: {toPersianDigits(selectedRoomForDetails.capacity)} تخت
                        </span>
                        <span className="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                          کل ساکنین: {toPersianDigits(occupants.length)} نفر
                        </span>
                      </div>

                      {matrix.surplusCount > 0 ? (
                        <span className="px-2.5 py-1 rounded-xl text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse flex items-center gap-1 font-mono">
                          <AlertTriangle className="w-3.5 h-3.5" />
                          <span>{toPersianDigits(matrix.surplusCount)} نفر مازاد بر ظرفیت اتاق (پذیرش مازاد مجاز) ⚠️</span>
                        </span>
                      ) : (
                        <span className="px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          <span>ظرفیت در حد استاندارد</span>
                        </span>
                      )}
                    </div>

                    {/* Explanatory Rule Banner */}
                    <div className="pt-2 border-t border-slate-800 text-[11px] text-slate-300 leading-relaxed bg-slate-950/40 p-2.5 rounded-xl border border-slate-800/80">
                      ⚖️ <strong>قانون اولویت اسکان و واگذاری تخت:</strong> اولویت نخست با دانشجویان <strong>روزانه</strong> (بر اساس تقدم زمان اسکان تا سقف {toPersianDigits(selectedRoomForDetails.capacity)} تخت مصوب) است. دانشجویان روزانه‌ای که پس از تکمیل ظرفیت اسکان می‌یابند، به همراه دانشجویان <strong>میهمان</strong> و <strong>شبانه (نوبت دوم)</strong> در فهرست مازاد بر ظرفیت بدون تخت قرار می‌گیرند.
                    </div>
                  </div>

                  {/* Section 1: Students with Approved Beds */}
                  <div className="space-y-2.5">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-bold text-emerald-400 flex items-center gap-1.5">
                        <BedDouble className="w-4 h-4 text-emerald-400" />
                        <span>دانشجویان دارای تخت مصوب ({toPersianDigits(withBedStudents.length)} تخت از {toPersianDigits(selectedRoomForDetails.capacity)}):</span>
                      </span>
                      <span className="text-[10px] text-emerald-300 bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-800 font-mono">
                        رنگ سبز: تخصیص قطعی تخت مصوب
                      </span>
                    </div>

                    <div className="space-y-2 max-h-[35vh] overflow-y-auto pr-1">
                      {withBedStudents.map((occ) => {
                        const adm = admittedStudents.find(s => s.studentId === occ.studentId || s.nationalId === occ.nationalId);
                        const unh = unhousedStudents.find(s => s.studentId === occ.studentId || s.nationalId === occ.nationalId);
                        const prov = unh?.province || (occ.studentId === '4012130045' ? 'اصفهان' : occ.studentId === '4012130046' ? 'فارس' : occ.studentId === '4012130047' ? 'خراسان رضوی' : 'تهران');
                        const city = unh?.city || (occ.studentId === '4012130045' ? 'کاشان' : occ.studentId === '4012130046' ? 'شیراز' : occ.studentId === '4012130047' ? 'مشهد' : 'تهران');
                        const mobile = unh?.mobile || '0912' + occ.nationalId.slice(0, 7);

                        return (
                          <div
                            key={occ.id}
                            className="p-3 bg-emerald-950/15 border-2 border-emerald-500/40 hover:border-emerald-500 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 transition-colors"
                          >
                            <div className="flex items-center gap-3">
                              <span className="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex flex-col items-center justify-center shrink-0">
                                <span className="text-[9px] font-bold">تخت</span>
                                <strong className="text-sm font-mono font-black">{toPersianDigits(occ.bedNumber || 1)}</strong>
                              </span>

                              <div className="w-11 h-13 rounded-xl overflow-hidden border border-emerald-500/30 bg-slate-800 shrink-0">
                                <img
                                  src="/src/assets/images/student_sample_avatar_1790759772433.jpg"
                                  alt={occ.studentName}
                                  className="w-full h-full object-cover"
                                />
                              </div>

                              <div className="space-y-1 text-xs">
                                <div className="flex items-center gap-2">
                                  <h4 className="font-bold text-white text-sm">{occ.studentName}</h4>
                                  <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                    {occ.studyShift || 'روزانه'} (دارای تخت مصوب)
                                  </span>
                                  <span className="text-[10px] text-slate-400 font-mono">
                                    زمان اسکان: {occ.allocationDate}
                                  </span>
                                </div>

                                <div className="text-slate-400 flex items-center gap-2 font-mono text-[11px]">
                                  <span>ش.د: <strong className="text-indigo-400">{toPersianDigits(occ.studentId)}</strong></span>
                                  <span>|</span>
                                  <span>کدملی: <strong className="text-cyan-300">{toPersianDigits(occ.nationalId)}</strong></span>
                                  <span>|</span>
                                  <span>محل: <strong className="text-amber-200">{prov} ({city})</strong></span>
                                </div>
                              </div>
                            </div>

                            <button
                              type="button"
                              onClick={() => setSelectedStudentForCard({
                                studentId: occ.studentId,
                                fullName: occ.studentName,
                                nationalId: occ.nationalId,
                                fieldOfStudy: adm?.fieldOfStudy,
                                campus: adm?.campus,
                                roomNumber: selectedRoomForDetails.roomNumber,
                                floor: selectedRoomForDetails.floor,
                                dormStatus: occ.dormStatus,
                                province: prov,
                                city: city,
                                mobile: mobile
                              })}
                              className="px-3 py-1.5 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 hover:text-white rounded-xl text-xs font-bold transition-all border border-indigo-500/40 cursor-pointer flex items-center gap-1 self-end sm:self-auto shrink-0"
                            >
                              <CreditCard className="w-3.5 h-3.5" />
                              <span>کارت</span>
                            </button>
                          </div>
                        );
                      })}
                    </div>
                  </div>

                  {/* Section 2: Overcapacity Surplus Students (Without Bed) */}
                  {surplusStudents.length > 0 && (
                    <div className="space-y-2.5 pt-2 border-t border-slate-800">
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-amber-400 flex items-center gap-1.5">
                          <AlertTriangle className="w-4 h-4 text-amber-400" />
                          <span>فهرست دانشجویان مازاد بر ظرفیت اتاق ({toPersianDigits(surplusStudents.length)} نفر - بدون تخت):</span>
                        </span>
                        <span className="text-[10px] text-amber-300 bg-amber-950/60 px-2 py-0.5 rounded-full border border-amber-800 font-mono">
                          رنگ کهربایی/قرمز: اسکان مازاد بر ظرفیت
                        </span>
                      </div>

                      <div className="space-y-2 max-h-[35vh] overflow-y-auto pr-1">
                        {surplusStudents.map((occ) => {
                          const adm = admittedStudents.find(s => s.studentId === occ.studentId || s.nationalId === occ.nationalId);
                          const unh = unhousedStudents.find(s => s.studentId === occ.studentId || s.nationalId === occ.nationalId);
                          const prov = unh?.province || 'تهران';
                          const city = unh?.city || 'تهران';
                          const mobile = unh?.mobile || '0912' + occ.nationalId.slice(0, 7);

                          const isDaySurplus = occ.surplusType === 'regular_surplus';
                          const isGuestSurplus = occ.surplusType === 'guest_surplus';
                          const isEveningSurplus = occ.surplusType === 'evening_surplus';

                          return (
                            <div
                              key={occ.id}
                              className={`p-3.5 rounded-2xl border-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 transition-colors ${
                                isDaySurplus
                                  ? 'bg-amber-950/20 border-amber-500/50 hover:border-amber-400'
                                  : isGuestSurplus
                                  ? 'bg-purple-950/20 border-purple-500/50 hover:border-purple-400'
                                  : 'bg-rose-950/20 border-rose-500/50 hover:border-rose-400'
                              }`}
                            >
                              <div className="flex items-center gap-3">
                                <span className={`w-10 h-10 rounded-xl flex flex-col items-center justify-center shrink-0 border ${
                                  isDaySurplus
                                    ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
                                    : isGuestSurplus
                                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/40'
                                    : 'bg-rose-500/20 text-rose-300 border-rose-500/40'
                                }`}>
                                  <span className="text-[9px] font-bold">مازاد</span>
                                  <strong className="text-xs font-mono font-black">#{toPersianDigits(occ.surplusRank || 1)}</strong>
                                </span>

                                <div className="w-11 h-13 rounded-xl overflow-hidden border border-slate-700 bg-slate-800 shrink-0">
                                  <img
                                    src="/src/assets/images/student_sample_avatar_1790759772433.jpg"
                                    alt={occ.studentName}
                                    className="w-full h-full object-cover"
                                  />
                                </div>

                                <div className="space-y-1 text-xs">
                                  <div className="flex flex-wrap items-center gap-2">
                                    <h4 className="font-bold text-white text-sm">{occ.studentName}</h4>
                                    
                                    {isDaySurplus && (
                                      <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                        روزانه مازاد بر ظرفیت (بدون تخت) ⚠️
                                      </span>
                                    )}
                                    {isGuestSurplus && (
                                      <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                        میهمان مازاد بر ظرفیت (بدون تخت) ⏳
                                      </span>
                                    )}
                                    {isEveningSurplus && (
                                      <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                        شبانه نوبت دوم مازاد (بدون تخت) 🌙
                                      </span>
                                    )}
                                  </div>

                                  <p className="text-[11px] text-slate-300">
                                    {isDaySurplus && 'پذیرش پس از تکمیل ظرفیت ۱۰ نفره اتاق بر اساس زمان اسکان - در اولویت اول دریافت اولین تخت خالی.'}
                                    {isGuestSurplus && `دانشجوی میهمان - اسکان موقت مازاد (${occ.guestDaysRemaining ? toPersianDigits(occ.guestDaysRemaining) + ' روز باقی‌مانده' : '۷ روز'}).`}
                                    {isEveningSurplus && 'دانشجوی نوبت دوم (شبانه) - اسکان مازاد بر ظرفیت اتاق.'}
                                  </p>

                                  <div className="text-slate-400 flex items-center gap-2 font-mono text-[11px]">
                                    <span>ش.د: <strong className="text-indigo-400">{toPersianDigits(occ.studentId)}</strong></span>
                                    <span>|</span>
                                    <span>کدملی: <strong className="text-cyan-300">{toPersianDigits(occ.nationalId)}</strong></span>
                                    <span>|</span>
                                    <span>زمان اسکان: <strong className="text-white">{occ.allocationDate}</strong></span>
                                  </div>
                                </div>
                              </div>

                              <button
                                type="button"
                                onClick={() => setSelectedStudentForCard({
                                  studentId: occ.studentId,
                                  fullName: occ.studentName,
                                  nationalId: occ.nationalId,
                                  fieldOfStudy: adm?.fieldOfStudy,
                                  campus: adm?.campus,
                                  roomNumber: selectedRoomForDetails.roomNumber,
                                  floor: selectedRoomForDetails.floor,
                                  dormStatus: occ.dormStatus,
                                  province: prov,
                                  city: city,
                                  mobile: mobile
                                })}
                                className="px-3 py-1.5 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 hover:text-white rounded-xl text-xs font-bold transition-all border border-indigo-500/40 cursor-pointer flex items-center gap-1 self-end sm:self-auto shrink-0"
                              >
                                <CreditCard className="w-3.5 h-3.5" />
                                <span>کارت</span>
                              </button>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>
              );
            })()}

            <div className="pt-2 border-t border-slate-700 flex justify-end">
              <button
                type="button"
                onClick={() => setSelectedRoomForDetails(null)}
                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer"
              >
                بستن پنجره
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: STUDENT ID CARD ===================== */}
      {selectedStudentForCard && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn">
          <div className="bg-[#0f172a] text-white rounded-3xl max-w-md w-full border border-indigo-500/40 shadow-2xl p-6 space-y-4 my-8 relative overflow-hidden">
            <div className="flex items-center justify-between border-b border-indigo-900/60 pb-3 print:hidden">
              <div className="flex items-center gap-2">
                <CreditCard className="w-5 h-5 text-indigo-400" />
                <h3 className="text-sm font-bold text-white">کارت شناسایی هوشمند دانشجو</h3>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => window.print()}
                  className="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold cursor-pointer flex items-center gap-1"
                >
                  <Printer className="w-3.5 h-3.5" />
                  <span>چاپ کارت</span>
                </button>
                <button
                  type="button"
                  onClick={() => setSelectedStudentForCard(null)}
                  className="text-slate-400 hover:text-white font-bold p-1 cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>

            {/* Official University Card Template */}
            <div className="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 border border-indigo-500/30 space-y-4 relative shadow-xl">
              {/* Card Header */}
              <div className="flex items-center justify-between border-b border-indigo-800/60 pb-3">
                <div className="flex items-center gap-2.5">
                  <div className="w-10 h-10 rounded-xl bg-white p-1 overflow-hidden shrink-0 border border-indigo-300/30">
                    <img
                      src="/src/assets/images/samad_campus_emblem_1790759744696.jpg"
                      alt="لوگو صمد"
                      className="w-full h-full object-contain"
                    />
                  </div>
                  <div>
                    <h2 className="text-xs font-black tracking-tight text-white">کارت شناسایی و اسکان صمد</h2>
                    <span className="text-[10px] text-indigo-300 block">{selectedStudentForCard.campus || 'پردیس فناوری صمد'}</span>
                  </div>
                </div>
                <span className="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                  معتبر ۱۴۰۵-۱۴۰۴ ✓
                </span>
              </div>

              {/* Photo & Details */}
              <div className="flex gap-4 items-center">
                <div className="w-24 h-32 rounded-xl overflow-hidden border-2 border-indigo-400/40 bg-slate-800 shrink-0 shadow-md">
                  <img
                    src="/src/assets/images/student_sample_avatar_1790759772433.jpg"
                    alt={selectedStudentForCard.fullName}
                    className="w-full h-full object-cover"
                  />
                </div>

                <div className="space-y-1.5 text-xs flex-1">
                  <div>
                    <span className="text-[10px] text-slate-400 block">نام و نام خانوادگی:</span>
                    <strong className="text-white text-sm">{selectedStudentForCard.fullName}</strong>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 block">شماره دانشجویی:</span>
                    <strong className="font-mono text-indigo-300">{toPersianDigits(selectedStudentForCard.studentId)}</strong>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 block">کد ملی:</span>
                    <strong className="font-mono text-cyan-300">{toPersianDigits(selectedStudentForCard.nationalId)}</strong>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 block">رشته تحصیلی:</span>
                    <span className="text-slate-200 text-[11px]">{selectedStudentForCard.fieldOfStudy || 'مهندسی و فناوری'}</span>
                  </div>
                </div>
              </div>

              {/* Residence & Room Badge */}
              <div className="p-3 bg-slate-950/70 rounded-xl border border-indigo-900/60 text-xs space-y-1">
                <div className="flex justify-between items-center">
                  <span className="text-slate-400">محل سکونت در خوابگاه:</span>
                  <span className="font-bold text-emerald-400">
                    {selectedStudentForCard.roomNumber ? `اتاق ${selectedStudentForCard.roomNumber} (${selectedStudentForCard.floor})` : 'در صف اسکان'}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-400">استان / محل زندگی:</span>
                  <span className="text-slate-300">{selectedStudentForCard.province || 'تهران'} ({selectedStudentForCard.city || 'تهران'})</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-400">وضعیت اسکان:</span>
                  <span className="text-indigo-300 font-bold">{selectedStudentForCard.dormStatus || 'ثبت‌شده در سامانه'}</span>
                </div>
              </div>

              {/* Card Footer Barcode & Stamp */}
              <div className="pt-2 border-t border-indigo-900/60 flex items-center justify-between text-[10px] text-slate-400">
                <div className="space-y-0.5">
                  <div className="font-mono text-[9px] tracking-widest text-slate-400">||| | || |||| | ||| |||| |</div>
                  <span>بارکد اختصاصی احراز هویت دانشگاه</span>
                </div>
                <div className="text-center">
                  <span className="text-emerald-400 font-bold block">مهر دیجیتال صمد</span>
                  <span className="text-[8px] text-slate-500 font-mono">SAMAD-ID-{selectedStudentForCard.studentId.slice(-4)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: EDIT SUPERVISOR SHIFT ===================== */}
      {editingSupShift && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-xs p-4 animate-fadeIn">
          <div className="bg-[#1e293b] rounded-2xl max-w-md w-full border border-slate-700 shadow-2xl p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2">
                <span className="p-1.5 bg-amber-500/20 text-amber-300 rounded-lg">
                  <Clock className="w-5 h-5" />
                </span>
                <h3 className="text-sm font-bold text-white">
                  تنظیم روزها و ساعت کاری شیفت «{editingSupShift.fullName}»
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setEditingSupShift(null)}
                className="text-slate-400 hover:text-white font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleUpdateSupervisorShift} className="space-y-4 text-xs">
              <div>
                <label className="block text-slate-300 font-semibold mb-1">روزهای کاری شیفت:</label>
                <input
                  type="text"
                  value={editShiftDays}
                  onChange={(e) => setEditShiftDays(e.target.value)}
                  placeholder="مثال: شنبه تا چهارشنبه"
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none"
                />
                <div className="flex flex-wrap gap-1 mt-1.5">
                  {['شنبه تا چهارشنبه', 'پنج‌شنبه و جمعه', 'روزهای زوج', 'روزهای فرد', 'همه روزه'].map(d => (
                    <button
                      key={d}
                      type="button"
                      onClick={() => setEditShiftDays(d)}
                      className="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer"
                    >
                      {d}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-slate-300 font-semibold mb-1">ساعات کاری شیفت:</label>
                <input
                  type="text"
                  value={editShiftHours}
                  onChange={(e) => setEditShiftHours(e.target.value)}
                  placeholder="مثال: ۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)"
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-amber-500 outline-none"
                />
                <div className="flex flex-wrap gap-1 mt-1.5">
                  {['۲۲:۰۰ الی ۰۶:۰۰ (شیفت شب)', '۰۸:۰۰ الی ۱۶:۰۰ (شیفت صبح)', '۱۶:۰۰ الی ۲۴:۰۰ (شیفت عصر)'].map(h => (
                    <button
                      key={h}
                      type="button"
                      onClick={() => setEditShiftHours(h)}
                      className="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white cursor-pointer"
                    >
                      {h}
                    </button>
                  ))}
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-700">
                <button
                  type="button"
                  onClick={() => setEditingSupShift(null)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl text-xs font-bold cursor-pointer shadow-md"
                >
                  ذخیره تغییرات شیفت
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: COMPLETE ROOM FACILITIES & EQUIPMENT ===================== */}
      {selectedRoomForFacilities && (
        <RoomFacilitiesModal
          room={selectedRoomForFacilities}
          onClose={() => setSelectedRoomForFacilities(null)}
          onSave={handleSaveRoomFacilities}
        />
      )}

      {/* ===================== MODAL: MAINTENANCE ACTION & RESOLUTION ===================== */}
      {selectedReportForResolution && (
        <MaintenanceResolutionModal
          report={selectedReportForResolution}
          onClose={() => setSelectedReportForResolution(null)}
          onResolve={handleResolveMaintenance}
        />
      )}

      {/* ===================== MODAL: SINGLE OFFICIAL INVOICE VIEW & PRINT ===================== */}
      {selectedReportForSingleInvoice && (
        <SingleInvoiceModal
          report={selectedReportForSingleInvoice}
          onClose={() => setSelectedReportForSingleInvoice(null)}
        />
      )}

      {/* ===================== MODAL: OFFICIAL NIGHTLY CLEANLINESS PRINT ===================== */}
      {showCleanlinessPrintModal && (
        <CleanlinessPrintModal
          records={cleanlinessRecords}
          rooms={rooms}
          selectedDate={selectedCleanlinessDate}
          onClose={() => setShowCleanlinessPrintModal(false)}
        />
      )}

      {/* ===================== MODAL: OFFICIAL WEEKLY / MONTHLY CLEANLINESS RANKING PRINT ===================== */}
      {showWeeklyMonthlyPrintModal && (
        <WeeklyMonthlyCleanlinessPrintModal
          rankings={cleanlinessRankings}
          periodTitle={cleanlinessRankingPeriod === 'weekly' ? 'عملکرد هفتگی (۷ روز اخیر)' : cleanlinessRankingPeriod === 'monthly' ? 'عملکرد ماهانه (۳۰ روز اخیر)' : 'کل دوره‌های ارزیابی'}
          periodType={cleanlinessRankingPeriod === 'weekly' ? 'weekly' : 'monthly'}
          onClose={() => setShowWeeklyMonthlyPrintModal(false)}
        />
      )}

      {/* ===================== MODAL: OFFICIAL MAINTENANCE FINANCIAL & INVOICE PRINT ===================== */}
      {showMaintenancePrintModal && (
        <MaintenanceReportPrintModal
          reports={filteredMaintenanceReports}
          periodTitle={maintenancePeriodFilter === 'weekly' ? 'گزارش هفتگی مخارج و تعمیرات (۷ روز اخیر)' : maintenancePeriodFilter === 'monthly' ? 'گزارش ماهانه مخارج و فاکتورها (۳۰ روز اخیر)' : 'گزارش جامع کلیه تعمیرات و اسناد مالی'}
          onClose={() => setShowMaintenancePrintModal(false)}
        />
      )}

      {/* ===================== MODAL: ISSUE TARGETED FESTIVAL CALL ===================== */}
      {showAddCallModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
          <div className="bg-[#1e293b] text-white rounded-3xl max-w-lg w-full border border-slate-700 shadow-2xl p-6 space-y-4 my-8">
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2">
                <Megaphone className="w-5 h-5 text-yellow-400" />
                <h3 className="text-sm font-bold text-white">صدور فراخوان و اطلاعیه هدفمند جدید</h3>
              </div>
              <button
                type="button"
                onClick={() => setShowAddCallModal(false)}
                className="text-slate-400 hover:text-white text-base cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAddFestivalCall} className="space-y-3.5 text-xs">
              <div>
                <label className="block text-slate-300 font-semibold mb-1">عنوان فراخوان *</label>
                <input
                  type="text"
                  value={newCallTitle}
                  onChange={(e) => setNewCallTitle(e.target.value)}
                  placeholder="مثال: فراخوان انتخابی تیم فوتبال پردیس یا مسابقات قرآنی..."
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none"
                  required
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-300 font-semibold mb-1">حوزه / جامعه هدف *</label>
                  <select
                    value={newCallCategory}
                    onChange={(e) => setNewCallCategory(e.target.value as any)}
                    className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none"
                  >
                    <option value="sports">جشنواره ورزشی و مسابقات پردیس</option>
                    <option value="religious">جشنواره مذهبی، قرآنی و معارف</option>
                    <option value="technical">المپیادهای فنی و اختراعات</option>
                    <option value="robotics">مسابقات رباتیک و هوش مصنوعی</option>
                    <option value="cultural">جشنواره فرهنگی، ادبی و هنری</option>
                    <option value="skillBased">مسابقات ملی مهارت و کارآفرینی</option>
                    <option value="all">فراخوان عمومی (کلیه دانشجویان)</option>
                  </select>
                </div>

                {newCallCategory === 'sports' && (
                  <div>
                    <label className="block text-slate-300 font-semibold mb-1">تیم ورزشی اختصاصی:</label>
                    <select
                      value={newCallSportsTeam}
                      onChange={(e) => setNewCallSportsTeam(e.target.value)}
                      className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-emerald-300 font-bold rounded-xl focus:border-emerald-500 outline-none"
                    >
                      {UNIVERSITY_SPORTS_TEAMS.map(team => (
                        <option key={team} value={team} className="bg-slate-900 text-white">
                          {team}
                        </option>
                      ))}
                    </select>
                  </div>
                )}
              </div>

              {/* Scheduling Quick Presets */}
              <div className="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/80 space-y-2">
                <span className="text-[11px] font-bold text-amber-300 block flex items-center gap-1.5">
                  <Sparkles className="w-3.5 h-3.5 text-amber-400" />
                  <span>تنظیم سریع بازه زمانی فراخوان (امکان زمان‌بندی برای روزهای آینده):</span>
                </span>
                <div className="flex flex-wrap gap-1.5">
                  <button
                    type="button"
                    onClick={() => {
                      const today = getTodayJalali();
                      setNewCallStartDate(today);
                      setNewCallDeadline(addDaysToJalali(today, 7));
                    }}
                    className="px-2.5 py-1 bg-emerald-950/50 hover:bg-emerald-900/60 text-emerald-300 border border-emerald-500/40 rounded-xl text-[10px] font-bold transition-all cursor-pointer flex items-center gap-1"
                  >
                    <span>🟢 شروع از امروز تا ۷ روز بعد (فوری)</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      const today = getTodayJalali();
                      setNewCallStartDate(addDaysToJalali(today, 5));
                      setNewCallDeadline(addDaysToJalali(today, 15));
                    }}
                    className="px-2.5 py-1 bg-amber-950/50 hover:bg-amber-900/60 text-amber-300 border border-amber-500/40 rounded-xl text-[10px] font-bold transition-all cursor-pointer flex items-center gap-1"
                  >
                    <span>🟡 شروع از ۵ روز دیگر تا ۱۵ روز دیگر (فراخوان آینده)</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      const today = getTodayJalali();
                      setNewCallStartDate(addDaysToJalali(today, 10));
                      setNewCallDeadline(addDaysToJalali(today, 30));
                    }}
                    className="px-2.5 py-1 bg-indigo-950/50 hover:bg-indigo-900/60 text-indigo-300 border border-indigo-500/40 rounded-xl text-[10px] font-bold transition-all cursor-pointer flex items-center gap-1"
                  >
                    <span>🟡 شروع از ۱۰ روز دیگر تا ۱ ماه بعد (رویداد ماه آینده)</span>
                  </button>
                </div>
              </div>

              {/* Start Date & Deadline Date Pickers Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                  <label className="block text-slate-300 font-semibold mb-1 flex items-center justify-between">
                    <span className="flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5 text-indigo-400" />
                      <span>تاریخ شروع فراخوان *</span>
                    </span>
                    <span className="text-[10px] text-indigo-300 font-mono">شروع ثبت‌نام</span>
                  </label>
                  <PersianDatePicker
                    value={newCallStartDate}
                    onChange={(jalali) => setNewCallStartDate(jalali)}
                    placeholder={getTodayJalali()}
                    helperText="امکان تنظیم برای روزهای آینده"
                  />
                  <span className="text-[10px] text-slate-400 mt-0.5 block">
                    در صورت تعیین تاریخ آینده، فراخوان با بردر زرد ذخیره شده و بعداً خودکار فعال می‌شود.
                  </span>
                </div>

                <div>
                  <label className="block text-slate-300 font-semibold mb-1 flex items-center justify-between">
                    <span className="flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5 text-rose-400" />
                      <span>مهلت / پایان فراخوان *</span>
                    </span>
                    <span className="text-[10px] text-rose-300 font-mono">پایان مهلت</span>
                  </label>
                  <PersianDatePicker
                    value={newCallDeadline}
                    onChange={(jalali) => setNewCallDeadline(jalali)}
                    placeholder={addDaysToJalali(getTodayJalali(), 10)}
                    helperText="انقضای فراخوان"
                  />
                  <div className="flex items-center gap-1.5 mt-1">
                    <span className="text-[10px] text-slate-400">ساعت پایان:</span>
                    <input
                      type="text"
                      value={newCallTime}
                      onChange={(e) => setNewCallTime(e.target.value)}
                      placeholder="۱۸:۰۰"
                      className="w-16 px-1.5 py-0.5 bg-slate-900 border border-slate-700 text-yellow-300 font-bold rounded-lg focus:border-yellow-500 outline-none font-mono text-center text-[10px]"
                    />
                    <div className="flex gap-1">
                      {['18:00', '23:59'].map(t => (
                        <button
                          key={t}
                          type="button"
                          onClick={() => setNewCallTime(t)}
                          className="px-1.5 py-0.5 bg-slate-800 hover:bg-slate-700 text-[9px] rounded font-mono text-slate-300 cursor-pointer"
                        >
                          {t}
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-slate-300 font-semibold mb-1">متن و توضیحات کامل فراخوان *</label>
                <textarea
                  rows={4}
                  value={newCallContent}
                  onChange={(e) => setNewCallContent(e.target.value)}
                  placeholder="شرایط شرکت، تاریخ آزمون انتخابی یا جلسه توجیهی..."
                  className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-yellow-500 outline-none leading-relaxed"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-700">
                <button
                  type="button"
                  onClick={() => setShowAddCallModal(false)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-400 hover:to-amber-500 text-slate-950 rounded-xl text-xs font-bold cursor-pointer shadow-md"
                >
                  انتشار فراخوان در کارتابل دانشجویان
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};
