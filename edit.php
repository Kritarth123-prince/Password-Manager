<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db_config.php';
$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

$stmt = $db->prepare("SELECT * FROM data WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) { header("Location: view.php"); exit; }

if (isset($_POST['update'])) {
    $stmt = $db->prepare("UPDATE data SET username=?, email=?, password=?, category=?, website=?, notes=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssssii", $_POST['username'], $_POST['email'], $_POST['password'], $_POST['category'], $_POST['website'], $_POST['notes'], $id, $user_id);
    if ($stmt->execute()) {
        header("Location: view.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit — Password Manager 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;height:100vh;}
.box{max-width:400px;width:100%;background:rgba(255,255,255,0.15);padding:20px;border-radius:15px;color:white;}
input,select,textarea{width:100%;padding:12px;margin:8px 0;border:none;border-radius:8px;}
.btn{background:#ff6b6b;color:white;padding:15px;border:none;border-radius:25px;width:100%;cursor:pointer;}
h2{text-align:center;margin-bottom:20px;}
a{color:white;text-decoration:none;display:block;text-align:center;margin-top:15px;cursor:pointer;}
.strength{height:6px;background:#ddd;border-radius:3px;margin:5px 0;display:none;}
.strength-bar{height:100%;border-radius:3px;transition:width 0.3s;}
.strength-text{font-size:11px;margin:5px 0;display:none;}
</style>
</head>
<body>
<div class="box">
    <h2>Edit Credentials</h2>
    <form method="post">
        <input name="username" placeholder="Username" value="<?= $data['username'] ?>">
        <input type="email" name="email" placeholder="Email id" value="<?= $data['email'] ?>">
        <input type="password" name="password" id="password" placeholder="Password" value="<?= $data['password'] ?>" required>
        <div class="strength" id="strength">
            <div class="strength-bar" id="bar"></div>
        </div>
        <div class="strength-text" id="text"></div>
        <input name="website" placeholder="Website or URL" value="<?= $data['website'] ?>" required>
        <select name="category" required>
            <option value="">Select Category</option>
            <option value="personal"<?= $data['category']=='personal'?' selected':'' ?>>Personal</option>
            <option value="banking"<?= $data['category']=='banking'?' selected':'' ?>>Banking</option>
            <option value="govt"<?= $data['category']=='govt'?' selected':'' ?>>Government</option>
            <option value="social"<?= $data['category']=='social'?' selected':'' ?>>Social</option>
            <option value="shopping"<?= $data['category']=='shopping'?' selected':'' ?>>Shopping</option>
            <option value="work"<?= $data['category']=='work'?' selected':'' ?>>Work</option>
            <option value="other"<?= $data['category']=='other'?' selected':'' ?>>Other</option>
        </select>
        <textarea name="notes" placeholder="Notes" rows="3"><?= $data['notes'] ?></textarea>
        <button type="submit" name="update" class="btn">Update</button>
    </form>
    <a onclick="window.location.href='view.php'">← Back to Dashboard</a>
</div>

<script>
document.getElementById('password').oninput = function() {
    let p = this.value, s = 0;
    if (!p) { 
        document.getElementById('strength').style.display = 'none';
        document.getElementById('text').style.display = 'none';
        return;
    }
    document.getElementById('strength').style.display = 'block';
    document.getElementById('text').style.display = 'block';
    
    if (p.length >= 6) s += 20;
    if (p.length >= 10) s += 20;
    if (/[a-z]/.test(p)) s += 20;
    if (/[A-Z]/.test(p)) s += 20;
    if (/[0-9]/.test(p)) s += 20;
    
    let bar = document.getElementById('bar'), text = document.getElementById('text');
    bar.style.width = s + '%';
    
    if (s <= 20) { bar.style.background = '#e74c3c'; text.textContent = 'Very Weak'; }
    else if (s <= 40) { bar.style.background = '#f39c12'; text.textContent = 'Weak'; }
    else if (s <= 60) { bar.style.background = '#f1c40f'; text.textContent = 'Medium'; }
    else if (s <= 80) { bar.style.background = '#2ecc71'; text.textContent = 'Strong'; }
    else { bar.style.background = '#27ae60'; text.textContent = 'Very Strong'; }
};
</script>
</body>
</html>