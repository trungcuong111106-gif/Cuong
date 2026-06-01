<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('auth.php');
$user = getCurrentUser();
if (!$user || !is_array($user)) { session_destroy(); redirect('auth.php'); }
$db = getDB();
$merge_best = 0;
try {
    $ms=$db->prepare("SELECT best_score,best_tile FROM merge_leaderboard WHERE taikhoan_id=?");
    $ms->execute([$user['id']]); $mr=$ms->fetch();
    $merge_best=$mr?$mr['best_score']:0;
    $merge_tile=$mr?$mr['best_tile']:0;
} catch(Exception $e){ $merge_tile=0; }
// Leaderboard
$lb_merge=[];
try{$lb_merge=$db->query("SELECT t.hoten,m.best_score,m.best_tile FROM merge_leaderboard m JOIN taikhoan t ON m.taikhoan_id=t.id ORDER BY m.best_score DESC LIMIT 8")->fetchAll();}catch(Exception $e){}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Merge Game 2048 – Ai Là Tỉ Phú</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
:root{--dark:#0a0612;--gold:#FFD700;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Rajdhani',sans-serif;background:var(--dark);min-height:100vh;color:#fff;overflow-x:hidden;}
.bg-fx{position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse at 20% 30%,rgba(124,58,237,.15) 0%,transparent 55%),radial-gradient(ellipse at 80% 70%,rgba(167,139,250,.08) 0%,transparent 55%),var(--dark);}
.stars{position:fixed;inset:0;z-index:0;overflow:hidden;}
.star{position:absolute;background:#fff;border-radius:50%;animation:tw var(--d) ease-in-out infinite alternate;}
@keyframes tw{from{opacity:.04}to{opacity:.6}}
header{position:sticky;top:0;z-index:100;background:rgba(124,58,237,.08);border-bottom:1px solid rgba(124,58,237,.2);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(12px);}
.logo{font-family:'Cinzel Decorative',serif;font-size:.95rem;background:linear-gradient(135deg,#a78bfa,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.btn-back{font-family:'Orbitron',sans-serif;font-size:.52rem;letter-spacing:1px;color:rgba(167,139,250,.7);text-decoration:none;border:1px solid rgba(124,58,237,.3);border-radius:20px;padding:6px 13px;transition:all .3s;}
.btn-back:hover{border-color:#a78bfa;color:#a78bfa;}
.game-wrap{position:relative;z-index:10;max-width:900px;margin:0 auto;padding:20px 14px;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start;}
@media(max-width:640px){.game-wrap{grid-template-columns:1fr;}}
/* Scoreboard */
.score-row{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
.score-box{flex:1;min-width:85px;background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.25);border-radius:12px;padding:10px 14px;text-align:center;}
.score-label{font-family:'Orbitron',sans-serif;font-size:.5rem;letter-spacing:2px;color:rgba(167,139,250,.6);margin-bottom:4px;}
.score-val{font-family:'Orbitron',sans-serif;font-size:1.3rem;font-weight:900;color:#a78bfa;}
.score-val.gold{color:#FFD700;}
/* Board */
.board-container{position:relative;}
.board-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.game-title{font-family:'Cinzel Decorative',serif;font-size:1.3rem;background:linear-gradient(135deg,#a78bfa,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.btn-new{font-family:'Orbitron',sans-serif;font-size:.55rem;letter-spacing:1px;background:linear-gradient(135deg,#7c3aed,#a78bfa);border:none;border-radius:10px;padding:9px 16px;color:#fff;cursor:pointer;transition:all .3s;}
.btn-new:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(124,58,237,.4);}
#board{display:grid;grid-template-columns:repeat(4,1fr);grid-template-rows:repeat(4,1fr);gap:10px;background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.25);border-radius:16px;padding:10px;width:min(380px,95vw);height:min(380px,95vw);user-select:none;}
.cell{border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-weight:900;transition:all .1s ease;position:relative;overflow:hidden;}
.cell.empty{background:rgba(255,255,255,.04);}
.cell.new-tile{animation:newTile .2s ease;}
.cell.merge-tile{animation:mergeTile .2s ease;}
@keyframes newTile{from{transform:scale(0)}to{transform:scale(1)}}
@keyframes mergeTile{0%{transform:scale(1)}50%{transform:scale(1.12)}100%{transform:scale(1)}}
/* Tile colors */
.t2{background:linear-gradient(135deg,#e0d8f0,#c4b5fd);color:#1a0f2e;font-size:clamp(1.1rem,4vw,1.5rem);}
.t4{background:linear-gradient(135deg,#c4b5fd,#a78bfa);color:#1a0f2e;font-size:clamp(1.1rem,4vw,1.5rem);}
.t8{background:linear-gradient(135deg,#a78bfa,#7c3aed);color:#fff;font-size:clamp(1rem,3.5vw,1.4rem);}
.t16{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:clamp(1rem,3.5vw,1.3rem);}
.t32{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:clamp(.9rem,3vw,1.2rem);}
.t64{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:clamp(.9rem,3vw,1.2rem);}
.t128{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:clamp(.8rem,2.8vw,1.1rem);}
.t256{background:linear-gradient(135deg,#ec4899,#db2777);color:#fff;font-size:clamp(.75rem,2.5vw,1rem);}
.t512{background:linear-gradient(135deg,#14b8a6,#0d9488);color:#fff;font-size:clamp(.7rem,2.3vw,.95rem);}
.t1024{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-size:clamp(.65rem,2vw,.88rem);}
.t2048{background:linear-gradient(135deg,#FFD700,#FFA500);color:#0a0612;font-size:clamp(.6rem,1.8vw,.82rem);box-shadow:0 0 30px rgba(255,215,0,.6);}
.t4096{background:linear-gradient(135deg,#fff,#e0e0e0);color:#0a0612;font-size:clamp(.55rem,1.6vw,.75rem);box-shadow:0 0 30px rgba(255,255,255,.5);}
/* Swipe instructions */
.swipe-hint{display:flex;gap:8px;margin-top:10px;justify-content:center;flex-wrap:wrap;}
.hint-key{font-family:'Orbitron',sans-serif;font-size:.5rem;background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.25);border-radius:7px;padding:4px 8px;color:rgba(167,139,250,.7);}
/* Game over overlay */
.board-overlay{position:absolute;inset:0;background:rgba(10,6,18,.88);border-radius:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:10;opacity:0;pointer-events:none;transition:opacity .4s;backdrop-filter:blur(6px);}
.board-overlay.show{opacity:1;pointer-events:all;}
.ov-title{font-family:'Cinzel Decorative',serif;font-size:1.4rem;margin-bottom:8px;}
.ov-score{font-family:'Orbitron',sans-serif;font-size:.9rem;color:#FFD700;margin-bottom:18px;}
/* Sidebar */
.sidebar{min-width:220px;}
@media(max-width:640px){.sidebar{min-width:100%;}}
.panel{background:rgba(124,58,237,.06);border:1px solid rgba(124,58,237,.2);border-radius:14px;padding:16px;margin-bottom:14px;}
.panel-title{font-family:'Orbitron',sans-serif;font-size:.6rem;letter-spacing:3px;color:rgba(167,139,250,.6);margin-bottom:12px;}
.lb-row{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem;}
.lb-row:last-child{border:none;}
.lb-rank{font-family:'Orbitron',sans-serif;font-size:.7rem;font-weight:900;width:22px;text-align:center;}
.r1{color:#FFD700}.r2{color:#C0C0C0}.r3{color:#CD7F32}
.lb-name{flex:1;font-weight:600;}
.lb-score{font-family:'Orbitron',sans-serif;font-size:.58rem;color:#a78bfa;}
.lb-tile{font-size:.7rem;margin-left:4px;}
.how-item{display:flex;gap:10px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.how-item:last-child{border:none;}
.how-key{font-family:'Orbitron',sans-serif;font-size:1rem;width:28px;text-align:center;}
.how-text{font-size:.78rem;color:rgba(255,255,255,.5);line-height:1.4;}
</style>
</head>
<body>
<div class="bg-fx"></div><div class="stars" id="stars"></div>
<header>
  <div class="logo">🔢 Merge Game 2048</div>
  <a href="index.php" class="btn-back">← TRANG CHỦ</a>
</header>

<div class="game-wrap">
  <div class="board-container">
    <div class="score-row">
      <div class="score-box"><div class="score-label">ĐIỂM</div><div class="score-val" id="scoreVal">0</div></div>
      <div class="score-box"><div class="score-label">KỶ LỤC</div><div class="score-val gold" id="bestVal"><?= number_format($merge_best) ?></div></div>
      <div class="score-box"><div class="score-label">Ô CAO NHẤT</div><div class="score-val" id="tileVal" style="color:#a78bfa"><?= $merge_tile ?: '-' ?></div></div>
    </div>

    <div class="board-header">
      <div class="game-title">2048</div>
      <button class="btn-new" onclick="newGame()">🔄 VÁN MỚI</button>
    </div>

    <div style="position:relative">
      <div id="board"></div>
      <div class="board-overlay" id="boardOverlay">
        <div class="ov-title" id="ovTitle">GAME OVER</div>
        <div class="ov-score" id="ovScore"></div>
        <button class="btn-new" onclick="newGame()" style="margin-top:8px">🔄 CHƠI LẠI</button>
      </div>
    </div>

    <div class="swipe-hint">
      <span class="hint-key">⬆️ W / ↑</span>
      <span class="hint-key">⬇️ S / ↓</span>
      <span class="hint-key">⬅️ A / ←</span>
      <span class="hint-key">➡️ D / →</span>
      <span class="hint-key">📱 VUỐT</span>
    </div>
  </div>

  <div class="sidebar">
    <div class="panel">
      <div class="panel-title">🏆 BẢNG XẾP HẠNG</div>
      <?php if(empty($lb_merge)):?>
        <p style="color:rgba(255,255,255,.3);font-size:.8rem;text-align:center;padding:10px">Chưa có ai chơi!</p>
      <?php else: foreach($lb_merge as $i=>$row): $rank=$i+1; ?>
        <div class="lb-row">
          <div class="lb-rank <?=$rank<=3?'r'.$rank:''?>"><?=['🥇','🥈','🥉'][$i]??$rank?></div>
          <div class="lb-name"><?=htmlspecialchars($row['hoten'])?></div>
          <div class="lb-score"><?=number_format($row['best_score'])?></div>
          <div class="lb-tile"><?=$row['best_tile']>0?'['.$row['best_tile'].']':''?></div>
        </div>
      <?php endforeach; endif;?>
    </div>

    <div class="panel">
      <div class="panel-title">📖 CÁCH CHƠI</div>
      <div class="how-item"><div class="how-key">🎯</div><div class="how-text">Gộp các ô có <strong>số giống nhau</strong> để tạo số lớn hơn</div></div>
      <div class="how-item"><div class="how-key">🔢</div><div class="how-text">Mục tiêu đạt được ô <strong style="color:#FFD700">2048</strong></div></div>
      <div class="how-item"><div class="how-key">💀</div><div class="how-text">Thua khi không còn nước đi nào</div></div>
      <div class="how-item"><div class="how-key">🏆</div><div class="how-text">Điểm cao nhất sẽ được <strong>lưu tự động</strong></div></div>
    </div>
  </div>
</div>

<script>
// ===== 2048 GAME ENGINE =====
const SIZE=4;
let board=[],score=0,bestScore=<?= $merge_best ?>,gameOver=false,won=false,moved=false;

function newGame(){board=Array.from({length:SIZE},()=>Array(SIZE).fill(0));score=0;gameOver=false;won=false;addTile();addTile();render();document.getElementById('boardOverlay').classList.remove('show');}

function addTile(){const empty=[];for(let r=0;r<SIZE;r++)for(let c=0;c<SIZE;c++)if(!board[r][c])empty.push([r,c]);if(!empty.length)return null;const[r,c]=empty[Math.floor(Math.random()*empty.length)];board[r][c]=Math.random()<.9?2:4;return[r,c];}

function getColor(v){const map={2:'t2',4:'t4',8:'t8',16:'t16',32:'t32',64:'t64',128:'t128',256:'t256',512:'t512',1024:'t1024',2048:'t2048',4096:'t4096'};return map[v]||'t4096';}

function render(){const el=document.getElementById('board');el.innerHTML='';for(let r=0;r<SIZE;r++)for(let c=0;c<SIZE;c++){const v=board[r][c];const cell=document.createElement('div');cell.className='cell '+(v?getColor(v):'empty');if(v)cell.textContent=v;el.appendChild(cell);}document.getElementById('scoreVal').textContent=score.toLocaleString();document.getElementById('bestVal').textContent=bestScore.toLocaleString();const maxTile=Math.max(...board.flat());document.getElementById('tileVal').textContent=maxTile>0?maxTile:'-';}

function slideRow(row){let arr=row.filter(x=>x);const merged=[];while(arr.length){if(arr.length>1&&arr[0]===arr[1]){const v=arr[0]*2;merged.push(v);score+=v;if(v>bestScore)bestScore=v;arr=arr.slice(2);}else{merged.push(arr[0]);arr=arr.slice(1);}}while(merged.length<SIZE)merged.push(0);return merged;}

function move(dir){let didMove=false;let prev=JSON.stringify(board);
  if(dir==='left'){board=board.map(r=>slideRow(r));}
  else if(dir==='right'){board=board.map(r=>slideRow(r.reverse()).reverse());}
  else if(dir==='up'){for(let c=0;c<SIZE;c++){let col=board.map(r=>r[c]);col=slideRow(col);for(let r=0;r<SIZE;r++)board[r][c]=col[r];}}
  else if(dir==='down'){for(let c=0;c<SIZE;c++){let col=board.map(r=>r[c]).reverse();col=slideRow(col).reverse();for(let r=0;r<SIZE;r++)board[r][c]=col[r];}}
  if(JSON.stringify(board)!==prev){addTile();didMove=true;}
  render();
  if(didMove)saveScore();
  checkGameOver();}

function checkGameOver(){const flat=board.flat();if(flat.includes(2048)&&!won){won=true;showOverlay('🏆 ĐẠT 2048!',true);return;}
  for(let r=0;r<SIZE;r++)for(let c=0;c<SIZE;c++){if(!board[r][c])return;if(r<SIZE-1&&board[r][c]===board[r+1][c])return;if(c<SIZE-1&&board[r][c]===board[r][c+1])return;}
  gameOver=true;showOverlay('💀 GAME OVER',false);}

function showOverlay(title,isWin){const ov=document.getElementById('boardOverlay');document.getElementById('ovTitle').textContent=title;document.getElementById('ovTitle').style.color=isWin?'#FFD700':'#ff4757';document.getElementById('ovScore').textContent='Điểm: '+score.toLocaleString()+' | Cao nhất: '+bestScore.toLocaleString();ov.classList.add('show');}

function saveScore(){const maxTile=Math.max(...board.flat());fetch('save_merge.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({score:score,best_tile:maxTile})});}

// Keyboard
document.addEventListener('keydown',e=>{if(gameOver&&!won)return;const map={'ArrowUp':'up','ArrowDown':'down','ArrowLeft':'left','ArrowRight':'right','w':'up','s':'down','a':'left','d':'right','W':'up','S':'down','A':'left','D':'right'};if(map[e.key]){e.preventDefault();move(map[e.key]);}});

// Touch swipe
let tx=0,ty=0;
document.getElementById('board').addEventListener('touchstart',e=>{tx=e.touches[0].clientX;ty=e.touches[0].clientY;},{passive:true});
document.getElementById('board').addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-tx,dy=e.changedTouches[0].clientY-ty;if(Math.abs(dx)<10&&Math.abs(dy)<10)return;if(Math.abs(dx)>Math.abs(dy)){move(dx>0?'right':'left');}else{move(dy>0?'down':'up');}},{passive:true});

// Stars
const s=document.getElementById('stars');for(let i=0;i<80;i++){const el=document.createElement('div');el.className='star';const sz=Math.random()*2+.4;el.style.cssText=`width:${sz}px;height:${sz}px;left:${Math.random()*100}%;top:${Math.random()*100}%;--d:${2+Math.random()*5}s;animation-delay:${Math.random()*5}s`;s.appendChild(el);}

newGame();
</script>
</body>
</html>
