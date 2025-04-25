<?php
/**
 * ملف الاتصال بقاعدة البيانات (PostgreSQL عبر Railway)
 * FlexAutoPro - نظام إدارة طلبات خدمة السيارات
 */

// بيانات الاتصال بقاعدة PostgreSQL من Railway
$host = 'monorail.proxy.rlwy.net';      // PGHOST
$db_name = 'railway';                   // PGDATABASE
$username = 'postgres';                 // PGUSER
$password = 'qPDuGhAJpcnSsGanToKibGYbhGSAvyat'; // PGPASSWORD
$port = '5432';                         // المنفذ الافتراضي PostgreSQL
$charset = 'utf8';                      // الترميز الافتراضي

// إعداد DSN لاتصال PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;user=$username;password=$password";

// إعدادات خيارات PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// محاولة الاتصال بقاعدة البيانات
try {
    $pdo = new PDO($dsn, null, null, $options);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات (PostgreSQL): " . $e->getMessage());
}
