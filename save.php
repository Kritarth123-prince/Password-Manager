<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db_config.php';
$user_id = $_SESSION['user_id'];
$msg = '';

if (isset($_POST['save'])) {
    $stmt = $db->prepare("INSERT INTO data (user_id, username, email, password, category, website, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $_POST['username'], $_POST['email'], $_POST['password'], $_POST['category'], $_POST['website'], $_POST['notes']);
    $msg = $stmt->execute() ? 'saved' : 'error';
}

if (isset($_POST['import']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $imported = 0;
    
    if ($ext === 'json') {
        // Firefox JSON format
        $data = json_decode(file_get_contents($file['tmp_name']), true);
        if ($data && isset($data['logins'])) {
            foreach ($data['logins'] as $login) {
                if (isset($login['formActionOrigin'], $login['username'], $login['password'])) {
                    $stmt = $db->prepare("INSERT INTO data (user_id, username, password, website, category, notes) VALUES (?, ?, ?, ?, 'other', 'Imported from Firefox')");
                    $stmt->bind_param("isss", $user_id, $login['username'], $login['password'], $login['formActionOrigin']);
                    if ($stmt->execute()) $imported++;
                }
            }
        }
    } else {
        // CSV format (Chrome/Brave export)
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            $header = fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) >= 3 && !empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
                    $stmt = $db->prepare("INSERT INTO data (user_id, username, password, website, category, notes) VALUES (?, ?, ?, ?, 'other', 'Imported from browser')");
                    $stmt->bind_param("isss", $user_id, $row[1], $row[2], $row[0]);
                    if ($stmt->execute()) $imported++;
                }
            }
            fclose($handle);
        }
    }
    $msg = $imported > 0 ? "imported_$imported" : 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Password Manager 🔐</title>
<link rel="icon" href="assets/icon.png" type="image/x-icon">
<style>
body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.container{display:flex;gap:20px;max-width:900px;width:100%;}
.box{background:rgba(255,255,255,0.15);padding:25px;border-radius:15px;color:white;min-width:400px;}
.import-box{min-width:300px;}
input,select,textarea{width:100%;padding:12px;margin:8px 0;border:none;border-radius:8px;box-sizing:border-box;}
.btn{background:#ff6b6b;color:white;padding:15px;border:none;border-radius:25px;width:100%;cursor:pointer;margin:10px 0;}
.import-btn{background:#3498db;}
h2{text-align:center;margin-bottom:20px;}
a{color:white;text-decoration:none;display:block;text-align:center;margin-top:15px;}
.strength{height:6px;background:#ddd;border-radius:3px;margin:5px 0;display:none;}
.strength-bar{height:100%;border-radius:3px;transition:width 0.3s;}
.strength-text{font-size:11px;margin:5px 0;display:none;}
.file-input{background:white;color:#333;}
.info{font-size:12px;color:#ddd;margin:10px 0;line-height:1.4;}
</style>
</head>
<body>
<div class="container">
    <div class="box">
        <h2>Add New Credentials</h2>
        <form method="post">
            <input name="username" placeholder="Username">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <div class="strength" id="strength"><div class="strength-bar" id="bar"></div></div>
            <div class="strength-text" id="text"></div>
            <input name="website" placeholder="Website" required>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="personal">Personal</option>
                <option value="banking">Banking</option>
                <option value="govt">Government</option>
                <option value="social">Social</option>
                <option value="shopping">Shopping</option>
                <option value="work">Work</option>
                <option value="other">Other</option>
            </select>
            <textarea name="notes" placeholder="Notes" rows="3"></textarea>
            <button type="submit" name="save" class="btn">Save Password</button>
        </form>
        <a href="view.php">← Back to Dashboard</a>
    </div>
    
    <div class="box import-box">
        <h2>Import Passwords</h2>
        <div class="info">
            <strong>Firefox:</strong> Export as JSON file<br>
            <strong>Chrome/Brave:</strong> Export as CSV file
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" class="file-input" accept=".json,.csv" required>
            <button type="submit" name="import" class="btn import-btn">Import Passwords</button>
        </form>
        <div class="info">
            Only website, username, and password will be imported. Category set to "Other".
        </div>
    </div>
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

<?php if($msg === 'saved'): ?>
alert('Password saved!'); window.location = 'view.php';
<?php elseif($msg === 'error'): ?>
alert('Error occurred!');
<?php elseif(strpos($msg, 'imported_') === 0): ?>
alert('<?=substr($msg,9)?> passwords imported!'); window.location = 'view.php';
<?php endif; ?>
</script>
</body>
</html>