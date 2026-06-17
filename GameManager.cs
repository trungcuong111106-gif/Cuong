using UnityEngine;
using UnityEngine.SceneManagement;
using System.Collections;

/// <summary>
/// Quản lý toàn bộ trạng thái game: mạng, điểm, xu, checkpoint, hồi sinh.
/// </summary>
public class GameManager : MonoBehaviour
{
    public static GameManager Instance { get; private set; }

    [Header("Lives & Score")]
    public int lives = 3;
    public int score = 0;
    public int coins = 0;
    public int coinsForExtraLife = 100;

    [Header("Respawn")]
    public float respawnDelay = 3f;
    public Transform defaultSpawnPoint;
    private Vector3 checkpointPosition;
    private bool hasCheckpoint;

    [Header("Time")]
    public float levelTime = 300f;
    private float timeRemaining;
    private bool timerRunning;

    void Awake()
    {
        if (Instance != null && Instance != this)
        {
            Destroy(gameObject);
            return;
        }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    void Start()
    {
        timeRemaining = levelTime;
        timerRunning = true;
        UIManager.Instance?.UpdateLives(lives);
        UIManager.Instance?.UpdateScore(score);
        UIManager.Instance?.UpdateCoins(coins);
        SaveSystem.Instance?.LoadGame(); // Auto-load nếu có save
    }

    void Update()
    {
        if (timerRunning && timeRemaining > 0)
        {
            timeRemaining -= Time.deltaTime;
            UIManager.Instance?.UpdateTimer(Mathf.CeilToInt(timeRemaining));
            if (timeRemaining <= 0) OnTimeUp();
        }
    }

    // ─── Score & Coins ────────────────────────────────────────────────────────

    public void AddScore(int amount)
    {
        score += amount;
        UIManager.Instance?.UpdateScore(score);
        UIManager.Instance?.ShowFloatingScore(amount,
            PlayerController.Instance?.transform.position ?? Vector3.zero);
    }

    public void CollectCoin(int amount = 1)
    {
        coins += amount;
        score += 200 * amount;
        UIManager.Instance?.UpdateCoins(coins);
        UIManager.Instance?.UpdateScore(score);

        if (coins >= coinsForExtraLife)
        {
            coins -= coinsForExtraLife;
            GainExtraLife();
            UIManager.Instance?.UpdateCoins(coins);
        }
    }

    public void GainExtraLife()
    {
        lives++;
        AudioManager.Instance?.PlaySFX("1up");
        UIManager.Instance?.UpdateLives(lives);
        UIManager.Instance?.ShowMessage("1UP!", 2f);
    }

    // ─── Lives & Death ────────────────────────────────────────────────────────

    public void OnPlayerDied()
    {
        timerRunning = false;
        lives--;
        UIManager.Instance?.UpdateLives(lives);
        SaveSystem.Instance?.AutoSave();

        if (lives <= 0)
        {
            StartCoroutine(GameOver());
        }
        else
        {
            StartCoroutine(RespawnPlayer());
        }
    }

    IEnumerator RespawnPlayer()
    {
        yield return new WaitForSeconds(respawnDelay);
        // Hồi sinh tại checkpoint hoặc điểm đầu màn
        Vector3 spawnPos = hasCheckpoint ? checkpointPosition
                                        : defaultSpawnPoint.position;
        var player = PlayerController.Instance;
        if (player != null)
        {
            player.transform.position = spawnPos;
            // Reset về Small state
            SceneManager.LoadScene(SceneManager.GetActiveScene().buildIndex);
        }
        timeRemaining = levelTime;
        timerRunning = true;
    }

    IEnumerator GameOver()
    {
        UIManager.Instance?.ShowMessage("GAME OVER", 3f);
        yield return new WaitForSeconds(3f);
        // Reset tất cả
        lives = 3;
        score = 0;
        coins = 0;
        hasCheckpoint = false;
        SceneManager.LoadScene("TitleScreen");
    }

    void OnTimeUp()
    {
        timerRunning = false;
        PlayerController.Instance?.TakeDamage();
    }

    // ─── Checkpoint ───────────────────────────────────────────────────────────

    public void SetCheckpoint(Vector3 position)
    {
        if (hasCheckpoint) return; // Chỉ lưu checkpoint một lần
        hasCheckpoint = true;
        checkpointPosition = position;
        AudioManager.Instance?.PlaySFX("checkpoint");
        UIManager.Instance?.ShowMessage("Checkpoint!", 1.5f);
    }

    // ─── Level Complete ───────────────────────────────────────────────────────

    public void LevelComplete()
    {
        timerRunning = false;
        AudioManager.Instance?.PlayMusic("levelClear");
        StartCoroutine(LevelClearSequence());
    }

    IEnumerator LevelClearSequence()
    {
        // Tính điểm thời gian còn lại
        int timeBonus = Mathf.CeilToInt(timeRemaining) * 50;
        UIManager.Instance?.ShowTimeBonus(timeBonus);
        score += timeBonus;
        UIManager.Instance?.UpdateScore(score);

        // Cập nhật % hoàn thành
        AchievementSystem.Instance?.UpdateCompletion(
            SceneManager.GetActiveScene().name, score, coins);

        SaveSystem.Instance?.AutoSave();
        yield return new WaitForSeconds(4f);

        int nextSceneIndex = SceneManager.GetActiveScene().buildIndex + 1;
        if (nextSceneIndex < SceneManager.sceneCountInBuildSettings)
        {
            hasCheckpoint = false;
            SceneManager.LoadScene(nextSceneIndex);
        }
        else
        {
            SceneManager.LoadScene("EndScreen");
        }
    }
}
