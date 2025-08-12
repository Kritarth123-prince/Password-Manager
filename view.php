<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_config.php';
$user_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file paths
$errorLogFile   = __DIR__ . '/mail_error.log';
$successLogFile = __DIR__ . '/mail_success.log';

if (isset($_POST['action']) && $_POST['action'] === 'share') {
    header('Content-Type: application/json; charset=utf-8');
    $password_id = isset($_POST['password_id']) ? intval($_POST['password_id']) : 0;
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $share_note = isset($_POST['share_note']) ? trim($_POST['share_note']) : '';
    $expire_raw = isset($_POST['expire']) ? $_POST['expire'] : '+1 hour';

    $check = $db->prepare("SELECT token FROM shared_passwords WHERE password_id = ? AND user_id = ?");
    $check->bind_param("ii", $password_id, $user_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $share_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/share.php?t=" . $existing['token'];
        echo json_encode(['success' => true, 'link' => $share_link, 'existing' => true]);
        exit;
    }

    try {
        $token = bin2hex(random_bytes(16));
    } catch (\Exception $e) {
        $token = bin2hex(openssl_random_pseudo_bytes(16));
    }

    $expire = date('Y-m-d H:i:s', strtotime($expire_raw));
    $stmt = $db->prepare("INSERT INTO shared_passwords (token, password_id, user_id, email, note, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $token, $password_id, $user_id, $email, $share_note, $expire);

    if ($stmt->execute()) {
        $share_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/share.php?t=" . $token;

        // === SEND EMAIL IF EMAIL IS PROVIDED ===
        if (!empty($email)) {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8'; // ensures proper Unicode/emoji support
            $mail->Encoding = 'base64';
            try {
                // SMTP setup
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $email_sender;
                $mail->Password = $email_password;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtp_port;

                // Recipients
                $mail->setFrom($email_sender, 'Password Manager');
                $mail->addAddress($email);

                // Email content (Stylish)
                $mail->isHTML(true);
                $mail->Subject = '🔐 You’ve Received a Secure Password Link';
                $mail->Body = '
                    <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
                        <div style="max-width: 600px; margin: auto; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd;">
                            <div style="background-color: #2c3e50; color: white; padding: 15px; text-align: center;">
                                <h2>🔐 Password Manager</h2>
                            </div>
                            <div style="padding: 20px; color: #333;">
                                <p>Hello,</p>
                                <p>' . nl2br(htmlspecialchars($share_note)) . '</p>
                                <p>Click the secure link below to view the shared password:</p>
                                <p style="text-align: center;">
                                    <a href="' . $share_link . '" style="display: inline-block; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">View Password</a>
                                </p>
                                <p>This link will expire on <strong>' . $expire . '</strong>.</p>
                            </div>
                            <div style="background-color: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #666;">
                                &copy; ' . date("Y") . ' Password Manager. All rights reserved.
                            </div>
                        </div>
                    </div>
                ';
                $mail->AltBody = "Hello,\n\n" . $share_note . "\n\nLink: " . $share_link . "\nExpires on: " . $expire;

                $mail->send();

                // Log success
                file_put_contents($successLogFile, "[" . date("Y-m-d H:i:s") . "] Mail sent to {$email} for password ID {$password_id}\n", FILE_APPEND);

            } catch (Exception $e) {
                // Log error
                file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] Error sending mail to {$email}: {$mail->ErrorInfo}\n", FILE_APPEND);
            }
        }

        echo json_encode(['success' => true, 'link' => $share_link]);
    } else {
        echo json_encode(['success' => false, 'error' => 'db_insert_failed']);
    }
    exit;
}


if (isset($_POST['delete'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $stmt = $db->prepare("DELETE FROM data WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $ok = $stmt->execute();
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => (bool)$ok]);
        exit;
    } else {
        $msg = $ok ? "deleted" : "delete_failed";
    }
}

if (isset($_POST['revoke'])) {
    $password_id = isset($_POST['password_id']) ? intval($_POST['password_id']) : 0;
    $stmt = $db->prepare("DELETE FROM shared_passwords WHERE password_id=? AND user_id=?");
    $stmt->bind_param("ii", $password_id, $user_id);
    $ok = $stmt->execute();
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => (bool)$ok]);
        exit;
    } else {
        $msg = $ok ? "revoked" : "revoke_failed";
    }
}

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM data WHERE user_id=?");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$countRow = $countStmt->get_result()->fetch_assoc();
$count = (int)$countRow['total'];
$total_pages = max(1, ceil($count / $limit));

$shared_ids = [];
$shared_stmt = $db->prepare("SELECT password_id FROM shared_passwords WHERE user_id=?");
$shared_stmt->bind_param("i", $user_id);
$shared_stmt->execute();
$shared_result = $shared_stmt->get_result();
while ($shared_row = $shared_result->fetch_assoc()) {
    $shared_ids[] = $shared_row['password_id'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Manager 🔐</title>
    <link rel="icon" href="assets/icon.png" type="image/x-icon">
    <style>
        body{background:linear-gradient(45deg,#667eea,#764ba2);font-family:Arial;margin:0;padding:0;}
        .header{display:flex;justify-content:space-between;padding:20px;}
        .logout{background:#e74c3c;color:#fff;padding:8px 16px;border:none;border-radius:20px;text-decoration:none;}
        .add{background:#2ecc71;color:#fff;padding:8px 16px;border:none;border-radius:20px;text-decoration:none;}
        .export{background:#f39c12;color:#fff;padding:8px 16px;border:none;border-radius:20px;text-decoration:none;margin-right:10px;}
        .search{padding:10px;border:none;border-radius:25px;width:300px;margin:20px auto;display:block;}
        table{width:95%;margin:auto;background:rgba(255,255,255,0.1);border-radius:10px;color:#fff;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,0.1);vertical-align:middle;}
        th{background:rgba(0,0,0,0.2);}
        .cat{padding:3px 8px;border-radius:10px;font-size:12px;}
        .personal{background:#16a085;}.banking{background:#e74c3c;}.govt{background:#3498db;}.social{background:#9b59b6;}.shopping{background:#f39c12;}.work{background:#34495e;}.other{background:#7f8c8d;}
        .btn{background:#2ecc71;color:#fff;border:none;padding:5px 8px;border-radius:5px;cursor:pointer;margin-left:2px;font-size:12px;}
        .del{background:#ff4757;}.edit{background:#5352ed;}.share{background:#9b59b6;}.revoke{background:#e67e22;}
        .link{color:#fff;text-decoration:none;margin-left:4px;}
        .msg{position:fixed;top:20px;right:20px;background:#2ecc71;color:#fff;padding:10px 15px;border-radius:10px;transform:translateX(300px);transition:transform 0.3s;}
        .msg.show{transform:translateX(0);}
        .exp{cursor:pointer;user-select:none;}
        .fade{animation:fadeOut 0.6s forwards;}
        @keyframes fadeOut{to{opacity:0;transform:translateX(-20px);}}
        .pagination{text-align:center;margin:20px 0;}
        .page-btn{background:#3498db;color:#fff;padding:8px 12px;margin:0 5px;border:none;border-radius:5px;text-decoration:none;cursor:pointer;}
        .page-btn.active{background:#2ecc71;}
        .left-group{display:flex;gap:10px;}
        .dm-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999;}
        .dm-modal{background:#fff;color:#222;padding:18px;border-radius:10px;width:360px;box-sizing:border-box;}
        .dm-modal input,.dm-modal select,.dm-modal textarea{width:100%;padding:8px;margin:6px 0;border:1px solid #ddd;border-radius:6px;box-sizing:border-box;}
        .share-link{background:#f0f0f0;padding:10px;margin-top:10px;border-radius:6px;word-break:break-all;display:block;color:#111;text-decoration:none;}
        .dm-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-radius:50%;border-top-color:white;animation:spin 0.6s linear infinite;margin-right:6px;}
        @keyframes spin{to{transform:rotate(360deg);}}
    </style>
</head>
<body>
    <div class="header">
        <div class="left-group">
            <a href="save.php" class="add">➕ Add New</a>
            <a href="?export=1" class="export">📥 Export</a>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <input type="text" id="search" class="search" placeholder="🔍 Search anything..." onkeyup="doSearch()">

    <table>
        <tr>
            <th>Website</th><th>Username</th><th>Email</th><th>Password</th><th>Category</th><th>Notes</th><th>Actions</th>
        </tr>
        <tbody id="data">
        <?php
        $stmt = $db->prepare("SELECT * FROM data WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()):
            $url = strpos($row['website'], 'http') === 0 ? $row['website'] : 'https://' . $row['website'];
            $is_shared = in_array($row['id'], $shared_ids);
            $id = (int)$row['id'];
            $website = htmlspecialchars($row['website']);
            $username = htmlspecialchars($row['username']);
            $email = htmlspecialchars($row['email']);
            $password = $row['password'];
            $category = htmlspecialchars($row['category']);
            $notes = htmlspecialchars($row['notes']);
        ?>
        <tr id="r<?=$id?>">
            <td class="exp" onclick="expandCell('w<?=$id?>', event)">
                <span id="w<?=$id?>"><?=strlen($website) > 15 ? substr($website,0,15).'..' : $website ?></span>
                <span id="w<?=$id?>_full" style="display:none"><?=$website?></span>
                <button class="link" onclick="event.stopPropagation(); window.open('<?=$url?>','_blank')">🔗</button>
            </td>
            <td class="exp" onclick="expandCell('u<?=$id?>', event)">
                <span id="u<?=$id?>"><?=strlen($username) > 12 ? substr($username,0,12).'..' : $username ?></span>
                <span id="u<?=$id?>_full" style="display:none"><?=$username?></span>
            </td>
            <td class="exp" onclick="expandCell('e<?=$id?>', event)">
                <span id="e<?=$id?>"><?=strlen($email) > 15 ? substr($email,0,15).'..' : $email ?></span>
                <span id="e<?=$id?>_full" style="display:none"><?=$email?></span>
            </td>
            <td>
                <span id="p<?=$id?>">••••••••</span>
                <button class="btn" id="eye<?=$id?>" onclick="event.stopPropagation(); showPass(<?=$id?>, '<?=htmlspecialchars($password, ENT_QUOTES)?>')">👁️</button>
                <button class="btn" onclick="event.stopPropagation(); copyPass('<?=htmlspecialchars($password, ENT_QUOTES)?>')">📋</button>
            </td>
            <td><span class="cat <?=$category?>"><?=ucfirst($category)?></span></td>
            <td class="exp" onclick="expandCell('n<?=$id?>', event)">
                <span id="n<?=$id?>"><?=strlen($notes) > 20 ? substr($notes,0,20).'..' : $notes ?></span>
                <span id="n<?=$id?>_full" style="display:none"><?=$notes?></span>
            </td>
            <td>
                <button class="btn share" onclick="event.stopPropagation(); openShare(<?=$id?>)">🔗</button>
                <?php if ($is_shared): ?>
                    <button class="btn revoke" onclick="event.stopPropagation(); revokeShare(<?=$id?>)">🚫</button>
                <?php endif; ?>
                <a href="edit.php?id=<?=$id?>" class="btn edit">🪄</a>
                <button class="btn del" onclick="event.stopPropagation(); askDel(<?=$id?>)">💣</button>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="pagination" id="pagination">
        <?php if ($total_pages > 1): ?>
            <?php if ($page > 1): ?><a href="?page=<?=$page-1?>" class="page-btn">←</a><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?=$i?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?=$page+1?>" class="page-btn">→</a><?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="msg" id="msg"></div>

    <script>
    let searchPage = 1;
    let searchQuery = '';
    let searchTotal = 0;

    function showMsg(t, time = 2000) {
        const m = document.getElementById('msg');
        m.textContent = t;
        m.classList.add('show');
        setTimeout(() => m.classList.remove('show'), time);
    }

    function doSearch() {
        let v = document.getElementById('search').value.trim();
        if (v !== searchQuery) {
            searchPage = 1;
            searchQuery = v;
        }
        
        let x = new XMLHttpRequest();
        x.open('POST', 'view_search.php', true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onload = () => {
            if (x.status === 200) {
                const response = JSON.parse(x.responseText);
                document.getElementById('data').innerHTML = response.data;
                searchTotal = response.total;
                updatePagination(v.length > 0);
            } else {
                showMsg('Search error');
            }
        };
        x.send('q=' + encodeURIComponent(v) + '&page=' + searchPage);
    }

    function updatePagination(isSearch) {
        const pag = document.getElementById('pagination');
        if (!isSearch) {
            pag.style.display = 'block';
            return;
        }

        const totalPages = Math.ceil(searchTotal / 25);
        if (totalPages <= 1) {
            pag.style.display = 'none';
            return;
        }

        let html = '';
        if (searchPage > 1) html += `<button class="page-btn" onclick="searchPrev()">←</button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === searchPage ? 'active' : ''}" onclick="searchGo(${i})">${i}</button>`;
        }
        if (searchPage < totalPages) html += `<button class="page-btn" onclick="searchNext()">→</button>`;
        
        pag.innerHTML = html;
        pag.style.display = 'block';
    }

    function searchPrev() {
        if (searchPage > 1) {
            searchPage--;
            doSearch();
        }
    }

    function searchNext() {
        const totalPages = Math.ceil(searchTotal / 25);
        if (searchPage < totalPages) {
            searchPage++;
            doSearch();
        }
    }

    function searchGo(page) {
        searchPage = page;
        doSearch();
    }

    function showPass(id, pass) {
        let el = document.getElementById('p' + id);
        let eye = document.getElementById('eye' + id);
        if (!el || !eye) return;
        
        if (el.textContent === '••••••••') {
            el.textContent = pass;
            eye.textContent = '🙈';
        } else {
            el.textContent = '••••••••';
            eye.textContent = '👁️';
        }
    }

    function copyPass(pass) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(pass).then(() => showMsg('Copied!'), () => showMsg('Copy failed'));
        } else {
            const ta = document.createElement('textarea');
            ta.value = pass;
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            showMsg('Copied!');
        }
    }

    let expandedCell = null;
    function expandCell(cellId, event) {
        event.stopPropagation();
        if (expandedCell && expandedCell !== cellId) {
            const prevShort = document.getElementById(expandedCell);
            const prevFull = document.getElementById(expandedCell + '_full');
            if (prevShort && prevFull) { prevShort.style.display = 'inline'; prevFull.style.display = 'none'; }
        }
        const shortText = document.getElementById(cellId);
        const fullText = document.getElementById(cellId + '_full');
        if (!shortText || !fullText) return;
        if (shortText.style.display === 'none') {
            shortText.style.display = 'inline'; fullText.style.display = 'none'; expandedCell = null;
        } else {
            shortText.style.display = 'none'; fullText.style.display = 'inline'; expandedCell = cellId;
        }
    }
    document.addEventListener('click', function(event){
        if (!event.target.closest('.exp') && expandedCell) {
            const shortText = document.getElementById(expandedCell);
            const fullText = document.getElementById(expandedCell + '_full');
            if (shortText && fullText) { shortText.style.display = 'inline'; fullText.style.display = 'none'; }
            expandedCell = null;
        }
    });

    const deleteTimers = {};
    function askDel(id) {
        if (deleteTimers[id]) {
            clearTimeout(deleteTimers[id]);
            deleteTimers[id] = null;
            delNow(id);
        } else {
            showMsg('Click again to delete!');
            deleteTimers[id] = setTimeout(() => { deleteTimers[id] = null; }, 10000);
        }
    }

    function delNow(id) {
        const row = document.getElementById('r' + id);
        if (!row) return;
        row.classList.add('fade');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'delete=1&id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                setTimeout(() => { row.remove(); showMsg('Deleted!'); }, 650);
            } else {
                row.classList.remove('fade');
                showMsg('Delete failed');
            }
        })
        .catch(() => {
            row.classList.remove('fade');
            showMsg('Delete error');
        });
    }

    function openShare(id) {
        const overlay = document.createElement('div');
        overlay.className = 'dm-overlay';
        overlay.innerHTML = `
            <div class="dm-modal">
                <h3 style="margin-top:0">Share Password</h3>
                <input type="hidden" id="dm_share_id" value="${id}">
                <input type="email" id="dm_share_email" placeholder="Email (optional)">
                <textarea id="dm_share_note" rows="2" placeholder="Note (optional)"></textarea>
                <select id="dm_share_expire">
                    <option value="+30 minutes">30 Minutes</option>
                    <option value="+1 hour" selected>1 Hour</option>
                    <option value="+1 day">1 Day</option>
                    <option value="+1 week">1 Week</option>
                    <option value="+1 month">1 Month</option>
                </select>
                <div style="margin-top:10px;display:flex;gap:8px">
                    <button id="dm_create_btn" style="flex:1;background:#2ecc71;color:#fff;border:none;padding:8px;border-radius:6px;cursor:pointer">Create Link</button>
                    <button id="dm_close_btn" style="flex:1;background:#e74c3c;color:#fff;border:none;padding:8px;border-radius:6px;cursor:pointer">Cancel</button>
                </div>
                <div id="dm_result" style="margin-top:10px"></div>
            </div>`;
        document.body.appendChild(overlay);

        overlay.querySelector('#dm_close_btn').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

        overlay.querySelector('#dm_create_btn').addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="dm-spinner"></span>Creating...';
            const pid = document.getElementById('dm_share_id').value;
            const mail = document.getElementById('dm_share_email').value.trim();
            const note = document.getElementById('dm_share_note').value.trim();
            const expire = document.getElementById('dm_share_expire').value;
            const body = 'action=share&password_id=' + encodeURIComponent(pid) + '&email=' + encodeURIComponent(mail) + '&expire=' + encodeURIComponent(expire) + '&share_note=' + encodeURIComponent(note);
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    document.getElementById('dm_result').innerHTML = `<a class="share-link" href="${res.link}" target="_blank">${res.link}</a>`;
                    showMsg(res.existing ? 'Already shared' : 'Link created!');
                    const row = document.getElementById('r' + pid);
                    if (row && !row.querySelector('.btn.revoke')) {
                        const revokeBtn = document.createElement('button');
                        revokeBtn.className = 'btn revoke';
                        revokeBtn.textContent = '🚫';
                        revokeBtn.onclick = function (ev) { ev.stopPropagation(); revokeShare(pid); };
                        row.querySelector('td:last-child').insertBefore(revokeBtn, row.querySelector('a.edit'));
                    }
                    btn.innerHTML = '✔ Created';
                } else {
                    btn.innerHTML = '❌ Failed';
                    showMsg('Share failed');
                }
                setTimeout(() => { btn.disabled = false; btn.innerHTML = 'Create Link'; }, 2000);
            })
            .catch(() => {
                btn.innerHTML = '❌ Error';
                showMsg('Share error');
                setTimeout(() => { btn.disabled = false; btn.innerHTML = 'Create Link'; }, 2000);
            });
        });
    }

    function revokeShare(id) {
        if (!confirm('Stop sharing?')) return;
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'revoke=1&password_id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                const row = document.getElementById('r' + id);
                if (row) {
                    const revokeBtn = row.querySelector('.btn.revoke');
                    if (revokeBtn) revokeBtn.remove();
                }
                showMsg('Stopped sharing');
            } else {
                showMsg('Failed to stop sharing');
            }
        })
        .catch(() => showMsg('Revoke error'));
    }
    </script>
</body>
</html>