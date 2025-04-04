<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$username = $_SESSION['username'];
$message = "";
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $programmer_name = trim($_POST['programmer_name'] ?? '');
    $tool_type = $_POST['tool_type'] ?? '';
    $car_make = trim($_POST['car_make'] ?? '');
    $vin = strtoupper(trim($_POST['vin'] ?? ''));
    $notes = trim($_POST['notes'] ?? '');
    $file = $_FILES['upload_file'] ?? null;

    $uploadOk = false;
    $newFileName = null;
    $allowedTypes = ['bin', 'hex', 'zip', 'jpg', 'jpeg', 'png'];
    $uploadPath = "uploads/tuning/";

    if ($file && $file['error'] === 0 && $file['size'] > 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedTypes)) {
            $newFileName = 'tuning_' . $username . '_' . date('Ymd_His') . '.' . $ext;
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
            $message = "❌ نوع الملف غير مسموح. الرجاء رفع BIN, HEX, ZIP أو صور فقط.";
        }
    } else {
        $uploadOk = true; // لأن الملف اختياري
    }

    if ($uploadOk) {
        try {
            $stmt = $pdo->prepare("INSERT INTO ecu_tuning_requests 
                (username, programmer_name, tool_type, car_make, vin, notes, uploaded_file, status, created_at) 
                VALUES (:username, :programmer_name, :tool_type, :car_make, :vin, :notes, :uploaded_file, 0, NOW())");

            $stmt->execute([
                ':username' => $username,
                ':programmer_name' => $programmer_name,
                ':tool_type' => $tool_type,
                ':car_make' => $car_make,
                ':vin' => $vin,
                ':notes' => $notes,
                ':uploaded_file' => $newFileName
            ]);

            $lastId = $pdo->lastInsertId();
            $message = "
            <div style='padding:20px; background-color:rgba(0,100,0,0.2); border:1px solid rgba(0,255,0,0.3); border-radius:12px; font-size:16px; line-height:1.8;'>
                ✅ <strong>تم استلام طلبك لتعديل البرمجة بنجاح!</strong><br>
                🛠️ <strong>البرنامج المستخدم:</strong> $programmer_name<br>
                🔧 <strong>نوع الأداة:</strong> $tool_type<br>
                🚗 <strong>نوع السيارة:</strong> $car_make<br>
                📌 <strong>رقم الشاسيه:</strong> $vin<br>
                🔢 <strong>رقم الطلب:</strong> $lastId<br><br>
                💬 <strong>طلبك قيد المراجعة، وسنقوم بالتواصل معك قريبًا.</strong><br><br>
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
    <title>طلب تعديل برمجة وحدة ECU | FlexAuto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: white;
            background-color: #1a1f2e;
            overflow-x: hidden;
        }
        
        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
        
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }
        
        .container {
            background: rgba(0, 0, 0, 0.65);
            padding: 25px;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(66, 135, 245, 0.2);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
        }
        
        .container::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 15px;
            padding: 1px; 
            background: linear-gradient(135deg, rgba(30, 144, 255, 0.5), rgba(0, 191, 255, 0.2)); 
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
        
        .container:hover {
            box-shadow: 0 0 40px rgba(0, 255, 255, 0.2);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 5px;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
            font-size: 22px;
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        h1::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: -8px;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, rgba(0, 255, 255, 0.1), rgba(0, 255, 255, 0.8), rgba(0, 255, 255, 0.1));
            border-radius: 3px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #a0d0ff;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid rgba(0, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 15px;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #1e90ff;
            box-shadow: 0 0 0 2px rgba(30, 144, 255, 0.2);
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #1e90ff, #00bfff);
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            margin-top: 15px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #00bfff, #1e90ff);
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shine 1.5s infinite;
        }
        
        .alert {
            margin: 10px 0;
            padding: 10px;
            border-radius: 6px;
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
            padding: 10px;
            font-size: 15px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .file-input-button:hover {
            background: linear-gradient(135deg, #00bfff, #1e90ff);
        }
        
        .file-input-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shine 2s infinite;
        }
        
        #file-name-display {
            margin-top: 6px;
            font-size: 14px;
            color: #a0d0ff;
        }
        
        .logout-btn, .home-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
            font-weight: bold;
            margin: 0 5px;
        }
        
        .logout-btn {
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.4);
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 107, 107, 0.1);
            border-color: rgba(255, 107, 107, 0.6);
        }
        
        .home-btn {
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }
        
        .home-btn:hover {
            background-color: rgba(76, 175, 80, 0.1);
            border-color: rgba(76, 175, 80, 0.6);
        }
        
        footer {
            background-color: rgba(0, 0, 0, 0.9);
            color: #eee;
            text-align: center;
            padding: 15px;
            font-size: 13px;
            width: 100%;
        }
        
        .footer-highlight {
            font-size: 18px;
            font-weight: bold;
            color: #00ffff;
            margin-bottom: 8px;
        }
        
        .success {
            padding: 15px;
            background-color: rgba(0, 100, 0, 0.2);
            border: 1px solid rgba(0, 255, 0, 0.3);
            border-radius: 12px;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        @keyframes shine {
            100% {
                left: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .container { width: 95%; padding: 15px; }
            header { font-size: 22px; padding: 12px; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="svg-background">
        <embed type="image/svg+xml" src="admin/admin_background.svg" class="svg-object">
    </div>

    <header>
        FlexAuto - نظام تعديل برمجة وحدة ECU
    </header>

    <div class="content-wrapper">
        <div class="container">
            <h1><i class="fas fa-microchip"></i> طلب تعديل برمجة وحدة ECU</h1>

            <?php if (!empty($message)): ?>
                <div <?php echo strpos($message, '✅') !== false ? 'class="success"' : 'class="alert"'; ?>>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($showForm): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="programmer_name"><i class="fas fa-tools"></i> اسم برنامج البرمجة:</label>
                        <input type="text" name="programmer_name" id="programmer_name" required placeholder="مثال: WinOLS, Kess">
                    </div>

                    <div class="form-group">
                        <label for="tool_type"><i class="fas fa-wrench"></i> نوع الأداة:</label>
                        <select name="tool_type" id="tool_type" required>
                            <option value="">-- اختر --</option>
                            <option value="Slave">Slave</option>
                            <option value="Master">Master</option>
                        </select>
                    </div>

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
                        <label for="vin"><i class="fas fa-fingerprint"></i> رقم الشاسيه (VIN):</label>
                        <input type="text" name="vin" id="vin" maxlength="17" placeholder="مثال: 1HGCM82633A123456" required>
                    </div>

                    <div class="form-group">
                        <label for="notes"><i class="fas fa-clipboard"></i> ملاحظات إضافية (اختياري):</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="مثل: نوع التعديل المطلوب أو الملاحظات الأخرى"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="upload_file"><i class="fas fa-file-upload"></i> رفع ملف أو صورة (اختياري):</label>
                        <div class="file-input-wrapper">
                            <div class="file-input-button">
                                <i class="fas fa-upload"></i> اختر الملف
                            </div>
                            <input type="file" name="upload_file" id="upload_file" accept=".bin,.hex,.zip,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                        </div>
                        <div id="file-name-display">لم يتم اختيار ملف</div>
                    </div>

                    <button type="submit" name="submit_request" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> إرسال الطلب
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <a href="home.php" class="home-btn">
            <i class="fas fa-home"></i> الصفحة الرئيسية
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
        </a>
    </div>

    <footer>
        <div class="footer-highlight">ذكاءٌ في الخدمة، سرعةٌ في الاستجابة، جودةٌ بلا حدود.</div>
        <div>Smart service, fast response, unlimited quality.</div>
        <div style="margin-top: 8px;">📧 raedfss@hotmail.com | ☎️ +962796519007</div>
        <div style="margin-top: 5px;">&copy; <?= date('Y') ?> FlexAuto. جميع الحقوق محفوظة.</div>
    </footer>
</div>

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