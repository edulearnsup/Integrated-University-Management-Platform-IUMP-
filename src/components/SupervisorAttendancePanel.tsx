import React, { useState, useEffect } from 'react';
import {
  DormRoom, DormAllocation, NightAttendanceRecord, AttendanceStatus,
  DormFloor, SupervisorAccount, MaintenanceReport, RoomCleanlinessRecord,
  CleanlinessGrade
} from '../types';
import {
  UserCheck, CheckCircle2, XCircle, Clock, Calendar, Save, LogOut,
  AlertTriangle, ChevronRight, ChevronLeft, RotateCcw, Check, Sparkles, Filter,
  CreditCard, ShieldCheck, Wrench, Building2, Tv, Wind, Send, Star, Layers,
  CheckCheck, Info
} from 'lucide-react';
import { toPersianDigits } from '../utils/validators';
import {
  gregorianToJalali, jalaliToGregorian, jalaliToDateString,
  formatToPersianDateText, formatToPersianDateShort, addDaysToDateString,
  PERSIAN_MONTHS, parseDateStringToGregorian
} from '../utils/persianDate';
import { SupervisorCard } from './SupervisorCard';
import { SupervisorReportDamageModal } from './SupervisorReportDamageModal';

export type SupervisorTab = 'attendance' | 'facilities' | 'cleanliness';

interface SupervisorAttendancePanelProps {
  rooms: DormRoom[];
  allocations: DormAllocation[];
  attendanceRecords: NightAttendanceRecord[];
  currentSupervisor?: SupervisorAccount;
  maintenanceReports?: MaintenanceReport[];
  cleanlinessRecords?: RoomCleanlinessRecord[];
  onSaveAttendance: (records: NightAttendanceRecord[]) => void;
  onSaveMaintenanceReport?: (report: MaintenanceReport) => void;
  onSaveCleanlinessRecords?: (records: RoomCleanlinessRecord[]) => void;
  onLogout: () => void;
}

export const SupervisorAttendancePanel: React.FC<SupervisorAttendancePanelProps> = ({
  rooms,
  allocations,
  attendanceRecords,
  currentSupervisor,
  maintenanceReports = [],
  cleanlinessRecords = [],
  onSaveAttendance,
  onSaveMaintenanceReport,
  onSaveCleanlinessRecords,
  onLogout
}) => {
  const [activeTab, setActiveTab] = useState<SupervisorTab>('attendance');

  // ISO date format for internal storage (e.g. '2026-09-30')
  const [selectedDate, setSelectedDate] = useState<string>('2026-09-30');
  const [floorFilter, setFloorFilter] = useState<'all' | DormFloor>('all');
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [showSupervisorCardModal, setShowSupervisorCardModal] = useState<boolean>(false);

  // Maintenance Reporting State
  const [showDamageReportModal, setShowDamageReportModal] = useState<boolean>(false);
  const [reportForRoomId, setReportForRoomId] = useState<number | undefined>(undefined);
  const [facilitiesFloorFilter, setFacilitiesFloorFilter] = useState<'all' | DormFloor>('all');

  // Cleanliness Inspection State
  const [cleanlinessDate, setCleanlinessDate] = useState<string>('۱۴۰۳/۰۷/۱۴');
  const [cleanlinessFloorFilter, setCleanlinessFloorFilter] = useState<'all' | DormFloor>('all');
  const [cleanlinessMap, setCleanlinessMap] = useState<Record<number, { grade: CleanlinessGrade; notes: string }>>({});

  // Active supervisor data fallback
  const activeSup: SupervisorAccount = currentSupervisor || {
    id: 1,
    nationalId: '0099887766',
    fullName: 'مهندس کامران قنبری',
    mobile: '09129876543',
    assignedDuty: 'سرپرست شیفت شب - طبقات همکف و اول',
    isActive: true,
    createdAt: '1405/06/15',
    shiftHours: '۲۲:۰۰ الی ۰۶:۰۰ (شیفت شبانه)',
    supervisorCode: 'SUP-405-01',
    avatarUrl: '/src/assets/images/student_sample_avatar_1790759772433.jpg'
  };

  // Derive Persian date components
  const { gy, gm, gd } = parseDateStringToGregorian(selectedDate);
  const { jy, jm, jd } = gregorianToJalali(gy, gm, gd);

  // Local state for roll-call entries (studentId -> { status, notes })
  const [attendanceMap, setAttendanceMap] = useState<Record<string, { status: AttendanceStatus; notes: string }>>({});

  // Reload attendance records whenever selectedDate or allocations change
  useEffect(() => {
    const nextMap: Record<string, { status: AttendanceStatus; notes: string }> = {};
    allocations.forEach(alloc => {
      const match = attendanceRecords.find(
        r => r.studentId === alloc.studentId && r.attendanceDate === selectedDate
      );
      nextMap[alloc.studentId] = {
        status: match ? match.status : 'حاضر',
        notes: match?.notes || ''
      };
    });
    setAttendanceMap(nextMap);
  }, [selectedDate, allocations, attendanceRecords]);

  // Initialize cleanliness map for active rooms
  useEffect(() => {
    const nextCleanMap: Record<number, { grade: CleanlinessGrade; notes: string }> = {};
    rooms.filter(r => r.isActive).forEach(r => {
      const existing = cleanlinessRecords.find(
        c => c.roomId === r.id && c.inspectionDate === cleanlinessDate
      );
      nextCleanMap[r.id] = {
        grade: existing ? existing.grade : 'خوب',
        notes: existing?.notes || ''
      };
    });
    setCleanlinessMap(nextCleanMap);
  }, [cleanlinessDate, rooms, cleanlinessRecords]);

  // Click handler to update status for a student - updates state immediately
  const handleStatusChange = (studentId: string, newStatus: AttendanceStatus) => {
    setAttendanceMap(prev => {
      const currentEntry = prev[studentId] || { status: 'حاضر', notes: '' };
      return {
        ...prev,
        [studentId]: {
          ...currentEntry,
          status: newStatus
        }
      };
    });
  };

  const handleNotesChange = (studentId: string, notes: string) => {
    setAttendanceMap(prev => {
      const currentEntry = prev[studentId] || { status: 'حاضر', notes: '' };
      return {
        ...prev,
        [studentId]: {
          ...currentEntry,
          notes
        }
      };
    });
  };

  // Quick batch set all to Present
  const handleMarkAllPresent = () => {
    setAttendanceMap(prev => {
      const updated = { ...prev };
      allocations.forEach(a => {
        if (updated[a.studentId]) {
          updated[a.studentId] = { ...updated[a.studentId], status: 'حاضر' };
        } else {
          updated[a.studentId] = { status: 'حاضر', notes: '' };
        }
      });
      return updated;
    });
    setSuccessMessage('وضعیت تمامی دانشجویان خوابگاه به «حاضر» تغییر یافت.');
    setTimeout(() => setSuccessMessage(null), 3000);
  };

  // Cleanliness handlers
  const handleCleanlinessGradeChange = (roomId: number, grade: CleanlinessGrade) => {
    setCleanlinessMap(prev => ({
      ...prev,
      [roomId]: {
        grade,
        notes: prev[roomId]?.notes || ''
      }
    }));
  };

  const handleCleanlinessNotesChange = (roomId: number, notes: string) => {
    setCleanlinessMap(prev => ({
      ...prev,
      [roomId]: {
        grade: prev[roomId]?.grade || 'خوب',
        notes
      }
    }));
  };

  const handleSaveCleanliness = () => {
    const activeRooms = rooms.filter(r => r.isActive);
    const now = new Date();
    const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    const newRecords: RoomCleanlinessRecord[] = activeRooms.map(r => {
      const data = cleanlinessMap[r.id] || { grade: 'خوب', notes: '' };
      return {
        id: Date.now() + r.id,
        roomId: r.id,
        roomNumber: r.roomNumber,
        floor: r.floor,
        inspectionDate: cleanlinessDate,
        grade: data.grade,
        supervisorNationalId: activeSup.nationalId,
        supervisorName: activeSup.fullName,
        notes: data.notes.trim() || undefined,
        recordedAt: `${cleanlinessDate} - ${timeStr}`
      };
    });

    if (onSaveCleanlinessRecords) {
      onSaveCleanlinessRecords(newRecords);
    }

    setSuccessMessage(`گزارش نظافت شبانه برای ${toPersianDigits(newRecords.length)} اتاق خوابگاه با موفقیت ثبت و به مدیریت ارسال شد.`);
    setTimeout(() => setSuccessMessage(null), 4000);
  };

  // Date Navigation Helpers
  const handlePrevDay = () => {
    setSelectedDate(prev => addDaysToDateString(prev, -1));
  };

  const handleNextDay = () => {
    setSelectedDate(prev => addDaysToDateString(prev, 1));
  };

  const handleSetToday = () => {
    setSelectedDate('2026-09-30');
  };

  // Save attendance
  const handleSave = () => {
    const updatedRecords: NightAttendanceRecord[] = [];
    const timestamp = new Date().toISOString();

    allocations.forEach(alloc => {
      const state = attendanceMap[alloc.studentId] || { status: 'حاضر', notes: '' };
      updatedRecords.push({
        id: Date.now() + Math.floor(Math.random() * 10000),
        studentId: alloc.studentId,
        roomId: alloc.roomId,
        attendanceDate: selectedDate,
        status: state.status,
        supervisorNationalId: activeSup.nationalId,
        notes: state.notes,
        recordedAt: timestamp
      });
    });

    onSaveAttendance(updatedRecords);
    setSuccessMessage(`سرشماری تاریخ ${toPersianDigits(jd)} ${PERSIAN_MONTHS[jm - 1]} با موفقیت در پایگاه داده ثبت شد.`);
    setTimeout(() => setSuccessMessage(null), 4000);
  };

  // Filter allocations for Attendance
  const filteredAllocations = allocations.filter(alloc => {
    if (floorFilter === 'all') return true;
    const room = rooms.find(r => r.id === alloc.roomId);
    return room?.floor === floorFilter;
  });

  // Filtered rooms for Facilities
  const filteredFacilitiesRooms = rooms.filter(r => {
    if (facilitiesFloorFilter === 'all') return true;
    return r.floor === facilitiesFloorFilter;
  });

  // Filtered rooms for Cleanliness
  const filteredCleanlinessRooms = rooms.filter(r => r.isActive && (cleanlinessFloorFilter === 'all' || r.floor === cleanlinessFloorFilter));

  // Compute stats for attendance
  const totalStudents = allocations.length;
  const presentCount = Object.values(attendanceMap).filter(v => v.status === 'حاضر').length;
  const absentCount = Object.values(attendanceMap).filter(v => v.status === 'غایب').length;
  const leaveCount = Object.values(attendanceMap).filter(v => v.status === 'مرخصی').length;
  const attendanceRate = totalStudents > 0 ? Math.round((presentCount / totalStudents) * 100) : 100;

  return (
    <div className="min-h-screen bg-slate-100/70 p-3 sm:p-6 lg:p-8 flex justify-center selection:bg-indigo-500 selection:text-white" dir="rtl">
      <div className="max-w-6xl w-full space-y-6">

        {/* Top Header Card with Supervisor Identity */}
        <div className="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="flex items-center gap-3.5">
            <div className="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 font-bold shrink-0">
              <ShieldCheck className="w-7 h-7" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-lg font-black tracking-tight text-slate-900">
                  پنل سرپرست شیفت شب خوابگاه
                </h1>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  شیفت فعال ✓
                </span>
              </div>
              <p className="text-xs text-slate-500 mt-0.5">
                سرپرست: <strong>{activeSup.fullName}</strong> | {activeSup.assignedDuty}
              </p>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2 self-stretch md:self-auto justify-end">
            <button
              type="button"
              onClick={() => setShowSupervisorCardModal(true)}
              className="flex items-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
            >
              <CreditCard className="w-4 h-4" />
              <span>کارت سرپرستی پرسنلی</span>
            </button>

            <button
              type="button"
              onClick={onLogout}
              className="flex items-center gap-1.5 px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
            >
              <LogOut className="w-4 h-4" />
              <span>خروج از پنل</span>
            </button>
          </div>
        </div>

        {/* Navigation Tabs Bar */}
        <div className="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-1">
          <button
            type="button"
            onClick={() => setActiveTab('attendance')}
            className={`flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer ${
              activeTab === 'attendance'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <UserCheck className="w-4 h-4" />
            <span>سرشماری شبانه دانشجویان</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('facilities')}
            className={`flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer ${
              activeTab === 'facilities'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <Wrench className="w-4 h-4" />
            <span>امکانات اتاق‌ها و گزارش خرابی</span>
            {maintenanceReports.filter(m => m.status === 'در انتظار اقدام').length > 0 && (
              <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-400 text-slate-950 font-bold font-mono">
                {toPersianDigits(maintenanceReports.filter(m => m.status === 'در انتظار اقدام').length)}
              </span>
            )}
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('cleanliness')}
            className={`flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer ${
              activeTab === 'cleanliness'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <Sparkles className="w-4 h-4" />
            <span>نظافت خوابگاه‌ها (ارزیابی ۴ سطحی شبانه)</span>
          </button>
        </div>

        {/* Success Alert Banner */}
        {successMessage && (
          <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-xs animate-fadeIn">
            <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
            <span className="font-bold flex-1">{successMessage}</span>
          </div>
        )}

        {/* ===================== TAB 1: ATTENDANCE ROLL-CALL ===================== */}
        {activeTab === 'attendance' && (
          <div className="space-y-6 animate-fadeIn">
            {/* KPI Metrics */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
              <div className="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-slate-500 block">کل دانشجویان ساکن</span>
                  <strong className="text-xl font-black text-slate-900 font-mono">{toPersianDigits(totalStudents)} نفر</strong>
                </div>
                <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                  <UserCheck className="w-5 h-5" />
                </div>
              </div>

              <div className="p-4 bg-white rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-emerald-700 block">حاضرین امشب</span>
                  <strong className="text-xl font-black text-emerald-700 font-mono">{toPersianDigits(presentCount)} نفر</strong>
                </div>
                <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                  <CheckCircle2 className="w-5 h-5" />
                </div>
              </div>

              <div className="p-4 bg-white rounded-2xl border border-rose-200 shadow-xs flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-rose-700 block">غائبین بدون اطلاع</span>
                  <strong className="text-xl font-black text-rose-700 font-mono">{toPersianDigits(absentCount)} نفر</strong>
                </div>
                <div className="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                  <XCircle className="w-5 h-5" />
                </div>
              </div>

              <div className="p-4 bg-white rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-amber-700 block">دارای مرخصی تاییدشده</span>
                  <strong className="text-xl font-black text-amber-700 font-mono">{toPersianDigits(leaveCount)} نفر</strong>
                </div>
                <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                  <Clock className="w-5 h-5" />
                </div>
              </div>
            </div>

            {/* Attendance Table Card */}
            <div className="bg-white rounded-3xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b pb-4">
                <div className="flex items-center gap-2">
                  <Calendar className="w-5 h-5 text-indigo-600" />
                  <span className="text-sm font-bold text-slate-900">
                    سرشماری تاریخ: {toPersianDigits(jd)} {PERSIAN_MONTHS[jm - 1]} {toPersianDigits(jy)}
                  </span>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    onClick={handleMarkAllPresent}
                    className="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl transition-colors cursor-pointer"
                  >
                    حاضر کردن همه
                  </button>
                  <button
                    type="button"
                    onClick={handleSave}
                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer"
                  >
                    <Save className="w-4 h-4" />
                    <span>ثبت نهایی سرشماری 💾</span>
                  </button>
                </div>
              </div>

              {/* Table */}
              <div className="overflow-x-auto">
                <table className="w-full text-right text-xs">
                  <thead>
                    <tr className="bg-slate-50 text-slate-700 border-b">
                      <th className="p-3">دانشجو</th>
                      <th className="p-3">شماره دانشجویی</th>
                      <th className="p-3">اتاق و طبقه</th>
                      <th className="p-3 text-center">وضعیت حضور</th>
                      <th className="p-3">یادداشت سرپرستی</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredAllocations.map(alloc => {
                      const room = rooms.find(r => r.id === alloc.roomId);
                      const currentStatus = attendanceMap[alloc.studentId]?.status || 'حاضر';
                      return (
                        <tr key={alloc.id} className="hover:bg-slate-50/80">
                          <td className="p-3 font-bold text-slate-900">{alloc.studentName}</td>
                          <td className="p-3 font-mono text-indigo-600">{toPersianDigits(alloc.studentId)}</td>
                          <td className="p-3">
                            <span className="font-semibold text-slate-800">اتاق {room?.roomNumber}</span>
                            <span className="text-[10px] text-slate-500 block">{room?.floor}</span>
                          </td>
                          <td className="p-3">
                            <div className="flex items-center justify-center gap-1.5 bg-slate-100 p-1 rounded-xl w-fit mx-auto">
                              <button
                                type="button"
                                onClick={() => handleStatusChange(alloc.studentId, 'حاضر')}
                                className={`px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                                  currentStatus === 'حاضر' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                }`}
                              >
                                حاضر
                              </button>
                              <button
                                type="button"
                                onClick={() => handleStatusChange(alloc.studentId, 'غایب')}
                                className={`px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                                  currentStatus === 'غایب' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                }`}
                              >
                                غایب
                              </button>
                              <button
                                type="button"
                                onClick={() => handleStatusChange(alloc.studentId, 'مرخصی')}
                                className={`px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                                  currentStatus === 'مرخصی' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                }`}
                              >
                                مرخصی
                              </button>
                            </div>
                          </td>
                          <td className="p-3">
                            <input
                              type="text"
                              value={attendanceMap[alloc.studentId]?.notes || ''}
                              onChange={(e) => handleNotesChange(alloc.studentId, e.target.value)}
                              placeholder="توضیح سرپرست..."
                              className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:bg-white focus:border-indigo-500"
                            />
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

        {/* ===================== TAB 2: ROOM FACILITIES & DAMAGE REPORTS ===================== */}
        {activeTab === 'facilities' && (
          <div className="space-y-6 animate-fadeIn">
            {/* Top Action Box */}
            <div className="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-3xl p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-white flex items-center gap-2">
                  <Wrench className="w-5 h-5 text-amber-400" />
                  <span>امکانات و تجهیزات ثبت‌شده توسط مدیر دانشگاه</span>
                </h3>
                <p className="text-xs text-slate-300">
                  سرپرست محترم، در صورت مشاهده هرگونه خرابی یا نیاز به تعمیر/تعویض در هر اتاق، سریعاً گزارش ثبت فرمایید.
                </p>
              </div>

              <button
                type="button"
                onClick={() => {
                  setReportForRoomId(undefined);
                  setShowDamageReportModal(true);
                }}
                className="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer shrink-0"
              >
                <AlertTriangle className="w-4 h-4" />
                <span>ثبت گزارش جدید خرابی و تعمیرات ⚠️</span>
              </button>
            </div>

            {/* Filter by Floor */}
            <div className="flex items-center gap-2 text-xs">
              <span className="text-slate-500 font-semibold">فیلتر طبقه:</span>
              {(['all', 'همکف', 'طبقه اول', 'طبقه دوم'] as const).map(fl => (
                <button
                  key={fl}
                  type="button"
                  onClick={() => setFacilitiesFloorFilter(fl)}
                  className={`px-3 py-1.5 rounded-xl font-bold transition-colors cursor-pointer ${
                    facilitiesFloorFilter === fl ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 border hover:bg-slate-50'
                  }`}
                >
                  {fl === 'all' ? 'همه طبقات' : fl}
                </button>
              ))}
            </div>

            {/* Room Facilities Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredFacilitiesRooms.map(room => {
                const fac = room.facilities;
                const roomOccupants = allocations.filter(a => a.isActive && a.roomId === room.id);
                const hasPendingDamage = maintenanceReports.some(m => m.roomId === room.id && m.status === 'در انتظار اقدام');

                return (
                  <div
                    key={room.id}
                    className={`bg-white rounded-2xl border p-5 space-y-3.5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between ${
                      hasPendingDamage ? 'border-rose-400 ring-2 ring-rose-200' : 'border-slate-200'
                    }`}
                  >
                    <div>
                      {/* Card Header */}
                      <div className="flex items-center justify-between border-b pb-2.5">
                        <div className="flex items-center gap-2">
                          <span className="text-base font-black text-slate-900 font-mono">اتاق {room.roomNumber}</span>
                          <span className="text-xs text-slate-500">({room.floor})</span>
                        </div>
                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                          room.isCompleted
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-amber-50 text-amber-700 border border-amber-200'
                        }`}>
                          {room.isCompleted ? 'تکمیل‌شده ✓' : 'تکمیل‌نشده ⚠️'}
                        </span>
                      </div>

                      {/* Equipment List */}
                      {room.isCompleted && fac ? (
                        <div className="pt-2 space-y-2 text-xs text-slate-700">
                          <div className="flex items-center justify-between">
                            <span className="text-slate-500">🧊 یخچال:</span>
                            <span className="font-semibold text-slate-900">{fac.refrigeratorCount} دستگاه ({fac.refrigeratorModel})</span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span className="text-slate-500">🛏 تخت‌ها:</span>
                            <span className="font-semibold text-slate-900">{fac.bedCount} تخت ({fac.bedType})</span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span className="text-slate-500">🧹 نظافت و شستشو:</span>
                            <span className="space-x-1 font-mono text-[11px]">
                              <span>{fac.cleaningTools?.hasBroom ? 'جارو ✓' : 'فاقد جارو ✕'}</span>
                              <span>·</span>
                              <span>{fac.cleaningTools?.hasBasin ? 'لگن ✓' : 'فاقد لگن ✕'}</span>
                              <span>·</span>
                              <span>{fac.cleaningTools?.hasTrashCan ? 'سطل ✓' : 'فاقد سطل ✕'}</span>
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span className="text-slate-500">🛋 رفاهی و تاسیسات:</span>
                            <span className="space-x-1 text-[11px]">
                              <span>{fac.carpetCount} تخته فرش</span>
                              <span>·</span>
                              <span>{fac.hasAc ? 'کولر دارد' : 'بدون کولر'}</span>
                              <span>·</span>
                              <span>{fac.hasTv ? 'تلویزیون دارد' : 'بدون TV'}</span>
                            </span>
                          </div>
                          {fac.customEquipment && fac.customEquipment.length > 0 && (
                            <div className="pt-1 flex flex-wrap gap-1">
                              {fac.customEquipment.map((c, i) => (
                                <span key={i} className="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px]">
                                  + {c}
                                </span>
                              ))}
                            </div>
                          )}
                        </div>
                      ) : (
                        <div className="p-4 bg-amber-50/60 rounded-xl border border-amber-200 text-amber-800 text-xs text-center space-y-1 my-2">
                          <AlertTriangle className="w-5 h-5 mx-auto text-amber-600" />
                          <p className="font-bold">اطلاعات امکانات هنوز توسط مدیر تکمیل نشده است.</p>
                          <p className="text-[10px] text-amber-700">ظرفیت پیش‌فرض: {toPersianDigits(room.capacity)} نفره</p>
                        </div>
                      )}
                    </div>

                    {/* Room Action Button */}
                    <div className="pt-3 border-t flex items-center justify-between">
                      <span className="text-[11px] text-slate-500">
                        ساکنین: <strong>{toPersianDigits(roomOccupants.length)}</strong> نفر
                      </span>
                      <button
                        type="button"
                        onClick={() => {
                          setReportForRoomId(room.id);
                          setShowDamageReportModal(true);
                        }}
                        className="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1"
                      >
                        <AlertTriangle className="w-3.5 h-3.5" />
                        <span>گزارش خرابی این اتاق</span>
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Reports Log Table */}
            <div className="bg-white rounded-3xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
              <div className="flex items-center justify-between border-b pb-3">
                <div className="flex items-center gap-2">
                  <AlertTriangle className="w-5 h-5 text-rose-600" />
                  <h3 className="text-sm font-bold text-slate-900">سوابق گزارش‌های خرابی و پاسخ مدیریت دانشگاه</h3>
                </div>
                <span className="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-bold font-mono">
                  {toPersianDigits(maintenanceReports.length)} گزارش
                </span>
              </div>

              {maintenanceReports.length === 0 ? (
                <p className="text-xs text-slate-500 text-center py-6">تاکنون هیچ گزارش خرابی ثبت نشده است.</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead>
                      <tr className="bg-slate-50 text-slate-700 border-b">
                        <th className="p-3">اتاق</th>
                        <th className="p-3">قطعه / تجهیز خراب</th>
                        <th className="p-3">نوع تقاضا</th>
                        <th className="p-3">فوریت</th>
                        <th className="p-3">زمان ثبت گزارش</th>
                        <th className="p-3">وضعیت رسیدگی مدیر</th>
                        <th className="p-3">توضیحات و پاسخ رفع عیب</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {maintenanceReports.map(rep => (
                        <tr key={rep.id} className="hover:bg-slate-50/80">
                          <td className="p-3 font-bold font-mono text-slate-900">
                            اتاق {rep.roomNumber} ({rep.floor})
                          </td>
                          <td className="p-3 font-semibold text-slate-800">{rep.equipmentName}</td>
                          <td className="p-3">
                            <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                              {rep.maintenanceType}
                            </span>
                          </td>
                          <td className="p-3">
                            <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                              rep.urgency === 'فوری' ? 'bg-rose-100 text-rose-700' :
                              rep.urgency === 'عادی' ? 'bg-amber-100 text-amber-700' :
                              'bg-slate-100 text-slate-600'
                            }`}>
                              {rep.urgency}
                            </span>
                          </td>
                          <td className="p-3 font-mono text-slate-500 text-[11px]">{rep.reportedAt}</td>
                          <td className="p-3">
                            <span className={`px-2.5 py-1 rounded-full text-[11px] font-bold ${
                              rep.status.includes('رفع‌شده')
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-rose-100 text-rose-800 animate-pulse'
                            }`}>
                              {rep.status}
                            </span>
                          </td>
                          <td className="p-3 text-[11px] text-slate-600 max-w-xs">
                            {rep.resolvedAt ? (
                              <div className="space-y-0.5">
                                <span className="font-bold text-emerald-700 block">رفع‌شده در: {rep.resolvedAt}</span>
                                <span>{rep.resolutionNotes}</span>
                              </div>
                            ) : (
                              <span>{rep.description}</span>
                            )}
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

        {/* ===================== TAB 3: CLEANLINESS 4-LEVEL NIGHTLY INSPECTION ===================== */}
        {activeTab === 'cleanliness' && (
          <div className="space-y-6 animate-fadeIn">
            {/* Top Info Banner */}
            <div className="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div>
                <div className="flex items-center gap-2">
                  <Sparkles className="w-5 h-5 text-amber-500" />
                  <h3 className="text-base font-bold text-slate-900">
                    ارزیابی شب به شب کیفیت نظافت اتاق‌های خوابگاه
                  </h3>
                </div>
                <p className="text-xs text-slate-500 mt-0.5">
                  سرپرست شیفت هر شب مانند سرشماری حضور و غیاب، وضعیت بهداشت و نظافت هر اتاق را در ۴ سطح ارزیابی می‌نماید.
                </p>
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleSaveCleanliness}
                  className="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                >
                  <Save className="w-4 h-4" />
                  <span>ثبت و ارسال ارزیابی نظافت به مدیریت 💾</span>
                </button>
              </div>
            </div>

            {/* Filter by Floor */}
            <div className="flex items-center gap-2 text-xs">
              <span className="text-slate-500 font-semibold">فیلتر طبقه:</span>
              {(['all', 'همکف', 'طبقه اول', 'طبقه دوم'] as const).map(fl => (
                <button
                  key={fl}
                  type="button"
                  onClick={() => setCleanlinessFloorFilter(fl)}
                  className={`px-3 py-1.5 rounded-xl font-bold transition-colors cursor-pointer ${
                    cleanlinessFloorFilter === fl ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 border hover:bg-slate-50'
                  }`}
                >
                  {fl === 'all' ? 'همه طبقات' : fl}
                </button>
              ))}
            </div>

            {/* Cleanliness Room Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {filteredCleanlinessRooms.map(room => {
                const data = cleanlinessMap[room.id] || { grade: 'خوب', notes: '' };
                const roomOccupants = allocations.filter(a => a.isActive && a.roomId === room.id);

                return (
                  <div
                    key={room.id}
                    className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs"
                  >
                    <div className="flex items-center justify-between border-b pb-2.5">
                      <div className="flex items-center gap-2">
                        <span className="text-base font-black text-slate-900 font-mono">اتاق {room.roomNumber}</span>
                        <span className="text-xs text-slate-500">({room.floor})</span>
                      </div>
                      <span className="text-xs text-slate-500">
                        {toPersianDigits(roomOccupants.length)} دانشجو مستقر
                      </span>
                    </div>

                    {/* 4-Level Cleanliness Grading Buttons */}
                    <div className="space-y-1.5">
                      <label className="block text-slate-700 font-bold text-xs">کیفیت نظافت اتاق امشب (۴ سطح):</label>
                      <div className="grid grid-cols-4 gap-2">
                        {/* 1. عالی */}
                        <button
                          type="button"
                          onClick={() => handleCleanlinessGradeChange(room.id, 'عالی')}
                          className={`p-2.5 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1 ${
                            data.grade === 'عالی'
                              ? 'bg-emerald-600 text-white font-bold shadow-sm border-emerald-600 ring-2 ring-emerald-300'
                              : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                          }`}
                        >
                          <Star className="w-4 h-4 text-amber-300 fill-amber-300" />
                          <span className="text-xs">عالی</span>
                        </button>

                        {/* 2. خوب */}
                        <button
                          type="button"
                          onClick={() => handleCleanlinessGradeChange(room.id, 'خوب')}
                          className={`p-2.5 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1 ${
                            data.grade === 'خوب'
                              ? 'bg-blue-600 text-white font-bold shadow-sm border-blue-600 ring-2 ring-blue-300'
                              : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                          }`}
                        >
                          <CheckCircle2 className="w-4 h-4 text-white" />
                          <span className="text-xs">خوب</span>
                        </button>

                        {/* 3. متوسط */}
                        <button
                          type="button"
                          onClick={() => handleCleanlinessGradeChange(room.id, 'متوسط')}
                          className={`p-2.5 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1 ${
                            data.grade === 'متوسط'
                              ? 'bg-amber-500 text-slate-950 font-bold shadow-sm border-amber-500 ring-2 ring-amber-300'
                              : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                          }`}
                        >
                          <AlertTriangle className="w-4 h-4" />
                          <span className="text-xs">متوسط</span>
                        </button>

                        {/* 4. ضعیف */}
                        <button
                          type="button"
                          onClick={() => handleCleanlinessGradeChange(room.id, 'ضعیف')}
                          className={`p-2.5 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1 ${
                            data.grade === 'ضعیف'
                              ? 'bg-rose-600 text-white font-bold shadow-sm border-rose-600 ring-2 ring-rose-300'
                              : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                          }`}
                        >
                          <XCircle className="w-4 h-4 text-white" />
                          <span className="text-xs">ضعیف</span>
                        </button>
                      </div>
                    </div>

                    {/* Inspector Note Input */}
                    <div>
                      <input
                        type="text"
                        value={data.notes}
                        onChange={(e) => handleCleanlinessNotesChange(room.id, e.target.value)}
                        placeholder="توضیحات و تذکرات سرپرست به ساکنین (مثال: کف تمیز، تذکر بابت سطل زباله)..."
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:bg-white focus:border-indigo-500"
                      />
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Bottom Save Action */}
            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={handleSaveCleanliness}
                className="px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-2 cursor-pointer"
              >
                <Save className="w-4 h-4" />
                <span>ثبت و ارسال ارزیابی نظافت امشب به مدیریت 💾</span>
              </button>
            </div>
          </div>
        )}

        {/* Supervisor ID Card Modal */}
        {showSupervisorCardModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-fadeIn">
            <SupervisorCard
              supervisor={activeSup}
              onClose={() => setShowSupervisorCardModal(false)}
            />
          </div>
        )}

        {/* Supervisor Damage / Maintenance Report Modal */}
        {showDamageReportModal && (
          <SupervisorReportDamageModal
            rooms={rooms}
            currentSupervisor={activeSup}
            preselectedRoomId={reportForRoomId}
            onClose={() => setShowDamageReportModal(false)}
            onSubmit={(newReport) => {
              if (onSaveMaintenanceReport) {
                onSaveMaintenanceReport(newReport);
              }
              setSuccessMessage(`گزارش خرابی برای اتاق ${newReport.roomNumber} با موفقیت ثبت و به کارتابل مدیر ارسال گردید.`);
              setTimeout(() => setSuccessMessage(null), 4000);
            }}
          />
        )}

      </div>
    </div>
  );
};
