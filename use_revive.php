<?php
require_once 'includes/config.php';
if (!isLoggedIn()) { http_response_code(401); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];
try {
    // Check current
    $st = $db->prepare("SELECT so_luong FROM user_items WHERE taikhoan_id=? AND item_type='revive'");
    $st->execute([$uid]);
    $row = $st->fetch();
    if ($row && $row['so_luong'] > 0) {
        $db->prepare("UPDATE user_items SET so_luong=so_luong-1 WHERE taikhoan_id=? AND item_type='revive'")->execute([$uid]);
    }
    echo json_encode(['ok'=>true,'remaining'=>max(0,($row['so_luong']??1)-1)]);
} catch(Exception $e) { echo json_encode(['ok'=>true]); }
