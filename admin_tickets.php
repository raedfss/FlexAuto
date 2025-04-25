<?php
session_start();

// التحقق من تسجيل الدخول والصلاحيات
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

// تحديث حالة التذكرة عند النقر على "تمت المراجعة"
if (isset($_GET['mark_seen']) && isset($_GET['type'])) {
    $id = intval($_GET['mark_seen']);
    $type = $_GET['type'];
    
    switch ($type) {
        case 'airbag':
            $pdo->query("UPDATE airbag_requests SET status = 1 WHERE id = $id");
            break;
        case 'ecu':
            $pdo->query("UPDATE ecu_tuning_requests SET status = 1 WHERE id = $id");
            break;
        case 'key':
            $pdo->query("UPDATE key_requests SET status = 1 WHERE id = $id");
            break;
        case 'diagnostic':
            $pdo->query("UPDATE diagnostic_requests SET status = 1 WHERE id = $id");
            break;
    }
    
    header("Location: admin_tickets.php");
    exit;
}

// إكمال الطلب
if (isset($_GET['complete_ticket']) && isset($_GET['type'])) {
    $id = intval($_GET['complete_ticket']);
    $type = $_GET['type'];
    
    switch ($type) {
        case 'airbag':
            $pdo->query("UPDATE airbag_requests SET status = 2 WHERE id = $id");
            break;
        case 'ecu':
            $pdo->query("UPDATE ecu_tuning_requests SET status = 2 WHERE id = $id");
            break;
        case 'key':
            $pdo->query("UPDATE key_requests SET status = 2 WHERE id = $id");
            break;
        case 'diagnostic':
            $pdo->query("UPDATE diagnostic_requests SET status = 2 WHERE id = $id");
            break;
    }
    
    header("Location: admin_tickets.php");
    exit;
}

// إلغاء التذكرة
if (isset($_GET['cancel_ticket']) && isset($_GET['type'])) {
    $id = intval($_GET['cancel_ticket']);
    $type = $_GET['type'];
    
    switch ($type) {
        case 'airbag':
            $pdo->query("UPDATE airbag_requests SET status = 3 WHERE id = $id");
            break;
        case 'ecu':
            $pdo->query("UPDATE ecu_tuning_requests SET status = 3 WHERE id = $id");
            break;
        case 'key':
            $pdo->query("UPDATE key_requests SET status = 3 WHERE id = $id");
            break;
        case 'diagnostic':
            $pdo->query("UPDATE diagnostic_requests SET status = 3 WHERE id = $id");
            break;
    }
    
    header("Location: admin_tickets.php");
    exit;
}

// تحديد نوع التذكرة النشط (الافتراضي: الكل)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// دالة مساعدة لتحديد حالة التبويب النشط
function isActiveTab($tab) {
    global $activeTab;
    return $activeTab === $tab ? 'active' : '';
}

// استعلامات للحصول على طلبات كل نوع
$airbagQuery = "SELECT id, username, car_make, ecu_model AS service_detail, vin, status, created_at, 
                'airbag' AS request_type, 'مسح بيانات Airbag' AS service_name 
                FROM airbag_requests ORDER BY created_at DESC";

$ecuQuery = "SELECT id, username, car_make, tool_type AS service_detail, vin, status, created_at, 
             'ecu' AS request_type, 'تعديل برمجة ECU' AS service_name 
             FROM ecu_tuning_requests ORDER BY created_at DESC";

$keyQuery = "SELECT id, username, car_make, ecu_type AS service_detail, vin, status, created_at, 
             'key' AS request_type, 'برمجة المفتاح' AS service_name 
             FROM key_requests ORDER BY created_at DESC";

$diagnosticQuery = "SELECT id, username, car_make, issue_desc AS service_detail, vin, status, created_at, 
                    'diagnostic' AS request_type, 'تشخيص أعطال' AS service_name 
                    FROM diagnostic_requests ORDER BY created_at DESC";

// تنفيذ الاستعلامات
try {
    $airbag_requests = $pdo->query($airbagQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $airbag_requests = [];
}

try {
    $ecu_requests = $pdo->query($ecuQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ecu_requests = [];
}

try {
    $key_requests = $pdo->query($keyQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $key_requests = [];
}

try {
    $diagnostic_requests = $pdo->query($diagnosticQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $diagnostic_requests = [];
}

// دمج جميع أنواع الطلبات معًا للعرض الكامل
$all_requests = array_merge($airbag_requests, $ecu_requests, $key_requests, $diagnostic_requests);

// ترتيب جميع الطلبات حسب تاريخ الإنشاء (الأحدث أولاً)
usort($all_requests, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// تحديد الطلبات المراد عرضها بناءً على التبويب النشط
$display_requests = [];
switch ($activeTab) {
    case 'airbag':
        $display_requests = $airbag_requests;
        break;
    case 'ecu':
        $display_requests = $ecu_requests;
        break;
    case 'key':
        $display_requests = $key_requests;
        break;
    case 'diagnostic':
        $display_requests = $diagnostic_requests;
        break;
    default:
        $display_requests = $all_requests;
}

// احصائيات
$total_requests = count($all_requests);
$new_requests = count(array_filter($all_requests, function($req) { return $req['status'] == 0; }));
$in_progress = count(array_filter($all_requests, function($req) { return $req['status'] == 1; }));
$completed = count(array_filter($all_requests, function($req) { return $req['status'] == 2; }));
$cancelled = count(array_filter($all_requests, function($req) { return $req['status'] == 3; }));

// البحث في الطلبات
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search_term)) {
    $display_requests = array_filter($display_requests, function($req) use ($search_term) {
        return stripos($req['username'], $search_term) !== false || 
               stripos($req['vin'], $search_term) !== false || 
               stripos($req['car_make'], $search_term) !== false ||
               stripos($req['service_name'], $search_term) !== false;
    });
}

// الفلترة حسب الحالة
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
if ($status_filter !== '') {
    $display_requests = array_filter($display_requests, function($req) use ($status_filter) {
        return $req['status'] == $status_filter;
    });
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التذاكر | FlexAuto</title>
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
            background: linear-gradient(90deg, var(--primary), var(--secondary));
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
        .container {
            max-width: 1300px;
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
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* إحصائيات الطلبات */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .label {
            font-size: 14px;
            color: var(--muted-text);
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        /* التبويبات */
        .tabs {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            color: var(--muted-text);
            text-decoration: none;
        }
        
        .tab.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--light-text);
        }
        
        .tab:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .tab .count {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .tab.active .count {
            background-color: var(--primary);
        }
        
        /* شريط البحث والفلاتر */
        .filters-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
        }
        
        .search-box {
            flex: 1;
            display: flex;
            max-width: 500px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            background-color: var(--card-bg);
            color: var(--light-text);
            border-radius: 8px 0 0 8px;
            font-family: inherit;
        }
        
        .search-box button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-dropdown select {
            padding: 10px;
            background-color: var(--card-bg);
            color: var(--light-text);
            border: none;
            border-radius: 8px;
            font-family: inherit;
            cursor: pointer;
        }
        
        /* جدول الطلبات */
        .tickets-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .tickets-table th {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 15px 10px;
            font-weight: 600;
            text-align: right;
            color: var(--secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .tickets-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .tickets-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .tickets-table tr:last-child td {
            border-bottom: none;
        }
        
        /* حالات الطلبات وأنواعها */
        .service-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .service-airbag {
            background-color: rgba(139, 92, 246, 0.15);
            color: var(--airbag-color);
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .service-ecu {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--ecu-color);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .service-key {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--key-color);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .service-diagnostic {
            background-color: rgba(59, 130, 246, 0.15);
            color: var(--info);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-new {
            background-color: rgba(59, 130, 246, 0.15);
            color: var(--info);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .status-in-progress {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .status-completed {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        /* أزرار الإجراءات */
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0da271;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #e02424;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .btn-info {
            background-color: var(--info);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #2563eb;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--light-text);
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-menu {
            position: absolute;
            left: 0;
            top: 100%;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            min-width: 200px;
            z-index: 10;
            margin-top: 5px;
            display: none;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .actions-dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            text-decoration: none;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: var(--border-color);
        }
        
        /* فوتر الصفحة */
        footer {
            background-color: var(--darker-bg);
            padding: 15px;
            text-align: center;
            font-size: 14px;
            color: var(--muted-text);
            border-top: 1px solid var(--border-color);
        }
        
        /* توافقية الموبايل */
        @media (max-width: 992px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tabs {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .hide-mobile {
                display: none;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .tickets-table {
                font-size: 12px;
            }
            
            .tickets-table th, 
            .tickets-table td {
                padding: 8px 5px;
            }
            
            .action-btn {
                padding: 5px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="top-bar"></div>

<header>
    <div class="logo">
        <i class="fas fa-ticket-alt"></i>
        FlexAuto
    </div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-stethoscope"></i>
            <span>تشخيص الأعطال</span>
            <span class="count"><?= count($diagnostic_requests) ?></span>
        </a>
    </div>
    
    <!-- البحث والفلاتر -->
    <div class="filters-row">
        <form class="search-box" method="GET" action="">
            <input type="hidden" name="tab" value="<?= $activeTab ?>">
            <input type="text" name="search" placeholder="بحث عن طلب..." value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit">
                <i class="fas fa-search"></i>
                بحث
            </button>
        </form>
        
        <div class="filter-dropdown">
            <select name="status_filter" id="status-filter" onchange="this.form.submit()">
                <option value="">جميع الحالات</option>
                <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>جديد</option>
                <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>قيد المعالجة</option>
                <option value="2" <?= $status_filter === '2' ? 'selected' : '' ?>>مكتمل</option>
                <option value="3" <?= $status_filter === '3' ? 'selected' : '' ?>>ملغي</option>
            </select>
        </div>
    </div>
    
    <!-- جدول الطلبات -->
    <table class="tickets-table">
        <thead>
            <tr>
                <th width="60">#</th>
                <th>المستخدم</th>
                <th>نوع الطلب</th>
                <th class="hide-mobile">التاريخ</th>
                <th>السيارة</th>
                <th>رقم الشاسيه</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($display_requests) > 0): ?>
                <?php foreach ($display_requests as $request): ?>
                    <tr>
                        <td><?= $request['id'] ?></td>
                        <td><?= htmlspecialchars($request['username']) ?></td>
                        <td>
                            <?php 
                            $service_class = '';
                            switch ($request['request_type']) {
                                case 'airbag':
                                    $service_class = 'service-airbag';
                                    $icon = 'fa-car-crash';
                                    break;
                                case 'ecu':
                                    $service_class = 'service-ecu';
                                    $icon = 'fa-microchip';
                                    break;
                                case 'key':
                                    $service_class = 'service-key';
                                    $icon = 'fa-key';
                                    break;
                                case 'diagnostic':
                                    $service_class = 'service-diagnostic';
                                    $icon = 'fa-stethoscope';
                                    break;
                            }
                            ?>
                            <span class="service-badge <?= $service_class ?>">
                                <i class="fas <?= $icon ?>"></i>
                                <?= htmlspecialchars($request['service_name']) ?>
                            </span>
                        </td>
                        <td class="hide-mobile"><?= date('Y/m/d', strtotime($request['created_at'])) ?></td>
                        <td><?= htmlspecialchars($request['car_make']) ?></td>
                        <td><?= htmlspecialchars($request['vin']) ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            $status_icon = '';
                            
                            switch ($request['status']) {
                                case 0:
                                    $status_class = 'status-new';
                                    $status_text = 'جديد';
                                    $status_icon = 'fa-bell';
                                    break;
                                case 1:
                                    $status_class = 'status-in-progress';
                                    $status_text = 'قيد المعالجة';
                                    $status_icon = 'fa-clock';
                                    break;
                                case 2:
                                    $status_class = 'status-completed';
                                    $status_text = 'مكتمل';
                                    $status_icon = 'fa-check-circle';
                                    break;
                                case 3:
                                    $status_class = 'status-cancelled';
                                    $status_text = 'ملغي';
                                    $status_icon = 'fa-ban';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?>"></i>
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions-dropdown">
                                <button class="action-btn btn-outline">
                                    <i class="fas fa-ellipsis-v"></i>
                                    الإجراءات
                                </button>
                                <div class="dropdown-menu">
                                    <a href="ticket_details.php?id=<?= $request['id'] ?>&type=<?= $request['request_type'] ?>" class="dropdown-item">
                                        <i class="fas fa-eye"></i>
                                        عرض التفاصيل
                                    </a>
                                    
                                    <?php if ($request['status'] == 0): ?>
                                        <a href="?mark_seen=<?= $request['id'] ?>&type=<?= $request['request_type'] ?>" class="dropdown-item">
                                            <i class="fas fa-check"></i>
                                            بدء المعالجة
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] == 1): ?>
                                        <a href="?complete_ticket=<?= $request['id'] ?>&type=<?= $request['request_type'] ?>" class="dropdown-item">
                                            <i class="fas fa-check-double"></i>
                                            إكمال الطلب
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] < 2): ?>
                                        <div class="dropdown-divider"></div>
                                        <a href="?cancel_ticket=<?= $request['id'] ?>&type=<?= $request['request_type'] ?>" class="dropdown-item" onclick="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟');">
                                            <i class="fas fa-ban"></i>
                                            إلغاء الطلب
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
                        <i class="fas fa-inbox" style="font-size: 40px; color: var(--muted-text); margin-bottom: 10px;"></i>
                        <p>لا توجد طلبات متاحة حاليًا</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer>
    <div>جميع الحقوق محفوظة &copy; <?= date('Y') ?> FlexAuto</div>
</footer>

<script>
    // تغيير فلتر الحالة
    document.getElementById('status-filter').addEventListener('change', function() {
        // تجميع المعلمات الحالية من الـ URL
        let currentUrl = new URL(window.location.href);
        let searchParams = currentUrl.searchParams;
        
        // تعيين أو تحديث معلمة الحالة
        if (this.value) {
            searchParams.set('status_filter', this.value);
        } else {
            searchParams.delete('status_filter');
        }
        
        // الانتقال إلى URL الجديد
        window.location.href = currentUrl.toString();
    });
</script>

</body>
</html> fa-tachometer-alt"></i>
            <span>لوحة التحكم</span>
        </a>
        <a href="admin_tickets.php" class="nav-link">
            <i class="fas fa-ticket-alt"></i>
            <span>التذاكر</span>
        </a>
        <a href="customers.php" class="nav-link">
            <i class="fas fa-users"></i>
            <span>العملاء</span>
        </a>
        <a href="reports.php" class="nav-link">
            <i class="fas fa-chart-bar"></i>
            <span>التقارير</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-clipboard-list"></i>
            إدارة التذاكر
        </h1>
    </div>
    
    <!-- إحصائيات الطلبات -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="label">إجمالي الطلبات</div>
            <div class="value">
                <div class="stat-icon" style="background-color: rgba(59, 130, 246, 0.2); color: var(--info);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <?= $total_requests ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">طلبات جديدة</div>
            <div class="value">
                <div class="stat-icon" style="background-color: rgba(59, 130, 246, 0.2); color: var(--info);">
                    <i class="fas fa-bell"></i>
                </div>
                <?= $new_requests ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">قيد المعالجة</div>
            <div class="value">
                <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.2); color: var(--warning);">
                    <i class="fas fa-clock"></i>
                </div>
                <?= $in_progress ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">تم إنجازه</div>
            <div class="value">
                <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.2); color: var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <?= $completed ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">ملغية</div>
            <div class="value">
                <div class="stat-icon" style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger);">
                    <i class="fas fa-ban"></i>
                </div>
                <?= $cancelled ?>
            </div>
        </div>
    </div>
    
    <!-- تبويبات أنواع الطلبات -->
    <div class="tabs">
        <a href="?tab=all" class="tab <?= isActiveTab('all') ?>">
            <i class="fas fa-list"></i>
            <span>جميع الطلبات</span>
            <span class="count"><?= count($all_requests) ?></span>
        </a>
        <a href="?tab=airbag" class="tab <?= isActiveTab('airbag') ?>">
            <i class="fas fa-car-crash"></i>
            <span>مسح Airbag</span>
            <span class="count"><?= count($airbag_requests) ?></span>
        </a>
        <a href="?tab=ecu" class="tab <?= isActiveTab('ecu') ?>">
            <i class="fas fa-microchip"></i>
            <span>برمجة ECU</span>
            <span class="count"><?= count($ecu_requests) ?></span>
        </a>
        <a href="?tab=key" class="tab <?= isActiveTab('key') ?>">
            <i class="fas fa-key"></i>
            <span>المفاتيح</span>
            <span class="count"><?= count($key_requests) ?></span>
        </a>
        <a href="?tab=diagnostic" class="tab <?= isActiveTab('diagnostic') ?>">
            <i class="fas