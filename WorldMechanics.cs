using UnityEngine;
using System.Collections;

// ─── MOVING PLATFORM ─────────────────────────────────────────────────────────
public class MovingPlatform : MonoBehaviour
{
    public Transform[] waypoints;
    public float speed = 2f;
    private int currentTarget;

    void Update()
    {
        if (waypoints.Length < 2) return;
        transform.position = Vector3.MoveTowards(
            transform.position, waypoints[currentTarget].position, speed * Time.deltaTime);
        if (Vector3.Distance(transform.position, waypoints[currentTarget].position) < 0.05f)
            currentTarget = (currentTarget + 1) % waypoints.Length;
    }

    // Kéo người chơi theo platform
    void OnCollisionEnter2D(Collision2D col)
    {
        if (col.gameObject.CompareTag("Player"))
            col.transform.SetParent(transform);
    }
    void OnCollisionExit2D(Collision2D col)
    {
        if (col.gameObject.CompareTag("Player"))
            col.transform.SetParent(null);
    }
}

// ─── WARP PIPE ────────────────────────────────────────────────────────────────
public class WarpPipe : MonoBehaviour
{
    public string destinationScene;
    public Vector2 exitPosition;
    public bool requiresDown = true; // Phải nhấn xuống để vào

    void OnTriggerStay2D(Collider2D col)
    {
        if (!col.CompareTag("Player")) return;
        bool pressDown = Input.GetAxisRaw("Vertical") < -0.5f;
        if (requiresDown && !pressDown) return;

        AudioManager.Instance?.PlaySFX("pipe");
        StartCoroutine(WarpPlayer(col.gameObject));
    }

    IEnumerator WarpPlayer(GameObject player)
    {
        player.GetComponent<PlayerController>().enabled = false;
        yield return new WaitForSeconds(0.5f);
        if (!string.IsNullOrEmpty(destinationScene))
            UnityEngine.SceneManagement.SceneManager.LoadScene(destinationScene);
    }
}

// ─── GOAL FLAG ────────────────────────────────────────────────────────────────
public class GoalFlag : MonoBehaviour
{
    public Transform flagPole;
    public Transform flagObject;
    public float slideDuration = 1.5f;
    private bool triggered;

    void OnTriggerEnter2D(Collider2D col)
    {
        if (!col.CompareTag("Player") || triggered) return;
        triggered = true;
        StartCoroutine(FlagSlideDown(col.gameObject));
    }

    IEnumerator FlagSlideDown(GameObject player)
    {
        player.GetComponent<PlayerController>().enabled = false;
        AudioManager.Instance?.PlaySFX("flagpole");

        // Tính điểm thưởng dựa trên vị trí người chơi trên cột cờ
        float heightRatio = (flagPole.position.y - player.transform.position.y)
                            / flagPole.localScale.y;
        int bonus = Mathf.RoundToInt((1f - Mathf.Clamp01(heightRatio)) * 5000);
        GameManager.Instance?.AddScore(bonus);

        // Cờ tụt xuống
        Vector3 flagTop = flagObject.position;
        Vector3 flagBottom = flagPole.position + Vector3.down * 4f;
        float t = 0;
        while (t < slideDuration)
        {
            flagObject.position = Vector3.Lerp(flagTop, flagBottom, t / slideDuration);
            t += Time.deltaTime;
            yield return null;
        }

        yield return new WaitForSeconds(0.5f);
        GameManager.Instance?.LevelComplete();
    }
}

// ─── CHECKPOINT FLAG ──────────────────────────────────────────────────────────
public class CheckpointFlag : MonoBehaviour
{
    private bool activated;
    private Animator animator;
    void Awake() => animator = GetComponent<Animator>();

    void OnTriggerEnter2D(Collider2D col)
    {
        if (!col.CompareTag("Player") || activated) return;
        activated = true;
        animator?.SetTrigger("Activate");
        GameManager.Instance?.SetCheckpoint(transform.position);
    }
}

// ─── BREAKABLE BRICK ─────────────────────────────────────────────────────────
public class BreakableBrick : MonoBehaviour
{
    public GameObject[] debrisPrefabs;

    void OnCollisionEnter2D(Collision2D col)
    {
        if (!col.CompareTag("Player")) return;
        float relY = col.transform.position.y - transform.position.y;
        if (relY < -0.4f) // Đập từ bên dưới
        {
            var player = col.gameObject.GetComponent<PlayerController>();
            if (player?.currentState == PlayerState.Small)
            {
                // Mario nhỏ → block rung nhưng không vỡ
                StartCoroutine(BumpAnim());
                AudioManager.Instance?.PlaySFX("blockHit");
            }
            else
            {
                Break();
            }
        }
    }

    void Break()
    {
        AudioManager.Instance?.PlaySFX("breakBlock");
        GameManager.Instance?.AddScore(50);
        foreach (var p in debrisPrefabs)
            Instantiate(p, transform.position, Random.rotation);
        Destroy(gameObject);
    }

    IEnumerator BumpAnim()
    {
        Vector3 orig = transform.position;
        transform.position += Vector3.up * 0.12f;
        yield return new WaitForSeconds(0.08f);
        transform.position = orig;
    }
}

// ─── COIN BLOCK (Hidden) ──────────────────────────────────────────────────────
public class HiddenCoinBlock : MonoBehaviour
{
    public int coinsToSpawn = 5;
    public float spawnInterval = 0.12f;
    private bool triggered;

    void OnCollisionEnter2D(Collision2D col)
    {
        if (triggered || !col.CompareTag("Player")) return;
        float relY = col.transform.position.y - transform.position.y;
        if (relY < -0.4f)
        {
            triggered = true;
            StartCoroutine(SpawnCoins());
        }
    }

    IEnumerator SpawnCoins()
    {
        for (int i = 0; i < coinsToSpawn; i++)
        {
            GameManager.Instance?.CollectCoin(1);
            AudioManager.Instance?.PlaySFX("coin");
            yield return new WaitForSeconds(spawnInterval);
        }
        // Thành khối trống
        GetComponent<Animator>()?.SetBool("IsEmpty", true);
    }
}
