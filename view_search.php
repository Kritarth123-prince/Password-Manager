<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['data' => '', 'total' => 0]));
}
require 'db_config.php';

function decryptPassword($encrypted_password, $created_at) {
    $encryption_key = $created_at;
    $iv = substr(md5($encryption_key), 0, 16);
    return openssl_decrypt($encrypted_password, 'AES-256-CBC', $encryption_key, 0, $iv);
}

$user_id = $_SESSION['user_id'];
$q = isset($_POST['q']) ? trim($_POST['q']) : '';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Get all data first
$data_sql = "SELECT * FROM data WHERE user_id = ? ORDER BY id DESC";
$data_stmt = $db->prepare($data_sql);
$data_stmt->bind_param("i", $user_id);
$data_stmt->execute();
$all_results = $data_stmt->get_result();

// Filter results
$matching_rows = [];
while ($row = $all_results->fetch_assoc()) {
    $decrypted = decryptPassword($row['password'], $row['created_at']);
    
    if ($q === '' || 
        stripos($row['website'], $q) !== false ||
        stripos($row['username'], $q) !== false ||
        stripos($row['email'], $q) !== false ||
        stripos($decrypted, $q) !== false ||
        stripos($row['notes'], $q) !== false ||
        stripos($row['category'], $q) !== false) {
        $matching_rows[] = $row;
    }
}

$total = count($matching_rows);
$paginated = array_slice($matching_rows, $offset, $limit);

// Get shared IDs
$shared_ids = [];
$shared_stmt = $db->prepare("SELECT password_id FROM shared_passwords WHERE user_id=?");
$shared_stmt->bind_param("i", $user_id);
$shared_stmt->execute();
$shared_result = $shared_stmt->get_result();
while ($shared_row = $shared_result->fetch_assoc()) {
    $shared_ids[] = $shared_row['password_id'];
}

// Build HTML
$html = '';
foreach ($paginated as $row) {
    $id = (int)$row['id'];
    $website = htmlspecialchars($row['website'] ?? '');
    $url = strpos($row['website'], 'http') === 0 ? $row['website'] : 'https://' . $row['website'];
    $username = htmlspecialchars($row['username'] ?? '');
    $email = htmlspecialchars($row['email'] ?? '');
    $password = htmlspecialchars(decryptPassword($row['password'], $row['created_at']), ENT_QUOTES);
    $category = htmlspecialchars($row['category'] ?? '');
    $notes = htmlspecialchars($row['notes'] ?? '');
    $is_shared = in_array($id, $shared_ids);

    $html .= "<tr id='r{$id}'>
        <td class='exp' onclick=\"expandCell('w{$id}', event)\">
            <span id='w{$id}'>" . (strlen($website) > 15 ? substr($website, 0, 15) . '..' : $website) . "</span>
            <span id='w{$id}_full' style='display:none'>{$website}</span>
            <button class='link' onclick=\"event.stopPropagation(); window.open('{$url}','_blank')\">🔗</button>
        </td>
        <td class='exp' onclick=\"expandCell('u{$id}', event)\">
            <span id='u{$id}'>" . (strlen($username) > 12 ? substr($username, 0, 12) . '..' : $username) . "</span>
            <span id='u{$id}_full' style='display:none'>{$username}</span>
        </td>
        <td class='exp' onclick=\"expandCell('e{$id}', event)\">
            <span id='e{$id}'>" . (strlen($email) > 15 ? substr($email, 0, 15) . '..' : $email) . "</span>
            <span id='e{$id}_full' style='display:none'>{$email}</span>
        </td>
        <td>
            <span id='p{$id}'>••••••••</span>
            <button class='btn' id='eye{$id}' onclick=\"event.stopPropagation(); showPass({$id}, '{$password}')\">👁️</button>
            <button class='btn' onclick=\"event.stopPropagation(); copyPass('{$password}')\">📋</button>
        </td>
        <td><span class='cat {$category}'>" . ucfirst($category) . "</span></td>
        <td class='exp' onclick=\"expandCell('n{$id}', event)\">
            <span id='n{$id}'>" . (strlen($notes) > 20 ? substr($notes, 0, 20) . '..' : $notes) . "</span>
            <span id='n{$id}_full' style='display:none'>{$notes}</span>
        </td>
        <td>
            <button class='btn share' onclick=\"event.stopPropagation(); openShare({$id})\">🔗</button>";
    if ($is_shared) {
        $html .= "<button class='btn revoke' onclick=\"event.stopPropagation(); revokeShare({$id})\">🚫</button>";
    }
    $html .= "<a href='edit.php?id={$id}' class='btn edit'>🪄</a>
            <button class='btn del' onclick=\"event.stopPropagation(); askDel({$id})\">💣</button>
        </td>
    </tr>";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => $html, 'total' => $total]);
?>