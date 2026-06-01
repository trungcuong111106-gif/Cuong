<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('auth.php');
$user = getCurrentUser();
if (!$user || !is_array($user)) { session_destroy(); redirect('auth.php'); }
$db = getDB();
try {
    $history = $db->prepare("SELECT l.*, lv.tenlinhvuc, lv.icon FROM luotchoi l LEFT JOIN linhvuc lv ON l.linhvuc_id=lv.id WHERE l.taikhoan_id=? ORDER BY l.ngaychoi DESC LIMIT 50");
    $history->execute([$user['id']]);
    $rows = $history->fetchAll();
} catch(Exception $e){ $rows=[]; }
// Stats
try {
    $st = $db->prepare("SELECT COUNT(*) as tong, SUM(CASE WHEN ketqua='Thắng' THEN 1 ELSE 0 END) as thang, MAX(sotien) as max_tien, SUM(sotien) as tong_tien, AVG(cau_dat_duoc) as avg_cau FROM luotchoi WHERE taikhoan_id=?");
    $st->execute([$user['id']]); $summary = $st->fetch();
} catch(Exception $e){ $summary=['tong'=>0,'thang'=>0,'max_tien'=>0,'tong_tien'=>0,'avg_cau'=>0]; }
function formatMoney($n){if($n>=1000000000)return number_format($n/1000000000,1).' Tỷ đ';if($n>=1000000)return number_format($n/1000000,0).' Triệu đ';return number_format($n).' đ';}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lịch Sử Chơi – Ai Là Tỉ Phú</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&family=Cinzel+Decorative:wght@700&display=swap" rel="stylesheet">
<style>
:root{--gold:#FFD700;--dark:#0a0612;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Rajdhani',sans-serif;background:var(--dark);color:#fff;min-height:100vh;}
.bg{position:fixed;inset:0;background:radial-gradient(ellipse at 20% 40%,rgba(155,89,182,.1) 0%,transparent 55%),var(--dark);z-index:0;}
header{position:sticky;top:0;z-index:100;background:rgba(255,215,0,.04);border-bottom:1px solid rgba(255,215,0,.12);padding:13px 22px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(12px);}
.logo{font-family:'Cinzel Decorative',serif;font-size:1rem;background:linear-gradient(135deg,#FFD700,#FFA500);-webkit-background-clip:text;-webkit-text-fill-color:transparent;filter:drop-shadow(0 0 8px rgba(255,165,0,.4));}
.btn-back{font-family:'Orbitron',sans-serif;font-size:.53rem;letter-spacing:1px;color:rgba(255,215,0,.7);text-decoration:none;border:1px solid rgba(255,215,0,.25);border-radius:20px;padding:6px 13px;transition:all .3s;}
.btn-back:hover{border-color:var(--gold);color:var(--gold);}
.container{position:relative;z-index:10;max-width:960px;margin:0 auto;padding:26px 14px;}
h1{font-family:'Orbitron',sans-serif;font-size:1rem;letter-spacing:4px;color:rgba(255,215,0,.7);text-align:center;margin-bottom:24px;}
/* Summary cards */
.summary-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:28px;}
.sum-card{background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.13);border-radius:13px;padding:14px 16px;text-align:center;}
.sum-num{font-family:'Orbitron',sans-serif;font-size:1.2rem;font-weight:900;color:#FFD700;display:block;line-height:1.1;}
.sum-label{font-size:.7rem;color:rgba(255,255,255,.38);margin-top:5px;letter-spacing:1px;}
/* Filter tabs */
.filter-row{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.filter-btn{font-family:'Orbitron',sans-serif;font-size:.52rem;letter-spacing:1px;padding:6px 14px;border-radius:20px;border:1px solid rgba(255,215,0,.2);background:transparent;color:rgba(255,215,0,.5);cursor:pointer;transition:all .3s;}
.filter-btn.active,.filter-btn:hover{background:rgba(255,215,0,.1);border-color:rgba(255,215,0,.5);color:var(--gold);}
/* Table */
.empty{text-align:center;padding:50px;color:rgba(255,255,255,.3);font-size:.95rem;}
.history-table{width:100%;border-collapse:separate;border-spacing:0 7px;}
.history-table th{font-family:'Orbitron',sans-serif;font-size:.52rem;letter-spacing:2px;color:rgba(255,215,0,.38);text-align:left;padding:7px 14px;text-transform:uppercase;}
.history-table td{padding:13px 14px;background:rgba(255,215,0,.025);border-top:1px solid rgba(255,215,0,.07);border-bottom:1px solid rgba(255,215,0,.07);}
.history-table td:first-child{border-left:1px solid rgba(255,215,0,.07);border-radius:11px 0 0 11px;}
.history-table td:last-child{border-right:1px solid rgba(255,215,0,.07);border-radius:0 11px 11px 0;}
.history-table tr:hover td{background:rgba(255,215,0,.04);}
.win-badge{background:rgba(46,213,115,.15);color:#2ed573;border:1px solid rgba(46,213,115,.28);padding:3px 9px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:.52rem;letter-spacing:1px;}
.lose-badge{background:rgba(255,71,87,.08);color:#ff6b6b;border:1px solid rgba(255,71,87,.22);padding:3px 9px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:.52rem;letter-spacing:1px;}
.event-tag{font-family:'Orbitron',sans-serif;font-size:.45rem;background:rgba(46,213,115,.1);color:#2ed573;border:1px solid rgba(46,213,115,.25);padding:2px 6px;border-radius:10px;margin-left:5px;}
.prize-cell{font-family:'Orbitron',sans-serif;font-size:.72rem;font-weight:700;color:var(--gold);}
.topic-cell{display:flex;align-items:center;gap:7px;font-weight:600;font-size:.88rem;}
.date-cell{font-size:.78rem;color:rgba(255,255,255,.38);}
.num-cell{font-family:'Orbitron',sans-serif;font-size:.68rem;color:rgba(255,255,255,.55);}
.revive-cell{font-size:.75rem;}
@media(max-width:600px){
  .history-table th:nth-child(4),.history-table td:nth-child(4),
  .history-table th:nth-child(5),.history-table td:nth-child(5){display:none;}
}
</style>
</head>
<body>
<div class="bg"></div>
<header>
  <div class="logo">🏆 Ai Là Tỉ Phú</div>
  <a href="index.php" class="btn-back">← TRANG CHỦ</a>
</header>
<div class="container">
  <h1>📋 LỊCH SỬ CHƠI – <?= htmlspecialchars($user['hoten']) ?></h1>

  <!-- Summary -->
  <div class="summary-row">
    <div class="sum-card"><span class="sum-num"><?= $summary['tong']??0 ?></span><div class="sum-label">Tổng lượt</div></div>
    <div class="sum-card"><span class="sum-num" style="color:#2ed573"><?= $summary['thang']??0 ?></span><div class="sum-label">Lần thắng</div></div>
    <div class="sum-card"><span class="sum-num" style="font-size:.9rem"><?= $summary['tong']>0?round($summary['thang']/$summary['tong']*100).'%':'0%' ?></span><div class="sum-label">Tỷ lệ thắng</div></div>
    <div class="sum-card"><span class="sum-num" style="font-size:.82rem"><?= formatMoney($summary['max_tien']??0) ?></span><div class="sum-label">Kỷ lục</div></div>
    <div class="sum-card"><span class="sum-num" style="font-size:.82rem"><?= round($summary['avg_cau']??0,1) ?></span><div class="sum-label">TB câu đúng</div></div>
  </div>

  <!-- Filter -->
  <div class="filter-row">
    <button class="filter-btn active" onclick="filterRows('all',this)">TẤT CẢ</button>
    <button class="filter-btn" onclick="filterRows('win',this)">🏆 THẮNG</button>
    <button class="filter-btn" onclick="filterRows('lose',this)">💔 THUA</button>
    <button class="filter-btn" onclick="filterRows('event',this)">💚 EVENT</button>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty">Bạn chưa có lượt chơi nào 🎮<br><a href="index.php" style="color:var(--gold)">Bắt đầu ngay!</a></div>
  <?php else: ?>
  <table class="history-table" id="histTable">
    <thead><tr>
      <th>Chủ đề</th>
      <th>Kết quả</th>
      <th>Tiền thưởng</th>
      <th>Số câu</th>
      <th>Trợ giúp</th>
      <th>Ngày chơi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): 
      $isEvent = !empty($r['loai_choi']) && $r['loai_choi']==='event';
      $usedRevive = !empty($r['da_hoi_sinh']);
    ?>
      <tr data-result="<?= $r['ketqua']==='Thắng'?'win':'lose' ?>" data-event="<?= $isEvent?'1':'0' ?>">
        <td><div class="topic-cell"><?= $r['icon'] ?> <?= htmlspecialchars($r['tenlinhvuc']??'?') ?><?php if($isEvent):?><span class="event-tag">EVENT</span><?php endif;?></div></td>
        <td><?= $r['ketqua']==='Thắng' ? '<span class="win-badge">THẮNG 🏆</span>' : '<span class="lose-badge">THUA 💔</span>' ?></td>
        <td class="prize-cell"><?= formatMoney($r['sotien']) ?></td>
        <td class="num-cell"><?= $r['cau_dat_duoc'] ?>/15</td>
        <td class="num-cell"><?= $r['so_trogiup'] ?>x<?php if($usedRevive):?> <span style="color:#2ed573">💚</span><?php endif;?></td>
        <td class="date-cell"><?= date('d/m/Y H:i', strtotime($r['ngaychoi'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<script>
function filterRows(type, btn){
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#histTable tbody tr').forEach(tr=>{
    if(type==='all') tr.style.display='';
    else if(type==='win') tr.style.display=tr.dataset.result==='win'?'':'none';
    else if(type==='lose') tr.style.display=tr.dataset.result==='lose'?'':'none';
    else if(type==='event') tr.style.display=tr.dataset.event==='1'?'':'none';
  });
}
</script>
</body>
</html>
