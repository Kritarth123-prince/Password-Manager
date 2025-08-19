<?php
require 'db_config.php';
date_default_timezone_set('Asia/Kolkata'); // Set IST

function decryptPassword($encrypted_password, $created_at) {
    $iv = substr(md5($created_at), 0, 16);
    return openssl_decrypt($encrypted_password, 'AES-256-CBC', $created_at, 0, $iv);
}

$token = $_GET['t'] ?? '';
$now = date('Y-m-d H:i:s');

$stmt = $db->prepare("SELECT sp.note as share_note, sp.*, d.* FROM shared_passwords sp JOIN data d ON sp.password_id = d.id WHERE sp.token = ? AND sp.expires_at > ?");
$stmt->bind_param("ss", $token, $now);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// if (!$data) die('Access Denied - Link expired or invalid');
// rest of your HTML code stays the same

if (!$data) die('<!DOCTYPE html><html><head><style>body{margin:0;background:linear-gradient(135deg,#667eea,#764ba2);font-family:Arial;height:100vh;display:flex;align-items:center;justify-content:center}.box{text-align:center;color:white;animation:fadeIn 1s}.icon{font-size:4rem;margin-bottom:15px;animation:bounce 2s infinite}h1{font-size:2rem;margin:10px 0;font-weight:300}p{opacity:0.9;margin-bottom:25px}.btn{background:rgba(255,255,255,0.2);border:2px solid white;color:white;padding:12px 25px;border-radius:25px;text-decoration:none;transition:all 0.3s}.btn:hover{background:white;color:#667eea}@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}}@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}</style></head><body><div class="box"><div class="icon">🔐</div><h1>Access Denied</h1><p>A mysterious absence: this link is lost beyond retrieval.</p><a href="javascript:window.location.href=window.location.protocol+\'//\'+window.location.host+\'/php/Password_Manager/\'" class="btn">Go Home</a></div></body></html>');

if ($data['expires_at'] < date('Y-m-d H:i:s')) die('<!DOCTYPE html><html><head><style>body{margin:0;background:linear-gradient(135deg,#ff9a9e,#fecfef);font-family:Arial;height:100vh;display:flex;align-items:center;justify-content:center}.box{text-align:center;color:#333;animation:slideIn 1s}.icon{font-size:4rem;margin-bottom:15px;animation:tick 1s infinite}h1{font-size:2rem;margin:10px 0;background:linear-gradient(45deg,#ff6b6b,#ee5a24);-webkit-background-clip:text;-webkit-text-fill-color:transparent}p{color:#666;margin-bottom:25px}.btn{background:linear-gradient(45deg,#ff6b6b,#ee5a24);border:none;color:white;padding:12px 25px;border-radius:25px;text-decoration:none;transition:all 0.3s}.btn:hover{transform:translateY(-2px)}@keyframes slideIn{from{opacity:0;transform:scale(0.8)}}@keyframes tick{50%{transform:rotate(10deg)}}</style></head><body><div class="box"><div class="icon">⏰</div><h1>Time\'s Up!</h1><p>This link’s chapter has closed — keeping your data safe.</p><a href="javascript:window.location.href=window.location.protocol+\'//\'+window.location.host+\'/php/Password_Manager/\'" class="btn">Go Home</a></div></body></html>');

$decrypted_password = decryptPassword($data['password'], $data['created_at']);
$url = strpos($data['website'], 'http') === 0 ? $data['website'] : 'https://' . $data['website'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Shared Password</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#fff}
.box{background:rgba(255,255,255,0.15);padding:30px;border-radius:20px;max-width:400px;width:90%}
.row{background:rgba(255,255,255,0.1);padding:15px;margin:10px 0;border-radius:10px}
.row b{color:#ffd700;display:block;margin-bottom:5px}
.pass{font-family:monospace;background:rgba(0,0,0,0.3);padding:10px;border-radius:5px;word-break:break-all}
.btn{background:#2ecc71;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;margin:5px}
.copy{background:#3498db}.hide{background:#e74c3c}
.toast{position:fixed;top:20px;right:20px;background:#2ecc71;color:#fff;padding:10px 20px;border-radius:20px;transform:translateX(300px);transition:transform 0.3s}
.toast.show{transform:translateX(0)}
a{color:#87ceeb;text-decoration:none}
h2{text-align:center;margin-bottom:20px}
</style>
</head>
<body>
<div class="box">
<h2>🔐 Shared Password</h2>
<div class="row">
<b>🌐 Website:</b>
<a href="<?=$url?>" target="_blank"><?=$data['website']?> 🔗</a>
</div>
<div class="row">
<b>👤 Username:</b>
<?=$data['username']?>
</div>
<?php if($data['email']): ?>
<div class="row">
<b>✉️ Email:</b>
<?=$data['email']?>
</div>
<?php endif; ?>
<div class="row">
<b>🔒 Password:</b>
<div class="pass" id="pass">••••••••••••</div>
<button class="btn" onclick="show()" id="showBtn">👁️ Show</button>
<button class="btn copy" onclick="copy()">📋 Copy</button>
</div>
<?php if($data['category']): ?>
<div class="row">
<b>📁 Category:</b>
<span style="background:rgba(255,255,255,0.2);padding:3px 10px;border-radius:15px"><?=ucfirst($data['category'])?></span>
</div>
<?php endif; ?>
<?php if($data['share_note']): ?>
<div class="row" style="background:linear-gradient(45deg,#ff9a56,#ffad56);color:#333">
<b>💬 Share Note:</b>
<?=htmlspecialchars($data['share_note'])?>
</div>
<?php endif; ?>
<div style="text-align:center;margin-top:20px;opacity:0.8">
🔒 Expires: <?=date('M j, Y g:i A', strtotime($data['expires_at']))?>
</div>
</div>
<div class="toast" id="toast"></div>
<script>
let shown = 0;
const pass = '<?=addslashes($decrypted_password)?>';
function show() {
    const p = document.getElementById('pass'), btn = document.getElementById('showBtn');
    if (shown) {
        p.textContent = '••••••••••••';
        btn.innerHTML = '👁️ Show';
        btn.className = 'btn';
    } else {
        p.textContent = pass;
        btn.innerHTML = '🙈 Hide';
        btn.className = 'btn hide';
    }
    shown = !shown;
}
function copy() {
    navigator.clipboard.writeText(pass).then(() => toast('✅ Copied!')).catch(() => toast('❌ Failed!'));
}
function toast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2000);
}
</script>
</body>
</html>