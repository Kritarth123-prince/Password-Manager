<?php
session_start();
require 'db_config.php';
if (!isset($_SESSION['pending_2fa'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['pending_2fa'];
$stmt = $db->prepare("SELECT 2fa_secret FROM members WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    require 'totp.php';
    if (totp_verify($user['2fa_secret'], $_POST['code'])) {
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['pending_2fa']);
        header("Location: view.php"); exit;
    } else {
        $error = "Invalid code! Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>2FA Verification — Password Manager</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(120deg,#667eea,#764ba2);font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:rgba(255,255,255,0.97);padding:40px 30px 30px 30px;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,0.15);min-width:330px;max-width:375px;text-align:center}
.box h2{color:#764ba2;font-size:28px;margin-bottom:18px;font-weight:700}
.box p{color:#444;font-size:16px;margin-bottom:25px}
input[type="text"]{font-size:22px;letter-spacing:3px;text-align:center;border:2px solid #667eea;border-radius:8px;padding:12px;width:80%;margin:14px 0 18px 0;outline:none;transition:0.2s}
input[type="text"]:focus{border-color:#764ba2;box-shadow:0 0 8px #764ba233}
.btn{background:#667eea;color:#fff;font-size:17px;font-weight:600;padding:13px 0;border:none;border-radius:24px;width:85%;margin:12px 0 8px 0;cursor:pointer;box-shadow:0 2px 6px rgba(102,126,234,0.08);transition:0.2s}
.btn:hover{background:#764ba2}
.error{color:#fff;background:#f44336;padding:10px;border-radius:8px;margin-top:10px;font-size:15px;animation:fadeIn 0.4s}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
#icon{font-size:48px;color:#667eea;margin-bottom:12px}
</style>
</head>
<body>
<div class="box">
    <div id="icon">🔐</div>
    <h2>Two-Factor Authentication</h2>
    <p>Please enter the 6-digit code from your authenticator app.</p>
    <form method="post" autocomplete="off">
        <input type="text" name="code" maxlength="6" pattern="\d{6}" placeholder="●●●●●●" required autofocus>
        <button class="btn" type="submit">Verify</button>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    </form>
    <div style="margin-top:17px;font-size:14px;color:#888;">
        Having trouble? <a href="login.php" style="color:#667eea;text-decoration:underline">Back to Login</a>
    </div>
</div>
</body>
</html>