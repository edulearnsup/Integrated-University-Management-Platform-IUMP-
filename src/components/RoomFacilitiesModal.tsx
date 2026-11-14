import React, { useState } from 'react';
import { DormRoom, RoomFacilities, CustomSecondaryItem } from '../types';
import {
  Wrench, CheckCircle2, AlertTriangle, Refrigerator, Bed,
  Sparkles, Tv, Wind, Flame, Layers, Plus, X, ShieldAlert,
  Info, Check, Trash2, ShieldCheck, Tag
} from 'lucide-react';
import { toPersianDigits } from '../utils/validators';

interface RoomFacilitiesModalProps {
  room: DormRoom | null;
  onClose: () => void;
  onSave: (updatedRoom: DormRoom) => void;
}

const QUICK_SECONDARY_ITEMS = [
  'سطل آشغال پدالی',
  'جارو و خاک‌انداز دسته بلند',
  'لگن شستشوی لباس',
  'تی شستشو',
  'پرده پنجره پارچه‌ای',
  'پنکه پایه‌دار',
  'اتو و میز اتو',
  'تلویزیون دیواری',
  'جاکفشی چوبی',
  'آینه قدی دیواری',
  'چراغ مطالعه اختصاصی',
  'پریز محافظ برق چندراهی'
];

export const RoomFacilitiesModal: React.FC<RoomFacilitiesModalProps> = ({
  room,
  onClose,
  onSave
}) => {
  if (!room) return null;

  // ۱. ظرفیت و تخت‌ها (الزامات پایه اتاق - تعداد تخت مستقیماً ظرفیت اتاق را تعیین می‌کند)
  const [bedCount, setBedCount] = useState<number>(
    room.facilities?.bedCount ?? room.capacity
  );
  const [bedType, setBedType] = useState<string>(
    room.facilities?.bedType || 'دو طبقه فلزی نردبان‌دار استاندارد'
  );

  // ۲. امکانات اصلی خوابگاه (یخچال، کمد، فرش - الزامی)
  const [refrigeratorCount, setRefrigeratorCount] = useState<number>(
    room.facilities?.refrigeratorCount ?? 1
  );
  const [refrigeratorModel, setRefrigeratorModel] = useState<string>(
    room.facilities?.refrigeratorModel || 'یخچال امرسان ۵ فوت استاندارد'
  );
  const [wardrobeCount, setWardrobeCount] = useState<number>(
    room.facilities?.wardrobeCount ?? (room.facilities?.bedCount ?? room.capacity)
  );
  const [wardrobeType, setWardrobeType] = useState<string>('کمد دیواری قفل‌دار مجزا برای هر دانشجو');
  const [carpetCount, setCarpetCount] = useState<number>(
    room.facilities?.carpetCount ?? 1
  );
  const [carpetSize, setCarpetSize] = useState<string>('فرش ۶ متری ماشینی استاندارد');

  // ۳. امکانات فرعی و تکمیلی (ورود دستی توسط مدیر)
  const initialSecondaryItems: CustomSecondaryItem[] = room.facilities?.secondaryItems || [
    { id: 'sec-1', name: 'سطل آشغال پدالی', count: 1 },
    { id: 'sec-2', name: 'جارو و خاک‌انداز', count: 1 },
    { id: 'sec-3', name: 'لگن شستشوی لباس', count: 1 }
  ];
  const [secondaryItems, setSecondaryItems] = useState<CustomSecondaryItem[]>(initialSecondaryItems);
  const [newSecondaryName, setNewSecondaryName] = useState<string>('');
  const [newSecondaryCount, setNewSecondaryCount] = useState<number>(1);
  const [newSecondaryNotes, setNewSecondaryNotes] = useState<string>('');

  // سایر امکانات تکمیلی برای سازگاری
  const [hasTv, setHasTv] = useState<boolean>(room.facilities?.hasTv ?? false);
  const [tvModel, setTvModel] = useState<string>(room.facilities?.tvModel || 'تلویزیون ۳۲ اینچ دیواری');
  const [hasAc, setHasAc] = useState<boolean>(room.facilities?.hasAc ?? true);
  const [acType, setAcType] = useState<string>(room.facilities?.acType || 'اسپلیت سرمایشی اینورتر');
  const [hasHeater, setHasHeater] = useState<boolean>(room.facilities?.hasHeater ?? true);
  const [specialNotes, setSpecialNotes] = useState<string>(room.facilities?.specialNotes || '');

  const handleAddSecondaryItem = (nameToAdd?: string) => {
    const name = (nameToAdd || newSecondaryName).trim();
    if (!name) return;

    const existingIdx = secondaryItems.findIndex(i => i.name.toLowerCase() === name.toLowerCase());
    if (existingIdx >= 0) {
      // Increase count if exists
      setSecondaryItems(prev => prev.map((item, idx) => 
        idx === existingIdx ? { ...item, count: item.count + (nameToAdd ? 1 : newSecondaryCount) } : item
      ));
    } else {
      const newItem: CustomSecondaryItem = {
        id: `sec-${Date.now()}-${Math.random().toString(36).substr(2, 4)}`,
        name,
        count: Math.max(1, nameToAdd ? 1 : newSecondaryCount),
        notes: newSecondaryNotes.trim() || undefined
      };
      setSecondaryItems(prev => [...prev, newItem]);
    }

    if (!nameToAdd) {
      setNewSecondaryName('');
      setNewSecondaryCount(1);
      setNewSecondaryNotes('');
    }
  };

  const handleRemoveSecondaryItem = (id: string) => {
    setSecondaryItems(prev => prev.filter(i => i.id !== id));
  };

  const handleUpdateSecondaryCount = (id: string, delta: number) => {
    setSecondaryItems(prev => prev.map(item => {
      if (item.id === id) {
        const next = Math.max(1, item.count + delta);
        return { ...item, count: next };
      }
      return item;
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // اعتبارسنجی موارد اصلی (باید حتما یخچال، کمد و فرش داشته باشد)
    if (refrigeratorCount < 1) {
      alert('طبق دستورالعمل خوابگاه، هر اتاق باید حداقل یک یخچال دارا باشد.');
      return;
    }
    if (wardrobeCount < 1) {
      alert('طبق دستورالعمل خوابگاه، هر اتاق باید دارای کمد اختصاصی برای دانشجویان باشد.');
      return;
    }
    if (carpetCount < 1) {
      alert('طبق دستورالعمل خوابگاه، هر اتاق باید حداقل دارای ۱ تخته فرش یا موکت باشد.');
      return;
    }

    const safeBedCount = Math.max(1, bedCount);

    const facilities: RoomFacilities = {
      // الزامات تخت
      bedCount: safeBedCount,
      bedType: bedType.trim(),

      // امکانات اصلی
      refrigeratorCount: Math.max(1, refrigeratorCount),
      refrigeratorModel: refrigeratorModel.trim(),
      wardrobeCount: Math.max(1, wardrobeCount),
      carpetCount: Math.max(1, carpetCount),

      // امکانات فرعی
      secondaryItems,

      // امکانات تکمیلی
      hasTv,
      tvModel: hasTv ? tvModel.trim() : undefined,
      hasAc,
      acType: hasAc ? acType.trim() : undefined,
      hasHeater,
      cleaningTools: {
        hasBroom: secondaryItems.some(i => i.name.includes('جارو')),
        hasBasin: secondaryItems.some(i => i.name.includes('لگن') || i.name.includes('سطل')),
        hasTrashCan: secondaryItems.some(i => i.name.includes('سطل') || i.name.includes('زباله'))
      },
      customEquipment: secondaryItems.map(i => `${i.name} (${toPersianDigits(i.count)} عدد)`),
      specialNotes: specialNotes.trim() || undefined,
      updatedAt: '۱۴۰۳/۰۷/۱۴'
    };

    const updatedRoom: DormRoom = {
      ...room,
      capacity: safeBedCount, // ظرفیت اتاق دقیقاً با تعداد تخت همگام می‌شود
      isCompleted: true,
      facilitiesCompletedAt: '۱۴۰۳/۰۷/۱۴',
      facilities
    };

    onSave(updatedRoom);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4 overflow-y-auto animate-fadeIn" dir="rtl">
      <div className="bg-[#1e293b] text-white rounded-3xl max-w-3xl w-full border border-slate-700 shadow-2xl p-6 space-y-5 my-8 max-h-[92vh] overflow-y-auto">
        
        {/* Modal Header */}
        <div className="flex items-center justify-between border-b border-slate-700 pb-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold text-xl">
              🏢
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-base font-bold text-white">
                  مدیریت و تکمیل اطلاعات خوابگاه - اتاق <span className="font-mono text-indigo-300">{room.roomNumber}</span> ({room.floor})
                </h3>
                <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                  room.isCompleted
                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                    : 'bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse'
                }`}>
                  {room.isCompleted ? 'تکمیل‌شده ✓' : 'تکمیل‌نشده ⚠️'}
                </span>
              </div>
              <p className="text-xs text-slate-400 mt-0.5">
                تفکیک الزامات پایه (ظرفیت و تخت)، امکانات اصلی (یخچال، کمد، فرش) و ثبت دستی امکانات فرعی
              </p>
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

        {/* Notice Info Box */}
        <div className="p-3.5 bg-indigo-950/40 border border-indigo-800/60 rounded-2xl flex items-start gap-3 text-xs text-indigo-200">
          <Info className="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
          <p className="leading-relaxed">
            امکانات ثبت‌شده توسط مدیر در این برگه، مستقیماً در داشبورد سرپرستان شیفت شب نمایش داده می‌شود. در صورت خرابی هر قطعه (از جمله تخت، یخچال، کمد، فرش یا امکانات فرعی)، سرپرست گزارش نقص را به کارتابل اطلاع‌رسانی مدیر ارسال خواهد کرد.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6 text-xs">
          
          {/* ========================================================================= */}
          {/* ۱. الزامات پایه اتاق (تخت و ظرفیت) - تخت جزو لیست امکانات نیست */}
          {/* ========================================================================= */}
          <div className="p-4 bg-slate-900/90 rounded-2xl border border-indigo-500/30 space-y-3">
            <div className="flex items-center justify-between border-b border-slate-800 pb-2">
              <span className="font-bold text-white flex items-center gap-2 text-xs">
                <Bed className="w-4 h-4 text-indigo-400" />
                <span className="text-indigo-300 font-bold">۱. ظرفیت اتاق و تخت‌ها (الزامات اولیه و پایه اتاق)</span>
              </span>
              <span className="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 rounded text-[10px] font-mono">
                تعداد تخت = ظرفیت رسمی اتاق
              </span>
            </div>

            <div className="p-2.5 bg-indigo-950/30 rounded-xl border border-indigo-900/40 text-[11px] text-slate-300 leading-relaxed">
              ℹ️ <strong>توجه ساختاری:</strong> تخت جزو الزامات اولیه اسکان است و در لیست امکانات قرار نمی‌گیرد. در این بخش می‌توانید ظرفیت اتاق را که همان تعداد تخت است ویرایش نمایید (امکان گزارش خرابی تخت توسط سرپرست خوابگاه در سیستم وجود دارد).
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div>
                <label className="block text-slate-300 mb-1 font-semibold">
                  ظرفیت اتاق (تعداد کل تخت‌های استاندارد):
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    min="1"
                    max="20"
                    value={bedCount}
                    onChange={(e) => setBedCount(parseInt(e.target.value) || 1)}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none font-mono text-center font-bold text-sm"
                  />
                  <span className="text-slate-400 text-xs shrink-0">تخت</span>
                </div>
              </div>

              <div>
                <label className="block text-slate-300 mb-1 font-semibold">ساختار و مدل تخت‌ها:</label>
                <select
                  value={bedType}
                  onChange={(e) => setBedType(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 text-white rounded-xl focus:border-indigo-500 outline-none cursor-pointer"
                >
                  <option value="دو طبقه فلزی نردبان‌دار استاندارد">دو طبقه فلزی نردبان‌دار استاندارد</option>
                  <option value="تک طبقه چوبی با کفی فلزی">تک طبقه چوبی با کفی فلزی</option>
                  <option value="دو طبقه فلزی سنگین صنعتی">دو طبقه فلزی سنگین صنعتی</option>
                  <option value="تخت باکس هتلی چرمی">تخت باکس هتلی چرمی</option>
                </select>
              </div>
            </div>
          </div>

          {/* ========================================================================= */}
          {/* ۲. امکانات اصلی خوابگاه (یخچال، کمد، فرش - باید حتما دارا باشد) */}
          {/* ========================================================================= */}
          <div className="p-4 bg-slate-900/90 rounded-2xl border border-emerald-500/30 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-800 pb-2">
              <span className="font-bold text-white flex items-center gap-2 text-xs">
                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                <span className="text-emerald-300 font-bold">۲. امکانات اصلی و الزامی خوابگاه (یخچال، کمد، فرش)</span>
              </span>
              <span className="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 rounded text-[10px] font-bold">
                الزامی برای هر اتاق ✓
              </span>
            </div>

            <p className="text-[11px] text-slate-400">
              هر خوابگاه باید حتماً این ۳ مورد اصلی را دارا باشد تا اطلاعات آن به عنوان «تکمیل‌شده» تایید گردد:
            </p>

            {/* A. Refrigerator (یخچال) */}
            <div className="p-3 bg-slate-950/70 rounded-xl border border-slate-800/80 space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-white flex items-center gap-1.5">
                  <span>🧊</span>
                  <span>مورد اصلی ۱: یخچال اتاق</span>
                </span>
                <span className="text-[11px] text-emerald-400 font-mono">
                  {toPersianDigits(refrigeratorCount)} دستگاه
                </span>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">تعداد یخچال (حداقل ۱):</label>
                  <input
                    type="number"
                    min="1"
                    max="4"
                    value={refrigeratorCount}
                    onChange={(e) => setRefrigeratorCount(parseInt(e.target.value) || 1)}
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">مدل و مشخصات یخچال:</label>
                  <input
                    type="text"
                    value={refrigeratorModel}
                    onChange={(e) => setRefrigeratorModel(e.target.value)}
                    placeholder="مثال: امرسان ۵ فوت، ایستکول ۶ فوت"
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
              </div>
            </div>

            {/* B. Wardrobe (کمد) */}
            <div className="p-3 bg-slate-950/70 rounded-xl border border-slate-800/80 space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-white flex items-center gap-1.5">
                  <span>🚪</span>
                  <span>مورد اصلی ۲: کمد لباس و وسایل</span>
                </span>
                <span className="text-[11px] text-emerald-400 font-mono">
                  {toPersianDigits(wardrobeCount)} درب / کمد
                </span>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">تعداد کمد یا درب قفل‌دار (حداقل ۱):</label>
                  <input
                    type="number"
                    min="1"
                    max="16"
                    value={wardrobeCount}
                    onChange={(e) => setWardrobeCount(parseInt(e.target.value) || 1)}
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">نوع و مشخصات کمد:</label>
                  <input
                    type="text"
                    value={wardrobeType}
                    onChange={(e) => setWardrobeType(e.target.value)}
                    placeholder="مثال: کمد دیواری قفل‌دار اختصاصی MDF"
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
              </div>
            </div>

            {/* C. Carpet (فرش) */}
            <div className="p-3 bg-slate-950/70 rounded-xl border border-slate-800/80 space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-white flex items-center gap-1.5">
                  <span>🧶</span>
                  <span>مورد اصلی ۳: فرش و کف‌پوش اتاق</span>
                </span>
                <span className="text-[11px] text-emerald-400 font-mono">
                  {toPersianDigits(carpetCount)} تخته
                </span>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">تعداد تخته فرش / موکت (حداقل ۱):</label>
                  <input
                    type="number"
                    min="1"
                    max="6"
                    value={carpetCount}
                    onChange={(e) => setCarpetCount(parseInt(e.target.value) || 1)}
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
                <div>
                  <label className="block text-slate-400 mb-1 text-[11px]">متراژ و نوع فرش:</label>
                  <input
                    type="text"
                    value={carpetSize}
                    onChange={(e) => setCarpetSize(e.target.value)}
                    placeholder="مثال: فرش ۶ متری ماشینی ۷۰۰ شانه، موکت پرزدار پالاز"
                    className="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl focus:border-emerald-500 outline-none text-xs"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* ========================================================================= */}
          {/* ۳. امکانات فرعی خوابگاه (ثبت دستی توسط مدیر) */}
          {/* ========================================================================= */}
          <div className="p-4 bg-slate-900/90 rounded-2xl border border-amber-500/30 space-y-3">
            <div className="flex items-center justify-between border-b border-slate-800 pb-2">
              <span className="font-bold text-white flex items-center gap-2 text-xs">
                <Sparkles className="w-4 h-4 text-amber-400" />
                <span className="text-amber-300 font-bold">۳. امکانات فرعی خوابگاه (ورود دستی توسط مدیر)</span>
              </span>
              <span className="text-[11px] text-slate-400">
                سطل زباله، جارو، لگن، پنکه، اتو و سایر اقلام
              </span>
            </div>

            <p className="text-[11px] text-slate-400">
              هرگونه امکانات فرعی و تکمیلی به صورت پویا توسط مدیر وارد شده و در لیست تجهیزات قابل گزارش سرپرست قرار می‌گیرد.
            </p>

            {/* Quick add chips */}
            <div className="space-y-1.5 pt-1">
              <span className="text-[11px] text-slate-400 block font-semibold">
                پیشنهاد اقلام فرعی پرکاربرد (برای افزودن سریع کلیک کنید):
              </span>
              <div className="flex flex-wrap gap-1.5">
                {QUICK_SECONDARY_ITEMS.map((tag) => (
                  <button
                    key={tag}
                    type="button"
                    onClick={() => handleAddSecondaryItem(tag)}
                    className="px-2.5 py-1 rounded-lg text-[10px] bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 border border-slate-700 cursor-pointer transition-colors"
                  >
                    + {tag}
                  </button>
                ))}
              </div>
            </div>

            {/* Add Custom Item Inputs */}
            <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-2">
              <span className="text-[11px] font-semibold text-white block">افزودن امکان فرعی جدید به اتاق:</span>
              <div className="grid grid-cols-1 sm:grid-cols-12 gap-2">
                <input
                  type="text"
                  value={newSecondaryName}
                  onChange={(e) => setNewSecondaryName(e.target.value)}
                  placeholder="نام امکان فرعی (مثلاً سطل آشغال پدالی، جارو دستی، لگن...)"
                  className="sm:col-span-6 px-3 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none focus:border-amber-500"
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleAddSecondaryItem();
                    }
                  }}
                />
                <div className="sm:col-span-3 flex items-center gap-1.5">
                  <input
                    type="number"
                    min="1"
                    max="20"
                    value={newSecondaryCount}
                    onChange={(e) => setNewSecondaryCount(parseInt(e.target.value) || 1)}
                    className="w-full px-2 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs outline-none text-center font-bold font-mono"
                  />
                  <span className="text-slate-400 text-[11px] shrink-0">عدد</span>
                </div>
                <button
                  type="button"
                  onClick={() => handleAddSecondaryItem()}
                  disabled={!newSecondaryName.trim()}
                  className="sm:col-span-3 px-3 py-1.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-40 text-slate-950 font-bold rounded-xl text-xs flex items-center justify-center gap-1 cursor-pointer transition-colors"
                >
                  <Plus className="w-3.5 h-3.5" />
                  <span>ثبت امکان فرعی</span>
                </button>
              </div>
            </div>

            {/* List of Registered Secondary Items */}
            <div className="space-y-2 pt-2">
              <span className="text-[11px] font-semibold text-slate-300 block">
                لیست امکانات فرعی ثبت‌شده برای این اتاق ({toPersianDigits(secondaryItems.length)} قلم):
              </span>

              {secondaryItems.length === 0 ? (
                <p className="text-center p-4 bg-slate-950/60 rounded-xl border border-dashed border-slate-800 text-slate-500 text-xs">
                  هنوز هیچ امکان فرعی برای این اتاق ثبت نشده است. از گزینه‌های بالا یک مورد اضافه نمایید.
                </p>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {secondaryItems.map((item) => (
                    <div
                      key={item.id}
                      className="p-2.5 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between text-xs"
                    >
                      <div className="flex items-center gap-2">
                        <Tag className="w-3.5 h-3.5 text-amber-400 shrink-0" />
                        <span className="font-semibold text-white">{item.name}</span>
                      </div>

                      <div className="flex items-center gap-2">
                        <div className="flex items-center bg-slate-900 rounded-lg border border-slate-700 px-1 py-0.5">
                          <button
                            type="button"
                            onClick={() => handleUpdateSecondaryCount(item.id, -1)}
                            className="px-1.5 text-slate-400 hover:text-white cursor-pointer font-bold"
                          >
                            -
                          </button>
                          <span className="px-2 font-mono font-bold text-amber-300 text-[11px]">
                            {toPersianDigits(item.count)}
                          </span>
                          <button
                            type="button"
                            onClick={() => handleUpdateSecondaryCount(item.id, 1)}
                            className="px-1.5 text-slate-400 hover:text-white cursor-pointer font-bold"
                          >
                            +
                          </button>
                        </div>

                        <button
                          type="button"
                          onClick={() => handleRemoveSecondaryItem(item.id)}
                          className="p-1 text-slate-400 hover:text-rose-400 cursor-pointer"
                          title="حذف این امکان فرعی"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* TV & Climate Toggles (امکانات رفاهی سرمایشی/گرمایشی) */}
          <div className="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-3">
            <span className="font-bold text-white flex items-center gap-2 text-xs border-b border-slate-800 pb-2">
              <span>❄️</span>
              <span>سیستم‌های سرمایش و گرمایش اتاق</span>
            </span>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-semibold text-slate-300 flex items-center gap-1.5">
                    <Wind className="w-4 h-4 text-cyan-400" />
                    <span>سیستم سرمایشی / کولر:</span>
                  </span>
                  <input
                    type="checkbox"
                    checked={hasAc}
                    onChange={(e) => setHasAc(e.target.checked)}
                    className="w-4 h-4 accent-cyan-600 rounded cursor-pointer"
                  />
                </div>
                {hasAc && (
                  <input
                    type="text"
                    value={acType}
                    onChange={(e) => setAcType(e.target.value)}
                    placeholder="نوع کولر (اسپلیت ۲۴۰۰۰، کولر آبی)"
                    className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 text-white rounded-lg text-xs outline-none"
                  />
                )}
              </div>

              <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-semibold text-slate-300 flex items-center gap-1.5">
                    <Flame className="w-4 h-4 text-orange-400" />
                    <span>سیستم گرمایشی / شوفاژ:</span>
                  </span>
                  <input
                    type="checkbox"
                    checked={hasHeater}
                    onChange={(e) => setHasHeater(e.target.checked)}
                    className="w-4 h-4 accent-orange-600 rounded cursor-pointer"
                  />
                </div>
                <span className="text-[11px] text-slate-400 block">
                  {hasHeater ? 'شوفاژ مرکزی پردیس فعال است' : 'فاقد شوفاژ مرکزی'}
                </span>
              </div>
            </div>
          </div>

          {/* Special Notes */}
          <div>
            <label className="block text-slate-300 font-semibold mb-1">یادداشت و دستورالعمل‌های خاص مدیر برای این اتاق:</label>
            <textarea
              rows={2}
              value={specialNotes}
              onChange={(e) => setSpecialNotes(e.target.value)}
              placeholder="نکات تحویل و تحول اموال، وضعیت ظاهری دیوارها و موکت، تذکرات لازم..."
              className="w-full px-3 py-2 bg-slate-900 border border-slate-700 text-white rounded-xl outline-none focus:border-indigo-500 resize-none text-xs"
            />
          </div>

          {/* Action Footer */}
          <div className="pt-4 border-t border-slate-700 flex items-center justify-between">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold cursor-pointer"
            >
              انصراف
            </button>

            <button
              type="submit"
              className="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 cursor-pointer"
            >
              <Check className="w-4 h-4" />
              <span>ذخیره نهایی و تکمیل اطلاعات خوابگاه ✓</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};
