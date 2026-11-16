<?php
/**
 * API: دریافت گراف تعاملی ساختار خوابگاه (Dormitory Graph DTO)
 * سازگار کامل با ماژول React (Dormitory Graph) و پایگاه‌داده MySQL سامانه صمد
 *
 * اکشن‌های پشتیبانی‌شده:
 *  - ?action=me                      -> گراف اسکان دانشجوی احراز هویت‌شده (همراه با هم‌اتاقی‌ها)
 *  - ?action=admin&floor=all|...     -> گراف جامع کل ساختمان، طبقات، اتاق‌ها و همه ساکنان برای مدیر
 *  - ?action=room_detail&room_id=... -> جزئیات مجاز اتاق + شناسنامه امکانات
 *  - ?action=student_detail&student_id=... -> جزئیات مجاز دانشجو با رعایت حریم خصوصی
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt_auth.php';

function send_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function derive_entry_year(string $studentId): ?int
{
    if (preg_match('/^40([0-9])/', $studentId, $m)) {
        return 1400 + (int)$m[1];
    }
    return null;
}

function get_avatar_initials(string $fullName): string
{
    $clean = trim(preg_replace('/\(.*?\)/u', '', $fullName));
    $parts = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts || count($parts) === 0) {
        return 'د';
    }
    $first = mb_substr($parts[0], 0, 1, 'UTF-8');
    $second = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
    return trim($first . ' ' . $second);
}

function floor_number_from_name(string $floorName): int
{
    switch (trim($floorName)) {
        case 'همکف':
            return 0;
        case 'طبقه اول':
            return 1;
        case 'طبقه دوم':
            return 2;
        case 'طبقه سوم':
            return 3;
        default:
            return 9;
    }
}

$action = trim($_GET['action'] ?? '');

if ($action === '') {
    $requestedRole = trim($_GET['role'] ?? '');
    $jwtPreview = samad_verify_jwt($pdo, null, false);
    if ($requestedRole === 'manager' || ($jwtPreview['valid'] && $jwtPreview['role'] === 'manager')) {
        $action = 'admin';
    } else {
        $action = 'me';
    }
}

$DORMITORY_ID = 'dorm-samad';
$DORMITORY_NAME = 'خوابگاه دانشجویی صمد';
$CAMPUS_ZONE = 'پردیس پسران دانشگاه ملی مهارت نیشابور';
$BUILDING_ID = 'bldg-samad-01';
$BUILDING_NAME = 'ساختمان اصلی خوابگاه صمد';
$BUILDING_CODE = 'SMD-01';
$ACADEMIC_SEMESTER = 'نیم‌سال تحصیلی جاری';

try {
    // =========================================================================
    // 1. MANAGER FULL BUILDING / FLOOR / ROOM / STUDENTS GRAPH (?action=admin)
    // =========================================================================
    if ($action === 'admin') {
        $jwtAuth = samad_require_api_jwt($pdo, ['manager'], 'dorm:graph:view_all');

        $floorFilter = trim($_GET['floor'] ?? 'all');
        if ($floorFilter === '') {
            $floorFilter = 'all';
        }

        // دریافت کل اتاق‌های خوابگاه برای محاسبه آمار کل و فیلتر طبقات
        $stmtAllRooms = $pdo->query("
            SELECT * FROM `dorm_rooms`
            ORDER BY FIELD(`floor`, 'همکف', 'طبقه اول', 'طبقه دوم'), `floor` ASC, `room_number` ASC
        ");
        $allRooms = $stmtAllRooms->fetchAll(PDO::FETCH_ASSOC);

        // دریافت تمام ساکنان فعال در همه اتاق‌ها (بدون حذف یا محدودیت یک دانشجو)
        $stmtAllAllocs = $pdo->query("
            SELECT da.id AS alloc_id, da.room_id, da.student_id, da.dorm_status, da.allocation_date,
                   da.guest_days_total, da.guest_days_remaining,
                   s.full_name, s.field_of_study, s.study_shift, s.campus
            FROM `dorm_allocations` da
            JOIN `students` s ON da.student_id = s.student_id
            WHERE da.is_active = 1
            ORDER BY da.room_id ASC, da.id ASC
        ");
        $allAllocs = $stmtAllAllocs->fetchAll(PDO::FETCH_ASSOC);

        $residentsByRoom = [];
        foreach ($allAllocs as $alloc) {
            $rId = (int)$alloc['room_id'];
            if (!isset($residentsByRoom[$rId])) {
                $residentsByRoom[$rId] = [];
            }
            $residentsByRoom[$rId][] = $alloc;
        }

        // محاسبه آمار طبقات برای نوار فیلتر طبقه به طبقه
        $roomsByFloorAll = [];
        $totalCapacity = 0;
        foreach ($allRooms as $rm) {
            $fl = $rm['floor'];
            if (!isset($roomsByFloorAll[$fl])) {
                $roomsByFloorAll[$fl] = [];
            }
            $roomsByFloorAll[$fl][] = $rm;
            $totalCapacity += (int)$rm['capacity'];
        }

        $availableFloors = [];
        foreach ($roomsByFloorAll as $flName => $flRooms) {
            $flCap = 0;
            $flOcc = 0;
            foreach ($flRooms as $rm) {
                $flCap += (int)$rm['capacity'];
                $flOcc += count($residentsByRoom[(int)$rm['id']] ?? []);
            }
            $availableFloors[] = [
                'id' => $flName,
                'title' => $flName,
                'buildingName' => $BUILDING_NAME,
                'floorNumber' => floor_number_from_name($flName),
                'roomsCount' => count($flRooms),
                'totalCapacity' => $flCap,
                'activeOccupancy' => $flOcc,
            ];
        }

        // فیلتر اتاق‌ها بر اساس طبقه انتخاب‌شده
        $targetRooms = [];
        if ($floorFilter !== 'all') {
            foreach ($allRooms as $rm) {
                if ($rm['floor'] === $floorFilter) {
                    $targetRooms[] = $rm;
                }
            }
        } else {
            $targetRooms = $allRooms;
        }

        // چیدمان سلسله‌مراتبی بدون هیچ‌گونه تداخل (NODE_WIDTH = 220 + CLEARANCE >= 20 -> gap >= 264)
        $STUDENT_GAP = 264;
        $INTER_ROOM_GAP = 64;
        $INTER_FLOOR_GAP = 100;
        $BASE_CENTER_X = 560;

        $roomsByFloorTarget = [];
        foreach ($targetRooms as $rm) {
            $fl = $rm['floor'];
            if (!isset($roomsByFloorTarget[$fl])) {
                $roomsByFloorTarget[$fl] = [];
            }
            $roomsByFloorTarget[$fl][] = $rm;
        }

        // محاسبه عرض هر اتاق و مجموع عرض کل بر اساس طبقات
        $roomSlots = [];
        $floorSpans = [];
        $totalSpan = 0;
        $floorKeys = array_keys($roomsByFloorTarget);

        foreach ($floorKeys as $fIdx => $flName) {
            $flRooms = $roomsByFloorTarget[$flName];
            $flWidth = 0;
            foreach ($flRooms as $rIdx => $rm) {
                $rId = (int)$rm['id'];
                $resCount = count($residentsByRoom[$rId] ?? []);
                $slotW = max(280, $resCount * $STUDENT_GAP);
                $roomSlots[$rId] = $slotW;
                $flWidth += $slotW;
                if ($rIdx < count($flRooms) - 1) {
                    $flWidth += $INTER_ROOM_GAP;
                }
            }
            $floorSpans[$flName] = $flWidth;
            $totalSpan += $flWidth;
            if ($fIdx < count($floorKeys) - 1) {
                $totalSpan += $INTER_FLOOR_GAP;
            }
        }

        $cursorX = $BASE_CENTER_X - ($totalSpan / 2);
        $roomCenterX = [];
        $floorCenterX = [];

        foreach ($floorKeys as $fIdx => $flName) {
            $flRooms = $roomsByFloorTarget[$flName];
            $flStartX = $cursorX;
            foreach ($flRooms as $rIdx => $rm) {
                $rId = (int)$rm['id'];
                $slotW = $roomSlots[$rId];
                $roomCenterX[$rId] = (int)round($cursorX + ($slotW / 2));
                $cursorX += $slotW;
                if ($rIdx < count($flRooms) - 1) {
                    $cursorX += $INTER_ROOM_GAP;
                }
            }
            $flEndX = $cursorX;
            $floorCenterX[$flName] = (int)round(($flStartX + $flEndX) / 2);
            if ($fIdx < count($floorKeys) - 1) {
                $cursorX += $INTER_FLOOR_GAP;
            }
        }

        $nodes = [];
        $edges = [];

        $dormNodeId = "node-dorm-{$DORMITORY_ID}";
        $bldgNodeId = "node-bldg-{$BUILDING_ID}";

        // ۱. گره مجتمع خوابگاهی
        $nodes[] = [
            'id' => $dormNodeId,
            'entityType' => 'dormitory',
            'entityId' => $DORMITORY_ID,
            'label' => $DORMITORY_NAME,
            'subtitle' => $CAMPUS_ZONE . ' · نظارت مدیریت',
            'position' => ['x' => $BASE_CENTER_X, 'y' => 50],
            'metadata' => [
                'dormitoryName' => $DORMITORY_NAME,
                'isManagerView' => true,
            ],
        ];

        // ۲. گره ساختمان
        $nodes[] = [
            'id' => $bldgNodeId,
            'entityType' => 'building',
            'entityId' => $BUILDING_ID,
            'label' => $BUILDING_NAME,
            'subtitle' => count($availableFloors) . ' طبقه · کد ' . $BUILDING_CODE,
            'position' => ['x' => $BASE_CENTER_X, 'y' => 210],
            'metadata' => [
                'dormitoryName' => $DORMITORY_NAME,
                'buildingName' => $BUILDING_NAME,
                'buildingCode' => $BUILDING_CODE,
                'totalFloors' => count($availableFloors),
                'isManagerView' => true,
            ],
        ];

        $edges[] = [
            'id' => "edge-{$DORMITORY_ID}-{$BUILDING_ID}",
            'source' => $dormNodeId,
            'target' => $bldgNodeId,
            'relationType' => 'contains_building',
            'label' => 'شامل ساختمان',
        ];

        // ۳. گره‌های طبقات، اتاق‌ها و تمام دانشجویان
        foreach ($roomsByFloorTarget as $flName => $flRooms) {
            $flSlug = md5($flName);
            $flNodeId = "node-flr-{$flSlug}";
            $flX = $floorCenterX[$flName] ?? $BASE_CENTER_X;

            $flOcc = 0;
            $flCap = 0;
            foreach ($flRooms as $rm) {
                $flCap += (int)$rm['capacity'];
                $flOcc += count($residentsByRoom[(int)$rm['id']] ?? []);
            }

            $nodes[] = [
                'id' => $flNodeId,
                'entityType' => 'floor',
                'entityId' => $flName,
                'label' => $flName,
                'subtitle' => count($flRooms) . ' اتاق · ' . $flOcc . ' از ' . $flCap . ' ساکن',
                'position' => ['x' => $flX, 'y' => 380],
                'metadata' => [
                    'buildingName' => $BUILDING_NAME,
                    'floorName' => $flName,
                    'floorNumber' => floor_number_from_name($flName),
                    'visibleRoomsCount' => count($flRooms),
                    'isManagerView' => true,
                ],
            ];

            $edges[] = [
                'id' => "edge-bldg-flr-{$flSlug}",
                'source' => $bldgNodeId,
                'target' => $flNodeId,
                'relationType' => 'contains_floor',
                'label' => 'شامل طبقه',
            ];

            foreach ($flRooms as $rm) {
                $rId = (int)$rm['id'];
                $rNodeId = "node-room-{$rId}";
                $rX = $roomCenterX[$rId] ?? $flX;
                $stus = $residentsByRoom[$rId] ?? [];
                $occ = count($stus);
                $cap = (int)$rm['capacity'];
                $vacant = max(0, $cap - $occ);
                $isActiveRoom = ((int)$rm['is_active']) === 1;

                $nodes[] = [
                    'id' => $rNodeId,
                    'entityType' => 'room',
                    'entityId' => (string)$rId,
                    'label' => 'اتاق ' . $rm['room_number'],
                    'subtitle' => $isActiveRoom
                        ? "ساکنان: {$occ} از {$cap} نفر ({$vacant} خالی)"
                        : "غیرفعال / تعمیرات · ظرفیت {$cap} نفر",
                    'position' => ['x' => $rX, 'y' => 550],
                    'metadata' => [
                        'dormitoryName' => $DORMITORY_NAME,
                        'buildingName' => $BUILDING_NAME,
                        'floorName' => $flName,
                        'floorNumber' => floor_number_from_name($flName),
                        'roomNumber' => (string)$rm['room_number'],
                        'capacity' => $cap,
                        'activeOccupancy' => $occ,
                        'vacantBeds' => $vacant,
                        'isRoomActive' => $isActiveRoom,
                        'academicSemester' => $ACADEMIC_SEMESTER,
                        'isManagerView' => true,
                    ],
                ];

                $edges[] = [
                    'id' => "edge-flr-room-{$flSlug}-{$rId}",
                    'source' => $flNodeId,
                    'target' => $rNodeId,
                    'relationType' => 'contains_room',
                    'label' => 'شامل اتاق',
                ];

                // توزیع متقارن و مرتب تمام دانشجویان زیر اتاق بدون هیچ هم‌پوشانی
                $stuStartX = $rX + (($occ - 1) * $STUDENT_GAP) / 2;
                foreach ($stus as $idx => $stu) {
                    $sId = (string)$stu['student_id'];
                    $sNodeId = "node-stu-{$sId}";
                    $sX = (int)round($stuStartX - ($idx * $STUDENT_GAP));
                    $entryYear = derive_entry_year($sId);

                    $nodes[] = [
                        'id' => $sNodeId,
                        'entityType' => 'student',
                        'entityId' => $sId,
                        'label' => $stu['full_name'],
                        'subtitle' => "{$stu['field_of_study']} · {$stu['dorm_status']}",
                        'isCurrentStudent' => false,
                        'position' => ['x' => $sX, 'y' => 740],
                        'metadata' => [
                            'major' => $stu['field_of_study'],
                            'faculty' => $stu['campus'],
                            'entryYear' => $entryYear,
                            'avatarInitials' => get_avatar_initials($stu['full_name']),
                            'tuitionStatus' => $stu['dorm_status'],
                            'studyShift' => $stu['study_shift'],
                            'guestDaysRemaining' => $stu['guest_days_remaining'] !== null ? (int)$stu['guest_days_remaining'] : null,
                            'roomNumber' => (string)$rm['room_number'],
                            'floorName' => $flName,
                            'academicSemester' => $ACADEMIC_SEMESTER,
                            'isManagerView' => true,
                        ],
                    ];

                    $edges[] = [
                        'id' => "edge-room-stu-{$rId}-{$sId}",
                        'source' => $rNodeId,
                        'target' => $sNodeId,
                        'relationType' => 'has_resident',
                        'label' => 'دارای ساکن',
                    ];
                }
            }
        }

        $totalResidents = count($allAllocs);
        $vacantBeds = max(0, $totalCapacity - $totalResidents);
        $managerName = $jwtAuth['name'] ?: ($_SESSION['admin_name'] ?? 'مدیریت امور اسکان و رفاه خوابگاه‌ها');

        send_json(200, [
            'apiVersion' => 'v1',
            'module' => 'dormitory',
            'status' => 'active',
            'message' => $floorFilter !== 'all'
                ? "نمای تفکیکی {$floorFilter} با موفقیت از پایگاه‌داده بارگذاری شد."
                : 'نمای مانیتورینگ جامع کل خوابگاه، طبقات، اتاق‌ها و ساکنان با موفقیت بارگذاری شد.',
            'currentStudent' => [
                'studentId' => (string)$jwtAuth['sub'],
                'displayName' => $managerName,
                'faculty' => 'مدیریت امور دانشجویی و خوابگاه‌ها',
                'major' => 'نظارت جامع پردیس',
                'entryYear' => 1403,
                'academicSemester' => $ACADEMIC_SEMESTER,
            ],
            'currentStudentNodeId' => null,
            'nodes' => $nodes,
            'edges' => $edges,
            'layout' => [
                'type' => 'deterministic-hierarchical',
                'version' => 1,
            ],
            'generatedAt' => date('c'),
            'role' => 'manager',
            'authMeta' => [
                'scheme' => 'JWT-HS256',
                'jtiPrefix' => substr((string)$jwtAuth['jti'], 0, 10),
                'expiresAt' => (int)$jwtAuth['exp'],
                'permissions' => $jwtAuth['permissions'],
            ],
            'managerStats' => [
                'totalBuildings' => 1,
                'totalFloors' => count($availableFloors),
                'totalRooms' => count($allRooms),
                'totalCapacity' => $totalCapacity,
                'totalResidents' => $totalResidents,
                'vacantBeds' => $vacantBeds,
                'availableFloors' => $availableFloors,
                'selectedFloorId' => $floorFilter,
            ],
        ]);
    }

    // =========================================================================
    // 2. ROOM DETAIL ENDPOINT (?action=room_detail&room_id=...)
    // =========================================================================
    if ($action === 'room_detail') {
        $jwtAuth = samad_require_api_jwt($pdo, ['manager', 'student']);
        $isManagerJwt = ($jwtAuth['role'] === 'manager');
        $hasStudentRoomOrGraph = samad_has_permission($jwtAuth, 'dorm:room:view_self') || samad_has_permission($jwtAuth, 'dorm:graph:view_self');
        if (($isManagerJwt && !samad_has_permission($jwtAuth, 'dorm:graph:view_all')) || (!$isManagerJwt && !$hasStudentRoomOrGraph)) {
            $missingPerm = $isManagerJwt ? 'dorm:graph:view_all' : 'dorm:graph:view_self';
            samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], $jwtAuth['role'], $jwtAuth['jti'], 'get_dorm_graph.php?action=room_detail', 403, "فقدان مجوز {$missingPerm}");
            send_json(403, [
                'errorCode' => 'FORBIDDEN_PERMISSION',
                'message' => 'مجوز مشاهده جزئیات اتاق برای توکن شما فعال نیست.',
            ]);
        }

        $rawRoomId = trim($_GET['room_id'] ?? '');
        $roomId = (int)preg_replace('/^node-room-/', '', $rawRoomId);
        if ($roomId <= 0) {
            send_json(400, [
                'errorCode' => 'INVALID_ROOM_ID',
                'message' => 'شناسه اتاق نامعتبر است.',
            ]);
        }

        $stmtRoom = $pdo->prepare("SELECT * FROM `dorm_rooms` WHERE `id` = ? LIMIT 1");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            send_json(404, [
                'errorCode' => 'ROOM_NOT_FOUND',
                'message' => 'اتاق موردنظر یافت نشد.',
            ]);
        }

        // کنترل دسترسی برای دانشجو: فقط اتاق فعلی خودش مجاز است
        $currentStudentId = !$isManagerJwt ? (string)$jwtAuth['sub'] : '';
        if (!$isManagerJwt) {
            $stmtChk = $pdo->prepare("
                SELECT COUNT(*) FROM `dorm_allocations`
                WHERE `student_id` = ? AND `room_id` = ? AND `is_active` = 1
            ");
            $stmtChk->execute([$currentStudentId, $roomId]);
            if ((int)$stmtChk->fetchColumn() === 0) {
                samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], $jwtAuth['role'], $jwtAuth['jti'], 'get_dorm_graph.php?action=room_detail', 403, "تلاش غیرمجاز مشاهده اتاق {$roomId}");
                send_json(403, [
                    'errorCode' => 'FORBIDDEN_ROOM_ACCESS',
                    'message' => 'شما مجوز مشاهده جزئیات این اتاق را ندارید.',
                ]);
            }
        }

        $stmtFac = $pdo->prepare("SELECT * FROM `room_facilities` WHERE `room_id` = ? LIMIT 1");
        $stmtFac->execute([$roomId]);
        $facRow = $stmtFac->fetch(PDO::FETCH_ASSOC);

        $secondaryItems = [];
        if ($facRow && !empty($facRow['secondary_facilities'])) {
            $decoded = json_decode($facRow['secondary_facilities'], true);
            if (is_array($decoded)) {
                $secondaryItems = $decoded;
            }
        }

        $stmtResidents = $pdo->prepare("
            SELECT da.student_id, da.dorm_status, s.full_name, s.field_of_study, s.study_shift
            FROM `dorm_allocations` da
            JOIN `students` s ON da.student_id = s.student_id
            WHERE da.room_id = ? AND da.is_active = 1
            ORDER BY da.id ASC
        ");
        $stmtResidents->execute([$roomId]);
        $resRows = $stmtResidents->fetchAll(PDO::FETCH_ASSOC);

        $residentsDto = [];
        foreach ($resRows as $r) {
            $sId = (string)$r['student_id'];
            $isMe = ($currentStudentId !== '' && $sId === $currentStudentId);
            $residentsDto[] = [
                'studentId' => $sId,
                'nodeId' => "node-stu-{$sId}",
                'displayName' => $r['full_name'],
                'major' => $r['field_of_study'],
                'entryYear' => derive_entry_year($sId),
                'isCurrentStudent' => $isMe,
                'avatarInitials' => get_avatar_initials($r['full_name']),
                'studyShift' => $r['study_shift'],
                'dormStatus' => $r['dorm_status'],
            ];
        }

        send_json(200, [
            'roomId' => (string)$room['id'],
            'roomNumber' => (string)$room['room_number'],
            'floorLabel' => (string)$room['floor'],
            'floorNumber' => floor_number_from_name((string)$room['floor']),
            'buildingName' => $BUILDING_NAME,
            'dormitoryName' => $DORMITORY_NAME,
            'capacity' => (int)$room['capacity'],
            'activeOccupancy' => count($residentsDto),
            'academicSemester' => $ACADEMIC_SEMESTER,
            'isActive' => ((int)$room['is_active']) === 1,
            'description' => $room['description'] ?? null,
            'facilities' => $facRow ? [
                'refrigerator' => $facRow['refrigerator'] ?? null,
                'wardrobe' => $facRow['wardrobe'] ?? null,
                'carpet' => $facRow['carpet'] ?? null,
                'secondary' => $secondaryItems,
                'status' => $facRow['status'] ?? null,
            ] : null,
            'residents' => $residentsDto,
        ]);
    }

    // =========================================================================
    // 3. STUDENT DETAIL ENDPOINT (?action=student_detail&student_id=...)
    // =========================================================================
    if ($action === 'student_detail') {
        $jwtAuth = samad_require_api_jwt($pdo, ['manager', 'student']);
        $isManagerJwt = ($jwtAuth['role'] === 'manager');
        $hasStudentRoomOrGraph = samad_has_permission($jwtAuth, 'dorm:room:view_self') || samad_has_permission($jwtAuth, 'dorm:graph:view_self');
        if (($isManagerJwt && !samad_has_permission($jwtAuth, 'dorm:graph:view_all')) || (!$isManagerJwt && !$hasStudentRoomOrGraph)) {
            $missingPerm = $isManagerJwt ? 'dorm:graph:view_all' : 'dorm:graph:view_self';
            samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], $jwtAuth['role'], $jwtAuth['jti'], 'get_dorm_graph.php?action=student_detail', 403, "فقدان مجوز {$missingPerm}");
            send_json(403, [
                'errorCode' => 'FORBIDDEN_PERMISSION',
                'message' => 'مجوز مشاهده اطلاعات دانشجو برای توکن شما فعال نیست.',
            ]);
        }

        $targetStudentId = trim($_GET['student_id'] ?? '');
        $targetStudentId = preg_replace('/^node-stu-/', '', $targetStudentId);
        if ($targetStudentId === '') {
            send_json(400, [
                'errorCode' => 'INVALID_STUDENT_ID',
                'message' => 'شناسه دانشجو نامعتبر است.',
            ]);
        }

        $stmtTarget = $pdo->prepare("
            SELECT s.student_id, s.full_name, s.campus, s.field_of_study, s.study_shift,
                   da.room_id, da.dorm_status, da.guest_days_remaining,
                   r.room_number, r.floor
            FROM `students` s
            JOIN `dorm_allocations` da ON s.student_id = da.student_id AND da.is_active = 1
            JOIN `dorm_rooms` r ON da.room_id = r.id
            WHERE s.student_id = ?
            LIMIT 1
        ");
        $stmtTarget->execute([$targetStudentId]);
        $target = $stmtTarget->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            send_json(404, [
                'errorCode' => 'STUDENT_NOT_FOUND',
                'message' => 'دانشجوی موردنظر دارای اسکان فعال نیست.',
            ]);
        }

        $currentStudentId = !$isManagerJwt ? (string)$jwtAuth['sub'] : '';
        if (!$isManagerJwt) {
            $stmtMyRoom = $pdo->prepare("
                SELECT `room_id` FROM `dorm_allocations`
                WHERE `student_id` = ? AND `is_active` = 1
                LIMIT 1
            ");
            $stmtMyRoom->execute([$currentStudentId]);
            $myRoomId = $stmtMyRoom->fetchColumn();

            if (!$myRoomId || (int)$myRoomId !== (int)$target['room_id']) {
                samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], $jwtAuth['role'], $jwtAuth['jti'], 'get_dorm_graph.php?action=student_detail', 403, "تلاش غیرمجاز مشاهده مشخصات دانشجوی {$targetStudentId}");
                send_json(403, [
                    'errorCode' => 'FORBIDDEN_STUDENT_ACCESS',
                    'message' => 'شما فقط مجاز به مشاهده مشخصات عمومی هم‌اتاقی‌های اتاق خود هستید.',
                ]);
            }
        }

        $isMe = ($currentStudentId !== '' && $targetStudentId === $currentStudentId);
        send_json(200, [
            'studentId' => (string)$target['student_id'],
            'displayName' => $target['full_name'],
            'isCurrentStudent' => $isMe,
            'major' => $target['field_of_study'],
            'faculty' => $target['campus'],
            'entryYear' => derive_entry_year($targetStudentId),
            'avatarInitials' => get_avatar_initials($target['full_name']),
            'studyShift' => $target['study_shift'],
            'dormStatus' => $target['dorm_status'],
            'guestDaysRemaining' => $target['guest_days_remaining'] !== null ? (int)$target['guest_days_remaining'] : null,
            'roomLabel' => 'اتاق ' . $target['room_number'] . ' (' . $target['floor'] . ')',
            'buildingName' => $BUILDING_NAME,
            'dormitoryName' => $DORMITORY_NAME,
            'privacyNote' => $isManagerJwt
                ? 'نمای مدیریتی: اطلاعات هویتی حساس مانند کد ملی و آدرس منزل در لایه گراف نمایش داده نمی‌شوند.'
                : ($isMe
                    ? 'اطلاعات حساس هویتی، کد ملی و شماره تماس طبق سیاست حریم خصوصی در لایه گراف ارسال نمی‌شوند.'
                    : 'صرفاً اطلاعات عمومی مجاز هم‌اتاقی مطابق سیاست حریم خصوصی دانشگاه نمایش داده شده است.'),
        ]);
    }

    // =========================================================================
    // 4. STUDENT SELF + ROOMMATES GRAPH (?action=me)
    // =========================================================================
    $jwtAuth = samad_require_api_jwt($pdo, ['student'], 'dorm:graph:view_self');
    $currentStudentId = (string)$jwtAuth['sub'];

    // جلوگیری از جعل هویت از طریق پارامتر URL
    if (isset($_GET['studentId']) && trim((string)$_GET['studentId']) !== '' && trim((string)$_GET['studentId']) !== $currentStudentId) {
        samad_log_auth_event($pdo, 'access_denied_403', $jwtAuth['sub'], $jwtAuth['role'], $jwtAuth['jti'], 'get_dorm_graph.php?action=me', 403, 'تلاش تغییر پارامتر studentId در آدرس');
        send_json(403, [
            'errorCode' => 'FORBIDDEN_USER_OVERRIDE',
            'message' => 'درخواست مشاهده اطلاعات دانشجوی دیگر از طریق پارامتر آدرس مجاز نیست.',
        ]);
    }

    $dormGraphLimit = samad_check_student_rate_limit($pdo, $currentStudentId, 'dorm:graph:view_self');
    if (!$dormGraphLimit['allowed']) {
        samad_log_rate_limit_exceeded($pdo, $currentStudentId, $jwtAuth['jti'] ?? null, 'get_dorm_graph.php?action=me', $dormGraphLimit);
        send_json(429, [
            'errorCode' => 'RATE_LIMIT_EXCEEDED',
            'message' => $dormGraphLimit['message'],
            'rateLimit' => $dormGraphLimit,
        ]);
    }

    $stmtStudent = $pdo->prepare("
        SELECT `student_id`, `full_name`, `campus`, `field_of_study`, `study_shift`
        FROM `students`
        WHERE `student_id` = ?
        LIMIT 1
    ");
    $stmtStudent->execute([$currentStudentId]);
    $studentRow = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$studentRow) {
        send_json(404, [
            'errorCode' => 'STUDENT_NOT_FOUND',
            'message' => 'پروفایل دانشجو یافت نشد.',
        ]);
    }

    samad_record_student_action($pdo, $currentStudentId, 'dorm:graph:view_self', 'بارگذاری گراف خوابگاه');

    $currentStudentBanner = [
        'studentId' => (string)$studentRow['student_id'],
        'displayName' => $studentRow['full_name'],
        'faculty' => $studentRow['campus'],
        'major' => $studentRow['field_of_study'],
        'entryYear' => derive_entry_year($currentStudentId) ?? 1401,
        'academicSemester' => $ACADEMIC_SEMESTER,
    ];

    // واکشی تخصیص فعال دانشجو در اتاق خوابگاه
    $stmtAlloc = $pdo->prepare("
        SELECT da.id AS alloc_id, da.room_id, da.dorm_status, da.guest_days_remaining, da.guest_days_total,
               r.room_number, r.floor, r.capacity, r.is_active AS room_is_active
        FROM `dorm_allocations` da
        JOIN `dorm_rooms` r ON da.room_id = r.id
        WHERE da.student_id = ? AND da.is_active = 1
        ORDER BY da.id DESC
        LIMIT 1
    ");
    $stmtAlloc->execute([$currentStudentId]);
    $allocation = $stmtAlloc->fetch(PDO::FETCH_ASSOC);

    if (!$allocation) {
        send_json(200, [
            'apiVersion' => 'v1',
            'module' => 'dormitory',
            'status' => 'not_assigned',
            'message' => 'در حال حاضر اتاق فعالی در خوابگاه به شما تخصیص داده نشده است. لطفاً وضعیت خود را از طریق مدیر امور رفاهی پیگیری نمایید.',
            'currentStudent' => $currentStudentBanner,
            'currentStudentNodeId' => null,
            'nodes' => [],
            'edges' => [],
            'layout' => [
                'type' => 'deterministic-hierarchical',
                'version' => 1,
            ],
            'generatedAt' => date('c'),
            'role' => 'student',
        ]);
    }

    $roomId = (int)$allocation['room_id'];
    $roomNumber = (string)$allocation['room_number'];
    $floorName = (string)$allocation['floor'];
    $capacity = (int)$allocation['capacity'];
    $isRoomActive = ((int)$allocation['room_is_active']) === 1;

    // تعداد اتاق‌های همین طبقه برای نمایش در متادیتا
    $stmtFloorRooms = $pdo->prepare("SELECT COUNT(*) FROM `dorm_rooms` WHERE `floor` = ?");
    $stmtFloorRooms->execute([$floorName]);
    $floorRoomsCount = (int)$stmtFloorRooms->fetchColumn();

    // واکشی تمامی ساکنان فعال این اتاق (خود دانشجو + تمام هم‌اتاقی‌ها با حذف PII)
    $stmtResidents = $pdo->prepare("
        SELECT da.student_id, da.dorm_status, da.guest_days_remaining,
               s.full_name, s.campus, s.field_of_study, s.study_shift
        FROM `dorm_allocations` da
        JOIN `students` s ON da.student_id = s.student_id
        WHERE da.room_id = ? AND da.is_active = 1
        ORDER BY da.id ASC
    ");
    $stmtResidents->execute([$roomId]);
    $residents = $stmtResidents->fetchAll(PDO::FETCH_ASSOC);

    $CENTER_X = 560;
    $STUDENT_GAP = 272;

    $dormNodeId = "node-dorm-{$DORMITORY_ID}";
    $bldgNodeId = "node-bldg-{$BUILDING_ID}";
    $flSlug = md5($floorName);
    $flNodeId = "node-flr-{$flSlug}";
    $roomNodeId = "node-room-{$roomId}";

    $nodes = [
        [
            'id' => $dormNodeId,
            'entityType' => 'dormitory',
            'entityId' => $DORMITORY_ID,
            'label' => $DORMITORY_NAME,
            'subtitle' => $CAMPUS_ZONE,
            'position' => ['x' => $CENTER_X, 'y' => 50],
            'metadata' => [
                'dormitoryName' => $DORMITORY_NAME,
            ],
        ],
        [
            'id' => $bldgNodeId,
            'entityType' => 'building',
            'entityId' => $BUILDING_ID,
            'label' => $BUILDING_NAME,
            'subtitle' => "۳ طبقه · کد {$BUILDING_CODE}",
            'position' => ['x' => $CENTER_X, 'y' => 220],
            'metadata' => [
                'dormitoryName' => $DORMITORY_NAME,
                'buildingName' => $BUILDING_NAME,
                'buildingCode' => $BUILDING_CODE,
                'totalFloors' => 3,
            ],
        ],
        [
            'id' => $flNodeId,
            'entityType' => 'floor',
            'entityId' => $floorName,
            'label' => $floorName,
            'subtitle' => $BUILDING_NAME,
            'position' => ['x' => $CENTER_X, 'y' => 390],
            'metadata' => [
                'buildingName' => $BUILDING_NAME,
                'floorName' => $floorName,
                'floorNumber' => floor_number_from_name($floorName),
                'visibleRoomsCount' => $floorRoomsCount,
            ],
        ],
        [
            'id' => $roomNodeId,
            'entityType' => 'room',
            'entityId' => (string)$roomId,
            'label' => "اتاق {$roomNumber}",
            'subtitle' => "ساکنان فعال: " . count($residents) . " از {$capacity} نفر",
            'position' => ['x' => $CENTER_X, 'y' => 560],
            'metadata' => [
                'dormitoryName' => $DORMITORY_NAME,
                'buildingName' => $BUILDING_NAME,
                'floorName' => $floorName,
                'floorNumber' => floor_number_from_name($floorName),
                'roomNumber' => $roomNumber,
                'capacity' => $capacity,
                'activeOccupancy' => count($residents),
                'vacantBeds' => max(0, $capacity - count($residents)),
                'isRoomActive' => $isRoomActive,
                'academicSemester' => $ACADEMIC_SEMESTER,
            ],
        ],
    ];

    $edges = [
        [
            'id' => "edge-{$DORMITORY_ID}-{$BUILDING_ID}",
            'source' => $dormNodeId,
            'target' => $bldgNodeId,
            'relationType' => 'contains_building',
            'label' => 'شامل ساختمان',
        ],
        [
            'id' => "edge-{$BUILDING_ID}-{$flSlug}",
            'source' => $bldgNodeId,
            'target' => $flNodeId,
            'relationType' => 'contains_floor',
            'label' => 'شامل طبقه',
        ],
        [
            'id' => "edge-{$flSlug}-{$roomId}",
            'source' => $flNodeId,
            'target' => $roomNodeId,
            'relationType' => 'contains_room',
            'label' => 'شامل اتاق',
        ],
    ];

    $resCount = count($residents);
    $startX = $CENTER_X + (($resCount - 1) * $STUDENT_GAP) / 2;
    $currentStudentNodeId = null;

    foreach ($residents as $idx => $res) {
        $sId = (string)$res['student_id'];
        $sNodeId = "node-stu-{$sId}";
        $isMe = ($sId === $currentStudentId);
        if ($isMe) {
            $currentStudentNodeId = $sNodeId;
        }
        $posX = (int)round($startX - ($idx * $STUDENT_GAP));
        $entryYear = derive_entry_year($sId);

        $nodes[] = [
            'id' => $sNodeId,
            'entityType' => 'student',
            'entityId' => $sId,
            'label' => $res['full_name'],
            'subtitle' => $isMe
                ? "{$res['field_of_study']} · شما"
                : "{$res['field_of_study']} · {$res['dorm_status']}",
            'isCurrentStudent' => $isMe,
            'position' => ['x' => $posX, 'y' => 750],
            'metadata' => [
                'major' => $res['field_of_study'],
                'faculty' => $res['campus'],
                'entryYear' => $entryYear,
                'avatarInitials' => get_avatar_initials($res['full_name']),
                'tuitionStatus' => $res['dorm_status'],
                'studyShift' => $res['study_shift'],
                'guestDaysRemaining' => $res['guest_days_remaining'] !== null ? (int)$res['guest_days_remaining'] : null,
                'roomNumber' => $roomNumber,
                'floorName' => $floorName,
                'academicSemester' => $ACADEMIC_SEMESTER,
            ],
        ];

        $edges[] = [
            'id' => "edge-{$roomId}-{$sId}",
            'source' => $roomNodeId,
            'target' => $sNodeId,
            'relationType' => 'has_resident',
            'label' => 'دارای ساکن',
        ];
    }

    send_json(200, [
        'apiVersion' => 'v1',
        'module' => 'dormitory',
        'status' => 'active',
        'message' => 'گراف اسکان دانشجو و هم‌اتاقی‌های اتاق با موفقیت از پایگاه‌داده دریافت شد.',
        'currentStudent' => $currentStudentBanner,
        'currentStudentNodeId' => $currentStudentNodeId,
        'nodes' => $nodes,
        'edges' => $edges,
        'layout' => [
            'type' => 'deterministic-hierarchical',
            'version' => 1,
        ],
        'generatedAt' => date('c'),
        'role' => 'student',
        'authMeta' => [
            'scheme' => 'JWT-HS256',
            'jtiPrefix' => substr((string)$jwtAuth['jti'], 0, 10),
            'expiresAt' => (int)$jwtAuth['exp'],
            'permissions' => $jwtAuth['permissions'],
        ],
    ]);
} catch (Throwable $e) {
    send_json(500, [
        'errorCode' => 'SERVER_ERROR',
        'message' => 'خطا در بازیابی اطلاعات گراف خوابگاه: ' . $e->getMessage(),
    ]);
}
