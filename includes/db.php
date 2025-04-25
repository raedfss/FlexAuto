<?php
/**
 * FlexAuto - ملف الاتصال الموحد بقاعدة البيانات
 * يدعم الاتصال بـ MySQL (localhost) و PostgreSQL (Railway)
 */

$is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

// إعداد الاتصال حسب البيئة:
if ($is_localhost) {
    // 🧪 بيئة محلية - MySQL (XAMPP)
    $db_type = 'mysql';
    $host = 'localhost';
    $db_name = 'flexauto';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';
    $dsn = "$db_type:host=$host;dbname=$db_name;charset=$charset";
} else {
    // 🚀 بيئة Railway - PostgreSQL
    $db_type = 'pgsql';
    $host = 'monorail.proxy.rlwy.net';
    $db_name = 'railway';
    $username = 'postgres';
    $password = 'qPDuGhAJpcnSsGanToKibGYbhGSAvyat';
    $port = '5432';
    $dsn = "$db_type:host=$host;port=$port;dbname=$db_name;user=$username;password=$password";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>
