using UnityEngine;
using System;
using System.IO;

/// <summary>
/// Hệ thống lưu game:
/// - Auto-save mỗi khi qua checkpoint hoặc hoàn thành màn.
/// - 3 ô lưu thủ công (Save Slots).
/// - Đồng bộ Cloud Save qua PlayerPrefs (có thể thay bằng Unity Gaming Services).
/// </summary>
public class SaveSystem : MonoBehaviour
{
    public static SaveSystem Instance { get; private set; }

    private const string AUTO_SAVE_KEY = "AutoSave";
    private const string SLOT_KEY_PREFIX = "SaveSlot_";
    private const int TOTAL_SLOTS = 3;

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    // ─── Data Structure ───────────────────────────────────────────────────────

    [Serializable]
    public class SaveData
    {
        public int lives;
        public int score;
        public int coins;
        public string currentScene;
        public bool hasCheckpoint;
        public float checkpointX, checkpointY;
        public float completionPercent;
        public int totalCoinsCollected;
        public string timestamp;
        public string[] unlockedAchievements;
    }

    SaveData CaptureCurrentState()
    {
        var gm = GameManager.Instance;
        var achieve = AchievementSystem.Instance;
        return new SaveData
        {
            lives = gm?.lives ?? 3,
            score = gm?.score ?? 0,
            coins = gm?.coins ?? 0,
            currentScene = UnityEngine.SceneManagement.SceneManager.GetActiveScene().name,
            completionPercent = achieve?.GetCompletionPercent() ?? 0f,
            totalCoinsCollected = achieve?.totalCoinsCollected ?? 0,
            timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm"),
            unlockedAchievements = achieve?.GetUnlockedAchievements() ?? new string[0]
        };
    }

    // ─── Auto Save ────────────────────────────────────────────────────────────

    public void AutoSave()
    {
        var data = CaptureCurrentState();
        string json = JsonUtility.ToJson(data, true);
        PlayerPrefs.SetString(AUTO_SAVE_KEY, json);
        PlayerPrefs.Save();
        Debug.Log("[SaveSystem] Auto-saved: " + data.timestamp);
        CloudSave(AUTO_SAVE_KEY, json);
    }

    public void LoadGame()
    {
        string json = PlayerPrefs.GetString(AUTO_SAVE_KEY, null);
        if (!string.IsNullOrEmpty(json))
            ApplySaveData(JsonUtility.FromJson<SaveData>(json));
    }

    // ─── Manual Save Slots ────────────────────────────────────────────────────

    public void SaveToSlot(int slot)
    {
        if (slot < 0 || slot >= TOTAL_SLOTS) return;
        var data = CaptureCurrentState();
        string json = JsonUtility.ToJson(data, true);
        string key = SLOT_KEY_PREFIX + slot;
        PlayerPrefs.SetString(key, json);
        PlayerPrefs.Save();
        UIManager.Instance?.ShowMessage($"Đã lưu vào Ô {slot + 1}", 2f);
        CloudSave(key, json);
    }

    public void LoadFromSlot(int slot)
    {
        if (slot < 0 || slot >= TOTAL_SLOTS) return;
        string json = PlayerPrefs.GetString(SLOT_KEY_PREFIX + slot, null);
        if (string.IsNullOrEmpty(json))
        {
            UIManager.Instance?.ShowMessage("Ô lưu trống!", 1.5f);
            return;
        }
        ApplySaveData(JsonUtility.FromJson<SaveData>(json));
    }

    public SaveData GetSlotInfo(int slot)
    {
        string json = PlayerPrefs.GetString(SLOT_KEY_PREFIX + slot, null);
        return string.IsNullOrEmpty(json) ? null : JsonUtility.FromJson<SaveData>(json);
    }

    void ApplySaveData(SaveData data)
    {
        if (data == null) return;
        var gm = GameManager.Instance;
        if (gm != null)
        {
            gm.lives = data.lives;
            gm.score = data.score;
            gm.coins = data.coins;
        }
        AchievementSystem.Instance?.RestoreAchievements(data.unlockedAchievements);
        Debug.Log($"[SaveSystem] Loaded save from {data.timestamp} | Scene: {data.currentScene}");
    }

    // ─── Cloud Save (Stub – thay bằng Unity Gaming Services) ─────────────────

    void CloudSave(string key, string json)
    {
        // TODO: Thay thế bằng Unity Cloud Save SDK thực tế:
        // await CloudSaveService.Instance.Data.Player.SaveAsync(new Dictionary<string,object>{{ key, json }});
        Debug.Log($"[CloudSave] Syncing key '{key}' to cloud... (stub)");
        // Hiện tại giả lập bằng file local
        string path = Path.Combine(Application.persistentDataPath, key + ".json");
        File.WriteAllText(path, json);
    }

    public void CloudLoad(string key)
    {
        string path = Path.Combine(Application.persistentDataPath, key + ".json");
        if (File.Exists(path))
        {
            string json = File.ReadAllText(path);
            ApplySaveData(JsonUtility.FromJson<SaveData>(json));
            UIManager.Instance?.ShowMessage("Đã đồng bộ từ Cloud!", 2f);
        }
        else
        {
            UIManager.Instance?.ShowMessage("Không có dữ liệu Cloud.", 2f);
        }
    }

    public void DeleteSlot(int slot)
    {
        PlayerPrefs.DeleteKey(SLOT_KEY_PREFIX + slot);
        PlayerPrefs.Save();
    }
}
