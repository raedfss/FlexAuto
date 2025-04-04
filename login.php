<?php
session_start();
include 'db_connect.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['role']; // حفظ نوع المستخدم
        header("Location: home.php");
        exit;
    } else {
        $login_error = "❌ البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | FlexAuto</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: url('assets/login_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: white;
        }

        header {
            background-color: rgba(0, 0, 0, 0.75);
            padding: 20px;
            text-align: center;
            font-size: 34px;
            font-weight: bold;
            color: #00ffff;
            letter-spacing: 1px;
        }

        .login-box {
            background: rgba(0, 0, 0, 0.6);
            padding: 40px;
            width: 350px;
            margin: 100px auto;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-box input[type="email"],
        .login-box input[type="password"],
        .login-box input[type="submit"] {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
        }

        .login-box input[type="submit"] {
            background-color: #1e90ff;
            color: white;
            cursor: pointer;
        }

        .login-box input[type="submit"]:hover {
            background-color: #63b3ed;
        }

        .extra-links {
            margin-top: 20px;
            text-align: center;
        }

        .extra-links a {
            color: #00ffff;
            text-decoration: none;
            display: block;
            margin: 8px 0;
        }

        .error {
            color: #ff7b7b;
            text-align: center;
            margin-top: 15px;
        }

        footer {
            background-color: rgba(0, 0, 0, 0.8);
            color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
        }

        .footer-highlight {
            font-size: 20px;
            font-weight: bold;
            color: #00ffff;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<header>FlexAuto - نظام ورشة السيارات الذكي</header>

<div class="login-box">
    <h2>تسجيل الدخول</h2>
    <form method="post" action="login.php">
        <input type="email" name="email" placeholder="البريد الإلكتروني" required>
        <input type="password" name="password" placeholder="كلمة المرور" required>
        <input type="submit" value="دخول">
    </form>

    <?php if (!empty($login_error)): ?>
        <div class="error"><?= $login_error ?></div>
    <?php endif; ?>

    <div class="extra-links">
        <a href="forgot_password.php">🔒 نسيت كلمة المرور؟</a>
        <a href="register.php">📝 مستخدم جديد؟ إنشاء حساب</a>
    </div>
</div>

<footer>
    <div class="footer-highlight">ذكاءٌ في الخدمة، سرعةٌ في الاستجابة، جودةٌ بلا حدود.</div>
    <div>Smart service, fast response, unlimited quality.</div>
    <div style="margin-top: 8px;">📧 raedfss@hotmail.com | ☎️ +962796519007</div>
    <div style="margin-top: 5px;">&copy; <?= date('Y') ?> FlexAuto. جميع الحقوق محفوظة.</div>
</footer>

</body>
</html>
