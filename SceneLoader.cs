using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using System.Collections;

/// <summary>
/// Chuyển scene mượt mà với màn hình loading + progress bar.
/// Hỗ trợ màn hình "World X-X" kiểu Mario gốc.
/// </summary>
public class SceneLoader : MonoBehaviour
{
    public static SceneLoader Instance { get; private set; }

    [Header("Loading Screen")]
    public GameObject loadingScreen;
    public Slider progressBar;
    public UnityEngine.UI.Image fadePanel;  // Panel đen để fade in/out
    public TMPro.TextMeshProUGUI worldLabel; // "WORLD 1-1"
    public TMPro.TextMeshProUGUI livesLabel; // "× 3"
    public float fadeDuration = 0.4f;
    public float worldCardDuration = 2f;

    [System.Serializable]
    public class WorldInfo
    {
        public string sceneName;
        public string worldLabel; // "WORLD 1-1"
    }
    public WorldInfo[] worldInfos;

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
        if (fadePanel) fadePanel.color = new Color(0, 0, 0, 0);
    }

    public void LoadScene(string sceneName)
        => StartCoroutine(LoadRoutine(sceneName));

    public void LoadScene(int buildIndex)
        => StartCoroutine(LoadRoutine(SceneManager.GetSceneByBuildIndex(buildIndex).name));

    IEnumerator LoadRoutine(string sceneName)
    {
        // Fade out
        yield return StartCoroutine(Fade(0f, 1f));

        // Hiện World Card
        ShowWorldCard(sceneName);
        yield return new WaitForSeconds(worldCardDuration);

        // Load thật
        loadingScreen?.SetActive(true);
        AsyncOperation op = SceneManager.LoadSceneAsync(sceneName);
        op.allowSceneActivation = false;

        while (op.progress < 0.9f)
        {
            if (progressBar) progressBar.value = op.progress;
            yield return null;
        }
        if (progressBar) progressBar.value = 1f;
        yield return new WaitForSeconds(0.2f);

        op.allowSceneActivation = true;
        loadingScreen?.SetActive(false);

        // Fade in
        yield return StartCoroutine(Fade(1f, 0f));
    }

    IEnumerator Fade(float from, float to)
    {
        if (fadePanel == null) yield break;
        float t = 0;
        while (t < fadeDuration)
        {
            fadePanel.color = new Color(0, 0, 0, Mathf.Lerp(from, to, t / fadeDuration));
            t += Time.deltaTime;
            yield return null;
        }
        fadePanel.color = new Color(0, 0, 0, to);
    }

    void ShowWorldCard(string sceneName)
    {
        var info = System.Array.Find(worldInfos, w => w.sceneName == sceneName);
        if (worldLabel) worldLabel.text = info?.worldLabel ?? sceneName;
        if (livesLabel) livesLabel.text = $"× {GameManager.Instance?.lives ?? 3}";
    }
}
