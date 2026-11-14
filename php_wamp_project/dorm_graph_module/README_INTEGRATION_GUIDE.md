# راهنمای جامع افزودن و استفاده از ماژول نقشه تعاملی خوابگاه (Dormitory Graph) در پروژه PHP

این پوشه (`dorm_graph_module`) شامل کلیه فایل‌های مستقل و مورد نیاز برای پیاده‌سازی و اجرای **ماژول نقشه تعاملی خوابگاه دانشجویان (Dormitory Graph)** مطابق با سند استاندارد فنی `Dormitory_Graph_Module_SRS.md` می‌باشد.

---

## ۱. فهرست فایل‌های موجود در این بسته

| نام فایل | نقش و کاربرد |
| :--- | :--- |
| `get_dorm_graph.php` | وب‌سرویس و اندپوینت استاندارد API با خروجی JSON DTO (مطابق بخش ۱۱ و ۱۲ سند SRS) |
| `dorm_graph_view.php` | کامپوننت ویژوال گراف با بوم تیره، خطوط منحنی بزیر، زوم، فیت و پنل کشویی جزئیات (قابل include یا اجرای مستقل) |
| `api_test_client.html` | کلاینت تحت وب برای تست مستقیم وب‌سرویس و مشاهده نودها و یال‌ها در مرورگر |
| `README_INTEGRATION_GUIDE.md` | همین سند راهنما |

---

## ۲. نحوه انتقال و اضافه کردن فایل‌ها به پروژه PHP / WampServer

### روش الف: قرار دادن در مسیر اصلی پروژه (توصیه‌شده)
پوشه `dorm_graph_module` را کپی کرده و مستقیماً داخل ریشه پروژه خود (مثلاً `www/samad_project/` یا `htdocs/samad_project/`) قرار دهید:

```text
c:/wamp64/www/samad_project/
├── db.php
├── dashboard.php
├── dorm_graph_module/
│   ├── get_dorm_graph.php
│   ├── dorm_graph_view.php
│   ├── api_test_client.html
│   └── README_INTEGRATION_GUIDE.md
```

---

## ۳. سه روش برای استفاده و نمایش ماژول در صفحات PHP

### روش ۱: فراخوانی مستقیم در پیشخوان دانشجو (`dashboard.php`)
در هر کجای فایل پیشخوان دانشجو یا تب اسکان خوابگاه، می‌توانید کامپوننت گراف را تنها با یک خط کد include کنید:

```php
<!-- تب وضعیت خوابگاه -->
<div id="tab-dorm-graph">
    <?php include __DIR__ . '/dorm_graph_module/dorm_graph_view.php'; ?>
</div>
```
کامپوننت به صورت خودکار اطلاعات نشست دانشجوی لاگین‌شده (`$_SESSION['student_id']`) را خوانده و گراف مربوط به همان دانشجو و اتاقش را با تمام امکانات تعاملی رسم می‌کند.

---

### روش ۲: باز شدن به عنوان صفحه اختصاصی مجزا (Standalone)
دانشجو یا مدیر می‌تواند مستقیماً آدرس زیر را در مرورگر باز کند:
```
http://localhost/samad_project/dorm_graph_module/dorm_graph_view.php
```
فایل به صورت خودکار تشخیص می‌دهد که به شکل صفحه مستقل باز شده و هدر و قالب کامل را با دکمه «بازگشت به پیشخوان» بارگذاری می‌کند.

---

### روش ۳: اتصال از طریق جاوااسکریپت و وب‌سرویس (AJAX / Fetch API)
اگر تمایل دارید گراف را در فریم‌ورک‌های Frontend (مانند React، Vue یا کتابخانه‌های گراف مانند Cytoscape/SeeNodes) لود کنید، کافیست از اندپوینت `get_dorm_graph.php` خروجی JSON بگیرید:

```javascript
fetch('dorm_graph_module/get_dorm_graph.php')
  .then(response => response.json())
  .then(dto => {
    console.log("وضعیت:", dto.status); // active
    console.log("نودهای گراف:", dto.data.nodes);
    console.log("یال‌های ارتباطی:", dto.data.edges);
  });
```

---

## ۴. ساختار پایگاه‌داده مورد نیاز (Database Schema)

برای عملکرد صحیح ماژول، جداول زیر در دیتابیس `samad_db` استفاده می‌شوند (که از پیش در فایل `database.sql` پروژه تعریف شده‌اند):

1. **`students`**: مشخصات دانشجو، نام، شماره دانشجویی، رشته تحصیلی.
2. **`dorm_rooms`**: اتاق‌های خوابگاه، شماره اتاق (`room_number`)، طبقه (`floor`)، ظرفیت (`capacity`) و وضعیت دایر/غیرفعال.
3. **`dorm_allocations`**: تخصیص‌های فعال دانشجویان به اتاق‌ها (`room_id`, `student_id`, `is_active=1`, `dorm_status`).
4. **`room_facilities`**: شناسنامه تجهیزات اتاق (مدل یخچال، نوع تخت، کمد، سرمایش/گرمایش).

---

## ۵. تست و اطمینان از عملکرد صحیح

1. در مرورگر وارد حساب یک دانشجو شوید (مثلاً شماره دانشجویی `4012130045` با رمز عبور `Samad@2026Secure!`).
2. صفحه تست `api_test_client.html` را باز کنید:
   ```
   http://localhost/samad_project/dorm_graph_module/api_test_client.html
   ```
3. دکمه «ارسال درخواست (Fetch API)» را کلیک کنید؛ پاسخ سرور به همراه تعداد نودها و یال‌ها نمایش داده خواهد شد.
