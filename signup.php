<?php
session_start();
require 'db_config.php';
$msg='';
if(isset($_POST['signup'])){
$name=$_POST['name'];
$email=$_POST['email'];
$pass=password_hash($_POST['password'],PASSWORD_BCRYPT);
$phone=$_POST['phone'];
$dob=$_POST['dob'];
$profession=$_POST['profession'];
$question=$_POST['question'];
$answer=$_POST['answer'];
$pin=$_POST['pin'];
$age=date_diff(date_create($dob),date_create('today'))->y;
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
$msg="Enter valid email!";
}elseif(strlen($phone)!=10||!ctype_digit($phone)){
$msg="Phone must be 10 digits!";
}elseif(strlen($_POST['password'])<8){
$msg="Password must be 8+ characters!";
}elseif($age<10){
$msg="You must be 10+ years old!";
}elseif(!isValidPin($pin,$phone)){
$msg="PIN too simple or matches phone!";
}else{
$stmt=$db->prepare("INSERT INTO members (name,email,password,phone,dob,profession,question,answer,pin) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("sssssssss",$name,$email,$pass,$phone,$dob,$profession,$question,$answer,$pin);
if($stmt->execute()){
    $msg = "Account created! Redirecting to login...";
    echo "<script>
        setTimeout(function(){
            window.location.href = 'login.php?signup=success';
        }, 2000);
    </script>";
} else {
    $msg = "Signup failed!";
}


}
}
function isValidPin($pin,$phone){
if(strlen($pin)!=6||!ctype_digit($pin))return false;
$bad=['123456','654321','012345','543210','111111','222222','333333','444444','555555','666666','777777','888888','999999','000000'];
if(in_array($pin,$bad))return false;
if(strpos($phone,$pin)!==false||strpos($pin,$phone)!==false)return false;
return true;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Signup — Password Manager 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(60deg,#43cea2,#185a9d);font-family:Arial;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#fff2;padding:20px;border-radius:15px;max-width:340px;width:100%;color:#fff}
input,select{width:100%;padding:10px;margin:5px 0;border:none;border-radius:8px;box-sizing:border-box}
.btn{background:#43cea2;color:#fff;padding:12px;border:none;border-radius:25px;width:100%;cursor:pointer;margin:10px 0}
h2{text-align:center;margin:10px 0}
a{display:block;text-align:center;color:#fff;margin:10px 0;text-decoration:none}
.notify{position:fixed;top:20px;right:20px;padding:15px;border-radius:8px;color:#fff;transform:translateX(400px);transition:transform 0.3s;z-index:1000}
.notify.show{transform:translateX(0)}
.error{background:#f44336}
.success{background:#4CAF50}
.info{background:#2196F3;padding:8px;border-radius:8px;margin:8px 0;text-align:center;font-size:12px}
.strength{margin:5px 0;font-size:12px;text-align:center}
.very-weak{color:#ff4444}
.weak{color:#ff8800}
.fair{color:#ffaa00}
.good{color:#88dd00}
.strong{color:#00dd44}
input[type="date"]:not(.has-value)::before {
  content: attr(placeholder);
  color: #aaa;
  margin-right: 0.5em;
}
</style>
</head>
<body>
<div class="box">
<h2>Sign Up</h2>
<form method="post">
<input name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email Address" required>
<input id="phone" name="phone" placeholder="Phone (10 digits)" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
<input type="password" name="password" placeholder="Password (8+ chars)" minlength="8" oninput="checkStrength(this.value)" required>
<div id="strength" class="strength"></div>
<div class="info">📝 Chill — this helps you log back in.</div>
<input type="date" id="dob" name="dob" placeholder="Date of Birth" max="" required>
<select name="profession" required>
<option value="">Select Profession</option>
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
<select name="question" required>
<option value="">Security Question</option>
<option value="Who was your first fictional character crush?">Who was your first fictional character crush?</option>
<option value="What's a city that lives in your heart?">What's a city that lives in your heart?</option>
<option value="If you could teleport to any café in the world, where would it be?">If you could teleport to any café in the world, where would it be?</option>
<option value="What's a hobby you've always wanted to try but haven't yet?">What's a hobby you've always wanted to try but haven't yet?</option>
<option value="Who was your favorite teacher growing up?">Who was your favorite teacher growing up?</option>
<option value="What's a dream destination you've never visited?">What's a dream destination you've never visited?</option>
<option value="What's a nickname only your family uses for you?">What's a nickname only your family uses for you?</option>
<option value="What's the name of a childhood imaginary friend?">What's the name of a childhood imaginary friend?</option>
</select>
<input name="answer" placeholder="Your Answer" required>
<input id="pin" name="pin" placeholder="6 Digit PIN (not 123456)" maxlength="6" oninput="validatePin(this)" required>
<button class="btn" name="signup">Create Account</button>
</form>
<a href="login.php">Already have an account?</a>
</div>
<div id="notify" class="notify"></div>
<script>
let today=new Date();
let tenYearsAgo=new Date(today.getFullYear()-10,today.getMonth(),today.getDate());
document.getElementById('dob').max=tenYearsAgo.toISOString().split('T')[0];

function showNotify(msg,type){
let n=document.getElementById('notify');
n.textContent=msg;
n.className='notify '+type+' show';
setTimeout(()=>n.classList.remove('show'),3000);
}

function checkAge(){
let dob=new Date(document.getElementById('dob').value);
let age=(new Date()-dob)/31557600000;
if(age<10){
showNotify('You must be 10+ years old!','error');
document.getElementById('dob').value='';
return false;
}
return true;
}

function validatePin(input){
input.value=input.value.replace(/[^0-9]/g,'');
let pin=input.value;
let phone=document.getElementById('phone').value;
if(pin.length==6){
let bad=['123456','654321','012345','543210','111111','222222','333333','444444','555555','666666','777777','888888','999999','000000'];
if(bad.includes(pin)){
showNotify('PIN too simple! Avoid 123456, 111111, etc.','error');
input.value='';
return;
}
if(phone.includes(pin)||pin.includes(phone.substring(0,6))){
showNotify('PIN cannot match your phone number!','error');
input.value='';
return;
}
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

document.addEventListener("DOMContentLoaded", function () {
  const dob = document.getElementById("dob");

  function toggleDatePlaceholder() {
    if (dob.value) {
      dob.classList.add("has-value");
    } else {
      dob.classList.remove("has-value");
    }
  }

  dob.addEventListener("change", toggleDatePlaceholder);
  dob.addEventListener("input", toggleDatePlaceholder);

  // Run on load in case date is pre-filled
  toggleDatePlaceholder();
});

<?php if($msg): ?>
showNotify('<?=$msg?>','<?=strpos($msg,"created")?"success":"error"?>');
<?php endif; ?>
</script>
</body>
</html>