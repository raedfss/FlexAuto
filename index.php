<?php
session_start();

// منع الدخول المباشر بدون تسجيل دخول
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$username = $_SESSION['username'];
$message = "";
$showForm = true;

// معالجة طلب الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $car_make = trim($_POST['car_make'] ?? '');
    $ecu_model = trim($_POST['ecu_model'] ?? '');
    $vin = strtoupper(trim($_POST['vin'] ?? ''));
    $file = $_FILES['ecu_file'] ?? null;

    $uploadOk = false;
    $allowedTypes = ['bin', 'hex', 'zip'];
    $uploadPath = "uploads/airbags/";

    // التحقق من الملف وتحميله
    if ($file && $file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedTypes)) {
            $newFileName = 'airbag_' . $username . '_' . date('Ymd_His') . '.' . $ext;
            $target = $uploadPath . $newFileName;
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $uploadOk = true;
            } else {
                $message = "❌ فشل رفع الملف. الرجاء المحاولة مرة أخرى.";
            }
        } else {
            $message = "❌ نوع الملف غير مسموح. الرجاء رفع ملفات bin أو hex أو zip فقط.";
        }
    } else {
        $message = "❌ الرجاء اختيار ملف صحيح للرفع.";
    }

    // إذا تم تحميل الملف بنجاح، قم بإدخال الطلب في قاعدة البيانات
    if ($uploadOk) {
        try {
            $stmt = $pdo->prepare("INSERT INTO airbag_requests (username, car_make, ecu_model, vin, uploaded_file, status, created_at) VALUES (:username, :car_make, :ecu_model, :vin, :file, 0, NOW())");
            $stmt->execute([
                ':username' => $username,
                ':car_make' => $car_make,
                ':ecu_model' => $ecu_model,
                ':vin' => $vin,
                ':file' => $newFileName
            ]);
            
            $lastId = $pdo->lastInsertId();
            $message = "
            <div style='padding:20px; background-color:rgba(0,100,0,0.2); border:1px solid rgba(0,255,0,0.3); border-radius:12px; font-size:16px; line-height:1.8;'>
                ✅ <strong>تم استلام طلبك بنجاح!</strong><br>
                🚗 <strong>نوع السيارة:</strong> $car_make<br>
                📌 <strong>رقم الشاسيه:</strong> $vin<br>
                🧠 <strong>موديل وحدة ECU:</strong> $ecu_model<br>
                🔢 <strong>رقم الطلب:</strong> $lastId<br><br>
                💬 <strong>شكرًا لتواصلكم معنا. طلبكم قيد المعالجة وسنقوم بالتواصل معكم في أقرب وقت.</strong><br><br>
                <a href='home.php' style='color:#00ffff; font-weight:bold;'>🏠 العودة إلى الصفحة الرئيسية</a>
            </div>
            ";
            $showForm = false;
        } catch (PDOException $e) {
            $message = "❌ خطأ في حفظ الطلب: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب مسح بيانات Airbag | FlexAuto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* أسلوب متناسق مع تنسيق الموقع */
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: white;
            background-color: #1a1f2e;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .svg-background {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            opacity: 0.6;
        }
        
        .svg-object {
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        header {
            background-color: rgba(0, 0, 0, 0.85);
            padding: 20px;
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            color: #00ffff;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.3);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.4);
        }
        
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }
        
        .container {
            background: rgba(0, 0, 0, 0.65);
            padding: 30px;
            width: 90%;
            max-width: 700px;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(66, 135, 245, 0.2);
            transition: all 0.3s ease;
        }
        
        .container:hover {
            box-shadow: 0 0 40px rgba(0, 255, 255, 0.2);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #a0d0ff;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #1e90ff;
            box-shadow: 0 0 0 2px rgba(30, 144, 255, 0.2);
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #1e90ff, #00bfff);
            border: none;
            padding: 14px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            margin-top: 25px;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #00bfff, #1e90ff);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .alert {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.4);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-button {
            display: inline-block;
            background: linear-gradient(135deg, #1e90ff, #00bfff);
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            width: 100%;
            text-align: center;
            transition: all 0.3s;
        }
        
        .file-input-button:hover {
            background: linear-gradient(135deg, #00bfff, #1e90ff);
        }
        
        #file-name-display {
            margin-top: 8px;
            font-size: 14px;
            color: #a0d0ff;
        }
        
        footer {
            background-color: rgba(0, 0, 0, 0.9);
            color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            width: 100%;
        }
        
        .footer-highlight {
            font-size: 20px;
            font-weight: bold;
            color: #00ffff;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .container { width: 95%; padding: 20px; }
            header { font-size: 24px; padding: 15px; }
            main { padding: 15px 10px; }
        }
    </style>
</head>
<body>

<div class="svg-background">
    <embed type="image/svg+xml" src="admin/admin_background.svg" class="svg-object">
</div>

<header>
    FlexAuto - نظام مسح بيانات وحدة Airbag
</header>

<main>
    <div class="container">
        <h1><i class="fas fa-car-crash"></i> طلب مسح بيانات وحدة Airbag</h1>

        <?php if (!empty($message)): ?>
            <div <?php echo strpos($message, '✅') !== false ? 'class="success"' : 'class="alert"'; ?>>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="car_make"><i class="fas fa-car"></i> نوع السيارة:</label>
                    <select name="car_make" id="car_make" required>
                        <option value="">-- اختر --</option>
                        <option value="KIA">KIA</option>
                        <option value="Hyundai">Hyundai</option>
                        <option value="Toyota">Toyota</option>
                        <option value="Nissan">Nissan</option>
                        <option value="Other">أخرى</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ecu_model"><i class="fas fa-microchip"></i> موديل وحدة ECU:</label>
                    <input type="text" name="ecu_model" id="ecu_model" placeholder="أدخل موديل وحدة ECU" required>
                </div>

                <div class="form-group">
                    <label for="vin"><i class="fas fa-fingerprint"></i> رقم الشاسيه (VIN):</label>
                    <input type="text" name="vin" id="vin" maxlength="17" placeholder="مثال: 1HGCM82633A123456" required>
                </div>

                <div class="form-group">
                    <label for="ecu_file"><i class="fas fa-file-upload"></i> تحميل ملف الذاكرة (BIN/HEX/ZIP):</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-button">
                            <i class="fas fa-upload"></i> اختر الملف
                        </div>
                        <input type="file" name="ecu_file" id="ecu_file" accept=".bin,.hex,.zip" required onchange="updateFileName(this)">
                    </div>
                    <div id="file-name-display">لم يتم اختيار ملف</div>
                </div>

                <button type="submit" name="submit_request" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> إرسال الطلب
                </button>
            </form>
        <?php endif; ?>
    </div>
</main>

<footer>
    <div class="footer-highlight">ذكاءٌ في الخدمة، سرعةٌ في الاستجابة، جودةٌ بلا حدود.</div>
    <div>Smart service, fast response, unlimited quality.</div>
    <div style="margin-top: 8px;">📧 raedfss@hotmail.com | ☎️ +962796519007</div>
    <div style="margin-top: 5px;">&copy; <?= date('Y') ?> FlexAuto. جميع الحقوق محفوظة.</div>
</footer>

<script>
    function updateFileName(input) {
        const fileNameDisplay = document.getElementById('file-name-display');
        if (input.files && input.files[0]) {
            fileNameDisplay.textContent = 'تم اختيار: ' + input.files[0].name;
        } else {
            fileNameDisplay.textContent = 'لم يتم اختيار ملف';
        }
    }
    
    // التحقق من صحة رقم الشاسيه
    document.getElementById('vin').addEventListener('input', function(e) {
        this.value = this.value.toUpperCase().replace(/[IO]/g, '');
    });
</script>

</body>
</html>