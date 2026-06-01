<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('auth.php');
$user = getCurrentUser();
if (!$user || !is_array($user)) { session_destroy(); redirect('auth.php'); }
$db = getDB();

$linhvuc_id = intval($_GET['linhvuc'] ?? 0);
$is_event   = ($_GET['event'] ?? '') === 'revive';
if (!$linhvuc_id) redirect('index.php');

$lv = $db->prepare("SELECT * FROM linhvuc WHERE id=?");
$lv->execute([$linhvuc_id]);
$linhvuc = $lv->fetch();
if (!$linhvuc) redirect('index.php');

// Revive stock
$has_revive = false;
$revive_count = 0;
if ($is_event) {
    $revive_count = getUserRevives($user['id']);
    $has_revive = $revive_count > 0;
}

// Load questions
$questions_raw = [];
if ($linhvuc_id == 8) {
    // Tổng hợp: lấy từ linhvuc 1-7
    $levels = [['mucdo'=>1,'count'=>3],['mucdo'=>2,'count'=>7],['mucdo'=>3,'count'=>3],['mucdo'=>4,'count'=>1],['mucdo'=>5,'count'=>1]];
    foreach ($levels as $lvl) {
        $stmt = $db->prepare("SELECT c.*, GROUP_CONCAT(d.id,'|',d.noidung,'|',d.ladapan_dung ORDER BY RAND() SEPARATOR ';;') as dapans FROM cauhoi c JOIN dapan d ON d.cauhoi_id=c.id WHERE c.linhvuc_id IN (1,2,3,4,5,6,7) AND c.mucdo=? GROUP BY c.id ORDER BY RAND() LIMIT ?");
        $stmt->execute([$lvl['mucdo'], $lvl['count']]);
        foreach ($stmt->fetchAll() as $r) $questions_raw[] = $r;
    }
} else {
    $levels = [['mucdo'=>1,'count'=>3],['mucdo'=>2,'count'=>7],['mucdo'=>3,'count'=>3],['mucdo'=>4,'count'=>1],['mucdo'=>5,'count'=>1]];
    foreach ($levels as $lvl) {
        $stmt = $db->prepare("SELECT c.*, GROUP_CONCAT(d.id,'|',d.noidung,'|',d.ladapan_dung ORDER BY RAND() SEPARATOR ';;') as dapans FROM cauhoi c JOIN dapan d ON d.cauhoi_id=c.id WHERE c.linhvuc_id=? AND c.mucdo=? GROUP BY c.id ORDER BY RAND() LIMIT ?");
        $stmt->execute([$linhvuc_id, $lvl['mucdo'], $lvl['count']]);
        foreach ($stmt->fetchAll() as $r) $questions_raw[] = $r;
    }
}
if (count($questions_raw) < 15) {
    $where = $linhvuc_id==8 ? "c.linhvuc_id IN (1,2,3,4,5,6,7)" : "c.linhvuc_id=$linhvuc_id";
    $stmt = $db->query("SELECT c.*, GROUP_CONCAT(d.id,'|',d.noidung,'|',d.ladapan_dung ORDER BY RAND() SEPARATOR ';;') as dapans FROM cauhoi c JOIN dapan d ON d.cauhoi_id=c.id WHERE $where GROUP BY c.id ORDER BY RAND() LIMIT 15");
    $questions_raw = $stmt->fetchAll();
}

$questions = [];
foreach ($questions_raw as $q) {
    $dapans = [];
    foreach (explode(';;', $q['dapans']) as $d) {
        $parts = explode('|', $d);
        if (count($parts) >= 3) $dapans[] = ['id'=>$parts[0],'noidung'=>$parts[1],'dung'=>$parts[2]];
    }
    shuffle($dapans);
    $questions[] = ['id'=>$q['id'],'noidung'=>$q['noidung'],'mucdo'=>$q['mucdo'],'thoigian'=>$q['thoigian'],'dapans'=>$dapans];
}

$prizes = getPrizeLadder();
$questionsJson = json_encode($questions, JSON_UNESCAPED_UNICODE);
$prizesJson    = json_encode($prizes);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ai Là Tỉ Phú – <?= htmlspecialchars($linhvuc['tenlinhvuc']) ?><?= $is_event?' [HỒI SINH]':'' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
:root{--gold:#FFD700;--gold2:#FFA500;--dark:#0a0612;--dark2:#110920;--safe:#2ed573;--danger:#ff4757;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Rajdhani',sans-serif;background:var(--dark);min-height:100vh;color:#fff;overflow-x:hidden;}
.bg-fx{position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse at 15% 30%,rgba(155,89,182,.12) 0%,transparent 55%),radial-gradient(ellipse at 85% 70%,rgba(255,165,0,.08) 0%,transparent 55%),var(--dark);}
.stars{position:fixed;inset:0;z-index:0;overflow:hidden;}
.star{position:absolute;background:#fff;border-radius:50%;animation:tw var(--d) ease-in-out infinite alternate;}
@keyframes tw{from{opacity:.05}to{opacity:.6}}
.game-layout{position:relative;z-index:10;display:grid;grid-template-columns:1fr 270px;grid-template-rows:auto 1fr;min-height:100vh;max-width:1180px;margin:0 auto;padding:14px;gap:14px;}
@media(max-width:768px){.game-layout{grid-template-columns:1fr;}}
.topbar{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;background:rgba(255,215,0,.04);border:1px solid rgba(255,215,0,.15);border-radius:13px;padding:11px 18px;flex-wrap:wrap;gap:10px;}
.topbar-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.topic-badge{font-family:'Orbitron',sans-serif;font-size:.62rem;letter-spacing:2px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:20px;padding:5px 13px;color:var(--gold);}
.event-badge-top{font-family:'Orbitron',sans-serif;font-size:.55rem;letter-spacing:1px;background:rgba(46,213,115,.1);border:1px solid rgba(46,213,115,.3);border-radius:20px;padding:5px 11px;color:#2ed573;}
.btn-home{font-family:'Orbitron',sans-serif;font-size:.53rem;letter-spacing:1px;color:rgba(255,255,255,.4);text-decoration:none;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:5px 11px;transition:all .3s;}
.btn-home:hover{color:#fff;border-color:rgba(255,255,255,.3);}
.topbar-right{display:flex;align-items:center;gap:14px;}
.timer-wrap{position:relative;width:60px;height:60px;}
.timer-svg{transform:rotate(-90deg);width:60px;height:60px;}
.timer-track{fill:none;stroke:rgba(255,255,255,.08);stroke-width:5;}
.timer-bar{fill:none;stroke-width:5;stroke-linecap:round;stroke-dasharray:164;stroke-dashoffset:0;transition:stroke-dashoffset .9s linear,stroke .5s;stroke:var(--gold);}
.timer-num{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:900;color:var(--gold);}
.current-prize{font-family:'Orbitron',sans-serif;font-size:.6rem;letter-spacing:1px;color:rgba(255,215,0,.6);text-align:right;}
.current-amount{font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:900;color:var(--gold);filter:drop-shadow(0 0 8px rgba(255,165,0,.6));}
.main-area{display:flex;flex-direction:column;gap:13px;}
.question-card{background:linear-gradient(145deg,rgba(26,15,46,.95),rgba(17,9,32,.98));border:1px solid rgba(255,215,0,.2);border-radius:19px;padding:26px 24px;position:relative;overflow:hidden;flex:1;}
.question-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#FFD700,#FFA500,#FFD700,transparent);}
.question-number{font-family:'Orbitron',sans-serif;font-size:.58rem;letter-spacing:3px;color:rgba(255,215,0,.4);margin-bottom:14px;}
.question-text{font-size:clamp(.96rem,2.4vw,1.2rem);font-weight:600;line-height:1.6;color:#fff;}
.answers-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px;}
@media(max-width:480px){.answers-grid{grid-template-columns:1fr;}}
.answer-btn{background:linear-gradient(145deg,rgba(255,215,0,.06),rgba(255,165,0,.03));border:1px solid rgba(255,215,0,.2);border-radius:13px;padding:13px 16px;display:flex;align-items:center;gap:11px;cursor:pointer;transition:all .25s cubic-bezier(.34,1.56,.64,1);text-align:left;position:relative;overflow:hidden;}
.answer-btn:hover:not(:disabled){background:linear-gradient(145deg,rgba(255,215,0,.12),rgba(255,165,0,.08));border-color:rgba(255,215,0,.5);transform:translateX(4px);box-shadow:0 4px 18px rgba(255,165,0,.14);}
.answer-btn:disabled{cursor:not-allowed;}
.answer-btn.selected{background:linear-gradient(145deg,rgba(255,165,0,.2),rgba(255,200,0,.12));border-color:var(--gold);box-shadow:0 0 0 2px rgba(255,215,0,.3),0 0 22px rgba(255,165,0,.2);}
.answer-btn.correct{background:linear-gradient(145deg,rgba(46,213,115,.2),rgba(0,200,80,.12));border-color:var(--safe);box-shadow:0 0 22px rgba(46,213,115,.3);animation:correctPulse .6s ease;}
.answer-btn.wrong{background:linear-gradient(145deg,rgba(255,71,87,.15),rgba(200,0,50,.08));border-color:var(--danger);animation:shake .4s ease;}
.answer-btn.hidden{opacity:.12;pointer-events:none;}
@keyframes correctPulse{0%{transform:scale(1)}50%{transform:scale(1.03)}100%{transform:scale(1)}}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-8px)}75%{transform:translateX(8px)}}
.answer-letter{width:30px;height:30px;flex-shrink:0;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,165,0,.08));border:1px solid rgba(255,215,0,.3);border-radius:7px;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:.68rem;font-weight:700;color:var(--gold);}
.answer-text{font-size:.92rem;font-weight:600;color:#fff;line-height:1.3;}
.lifelines{display:flex;gap:9px;flex-wrap:wrap;}
.lifeline-btn{flex:1;min-width:75px;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.2);border-radius:11px;padding:9px 10px;cursor:pointer;transition:all .3s;text-align:center;}
.lifeline-btn:hover:not(:disabled){background:rgba(255,215,0,.12);border-color:rgba(255,215,0,.5);transform:translateY(-2px);box-shadow:0 5px 18px rgba(255,165,0,.18);}
.lifeline-btn:disabled,.lifeline-btn.used{opacity:.25;cursor:not-allowed;filter:grayscale(1);}
.lifeline-icon{font-size:1.3rem;display:block;margin-bottom:3px;}
.lifeline-name{font-family:'Orbitron',sans-serif;font-size:.48rem;letter-spacing:1px;color:rgba(255,215,0,.6);}
.lifeline-btn.revive-btn{background:rgba(46,213,115,.1);border-color:rgba(46,213,115,.35);}
.lifeline-btn.revive-btn:hover:not(:disabled){background:rgba(46,213,115,.2);border-color:#2ed573;box-shadow:0 5px 18px rgba(46,213,115,.25);}
.lifeline-btn.revive-btn .lifeline-name{color:rgba(46,213,115,.8);}
/* Sidebar */
.sidebar{display:flex;flex-direction:column;gap:11px;}
.prize-ladder{background:rgba(255,215,0,.03);border:1px solid rgba(255,215,0,.12);border-radius:15px;padding:14px;flex:1;}
.ladder-title{font-family:'Orbitron',sans-serif;font-size:.58rem;letter-spacing:3px;color:rgba(255,215,0,.5);margin-bottom:10px;text-align:center;}
.ladder-row{display:flex;align-items:center;gap:7px;padding:4px 7px;border-radius:7px;transition:all .3s;}
.ladder-row.active{background:linear-gradient(90deg,rgba(255,215,0,.15),rgba(255,165,0,.08));border:1px solid rgba(255,215,0,.4);box-shadow:0 0 14px rgba(255,165,0,.14);}
.ladder-row.passed{opacity:.45;}
.ladder-row.safe-mark{border-left:2px solid var(--safe);}
.ladder-num{font-family:'Orbitron',sans-serif;font-size:.52rem;color:rgba(255,255,255,.3);width:18px;text-align:right;}
.ladder-prize{flex:1;font-family:'Orbitron',sans-serif;font-size:.6rem;font-weight:700;color:rgba(255,215,0,.7);}
.ladder-row.active .ladder-prize{color:var(--gold);font-size:.7rem;}
.ladder-safe{font-size:.65rem;}
/* Modals */
.modal-overlay{position:fixed;inset:0;z-index:500;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);opacity:0;pointer-events:none;transition:opacity .4s;}
.modal-overlay.show{opacity:1;pointer-events:all;}
.modal{background:linear-gradient(145deg,#1a0f2e,#110920);border:1px solid rgba(255,215,0,.3);border-radius:22px;padding:36px 32px;max-width:440px;width:90%;text-align:center;position:relative;overflow:hidden;animation:modalIn .5s cubic-bezier(.34,1.56,.64,1);}
.modal::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#FFD700,transparent);}
@keyframes modalIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.modal-icon{font-size:3.5rem;margin-bottom:14px;display:block;}
.modal-title{font-family:'Cinzel Decorative',serif;font-size:1.5rem;background:linear-gradient(135deg,#FFD700,#FFA500);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:10px;}
.modal-prize{font-family:'Orbitron',sans-serif;font-size:1.6rem;font-weight:900;color:var(--gold);margin:14px 0;filter:drop-shadow(0 0 14px rgba(255,165,0,.6));}
.modal-desc{color:rgba(255,255,255,.5);font-size:.9rem;margin-bottom:22px;}
.modal-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.btn-primary{padding:12px 26px;background:linear-gradient(135deg,#FFD700,#FFA500);border:none;border-radius:11px;font-family:'Orbitron',sans-serif;font-size:.65rem;font-weight:900;letter-spacing:2px;color:#0a0612;cursor:pointer;transition:all .3s;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(255,165,0,.4);}
.btn-secondary{padding:12px 26px;background:transparent;border:1px solid rgba(255,215,0,.3);border-radius:11px;font-family:'Orbitron',sans-serif;font-size:.65rem;letter-spacing:2px;color:rgba(255,215,0,.7);cursor:pointer;transition:all .3s;}
.btn-secondary:hover{border-color:var(--gold);color:var(--gold);}
.btn-revive{padding:12px 26px;background:linear-gradient(135deg,#2ed573,#00b36b);border:none;border-radius:11px;font-family:'Orbitron',sans-serif;font-size:.65rem;font-weight:900;letter-spacing:2px;color:#0a0612;cursor:pointer;transition:all .3s;}
.btn-revive:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(46,213,115,.4);}
.audience-bars{margin-top:10px;}
.aud-row{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.aud-letter{font-family:'Orbitron',sans-serif;font-size:.58rem;color:var(--gold);width:14px;text-align:center;}
.aud-bar-wrap{flex:1;height:13px;background:rgba(255,255,255,.06);border-radius:20px;overflow:hidden;}
.aud-bar{height:100%;background:linear-gradient(90deg,#FFD700,#FFA500);border-radius:20px;transition:width 1s cubic-bezier(.34,1.56,.64,1);}
.aud-pct{font-family:'Orbitron',sans-serif;font-size:.58rem;color:rgba(255,215,0,.7);width:30px;text-align:right;}
.phone-box{background:rgba(0,0,0,.3);border:1px solid rgba(255,215,0,.15);border-radius:11px;padding:13px;margin:14px 0;font-size:.88rem;color:rgba(255,255,255,.8);line-height:1.6;text-align:left;}
.phone-name{font-family:'Orbitron',sans-serif;font-size:.58rem;color:var(--gold);margin-bottom:7px;}
.revive-glow{animation:reviveGlow 1s ease infinite alternate;}
@keyframes reviveGlow{from{box-shadow:0 0 20px rgba(46,213,115,.3)}to{box-shadow:0 0 40px rgba(46,213,115,.6),0 0 80px rgba(46,213,115,.2)}}
.confetti-piece{position:fixed;top:-10px;width:10px;height:10px;border-radius:2px;animation:confettiFall var(--cf-d) var(--cf-delay) ease-in forwards;z-index:600;}
@keyframes confettiFall{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(110vh) rotate(720deg);opacity:0}}
</style>
</head>
<body>
<div class="bg-fx"></div><div class="stars" id="stars"></div>

<div class="game-layout">
  <div class="topbar">
    <div class="topbar-left">
      <a href="index.php" class="btn-home">🏠 TRANG CHỦ</a>
      <div class="topic-badge"><?=$linhvuc['icon']?> <?=htmlspecialchars($linhvuc['tenlinhvuc'])?></div>
      <?php if($is_event):?><div class="event-badge-top">💚 HỒI SINH EVENT</div><?php endif;?>
    </div>
    <div class="topbar-right">
      <div class="timer-wrap">
        <svg class="timer-svg" viewBox="0 0 60 60"><circle class="timer-track" cx="30" cy="30" r="26"/><circle class="timer-bar" id="timerBar" cx="30" cy="30" r="26"/></svg>
        <div class="timer-num" id="timerNum">30</div>
      </div>
      <div><div class="current-prize">GIÁ TRỊ</div><div class="current-amount" id="currentPrize">0 đ</div></div>
    </div>
  </div>

  <div class="main-area">
    <div class="question-card">
      <div class="question-number" id="questionNum">CÂU HỎI 1 / 15</div>
      <div class="question-text" id="questionText">Đang tải câu hỏi...</div>
    </div>
    <div class="answers-grid" id="answersGrid"></div>
    <div class="lifelines">
      <button class="lifeline-btn" id="ll5050" onclick="useLifeline('5050')"><span class="lifeline-icon">✂️</span><div class="lifeline-name">50:50</div></button>
      <button class="lifeline-btn" id="llAudience" onclick="useLifeline('audience')"><span class="lifeline-icon">👥</span><div class="lifeline-name">KHÁN GIẢ</div></button>
      <button class="lifeline-btn" id="llPhone" onclick="useLifeline('phone')"><span class="lifeline-icon">📞</span><div class="lifeline-name">GỌI ĐIỆN</div></button>
      <?php if($is_event && $has_revive):?>
      <button class="lifeline-btn revive-btn" id="llRevive" onclick="useRevive()"><span class="lifeline-icon">💚</span><div class="lifeline-name">HỒI SINH</div></button>
      <?php endif;?>
      <button class="lifeline-btn" onclick="confirmQuit()" style="border-color:rgba(255,71,87,.3)"><span class="lifeline-icon">🚪</span><div class="lifeline-name" style="color:rgba(255,71,87,.6)">DỪNG</div></button>
    </div>
  </div>

  <div class="sidebar">
    <div class="prize-ladder"><div class="ladder-title">💰 THANG TIỀN</div><div id="ladderList"></div></div>
  </div>
</div>

<!-- WIN -->
<div class="modal-overlay" id="modalWin">
  <div class="modal">
    <span class="modal-icon">🏆</span>
    <div class="modal-title">CHÚC MỪNG!</div>
    <div class="modal-prize" id="winPrize"></div>
    <div class="modal-desc">Bạn đã trả lời đúng tất cả 15 câu hỏi!</div>
    <div class="modal-btns"><button class="btn-primary" onclick="saveAndGo(true)">💾 LƯU KẾT QUẢ</button><button class="btn-secondary" onclick="location.href='index.php'">🏠 TRANG CHỦ</button></div>
  </div>
</div>

<!-- LOSE -->
<div class="modal-overlay" id="modalLose">
  <div class="modal">
    <span class="modal-icon" id="loseIcon">😢</span>
    <div class="modal-title" id="loseTitleEl" style="font-size:1.3rem">TIẾC QUÁ!</div>
    <div id="loseMsg" style="color:rgba(255,255,255,.6);font-size:.88rem;margin:10px 0;"></div>
    <div class="modal-prize" id="losePrize" style="font-size:1.25rem;color:#ff9800;"></div>
    <div class="modal-desc" id="loseDesc"></div>
    <div class="modal-btns" id="loseBtns">
      <button class="btn-primary" onclick="saveAndGo(false)">💾 LƯU KẾT QUẢ</button>
      <button class="btn-secondary" onclick="location.href='game.php?linhvuc=<?=$linhvuc_id?><?=$is_event?'&event=revive':''?>'">🔄 CHƠI LẠI</button>
    </div>
  </div>
</div>

<!-- REVIVE MODAL -->
<div class="modal-overlay" id="modalRevive">
  <div class="modal revive-glow" style="border-color:rgba(46,213,115,.5)">
    <span class="modal-icon">💚</span>
    <div class="modal-title" style="--c:#2ed573;font-size:1.2rem;background:linear-gradient(135deg,#2ed573,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">HỒI SINH?</div>
    <div id="reviveMsg" style="color:rgba(255,255,255,.6);font-size:.88rem;margin:12px 0;line-height:1.6;"></div>
    <div class="modal-prize" id="revivePrize" style="font-size:1.1rem;color:#2ed573;"></div>
    <div class="modal-btns">
      <button class="btn-revive" onclick="doRevive()">💚 DÙNG HỒI SINH!</button>
      <button class="btn-secondary" onclick="confirmFinalLose()">💀 BỎ CUỘC</button>
    </div>
  </div>
</div>

<!-- QUIT -->
<div class="modal-overlay" id="modalQuit">
  <div class="modal">
    <span class="modal-icon">🚪</span>
    <div class="modal-title" style="font-size:1.1rem">DỪNG CUỘC CHƠI?</div>
    <div class="modal-prize" id="quitPrize"></div>
    <div class="modal-desc">Bạn sẽ mang về số tiền thưởng hiện tại</div>
    <div class="modal-btns"><button class="btn-primary" onclick="doQuit()">✅ XÁC NHẬN</button><button class="btn-secondary" onclick="closeModal('modalQuit')">↩️ TIẾP TỤC</button></div>
  </div>
</div>

<!-- AUDIENCE -->
<div class="modal-overlay" id="modalAudience">
  <div class="modal">
    <span class="modal-icon">👥</span>
    <div class="modal-title" style="font-size:1.1rem">Ý KIẾN KHÁN GIẢ</div>
    <div class="audience-bars" id="audienceBars"></div>
    <div class="modal-btns" style="margin-top:18px"><button class="btn-primary" onclick="closeModal('modalAudience')">👍 ĐÃ HIỂU</button></div>
  </div>
</div>

<!-- PHONE -->
<div class="modal-overlay" id="modalPhone">
  <div class="modal">
    <span class="modal-icon">📞</span>
    <div class="modal-title" style="font-size:1.1rem">GỌI NGƯỜI THÂN</div>
    <div class="phone-box"><div class="phone-name" id="phoneNameEl">📱 Người thân đang nói...</div><div id="phoneText"></div></div>
    <div class="modal-btns"><button class="btn-primary" onclick="closeModal('modalPhone')">👍 CẢM ƠN!</button></div>
  </div>
</div>

<script>
const QUESTIONS=<?=$questionsJson?>;
const PRIZES=<?=$prizesJson?>;
const IS_EVENT=<?=$is_event?'true':'false'?>;
const HAS_REVIVE=<?=$has_revive?'true':'false'?>;
let REVIVE_COUNT=<?=$revive_count?>;
const LETTERS=['A','B','C','D'];

let state={current:0,answered:false,timer:null,timeLeft:30,
  lifelines:{l5050:false,audience:false,phone:false,revive:false},
  hidden:[],safeAmount:0,usedCount:0,reviveUsed:false,
  wrongIdx:-1,wrongCorrectIdx:-1,savedResult:false};

window.onload=()=>{genStars();buildLadder();showQuestion();};

function genStars(){const s=document.getElementById('stars');for(let i=0;i<80;i++){const el=document.createElement('div');el.className='star';const sz=Math.random()*2+.4;el.style.cssText=`width:${sz}px;height:${sz}px;left:${Math.random()*100}%;top:${Math.random()*100}%;--d:${2+Math.random()*5}s;animation-delay:${Math.random()*5}s`;s.appendChild(el);}}

function buildLadder(){const el=document.getElementById('ladderList');el.innerHTML='';for(let i=15;i>=1;i--){const p=PRIZES[i];const row=document.createElement('div');row.className='ladder-row'+(p.safe?' safe-mark':'');row.id='ladder-'+i;row.innerHTML=`<span class="ladder-num">${i}</span><span class="ladder-prize">${p.prize}</span>${p.safe?'<span class="ladder-safe">🛡️</span>':''}`;el.appendChild(row);}updateLadder();}

function updateLadder(){const cur=state.current+1;for(let i=1;i<=15;i++){const row=document.getElementById('ladder-'+i);if(!row)continue;row.classList.remove('active','passed');if(i===cur)row.classList.add('active');else if(i<cur)row.classList.add('passed');}const prev=state.current>0?PRIZES[state.current].prize:'0 đ';document.getElementById('currentPrize').textContent=prev;}

function showQuestion(){if(state.current>=QUESTIONS.length){showWin();return;}
  const q=QUESTIONS[state.current];state.answered=false;state.hidden=[];
  document.getElementById('questionNum').textContent=`CÂU HỎI ${state.current+1} / 15 — ${diffLabel(q.mucdo)}`;
  document.getElementById('questionText').textContent=q.noidung;
  const grid=document.getElementById('answersGrid');grid.innerHTML='';
  q.dapans.forEach((d,i)=>{const btn=document.createElement('button');btn.className='answer-btn';btn.id='ans-'+i;btn.setAttribute('data-correct',d.dung);btn.innerHTML=`<div class="answer-letter">${LETTERS[i]}</div><div class="answer-text">${d.noidung}</div>`;btn.onclick=()=>selectAnswer(i,d.dung==='1');grid.appendChild(btn);});
  updateLadder();startTimer(q.thoigian||30);}

function diffLabel(m){return['','⭐ DỄ','⭐⭐ TRUNG BÌNH','⭐⭐⭐ KHÓ','🔥 RẤT KHÓ','💎 TỈ PHÚ'][m]||'';}

function startTimer(sec){clearInterval(state.timer);state.timeLeft=sec;
  const bar=document.getElementById('timerBar'),num=document.getElementById('timerNum');
  const circ=2*Math.PI*26;bar.style.strokeDasharray=circ;bar.style.stroke='#FFD700';
  function tick(){num.textContent=state.timeLeft;const pct=state.timeLeft/sec;
    bar.style.strokeDashoffset=circ*(1-pct);
    bar.style.stroke=pct>.5?'#FFD700':pct>.25?'#FFA500':'#ff4757';
    if(state.timeLeft<=0){clearInterval(state.timer);timeUp();return;}state.timeLeft--;}
  tick();state.timer=setInterval(tick,1000);}

function timeUp(){if(state.answered)return;state.answered=true;disableAll();
  const btns=document.querySelectorAll('.answer-btn');
  btns.forEach(b=>{if(b.getAttribute('data-correct')==='1')b.classList.add('correct');});
  if(IS_EVENT&&HAS_REVIVE&&!state.reviveUsed&&REVIVE_COUNT>0){setTimeout(()=>offerRevive(-1),1800);}
  else{setTimeout(()=>showLose('⏰ Hết thời gian rồi!'),1800);}}

function selectAnswer(idx,isCorrect){if(state.answered)return;state.answered=true;clearInterval(state.timer);disableAll();
  const btn=document.getElementById('ans-'+idx);btn.classList.add('selected');
  setTimeout(()=>{btn.classList.remove('selected');
    if(isCorrect){btn.classList.add('correct');
      setTimeout(()=>{const lvl=state.current+1;if(PRIZES[lvl]&&PRIZES[lvl].safe)state.safeAmount=lvl;state.current++;if(state.current>=15){showWin();return;}showQuestion();},1800);}
    else{btn.classList.add('wrong');state.wrongIdx=idx;
      const btns=document.querySelectorAll('.answer-btn');let ci=-1;
      btns.forEach((b,i)=>{if(b.getAttribute('data-correct')==='1'){b.classList.add('correct');ci=i;}});
      state.wrongCorrectIdx=ci;
      if(IS_EVENT&&HAS_REVIVE&&!state.reviveUsed&&REVIVE_COUNT>0){setTimeout(()=>offerRevive(idx),1800);}
      else{setTimeout(()=>showLose('❌ Trả lời sai!'),2200);}}},900);}

function disableAll(){document.querySelectorAll('.answer-btn').forEach(b=>b.disabled=true);}

// REVIVE
function offerRevive(wrongIdx){
  const safeLevel=state.safeAmount;
  const safeP=safeLevel>0?PRIZES[safeLevel].prize:'0 đ';
  const nextP=PRIZES[state.current+1]?.prize||'0 đ';
  document.getElementById('reviveMsg').innerHTML=`Bạn đã trả lời <strong style="color:#ff4757">sai</strong>!<br>Dùng hồi sinh để tiếp tục từ câu <strong style="color:#2ed573">${state.current+1}</strong><br>Tiền an toàn hiện tại: <strong style="color:#FFD700">${safeP}</strong>`;
  document.getElementById('revivePrize').textContent=`💚 Còn ${REVIVE_COUNT} lần hồi sinh`;
  showModal('modalRevive');}

function doRevive(){
  closeModal('modalRevive');
  state.reviveUsed=true;REVIVE_COUNT--;
  // Reset current question state
  state.answered=false;state.hidden=[];
  const grid=document.getElementById('answersGrid');
  grid.querySelectorAll('.answer-btn').forEach(b=>{b.disabled=false;b.classList.remove('wrong','correct','selected','hidden');});
  // Flash revive effect
  document.body.style.boxShadow='inset 0 0 100px rgba(46,213,115,.3)';
  setTimeout(()=>document.body.style.boxShadow='',1000);
  startTimer(QUESTIONS[state.current].thoigian||30);
  // Update revive btn
  const rb=document.getElementById('llRevive');
  if(rb){rb.querySelector('.lifeline-name').textContent=`REVIVE ×${REVIVE_COUNT}`;if(REVIVE_COUNT<=0){rb.disabled=true;rb.classList.add('used');}}
  // Deduct from server
  fetch('use_revive.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'use'})});}

function confirmFinalLose(){closeModal('modalRevive');showLose('❌ Trả lời sai!');}

function useRevive(){if(!HAS_REVIVE||state.reviveUsed||REVIVE_COUNT<=0)return;offerRevive(state.wrongIdx);}

function useLifeline(type){
  if(type==='5050'&&state.lifelines.l5050)return;
  if(type==='audience'&&state.lifelines.audience)return;
  if(type==='phone'&&state.lifelines.phone)return;
  if(state.answered)return;
  const q=QUESTIONS[state.current];
  const btns=[...document.querySelectorAll('.answer-btn')];
  const correctIdx=btns.findIndex(b=>b.getAttribute('data-correct')==='1');
  if(type==='5050'){state.lifelines.l5050=true;document.getElementById('ll5050').disabled=true;do5050(btns,correctIdx);}
  else if(type==='audience'){state.lifelines.audience=true;document.getElementById('llAudience').disabled=true;doAudience(btns,correctIdx);}
  else if(type==='phone'){state.lifelines.phone=true;document.getElementById('llPhone').disabled=true;doPhone(btns,correctIdx,q.noidung);}
  state.usedCount++;}

function do5050(btns,correctIdx){const wrong=btns.map((_,i)=>i).filter(i=>i!==correctIdx);shuffle(wrong);wrong.slice(0,2).forEach(i=>{btns[i].classList.add('hidden');state.hidden.push(i);});}

function doAudience(btns,correctIdx){const cp=Math.floor(45+Math.random()*30);let rem=100-cp;const others=[];for(let i=0;i<btns.length-1;i++){const v=i===btns.length-2?rem:Math.floor(Math.random()*rem*.7);rem-=v;others.push(v);}const votes=[];let oi=0;for(let i=0;i<btns.length;i++){if(i===correctIdx)votes.push(cp);else votes.push(others[oi++]||0);}state.hidden.forEach(h=>votes[h]=0);const sum=votes.reduce((a,b)=>a+b,0);if(sum<100)votes[correctIdx]+=(100-sum);const el=document.getElementById('audienceBars');el.innerHTML='';votes.forEach((v,i)=>{if(state.hidden.includes(i))return;const row=document.createElement('div');row.className='aud-row';row.innerHTML=`<span class="aud-letter">${LETTERS[i]}</span><div class="aud-bar-wrap"><div class="aud-bar" style="width:0%" data-w="${v}%"></div></div><span class="aud-pct">${v}%</span>`;el.appendChild(row);});showModal('modalAudience');setTimeout(()=>document.querySelectorAll('.aud-bar').forEach(b=>b.style.width=b.getAttribute('data-w')),100);}

function doPhone(btns,correctIdx,question){const ct=btns[correctIdx].querySelector('.answer-text').textContent;const conf=Math.floor(60+Math.random()*35);const names=['anh Minh','chị Lan','bạn Hùng','thầy Nam','cô Mai'];const name=names[Math.floor(Math.random()*names.length)];const msgs=[`Tôi nghĩ đáp án là "${ct}". Khoảng ${conf}% chắc chắn. Chúc may mắn!`,`Theo tôi là "${ct}" – ${conf}% chắc. Quyết là của bạn nhé!`,`Hmm khó đấy! Tôi nghiêng về "${ct}", ${conf}% thôi.`];document.getElementById('phoneNameEl').textContent=`📱 ${name} đang nói...`;document.getElementById('phoneText').textContent=msgs[Math.floor(Math.random()*msgs.length)];showModal('modalPhone');}

function confirmQuit(){if(state.answered)return;clearInterval(state.timer);const lvl=state.current;const prize=lvl>0?PRIZES[lvl].prize:'0 đ';document.getElementById('quitPrize').textContent=prize;showModal('modalQuit');}
function doQuit(){closeModal('modalQuit');const lvl=state.current;document.getElementById('loseMsg').textContent='🤝 Bạn đã chọn dừng cuộc chơi.';document.getElementById('losePrize').textContent=lvl>0?PRIZES[lvl].prize:'0 đ';document.getElementById('loseDesc').textContent='Cảm ơn đã tham gia!';showModal('modalLose');}

function showWin(){fireConfetti();document.getElementById('winPrize').textContent=PRIZES[15].prize;showModal('modalWin');}
function showLose(reason){const sl=state.safeAmount;const prize=sl>0?PRIZES[sl].prize:'0 đ';document.getElementById('loseIcon').textContent='😢';document.getElementById('loseTitleEl').textContent='TIẾC QUÁ!';document.getElementById('loseMsg').textContent=reason;document.getElementById('losePrize').textContent=prize;document.getElementById('loseDesc').textContent=sl>0?`Bạn về mốc an toàn câu ${sl}: ${prize}`:'Chưa qua được mốc an toàn nào.';showModal('modalLose');}

function saveAndGo(isWin){if(state.savedResult){location.href='index.php';return;}state.savedResult=true;
  const lvl=isWin?15:state.safeAmount;const prizeRaw=lvl>0?PRIZES[lvl].prize:'0 đ';
  const prizeNum=parseFloat(prizeRaw.replace(/[^\d.]/g,''))||0;
  fetch('save_result.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({linhvuc_id:<?=$linhvuc_id?>,sotien:prizeNum,cau_dat_duoc:state.current,so_trogiup:state.usedCount,tong_thoigian:0,ketqua:isWin?'Thắng':'Thua',lifelines:state.lifelines,is_event:IS_EVENT,revive_used:state.reviveUsed})}).then(()=>location.href='index.php');}

function showModal(id){document.getElementById(id).classList.add('show');}
function closeModal(id){document.getElementById(id).classList.remove('show');}
function shuffle(arr){for(let i=arr.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[arr[i],arr[j]]=[arr[j],arr[i]];}return arr;}
function fireConfetti(){const c=['#FFD700','#FFA500','#FF6B6B','#48CAE4','#90E0EF','#FFFFFF','#FF9A3C'];for(let i=0;i<80;i++){const el=document.createElement('div');el.className='confetti-piece';el.style.cssText=`left:${Math.random()*100}vw;background:${c[Math.floor(Math.random()*c.length)]};--cf-d:${1.5+Math.random()*2.5}s;--cf-delay:${Math.random()*2}s;width:${6+Math.random()*8}px;height:${6+Math.random()*8}px;border-radius:${Math.random()>.5?'50%':'2px'}`;document.body.appendChild(el);setTimeout(()=>el.remove(),5000);}}
</script>
</body>
</html>
