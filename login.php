<?php
session_start();
require 'db_config.php';
$msg = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // Get user and 2FA status
    $stmt = $db->prepare("SELECT id, password, created_at, 2fa_enabled FROM members WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $logged_in = false;
    if ($user) {
        // Check encrypted password
        if (!empty($user['created_at'])) {
            $encryption_key = $user['created_at'];
            $iv = substr(md5($encryption_key), 0, 16);
            $decrypted = openssl_decrypt($user['password'], 'AES-256-CBC', $encryption_key, 0, $iv);
            if ($decrypted !== false && $pass === $decrypted) $logged_in = true;
        }
        // Check hashed password
        if (!$logged_in && password_verify($pass, $user['password'])) $logged_in = true;

        if ($logged_in) {
            if (!empty($user['2fa_enabled']) && $user['2fa_enabled'] == 1) {
                $_SESSION['pending_2fa'] = $user['id'];
                header("Location: 2fa.php");
                exit;
            } else {
                $_SESSION['user_id'] = $user['id'];
                header("Location: view.php");
                exit;
            }
        }
        $msg = "Login failed!";
    } else {
        $msg = "Login failed!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login — Password Manager 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#fff2;padding:24px;border-radius:16px;max-width:350px;width:100%;color:#fff}
input{width:100%;padding:10px;margin:7px 0;border:none;border-radius:8px;box-sizing:border-box}
.btn{background:#764ba2;color:#fff;padding:12px;border:none;border-radius:25px;width:100%;cursor:pointer;margin:5px 0}
h2{text-align:center}
a{display:block;text-align:center;color:#fff;margin:10px 0;text-decoration:none}
.notify{position:fixed;top:20px;right:20px;padding:15px;border-radius:8px;color:#fff;transform:translateX(400px);transition:transform 0.3s;z-index:1000}
.notify.show{transform:translateX(0)}
.error{background:#f44336}
.dev-icon{position:fixed;bottom:20px;right:20px;background:linear-gradient(45deg,#667eea,#764ba2);color:#fff;padding:12px;border-radius:50%;text-decoration:none;font-size:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.3);z-index:999;transition:0.3s}
.dev-icon:hover{transform:scale(1.1);box-shadow:0 6px 20px rgba(0,0,0,0.4)}
@media(max-width:600px){.dev-icon{bottom:15px;right:15px;padding:10px;font-size:1.2rem}}
</style>
</head>
<body>
<div class="box">
    <h2>Login</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn" name="login">Login</button>
    </form>
    <a href="signup.php">Create new account</a>
    <a href="forgot.php">Forgot Password?</a>
</div>
<div id="notify" class="notify"></div>
<a href="about-dev.php" class="dev-icon">🤖</a>
<script>
function showNotify(msg){
    let n=document.getElementById('notify');
    n.textContent=msg;
    n.className='notify error show';
    setTimeout(()=>n.classList.remove('show'),3000)
}
<?php if($msg):?>showNotify('<?=$msg?>');<?php endif;?>
</script>
</body>
</html>