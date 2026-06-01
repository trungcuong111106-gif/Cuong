<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('auth.php');

$user = getCurrentUser();
// Safety check: nếu session tồn tại nhưng user bị xóa khỏi DB
if (!$user || !is_array($user)) {
    session_destroy();
    redirect('auth.php');
}

$db = getDB();

// Stats - bọc try/catch phòng cột chưa tồn tại
$stat = ['tong'=>0,'thang'=>0,'max_tien'=>0];
try {
    $st = $db->prepare("SELECT COUNT(*) as tong, SUM(CASE WHEN ketqua='Thắng' THEN 1 ELSE 0 END) as thang, MAX(sotien) as max_tien FROM luotchoi WHERE taikhoan_id=?");
    $st->execute([$user['id']]);
    $row = $st->fetch();
    if ($row && is_array($row)) $stat = $row;
} catch(Exception $e) {}

// Lĩnh vực
$linhvucs = [];
try { $linhvucs = $db->query("SELECT * FROM linhvuc ORDER BY id")->fetchAll(); } catch(Exception $e) {}

// Leaderboard
$lb = [];
try { $lb = $db->query("SELECT t.hoten, MAX(l.sotien) as best FROM luotchoi l JOIN taikhoan t ON l.taikhoan_id=t.id WHERE l.ketqua='Thắng' GROUP BY l.taikhoan_id ORDER BY best DESC LIMIT 5")->fetchAll(); } catch(Exception $e) {}

// Đảm bảo user luôn có ít nhất 1 revive (nếu bảng user_items tồn tại)
try { ensureUserRevive($user['id']); } catch(Exception $e) {}

// Revive count
$revives = 1;
try { $revives = getUserRevives($user['id']); } catch(Exception $e) {}

// Merge best score
$merge_best = 0;
try {
    $ms = $db->prepare("SELECT best_score FROM merge_leaderboard WHERE taikhoan_id=?");
    $ms->execute([$user['id']]);
    $mr = $ms->fetch();
    $merge_best = ($mr && is_array($mr)) ? (int)$mr['best_score'] : 0;
} catch(Exception $e) {}

function formatMoney($n){
    if($n>=1000000000) return number_format($n/1000000000,1).' Tỷ';
    if($n>=1000000)    return number_format($n/1000000,0).' Tr';
    return number_format($n).' đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ai Là Tỉ Phú – Chọn Chủ Đề</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
:root{--gold:#FFD700;--gold2:#FFA500;--dark:#0a0612;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Rajdhani',sans-serif;background:var(--dark);min-height:100vh;color:#fff;}
.bg-fx{position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse at 10% 20%,rgba(155,89,182,.12) 0%,transparent 55%),radial-gradient(ellipse at 90% 80%,rgba(255,165,0,.08) 0%,transparent 55%),var(--dark);}
.stars{position:fixed;inset:0;z-index:0;overflow:hidden;}
.star{position:absolute;background:#fff;border-radius:50%;animation:tw var(--d) ease-in-out infinite alternate;}
@keyframes tw{from{opacity:.05}to{opacity:.7}}
.container{position:relative;z-index:10;max-width:1160px;margin:0 auto;padding:20px 16px;}
header{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;background:rgba(255,215,0,.04);border-bottom:1px solid rgba(255,215,0,.15);position:sticky;top:0;z-index:100;backdrop-filter:blur(12px);}
.header-logo{font-family:'Cinzel Decorative',serif;font-size:1.05rem;background:linear-gradient(135deg,#FFD700,#FFA500);-webkit-background-clip:text;-webkit-text-fill-color:transparent;filter:drop-shadow(0 0 8px rgba(255,165,0,.5));}
.header-right{display:flex;align-items:center;gap:9px;flex-wrap:wrap;}
.user-badge{font-family:'Orbitron',sans-serif;font-size:.58rem;color:rgba(255,215,0,.8);letter-spacing:1px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:20px;padding:6px 13px;}
.revive-badge{font-family:'Orbitron',sans-serif;font-size:.55rem;letter-spacing:1px;background:rgba(46,213,115,.1);border:1px solid rgba(46,213,115,.3);color:#2ed573;border-radius:20px;padding:6px 11px;}
.btn-nav{font-family:'Orbitron',sans-serif;font-size:.53rem;letter-spacing:1px;color:rgba(255,215,0,.6);text-decoration:none;border:1px solid rgba(255,215,0,.2);border-radius:20px;padding:6px 11px;transition:all .3s;}
.btn-nav:hover{border-color:var(--gold);color:var(--gold);}
.btn-logout{color:rgba(255,100,100,.7);border-color:rgba(255,100,100,.25);}
.btn-logout:hover{border-color:#ff4757;color:#ff4757;}
.hero{text-align:center;padding:34px 20px 22px;}
.hero-trophy{font-size:3.2rem;display:block;animation:float 3s ease-in-out infinite;filter:drop-shadow(0 0 20px rgba(255,165,0,.8));margin-bottom:10px;}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.hero-title{font-family:'Cinzel Decorative',serif;font-size:clamp(1.7rem,5vw,2.8rem);font-weight:900;background:linear-gradient(135deg,#FFD700 0%,#FFF176 40%,#FFD700 60%,#FF8C00 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;filter:drop-shadow(0 0 15px rgba(255,215,0,.5));margin-bottom:6px;}
.hero-sub{font-family:'Orbitron',sans-serif;font-size:.6rem;color:rgba(255,215,0,.4);letter-spacing:5px;margin-bottom:22px;}
.stats-row{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-bottom:30px;}
.stat-card{background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.15);border-radius:13px;padding:12px 18px;text-align:center;min-width:105px;}
.stat-num{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;color:#FFD700;display:block;line-height:1;}
.stat-label{font-size:.7rem;color:rgba(255,255,255,.4);margin-top:4px;letter-spacing:1px;}
.section-title{font-family:'Orbitron',sans-serif;font-size:.66rem;letter-spacing:4px;color:rgba(255,215,0,.5);text-align:center;margin-bottom:18px;display:flex;align-items:center;gap:12px;}
.section-title::before,.section-title::after{content:'';flex:1;height:1px;}
.section-title::before{background:linear-gradient(90deg,transparent,rgba(255,215,0,.3));}
.section-title::after{background:linear-gradient(90deg,rgba(255,215,0,.3),transparent);}
.topics-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:14px;margin-bottom:14px;}
.topic-card{position:relative;background:linear-gradient(145deg,rgba(26,15,46,.9),rgba(17,9,32,.95));border:1px solid rgba(255,215,0,.15);border-radius:17px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .35s cubic-bezier(.34,1.56,.64,1);overflow:hidden;text-decoration:none;display:block;}
.topic-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--tc),transparent);transform:scaleX(0);transition:transform .3s;}
.topic-card:hover{transform:translateY(-6px) scale(1.02);border-color:rgba(255,215,0,.4);box-shadow:0 14px 38px rgba(0,0,0,.5),0 0 28px rgba(255,165,0,.12);}
.topic-card:hover::after{transform:scaleX(1);}
.topic-icon{font-size:2.4rem;display:block;margin-bottom:9px;filter:drop-shadow(0 0 8px rgba(255,165,0,.3));transition:transform .3s;}
.topic-card:hover .topic-icon{transform:scale(1.15) rotate(5deg);}
.topic-name{font-family:'Orbitron',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:2px;color:#FFD700;margin-bottom:5px;}
.topic-desc{font-size:.7rem;color:rgba(255,255,255,.35);margin-bottom:11px;line-height:1.4;}
.topic-meta{display:flex;justify-content:center;gap:5px;flex-wrap:wrap;}
.meta-badge{font-family:'Orbitron',sans-serif;font-size:.46rem;padding:3px 7px;border-radius:20px;letter-spacing:1px;background:rgba(255,215,0,.08);color:rgba(255,215,0,.55);border:1px solid rgba(255,215,0,.18);}
.meta-badge.special{background:rgba(155,89,182,.15);color:rgba(200,150,255,.8);border-color:rgba(155,89,182,.3);}
.meta-badge.newbadge{background:rgba(255,71,87,.1);color:rgba(255,120,120,.8);border-color:rgba(255,71,87,.25);}
.topic-card.sp-card{border-color:rgba(155,89,182,.28);}
.topic-card.sp-card:hover{border-color:rgba(155,89,182,.65);box-shadow:0 14px 38px rgba(0,0,0,.5),0 0 28px rgba(155,89,182,.18);}
/* Events */
.events-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin-bottom:34px;}
.event-card{position:relative;background:linear-gradient(145deg,rgba(20,8,40,.96),rgba(10,4,25,.99));border-radius:20px;padding:24px 20px;text-align:center;overflow:hidden;text-decoration:none;display:block;transition:all .35s cubic-bezier(.34,1.56,.64,1);}
.event-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--ec);opacity:.9;}
.event-card:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.65),0 0 40px var(--eg);}
.event-badge{position:absolute;top:13px;right:13px;font-family:'Orbitron',sans-serif;font-size:.46rem;letter-spacing:1px;padding:3px 8px;border-radius:20px;background:rgba(255,71,87,.18);color:#ff6b6b;border:1px solid rgba(255,71,87,.38);}
.event-icon{font-size:2.8rem;display:block;margin-bottom:10px;animation:float 3s ease-in-out infinite;filter:drop-shadow(0 0 12px var(--eg));}
.event-name{font-family:'Cinzel Decorative',serif;font-size:.95rem;margin-bottom:7px;background:var(--ec);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.event-desc{font-size:.78rem;color:rgba(255,255,255,.45);margin-bottom:13px;line-height:1.5;}
.event-features{display:flex;flex-wrap:wrap;gap:5px;justify-content:center;}
.ef-tag{font-family:'Orbitron',sans-serif;font-size:.46rem;padding:3px 8px;border-radius:20px;letter-spacing:1px;border:1px solid;background:rgba(0,0,0,.3);}
/* Bottom */
.bottom-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:28px;}
@media(max-width:640px){.bottom-grid{grid-template-columns:1fr;}}
.panel{background:rgba(255,215,0,.03);border:1px solid rgba(255,215,0,.12);border-radius:15px;padding:18px;}
.panel-title{font-family:'Orbitron',sans-serif;font-size:.61rem;letter-spacing:3px;color:rgba(255,215,0,.6);margin-bottom:13px;}
.lb-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.lb-rank{font-family:'Orbitron',sans-serif;font-size:.78rem;font-weight:900;width:24px;text-align:center;}
.lb-rank.r1{color:#FFD700}.lb-rank.r2{color:#C0C0C0}.lb-rank.r3{color:#CD7F32}
.lb-name{flex:1;font-size:.86rem;font-weight:600;}
.lb-score{font-family:'Orbitron',sans-serif;font-size:.6rem;color:#FFD700;}
.prize-list{list-style:none;}
.prize-item{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem;}
.prize-item:last-child{border:none;}
.prize-level{font-family:'Orbitron',sans-serif;font-size:.57rem;color:rgba(255,255,255,.4);width:50px;}
.prize-amount{font-weight:700;color:#FFD700;}
.prize-safe{font-size:.6rem;color:#2ed573;}
</style>
</head>
<body>
<div class="bg-fx"></div><div class="stars" id="stars"></div>
<header>
  <div class="header-logo">🏆 Ai Là Tỉ Phú</div>
  <div class="header-right">
    <div class="revive-badge">💚 <?= $revives ?> Hồi sinh</div>
    <div class="user-badge">👤 <?= htmlspecialchars($user['hoten']) ?></div>
    <a href="history.php" class="btn-nav">📋 Lịch sử</a>
    <a href="logout.php" class="btn-nav btn-logout">THOÁT</a>
  </div>
</header>
<div class="container">
  <div class="hero">
    <span class="hero-trophy">💰</span>
    <div class="hero-title">Ai Là Tỉ Phú</div>
    <div class="hero-sub">CHỌN CHỦ ĐỀ VÀ BẮT ĐẦU HÀNH TRÌNH</div>
    <div class="stats-row">
      <div class="stat-card"><span class="stat-num"><?=$stat['tong']??0?></span><div class="stat-label">Lượt chơi</div></div>
      <div class="stat-card"><span class="stat-num" style="color:#2ed573"><?=$stat['thang']??0?></span><div class="stat-label">Lần thắng</div></div>
      <div class="stat-card"><span class="stat-num" style="font-size:.82rem"><?=formatMoney($stat['max_tien']??0)?></span><div class="stat-label">Kỷ lục</div></div>
      <?php if($merge_best>0):?><div class="stat-card"><span class="stat-num" style="color:#a78bfa;font-size:.95rem"><?=number_format($merge_best)?></span><div class="stat-label">Merge kỷ lục</div></div><?php endif;?>
    </div>
  </div>

  <div class="section-title">🎯 CHỌN CHỦ ĐỀ AI LÀ TỈ PHÚ</div>
  <?php
  $descs=[1=>'Vật lý • Hóa học • Sinh học',2=>'Món ăn • Ẩm thực thế giới',3=>'Bóng đá • Olympic • Kỷ lục',4=>'Nhạc cụ • Nghệ sĩ • Ban nhạc',5=>'Lịch sử thế giới • Sự kiện',6=>'Quốc gia • Địa danh • Địa hình',7=>'Tác phẩm • Tác giả • Văn học VN',8=>'Gộp tất cả chủ đề • Cực đại'];
  $colors=[1=>'#00bcd4',2=>'#ff9800',3=>'#4caf50',4=>'#9c27b0',5=>'#f44336',6=>'#2196f3',7=>'#e91e63',8=>'#ff6b35'];
  ?>
  <div class="topics-grid">
  <?php foreach($linhvucs as $lv):
    $c=$colors[$lv['id']]??'#FFD700'; $isSp=($lv['id']==8);
  ?>
    <a href="game.php?linhvuc=<?=$lv['id']?>" class="topic-card <?=$isSp?'sp-card':''?>" style="--tc:<?=$c?>">
      <span class="topic-icon"><?=$lv['icon']?></span>
      <div class="topic-name"><?=htmlspecialchars($lv['tenlinhvuc'])?></div>
      <div class="topic-desc"><?=$descs[$lv['id']]??''?></div>
      <div class="topic-meta">
        <span class="meta-badge"><?=$lv['id']==8?'700+ CÂU':'100 CÂU'?></span>
        <span class="meta-badge">5 CẤP</span>
        <?php if($lv['id']==8):?><span class="meta-badge special">TỔNG HỢP</span><?php endif;?>
        <?php if($lv['id']>=6):?><span class="meta-badge newbadge">MỚI</span><?php endif;?>
      </div>
    </a>
  <?php endforeach;?>
  </div>

  <div class="section-title" style="margin-top:8px">🎮 GAME EVENTS ĐẶC BIỆT</div>
  <div class="events-grid">
    <a href="merge_game.php" class="event-card" style="--ec:linear-gradient(135deg,#a78bfa,#7c3aed);--eg:rgba(124,58,237,.25);border:1px solid rgba(124,58,237,.35);">
      <span class="event-badge">EVENT</span>
      <span class="event-icon">🔢</span>
      <div class="event-name">Merge Game 2048</div>
      <div class="event-desc">Gộp các ô số giống nhau để đạt đến con số <strong style="color:#a78bfa">2048</strong>! Chinh phục bảng xếp hạng cao nhất.</div>
      <div class="event-features">
        <span class="ef-tag" style="color:#a78bfa;border-color:rgba(167,139,250,.3)">4×4 BOARD</span>
        <span class="ef-tag" style="color:#7dd3fc;border-color:rgba(125,211,252,.3)">HIGH SCORE</span>
        <span class="ef-tag" style="color:#86efac;border-color:rgba(134,239,172,.3)">BẢNG XH</span>
      </div>
    </a>
    <a href="game.php?linhvuc=8&event=revive" class="event-card" style="--ec:linear-gradient(135deg,#2ed573,#00b36b);--eg:rgba(46,213,115,.2);border:1px solid rgba(46,213,115,.3);">
      <span class="event-badge" style="background:rgba(46,213,115,.15);color:#2ed573;border-color:rgba(46,213,115,.4)">EVENT</span>
      <span class="event-icon" style="animation-delay:.5s">💚</span>
      <div class="event-name" style="--ec:linear-gradient(135deg,#2ed573,#00ff88)">Tỉ Phú Hồi Sinh</div>
      <div class="event-desc">Chơi Tổng hợp với <strong style="color:#2ed573">1 lần hồi sinh</strong> khi sai! Hiện có <strong style="color:#2ed573"><?=$revives?> lần</strong> hồi sinh.</div>
      <div class="event-features">
        <span class="ef-tag" style="color:#2ed573;border-color:rgba(46,213,115,.3)">💚 REVIVE ×<?=$revives?></span>
        <span class="ef-tag" style="color:#ffd700;border-color:rgba(255,215,0,.3)">TỔNG HỢP</span>
        <span class="ef-tag" style="color:#ff9f43;border-color:rgba(255,159,67,.3)">10 TỶ</span>
      </div>
    </a>
  </div>

  <div class="bottom-grid">
    <div class="panel">
      <div class="panel-title">🏅 BẢNG XẾP HẠNG</div>
      <?php if(empty($lb)):?><p style="color:rgba(255,255,255,.3);font-size:.85rem;text-align:center;padding:20px">Chưa có ai về đích 🎯</p>
      <?php else: foreach($lb as $i=>$row): $rank=$i+1;?>
        <div class="lb-row"><div class="lb-rank <?=$rank<=3?'r'.$rank:''?>"><?=['🥇','🥈','🥉'][$i]??$rank?></div><div class="lb-name"><?=htmlspecialchars($row['hoten'])?></div><div class="lb-score"><?=formatMoney($row['best'])?></div></div>
      <?php endforeach; endif;?>
    </div>
    <div class="panel">
      <div class="panel-title">💰 THANG TIỀN THƯỞNG</div>
      <ul class="prize-list">
      <?php $prizes=getPrizeLadder(); foreach([15,14,13,12,10,5,1] as $lvl): $p=$prizes[$lvl];?>
        <li class="prize-item"><span class="prize-level">Câu <?=$lvl?></span><span class="prize-amount"><?=$p['prize']?></span><?php if($p['safe']):?><span class="prize-safe">🛡️</span><?php endif;?></li>
      <?php endforeach;?>
      </ul>
    </div>
  </div>
</div>
<script>
const s=document.getElementById('stars');
for(let i=0;i<100;i++){const el=document.createElement('div');el.className='star';const sz=Math.random()*2+.4;el.style.cssText=`width:${sz}px;height:${sz}px;left:${Math.random()*100}%;top:${Math.random()*100}%;--d:${2+Math.random()*5}s;animation-delay:${Math.random()*5}s`;s.appendChild(el);}
</script>
</body>
</html>
