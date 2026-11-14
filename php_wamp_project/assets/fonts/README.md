# راهنمای جایگزینی فونت‌های محلی (`assets/fonts`)

تمامی فونت‌های استفاده‌شده در صفحات فرود (فارسی و انگلیسی)، پنل‌های کاربری و ماژول گراف خوابگاه در همین پوشه (`assets/fonts/`) متمرکز شده‌اند و از طریق فایل `fonts.css` بدون هیچ لینک خارجی بارگذاری می‌شوند.

فایل‌های `.woff2` فعلی به‌عنوان جای‌نگهدار (Placeholder) ایجاد شده‌اند تا نام دقیق مورد انتظار برنامه مشخص باشد. کافی است فایل‌های واقعی هر فونت را دانلود کرده و دقیقاً با همین نام‌ها جایگزین کنید.

---

## ۱. فونت فارسی وزیرمتن (`Vazirmatn`)
کاربرد: کل پنل‌های صمد، صفحه فرود فارسی و رابط گراف فارسی.
منبع رسمی دانلود: مخزن گیت‌هاب `rastikerdar/vazirmatn` (بخش Releases -> `fonts/webfonts/`).

| نام فایل مورد انتظار | وزن (Weight) | استایل |
|---|---|---|
| `Vazirmatn-Light.woff2` | 300 (Light) | normal |
| `Vazirmatn-Regular.woff2` | 400 (Regular) | normal |
| `Vazirmatn-Medium.woff2` | 500 (Medium) | normal |
| `Vazirmatn-SemiBold.woff2` | 600 (SemiBold) | normal |
| `Vazirmatn-Bold.woff2` | 700 (Bold) | normal |
| `Vazirmatn-ExtraBold.woff2` | 800 (ExtraBold) | normal |
| `Vazirmatn-Black.woff2` | 900 (Black) | normal |

---

## ۲. فونت تیتر انگلیسی (`Playfair Display`)
کاربرد: تیترهای صفحه فرود انگلیسی (`landing_en.php`).
منبع دانلود: Fontsource (`@fontsource/playfair-display`) یا Google Webfonts Helper.

| نام فایل مورد انتظار | وزن (Weight) | استایل |
|---|---|---|
| `PlayfairDisplay-Medium.woff2` | 500 | normal |
| `PlayfairDisplay-SemiBold.woff2` | 600 | normal |
| `PlayfairDisplay-SemiBoldItalic.woff2` | 600 | italic |
| `PlayfairDisplay-Bold.woff2` | 700 | normal |

---

## ۳. فونت متن انگلیسی (`Inter`)
کاربرد: متون بدنه و برچسب‌های صفحه فرود انگلیسی (`landing_en.php`).
منبع دانلود: مخزن رسمی `rsms/inter` یا Fontsource (`@fontsource/inter`).

| نام فایل مورد انتظار | وزن (Weight) | استایل |
|---|---|---|
| `Inter-Regular.woff2` | 400 | normal |
| `Inter-Medium.woff2` | 500 | normal |
| `Inter-SemiBold.woff2` | 600 | normal |
| `Inter-Bold.woff2` | 700 | normal |

---

## ۴. فونت ماژول گراف (`Plus Jakarta Sans` و `JetBrains Mono`)
کاربرد: نمای گراف تعاملی خوابگاه (`dorm_graph/index.html`).
منبع دانلود: Fontsource یا مخازن رسمی فونت‌ها در GitHub (`tokotype/PlusJakartaSans` و `JetBrains/JetBrainsMono`).

| نام فایل مورد انتظار | خانواده | وزن (Weight) |
|---|---|---|
| `PlusJakartaSans-Regular.woff2` | Plus Jakarta Sans | 400 |
| `PlusJakartaSans-SemiBold.woff2` | Plus Jakarta Sans | 600 |
| `PlusJakartaSans-Bold.woff2` | Plus Jakarta Sans | 700 |
| `JetBrainsMono-Regular.woff2` | JetBrains Mono | 400 |
| `JetBrainsMono-SemiBold.woff2` | JetBrains Mono | 600 |

> **نکته:** اگر فونت جدیدی با فرمت `.ttf` دارید و می‌خواهید استفاده کنید، می‌توانید آن را به `.woff2` تبدیل کنید یا در `fonts.css` فرمت فایل مربوطه را به `format('truetype')` تغییر دهید.
