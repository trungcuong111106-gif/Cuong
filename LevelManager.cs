using UnityEngine;
using UnityEngine.Tilemaps;
using System.Collections.Generic;

/// <summary>
/// Quản lý màn chơi: spawn kẻ địch, đặt vật phẩm, mở khóa cổng bí mật,
/// theo dõi tổng xu và % hoàn thành.
/// </summary>
public class LevelManager : MonoBehaviour
{
    public static LevelManager Instance { get; private set; }

    [Header("Level Info")]
    public string levelName;
    public int totalCoinsInLevel = 50;
    public int totalEnemiesInLevel = 10;

    [Header("Spawning")]
    public List<EnemySpawnData> enemySpawns;
    public List<ItemSpawnData>  itemSpawns;

    [Header("Secret Areas")]
    public List<GameObject> secretAreas;  // Khu vực bí mật, ẩn ban đầu

    [Header("Tilemap")]
    public Tilemap groundTilemap;

    // Tracking
    private int coinsCollected;
    private int enemiesDefeated;
    private bool levelStarted;
    private float levelStartTime;

    [System.Serializable]
    public class EnemySpawnData
    {
        public GameObject prefab;
        public Vector2 position;
        public bool spawnOnStart = true;
    }

    [System.Serializable]
    public class ItemSpawnData
    {
        public GameObject prefab;
        public Vector2 position;
    }

    void Awake()
    {
        Instance = this;
    }

    void Start()
    {
        SpawnAll();
        SetCameraBoundsFromTilemap();
        levelStartTime = Time.time;
        levelStarted = true;
    }

    void SpawnAll()
    {
        foreach (var e in enemySpawns)
            if (e.spawnOnStart && e.prefab != null)
                Instantiate(e.prefab, e.position, Quaternion.identity);

        foreach (var i in itemSpawns)
            if (i.prefab != null)
                Instantiate(i.prefab, i.position, Quaternion.identity);
    }

    // ─── Tracking ─────────────────────────────────────────────────────────────

    public void OnCoinCollected()
    {
        coinsCollected++;
        if (coinsCollected >= totalCoinsInLevel)
            AchievementSystem.Instance?.Unlock("all_coins");
    }

    public void OnEnemyDefeated()
    {
        enemiesDefeated++;
        // Mở khu vực bí mật nếu đánh bại đủ quái
        if (enemiesDefeated >= totalEnemiesInLevel / 2 && secretAreas.Count > 0)
            UnlockSecretArea(0);
    }

    public void OnLevelComplete()
    {
        float elapsed = Time.time - levelStartTime;
        AchievementSystem.Instance?.CheckSpeedRun(elapsed);

        bool tookDamage = PlayerController.Instance?.currentState == PlayerState.Small
                        && GameManager.Instance?.lives < 3;
        AchievementSystem.Instance?.CheckNoDamage(!tookDamage);

        float pct = CalculateCompletionPercent();
        AchievementSystem.Instance?.UpdateCompletion(levelName, GameManager.Instance?.score ?? 0, coinsCollected);
    }

    float CalculateCompletionPercent()
    {
        float coinPct   = totalCoinsInLevel > 0 ? (float)coinsCollected / totalCoinsInLevel : 0;
        float enemyPct  = totalEnemiesInLevel > 0 ? (float)enemiesDefeated / totalEnemiesInLevel : 0;
        return (coinPct * 0.5f + enemyPct * 0.3f + 0.2f) * 100f; // 0.2 = hoàn thành màn
    }

    // ─── Secret Areas ─────────────────────────────────────────────────────────

    public void UnlockSecretArea(int index)
    {
        if (index >= secretAreas.Count) return;
        secretAreas[index]?.SetActive(true);
        UIManager.Instance?.ShowMessage("Khu Vực Bí Mật Mở Khóa! 🎉", 2f);
        AudioManager.Instance?.PlaySFX("achievement");
    }

    // ─── Camera Bounds từ Tilemap ─────────────────────────────────────────────

    void SetCameraBoundsFromTilemap()
    {
        if (groundTilemap == null) return;
        Bounds b = groundTilemap.localBounds;
        CameraController.Instance?.SetBounds(
            b.min.x, b.max.x,
            b.min.y, b.max.y + 10f);
    }
}
