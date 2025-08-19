<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_config.php';

$user_id = $_SESSION['user_id'];

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] == 'password') {
        $stmt = $db->prepare("SELECT password, created_at FROM members WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $password_correct = false;
        if (!empty($user['created_at'])) {
            $encryption_key = $user['created_at'];
            $iv = substr(md5($encryption_key), 0, 16);
            $decrypted = openssl_decrypt($user['password'], 'AES-256-CBC', $encryption_key, 0, $iv);
            if ($decrypted !== false && $_POST['old_password'] === $decrypted) $password_correct = true;
        }
        if (!$password_correct && password_verify($_POST['old_password'], $user['password'])) $password_correct = true;
        if ($password_correct) {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE members SET password=? WHERE id=?");
            $update->bind_param("si", $hash, $user_id);
            echo json_encode(['success' => $update->execute(), 'message' => 'Password updated!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is wrong!']);
        }
    } elseif ($_POST['action'] == 'security') {
        $question = $_POST['question'];
        $answer = $_POST['answer'];
        $stmt = $db->prepare("UPDATE members SET question=?, answer=? WHERE id=?");
        $stmt->bind_param("ssi", $question, $answer, $user_id);
        echo json_encode(['success' => $stmt->execute(), 'message' => 'Security saved!']);
    } elseif ($_POST['action'] == '2fa') {
        if ($_POST['mode'] === 'setup') {
            function base32_encode($data) {
                $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
                $binary = '';
                foreach (str_split($data) as $c) {
                    $binary .= sprintf('%08b', ord($c));
                }
                $base32 = '';
                foreach (str_split($binary, 5) as $chunk) {
                    $base32 .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
                }
                return $base32;
            }
            $secret = base32_encode(random_bytes(10));
            $db->query("UPDATE members SET 2fa_secret='$secret', 2fa_enabled=0 WHERE id=$user_id");
            echo json_encode(['secret' => $secret]);
        } elseif ($_POST['mode'] === 'verify') {
            require_once 'totp.php';
            $secret = $db->query("SELECT 2fa_secret FROM members WHERE id=$user_id")->fetch_assoc()['2fa_secret'];
            $code = $_POST['code'];
            if (totp_verify($secret, $code)) {
                $db->query("UPDATE members SET 2fa_enabled=1 WHERE id=$user_id");
                echo json_encode(['success' => true, 'message' => '2FA enabled!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid code!']);
            }
        } elseif ($_POST['mode'] === 'disable') {
            $db->query("UPDATE members SET 2fa_enabled=0, 2fa_secret=NULL WHERE id=$user_id");
            echo json_encode(['success' => true, 'message' => '2FA disabled!']);
        }
    } else {
        $field = $_POST['action'];
        $value = $_POST[$field];
        if ($field == 'phone' && strlen($value) != 10) {
            echo json_encode(['success' => false, 'message' => 'Phone must be 10 digits!']);
            exit;
        }
        $stmt = $db->prepare("UPDATE members SET `$field`=? WHERE id=?");
        $stmt->bind_param("si", $value, $user_id);
        echo json_encode(['success' => $stmt->execute(), 'message' => 'Saved!']);
    }
    exit;
}

$stmt = $db->prepare("SELECT * FROM members WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$section = $_GET['section'] ?? 'profile';
$maxDate = date('Y-m-d', strtotime('-10 years'));
$professions = [
    "Teacher", "Doctor", "Engineer", "Business", "Freelancer", "Homemaker", "Developer", "Designer", "Manager", "Other"
];
?>
<!DOCTYPE html>
<html>
<title>Profile — Password Manager</title>
<link rel="icon" href="assets/icon.png">
<style>
*{font-family:Arial;box-sizing:border-box;margin:0;padding:0}
body{background:linear-gradient(45deg,#667eea,#764ba2);display:flex;min-height:100vh}
.sidebar{width:180px;background:rgba(255,255,255,0.1);padding:20px;color:#fff}
.nav{display:block;color:#fff;text-decoration:none;padding:10px;margin:5px 0;border-radius:8px;transition:0.3s}
.nav:hover,.active{background:rgba(255,255,255,0.2)}
.content{flex:1;padding:20px}
.box{background:rgba(255,255,255,0.95);padding:30px;border-radius:12px;max-width:600px;box-shadow:0 4px 20px rgba(0,0,0,0.2)}
.field{margin:20px 0;padding:15px;background:#f8f9fa;border-radius:8px;border-left:4px solid #667eea}
.show{display:flex;justify-content:space-between;align-items:center}
.edit{display:none;gap:10px;margin-top:10px}
input,select{padding:10px;border:2px solid #ddd;border-radius:6px;font-size:14px;width:200px}
input:focus,select:focus{outline:none;border-color:#667eea;box-shadow:0 0 8px rgba(102,126,234,0.3)}
.btn{background:#667eea;color:#fff;padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-size:13px;transition:0.3s}
.btn:hover{background:#5a67d8;transform:translateY(-1px)}
.btn.edit-btn{background:#f6ad55}
.btn.cancel{background:#fc8181}
.notify{position:fixed;top:20px;right:20px;padding:15px 20px;border-radius:8px;color:#fff;transform:translateX(300px);transition:0.3s;font-weight:500}
.notify.success{background:#48bb78}
.notify.error{background:#f56565}
.notify.show{transform:translateX(0)}
h2{margin-bottom:25px;color:#2d3748;font-size:26px}
strong{color:#4a5568;font-size:16px}
.info{color:#718096;font-size:14px;margin-top:5px}
#strength{margin:8px 0;font-weight:500}
.very-weak{color:#e53e3e}.weak{color:#dd6b20}.fair{color:#d69e2e}.good{color:#38a169}.strong{color:#3182ce}
</style>
<body>
<div class="sidebar">
<h3>Settings</h3>
<a href="?section=profile" class="nav <?=$section=='profile'?'active':''?>">👤 Profile</a>
<a href="?section=security" class="nav <?=$section=='security'?'active':''?>">🔒 Security</a>
<a href="?section=password" class="nav <?=$section=='password'?'active':''?>">🔑 Password</a>
<a href="?section=2fa" class="nav <?=$section=='2fa'?'active':''?>">🔐 2FA</a>
<a href="view.php" class="nav">← Dashboard</a>
</div>
<div class="content">
<?php if($section == 'profile'): ?>
<div class="box">
<h2>Personal Information</h2>
<div class="field"><div><strong>Email:</strong><div class="info"><?=$user['email']?> (Cannot change)</div></div></div>
<div class="field"><div class="show" id="name_show"><div><strong>Name:</strong><div class="info"><?=$user['name']?:'Not set'?></div></div><button class="btn edit-btn" onclick="edit('name')">Edit</button></div><div class="edit" id="name_edit"><input type="text" id="name_input" value="<?=$user['name']?>"><button class="btn" onclick="save('name')">Save</button><button class="btn cancel" onclick="cancel('name')">Cancel</button></div></div>
<div class="field"><div class="show" id="phone_show"><div><strong>Phone:</strong><div class="info"><?=$user['phone']?:'Not set'?></div></div><button class="btn edit-btn" onclick="edit('phone')">Edit</button></div><div class="edit" id="phone_edit"><input type="tel" id="phone_input" value="<?=$user['phone']?>" maxlength="10"><button class="btn" onclick="save('phone')">Save</button><button class="btn cancel" onclick="cancel('phone')">Cancel</button></div></div>
<div class="field">
  <div class="show" id="dob_show">
    <div>
      <strong>Date of Birth:</strong>
      <div class="info">
        <?= $user['dob'] ? date('M j, Y', strtotime($user['dob'])) : 'Not set' ?>
      </div>
    </div>
    <button class="btn edit-btn" onclick="edit('dob')">Edit</button>
  </div>
  <div class="edit" id="dob_edit">
    <input type="date" id="dob_input" value="<?= $user['dob'] ?>" max="<?= $maxDate ?>">
    <button class="btn" onclick="save('dob')">Save</button>
    <button class="btn cancel" onclick="cancel('dob')">Cancel</button>
  </div>
</div>
<div class="field"><div class="show" id="profession_show"><div><strong>Profession:</strong><div class="info"><?=$user['profession']?:'Not set'?></div></div><button class="btn edit-btn" onclick="edit('profession')">Edit</button></div><div class="edit" id="profession_edit"><select id="profession_input"><option value="">Select</option><?php foreach($professions as $p): ?><option value="<?=$p?>" <?=$user['profession']==$p?'selected':''?>><?=$p?></option><?php endforeach; ?></select><button class="btn" onclick="save('profession')">Save</button><button class="btn cancel" onclick="cancel('profession')">Cancel</button></div></div>
</div>
<?php elseif($section == 'security'): ?>
<div class="box">
<h2>Security Settings</h2>
<div class="field">
  <div class="show" id="pin_show">
    <div><strong>Security PIN:</strong><div class="info"><?=$user['pin']?'6-digit PIN set':'Not set'?></div></div>
    <button class="btn edit-btn" onclick="edit('pin')">Edit</button>
  </div>
  <div class="edit" id="pin_edit">
    <input type="password" id="pin_input" maxlength="6">
    <button class="btn" onclick="save('pin')">Save</button>
    <button class="btn cancel" onclick="cancel('pin')">Cancel</button>
  </div>
</div>
<div class="field">
  <div class="show" id="security_show">
    <div><strong>Security Question:</strong><div class="info"><?=$user['question'] ? 'Question set' : 'Not set'?></div></div>
    <button class="btn edit-btn" onclick="edit('security')">Edit</button>
  </div>
  <div class="edit" id="security_edit">
    <select id="question_input" style="width:100%;margin-bottom:10px">
      <option value="">Choose question</option>
      <option value="Who was your first fictional character crush?">Who was your first fictional character crush?</option>
      <option value="What's a city that lives in your heart?">What's a city that lives in your heart?</option>
      <option value="If you could teleport to any café in the world, where would it be?">If you could teleport to any café in the world, where would it be?</option>
      <option value="What's a hobby you've always wanted to try but haven't yet?">What's a hobby you've always wanted to try but haven't yet?</option>
      <option value="Who was your favorite teacher growing up?">Who was your favorite teacher growing up?</option>
      <option value="What's a dream destination you've never visited?">What's a dream destination you've never visited?</option>
      <option value="What's a nickname only your family uses for you?">What's a nickname only your family uses for you?</option>
      <option value="What's the name of a childhood imaginary friend?">What's the name of a childhood imaginary friend?</option>
    </select>
    <input type="text" id="answer_input" placeholder="Your answer" style="width:100%">
    <button class="btn" onclick="saveSecurity()">Save</button>
    <button class="btn cancel" onclick="cancel('security')">Cancel</button>
  </div>
</div>
</div>
<?php elseif($section == '2fa'): ?>
<div class="box">
  <h2>Two-Factor Authentication (2FA)</h2>
  <?php if(!$user['2fa_enabled']): ?>
    <div id="setup_2fa">
      <button type="button" class="btn" id="enable2faBtn" onclick="setup2fa()">Enable 2FA with Authenticator App</button>
      <div id="qr_wrap" style="margin:20px 0;display:none"></div>
      <input type="text" id="code_input" placeholder="Enter 6-digit code" maxlength="6" style="display:none">
      <button type="button" class="btn" id="verify_btn" style="display:none" onclick="verify2fa()">Verify & Enable</button>
      <button type="button" class="btn cancel" id="cancel2fa" style="display:none" onclick="cancel2fa()">Cancel</button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
      function setup2fa() {
        document.getElementById('enable2faBtn').style.display = 'none';
        fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=2fa&mode=setup'})
        .then(r=>r.json()).then(d=>{
          let secret = d.secret;
          let uri = `otpauth://totp/PasswordManager:${encodeURIComponent('<?= $user['email'] ?>')}?secret=${secret}&issuer=PasswordManager`;
          let qr = new QRious({value:uri,size:180});
          document.getElementById('qr_wrap').innerHTML = `<div>Scan with app:<br><img src="${qr.toDataURL()}"></div><div>Or enter: <b>${secret}</b></div>`;
          document.getElementById('qr_wrap').style.display = 'block';
          document.getElementById('code_input').style.display = 'inline-block';
          document.getElementById('verify_btn').style.display = 'inline-block';
          document.getElementById('cancel2fa').style.display = 'inline-block';
        });
      }
    </script>
  <?php else: ?>
    <div>
      <strong>2FA is enabled.</strong>
      <button type="button" class="btn" onclick="disable2fa()">Disable 2FA</button>
    </div>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="box">
<h2>Change Password</h2>
<input type="password" id="old_password" placeholder="Current password" style="width:100%;margin:10px 0">
<input type="password" id="new_password" placeholder="New password" style="width:100%;margin:10px 0">
<input type="password" id="confirm_password" placeholder="Confirm password" style="width:100%;margin:10px 0">
<button class="btn" onclick="changePassword()" style="width:100%;margin-top:15px;">Update Password</button>
</div>
<?php endif; ?>
</div>
<div class="notify" id="notify"></div>
<script>
function showNotify(msg,type='success'){const n=document.getElementById('notify');n.textContent=msg;n.className=`notify ${type} show`;setTimeout(()=>n.classList.remove('show'),3000);}
function edit(field){document.getElementById(field+'_show').style.display='none';document.getElementById(field+'_edit').style.display='flex';}
function cancel(field){document.getElementById(field+'_show').style.display='flex';document.getElementById(field+'_edit').style.display='none';}
function save(field){
    const v=document.getElementById(field+'_input')?document.getElementById(field+'_input').value:'';
    if(field==='phone'&&v.length!==10){showNotify('Phone must be 10 digits!','error');return;}
    fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=${field}&${field}=${encodeURIComponent(v)}`})
    .then(r=>r.json()).then(d=>{showNotify(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),1000);});
}
function saveSecurity(){
    const q = document.getElementById('question_input').value;
    const a = document.getElementById('answer_input').value;
    if (!q || !a) { showNotify('Fill both fields!','error'); return; }
    fetch('',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=security&question=${encodeURIComponent(q)}&answer=${encodeURIComponent(a)}`
    })
    .then(r=>r.json())
    .then(d=>{showNotify(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),1000);});
}
function changePassword(){
    const old=document.getElementById('old_password').value,newP=document.getElementById('new_password').value,conf=document.getElementById('confirm_password').value;
    if(!old||!newP||!conf){showNotify('Fill all fields!','error');return;}
    if(newP!==conf){showNotify('Passwords do not match!','error');return;}
    fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=password&old_password=${encodeURIComponent(old)}&new_password=${encodeURIComponent(newP)}`})
    .then(r=>r.json()).then(d=>{showNotify(d.message,d.success?'success':'error');if(d.success){document.getElementById('old_password').value='';document.getElementById('new_password').value='';document.getElementById('confirm_password').value='';}});
}
function verify2fa(){
    let code=document.getElementById('code_input').value;
    fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=2fa&mode=verify&code=${code}`})
    .then(r=>r.json()).then(d=>{
        showNotify(d.message,d.success?'success':'error');
        if(d.success)setTimeout(()=>location.reload(),1000);
    });
}
function disable2fa(){
    if(confirm('Disable 2FA?')){
        fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=2fa&mode=disable'})
        .then(r=>r.json()).then(d=>{
            showNotify(d.message,'success');
            setTimeout(()=>location.reload(),1000);
        });
    }
}
function cancel2fa(){location.reload();}
</script>
</body>
</html>