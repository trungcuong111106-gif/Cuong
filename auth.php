<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $hoten = trim($_POST['hoten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sodt  = trim($_POST['sodt'] ?? '');
        $matkhau = $_POST['matkhau'] ?? '';
        $xacnhan = $_POST['xacnhan'] ?? '';

        if (!$hoten || !$email || !$matkhau) {
            $error = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ!';
        } elseif (strlen($matkhau) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } elseif ($matkhau !== $xacnhan) {
            $error = 'Mật khẩu xác nhận không khớp!';
        } else {
            $stmt = $db->prepare("SELECT id FROM taikhoan WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email này đã được sử dụng!';
            } else {
                $hash = password_hash($matkhau, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO taikhoan (hoten, email, sodt, matkhau) VALUES (?,?,?,?)");
                $stmt->execute([$hoten, $email, $sodt ?: null, $hash]);
                $new_id = $db->lastInsertId();
                // Auto-grant 1 revive to new user
                try {
                    $db->prepare("INSERT INTO user_items (taikhoan_id,item_type,so_luong) VALUES (?,?,?) ON DUPLICATE KEY UPDATE so_luong=so_luong")->execute([$new_id,'revive',1]);
                } catch(Exception $ex) {}
                $success = 'Đăng ký thành công! Hãy đăng nhập. Bạn nhận được 💚 1 Hồi sinh miễn phí!';
                $mode = 'login';
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $matkhau = $_POST['matkhau'] ?? '';
        
        if (!$email || !$matkhau) {
            $error = 'Vui lòng nhập email và mật khẩu!';
        } else {
            $stmt = $db->prepare("SELECT * FROM taikhoan WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($matkhau, $user['matkhau'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['hoten'];
                redirect('index.php');
            } else {
                $error = 'Email hoặc mật khẩu không đúng!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ai Là Tỉ Phú – Đăng Nhập</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #FFD700;
  --gold2: #FFA500;
  --gold3: #B8860B;
  --dark: #0a0612;
  --dark2: #110920;
  --dark3: #1a0f2e;
  --accent: #9b59b6;
  --accent2: #6c3483;
  --glow: 0 0 20px rgba(255,215,0,0.6);
  --glow2: 0 0 40px rgba(255,215,0,0.3);
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family: 'Rajdhani', sans-serif;
  background: var(--dark);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

/* Animated background */
.bg-stars {
  position: fixed;
  inset: 0;
  background: 
    radial-gradient(ellipse at 20% 50%, rgba(155,89,182,0.15) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(255,165,0,0.1) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 80%, rgba(255,215,0,0.08) 0%, transparent 50%),
    var(--dark);
  z-index: 0;
}

.stars {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
}

.star {
  position: absolute;
  background: white;
  border-radius: 50%;
  animation: twinkle var(--d) ease-in-out infinite alternate;
}

@keyframes twinkle {
  from { opacity: 0.1; transform: scale(0.8); }
  to   { opacity: 0.9; transform: scale(1.2); }
}

.spotlight {
  position: fixed;
  top: -200px;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 700px;
  background: conic-gradient(from 270deg at 50% 0%, transparent 0deg, rgba(255,215,0,0.08) 30deg, transparent 60deg);
  animation: spotMove 6s ease-in-out infinite alternate;
  z-index: 0;
}

@keyframes spotMove {
  from { transform: translateX(-60%) rotate(-5deg); }
  to   { transform: translateX(-40%) rotate(5deg); }
}

/* Container */
.auth-wrap {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 480px;
  padding: 20px;
}

/* Logo */
.logo-area {
  text-align: center;
  margin-bottom: 30px;
  animation: fadeDown 0.8s ease;
}

@keyframes fadeDown {
  from { opacity:0; transform: translateY(-30px); }
  to   { opacity:1; transform: translateY(0); }
}

.logo-title {
  font-family: 'Cinzel Decorative', serif;
  font-size: clamp(1.4rem, 4vw, 2rem);
  font-weight: 900;
  background: linear-gradient(135deg, #FFD700 0%, #FFF176 40%, #FFD700 60%, #FF8C00 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: none;
  filter: drop-shadow(0 0 15px rgba(255,215,0,0.7));
  line-height: 1.2;
  letter-spacing: 2px;
}

.logo-sub {
  font-family: 'Orbitron', sans-serif;
  font-size: 0.7rem;
  color: rgba(255,215,0,0.5);
  letter-spacing: 4px;
  margin-top: 6px;
}

.trophy-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 8px;
  animation: float 3s ease-in-out infinite;
  filter: drop-shadow(0 0 15px rgba(255,165,0,0.8));
}

@keyframes float {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-8px); }
}

/* Card */
.auth-card {
  background: linear-gradient(145deg, rgba(26,15,46,0.95) 0%, rgba(17,9,32,0.98) 100%);
  border: 1px solid rgba(255,215,0,0.3);
  border-radius: 20px;
  padding: 36px 32px;
  box-shadow: 
    0 0 0 1px rgba(255,215,0,0.1),
    0 20px 60px rgba(0,0,0,0.6),
    inset 0 1px 0 rgba(255,215,0,0.2);
  animation: fadeUp 0.8s ease 0.2s both;
  position: relative;
  overflow: hidden;
}

.auth-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #FFD700, #FFA500, #FFD700, transparent);
  animation: shimmer 3s linear infinite;
}

@keyframes shimmer {
  0%   { background-position: -200% center; }
  100% { background-position: 200% center; }
}

@keyframes fadeUp {
  from { opacity:0; transform: translateY(30px); }
  to   { opacity:1; transform: translateY(0); }
}

/* Tabs */
.tabs {
  display: flex;
  background: rgba(0,0,0,0.4);
  border-radius: 50px;
  padding: 4px;
  margin-bottom: 28px;
  border: 1px solid rgba(255,215,0,0.15);
}

.tab-btn {
  flex: 1;
  padding: 10px 16px;
  border: none;
  background: transparent;
  border-radius: 50px;
  font-family: 'Orbitron', sans-serif;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 2px;
  color: rgba(255,215,0,0.4);
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.tab-btn.active {
  background: linear-gradient(135deg, #FFD700, #FFA500);
  color: #0a0612;
  box-shadow: 0 0 20px rgba(255,165,0,0.4);
}

.tab-btn:hover:not(.active) {
  color: rgba(255,215,0,0.8);
}

/* Form */
.form-group {
  margin-bottom: 18px;
  position: relative;
}

.form-label {
  display: block;
  font-family: 'Orbitron', sans-serif;
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 2px;
  color: rgba(255,215,0,0.6);
  margin-bottom: 8px;
  text-transform: uppercase;
}

.input-wrap {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  opacity: 0.5;
  pointer-events: none;
}

.form-input {
  width: 100%;
  padding: 13px 16px 13px 42px;
  background: rgba(0,0,0,0.4);
  border: 1px solid rgba(255,215,0,0.2);
  border-radius: 12px;
  font-family: 'Rajdhani', sans-serif;
  font-size: 1rem;
  font-weight: 500;
  color: #fff;
  transition: all 0.3s ease;
  outline: none;
}

.form-input:focus {
  border-color: rgba(255,215,0,0.7);
  background: rgba(255,215,0,0.05);
  box-shadow: 0 0 0 3px rgba(255,215,0,0.1), 0 0 20px rgba(255,165,0,0.15);
}

.form-input::placeholder { color: rgba(255,255,255,0.2); }

/* Alert */
.alert {
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-error {
  background: rgba(231,76,60,0.15);
  border: 1px solid rgba(231,76,60,0.4);
  color: #ff6b6b;
}

.alert-success {
  background: rgba(46,213,115,0.15);
  border: 1px solid rgba(46,213,115,0.4);
  color: #2ed573;
}

/* Button */
.btn-submit {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
  border: none;
  border-radius: 12px;
  font-family: 'Orbitron', sans-serif;
  font-size: 0.85rem;
  font-weight: 900;
  letter-spacing: 3px;
  color: #0a0612;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
}

.btn-submit::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s ease;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(255,165,0,0.5), 0 0 0 1px rgba(255,215,0,0.5);
}

.btn-submit:hover::before { left: 100%; }
.btn-submit:active { transform: translateY(0); }

/* Money display */
.prize-display {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.prize-chip {
  font-family: 'Orbitron', sans-serif;
  font-size: 0.55rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 1px;
}

.prize-chip:nth-child(1) { background: rgba(255,215,0,0.1); color: rgba(255,215,0,0.5); border: 1px solid rgba(255,215,0,0.2); }
.prize-chip:nth-child(2) { background: rgba(255,165,0,0.1); color: rgba(255,165,0,0.6); border: 1px solid rgba(255,165,0,0.25); }
.prize-chip:nth-child(3) { background: rgba(255,100,0,0.1); color: rgba(255,120,0,0.7); border: 1px solid rgba(255,100,0,0.3); }
.prize-chip:nth-child(4) { background: rgba(255,50,0,0.1); color: rgba(255,80,0,0.8); border: 1px solid rgba(255,50,0,0.35); }

.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 16px 0;
}

.divider::before, .divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(255,215,0,0.15);
}

.divider span {
  font-family: 'Orbitron', sans-serif;
  font-size: 0.55rem;
  color: rgba(255,215,0,0.3);
  letter-spacing: 2px;
}

/* Responsive */
@media (max-width: 480px) {
  .auth-card { padding: 28px 20px; }
  .logo-title { font-size: 1.3rem; }
}
</style>
</head>
<body>

<div class="bg-stars"></div>
<div class="stars" id="stars"></div>
<div class="spotlight"></div>

<div class="auth-wrap">
  <!-- Logo -->
  <div class="logo-area">
    <span class="trophy-icon">🏆</span>
    <div class="logo-title">Ai Là Tỉ Phú</div>
    <div class="logo-sub">TRIỆU PHÚ VIỆT NAM</div>
  </div>

  <!-- Prize chips -->
  <div class="prize-display">
    <span class="prize-chip">1 TRIỆU</span>
    <span class="prize-chip">100 TRIỆU</span>
    <span class="prize-chip">1 TỶ</span>
    <span class="prize-chip">10 TỶ 🔥</span>
  </div>

  <!-- Card -->
  <div class="auth-card">
    <!-- Tabs -->
    <div class="tabs">
      <a href="?mode=login" class="tab-btn <?= $mode === 'login' ? 'active' : '' ?>">
        🔐 Đăng Nhập
      </a>
      <a href="?mode=register" class="tab-btn <?= $mode === 'register' ? 'active' : '' ?>">
        ✨ Đăng Ký
      </a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($mode === 'login'): ?>
    <!-- LOGIN FORM -->
    <form method="POST">
      <input type="hidden" name="action" value="login">
      
      <div class="form-group">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <span class="input-icon">📧</span>
          <input type="email" name="email" class="form-input" placeholder="your@email.com" 
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input type="password" name="matkhau" class="form-input" placeholder="••••••••" autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn-submit">🚀 Bắt Đầu Chơi</button>
    </form>

    <?php else: ?>
    <!-- REGISTER FORM -->
    <form method="POST">
      <input type="hidden" name="action" value="register">

      <div class="form-group">
        <label class="form-label">Họ và Tên *</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="hoten" class="form-input" placeholder="Nguyễn Văn A"
                 value="<?= htmlspecialchars($_POST['hoten'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email *</label>
        <div class="input-wrap">
          <span class="input-icon">📧</span>
          <input type="email" name="email" class="form-input" placeholder="your@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Số điện thoại</label>
        <div class="input-wrap">
          <span class="input-icon">📱</span>
          <input type="tel" name="sodt" class="form-input" placeholder="0900000000"
                 value="<?= htmlspecialchars($_POST['sodt'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Mật khẩu *</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input type="password" name="matkhau" class="form-input" placeholder="Tối thiểu 6 ký tự">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Xác nhận mật khẩu *</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="xacnhan" class="form-input" placeholder="Nhập lại mật khẩu">
        </div>
      </div>

      <button type="submit" class="btn-submit">🌟 Tạo Tài Khoản</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
// Generate stars
const starsEl = document.getElementById('stars');
for (let i = 0; i < 120; i++) {
  const s = document.createElement('div');
  s.className = 'star';
  const size = Math.random() * 2.5 + 0.5;
  s.style.cssText = `
    width:${size}px; height:${size}px;
    left:${Math.random()*100}%;
    top:${Math.random()*100}%;
    --d:${2 + Math.random()*4}s;
    animation-delay:${Math.random()*4}s;
  `;
  starsEl.appendChild(s);
}
</script>
</body>
</html>
