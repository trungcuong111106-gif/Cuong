<?php
require_once 'includes/config.php';
if (!isLoggedIn()) { http_response_code(401); exit; }
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];
$score = intval($data['score'] ?? 0);
$best_tile = intval($data['best_tile'] ?? 0);
try {
    $db->prepare("INSERT INTO merge_leaderboard (taikhoan_id,best_score,best_tile) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE 
        best_score=IF(VALUES(best_score)>best_score,VALUES(best_score),best_score),
        best_tile=IF(VALUES(best_tile)>best_tile,VALUES(best_tile),best_tile),
        cap_nhat=NOW()")
    ->execute([$uid,$score,$best_tile]);
    echo json_encode(['ok'=>true]);
} catch(Exception $e){ echo json_encode(['ok'=>false,'err'=>$e->getMessage()]); }
