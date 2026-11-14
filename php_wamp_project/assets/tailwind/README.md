# راهنمای نصب و کامپایل محلی Tailwind CSS (بدون CDN)

تمامی صفحات PHP پروژه به فایل خروجی کامپایل‌شده `assets/tailwind/tailwind.css` متصل شده‌اند و هیچ ارتباطی با `cdn.tailwindcss.com` وجود ندارد.
نسخه اولیه `tailwind.css` از قبل ساخته شده و آماده است. هر زمان کلاس جدیدی به فایل‌های PHP اضافه کردید، می‌توانید به یکی از دو روش زیر دوباره فایل CSS را تولید کنید:

---

## روش اول: استفاده از npm و Tailwind CLI (پیشنهادی برای توسعه‌دهندگان)

پیش‌نیاز: نصب بودن [Node.js](https://nodejs.org/) روی سیستم.

۱. ترمینال یا CMD را در همین پوشه (`php_wamp_project/assets/tailwind`) باز کنید:
```bash
cd php_wamp_project/assets/tailwind
```

۲. پکیج‌های Tailwind CLI را فقط یک بار نصب کنید:
```bash
npm install
```

۳. برای **ساخت یک‌باره** فایل نهایی فشرده (`tailwind.css`):
```bash
npm run build
```

۴. یا برای **کامپایل خودکار هنگام ویرایش فایل‌های PHP** (حالت Watch):
```bash
npm run watch
```

---

## روش دوم: استفاده از فایل اجرایی مستقل Tailwind (بدون نیاز به Node.js و npm)

اگر نمی‌خواهید Node.js نصب کنید:

۱. فایل اجرایی `tailwindcss-windows-x64.exe` (برای ویندوز/WAMP) یا نسخه لینوکس/مک را از صفحه رسمی انتشارهای Tailwind در گیت‌هاب دانلود کنید:
   `tailwindlabs/tailwindcss/releases`
۲. نام فایل دانلودشده را به `tailwindcss.exe` تغییر دهید و در همین پوشه (`assets/tailwind/`) قرار دهید.
۳. در CMD همین پوشه دستور زیر را اجرا کنید:
```cmd
tailwindcss.exe -i input.css -o tailwind.css --minify
```
یا برای حالت تماشای خودکار تغییرات:
```cmd
tailwindcss.exe -i input.css -o tailwind.css --watch
```

---

## ساختار فایل‌های این پوشه
- `input.css`: فایل منبع شامل `@import "tailwindcss";`، مسیرهای `@source` برای اسکن فایل‌های PHP، توکن‌های رنگی `@theme` و کلاس‌های تایپوگرافی صفحات فرود.
- `package.json`: تنظیمات و اسکریپت‌های `build` و `watch`.
- `tailwind.css`: فایل نهایی و کامپایل‌شده‌ای که در `<head>` تمام صفحات PHP لینک شده است.
