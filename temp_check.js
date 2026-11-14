
        const tabs = ['overview', 'admitted', 'unhoused', 'rooms', 'facilities', 'maintenance', 'cleanliness', 'students', 'attendance', 'supervisors', 'festivals', 'skills'];
        const tabTitles = {
            'overview': 'پیشخوان و داشبورد مدیریتی صمد',
            'admitted': 'پذیرش دانشجوی جدید در جدول منبع (admitted_students)',
            'unhoused': 'دانشجویان متقاضی خوابگاه در انتظار اسکان',
            'rooms': 'مدیریت و نظارت بر اتاق‌ها و طبقات خوابگاه',
            'facilities': 'شناسنامه و امکانات تفکیکی اتاق‌های خوابگاه',
            'maintenance': 'کارتابل خرابی، درخواست تعمیر/تعویض و فاکتورهای مالی',
            'cleanliness': 'ارزیابی کیفیت بهداشت و رتبه‌بندی عملکرد نظافت خوابگاه',
            'students': 'فهرست دانشجویان اسکان‌یافته و مدیریت میهمانان',
            'attendance': 'بررسی سرشماری شبانه خوابگاه و صدور کارنامه تردد',
            'supervisors': 'فهرست سرپرستان خوابگاه و صدور کارت پرسنلی',
            'festivals': 'سامانه جشنواره‌ها، المپیادها و داوطلبان دانشجویی',
            'skills': 'بانک مهارت‌های دانشجویی و استعدادیابی (جستجوی زنده)'
        };

        const allHousedData = "";
        const allFacilitiesData = "";

        function switchTab(tabId) {
            tabs.forEach(t => {
                const pane = document.getElementById('tab-' + t);
                const btn = document.getElementById('nav-' + t);
                if (pane) {
                    if (t === tabId) {
                        pane.classList.remove('hidden');
                    } else {
                        pane.classList.add('hidden');
                    }
                }
                if (btn) {
                    if (t === tabId) {
                        btn.className = 'tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25';
                    } else {
                        btn.className = 'tab-nav-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800/60';
                    }
                }
            });

            const titleEl = document.getElementById('top-title');
            if (titleEl && tabTitles[tabId]) {
                titleEl.textContent = tabTitles[tabId];
            }

            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }

        // مقداردهی اولیه تب بر اساس پارامتر URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab') || '""';
            if (tabs.includes(initialTab)) {
                switchTab(initialTab);
            } else {
                switchTab('overview');
            }
        });

        // مودال اسکان با پشتیبانی روزهای میهمانی
        function openAssignModal(studentId, fullName, studyShift) {
            document.getElementById('modal-std-id').value = studentId;
            document.getElementById('modal-std-name').textContent = fullName + ' (' + studentId + ')';
            document.getElementById('assignModal').classList.remove('hidden');
        }
        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }

        function toggleGuestDaysInput(val) {
            const box = document.getElementById('guest-days-box');
            if (val === 'میهمان') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        // مودال ویرایش اسکان
        function openEditDormModal(stdId, roomId, dormStatus, guestDays) {
            document.getElementById('edit-std-id-val').value = stdId;
            document.getElementById('edit-std-id-lbl').textContent = stdId;
            document.getElementById('edit-room-select').value = roomId;
            document.getElementById('edit-dorm-status').value = dormStatus;
            document.getElementById('edit-guest-days-val').value = guestDays || 7;
            toggleEditGuestDays(dormStatus);
            document.getElementById('editDormModal').classList.remove('hidden');
        }
        function closeEditDormModal() {
            document.getElementById('editDormModal').classList.add('hidden');
        }
        function toggleEditGuestDays(val) {
            const box = document.getElementById('edit-guest-box');
            if (val === 'میهمان') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        // مودال پرینت کلی سرشماری
        function openBatchPrintModal() {
            document.getElementById('batchPrintModal').classList.remove('hidden');
        }
        function closeBatchPrintModal() {
            document.getElementById('batchPrintModal').classList.add('hidden');
        }

        // مودال پرینت فردی دانشجو
        function openIndividualPrintModal(name, id, nid, room, floor, status, notes) {
            document.getElementById('indiv-name-lbl').textContent = name;
            document.getElementById('indiv-name-val').textContent = name;
            document.getElementById('indiv-id-val').textContent = id;
            document.getElementById('indiv-nid-val').textContent = nid;
            document.getElementById('indiv-room-val').textContent = 'اتاق ' + room + ' (' + floor + ')';
            document.getElementById('indiv-status-val').textContent = status;
            document.getElementById('indiv-notes-val').textContent = notes;
            document.getElementById('individualPrintModal').classList.remove('hidden');
        }
        function closeIndividualPrintModal() {
            document.getElementById('individualPrintModal').classList.add('hidden');
        }

        // مودال کارت سرپرست
        function openSupervisorCardModal(name, nid) {
            document.getElementById('sup-card-name').textContent = name;
            document.getElementById('sup-card-nid').textContent = nid;
            document.getElementById('supervisorCardModal').classList.remove('hidden');
        }
        function closeSupervisorCardModal() {
            document.getElementById('supervisorCardModal').classList.add('hidden');
        }

        // مودال تنظیم شیفت سرپرست
        function openEditSupervisorShiftModal(id, name, days, hours) {
            document.getElementById('ess-sup-id').value = id;
            document.getElementById('ess-sup-name').textContent = name;
            document.getElementById('ess-shift-days').value = days;
            document.getElementById('ess-shift-hours').value = hours;
            document.getElementById('editSupervisorShiftModal').classList.remove('hidden');
        }
        function closeEditSupervisorShiftModal() {
            document.getElementById('editSupervisorShiftModal').classList.add('hidden');
        }

        // مودال پرونده کامل اتاق و ساکنین
        function openRoomDetailsModal(roomNumber, floor, capacity) {
            document.getElementById('rdm-room-no').textContent = roomNumber;
            document.getElementById('rdm-floor').textContent = floor;
            document.getElementById('rdm-cap').textContent = capacity + ' نفر';

            const occupants = allHousedData.filter(h => String(h.room_number) === String(roomNumber));
            const occupiedCount = occupants.length;
            const freeCount = Math.max(0, capacity - occupiedCount);

            document.getElementById('rdm-occ-cnt').textContent = occupiedCount + ' نفر';
            document.getElementById('rdm-free-cnt').textContent = freeCount + ' تخت';

            const listEl = document.getElementById('rdm-occupants-list');
            listEl.innerHTML = '';

            if (occupants.length === 0) {
                listEl.innerHTML = `
                    <div class="p-8 text-center bg-slate-900/50 rounded-xl border border-slate-800 text-slate-400 text-xs">
                        ⚠️ در حال حاضر هیچ دانشجویی در این اتاق اسکان نیافته است. تمامی ${capacity} تخت این اتاق خالی می‌باشد.
                    </div>
                `;
            } else {
                occupants.forEach((occ, idx) => {
                    const cardDataJson = JSON.stringify(occ).replace(/"/g, '&quot;');
                    const prov = occ.province || 'تهران';
                    const city = occ.city || 'تهران';
                    const guestInfo = occ.dorm_status === 'میهمان' ? ` (${occ.guest_days_remaining || occ.guest_days_total || '۷'} روز باقی‌مانده)` : '';

                    const row = document.createElement('div');
                    row.className = 'p-4 bg-slate-900/90 rounded-xl border border-slate-700/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4';
                    row.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-16 rounded-xl overflow-hidden border border-emerald-500/30 bg-slate-800 shrink-0">
                                <img src="assets/images/student_sample_avatar_1790759772433.jpg" alt="${occ.full_name}" class="w-full h-full object-cover">
                            </div>
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-white text-sm">${occ.full_name}</h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold ${occ.dorm_status === 'میهمان' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'}">
                                        ${occ.dorm_status}${guestInfo}
                                    </span>
                                </div>
                                <div class="text-slate-400 flex items-center gap-2 font-mono">
                                    <span>ش.د: <strong class="text-indigo-400">${occ.student_id}</strong></span>
                                    <span>|</span>
                                    <span>کدملی: <strong class="text-cyan-300">${occ.national_id}</strong></span>
                                </div>
                                <div class="text-slate-300 text-[11px]">
                                    <span>رشته: </span>
                                    <span class="font-semibold text-white">${occ.field_of_study || 'فنی و مهندسی'}</span>
                                    <span class="text-slate-400 mx-1">(${occ.campus || 'پردیس فناوری'})</span>
                                </div>
                                <div class="text-slate-300 text-[11px] flex items-center gap-1.5 pt-0.5">
                                    <span class="text-amber-400">📍</span>
                                    <span>استان و محل سکونت: </span>
                                    <strong class="text-amber-200">${prov} (${city})</strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex sm:flex-col items-center sm:items-end gap-2 shrink-0 w-full sm:w-auto justify-between border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-800">
                            <span class="text-[11px] text-slate-400 font-mono">
                                📞 ${occ.mobile || '۰۹۱۲۰۰۰۰۰۰۰'}
                            </span>
                            <button type="button" onclick='openStudentCardModal(${cardDataJson})' class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-md cursor-pointer flex items-center gap-1">
                                <span>کارت دانشجویی 🪪</span>
                            </button>
                        </div>
                    `;
                    listEl.appendChild(row);
                });
            }

            // نمایش تخت‌های خالی باقیمانده به عنوان اسلات‌های آماده
            if (freeCount > 0) {
                for (let i = 1; i <= freeCount; i++) {
                    const emptyBed = document.createElement('div');
                    emptyBed.className = 'p-3 bg-slate-900/40 border border-dashed border-slate-700/60 rounded-xl flex items-center justify-between text-xs text-slate-400';
                    emptyBed.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">🛏</span>
                            <span>تخت خالی شماره ${occupiedCount + i}</span>
                        </div>
                        <span class="px-2 py-0.5 bg-slate-800 text-slate-400 rounded text-[10px]">آماده تخصیص به متقاضی جدید</span>
                    `;
                    listEl.appendChild(emptyBed);
                }
            }

            document.getElementById('roomDetailsModal').classList.remove('hidden');
        }

        function closeRoomDetailsModal() {
            document.getElementById('roomDetailsModal').classList.add('hidden');
        }

        // مودال کارت دانشجویی هوشمند
        function openStudentCardModal(student) {
            if (!student) return;
            document.getElementById('sc-name').textContent = student.full_name || student.fullName || '';
            document.getElementById('sc-sid').textContent = student.student_id || student.studentId || '';
            document.getElementById('sc-nid').textContent = student.national_id || student.nationalId || '';
            document.getElementById('sc-field').textContent = student.field_of_study || student.fieldOfStudy || 'فنی و مهندسی';
            document.getElementById('sc-campus').textContent = student.campus || 'پردیس فناوری صمد';

            const roomNo = student.room_number || student.roomNumber;
            const floor = student.floor || '';
            document.getElementById('sc-room').textContent = roomNo ? `اتاق ${roomNo} (${floor})` : 'در صف اسکان';

            const prov = student.province || 'تهران';
            const city = student.city || 'تهران';
            document.getElementById('sc-province').textContent = `${prov} (${city})`;

            document.getElementById('sc-dormstatus').textContent = student.dorm_status || student.dormStatus || 'ثبت‌شده در سامانه';

            const sid = String(student.student_id || student.studentId || '1000');
            document.getElementById('sc-stamp').textContent = 'SAMAD-ID-' + sid.slice(-4);

            document.getElementById('studentCardModal').classList.remove('hidden');
        }

        function closeStudentCardModal() {
            document.getElementById('studentCardModal').classList.add('hidden');
        }

        function openStudentCardFromAlloc(student) {
            openStudentCardModal(student);
        }

        function filterHousedTable() {
            const query = (document.getElementById('housedSearchInput')?.value || document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.housed-row, .housed-student-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر جدول پذیرفته‌شدگان (Tab 2) با کد ملی، شماره دانشجویی، نام و رشته
        function filterAdmittedTable() {
            const query = (document.getElementById('admittedSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.admitted-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر دانشجویان در صف اسکان (Tab 3)
        function filterUnhousedTable() {
            const query = (document.getElementById('unhousedSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.unhoused-row');
            rows.forEach(r => {
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                if (!query || search.includes(query)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر اتاق‌ها و طبقات (Tab 4)
        let roomCurrentFloor = 'all';
        let roomCurrentStatus = 'all';

        function setRoomFloorFilter(floor, btn) {
            roomCurrentFloor = floor;
            document.querySelectorAll('.room-floor-btn').forEach(b => {
                b.className = 'room-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'room-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterRoomsTable();
        }

        function setRoomStatusFilter(status, btn) {
            roomCurrentStatus = status;
            document.querySelectorAll('.room-status-btn').forEach(b => {
                b.className = 'room-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'room-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterRoomsTable();
        }

        function filterRoomsTable() {
            const query = (document.getElementById('roomOccupantSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.room-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const active = r.getAttribute('data-active') || '1';
                const occupants = (r.getAttribute('data-occupants') || '').toLowerCase();
                const roomNo = (r.getAttribute('data-room') || '').toLowerCase();

                const matchFloor = (roomCurrentFloor === 'all') || (floor === roomCurrentFloor);
                const matchStatus = (roomCurrentStatus === 'all') || (roomCurrentStatus === 'active' && active === '1') || (roomCurrentStatus === 'inactive' && active === '0');
                const matchQuery = !query || occupants.includes(query) || roomNo.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر دانشجویان اسکان یافته (Tab 5)
        let studentCurrentFloor = 'all';
        let studentCurrentStatus = 'all';

        function setStudentFloorFilter(floor, btn) {
            studentCurrentFloor = floor;
            document.querySelectorAll('.student-floor-btn').forEach(b => {
                b.className = 'student-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'student-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterStudentsTable();
        }

        function setStudentStatusFilter(status, btn) {
            studentCurrentStatus = status;
            document.querySelectorAll('.student-status-btn').forEach(b => {
                b.className = 'student-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'student-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterStudentsTable();
        }

        function filterStudentsTable() {
            const query = (document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.housed-student-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const dormStatus = r.getAttribute('data-dorm-status') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();

                const matchFloor = (studentCurrentFloor === 'all') || (floor === studentCurrentFloor);
                const matchStatus = (studentCurrentStatus === 'all') || (dormStatus === studentCurrentStatus);
                const matchQuery = !query || search.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر سرشماری شبانه (Tab 6)
        let attCurrentFloor = 'all';
        let attCurrentStatus = 'all';

        function setAttFloorFilter(floor, btn) {
            attCurrentFloor = floor;
            document.querySelectorAll('.att-floor-btn').forEach(b => {
                b.className = 'att-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'att-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterAttendanceTable();
        }

        function setAttStatusFilter(status, btn) {
            attCurrentStatus = status;
            document.querySelectorAll('.att-status-btn').forEach(b => {
                b.className = 'att-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'att-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterAttendanceTable();
        }

        function filterAttendanceTable() {
            const query = (document.getElementById('attendanceSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.att-row');

            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const status = r.getAttribute('data-status') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();

                const matchFloor = (attCurrentFloor === 'all') || (floor === attCurrentFloor);
                const matchStatus = (attCurrentStatus === 'all') || (status === attCurrentStatus);
                const matchQuery = !query || search.includes(query);

                if (matchFloor && matchStatus && matchQuery) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // فیلتر داوطلبان جشنواره‌ها (Tab 8)
        let festCurrentCat = 'all';

        function setFestivalCatFilter(cat, btn) {
            festCurrentCat = cat;
            document.querySelectorAll('.fest-cat-btn').forEach(b => {
                b.className = 'fest-cat-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'fest-cat-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-yellow-600 text-white cursor-pointer transition-all';
            filterFestivalsList();
        }

        function filterFestivalsList() {
            const query = (document.getElementById('festivalsSearchInput')?.value || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.fest-card');

            cards.forEach(c => {
                const cat = c.getAttribute('data-category') || '';
                const search = (c.getAttribute('data-search') || '').toLowerCase();

                const matchCat = (festCurrentCat === 'all') || (cat === festCurrentCat);
                const matchQuery = !query || search.includes(query);

                if (matchCat && matchQuery) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        // فیلتر و جستجوی لحظه‌ای بانک مهارت‌ها (Tab 9)
        function filterSkillsLive() {
            const input = document.getElementById('skillLiveQueryInput');
            const clearBtn = document.getElementById('clearSkillBtn');
            const query = (input?.value || '').toLowerCase().trim();

            if (clearBtn) {
                if (query.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }

            const cards = document.querySelectorAll('.skill-card');

            cards.forEach(c => {
                const search = (c.getAttribute('data-search') || '').toLowerCase();
                const badges = c.querySelectorAll('.skill-badge');

                let hasMatchingSkill = false;
                badges.forEach(b => {
                    const badgeText = (b.getAttribute('data-name') || b.textContent || '').toLowerCase();
                    if (query && badgeText.includes(query)) {
                        b.className = 'skill-badge px-2 py-0.5 rounded-lg text-[10px] font-bold bg-yellow-500 text-slate-950 border border-yellow-400 shadow-sm animate-pulse transition-all';
                        hasMatchingSkill = true;
                    } else {
                        b.className = 'skill-badge px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-950/40 text-purple-300 border border-purple-500/30 transition-all';
                    }
                });

                if (!query || search.includes(query) || hasMatchingSkill) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        function setSkillTag(tag) {
            const input = document.getElementById('skillLiveQueryInput');
            if (input) {
                input.value = tag;
                filterSkillsLive();
            }
        }

        function clearSkillLiveQuery() {
            const input = document.getElementById('skillLiveQueryInput');
            if (input) {
                input.value = '';
                filterSkillsLive();
            }
        }

        // ===================== FACILITIES JS =====================
        function openFacilitiesModal(roomNumber, floor) {
            document.getElementById('fac-form-room-number').value = roomNumber;
            document.getElementById('fac-modal-room-title').textContent = roomNumber + (floor ? ' (' + floor + ')' : '');
            
            const fac = (typeof allFacilitiesData !== 'undefined' && allFacilitiesData[roomNumber]) ? allFacilitiesData[roomNumber] : null;
            if (fac) {
                document.getElementById('fac-fridge-model').value = fac.fridge_model || '';
                document.getElementById('fac-bed-type').value = fac.bed_type || '';
                document.getElementById('fac-ac-heater').value = fac.ac_heater || '';
                document.getElementById('fac-tv-model').value = fac.tv_model || '';
                document.getElementById('fac-has-broom').checked = !!parseInt(fac.has_broom);
                document.getElementById('fac-has-trash').checked = !!parseInt(fac.has_trash_bin);
                document.getElementById('fac-has-bucket').checked = !!parseInt(fac.has_bucket);
                document.getElementById('fac-has-carpet').checked = !!parseInt(fac.has_carpet);
                document.getElementById('fac-has-wardrobe').checked = !!parseInt(fac.has_wardrobe);
                document.getElementById('fac-has-desk').checked = !!parseInt(fac.has_desk);
                document.getElementById('fac-has-shoerack').checked = !!parseInt(fac.has_shoe_rack);
                document.getElementById('fac-has-mirror').checked = !!parseInt(fac.has_mirror);
                document.getElementById('fac-notes').value = fac.notes || '';
            } else {
                document.getElementById('fac-fridge-model').value = 'یخچال ۵ فوت امرسان';
                document.getElementById('fac-bed-type').value = 'تخت فلزی دو طبقه استاندارد';
                document.getElementById('fac-ac-heater').value = 'شوفاژ مرکزی + کولر آبی';
                document.getElementById('fac-tv-model').value = '';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = false;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
                document.getElementById('fac-notes').value = '';
            }

            document.getElementById('facilitiesModal').classList.remove('hidden');
        }

        function closeFacilitiesModal() {
            document.getElementById('facilitiesModal').classList.add('hidden');
        }

        let facCurrentFloor = 'all';
        function setFacFloorFilter(floor, btn) {
            facCurrentFloor = floor;
            document.querySelectorAll('.fac-floor-btn').forEach(b => {
                b.className = 'fac-floor-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'fac-floor-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterFacilitiesTable();
        }

        function filterFacilitiesTable() {
            const query = (document.getElementById('facilitiesSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.fac-row');
            rows.forEach(r => {
                const floor = r.getAttribute('data-floor') || '';
                const search = (r.getAttribute('data-search') || '').toLowerCase();
                const matchFloor = (facCurrentFloor === 'all') || (floor === facCurrentFloor);
                const matchQuery = !query || search.includes(query);
                r.style.display = (matchFloor && matchQuery) ? '' : 'none';
            });
        }

        // ===================== MAINTENANCE JS =====================
        function openMaintenanceResolutionModal(rep) {
            if (!rep) return;
            document.getElementById('mrm-report-id').value = rep.id;
            document.getElementById('mrm-id').textContent = '#' + rep.id;
            document.getElementById('mrm-room').textContent = 'اتاق ' + rep.room_number + (rep.floor ? ' (' + rep.floor + ')' : '');
            document.getElementById('mrm-item').textContent = rep.equipment_name || rep.item_name || 'تجهیز خوابگاه';
            document.getElementById('mrm-desc').textContent = rep.description || '-';

            const urgEl = document.getElementById('mrm-urgency');
            const urg = rep.urgency || '';
            if (urg === 'urgent' || urg === 'فوری') {
                urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30';
                urgEl.textContent = 'فوری 🚨';
            } else if (urg === 'normal' || urg === 'عادی') {
                urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30';
                urgEl.textContent = 'عادی';
            } else {
                urgEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400';
                urgEl.textContent = 'کم‌اهمیت';
            }

            document.getElementById('mrm-action-taken').value = rep.resolution_action || rep.action_taken || (rep.maintenance_type === 'replacement' || rep.maintenance_type === 'تعویض / خرید جدید' ? 'replacement' : 'repair');
            document.getElementById('mrm-cost').value = rep.cost_rials || rep.resolution_cost || '';
            document.getElementById('mrm-inv-num').value = rep.invoice_number || '';
            document.getElementById('mrm-contractor').value = rep.contractor_or_store_name || '';
            document.getElementById('mrm-notes').value = rep.resolution_notes || '';

            document.getElementById('maintenanceResolutionModal').classList.remove('hidden');
        }

        function closeMaintenanceResolutionModal() {
            document.getElementById('maintenanceResolutionModal').classList.add('hidden');
        }

        // ===================== SINGLE INVOICE JS =====================
        function openSingleInvoiceModal(rep) {
            if (!rep) return;
            document.getElementById('sim-inv-num').textContent = rep.invoice_number || ('INV-1403-' + rep.id);
            document.getElementById('sim-code').textContent = '#' + rep.id;
            document.getElementById('sim-date').textContent = rep.resolved_at || rep.reported_at || 'ثبت شده در سامانه';
            document.getElementById('sim-room').textContent = 'اتاق ' + rep.room_number + (rep.floor ? ' (' + rep.floor + ')' : '');
            document.getElementById('sim-item').textContent = rep.equipment_name || rep.item_name || 'تجهیز خوابگاه';
            const isRep = (rep.resolution_action || rep.action_taken || '') === 'replacement' || String(rep.resolution_action || '').includes('خرید');
            document.getElementById('sim-action').textContent = isRep ? 'خرید و جایگزینی نو' : 'تعمیر و سرویس قطعه معیوب';
            document.getElementById('sim-contractor').textContent = rep.contractor_or_store_name || 'تعمیرات و خدمات فنی پردیس';
            const cost = parseInt(rep.cost_rials || rep.resolution_cost || 0);
            document.getElementById('sim-cost').textContent = cost.toLocaleString();
            document.getElementById('sim-notes').textContent = rep.resolution_notes || rep.description || 'اقدام فنی لازم توسط پیمانکار مجاز صورت گرفت و تحویل شد.';
            document.getElementById('sim-manager').textContent = rep.resolved_by_manager_name || rep.resolved_by || 'دکتر علیرضا حسینی (مدیر امور رفاهی)';

            const editBtn = document.getElementById('sim-edit-btn');
            if (editBtn) {
                editBtn.onclick = function() {
                    closeSingleInvoiceModal();
                    openMaintenanceResolutionModal(rep);
                };
            }
            document.getElementById('singleInvoiceModal').classList.remove('hidden');
        }

        function closeSingleInvoiceModal() {
            document.getElementById('singleInvoiceModal').classList.add('hidden');
        }

        function printSingleInvoice() {
            const el = document.getElementById('singleInvoicePrintable');
            if (!el) return;
            const w = window.open('', '_blank', 'width=750,height=900');
            if (w) {
                w.document.write('<!DOCTYPE html><html lang="fa" dir="rtl"><head><title>فاکتور رسمی خوابگاه صمد</title>');
                w.document.write('<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">');
                w.document.write('<script src="https://cdn.tailwindcss.com"></sc' + 'ript>');
                w.document.write('<style>body{font-family:Vazirmatn,sans-serif;padding:30px;direction:rtl;text-align:right;}@media print{.no-print{display:none;}}</style>');
                w.document.write('</head><body><div style="max-width:650px;margin:0 auto;border:2px solid #334155;padding:24px;border-radius:16px;">');
                w.document.write(el.innerHTML);
                w.document.write('</div><div style="text-align:center;margin-top:20px;" class="no-print"><button onclick="window.print()" style="padding:8px 24px;background:#4f46e5;color:white;border-radius:8px;font-weight:bold;cursor:pointer;">چاپ سند 🖨</button></div></body></html>');
                w.document.close();
                setTimeout(() => { w.focus(); w.print(); }, 500);
            } else {
                window.print();
            }
        }

        function openMaintenancePrintModal() {
            document.getElementById('maintenanceInvoicePrintModal').classList.remove('hidden');
        }

        function closeMaintenancePrintModal() {
            document.getElementById('maintenanceInvoicePrintModal').classList.add('hidden');
        }

        let maintCurrentStatus = 'all';
        let maintCurrentUrgency = 'all';

        function setMaintStatusFilter(status, btn) {
            maintCurrentStatus = status;
            document.querySelectorAll('.maint-status-btn').forEach(b => {
                b.className = 'maint-status-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'maint-status-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterMaintenanceTable();
        }

        function setMaintUrgencyFilter(urgency, btn) {
            maintCurrentUrgency = urgency;
            document.querySelectorAll('.maint-urgency-btn').forEach(b => {
                b.className = 'maint-urgency-btn px-2.5 py-1 rounded-xl text-xs bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            });
            btn.className = 'maint-urgency-btn px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-600 text-white cursor-pointer transition-all';
            filterMaintenanceTable();
        }

        function filterMaintenanceTable() {
            const rows = document.querySelectorAll('.maint-row');
            rows.forEach(r => {
                const st = r.getAttribute('data-status') || '';
                const urg = r.getAttribute('data-urgency') || '';
                const matchSt = (maintCurrentStatus === 'all') || (st === maintCurrentStatus);
                const matchUrg = (maintCurrentUrgency === 'all') || (urg === maintCurrentUrgency);
                r.style.display = (matchSt && matchUrg) ? '' : 'none';
            });
        }

        // ===================== CLEANLINESS JS =====================
        function switchCleanView(mode) {
            const rkPane = document.getElementById('cleanViewRanking');
            const hisPane = document.getElementById('cleanViewHistory');
            const rkBtn = document.getElementById('btn-clean-ranking');
            const hisBtn = document.getElementById('btn-clean-history');

            if (mode === 'ranking') {
                rkPane.classList.remove('hidden');
                hisPane.classList.add('hidden');
                rkBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white cursor-pointer transition-all';
                hisBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            } else {
                rkPane.classList.add('hidden');
                hisPane.classList.remove('hidden');
                hisBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white cursor-pointer transition-all';
                rkBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white cursor-pointer transition-all';
            }
        }

        function openCleanlinessPrintModal() {
            document.getElementById('cleanlinessPrintModal').classList.remove('hidden');
        }

        function closeCleanlinessPrintModal() {
            document.getElementById('cleanlinessPrintModal').classList.add('hidden');
        }

        // ===================== FESTIVALS CALLS JS =====================
        function openCreateCallModal() {
            document.getElementById('createFestivalCallModal').classList.remove('hidden');
        }

        function closeCreateCallModal() {
            document.getElementById('createFestivalCallModal').classList.add('hidden');
        }

        function toggleSportsTarget(cat) {
            const box = document.getElementById('call-sports-target-box');
            if (box) {
                if (cat === 'sports') {
                    box.classList.remove('hidden');
                } else {
                    box.classList.add('hidden');
                }
            }
        }

        // الگوهای آماده شناسنامه امکانات اتاق
        function applyFacPreset(type) {
            if (type === 'standard') {
                document.getElementById('fac-fridge-model').value = 'یخچال ۶ فوت امرسان استاندارد';
                document.getElementById('fac-bed-type').value = 'تخت فلزی دو طبقه استاندارد';
                document.getElementById('fac-ac-heater').value = 'شوفاژ مرکزی + کولر آبی پردیس';
                document.getElementById('fac-tv-model').value = '';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = false;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            } else if (type === 'elite') {
                document.getElementById('fac-fridge-model').value = 'یخچال ۹ فوت دونار بزرگ';
                document.getElementById('fac-bed-type').value = 'تخت دو طبقه فلزی تقویت‌شده نخبگان';
                document.getElementById('fac-ac-heater').value = 'اسپلیت سرمایشی/گرمایشی ۱۸ هزار';
                document.getElementById('fac-tv-model').value = 'تلویزیون ۳۲ اینچ LED دیواری';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = true;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            } else if (type === 'suite') {
                document.getElementById('fac-fridge-model').value = 'یخچال دوقلو ۱۲ فوت بزرگ ویژه ۱۰ نفره';
                document.getElementById('fac-bed-type').value = 'تخت‌های سفارشی با کمد تفکیکی ۱۰ درب';
                document.getElementById('fac-ac-heater').value = 'اسپلیت ۲۴ هزار ال‌جی + شوفاژ پره‌دار';
                document.getElementById('fac-tv-model').value = 'تلویزیون ۴۳ اینچ هوشمند پردیس';
                document.getElementById('fac-has-broom').checked = true;
                document.getElementById('fac-has-trash').checked = true;
                document.getElementById('fac-has-bucket').checked = true;
                document.getElementById('fac-has-carpet').checked = true;
                document.getElementById('fac-has-wardrobe').checked = true;
                document.getElementById('fac-has-desk').checked = true;
                document.getElementById('fac-has-shoerack').checked = true;
                document.getElementById('fac-has-mirror').checked = true;
            }
        }

        // الگوی آماده اقدام تعمیراتی مدیر
        function setMaintPreset(actionNotes, contractor, cost, invNum, actionType) {
            document.getElementById('mrm-action-taken').value = actionType || 'repair';
            document.getElementById('mrm-cost').value = cost || '';
            document.getElementById('mrm-inv-num').value = invNum || '';
            document.getElementById('mrm-contractor').value = contractor || '';
            document.getElementById('mrm-notes').value = actionNotes || '';
        }

        // تنظیم بازه زمانی فراخوان (دیت‌پیکر از شروع تا پایان)
        function setCallDuration(days) {
            const startInp = document.getElementById('call-start-date');
            const deadInp = document.getElementById('call-deadline-date');
            if (!deadInp) return;
            const base = (startInp && startInp.value) ? new Date(startInp.value) : new Date();
            base.setDate(base.getDate() + parseInt(days));
            deadInp.value = base.toISOString().split('T')[0];
        }

        // تنظیم سریع روزهای میهمانی و محاسبه تاریخ پایان
        function setGuestDaysValue(days) {
            const inp = document.getElementById('guest_days_inp');
            if (inp) {
                inp.value = days;
                updateGuestEndDatePreview();
            }
        }

        function updateGuestEndDatePreview() {
            const startInp = document.getElementById('guest_start_date_inp');
            const daysInp = document.getElementById('guest_days_inp');
            const prev = document.getElementById('preview-guest-end-date');
            if (!prev) return;
            const startVal = (startInp && startInp.value) ? startInp.value : new Date().toISOString().split('T')[0];
            const days = parseInt(daysInp ? daysInp.value : 7) || 7;
            const d = new Date(startVal);
            d.setDate(d.getDate() + days);
            prev.textContent = d.toISOString().split('T')[0] + ' (' + days + ' روز اقامت)';
        }

        // سوییچ زنده تم تاریک و روشن (با ذخیره در سشن و کوکی)
        function togglePortalTheme() {
            const isLight = document.body.classList.toggle('theme-light');
            document.body.classList.toggle('theme-dark', !isLight);
            const newTheme = isLight ? 'light' : 'dark';
            const icon = document.getElementById('themeToggleIcon');
            const text = document.getElementById('themeToggleText');
            if (icon) icon.textContent = isLight ? '🌙' : '☀️';
            if (text) text.textContent = isLight ? 'تم دارک' : 'تم لایت';
            try { localStorage.setItem('portal_theme', newTheme); } catch(e) {}
            fetch('?action_toggle_theme=1', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        }

        // الگوی آماده فراخوان جشنواره
        function setCallPreset(title, cat, team, desc) {
            document.getElementById('call-title-inp').value = title;
            const sel = document.getElementById('call-category-select');
            if (sel) {
                sel.value = cat;
                toggleSportsTarget(cat);
            }
            const teamSel = document.querySelector('select[name="target_sports_team"]');
            if (teamSel && team) teamSel.value = team;
            const descArea = document.querySelector('textarea[name="call_description"]');
            if (descArea) descArea.value = desc;
        }

        function toggleSidebarMobile() {
            const sb = document.getElementById('mainSidebar');
            if (sb) {
                sb.classList.toggle('hidden');
            }
        }

        // بستن هر یک از مودال‌ها با فشردن کلید Escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modalIds = [
                    'assignModal', 'editDormModal', 'batchPrintModal', 'individualPrintModal',
                    'supervisorCardModal', 'roomDetailsModal', 'studentCardModal',
                    'editSupervisorShiftModal', 'facilitiesModal', 'maintenanceResolutionModal',
                    'singleInvoiceModal', 'maintenanceInvoicePrintModal', 'cleanlinessPrintModal', 'createFestivalCallModal'
                ];
                modalIds.forEach(id => {
                    const m = document.getElementById(id);
                    if (m) m.classList.add('hidden');
                });
            }
        });
    