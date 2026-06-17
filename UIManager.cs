using UnityEngine;
using UnityEngine.UI;
using TMPro;
using System.Collections;

/// <summary>
/// Quản lý toàn bộ UI: HUD, popup, màn hình save/load.
/// </summary>
public class UIManager : MonoBehaviour
{
    public static UIManager Instance { get; private set; }

    [Header("HUD")]
    public TextMeshProUGUI livesText;
    public TextMeshProUGUI scoreText;
    public TextMeshProUGUI coinsText;
    public TextMeshProUGUI timerText;
    public TextMeshProUGUI completionText;
    public TextMeshProUGUI rareItemsText;

    [Header("Message")]
    public TextMeshProUGUI messageText;
    public GameObject messagePanel;

    [Header("Achievement Popup")]
    public GameObject achievementPopup;
    public TextMeshProUGUI achievementTitle;
    public TextMeshProUGUI achievementDesc;

    [Header("Floating Score")]
    public GameObject floatingScorePrefab;
    public Canvas worldCanvas;

    [Header("Save Slot UI")]
    public GameObject saveSlotPanel;
    public SaveSlotButton[] saveSlotButtons; // 3 nút

    [Header("Pause")]
    public GameObject pausePanel;
    private bool isPaused;

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
    }

    void Update()
    {
        if (Input.GetKeyDown(KeyCode.Escape))
            TogglePause();
    }

    // ─── HUD Updates ──────────────────────────────────────────────────────────

    public void UpdateLives(int lives)
        => livesText?.SetText($"× {lives}");

    public void UpdateScore(int score)
        => scoreText?.SetText(score.ToString("D7"));

    public void UpdateCoins(int coins)
        => coinsText?.SetText($"× {coins:D2}");

    public void UpdateTimer(int seconds)
    {
        if (timerText == null) return;
        timerText.SetText(seconds.ToString("D3"));
        timerText.color = seconds <= 100 ? Color.red : Color.white;
    }

    public void UpdateCompletionPercent(float pct)
        => completionText?.SetText($"{pct:F1}%");

    public void UpdateRareItems(int count)
        => rareItemsText?.SetText($"⭐ {count}");

    public void ShowTimeBonus(int bonus)
        => StartCoroutine(ShowTimeBonusRoutine(bonus));

    IEnumerator ShowTimeBonusRoutine(int bonus)
    {
        messagePanel?.SetActive(true);
        messageText?.SetText($"TIME BONUS  +{bonus}");
        yield return new WaitForSeconds(2.5f);
        messagePanel?.SetActive(false);
    }

    // ─── Messages ─────────────────────────────────────────────────────────────

    public void ShowMessage(string msg, float duration)
        => StartCoroutine(MessageRoutine(msg, duration));

    IEnumerator MessageRoutine(string msg, float duration)
    {
        messagePanel?.SetActive(true);
        messageText?.SetText(msg);
        yield return new WaitForSeconds(duration);
        messagePanel?.SetActive(false);
    }

    // ─── Floating Score ───────────────────────────────────────────────────────

    public void ShowFloatingScore(int amount, Vector3 worldPos)
    {
        if (floatingScorePrefab == null || worldCanvas == null) return;
        var go = Instantiate(floatingScorePrefab, worldCanvas.transform);
        go.GetComponentInChildren<TextMeshProUGUI>()?.SetText($"+{amount}");

        // Chuyển vị trí world → canvas
        Vector2 screenPos = Camera.main.WorldToScreenPoint(worldPos + Vector3.up * 0.5f);
        go.GetComponent<RectTransform>().position = screenPos;

        Destroy(go, 1f);
    }

    // ─── Achievement Popup ────────────────────────────────────────────────────

    public void ShowAchievementPopup(string title, string desc)
        => StartCoroutine(AchievementPopupRoutine(title, desc));

    IEnumerator AchievementPopupRoutine(string title, string desc)
    {
        achievementPopup?.SetActive(true);
        achievementTitle?.SetText(title);
        achievementDesc?.SetText(desc);
        yield return new WaitForSeconds(3f);
        achievementPopup?.SetActive(false);
    }

    // ─── Pause Menu ───────────────────────────────────────────────────────────

    void TogglePause()
    {
        isPaused = !isPaused;
        Time.timeScale = isPaused ? 0f : 1f;
        pausePanel?.SetActive(isPaused);
    }

    public void ResumeGame()
    {
        isPaused = false;
        Time.timeScale = 1f;
        pausePanel?.SetActive(false);
    }

    // ─── Save/Load Slot UI ────────────────────────────────────────────────────

    public void OpenSaveMenu()
    {
        saveSlotPanel?.SetActive(true);
        RefreshSlotButtons();
    }

    public void CloseSaveMenu()
        => saveSlotPanel?.SetActive(false);

    void RefreshSlotButtons()
    {
        for (int i = 0; i < saveSlotButtons.Length; i++)
        {
            var info = SaveSystem.Instance?.GetSlotInfo(i);
            saveSlotButtons[i].Refresh(i, info);
        }
    }
}

// ─── Save Slot Button ─────────────────────────────────────────────────────────

public class SaveSlotButton : MonoBehaviour
{
    public TextMeshProUGUI slotLabel;
    public TextMeshProUGUI timestampLabel;
    public TextMeshProUGUI scoreLabel;
    public Button saveButton;
    public Button loadButton;

    private int slotIndex;

    public void Refresh(int index, SaveSystem.SaveData data)
    {
        slotIndex = index;
        slotLabel?.SetText($"Ô {index + 1}");

        if (data == null)
        {
            timestampLabel?.SetText("Trống");
            scoreLabel?.SetText("");
            loadButton.interactable = false;
        }
        else
        {
            timestampLabel?.SetText(data.timestamp);
            scoreLabel?.SetText($"Điểm: {data.score:D7}  Màn: {data.currentScene}");
            loadButton.interactable = true;
        }

        saveButton.onClick.RemoveAllListeners();
        saveButton.onClick.AddListener(() => SaveSystem.Instance?.SaveToSlot(slotIndex));
        loadButton.onClick.RemoveAllListeners();
        loadButton.onClick.AddListener(() => SaveSystem.Instance?.LoadFromSlot(slotIndex));
    }
}
