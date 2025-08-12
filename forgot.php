<?php
session_start();
require 'db_config.php';

// Session timeout: 15 minutes
if (isset($_SESSION['reset_start']) && time() - $_SESSION['reset_start'] > 900) {
    unset($_SESSION['reset_user'], $_SESSION['reset_email'], $_SESSION['reset_progress'], $_SESSION['reset_start']);
}

$msg = '';
$step = 1;

// Initialize reset progress if not set
if (!isset($_SESSION['reset_progress'])) {
    $_SESSION['reset_progress'] = 1;
}

// Step 1: Email check
if (isset($_POST['check_email'])) {
    $email = trim($_POST['email']);
    $stmt = $db->prepare("SELECT * FROM members WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $_SESSION['reset_user'] = $user;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_progress'] = 2;
        $_SESSION['reset_start'] = time();
        $step = 2;
    } else {
        $msg = "Email not found!";
    }
}

// Step 2: Verify identity
if (isset($_POST['verify']) && $_SESSION['reset_progress'] === 2) {
    $user = $_SESSION['reset_user'];
    $method = $_POST['method'];

    if ($method == 'pin') {
        if ($_POST['pin'] === $user['pin']) {
            $_SESSION['reset_progress'] = 3;
            $step = 3;
        } else {
            $msg = "Wrong PIN!";
            $step = 2;
        }
    } else {
        // Compare only date portion of DOB
        $input_dob = date('Y-m-d', strtotime($_POST['dob']));
        $stored_dob = date('Y-m-d', strtotime($user['dob']));
        $age = date_diff(date_create($input_dob), date_create('today'))->y;

        $match = (
            $age >= 10 &&
            $input_dob === $stored_dob &&
            strcasecmp($_POST['profession'], $user['profession']) === 0 &&
            $_POST['question'] === $user['question'] &&
            strcasecmp($_POST['answer'], $user['answer']) === 0
        );

        if ($match) {
            $_SESSION['reset_progress'] = 3;
            $step = 3;
        } else {
            $msg = "Details don't match or age under 10!";
            $step = 2;
        }
    }
}

// Step 3: Reset password
if (isset($_POST['reset_password']) && $_SESSION['reset_progress'] === 3) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if ($new_pass === $confirm_pass && strlen($new_pass) >= 8) {
        $pass = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE members SET password=? WHERE email=?");
        $stmt->bind_param("ss", $pass, $_SESSION['reset_email']);
        if ($stmt->execute()) {
            unset($_SESSION['reset_user'], $_SESSION['reset_email'], $_SESSION['reset_progress'], $_SESSION['reset_start']);
            $step = 4;
            $msg = "Password updated!";
        } else {
            $msg = "Update failed!";
            $step = 3;
        }
    } else {
        $msg = "Passwords don't match or too short!";
        $step = 3;
    }
}

// Enforce correct step order
if (isset($_SESSION['reset_progress']) && $step !== 4) {
    $step = $_SESSION['reset_progress'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#fff2;padding:20px;border-radius:15px;max-width:300px;width:100%;color:#fff}
input,select{width:100%;padding:10px;margin:5px 0;border:none;border-radius:8px;box-sizing:border-box}
.btn{background:#764ba2;color:#fff;padding:12px;border:none;border-radius:25px;width:100%;cursor:pointer;margin:10px 0}
h2{text-align:center;margin:10px 0}
a{display:block;text-align:center;color:#fff;margin:10px 0;text-decoration:none}
.notify{position:fixed;top:20px;right:20px;padding:15px;border-radius:8px;color:#fff;transform:translateX(400px);transition:transform 0.3s;z-index:1000}
.notify.show{transform:translateX(0)}
.error{background:#f44336}
.success{background:#4CAF50}
.tabs{display:flex;gap:5px;margin:10px 0}
.tab{flex:1;background:#fff1;padding:8px;text-align:center;cursor:pointer;border-radius:8px;font-size:13px}
.tab.active{background:#764ba2}
.strength{margin:5px 0;font-size:12px;text-align:center}
.very-weak{color:#ff4444}.weak{color:#ff8800}.fair{color:#ffaa00}.good{color:#88dd00}.strong{color:#00dd44}
</style>
</head>
<body>
<div class="box">
<?php if($step==1):?>
<h2>Reset Password</h2>
<form method="post">
<input type="email" name="email" placeholder="Enter your email" required>
<button class="btn" name="check_email">Next</button>
</form>

<?php elseif($step==2):?>
<h2>Verify Identity</h2>
<div class="tabs">
<div class="tab active" onclick="show('pin', this)">PIN</div>
<div class="tab" onclick="show('details', this)">Details</div>
</div>
<form method="post">
<input type="hidden" name="method" value="pin" id="method">
<div id="pin_box">
<input name="pin" placeholder="6 digit PIN" maxlength="6" required>
</div>
<div id="details_box" style="display:none">
<input type="date" name="dob" id="dob" max="">
<select name="profession">
<option value="">Your Profession</option>
<option value="Student">Student</option>
<option value="Teacher">Teacher</option>
<option value="Doctor">Doctor</option>
<option value="Engineer">Engineer</option>
<option value="Business">Business</option>
<option value="Freelancer">Freelancer</option>
<option value="Homemaker">Homemaker</option>
<option value="Developer">Developer</option>
<option value="Designer">Designer</option>
<option value="Manager">Manager</option>
<option value="Other">Other</option>
</select>
<select name="question">
<option value="">Pick Your Question</option>
<option value="Who was your first fictional character crush?">Who was your first fictional character crush?</option>
<option value="What's a city that lives in your heart?">What's a city that lives in your heart?</option>
<option value="If you could teleport to any café in the world, where would it be?">If you could teleport to any café in the world, where would it be?</option>
<option value="What's a hobby you've always wanted to try but haven't yet?">What's a hobby you've always wanted to try but haven't yet?</option>
<option value="Who was your favorite teacher growing up?">Who was your favorite teacher growing up?</option>
<option value="What's a dream destination you've never visited?">What's a dream destination you've never visited?</option>
<option value="What's a nickname only your family uses for you?">What's a nickname only your family uses for you?</option>
<option value="What's the name of a childhood imaginary friend?">What's the name of a childhood imaginary friend?</option>
</select>
<input name="answer" placeholder="Your answer">
</div>
<button class="btn" name="verify">Verify</button>
</form>

<?php elseif($step==3):?>
<h2>New Password</h2>
<form method="post" id="passwordForm">
<input type="password" name="new_pass" placeholder="New password (8+ chars)" minlength="8" oninput="checkStrength(this.value)" required>
<div id="strength" class="strength"></div>
<input type="password" name="confirm_pass" placeholder="Confirm password" minlength="8" required>
<button class="btn" name="reset_password">Save Password</button>
</form>

<?php else:?>
<h2>Success!</h2>
<p>Password changed successfully!</p>
<a href="login.php" class="btn" style="text-decoration:none">Login Now</a>
<?php endif;?>
<a href="login.php">← Back to Login</a>
</div>
<div id="notify" class="notify"></div>
<script>
let today=new Date();
let tenYears=new Date(today.getFullYear()-10,today.getMonth(),today.getDate());
document.getElementById('dob')?.setAttribute('max',tenYears.toISOString().split('T')[0]);

function toggleVerificationFields(type){
  const details = document.querySelectorAll('#details_box input, #details_box select');
  details.forEach(el => {
    el.disabled = (type !== 'details');
    el.required = (type === 'details');
  });

  const pin = document.querySelector('#pin_box input[name="pin"]');
  if (pin) {
    pin.disabled = (type !== 'pin');
    pin.required = (type === 'pin');
  }
}

function show(type, el){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('method').value=type;
  document.getElementById('pin_box').style.display=type=='pin'?'block':'none';
  document.getElementById('details_box').style.display=type=='details'?'block':'none';
  toggleVerificationFields(type);
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function(){
  const startType = document.getElementById('method')?.value || 'pin';
  toggleVerificationFields(startType);
});

function checkAge(){
let dob=new Date(document.getElementById('dob').value);
let age=(new Date()-dob)/31557600000;
if(age<10){
showNotify('Must be 10+ years old!','error');
document.getElementById('dob').value='';
}
}

function checkStrength(p){
let s=0,t,c;
if(p.length>=8)s++;
if(p.length>=12)s++;
if(/[a-z]/.test(p))s++;
if(/[A-Z]/.test(p))s++;
if(/[0-9]/.test(p))s++;
if(/[^A-Za-z0-9]/.test(p))s++;
if(s<=1){t='Too weak — let’s make it stronger!';c='very-weak'}
else if(s<=2){t='Almost there, add some variety!';c='weak'}
else if(s<=3){t='Good effort — getting safer!';c='fair'}
else if(s<=4){t='Strong password — you’re secure!';c='good'}
else{t='Excellent! Your password is rock solid.';c='strong'}
document.getElementById('strength').innerHTML='<span class="'+c+'">'+t+'</span>';
}

function showNotify(msg,type='error'){
let n=document.getElementById('notify');n.textContent=msg;n.className='notify '+type+' show';
setTimeout(()=>n.classList.remove('show'),3000);
}

// Client-side password match check
document.getElementById('passwordForm')?.addEventListener('submit', function(e){
let pass1=document.querySelector('input[name="new_pass"]').value;
let pass2=document.querySelector('input[name="confirm_pass"]').value;
if(pass1!==pass2){
e.preventDefault();
showNotify("Passwords do not match!", "error");
}
});

<?php if($msg):?>showNotify('<?=$msg?>','<?=$step==4?"success":"error"?>');<?php endif;?>
</script>
</body>
</html>
