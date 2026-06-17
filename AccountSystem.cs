using UnityEngine;
using System.Collections;

/// <summary>
/// Hệ thống tài khoản – đăng nhập/đăng ký để quản lý thông tin và chơi online.
/// Tích hợp với Unity Gaming Services (Authentication + Cloud Save).
/// </summary>
public class AccountSystem : MonoBehaviour
{
    public static AccountSystem Instance { get; private set; }

    public bool IsLoggedIn { get; private set; }
    public string PlayerName { get; private set; } = "Guest";
    public string PlayerId  { get; private set; }

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    void Start()
    {
        // Cố gắng tự động đăng nhập ẩn danh khi mở game
        StartCoroutine(AutoSignIn());
    }

    // ─── Sign-In ──────────────────────────────────────────────────────────────

    IEnumerator AutoSignIn()
    {
        UIManager.Instance?.ShowMessage("Đang kết nối...", 2f);
        yield return new WaitForSeconds(1f); // Giả lập network

        // TODO: Thay bằng Unity Authentication:
        // await UnityServices.InitializeAsync();
        // await AuthenticationService.Instance.SignInAnonymouslyAsync();

        IsLoggedIn = true;
        PlayerId = SystemInfo.deviceUniqueIdentifier;
        PlayerName = PlayerPrefs.GetString("PlayerName", "Người Chơi");

        UIManager.Instance?.ShowMessage($"Đã đăng nhập: {PlayerName}", 2f);
        SaveSystem.Instance?.CloudLoad("AutoSave"); // Kéo cloud save về
    }

    public void SignInWithEmail(string email, string password)
    {
        // TODO: Unity Authentication với email/password
        // AuthenticationService.Instance.SignInWithUsernamePasswordAsync(email, password)
        Debug.Log($"[Account] Sign-in attempt: {email}");
    }

    public void SignUp(string name, string email, string password)
    {
        // TODO: AuthenticationService.Instance.SignUpWithUsernamePasswordAsync(email, password)
        PlayerName = name;
        PlayerPrefs.SetString("PlayerName", name);
        Debug.Log($"[Account] Sign-up: {name} / {email}");
    }

    public void SignOut()
    {
        IsLoggedIn = false;
        PlayerName = "Guest";
        UIManager.Instance?.ShowMessage("Đã đăng xuất.", 2f);
    }

    // ─── Online Leaderboard (Stub) ────────────────────────────────────────────

    public void SubmitScore(int score)
    {
        if (!IsLoggedIn) return;
        // TODO: Unity Leaderboards SDK
        // await LeaderboardsService.Instance.AddPlayerScoreAsync("main_board", score);
        Debug.Log($"[Leaderboard] Submitted score {score} for {PlayerName}");
    }

    public void FetchLeaderboard()
    {
        // TODO: var scores = await LeaderboardsService.Instance.GetScoresAsync("main_board");
        Debug.Log("[Leaderboard] Fetching top scores...");
    }
}
