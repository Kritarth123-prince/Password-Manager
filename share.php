<?php
require 'db_config.php';
$token=$_GET['t']??'';

// Get shared password info
$stmt=$db->prepare("SELECT sp.*,d.* FROM shared_passwords sp JOIN data d ON sp.password_id=d.id WHERE sp.token=?");
$stmt->bind_param("s",$token);
$stmt->execute();
$data=$stmt->get_result()->fetch_assoc();

// Check if link is valid
if(!$data)die('<div style="font-family:Arial;background:#ff4757;color:#fff;height:100vh;display:flex;align-items:center;justify-content:center;margin:0"><div style="text-align:center"><h1>❌ Invalid Link</h1><p>Link not found or removed</p></div></div>');

// Check if expired
if($data['expires_at']<date('Y-m-d H:i:s'))die('<div style="font-family:Arial;background:#ffa726;color:#fff;height:100vh;display:flex;align-items:center;justify-content:center;margin:0"><div style="text-align:center"><h1>⏰ Expired</h1><p>This link has expired</p></div></div>');

$url=strpos($data['website'],'http')===0?$data['website']:'https://'.$data['website'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Password Manager 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<meta name="viewport" content="width=device-width,initial-scale=1"><style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#fff}
.box{background:rgba(255,255,255,0.15);backdrop-filter:blur(20px);padding:30px;border-radius:20px;max-width:400px;width:90%;box-shadow:0 20px 40px rgba(0,0,0,0.3)}
.row{background:rgba(255,255,255,0.1);padding:15px;margin:10px 0;border-radius:10px;border:1px solid rgba(255,255,255,0.2)}
.row b{color:#ffd700;display:block;margin-bottom:5px}
.pass{font-family:monospace;background:rgba(0,0,0,0.3);padding:10px;border-radius:5px;font-size:1.1em;word-break:break-all}
.btn{background:#2ecc71;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;margin:5px;transition:all 0.3s}
.btn:hover{background:#27ae60;transform:translateY(-2px)}
.copy{background:#3498db}.copy:hover{background:#2980b9}
.hide{background:#e74c3c}.hide:hover{background:#c0392b}
.toast{position:fixed;top:20px;right:20px;background:#2ecc71;color:#fff;padding:10px 20px;border-radius:20px;transform:translateX(300px);transition:transform 0.3s;z-index:999}
.toast.show{transform:translateX(0)}
a{color:#87ceeb;text-decoration:none}
h2{text-align:center;margin-bottom:20px;font-size:1.5em}
</style></head><body>
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

<?php if($data['email']):?>
<div class="row">
<b>✉️ Email:</b>
<?=$data['email']?>
</div>
<?php endif;?>

<div class="row">
<b>🔒 Password:</b>
<div class="pass" id="pass">••••••••••••</div>
<button class="btn" onclick="show()" id="showBtn">👁️ Show</button>
<button class="btn copy" onclick="copy()">📋 Copy</button>
</div>

<?php if($data['category']):?>
<div class="row">
<b>📁 Category:</b>
<span style="background:rgba(255,255,255,0.2);padding:3px 10px;border-radius:15px"><?=ucfirst($data['category'])?></span>
</div>
<?php endif;?>

<?php if($data['notes']):?>
<div class="row">
<b>📝 Notes:</b>
<?=htmlspecialchars($data['notes'])?>
</div>
<?php endif;?>

<?php if($data['note']):?>
<div class="row" style="background:linear-gradient(45deg,#ff9a56,#ffad56);color:#333">
<b>💬 Share Note:</b>
<?=htmlspecialchars($data['note'])?>
</div>
<?php endif;?>

<div style="text-align:center;margin-top:20px;opacity:0.8;font-size:0.9em">
🔒 Expires: <?=date('M j, Y g:i A',strtotime($data['expires_at']))?>
</div>
</div>

<div class="toast" id="toast"></div>

<script>
let shown=0;
const pass='<?=addslashes($data['password'])?>';

function show(){
const p=document.getElementById('pass'),btn=document.getElementById('showBtn');
if(shown){
p.textContent='••••••••••••';
btn.innerHTML='👁️ Show';
btn.className='btn';
}else{
p.textContent=pass;
btn.innerHTML='🙈 Hide';
btn.className='btn hide';
}
shown=!shown;
}

function copy(){
navigator.clipboard.writeText(pass).then(()=>toast('✅ Copied!')).catch(()=>toast('❌ Failed!'));
}

function toast(msg){
const t=document.getElementById('toast');
t.textContent=msg;
t.classList.add('show');
setTimeout(()=>t.classList.remove('show'),2000);
}
</script>
</body></html>