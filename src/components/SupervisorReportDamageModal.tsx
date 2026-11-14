import React, { useState } from 'react';
import { DormRoom, MaintenanceReport, SupervisorAccount, MaintenanceUrgency, MaintenanceType } from '../types';
import { AlertTriangle, Wrench, ShoppingCart, UserCheck, Clock, X, Send } from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface SupervisorReportDamageModalProps {
  rooms: DormRoom[];
  currentSupervisor?: SupervisorAccount;
  preselectedRoomId?: number;
  onClose: () => void;
  onSubmit: (report: MaintenanceReport) => void;
}

const COMMON_DAMAGED_ITEMS = [
  'یخچال اتاق',
  'شیر آب و لوله روشویی',
  'تخت خواب و کفی فلزی',
  'شوفاژ / رادیاتور گرمایشی',
  'اسپلیت / کولر سرمایشی',
  'جارو و خاک‌انداز',
  'لگن و سطل شستشو',
  'سطل زباله پدالی',
  'تلویزیون دیواری',
  'پریز و کلید برق',
  'قفل و دستگیره درب'
];

export const SupervisorReportDamageModal: React.FC<SupervisorReportDamageModalProps> = ({
  rooms,
  currentSupervisor,
  preselectedRoomId,
  onClose,
  onSubmit
}) => {
  const activeRooms = rooms.filter(r => r.isActive);
  const initialRoom = preselectedRoomId 
    ? activeRooms.find(r => r.id === preselectedRoomId) || activeRooms[0]
    : activeRooms[0];

  const [selectedRoomId, setSelectedRoomId] = useState<number>(initialRoom?.id || 1);
  const [equipmentName, setEquipmentName] = useState<string>('یخچال اتاق');
  const [maintenanceType, setMaintenanceType] = useState<MaintenanceType>('تعمیر');
  const [urgency, setUrgency] = useState<MaintenanceUrgency>('فوری');
  const [description, setDescription] = useState<string>('');

  const activeSup = currentSupervisor || {
    id: 1,
    nationalId: '0012345678',
    fullName: 'محمدرضا علیزاده',
    assignedDuty: 'سرپرست شیفت شب'
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const targetRoom = rooms.find(r => r.id === selectedRoomId);
    if (!targetRoom) return;

    if (!equipmentName.trim() || !description.trim()) {
      alert('لطفاً عنوان تجهیز و شرح مشکل را تکمیل فرمایید.');
      return;
    }

    const now = new Date();
    const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    const dateStr = '۱۴۰۳/۰۷/۱۴'; // Current Jalali Date

    const newReport: MaintenanceReport = {
      id: Date.now(),
      roomId: targetRoom.id,
      roomNumber: targetRoom.roomNumber,
      floor: targetRoom.floor,
      equipmentName: equipmentName.trim(),
      maintenanceType,
      urgency,
      status: 'در انتظار اقدام',
      description: description.trim(),
      reportedBySupervisorId: activeSup.nationalId,
      reportedBySupervisorName: activeSup.fullName,
      reportedAt: `${dateStr} - ${timeStr}`
    };

    onSubmit(newReport);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-[#1e293b] text-white rounded-3xl max-w-lg w-full border border-slate-700 shadow-2xl p-6 space-y-5 my-8">
        
        {/* Header */}
        <div className="flex items-center justify-between border-b border-slate-700 pb-3">
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-rose-500/20 text-rose-300">
              <AlertTriangle className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-sm font-bold text-white">ثبت گزارش خرابی و نقص فنی به مدیریت خوابگاه‌ها</h3>
              <span className="text-[11px] text-slate-400">ارسال مستقیم به کارتابل اطلاع‌رسانی مدیر دانشگاه</span>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold cursor-pointer"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          
          {/* Room Selection */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1">اتاق مربوطه *:</label>
            <select
              value={selectedRoomId}
              onChange={(e) => setSelectedRoomId(parseInt(e.target.value))}
              className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none cursor-pointer"
            >
              {activeRooms.map(r => (
                <option key={r.id} value={r.id}>
                  اتاق {r.roomNumber} ({r.floor}) - ظرفیت {r.capacity} نفره
                </option>
              ))}
            </select>
          </div>

          {/* Damaged Item Name */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1">نام قطعه یا وسیله آسیب‌دیده *:</label>
            <input
              type="text"
              value={equipmentName}
              onChange={(e) => setEquipmentName(e.target.value)}
              placeholder="مثلاً: شیر آب روشویی، یخچال، جارو، تخت و..."
              className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none"
            />
            {/* Quick tags */}
            <div className="flex flex-wrap gap-1 mt-1.5">
              {COMMON_DAMAGED_ITEMS.slice(0, 6).map(item => (
                <button
                  key={item}
                  type="button"
                  onClick={() => setEquipmentName(item)}
                  className="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 hover:text-white border border-slate-700 cursor-pointer"
                >
                  {item}
                </button>
              ))}
            </div>
          </div>

          {/* Maintenance Type */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1.5">نوع درخواست و اقدام پیشنهادی:</label>
            <div className="grid grid-cols-2 gap-3">
              <label className={`p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors ${
                maintenanceType === 'تعمیر' ? 'bg-indigo-950/60 border-indigo-500 text-white font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="mType"
                  checked={maintenanceType === 'تعمیر'}
                  onChange={() => setMaintenanceType('تعمیر')}
                  className="accent-indigo-600"
                />
                <Wrench className="w-4 h-4 text-indigo-400" />
                <span>نیاز به تعمیر قطعه 🛠</span>
              </label>

              <label className={`p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors ${
                maintenanceType === 'تعویض / خرید جدید' ? 'bg-amber-950/60 border-amber-500 text-white font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="mType"
                  checked={maintenanceType === 'تعویض / خرید جدید'}
                  onChange={() => setMaintenanceType('تعویض / خرید جدید')}
                  className="accent-amber-600"
                />
                <ShoppingCart className="w-4 h-4 text-amber-400" />
                <span>نیاز به خرید و تعویض نو 🛒</span>
              </label>
            </div>
          </div>

          {/* Urgency */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1.5">سطح فوریت و اولویت رسیدگی:</label>
            <div className="grid grid-cols-3 gap-2">
              <label className={`p-2 rounded-xl border text-center cursor-pointer transition-colors ${
                urgency === 'فوری' ? 'bg-rose-950/60 border-rose-500 text-rose-300 font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="urgency"
                  checked={urgency === 'فوری'}
                  onChange={() => setUrgency('فوری')}
                  className="sr-only"
                />
                <span>🔴 فوری (اضطراری)</span>
              </label>

              <label className={`p-2 rounded-xl border text-center cursor-pointer transition-colors ${
                urgency === 'عادی' ? 'bg-amber-950/60 border-amber-500 text-amber-300 font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="urgency"
                  checked={urgency === 'عادی'}
                  onChange={() => setUrgency('عادی')}
                  className="sr-only"
                />
                <span>🟡 عادی و متعارف</span>
              </label>

              <label className={`p-2 rounded-xl border text-center cursor-pointer transition-colors ${
                urgency === 'کم‌اهمیت' ? 'bg-slate-800 border-slate-600 text-slate-300 font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'
              }`}>
                <input
                  type="radio"
                  name="urgency"
                  checked={urgency === 'کم‌اهمیت'}
                  onChange={() => setUrgency('کم‌اهمیت')}
                  className="sr-only"
                />
                <span>🟢 کم‌اهمیت / نوبتی</span>
              </label>
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1">شرح دقیق خرابی و وضعیت فعلی *:</label>
            <textarea
              rows={3}
              required
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="مثلاً: در اثر نوسان برق، رله استارت یخچال سوخته و کار نمی‌کند / دسته جارو شکسته و امکان نظافت نیست..."
              className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none resize-none"
            />
          </div>

          {/* Auto Audit Info */}
          <div className="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
            <span className="flex items-center gap-1.5">
              <UserCheck className="w-3.5 h-3.5 text-indigo-400" />
              <span>سرپرست بازرس: <strong className="text-white">{activeSup.fullName}</strong></span>
            </span>
            <span className="flex items-center gap-1.5 font-mono">
              <Clock className="w-3.5 h-3.5 text-slate-500" />
              <span>ثبت خودکار ساعت و تاریخ</span>
            </span>
          </div>

          {/* Footer Actions */}
          <div className="pt-3 border-t border-slate-700 flex items-center justify-between">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold cursor-pointer"
            >
              انصراف
            </button>

            <button
              type="submit"
              className="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white rounded-xl font-bold flex items-center gap-2 shadow-lg shadow-rose-600/30 cursor-pointer"
            >
              <Send className="w-4 h-4" />
              <span>ارسال گزارش به کارتابل مدیر 📨</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};
