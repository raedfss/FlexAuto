<?php
// ملف الاتصال المخصص لـ mysqli (في صفحات تستخدم mysqli بدلاً من PDO)
$is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

if ($is_localhost) {
    // الاتصال بـ MySQL
    $conn = new mysqli("localhost", "root", "", "flexauto");
} else {
    // الاتصال بـ PostgreSQL عبر Railway غير مدعوم بـ mysqli
    // (هذا الملف خاص بـ localhost فقط)
    die("❌ خطأ: هذه الصفحة مصممة فقط للعمل على localhost باستخدام MySQL.");
}

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
