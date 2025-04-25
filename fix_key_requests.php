<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

// التحقق من وجود معرف التذكرة ونوعها
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['type'])) {
    die("المعلمات غير صحيحة.");
}

$id = intval($_GET['id']);
$type = $_GET['type'];

// الحصول على التفاصيل الإضافية حسب نوع الطلب
$additionalDetails = [];

// تحديد الجدول والاستعلام المناسب حسب نوع الطلب
switch ($type) {
    case 'airbag':
        $stmt = $pdo->prepare("SELECT a.*, u.email, u.phone 
                               FROM airbag_requests a 
                               LEFT JOIN users u ON a.username = u.username 
                               WHERE a.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $ticket['service_type'] = 'مسح بيانات Airbag';
            $additionalDetails = [
                'نوع ECU' => $ticket['ecu_model'],
                'حالة السيارة' => $ticket['car_status']
            ];
        }
        break;
        
    case 'ecu':
        $stmt = $pdo->prepare("SELECT e.*, u.email, u.phone 
                               FROM ecu_tuning_requests e 
                               LEFT JOIN users u ON e.username = u.username 
                               WHERE e.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $ticket['service_type'] = 'تعديل برمجة ECU';
            $additionalDetails = [
                'نوع الأداة' => $ticket['tool_type'],
                'التعديلات المطلوبة' => $ticket['modifications']
            ];
        }
        break;
        
    case 'key':
        $stmt = $pdo->prepare("SELECT k.*, u.email, u.phone 
                               FROM key_requests k 
                               LEFT JOIN users u ON k.username = u.username 
                               WHERE k.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $ticket['service_type'] = 'برمجة المفتاح';
            $additionalDetails = [
                'نوع ECU' => $ticket['ecu_type'],
                'عدد المفاتيح' => $ticket['keys_count']
            ];
        }
        break;
        
    case 'diagnostic':
        $stmt = $pdo->prepare("SELECT d.*, u.email, u.phone 
                               FROM diagnostic_requests d 
                               LEFT JOIN users u ON d.username = u.username 
                               WHERE d.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $ticket['service_type'] = 'تشخيص أعطال';
            $additionalDetails = [
                'وصف المشكلة' => $ticket['issue_desc'],
                'رموز الخطأ' => $ticket['error_codes']
            ];
        }
        break;
        
    default:
        die("نوع الطلب غير معروف.");
}

if (!$ticket) {
    die("لم يتم العثور على التذكرة.");
}

// تحديث الملاحظات إذا تم إرسال نموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comments'])) {
    $newComments = trim($_POST['comments']);
    $newStatus = isset($_POST['status']) ? intval($_POST['status']) : $ticket['status'];
    
    switch ($type) {
        case 'airbag':
            $updateStmt = $pdo->prepare("UPDATE airbag_requests SET comments = ?, status = ? WHERE id = ?");
            break;
        case 'ecu':
            $updateStmt = $pdo->prepare("UPDATE ecu_tuning_requests SET comments = ?, status = ? WHERE id = ?");
            break;
        case 'key':
            $updateStmt = $pdo->prepare("UPDATE key_requests SET comments = ?, status = ? WHERE id = ?");
            break;
        case 'diagnostic':
            $updateStmt = $pdo->prepare("UPDATE diagnostic_requests SET comments = ?, status = ? WHERE id = ?");
            break;
    }
    
    $updateStmt->execute([$newComments, $newStatus, $id]);
    
    // إعادة تحميل الصفحة لعرض التحديثات
    header("Location: ticket_details.php?id=$id&type=$type&updated=1");
    exit;
}

// استخراج حالة التحديث من الرابط
$updated = isset($_GET['updated']) && $_GET['updated'] == 1;

// الحصول على اسم الخدمة حسب النوع
function getServiceIcon($type) {
    switch ($type) {
        case 'airbag':
            return 'fa-car-crash';
        case 'ecu':
            return 'fa-microchip';
        case 'key':
            return 'fa-key';
        case 'diagnostic':
            return 'fa-stethoscope';
        default:
            return 'fa-ticket-alt';
    }
}

// الحصول على لون الخدمة حسب النوع
function getServiceColor($type) {
    switch ($type) {
        case 'airbag':
            return '#8b5cf6';
        case 'ecu':
            return '#10b981';
        case 'key':
            return '#f59e0b';
        case 'diagnostic':
            return '#3b82f6';
        default:
            return '#0099ff';
    }
}

// الحصول على حالة التذكرة
function getStatusText($status) {
    switch ($status) {
        case 0:
            return 'جديدة';
        case 1:
            return 'قيد المعالجة';
        case 2:
            return 'مكتملة';
        case 3:
            return 'ملغية';
        default:
            return 'غير معروفة';
    }
}

// الحصول على لون حالة التذكرة
function getStatusColor($status) {
    switch ($status) {
        case 0:
            return '#3b82f6';
        case 1:
            return '#f59e0b';
        case 2:
            return '#10b981';
        case 3:
            return '#ef4444';
        default:
            return '#94a3b8';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل التذكرة | FlexAuto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0099ff;
            --primary-dark: #0077cc;
            --secondary: #00d9ff;
            --dark-bg: #1a1f2e;
            --darker-bg: #0f172a;
            --card-bg: #2a3142;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light-text: #f8fafc;
            --muted-text: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            
            /* خاص بأنواع الطلبات */
            --airbag-color: #8b5cf6;
            --ecu-color: #10b981;
            --key-color: #f59e0b;
            --diagnostic-color: #3b82f6;
            
            /* حسب نوع الطلب الحالي */
            --service-color: <?= getServiceColor($type) ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Cairo', sans-serif;
            background-color: var(--dark-bg);
            color: var(--light-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* الشريط العلوي */
        .top-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--service-color));
        }
        
        /* الهيدر */
        header {
            background-color: var(--darker-bg);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: bold;
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--light-text);
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* المحتوى الرئيسي */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 80vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
            flex: 1;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--service-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* بطاقة تفاصيل التذكرة */
        .ticket-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .ticket-header {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ticket-id {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ticket-id span {
            font-size: 20px;
            font-weight: bold;
            color: var(--service-color);
        }
        
        .ticket-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--service-color);
        }
        
        .ticket-status {
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 14px;
            background-color: rgba(<?= implode(', ', sscanf(getStatusColor($ticket['status']), "#%02x%02x%02x")) ?>, 0.1);
            color: <?= getStatusColor($ticket['status']) ?>;
            border: 1px solid rgba(<?= implode(', ', sscanf(getStatusColor($ticket['status']), "#%02x%02x%02x")) ?>, 0.3);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ticket-body {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--service-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-item {
            margin-bottom: 12px;
        }
        
        .info-label {
            font-size: 14px;
            color: var(--muted-text);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 20px 0;
        }
        
        /* نموذج الملاحظات */
        .comments-section textarea {
            width: 100%;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            color: var(--light-text);
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 15px;
        }
        
        .comments-section textarea:focus {
            outline: none;
            border-color: var(--service-color);
        }
        
        /* أزرار */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: var(--service-color);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--light-text);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        /* التنبيه */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }
        
        /* فوتر الصفحة */
        footer {
            background-color: var(--darker-bg);
            padding: 15px;
            text-align: center;
            font-size: 14px;
            color: var(--muted-text);
            border-top: 1px solid var(--border-color);
            width: 100%;
            margin-top: auto;
        }
        
        /* توافقية الموبايل */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .ticket-status {
                align-self: flex-start;
            }
        }
        
        /* طباعة */
        @media print {
            body {
                background-color: white;
                color: black;
            }
            
            header, footer, .actions, form {
                display: none;
            }
            
            .ticket-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .ticket-header {
                background-color: #f5f5f5;
                color: black;
            }
            
            .ticket-icon, .section-title {
                color: black;
            }
            
            .ticket-status {
                border: 1px solid #ddd;
                color: black;
                background-color: #f5f5f5;
            }
        }
        
        /* خاص بتحديد حالة التذكرة */
        .status-select-container {
            margin-bottom: 15px;
        }
        
        .status-select {
            padding: 8px 12px;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--light-text);
            font-family: inherit;
            width: 100%;
            max-width: 250px;
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--service-color);
        }