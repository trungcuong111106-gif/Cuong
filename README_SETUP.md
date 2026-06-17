# 🎮 Mario-Style Game – Unity Setup Guide

## Yêu Cầu
- Unity 2022.3 LTS trở lên
- Package: **TextMeshPro** (Window > Package Manager)
- Package: **Unity Gaming Services** (tùy chọn – cho Account & Cloud Save thật)

---

## Cấu Trúc Scripts

```
Assets/Scripts/
├── Player/
│   ├── PlayerController.cs      ← Di chuyển, nhảy, biến hình, va chạm
│   └── FireballSpawner.cs       ← Bắn lửa, viên đạn Fireball
├── Enemy/
│   ├── EnemyBase.cs             ← Lớp base cho mọi kẻ địch
│   └── Enemies.cs               ← Goomba, Koopa + mai Koopa
├── Powerup/
│   └── PowerupItem.cs           ← Nấm, Hoa, Sao, Xu, QuestionBlock
├── World/
│   └── WorldMechanics.cs        ← Platform, Pipe, Flag, Brick, CoinBlock
├── System/
│   ├── GameManager.cs           ← Mạng, điểm, xu, checkpoint, hồi sinh
│   ├── SaveSystem.cs            ← Auto-save, 3 save slots, cloud sync
│   ├── AchievementSystem.cs     ← % hoàn thành, vật phẩm hiếm, thành tích
│   ├── AudioManager.cs          ← Nhạc nền và SFX
│   └── AccountSystem.cs         ← Đăng nhập, leaderboard
└── UI/
    ├── UIManager.cs             ← HUD, popup, pause, save slot UI
    └── ShopSystem.cs            ← Cửa hàng trang phục & vật phẩm
```

---

## Thiết Lập Scene

### 1. Game Manager Object
Tạo **Empty GameObject** tên `_GameManager`, thêm các component:
- `GameManager`
- `SaveSystem`
- `AchievementSystem`
- `AudioManager`
- `AccountSystem`
- `FireballSpawner`

### 2. Player Setup
Tạo **Sprite GameObject** tên `Player`:
- Tag: `Player`
- Layer: `Player`
- Components: `PlayerController`, `Rigidbody2D`, `CapsuleCollider2D`, `Animator`
- Child: `GroundCheck` (Empty, đặt dưới chân) → gán vào `groundCheck`
- Rigidbody2D: Freeze Rotation Z

### 3. Layer Setup (Edit > Project Settings > Tags & Layers)
```
Layer 8:  Ground
Layer 9:  Player
Layer 10: Enemy
Layer 11: Powerup
```

### 4. Physics (Edit > Project Settings > Physics 2D)
- Tắt collision: `Player ↔ Powerup` (để dùng Trigger thay)
- Tắt collision: `Enemy ↔ Enemy` (nếu muốn quái không đẩy nhau)

### 5. Animator Controller – Player
States:
```
Idle → Walk (Speed > 0.1)
Walk → Run (IsRunning = true)
Any  → Jump (!IsGrounded)
Jump → Fall (VelocityY < 0)
Fall → Idle (IsGrounded)
```
Parameters: `Speed (Float)`, `IsGrounded (Bool)`, `VelocityY (Float)`, `IsRunning (Bool)`, `State (Int)`

### 6. Prefabs Cần Tạo
| Prefab | Components |
|--------|-----------|
| `Goomba` | `Goomba`, `Rigidbody2D`, `BoxCollider2D`, `Animator` |
| `Koopa` | `Koopa`, `Rigidbody2D`, `CapsuleCollider2D`, `Animator` |
| `Mushroom` | `PowerupItem(type=Mushroom)`, `Rigidbody2D`, `CircleCollider2D(Trigger)`, `PowerupRise` |
| `FireFlower` | `PowerupItem(type=FireFlower)`, `CircleCollider2D(Trigger)`, `PowerupRise` |
| `Star` | `PowerupItem(type=Star)`, `Rigidbody2D`, `CircleCollider2D(Trigger)`, `PowerupRise` |
| `Coin` | `CoinPickup`, `CircleCollider2D(Trigger)` – Tag: `Coin` |
| `Fireball` | `Fireball`, `Rigidbody2D`, `CircleCollider2D` |
| `QuestionBlock` | `QuestionBlock`, `BoxCollider2D` – Tag: `Wall` |

### 7. UI Canvas
```
Canvas (Screen Space - Overlay)
├── HUD Panel
│   ├── LivesText (TMP)
│   ├── ScoreText (TMP)
│   ├── CoinsText (TMP)
│   ├── TimerText (TMP)
│   ├── CompletionText (TMP)
│   └── RareItemsText (TMP)
├── MessagePanel (hidden by default)
│   └── MessageText (TMP)
├── AchievementPopup (hidden)
│   ├── AchievementTitle (TMP)
│   └── AchievementDesc (TMP)
├── PausePanel (hidden)
│   ├── ResumeButton
│   └── SaveMenuButton
└── SaveSlotPanel (hidden)
    ├── SlotButton0 (SaveSlotButton component)
    ├── SlotButton1
    └── SlotButton2
```

---

## Cơ Chế Chi Tiết

### Quán Tính & Đà
- `acceleration = 15` → tăng tốc mượt
- `deceleration = 20` → dừng tự nhiên
- Nhảy khi đang chạy: `runJumpBonus = 3`, `runJumpDistanceBonus = 2`

### Va Chạm
- Giẫm đầu quái: kiểm tra `relativeY > 0.3f && velocityY < -0.5f`
- Húc đầu dưới khối: kiểm tra `relativeY < -0.4f`

### Hệ Thống Mạng
- Nhặt 100 xu → +1 mạng (tự động reset coins)
- Chết → hồi sinh tại checkpoint hoặc đầu màn
- Hết mạng → Game Over → về TitleScreen

### Lưu Game
- **Auto-save**: tự động sau checkpoint/hoàn thành màn
- **3 Save Slots**: mở từ Pause Menu
- **Cloud Save**: file JSON lưu tại `Application.persistentDataPath`
  - Thay bằng Unity Cloud Save SDK cho production

---

## Build Settings
File → Build Settings:
```
Scene 0: TitleScreen
Scene 1: World1-1
Scene 2: World1-2
...
Scene N: EndScreen
```

## Audio Files Cần Có
Đặt AudioClip vào AudioManager và đặt tên:
`jump`, `coin`, `powerup`, `stomp`, `kick`, `die`, `1up`, `pipe`,
`blockHit`, `breakBlock`, `fireball`, `flagpole`, `checkpoint`,
`achievement`, `levelTheme`, `starTheme`, `levelClear`

---

## Các Scripts Bổ Sung (Phần 2)

| Script | Chức Năng |
|--------|-----------|
| `CameraController.cs` | Theo dõi Mario mượt, giới hạn biên, rung camera, không cuộn lùi |
| `ParallaxBackground.cs` | Nền cuộn nhiều lớp với tốc độ khác nhau |
| `EnemyPatrol.cs` | AI tuần tra: dừng tại mép sàn, phát hiện & đuổi theo Mario |
| `InputManager.cs` | Hỗ trợ bàn phím PC và nút cảm ứng Mobile (Joystick ảo) |
| `LevelManager.cs` | Spawn quái/vật phẩm, theo dõi % hoàn thành, khu vực bí mật |
| `PlayerAnimator.cs` | Squash & Stretch, đổi AnimatorController theo state |
| `SceneLoader.cs` | Fade màn hình, World Card (WORLD 1-1), loading bar |
| `CoinEffect.cs` | Coin bay lên HUD, ItemRiser cho vật phẩm nổi ra từ khối |
| `PoolManager.cs` | Object Pool tái sử dụng để tối ưu hiệu năng |

---

## Thiết Lập Camera

1. Chọn **Main Camera** → Add `CameraController`
2. Gán `target` = Player transform
3. Set `offset` = `(2, 1, -10)`
4. Set `groundLayer` mask đúng
5. `noScrollBack = true` (chuẩn Mario)

## Thiết Lập Parallax

Tạo các layer nền (Sprite), gán `ParallaxBackground` vào một Empty:
```
Background Manager (Empty)
└── ParallaxBackground component
    ├── Layer 0: Sky     parallaxFactor = 0.05
    ├── Layer 1: Clouds  parallaxFactor = 0.15
    ├── Layer 2: Hills   parallaxFactor = 0.3
    └── Layer 3: Trees   parallaxFactor = 0.5
```

## Mobile Controls UI
```
MobileControls (Panel, Canvas)
├── FloatingJoystick (RectTransform, FloatingJoystick component)
│   ├── Background (Image – vòng tròn ngoài)
│   └── Handle     (Image – điểm chạm)
├── JumpButton  (Button)
├── RunButton   (Button)
└── FireButton  (Button)
```
Gán tất cả vào `InputManager` trong Inspector.

## Object Pool Setup
Add `PoolManager` vào `_GameManager`:
```
Pools:
- tag: "Coin"         prefab: CoinVFX    size: 20
- tag: "Fireball"     prefab: Fireball   size: 5
- tag: "FloatScore"   prefab: ScoreText  size: 10
- tag: "Debris"       prefab: Brick VFX  size: 15
```
