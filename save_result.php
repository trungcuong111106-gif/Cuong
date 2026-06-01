<?php
require_once 'includes/config.php';
if (!isLoggedIn()) { http_response_code(401); exit; }
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];
$linhvuc_id    = intval($data['linhvuc_id'] ?? 0);
$sotien        = intval($data['sotien'] ?? 0);
$cau_dat_duoc  = intval($data['cau_dat_duoc'] ?? 0);
$so_trogiup    = intval($data['so_trogiup'] ?? 0);
$tong_thoigian = intval($data['tong_thoigian'] ?? 0);
$ketqua        = ($data['ketqua']==='Thắng') ? 'Thắng' : 'Thua';
$loai_choi     = !empty($data['is_event']) ? 'event' : 'normal';
$da_hoi_sinh   = !empty($data['revive_used']) ? 1 : 0;
try {
    $db->prepare("INSERT INTO luotchoi (taikhoan_id,linhvuc_id,sotien,cau_dat_duoc,so_trogiup,tong_thoigian,ketqua,loai_choi,da_hoi_sinh) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$uid,$linhvuc_id,$sotien,$cau_dat_duoc,$so_trogiup,$tong_thoigian,$ketqua,$loai_choi,$da_hoi_sinh]);
    $luotchoi_id = $db->lastInsertId();
    $ll = $data['lifelines'] ?? [];
    $ll_map = ['l5050'=>1,'audience'=>2,'phone'=>3];
    foreach ($ll_map as $key=>$tid) {
        if (!empty($ll[$key])) {
            $db->prepare("INSERT INTO sudung_trogiup (luotchoi_id,trogiup_id) VALUES (?,?)")->execute([$luotchoi_id,$tid]);
        }
    }
    // Restore 1 revive daily (if not already reset today)
    if ($loai_choi==='event') {
        $chk = $db->prepare("SELECT so_luong,DATE(cap_nhat) as last_date FROM user_items WHERE taikhoan_id=? AND item_type='revive'");
        $chk->execute([$uid]); $row=$chk->fetch();
        if (!$row) {
            $db->prepare("INSERT INTO user_items (taikhoan_id,item_type,so_luong) VALUES (?,?,?)")->execute([$uid,'revive',1]);
        } elseif ($row['last_date'] < date('Y-m-d') && $row['so_luong']<1) {
            $db->prepare("UPDATE user_items SET so_luong=1 WHERE taikhoan_id=? AND item_type='revive'")->execute([$uid]);
        }
    }
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
