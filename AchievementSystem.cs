using UnityEngine;
using System.Collections.Generic;
using System.Linq;

/// <summary>
/// Hệ thống thành tích:
/// - % hoàn thành màn chơi
/// - Số lượng vật phẩm hiếm đã thu thập
/// - Mở khóa thành tích đặc biệt
/// </summary>
public class AchievementSystem : MonoBehaviour
{
    public static AchievementSystem Instance { get; private set; }

    [System.Serializable]
    public class Achievement
    {
        public string id;
        public string title;
        public string description;
        public bool unlocked;
        public Sprite icon;
    }

    [Header("Achievements")]
    public List<Achievement> achievements = new List<Achievement>
    {
        new Achievement { id = "first_star",    title = "Ngôi Sao Đầu Tiên",  description = "Thu thập ngôi sao đầu tiên" },
        new Achievement { id = "coin_100",      title = "Triệu Phú Xu",        description = "Thu thập 100 xu" },
        new Achievement { id = "no_damage",     title = "Không Sứt Mẻ",        description = "Hoàn thành màn không bị thương" },
        new Achievement { id = "speed_run",     title = "Thần Tốc",            description = "Hoàn thành màn trong 60 giây" },
        new Achievement { id = "all_coins",     title = "Thu Thập Hoàn Hảo",   description = "Nhặt hết xu trong màn" },
        new Achievement { id = "all_levels",    title = "Chinh Phục Tất Cả",   description = "Hoàn thành toàn bộ màn chơi" },
    };

    [Header("Progress")]
    public int totalCoinsCollected;
    public int totalStarsCollected;
    public Dictionary<string, float> levelCompletionPercents = new Dictionary<string, float>();
    public int totalLevels = 8;

    private HashSet<string> unlockedIds = new HashSet<string>();

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    // ─── Completion % ─────────────────────────────────────────────────────────

    public void UpdateCompletion(string levelName, int score, int coinsInLevel)
    {
        // Tính % dựa trên xu, điểm và thành tích
        float pct = 0f;
        pct += Mathf.Min(coinsInLevel / 50f, 1f) * 50f;  // Tối đa 50% từ xu
        pct += Mathf.Min(score / 10000f, 1f) * 30f;       // Tối đa 30% từ điểm
        pct += 20f;                                         // 20% cho việc hoàn thành

        levelCompletionPercents[levelName] = Mathf.Min(pct, 100f);
        UIManager.Instance?.UpdateCompletionPercent(GetCompletionPercent());
        CheckAllLevelsComplete();
    }

    public float GetCompletionPercent()
    {
        if (levelCompletionPercents.Count == 0) return 0f;
        float total = levelCompletionPercents.Values.Sum();
        return total / totalLevels;
    }

    // ─── Rare Items ───────────────────────────────────────────────────────────

    public void OnStarCollected()
    {
        totalStarsCollected++;
        if (totalStarsCollected >= 1) Unlock("first_star");
        UIManager.Instance?.UpdateRareItems(totalStarsCollected);
    }

    public void OnCoinCollected()
    {
        totalCoinsCollected++;
        if (totalCoinsCollected >= 100) Unlock("coin_100");
        if (GameManager.Instance?.coins >= 50) Unlock("all_coins");
    }

    // ─── Unlock ───────────────────────────────────────────────────────────────

    public void Unlock(string id)
    {
        if (unlockedIds.Contains(id)) return;
        var achieve = achievements.Find(a => a.id == id);
        if (achieve == null) return;

        achieve.unlocked = true;
        unlockedIds.Add(id);
        UIManager.Instance?.ShowAchievementPopup(achieve.title, achieve.description);
        AudioManager.Instance?.PlaySFX("achievement");
        SaveSystem.Instance?.AutoSave();
    }

    void CheckAllLevelsComplete()
    {
        if (levelCompletionPercents.Count >= totalLevels &&
            levelCompletionPercents.Values.All(p => p >= 100f))
        {
            Unlock("all_levels");
        }
    }

    public void CheckSpeedRun(float timeUsed)
    {
        if (timeUsed <= 60f) Unlock("speed_run");
    }

    public void CheckNoDamage(bool tookDamage)
    {
        if (!tookDamage) Unlock("no_damage");
    }

    // ─── Restore ──────────────────────────────────────────────────────────────

    public void RestoreAchievements(string[] ids)
    {
        if (ids == null) return;
        foreach (var id in ids)
        {
            unlockedIds.Add(id);
            var a = achievements.Find(x => x.id == id);
            if (a != null) a.unlocked = true;
        }
    }

    public string[] GetUnlockedAchievements() => unlockedIds.ToArray();
}
